<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorSessionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_includes_graded_sessions_in_payload(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Monitor',
            'slug' => 'sistema-monitor',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'email' => 'professor-monitor@example.test',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'name' => 'Aluno Monitor',
            'email' => 'aluno-monitor@example.test',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Monitor',
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
            'status' => 'graded',
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(5),
            'final_score' => 9.25,
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('monitor.exam', $exam->id));

        $response->assertOk();
        $response->assertSee('"id":'.$session->id, false);
        $response->assertSee('"student_name":"Aluno Monitor"', false);
        $response->assertSee('"status":"graded"', false);
        $response->assertSee('"final_score":"9.25"', false);
    }

    public function test_monitor_prioritizes_started_or_graded_sessions_over_pending_attempts(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Monitor Prioridade',
            'slug' => 'sistema-monitor-prioridade',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'name' => 'Aluno Prioridade',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Monitor Prioridade',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $pendingSession = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'pending',
        ]);

        $gradedSession = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 2,
            'status' => 'graded',
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(2),
            'final_score' => 14.5,
        ]);

        $response = $this
            ->actingAs($professor)
            ->get(route('monitor.exam', $exam->id));

        $response->assertOk();
        $response->assertSeeInOrder([
            '"id":'.$gradedSession->id,
            '"id":'.$pendingSession->id,
        ], false);
        // Auditoria não abre automaticamente; o usuário deve clicar
        $response->assertSee('this.selectedSessionId = null', false);
        $response->assertSee('"status":"graded"', false);
    }
}
