<?php

namespace Tests\Feature;

use App\Jobs\SyncGradeToMoodle;
use App\Jobs\SyncLtiGrade;
use App\Models\Choice;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ExamSubmissionMoodleDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_dispatches_lti_grade_sync_even_when_score_is_zero(): void
    {
        Bus::fake();

        [$session, $question, $wrongChoice] = $this->createInProgressSession(launchSource: 'lti');

        $this->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([$question->id => $wrongChoice->id]),
            ])
            ->assertRedirect(route('exam.result', $session->id));

        $session->refresh();

        $this->assertSame(0.0, (float) $session->final_score);

        Bus::assertDispatched(SyncLtiGrade::class, function (SyncLtiGrade $job) use ($session) {
            return $job->session->id === $session->id;
        });
    }

    public function test_submit_dispatches_rest_moodle_sync_even_when_score_is_zero(): void
    {
        Bus::fake();

        [$session, $question, $wrongChoice] = $this->createInProgressSession(launchSource: null, withRestMoodle: true);

        $this->withSession(['exam_session_id' => $session->id])
            ->post(route('exam.submit', $session->id), [
                'answers' => json_encode([$question->id => $wrongChoice->id]),
            ])
            ->assertRedirect(route('exam.result', $session->id));

        $session->refresh();

        $this->assertSame(0.0, (float) $session->final_score);

        Bus::assertDispatched(SyncGradeToMoodle::class, function (SyncGradeToMoodle $job) use ($session) {
            return $job->session->id === $session->id;
        });
    }

    public function test_moodle_sync_jobs_target_the_moodle_sync_queue(): void
    {
        [$session] = $this->createInProgressSession(launchSource: 'lti');

        $this->assertSame('moodle-sync', (new SyncLtiGrade($session))->queue);
        $this->assertSame('moodle-sync', (new SyncGradeToMoodle($session))->queue);
    }

    private function createInProgressSession(?string $launchSource = null, bool $withRestMoodle = false): array
    {
        $system = ClientSystem::query()->create([
            'name' => 'Sistema Moodle',
            'slug' => 'sistema-moodle-'.($launchSource ?: 'rest'),
            'active' => true,
            'moodle_config' => $withRestMoodle ? [
                'url' => 'https://certificacao.faculdadeanaspsead.com.br/moodle',
                'token' => 'token-abc',
            ] : null,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'external_id' => 'mdl-student-001',
            'moodle_user_id' => 'mdl-student-001',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'Prova Dispatch',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);

        $question = Question::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'type' => 'multiple_choice',
            'content' => 'Pergunta dispatch',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        $correctChoice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Correta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $wrongChoice = Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Errada',
            'is_correct' => false,
            'order' => 2,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => 1.0]);

        $registrationId = null;
        $resourceLinkId = null;

        if ($launchSource === 'lti') {
            $registration = LtiRegistration::query()->create([
                'client_system_id' => $system->id,
                'issuer' => 'https://certificacao.faculdadeanaspsead.com.br/moodle',
                'client_id' => 'dispatch-client',
                'deployment_id' => 'dispatch-deployment',
                'platform_name' => 'Moodle',
                'active' => true,
            ]);

            $resourceLink = LtiResourceLink::query()->create([
                'lti_registration_id' => $registration->id,
                'exam_id' => $exam->id,
                'resource_link_id' => 'dispatch-resource-link',
                'lineitem_url' => 'https://certificacao.faculdadeanaspsead.com.br/moodle/lineitems/99',
            ]);

            $registrationId = $registration->id;
            $resourceLinkId = $resourceLink->id;
        }

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => $launchSource,
            'lti_registration_id' => $registrationId,
            'lti_resource_link_id' => $resourceLinkId,
            'attempt_number' => 1,
            'status' => 'in_progress',
            'started_at' => now(),
            'question_order' => [$question->id],
            'choice_order' => [$question->id => [$correctChoice->id, $wrongChoice->id]],
            'expires_at' => now()->addHour(),
        ]);

        return [$session, $question, $wrongChoice];
    }
}
