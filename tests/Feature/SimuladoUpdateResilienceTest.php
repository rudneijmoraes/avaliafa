<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladoUpdateResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_update_simulado_without_triggering_server_error(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Update Simulado',
            'slug' => 'sistema-update-simulado',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Simulado antigo',
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
            'slug' => 'simulado-antigo',
            'name' => 'Simulado antigo',
            'description' => 'Descrição antiga',
            'status' => 'draft',
            'capture_photo_enabled' => false,
            'webcam_enabled' => true,
            'fullscreen_enabled' => true,
            'show_result_immediately' => true,
            'auto_email_enabled' => true,
            'moodle_integration_enabled' => false,
            'settings' => [],
        ]);

        $response = $this->actingAs($professor)->put(route('simulados.update', $simulado), [
            'name' => 'Simulado INSS atualizado',
            'description' => 'Descrição atualizada',
            'status' => 'active',
            'duration_minutes' => 90,
            'passing_score' => 70,
            'max_violations' => 5,
            'max_attempts' => 0,
            'shuffle_questions' => '1',
            'shuffle_choices' => '1',
            'capture_photo_enabled' => '0',
            'webcam_enabled' => '1',
            'fullscreen_enabled' => '1',
            'show_result_immediately' => '1',
            'auto_email_enabled' => '1',
            'moodle_integration_enabled' => '0',
            'notify_participants_on_save' => '1',
            'public_hub_slug' => 'concurso-inss',
            'weekly_label' => 'Semana 2',
            'branding_watermark_text' => 'Anasps',
        ]);

        $response->assertRedirect(route('simulados.show', $simulado));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('simulados', [
            'id' => $simulado->id,
            'name' => 'Simulado INSS atualizado',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Simulado INSS atualizado',
            'duration_minutes' => 90,
        ]);

        $simulado->refresh();
        $exam->refresh();

        $this->assertSame('Anasps', data_get($simulado->settings ?? [], 'branding_watermark_text'));
        $this->assertTrue((bool) data_get($simulado->settings ?? [], 'notify_participants_on_save'));
        $this->assertSame('Anasps', data_get($exam->settings ?? [], 'branding_watermark_text'));
    }
}
