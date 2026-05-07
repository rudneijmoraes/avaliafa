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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SimuladoPublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_participant_user_registration_and_session(): void
    {
        $simulado = $this->createActiveSimulado();
        $cpf = '52998224725';
        $captchaToken = 'token-teste';
        $captchaAnswer = 9;

        $response = $this
            ->withSession(['simulado_captcha.'.$captchaToken => $captchaAnswer])
            ->post(route('simulados.public.inscricao.store', $simulado->slug), [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'email' => 'joao.silva@demo.com',
                'phone' => '(61) 99999-0000',
                'cpf' => $cpf,
                'robot_confirm' => '1',
                'captcha_token' => $captchaToken,
                'captcha_answer' => $captchaAnswer,
            ]);

        $response->assertRedirect();
        $this->assertStringContainsString('/exam/start?token=', $response->headers->get('Location', ''));

        $participant = SimuladoParticipant::query()
            ->where('client_system_id', $simulado->client_system_id)
            ->where('cpf', $cpf)
            ->first();

        $this->assertNotNull($participant);
        $this->assertSame('João', $participant->first_name);
        $this->assertSame('Silva', $participant->last_name);

        $student = User::query()
            ->where('client_system_id', $simulado->client_system_id)
            ->where('cpf', $cpf)
            ->first();

        $this->assertNotNull($student);
        $this->assertSame('student', $student->role);
        $this->assertTrue(Hash::check($cpf, (string) $student->password));

        $registration = SimuladoRegistration::query()
            ->where('simulado_id', $simulado->id)
            ->where('participant_id', $participant->id)
            ->first();

        $this->assertNotNull($registration);
        $this->assertSame('in_progress', $registration->status);
        $this->assertNotNull($registration->started_at);
        $this->assertNotNull($registration->exam_session_id);

        $session = $registration->examSession;
        $this->assertNotNull($session);
        $this->assertSame($simulado->exam_id, $session->exam_id);
        $this->assertSame($student->id, $session->student_id);
        $this->assertTrue((bool) $session->is_simulation);
        $this->assertSame('simulado', $session->launch_source);
        $this->assertSame('pending', $session->status);
    }

    public function test_public_registration_page_displays_simulados_anasps_identity_and_robot_validation(): void
    {
        $simulado = $this->createActiveSimulado();

        $this->get(route('simulados.public.inscricao', $simulado->slug))
            ->assertOk()
            ->assertSee('Simulados Anasps')
            ->assertSee('logo-anasps.png')
            ->assertSee('Não sou robô');
    }

    public function test_public_registration_blocks_new_attempt_when_limit_is_reached(): void
    {
        $simulado = $this->createActiveSimulado();
        $simulado->update([
            'settings' => array_merge($simulado->settings ?? [], [
                'max_attempts' => 1,
            ]),
        ]);

        $cpf = '12312312387';
        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $simulado->client_system_id,
            'first_name' => 'Aluno',
            'last_name' => 'Limite',
            'email' => 'aluno.limite.publico@example.com',
            'phone' => '61999990001',
            'cpf' => $cpf,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $simulado->client_system_id,
            'role' => 'student',
            'cpf' => $cpf,
            'active' => true,
        ]);

        $participant->update(['user_id' => $student->id]);

        ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
        ]);

        $captchaToken = 'token-limite';
        $captchaAnswer = 7;

        $response = $this
            ->withSession(['simulado_captcha.'.$captchaToken => $captchaAnswer])
            ->post(route('simulados.public.inscricao.store', $simulado->slug), [
                'first_name' => 'Aluno',
                'last_name' => 'Limite',
                'email' => 'aluno.limite.publico@example.com',
                'phone' => '(61) 99999-0001',
                'cpf' => $cpf,
                'robot_confirm' => '1',
                'captcha_token' => $captchaToken,
                'captcha_answer' => $captchaAnswer,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertSame(
            1,
            ExamSession::query()
                ->where('exam_id', $simulado->exam_id)
                ->where('student_id', $student->id)
                ->count()
        );
    }

    private function createActiveSimulado(): Simulado
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Simulado',
            'slug' => 'sistema-simulado',
            'client_id' => 'client-simulado',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '11111111111',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado de Captação',
            'description' => null,
            'status' => 'active',
            'duration_minutes' => 30,
            'max_violations' => 3,
            'webcam_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 6,
            'settings' => [],
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Pergunta do simulado',
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

        return Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => 'simulado-captacao',
            'name' => 'Simulado Captação',
            'description' => 'Simulado público',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => true,
            'fullscreen_enabled' => true,
            'show_result_immediately' => true,
            'auto_email_enabled' => true,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);
    }
}
