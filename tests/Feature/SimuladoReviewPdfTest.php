<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use App\Services\SimuladoReviewPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladoReviewPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_pdf_service_uses_randomized_question_and_choice_order_and_correct_metrics(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor('Sistema PDF');
        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'active' => true,
        ]);

        $simulado = $this->createSimulado($system, $professor, 'Simulado PDF');
        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'user_id' => $student->id,
            'first_name' => 'Emilly',
            'last_name' => 'Messias',
            'cpf' => '06212345693',
            'email' => 'emilly@example.com',
            'phone' => '61999990005',
        ]);

        [$questionOne, $questionTwo] = $this->createQuestionsForExam($simulado->exam_id, $system->id, $professor->id);

        $questionOneChoices = $questionOne->choices()->orderBy('order')->get()->values();
        $questionTwoChoices = $questionTwo->choices()->orderBy('order')->get()->values();

        $session = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'raw_score' => 1,
            'final_score' => 1,
            'question_order' => [$questionTwo->id, $questionOne->id],
            'choice_order' => [
                $questionOne->id => [$questionOneChoices[1]->id, $questionOneChoices[0]->id],
                $questionTwo->id => [$questionTwoChoices[1]->id, $questionTwoChoices[0]->id],
            ],
            'submitted_at' => now()->subMinute(),
            'grade_published' => true,
        ]);

        Answer::query()->create([
            'session_id' => $session->id,
            'question_id' => $questionOne->id,
            'choice_id' => $questionOneChoices[0]->id,
            'is_correct' => true,
            'score' => 1,
        ]);

        Answer::query()->create([
            'session_id' => $session->id,
            'question_id' => $questionTwo->id,
            'choice_id' => $questionTwoChoices[0]->id,
            'is_correct' => false,
            'score' => 0,
        ]);

        $registration = SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'exam_session_id' => $session->id,
            'status' => 'completed',
            'registered_at' => now()->subHour(),
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinute(),
            'raw_score' => 1,
            'final_score' => 1,
            'total_correct' => 1,
            'total_wrong' => 1,
            'percentage_correct' => 50,
        ]);

        $reviewData = app(SimuladoReviewPdfService::class)->build($registration);

        $this->assertSame([$questionTwo->id, $questionOne->id], collect($reviewData['questions'])->pluck('id')->all());
        $this->assertSame(
            [$questionTwoChoices[1]->id, $questionTwoChoices[0]->id],
            collect($reviewData['questions'][0]['choices'])->pluck('id')->all()
        );
        $this->assertSame(
            [$questionOneChoices[1]->id, $questionOneChoices[0]->id],
            collect($reviewData['questions'][1]['choices'])->pluck('id')->all()
        );
        $this->assertSame(1, $reviewData['correct_count']);
        $this->assertSame(1, $reviewData['wrong_count']);
        $this->assertSame(2, $reviewData['total_count']);
        $this->assertSame(50.0, $reviewData['percentage']);
    }

    public function test_simulation_result_page_hides_avaliafa_details_button(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor('Sistema Resultado');
        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado Resultado',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'raw_score' => 14,
            'final_score' => 14,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinute(),
            'grade_published' => true,
        ]);

        $response = $this->actingAs($student)->get(route('exam.result', $session));

        $response->assertOk();
        $response->assertDontSee('Ver detalhes no AvaliaFA');
    }

    /**
     * @return array{0: ClientSystem, 1: User}
     */
    private function createSystemAndProfessor(string $name): array
    {
        $slug = strtolower(str_replace(' ', '-', $name));

        $system = ClientSystem::query()->create([
            'name' => $name,
            'slug' => $slug,
            'client_id' => $slug,
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        return [$system, $professor];
    }

    private function createSimulado(ClientSystem $system, User $professor, string $title): Simulado
    {
        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => $title,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 6,
            'settings' => [],
        ]);

        return Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => str($title)->slug()->value(),
            'name' => $title,
            'description' => $title.' descrição',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => false,
            'fullscreen_enabled' => false,
            'show_result_immediately' => true,
            'auto_email_enabled' => false,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);
    }

    /**
     * @return array{0: Question, 1: Question}
     */
    private function createQuestionsForExam(int $examId, int $systemId, int $creatorId): array
    {
        $questionOne = Question::query()->create([
            'client_system_id' => $systemId,
            'created_by' => $creatorId,
            'type' => 'multiple_choice',
            'content' => 'Questão 1',
            'difficulty' => 'medium',
            'active' => true,
        ]);

        $questionTwo = Question::query()->create([
            'client_system_id' => $systemId,
            'created_by' => $creatorId,
            'type' => 'multiple_choice',
            'content' => 'Questão 2',
            'difficulty' => 'medium',
            'active' => true,
        ]);

        Choice::query()->create([
            'question_id' => $questionOne->id,
            'content' => 'Q1 Alternativa A',
            'is_correct' => true,
            'order' => 1,
        ]);

        Choice::query()->create([
            'question_id' => $questionOne->id,
            'content' => 'Q1 Alternativa B',
            'is_correct' => false,
            'order' => 2,
        ]);

        Choice::query()->create([
            'question_id' => $questionTwo->id,
            'content' => 'Q2 Alternativa A',
            'is_correct' => false,
            'order' => 1,
        ]);

        Choice::query()->create([
            'question_id' => $questionTwo->id,
            'content' => 'Q2 Alternativa B',
            'is_correct' => true,
            'order' => 2,
        ]);

        ExamQuestion::query()->create([
            'exam_id' => $examId,
            'question_id' => $questionOne->id,
            'order' => 1,
            'weight' => 1,
        ]);

        ExamQuestion::query()->create([
            'exam_id' => $examId,
            'question_id' => $questionTwo->id,
            'order' => 2,
            'weight' => 1,
        ]);

        return [$questionOne->fresh('choices'), $questionTwo->fresh('choices')];
    }
}
