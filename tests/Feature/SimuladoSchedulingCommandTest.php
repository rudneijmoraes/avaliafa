<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Simulado;
use App\Models\SimuladoEmailTemplate;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\User;
use App\Services\SimuladoEmailService;
use App\Services\SimuladoSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SimuladoSchedulingCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_service_activates_scheduled_simulado_and_marks_notification(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor();
        $template = SimuladoEmailTemplate::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'name' => 'Aviso de disponibilidade',
            'subject' => 'Simulado {{nome_simulado}} disponivel',
            'html_body' => '<p>Ola {{primeiro_nome}}, o simulado {{nome_simulado}} esta disponivel.</p>',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado Agendado',
            'status' => 'draft',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'settings' => [],
        ]);

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'template_id' => $template->id,
            'slug' => 'simulado-agendado',
            'name' => 'Simulado Agendado',
            'status' => 'scheduled',
            'auto_email_enabled' => true,
        ]);

        $participant = SimuladoParticipant::query()->create([
            'client_system_id' => $system->id,
            'first_name' => 'Aluno',
            'last_name' => 'Agendado',
            'cpf' => '12345678901',
            'email' => 'aluno@example.com',
        ]);

        SimuladoRegistration::query()->create([
            'simulado_id' => $simulado->id,
            'participant_id' => $participant->id,
            'status' => 'registered',
            'registered_at' => now()->subDay(),
        ]);

        $emailService = Mockery::mock(SimuladoEmailService::class);
        $emailService->shouldReceive('sendAvailabilityNotification')
            ->once()
            ->andReturn(1);
        $result = (new SimuladoSchedulingService($emailService))->process();

        $this->assertSame(1, $result['activated']);
        $this->assertSame(0, $result['deactivated']);
        $this->assertSame(1, $result['emails_sent']);
        $this->assertSame('active', $simulado->fresh()->status);
        $this->assertSame('active', $exam->fresh()->status);
        $this->assertNotNull($simulado->fresh()->activation_notified_at);
    }

    public function test_command_inactivates_expired_active_simulado(): void
    {
        [$system, $professor] = $this->createSystemAndProfessor();

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado Encerrado',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'settings' => [],
        ]);

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => 'simulado-encerrado',
            'name' => 'Simulado Encerrado',
            'status' => 'active',
            'auto_email_enabled' => false,
        ]);

        $this->artisan('simulados:process-schedule')
            ->expectsOutputToContain('Inativados: 1')
            ->assertExitCode(0);

        $this->assertSame('inactive', $simulado->fresh()->status);
        $this->assertSame('draft', $exam->fresh()->status);
    }

    private function createSystemAndProfessor(): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Agenda',
            'slug' => 'sistema-agenda',
            'client_id' => 'sistema-agenda',
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
}
