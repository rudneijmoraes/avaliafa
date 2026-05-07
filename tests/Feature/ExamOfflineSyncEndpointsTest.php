<?php

namespace Tests\Feature;

use App\Jobs\RecordSecurityEvent;
use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExamOfflineSyncEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_saved_answers_from_offline_payload(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [
                    (string) $question->id => (int) $choice->id,
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);
    }

    public function test_it_overwrites_previous_answer_on_reconnection_sync(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $secondChoice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Alternativa B',
            'is_correct' => false,
            'order' => 2,
        ]);

        $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [(string) $question->id => (int) $choice->id],
            ])
            ->assertOk();

        $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [(string) $question->id => (int) $secondChoice->id],
            ])
            ->assertOk();

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $secondChoice->id,
        ]);
    }

    public function test_it_ignores_invalid_question_keys_during_save_progress(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.save-progress', $session->id), [
                'answers' => [
                    'q_'.$question->id => (string) $choice->id,
                    '0' => 999999,
                    'foo' => 1,
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);
        $this->assertDatabaseMissing('answers', [
            'session_id' => $session->id,
            'question_id' => 0,
        ]);
    }

    public function test_it_queues_security_event_for_late_sync(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'connection_lost',
                'metadata' => ['offline_ms' => 12000],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        Bus::assertDispatched(RecordSecurityEvent::class, function (RecordSecurityEvent $job) use ($session) {
            return $job->session->id === $session->id
                && $job->type === 'connection_lost'
                && ($job->metadata['offline_ms'] ?? null) === 12000;
        });
    }

    public function test_it_rejects_security_event_when_session_context_is_invalid(): void
    {
        Bus::fake();
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id + 999])
            ->postJson(route('exam.security-event', $session->id), [
                'type' => 'connection_lost',
                'metadata' => ['offline_ms' => 12000],
            ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        Bus::assertNotDispatched(RecordSecurityEvent::class);
    }

    public function test_it_receives_snapshot_upload_from_sync_queue(): void
    {
        Storage::fake('public');
        [$session] = $this->createInProgressSession();

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.snapshot', $session->id), [
                'snapshot' => UploadedFile::fake()->image('offline-snapshot.jpg'),
                'trigger' => 'scheduled',
                'captured_at' => now()->toIso8601String(),
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $snapshot = Snapshot::query()->where('session_id', $session->id)->latest('id')->first();

        $this->assertNotNull($snapshot);
        $this->assertSame('scheduled', $snapshot->trigger);
        Storage::disk('public')->assertExists($snapshot->path);
    }

    public function test_it_rejects_snapshot_sync_for_non_in_progress_session(): void
    {
        Storage::fake('public');
        [$session] = $this->createInProgressSession();
        $session->update(['status' => 'terminated']);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.snapshot', $session->id), [
                'snapshot' => UploadedFile::fake()->image('offline-snapshot.jpg'),
                'trigger' => 'scheduled',
                'captured_at' => now()->toIso8601String(),
            ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertDatabaseMissing('snapshots', ['session_id' => $session->id]);
    }

    public function test_it_ignores_invalid_question_keys_during_submit(): void
    {
        [$session, $question, $choice] = $this->createInProgressSession();

        $payload = json_encode([
            'q_'.$question->id => (string) $choice->id,
            '0' => 999999,
            'foo' => null,
        ]);

        $response = $this
            ->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), ['answers' => $payload]);

        $response->assertRedirect(route('exam.result', $session->id));

        $this->assertDatabaseHas('answers', [
            'session_id' => $session->id,
            'question_id' => $question->id,
            'choice_id' => $choice->id,
        ]);
        $this->assertDatabaseMissing('answers', [
            'session_id' => $session->id,
            'question_id' => 0,
        ]);
    }

    private function createInProgressSession(): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Teste',
            'slug' => 'sistema-teste',
            'client_id' => 'client-test',
            'client_secret' => 'secret',
            'active' => true,
        ]);

        $professor = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'cpf' => '55555555555',
            'active' => true,
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'cpf' => '66666666666',
            'active' => true,
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'title' => 'Prova Offline',
            'description' => null,
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => true,
            'shuffle_questions' => true,
            'shuffle_choices' => true,
            'passing_score' => 6,
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $professor->id,
            'type' => 'multiple_choice',
            'content' => 'Pergunta de sincronização',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $choice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Alternativa A',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 1.0]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'question_order' => [$question->id],
            'choice_order' => [$question->id => [$choice->id]],
            'expires_at' => now()->addHour(),
            'is_simulation' => false,
        ]);

        return [$session, $question, $choice];
    }
}
