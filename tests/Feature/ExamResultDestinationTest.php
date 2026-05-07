<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamResultDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_result_page_uses_lti_return_url_when_available(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Certificadora',
            'slug' => 'certificadora',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Prova Final',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://certificacao.faculdadeanaspsead.com.br/moodle',
            'client_id' => 'lti-client',
            'deployment_id' => 'deployment-result',
            'platform_name' => 'Moodle Certificadora',
            'active' => true,
        ]);

        $resourceLink = LtiResourceLink::query()->create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-result',
            'settings' => [
                'launch_presentation' => [
                    'return_url' => 'https://certificacao.faculdadeanaspsead.com.br/moodle/grade/report/overview/index.php',
                ],
            ],
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
            'lti_registration_id' => $registration->id,
            'lti_resource_link_id' => $resourceLink->id,
            'attempt_number' => 1,
            'status' => 'graded',
        ]);

        $this->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee('Ver minhas notas no Moodle')
            ->assertSee('https://certificacao.faculdadeanaspsead.com.br/moodle/grade/report/overview/index.php', false);
    }

    public function test_result_page_falls_back_to_course_grade_report_when_system_has_moodle_course(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Certificadora',
            'slug' => 'certificadora',
            'active' => true,
            'moodle_config' => [
                'certifier_url' => 'https://certificacao.faculdadeanaspsead.com.br/moodle',
                'token' => 'token-123',
                'course_id' => 0,
            ],
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Prova Moodle',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [
                'moodle' => [
                    'course_id' => 321,
                ],
            ],
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
        ]);

        $this->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee('https://certificacao.faculdadeanaspsead.com.br/moodle/grade/report/user/index.php?id=321', false);
    }

    public function test_simulation_result_primary_button_points_to_user_dashboard(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Simulados',
            'slug' => 'sistema-simulados',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Simulado de Revisao',
            'status' => 'active',
            'duration_minutes' => 45,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [
                'simulado' => [
                    'show_result_immediately' => true,
                ],
            ],
        ]);

        Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'slug' => 'simulado-revisao',
            'name' => 'Simulado Revisao',
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
            'attempt_number' => 2,
            'status' => 'graded',
            'is_simulation' => true,
            'final_score' => 7.5,
            'raw_score' => 7.5,
            'grade_published' => true,
            'started_at' => now()->subMinutes(30),
            'submitted_at' => now()->subMinutes(1),
        ]);

        $this->actingAs($student)
            ->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee(route('dashboard'), false)
            ->assertSee('Voltar para meu dashboard')
            ->assertSee('https://filie-se.anasps.org.br/', false);
    }

    public function test_simulation_result_primary_button_points_to_auto_login_entry_for_guest(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Simulados',
            'slug' => 'sistema-simulados-guest',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Simulado Guest',
            'status' => 'active',
            'duration_minutes' => 45,
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
            'status' => 'graded',
            'is_simulation' => true,
            'token_jti' => 'result-guest-token-jti',
        ]);

        $this->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee(route('exam.enter-dashboard', $session), false)
            ->assertSee('Entrar no sistema');
    }

    public function test_enter_dashboard_logs_in_student_when_exam_session_is_valid(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Simulados',
            'slug' => 'sistema-simulados-login',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Simulado Login',
            'status' => 'active',
            'duration_minutes' => 45,
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
            'status' => 'graded',
            'is_simulation' => true,
            'token_jti' => 'enter-dashboard-token-jti',
        ]);

        $response = $this
            ->withSession([
                'exam_session_id' => $session->id,
                'exam_session_jwt' => $session->token_jti,
            ])
            ->get(route('exam.enter-dashboard', $session));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }
}
