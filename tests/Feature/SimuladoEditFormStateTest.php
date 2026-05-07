<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladoEditFormStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_uses_saved_exam_shuffle_flags(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Form',
            'slug' => 'sistema-form',
            'client_id' => 'sistema-form',
            'client_secret' => 'secret',
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
            'title' => 'Simulado Form',
            'status' => 'draft',
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
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => 'simulado-form',
            'name' => 'Simulado Form',
            'status' => 'draft',
            'settings' => ['notify_participants_on_save' => true],
        ]);

        $response = $this->actingAs($professor)->get(route('simulados.edit', $simulado));

        $response->assertOk();

        $html = $response->getContent();
        preg_match('/<input type="checkbox" name="shuffle_questions" value="1"[^>]*>/', $html, $shuffleQuestionsInput);
        preg_match('/<input type="checkbox" name="shuffle_choices" value="1"[^>]*>/', $html, $shuffleChoicesInput);
        preg_match('/<input type="checkbox" name="notify_participants_on_save" value="1"[^>]*>/', $html, $notifyOnSaveInput);

        $this->assertNotEmpty($shuffleQuestionsInput);
        $this->assertNotEmpty($shuffleChoicesInput);
        $this->assertNotEmpty($notifyOnSaveInput);
        $this->assertStringNotContainsString('checked', $shuffleQuestionsInput[0]);
        $this->assertStringNotContainsString('checked', $shuffleChoicesInput[0]);
        $this->assertStringContainsString('checked', $notifyOnSaveInput[0]);
    }
}
