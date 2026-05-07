<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SimuladoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_simulado_page_loads_successfully_for_professor(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '98765432100',
            'active' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'user_id' => $student->id,
            'first_name' => 'Aluno',
            'last_name' => 'Teste',
            'email' => 'aluno.teste@example.com',
            'phone' => '61999990000',
            'cpf' => '98765432100',
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($professor)->get(route('simulados.show', $simulado));

        $response->assertOk();
        $response->assertSee($simulado->name);
    }

    public function test_simulados_index_displays_crud_actions_as_icons(): void
    {
        [$professor] = $this->createSimuladoForProfessor();

        $response = $this->actingAs($professor)->get(route('simulados.index'));

        $response->assertOk();
        $response->assertSee('aria-label="Abrir simulado"', false);
        $response->assertSee('aria-label="Editar simulado"', false);
        $response->assertSee('aria-label="Excluir simulado"', false);
    }

    public function test_professor_can_delete_simulado_from_crud(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $response = $this
            ->actingAs($professor)
            ->delete(route('simulados.destroy', $simulado));

        $response->assertRedirect(route('simulados.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('simulados', ['id' => $simulado->id]);
        $this->assertSame(
            'archived',
            DB::table('exams')->where('id', $simulado->exam_id)->value('status')
        );
    }

    public function test_simulados_index_computes_simulation_attempts_for_admin_dashboard(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '55555555555',
            'active' => true,
        ]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'final_score' => 7.0,
            'raw_score' => 7.0,
            'grade_published' => true,
        ]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 2,
            'status' => 'graded',
            'is_simulation' => true,
            'final_score' => 8.0,
            'raw_score' => 8.0,
            'grade_published' => true,
        ]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 3,
            'status' => 'graded',
            'is_simulation' => false,
            'final_score' => 9.0,
            'raw_score' => 9.0,
            'grade_published' => true,
        ]);

        $response = $this->actingAs($professor)->get(route('simulados.index'));

        $response->assertOk();
        $response->assertSee('data-simulado-attempts="2"', false);
    }

    public function test_professor_can_export_personalized_participant_pdf(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '77777777777',
            'active' => true,
            'first_name' => 'Maria',
            'last_name' => 'Silva',
            'email' => 'maria.silva@example.com',
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'user_id' => $student->id,
            'first_name' => 'Maria',
            'last_name' => 'Silva',
            'email' => 'maria.silva@example.com',
            'phone' => '61999990000',
            'cpf' => '77777777777',
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'status' => 'completed',
            'registered_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('simulados.participants.export.pdf', [$simulado, $participant]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_export_excel_ignores_deleted_participants(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $activeParticipant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'first_name' => 'Ativo',
            'last_name' => 'Participante',
            'email' => 'ativo@example.com',
            'phone' => '61999991111',
            'cpf' => '11111111111',
        ]);

        $deletedParticipant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'first_name' => 'Excluido',
            'last_name' => 'Participante',
            'email' => 'excluido@example.com',
            'phone' => '61999992222',
            'cpf' => '22222222222',
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $activeParticipant->id,
            'status' => 'completed',
            'registered_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(10),
            'final_score' => 7.5,
            'percentage_correct' => 75,
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $deletedParticipant->id,
            'status' => 'completed',
            'registered_at' => now()->subMinutes(40),
            'completed_at' => now()->subMinutes(30),
            'final_score' => 8.0,
            'percentage_correct' => 80,
        ]);

        $deletedParticipant->delete();

        $response = $this
            ->actingAs($professor)
            ->get(route('simulados.export.excel', $simulado));

        $response->assertOk();

        $xml = $response->streamedContent();

        $this->assertStringContainsString('Ativo Participante', $xml);
        $this->assertStringNotContainsString('Excluido Participante', $xml);
    }

    public function test_student_can_retry_simulado_from_personal_area_and_create_new_attempt(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();
        $simulado->loadMissing('exam');
        $this->attachQuestionToExam($simulado->exam, $professor);

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '33333333333',
            'active' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'user_id' => $student->id,
            'first_name' => 'Aluno',
            'last_name' => 'Reteste',
            'email' => 'aluno.reteste@example.com',
            'phone' => '61999993333',
            'cpf' => '33333333333',
        ]);

        $firstSession = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'raw_score' => 6.0,
            'final_score' => 6.0,
            'grade_published' => true,
        ]);

        $registration = SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'exam_session_id' => $firstSession->id,
            'status' => 'completed',
            'registered_at' => now()->subHour(),
            'started_at' => now()->subMinutes(50),
            'completed_at' => now()->subMinutes(20),
            'final_score' => 6.0,
            'percentage_correct' => 60.0,
        ]);

        $retryResponse = $this->actingAs($student)
            ->post(route('simulados.retry', $simulado));

        $retryResponse->assertRedirect();
        $this->assertStringContainsString('/exam/start?token=', $retryResponse->headers->get('Location', ''));

        $latestSession = ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $student->id)
            ->orderByDesc('attempt_number')
            ->first();

        $this->assertNotNull($latestSession);
        $this->assertSame(2, (int) $latestSession->attempt_number);
        $this->assertSame('pending', $latestSession->status);
        $this->assertTrue((bool) $latestSession->is_simulation);

        $registration->refresh();
        $this->assertSame($latestSession->id, (int) $registration->exam_session_id);
        $this->assertSame('in_progress', $registration->status);

        $areaResponse = $this->actingAs($student)
            ->get(route('simulados.minha-area'));

        $areaResponse->assertOk();
        $areaResponse->assertSee('Fazer novamente');
        $areaResponse->assertViewHas('attemptsByRegistrationId', function ($map) use ($registration) {
            return (int) ($map[$registration->id] ?? 0) === 2;
        });
    }

    public function test_student_cannot_retry_when_simulado_attempt_limit_is_reached(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();
        $simulado->loadMissing('exam');
        $this->attachQuestionToExam($simulado->exam, $professor);

        $simulado->update([
            'settings' => array_merge($simulado->settings ?? [], [
                'max_attempts' => 2,
            ]),
        ]);

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '66666666666',
            'active' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'user_id' => $student->id,
            'first_name' => 'Aluno',
            'last_name' => 'Limite',
            'email' => 'aluno.limite@example.com',
            'phone' => '61999996666',
            'cpf' => '66666666666',
        ]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
        ]);

        $lastSession = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 2,
            'status' => 'graded',
            'is_simulation' => true,
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'exam_session_id' => $lastSession->id,
            'status' => 'completed',
            'registered_at' => now()->subHour(),
            'started_at' => now()->subMinutes(50),
            'completed_at' => now()->subMinutes(15),
        ]);

        $retryResponse = $this->actingAs($student)
            ->post(route('simulados.retry', $simulado));

        $retryResponse->assertRedirect(route('simulados.minha-area'));
        $retryResponse->assertSessionHas('error');

        $totalAttempts = ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $student->id)
            ->count();

        $this->assertSame(2, $totalAttempts);

        $areaResponse = $this->actingAs($student)
            ->get(route('simulados.minha-area'));

        $areaResponse->assertOk();
        $areaResponse->assertSee('Limite atingido');
    }

    public function test_crm_view_includes_total_attempts_per_participant(): void
    {
        [$professor, $simulado] = $this->createSimuladoForProfessor();

        $student = User::factory()->create([
            'client_system_id' => $professor->client_system_id,
            'role' => 'student',
            'cpf' => '44444444444',
            'active' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $professor->client_system_id,
            'user_id' => $student->id,
            'first_name' => 'Aluno',
            'last_name' => 'CRM',
            'email' => 'aluno.crm@example.com',
            'phone' => '61999994444',
            'cpf' => '44444444444',
        ]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'raw_score' => 5.0,
            'final_score' => 5.0,
            'grade_published' => true,
        ]);

        $secondSession = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 2,
            'status' => 'in_progress',
            'is_simulation' => true,
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'exam_session_id' => $secondSession->id,
            'status' => 'in_progress',
            'registered_at' => now()->subHour(),
            'started_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($professor)->get(route('simulados.show', $simulado));

        $response->assertOk();
        $response->assertSee('Tentativas');
        $response->assertViewHas('provasFeitasPorParticipante', function ($map) use ($participant) {
            return (int) ($map[$participant->id] ?? 0) === 2;
        });
    }

    /**
     * @return array{0: User, 1: Simulado}
     */
    private function createSimuladoForProfessor(): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Simulados',
            'slug' => 'sistema-simulados',
            'client_id' => 'system-simulados',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '12345678901',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado de Gestão',
            'status' => 'draft',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => 'simulado-gestao',
            'name' => 'Simulado Gestão',
            'description' => 'Simulado para testes',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => true,
            'fullscreen_enabled' => true,
            'show_result_immediately' => true,
            'auto_email_enabled' => true,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);

        return [$professor, $simulado];
    }

    private function attachQuestionToExam(Exam $exam, User $professor): void
    {
        $question = Question::query()->create([
            'client_system_id' => $exam->client_system_id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Pergunta de validacao do simulado',
            'difficulty' => 'easy',
        ]);

        Choice::query()->insert([
            [
                'question_id' => $question->id,
                'content' => 'Alternativa correta',
                'is_correct' => true,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'question_id' => $question->id,
                'content' => 'Alternativa incorreta',
                'is_correct' => false,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $exam->questions()->attach($question->id, ['order' => 1]);
    }
}
