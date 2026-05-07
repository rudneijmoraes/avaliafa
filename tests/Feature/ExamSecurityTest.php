<?php

namespace Tests\Feature;

use App\Jobs\RecordSecurityEvent;
use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ExamSecurityTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Show page — Security gate & meta tags
    // -------------------------------------------------------------------------

    public function test_show_page_renders_security_gate_overlay(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('id="security-gate"', false);
        $response->assertSee('Ativando Modo Seguro');
        $response->assertSee('Ativar Tela Cheia e Iniciar');
    }

    public function test_show_page_renders_api_base_meta_tag(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('data-api-base="', false);
        $response->assertSee('name="avalia-fa-exam"', false);
    }

    public function test_show_page_uses_one_minute_snapshot_interval_by_default(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('data-snapshot-interval="60000"', false);
    }

    public function test_show_page_uses_configured_snapshot_interval_from_exam_settings(): void
    {
        [$session] = $this->createInProgressSession();
        $session->exam->update([
            'settings' => array_merge($session->exam->settings ?? [], ['snapshot_interval_seconds' => 90]),
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('data-snapshot-interval="90000"', false);
    }

    public function test_show_page_renders_branding_watermark_when_configured(): void
    {
        [$session] = $this->createInProgressSession([
            'settings' => [
                'branding_watermark_text' => 'Anasps',
            ],
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('question-watermark', false);
        $response->assertSee('Anasps');
    }

    public function test_show_page_renders_exit_button(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('Sair da Prova');
        $response->assertSee('confirmExit', false);
    }

    public function test_show_page_renders_exit_confirmation_modal(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('Deseja sair da prova?');
        $response->assertSee('Sair e Entregar');
        $response->assertSee('voluntary_exit', false);
    }

    public function test_show_page_includes_webcam_gate_when_webcam_enabled(): void
    {
        [$session] = $this->createInProgressSession(['webcam_enabled' => true]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('webcam-preview', false);
        $response->assertSee('__avaliaWebcamStream', false);
    }

    public function test_security_gate_notifies_engine_before_webcam_snapshots_start(): void
    {
        $showContents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $engineContents = file_get_contents(base_path('resources/js/secure-exam-engine.js'));

        $this->assertNotFalse($showContents);
        $this->assertNotFalse($engineContents);
        $this->assertStringContainsString('secureexam:gate-ready', $showContents);
        $this->assertStringContainsString('secureexam:gate-ready', $engineContents);
        $this->assertStringContainsString("document.getElementById('security-gate')", $engineContents);
    }

    public function test_show_page_hides_webcam_sidebar_when_disabled(): void
    {
        [$session] = $this->createInProgressSession(['webcam_enabled' => false]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        // The webcam sidebar section (video tag with preview) should not render
        $response->assertDontSee('id="webcam-preview"', false);
    }

    // -------------------------------------------------------------------------
    // Security event endpoint
    // -------------------------------------------------------------------------

    public function test_voluntary_exit_event_is_recorded(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'voluntary_exit',
                'metadata' => [
                    'answered_count' => 3,
                    'total_questions' => 10,
                    'time_remaining' => 1800,
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Bus::assertDispatched(RecordSecurityEvent::class, function (RecordSecurityEvent $job) use ($session) {
            return $job->session->id === $session->id
                && $job->type === 'voluntary_exit'
                && ($job->metadata['answered_count'] ?? null) === 3
                && ($job->metadata['total_questions'] ?? null) === 10
                && ($job->metadata['time_remaining'] ?? null) === 1800;
        });
    }

    public function test_fullscreen_exit_event_is_recorded(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'fullscreen_exit',
                'metadata' => ['violation_count' => 1],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Bus::assertDispatched(RecordSecurityEvent::class, function (RecordSecurityEvent $job) {
            return $job->type === 'fullscreen_exit';
        });
    }

    public function test_tab_switch_event_is_recorded(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'tab_switch',
                'metadata' => ['violation_count' => 2],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Bus::assertDispatched(RecordSecurityEvent::class, function (RecordSecurityEvent $job) {
            return $job->type === 'tab_switch';
        });
    }

    public function test_security_event_rejected_for_invalid_session(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id + 999])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'voluntary_exit',
                'metadata' => [],
            ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        Bus::assertNotDispatched(RecordSecurityEvent::class);
    }

    // -------------------------------------------------------------------------
    // Exam submission
    // -------------------------------------------------------------------------

    public function test_submit_saves_answers_and_grades_exam(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();
        $question->update([
            'explanation' => 'Comentário pedagógico da questão.',
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([$question->id => $choice->id]),
            ]);

        $response->assertRedirect(route('exam.result', $session->id));

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
            'is_correct' => true,
            'feedback' => 'Comentário pedagógico da questão.',
        ]);

        $session->refresh();
        $this->assertSame('graded', $session->status);
        $this->assertNotNull($session->submitted_at);
        $this->assertEquals(10.00, (float) $session->final_score);
        $this->assertTrue($session->passed);
    }

    public function test_submit_sums_question_weights_as_final_score(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();
        $exam = $session->exam;
        $system = $exam->clientSystem;
        $professor = $exam->creator;

        $secondQuestion = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Segunda pergunta valendo sete e meio',
            'difficulty' => 'medium',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $secondChoice = Choice::query()->create([
            'question_id' => $secondQuestion->id,
            'content' => 'Resposta correta 2',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($secondQuestion->id, ['order' => 2, 'weight' => 7.5]);
        $session->update([
            'question_order' => [$question->id, $secondQuestion->id],
            'choice_order' => [
                $question->id => [$choice->id],
                $secondQuestion->id => [$secondChoice->id],
            ],
        ]);

        $this->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([
                    $question->id => $choice->id,
                    $secondQuestion->id => $secondChoice->id,
                ]),
            ])
            ->assertRedirect(route('exam.result', $session->id));

        $session->refresh();

        $this->assertEquals(17.50, (float) $session->final_score);
        $this->assertEquals(17.50, (float) $session->raw_score);
    }

    public function test_submit_with_wrong_answer_gets_zero(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $wrongChoice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Alternativa errada',
            'is_correct' => false,
            'order' => 2,
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([$question->id => $wrongChoice->id]),
            ]);

        $response->assertRedirect(route('exam.result', $session->id));

        $session->refresh();
        $this->assertSame('graded', $session->status);
        $this->assertEquals(0.00, (float) $session->final_score);
        $this->assertFalse($session->passed);
    }

    public function test_submit_with_no_answers_gets_zero(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([]),
            ]);

        $response->assertRedirect(route('exam.result', $session->id));

        $session->refresh();
        $this->assertSame('graded', $session->status);
        $this->assertEquals(0.00, (float) $session->final_score);
    }

    public function test_submit_rejects_non_in_progress_session(): void
    {
        [$session] = $this->createInProgressSession();
        $session->update(['status' => 'graded']);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([]),
            ]);

        $response->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // Show page access control
    // -------------------------------------------------------------------------

    public function test_show_rejects_expired_session(): void
    {
        [$session] = $this->createInProgressSession();
        $session->update(['expires_at' => now()->subMinute()]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('expirou');
    }

    public function test_show_rejects_invalid_session_context(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id + 999])
            ->get(route('exam.show', $session->id));

        $response->assertRedirect();
    }

    public function test_show_rejects_graded_session(): void
    {
        [$session] = $this->createInProgressSession();
        $session->update(['status' => 'graded']);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // Save progress
    // -------------------------------------------------------------------------

    public function test_save_progress_stores_answers(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [$question->id => $choice->id],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);
    }

    public function test_save_progress_rejects_invalid_session(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id + 999])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [$question->id => $choice->id],
            ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Fingerprint endpoint
    // -------------------------------------------------------------------------

    public function test_fingerprint_is_stored(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.fingerprint', $session->id), [
                'fingerprint' => 'abc123fingerprint',
                'metadata' => ['screen' => '1920x1080', 'timezone' => 'America/Sao_Paulo'],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $session->refresh();
        $this->assertSame('abc123fingerprint', $session->device_fingerprint);
        $this->assertSame('1920x1080', $session->device_metadata['screen'] ?? null);
    }

    // -------------------------------------------------------------------------
    // Multiple questions with shuffle
    // -------------------------------------------------------------------------

    public function test_show_renders_multiple_questions(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();
        $exam = $session->exam;
        $system = $exam->clientSystem;
        $professor = $exam->creator;

        // Add a second question
        $q2 = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Segunda pergunta sobre teste',
            'difficulty' => 'medium',
            'active' => true,
            'visibility_scope' => 'system',
        ]);
        $c2 = Choice::query()->create([
            'question_id' => $q2->id,
            'content' => 'Resp Q2',
            'is_correct' => true,
            'order' => 1,
        ]);
        $exam->questions()->attach($q2->id, ['order' => 2, 'weight' => 1.0]);

        // Set explicit question order with both questions
        $session->update([
            'question_order' => [$q2->id, $question->id],
            'choice_order' => [
                $question->id => [$choice->id],
                $q2->id => [$c2->id],
            ],
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        // Should have 2 questions with navigation grid
        $response->assertSee('de 2 respondidas', false);
        $response->assertSee('exam-nav-grid', false);
    }

    // -------------------------------------------------------------------------
    // Exam timer and metadata
    // -------------------------------------------------------------------------

    public function test_show_page_includes_timer_with_remaining_seconds(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('formatTime(timeLeft)', false);
        $response->assertSee('exam-timer', false);
    }

    public function test_show_page_includes_submit_form(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('id="submit-form"', false);
        $response->assertSee(route('exam.submit', $session->id), false);
    }

    public function test_show_page_includes_delivery_confirmation_modal(): void
    {
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->get(route('exam.show', $session->id));

        $response->assertOk();
        $response->assertSee('Entregar Prova?');
        $response->assertSee('confirmSubmit', false);
        $response->assertSee('Confirmar Entrega');
    }

    public function test_show_page_submit_flow_uses_snapshot_timeout_and_loading_state(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $engineContents = file_get_contents(base_path('resources/js/secure-exam-engine.js'));

        $this->assertNotFalse($contents);
        $this->assertNotFalse($engineContents);
        $this->assertStringContainsString('submittingExam: false', $contents);
        $this->assertStringContainsString('withSnapshotTimeout', $contents);
        $this->assertStringContainsString('Entregando prova...', $contents);
        $this->assertStringContainsString('SNAPSHOT_CAPTURE_TIMEOUT', $engineContents);
    }

    public function test_show_page_delivery_modal_uses_horizontal_desktop_layout(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $layoutContents = file_get_contents(base_path('resources/views/layouts/exam.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertNotFalse($layoutContents);
        $this->assertStringContainsString('class="submit-modal-layout"', $contents);
        $this->assertStringContainsString('grid-template-columns:minmax(0,1.15fr) minmax(240px,0.85fr);', $layoutContents);
    }

    public function test_show_page_delivery_modal_buttons_use_exam_modal_button_classes(): void
    {
        $contents = file_get_contents(base_path('resources/views/exam/show.blade.php'));
        $layoutContents = file_get_contents(base_path('resources/views/layouts/exam.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertNotFalse($layoutContents);
        $this->assertStringContainsString('class="exam-modal-btn exam-modal-btn-secondary"', $contents);
        $this->assertStringContainsString('class="exam-modal-btn exam-modal-btn-primary"', $contents);
        $this->assertStringContainsString('.exam-modal-btn', $layoutContents);
        $this->assertStringContainsString('.exam-modal-btn-danger', $layoutContents);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function createInProgressSession(array $examOverrides = []): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Teste',
            'slug' => 'sistema-teste-sec',
            'client_id' => 'client-sec-test',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '11111111111',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => '22222222222',
            'active' => true,
        ]);

        $exam = Exam::query()->create(array_merge([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Seguranca',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 6,
        ], $examOverrides));

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Pergunta de teste',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $choice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Resposta correta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 10.0]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'question_order' => [$question->id],
            'choice_order' => [$question->id => [$choice->id]],
            'expires_at' => now()->addHour(),
            'is_simulation' => false,
        ]);

        return [$session, $question, $choice];
    }
}
