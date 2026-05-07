<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSimulationAttemptsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_simulation_attempts_kpi(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Dashboard',
            'slug' => 'sistema-dashboard',
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
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Simulada',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
            'settings' => [],
        ]);

        ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'is_simulation' => true,
            'final_score' => 7.2,
            'raw_score' => 7.2,
            'grade_published' => true,
        ]);

        ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 2,
            'status' => 'graded',
            'is_simulation' => true,
            'final_score' => 8.4,
            'raw_score' => 8.4,
            'grade_published' => true,
        ]);

        ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 3,
            'status' => 'graded',
            'is_simulation' => false,
            'final_score' => 9.1,
            'raw_score' => 9.1,
            'grade_published' => true,
        ]);

        $this->actingAs($professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tentativas de Simulado')
            ->assertSee('data-kpi-simulation-attempts="2"', false);
    }
}

