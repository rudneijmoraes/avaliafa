<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrdAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enforces_question_visibility_scope_in_question_index(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Graduação',
            'slug' => 'graduacao',
            'client_id' => 'client-grad',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $viewer = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '11111111111',
            'active' => true,
        ]);

        $author = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '22222222222',
            'active' => true,
        ]);

        Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $author->id,
            'type' => 'multiple_choice',
            'content' => 'Questão Privada Oculta',
            'difficulty' => 'easy',
            'active' => true,
            'owner_department' => 'Direito',
            'visibility_scope' => 'private',
        ]);

        Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $author->id,
            'type' => 'multiple_choice',
            'content' => 'Questão Department Oculta',
            'difficulty' => 'easy',
            'active' => true,
            'owner_department' => 'Direito',
            'visibility_scope' => 'department',
        ]);

        Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $author->id,
            'type' => 'multiple_choice',
            'content' => 'Questão System Visível',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $response = $this->actingAs($viewer)->get(route('questoes.index'));

        $response->assertOk();
        $response->assertSee('Questão System Visível');
        $response->assertDontSee('Questão Privada Oculta');
        $response->assertDontSee('Questão Department Oculta');
    }

    public function test_it_records_grade_updated_when_manual_grade_is_adjusted(): void
    {
        $system = ClientSystem::query()->create([
            'name' => 'Pós',
            'slug' => 'pos',
            'client_id' => 'client-pos',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $admin = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'admin',
            'cpf' => '33333333333',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => '44444444444',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $admin->id,
            'title' => 'Prova de Auditoria',
            'description' => null,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 7,
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'raw_score' => 4.50,
            'final_score' => 4.50,
            'passed' => false,
            'grade_published' => true,
            'is_simulation' => false,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('relatorios.prova.grade.update', [$exam, $session]),
            ['final_score' => 8.85]
        );

        $response->assertRedirect();

        $session->refresh();
        $this->assertSame('8.85', (string) $session->final_score);
        $this->assertTrue($session->passed);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'grade.updated',
            'auditable_type' => ExamSession::class,
            'auditable_id' => $session->id,
            'client_system_id' => $system->id,
            'user_id' => $admin->id,
        ]);

        $log = AuditLog::query()->where('action', 'grade.updated')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(4.5, (float) ($log->old_values['final_score'] ?? 0));
        $this->assertSame(8.85, (float) ($log->new_values['final_score'] ?? 0));
    }
}
