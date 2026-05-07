<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamQuestion;
use App\Models\Question;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRecentSimuladosTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_panel_shows_recent_unfinished_simulados(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Aluno',
            'slug' => 'sistema-aluno',
            'client_id' => 'sistema-aluno',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => '12345678901',
            'active' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'user_id' => $student->id,
            'first_name' => 'Aluno',
            'last_name' => 'Teste',
            'cpf' => '12345678901',
            'email' => 'aluno@example.com',
        ]);

        $pendingSimulado = $this->createSimuladoWithQuestion($system->id, $professor->id, 'Simulado Recente');
        $finishedSimulado = $this->createSimuladoWithQuestion($system->id, $professor->id, 'Simulado Concluido');

        SimuladoRegistration::query()->create([
            'simulado_id' => $finishedSimulado->id,
            'participant_id' => $participant->id,
            'status' => 'completed',
            'registered_at' => now()->subDay(),
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($student)->get(route('student.grades.index'));

        $response->assertOk();
        $response->assertSee('Simulados recentes para voce');
        $response->assertSee('Simulado Recente');
        $response->assertDontSee('Simulado Concluido');
        $response->assertSee(route('simulados.public.inscricao', $pendingSimulado->slug), false);
    }

    private function createSimuladoWithQuestion(int $systemId, int $creatorId, string $title): Simulado
    {
        $exam = Exam::query()->create([
            'client_system_id' => $systemId,
            'created_by' => $creatorId,
            'title' => $title,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $question = Question::query()->create([
            'client_system_id' => $systemId,
            'created_by' => $creatorId,
            'type' => 'multiple_choice',
            'content' => $title.' Questao',
            'difficulty' => 'medium',
            'active' => true,
        ]);

        ExamQuestion::query()->create([
            'exam_id' => $exam->id,
            'question_id' => $question->id,
            'order' => 1,
            'weight' => 1,
        ]);

        return Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $systemId,
            'created_by' => $creatorId,
            'slug' => str($title)->slug()->value(),
            'name' => $title,
            'description' => $title.' descricao',
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
}
