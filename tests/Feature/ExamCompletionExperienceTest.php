<?php

namespace Tests\Feature;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamCompletionExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_result_page_uses_neutral_completion_copy(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/result.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Obrigado por concluir sua prova', $contents);
        $this->assertStringContainsString('A correção e a divulgação serão realizadas pelos canais oficiais da instituição.', $contents);
        $this->assertStringNotContainsString('Aprovado!', $contents);
        $this->assertStringNotContainsString('Nao aprovado', $contents);
        $this->assertStringNotContainsString('Certificado emitido!', $contents);
        $this->assertStringNotContainsString('Baixar', $contents);
    }

    public function test_exam_result_page_uses_wide_two_column_layout_on_desktop(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/result.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.result-shell', $contents);
        $this->assertStringContainsString('.result-grid', $contents);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);', $contents);
        $this->assertStringContainsString('.result-side', $contents);
    }

    public function test_exam_result_page_displays_final_score_and_time_spent_immediately(): void
    {
        $session = $this->createGradedSession(finalScore: 8.47, startedMinutesAgo: 58, submittedMinutesAgo: 13);

        $this->get(route('exam.result', $session))
            ->assertOk()
            ->assertSee('Nota final')
            ->assertSee('Tempo gasto')
            ->assertSee('data-final-score="8.47"', false)
            ->assertSee('45min 00s');
    }

    private function createGradedSession(float $finalScore, int $startedMinutesAgo, int $submittedMinutesAgo): ExamSession
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Resultado',
            'slug' => 'sistema-resultado',
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
            'title' => 'Prova de Encerramento',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        return ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'started_at' => now()->subMinutes($startedMinutesAgo),
            'submitted_at' => now()->subMinutes($submittedMinutesAgo),
            'final_score' => $finalScore,
            'raw_score' => $finalScore,
            'grade_published' => true,
        ]);
    }
}
