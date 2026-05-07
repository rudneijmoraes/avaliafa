<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\EmailLog;
use App\Models\SimuladoEmailTemplate;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Services\SimuladoEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailLogRecordingTest extends TestCase
{
    use RefreshDatabase;

    private function makeSystem(): ClientSystem
    {
        return ClientSystem::create([
            'name' => 'Sistema Teste', 'slug' => 'sis-teste',
            'client_id' => 'cid-t', 'client_secret' => 'sec-t', 'active' => true,
        ]);
    }

    public function test_send_result_email_records_sent_log_on_success(): void
    {
        Mail::fake();

        $system = $this->makeSystem();
        $admin = \App\Models\User::factory()->create([
            'client_system_id' => $system->id,
            'role'             => 'admin',
        ]);
        $template = SimuladoEmailTemplate::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'name'             => 'Template Resultado',
            'subject'          => 'Seu resultado',
            'html_body'        => '<p>Olá {{primeiro_nome}}</p>',
            'active'           => true,
        ]);

        $exam = \App\Models\Exam::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'title'            => 'Prova Teste',
            'status'           => 'active',
            'time_limit'       => 60,
        ]);
        $simulado = \App\Models\Simulado::create([
            'exam_id'          => $exam->id,
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'template_id'      => $template->id,
            'slug'             => 'simulado-teste-result',
            'name'             => 'Simulado Teste',
            'status'           => 'active',
        ]);
        $participant = SimuladoParticipant::create([
            'client_system_id' => $system->id,
            'first_name' => 'João',
            'last_name'  => 'Silva',
            'email'      => 'joao@example.com',
            'cpf'        => '12345678901',
        ]);
        $registration = SimuladoRegistration::create([
            'simulado_id'    => $simulado->id,
            'participant_id' => $participant->id,
            'status'         => 'completed',
        ]);

        (new SimuladoEmailService())->sendResultEmail($registration);

        $this->assertDatabaseHas('email_logs', [
            'type'            => 'resultado',
            'recipient_email' => 'joao@example.com',
            'simulado_id'     => $simulado->id,
            'status'          => 'sent',
        ]);
    }

    public function test_send_result_email_records_failed_log_on_exception(): void
    {
        Mail::shouldReceive('html')->andThrow(new \Exception('SMTP timeout'));

        $system = $this->makeSystem();
        $admin = \App\Models\User::factory()->create([
            'client_system_id' => $system->id,
            'role'             => 'admin',
        ]);
        $template = SimuladoEmailTemplate::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'name'             => 'Template',
            'subject'          => 'Resultado',
            'html_body'        => '<p>Olá</p>',
            'active'           => true,
        ]);
        $exam = \App\Models\Exam::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'title'            => 'Prova Teste',
            'status'           => 'active',
            'time_limit'       => 60,
        ]);
        $simulado = \App\Models\Simulado::create([
            'exam_id'          => $exam->id,
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'template_id'      => $template->id,
            'slug'             => 'simulado-fail',
            'name'             => 'Simulado Fail',
            'status'           => 'active',
        ]);
        $participant = SimuladoParticipant::create([
            'client_system_id' => $system->id,
            'first_name' => 'Maria',
            'last_name'  => 'Souza',
            'email'      => 'maria@example.com',
            'cpf'        => '98765432100',
        ]);
        $registration = SimuladoRegistration::create([
            'simulado_id'    => $simulado->id,
            'participant_id' => $participant->id,
            'status'         => 'completed',
        ]);

        (new SimuladoEmailService())->sendResultEmail($registration);

        $this->assertDatabaseHas('email_logs', [
            'type'            => 'resultado',
            'recipient_email' => 'maria@example.com',
            'status'          => 'failed',
            'error_message'   => 'SMTP timeout',
        ]);
    }

    public function test_broadcast_to_all_students_records_log_per_recipient(): void
    {
        Mail::fake();

        $system = $this->makeSystem();
        $admin = \App\Models\User::factory()->create([
            'client_system_id' => $system->id,
            'role'             => 'admin',
        ]);
        $template = SimuladoEmailTemplate::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'name'             => 'Template Broadcast',
            'subject'          => 'Novo simulado',
            'html_body'        => '<p>Olá {{primeiro_nome}}</p>',
            'active'           => true,
        ]);
        $exam = \App\Models\Exam::create([
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'title'            => 'Prova BC',
            'status'           => 'active',
            'time_limit'       => 60,
        ]);
        $simulado = \App\Models\Simulado::create([
            'exam_id'          => $exam->id,
            'client_system_id' => $system->id,
            'created_by'       => $admin->id,
            'template_id'      => $template->id,
            'slug'             => 'simulado-bc',
            'name'             => 'Simulado BC',
            'status'           => 'active',
        ]);

        \App\Models\User::factory()->count(3)->create([
            'client_system_id' => $system->id,
            'role'  => 'student',
            'email' => fn() => fake()->unique()->safeEmail(),
        ]);

        (new SimuladoEmailService())->broadcastToAllStudents($simulado);

        $this->assertEquals(3, EmailLog::ofType('broadcast')->sent()->count());
    }
}
