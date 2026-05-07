<?php

namespace Tests\Feature;

use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Simulado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimuladoPortalNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_receives_available_simulado_in_portal_feed(): void
    {
        [$student, $simulado] = $this->createAvailableSimuladoScenario();

        $response = $this->actingAs($student)->getJson(route('simulados.notifications', ['after_id' => 0]));

        $response->assertOk();
        $response->assertJsonPath('latest_id', $simulado->id);
        $response->assertJsonPath('items.0.id', $simulado->id);
        $response->assertJsonPath('items.0.name', 'Simulado INSS Semana 2');
    }

    public function test_student_feed_respects_after_id(): void
    {
        [$student, $simulado] = $this->createAvailableSimuladoScenario();

        $response = $this->actingAs($student)->getJson(route('simulados.notifications', ['after_id' => $simulado->id]));

        $response->assertOk();
        $response->assertJsonPath('latest_id', $simulado->id);
        $response->assertJsonCount(0, 'items');
    }

    public function test_non_student_receives_empty_simulado_notification_feed(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Portal',
            'slug' => 'sistema-portal',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $response = $this->actingAs($professor)->getJson(route('simulados.notifications', ['after_id' => 0]));

        $response->assertOk();
        $response->assertExactJson([
            'items' => [],
            'latest_id' => 0,
        ]);
    }

    private function createAvailableSimuladoScenario(): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Notificacoes',
            'slug' => 'sistema-notificacoes',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
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
            'title' => 'Simulado INSS Semana 2',
            'status' => 'active',
            'duration_minutes' => 60,
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
            'content' => 'Pergunta do novo simulado',
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

        $simulado = Simulado::query()->create([
            'exam_id' => $exam->id,
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'slug' => 'simulado-inss-semana-2',
            'name' => 'Simulado INSS Semana 2',
            'description' => 'Novo simulado disponível',
            'status' => 'active',
            'capture_photo_enabled' => false,
            'webcam_enabled' => true,
            'fullscreen_enabled' => true,
            'show_result_immediately' => true,
            'auto_email_enabled' => true,
            'moodle_integration_enabled' => false,
            'settings' => [
                'weekly_label' => 'Semana 2',
            ],
        ]);

        return [$student, $simulado];
    }
}
