<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentGradesPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_login_redirects_to_student_grades_panel(): void
    {
        $student = $this->createStudent(cpf: '12345678901', password: '123456');

        $response = $this->post(route('login.submit'), [
            'cpf' => '123.456.789-01',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('student.grades.index'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_dashboard_redirects_authenticated_student_to_student_grades_panel(): void
    {
        $student = $this->createStudent(cpf: '98765432100', password: '654321');

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertRedirect(route('student.grades.index'));
    }

    public function test_student_grades_index_lists_only_current_student_graded_sessions(): void
    {
        [$student, $visibleSession] = $this->createCompletedSession('Direito Previdenciario I');
        [, $hiddenOwnPending] = $this->createSessionForStudent($student, 'Sessao ainda sem nota', status: 'submitted', finalScore: null);
        [$otherStudent, $hiddenOtherSession] = $this->createCompletedSession('Matematica Financeira');

        $response = $this->actingAs($student)->get(route('student.grades.index'));

        $response->assertOk();
        $response->assertSee('Direito Previdenciario I');
        $response->assertDontSee('Sessao ainda sem nota');
        $response->assertDontSee('Matematica Financeira');
        $response->assertSee((string) $visibleSession->final_score);
        $this->assertNotSame($student->id, $otherStudent->id);
        $this->assertNotNull($hiddenOtherSession);
        $this->assertNotNull($hiddenOwnPending);
    }

    public function test_student_grades_show_returns_not_found_for_other_students_session(): void
    {
        [$student] = $this->createCompletedSession('Gestao Publica');
        [, $otherSession] = $this->createCompletedSession('Auditoria Interna');

        $response = $this->actingAs($student)->get(route('student.grades.show', $otherSession));

        $response->assertNotFound();
    }

    public function test_student_grades_show_displays_answered_questions_and_selected_choices(): void
    {
        [$student, $session, $question, $selectedChoice] = $this->createCompletedSessionWithAnswer();

        $response = $this->actingAs($student)->get(route('student.grades.show', $session));

        $response->assertOk();
        $response->assertSee($session->exam->title);
        $response->assertSee($question->content, false);
        $response->assertSee($selectedChoice->content);
        $response->assertSee((string) $session->final_score);
    }

    public function test_student_dashboard_displays_restart_link_for_simulation_attempts(): void
    {
        [$student, $session, $simulado] = $this->createCompletedSimulationSession('Simulado Previdencia');

        $response = $this->actingAs($student)->get(route('student.grades.index'));

        $response->assertOk();
        $response->assertSee('Reiniciar simulado');
        $response->assertSee(route('simulados.public.inscricao', $simulado->slug), false);
        $response->assertSee($session->exam->title);
    }

    private function createStudent(string $cpf, string $password): User
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Aluno '.substr($cpf, -3),
            'slug' => 'sistema-aluno-'.substr($cpf, -3),
            'active' => true,
        ]);

        return User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => $cpf,
            'password' => Hash::make($password),
            'active' => true,
        ]);
    }

    private function createCompletedSession(string $examTitle): array
    {
        $student = $this->createStudent(fake()->numerify('###########'), 'password123');

        [, $session] = $this->createSessionForStudent($student, $examTitle, status: 'graded', finalScore: 8.25);

        return [$student, $session];
    }

    private function createSessionForStudent(User $student, string $examTitle, string $status, ?float $finalScore): array
    {
        $teacher = User::factory()->create([
            'client_system_id' => $student->client_system_id,
            'role' => 'professor',
            'cpf' => fake()->numerify('###########'),
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $student->client_system_id,
            'created_by' => $teacher->id,
            'title' => $examTitle,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => $status,
            'started_at' => now()->subMinutes(47),
            'submitted_at' => $finalScore !== null ? now()->subMinutes(5) : null,
            'final_score' => $finalScore,
            'raw_score' => $finalScore,
            'grade_published' => $finalScore !== null,
        ]);

        return [$exam, $session];
    }

    private function createCompletedSessionWithAnswer(): array
    {
        $student = $this->createStudent('11222333444', 'abc123');
        $teacher = User::factory()->create([
            'client_system_id' => $student->client_system_id,
            'role' => 'professor',
            'cpf' => '99888777666',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $student->client_system_id,
            'created_by' => $teacher->id,
            'title' => 'Controle Externo',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $question = Question::query()->create([
            'client_system_id' => $student->client_system_id,
            'created_by' => $teacher->id,
            'type' => 'multiple_choice',
            'content' => '<p>Qual orgao julga as contas do Executivo?</p>',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $selectedChoice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Tribunal de Contas',
            'is_correct' => true,
            'order' => 1,
        ]);

        Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Ministerio Publico',
            'is_correct' => false,
            'order' => 2,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 1]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'started_at' => now()->subMinutes(38),
            'submitted_at' => now()->subMinutes(4),
            'final_score' => 9.2,
            'raw_score' => 9.2,
            'grade_published' => true,
        ]);

        Answer::query()->create([
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $selectedChoice->id,
            'is_correct' => true,
            'score' => 1,
        ]);

        return [$student, $session, $question, $selectedChoice];
    }

    private function createCompletedSimulationSession(string $examTitle): array
    {
        $student = $this->createStudent('33444555666', 'abc123');

        $teacher = User::factory()->create([
            'client_system_id' => $student->client_system_id,
            'role' => 'professor',
            'cpf' => '11222333000',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $student->client_system_id,
            'created_by' => $teacher->id,
            'title' => $examTitle,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $student->client_system_id,
            'created_by' => $teacher->id,
            'slug' => 'simulado-previdencia',
            'name' => 'Simulado Previdencia',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => false,
            'fullscreen_enabled' => false,
            'show_result_immediately' => true,
            'auto_email_enabled' => false,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 3,
            'status' => 'graded',
            'is_simulation' => true,
            'started_at' => now()->subMinutes(32),
            'submitted_at' => now()->subMinutes(4),
            'final_score' => 8.1,
            'raw_score' => 8.1,
            'grade_published' => true,
        ]);

        return [$student, $session, $simulado];
    }
}
