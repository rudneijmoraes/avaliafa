<?php

namespace Tests\Feature\Lti;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\MoodleSyncLog;
use App\Models\Question;
use App\Models\Choice;
use App\Services\Lti\LtiGradeSyncService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LtiGradePassbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_lti_grade_passback_uses_ags_lineitem_scores_endpoint(): void
    {
        Http::fake([
            'https://moodle.example.test/lineitems/42/scores' => Http::response(['status' => 'ok'], 201),
        ]);

        $system = ClientSystem::query()->create([
            'name' => 'Graduacao',
            'slug' => 'graduacao',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'email' => 'teacher@example.test',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'email' => 'student@example.test',
            'external_id' => 'moodle-user-100',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'AV2 2026/2',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);
        $this->attachWeightedQuestion($exam, $teacher, 20);

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://moodle.example.test',
            'client_id' => 'avaliafa-tool-client',
            'deployment_id' => 'deployment-01',
            'platform_name' => 'Moodle',
            'settings' => [
                'ags_access_token' => 'ags-token-123',
            ],
            'active' => true,
        ]);

        $resourceLink = LtiResourceLink::query()->create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-01',
            'lineitem_url' => 'https://moodle.example.test/lineitems/42',
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
            'lti_registration_id' => $registration->id,
            'lti_resource_link_id' => $resourceLink->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'final_score' => 17.5,
            'grade_published' => true,
        ]);

        $this->app->make(LtiGradeSyncService::class)->sync(
            $session->fresh(['exam.clientSystem', 'student', 'ltiRegistration', 'ltiResourceLink'])
        );

        Http::assertSent(function ($request) use ($student) {
            $data = $request->data();

            return $request->url() === 'https://moodle.example.test/lineitems/42/scores'
                && $request->hasHeader('Authorization', 'Bearer ags-token-123')
                && ($data['userId'] ?? null) === $student->external_id
                && (float) ($data['scoreGiven'] ?? 0) === 87.5
                && (float) ($data['scoreMaximum'] ?? 0) === 100.0
                && ($data['gradingProgress'] ?? null) === 'FullyGraded';
        });

        $session->refresh();

        $this->assertTrue($session->moodle_synced);
        $this->assertDatabaseHas('moodle_sync_logs', [
            'session_id' => $session->id,
            'client_system_id' => $system->id,
            'status' => 'success',
        ]);

        $log = MoodleSyncLog::query()->where('session_id', $session->id)->first();

        $this->assertNotNull($log);
        $this->assertSame('success', $log->status);
    }

    public function test_lti_grade_passback_fetches_ags_token_dynamically_when_static_token_is_missing(): void
    {
        Http::fake([
            'https://moodle.example.test/mod/lti/token.php' => Http::response([
                'access_token' => 'dynamic-ags-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ], 200),
            'https://moodle.example.test/lineitems/42/scores' => Http::response(['status' => 'ok'], 201),
        ]);

        $system = ClientSystem::query()->create([
            'name' => 'Graduacao',
            'slug' => 'graduacao-dynamic',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'email' => 'teacher-dynamic@example.test',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'email' => 'student-dynamic@example.test',
            'external_id' => 'moodle-user-200',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'AV3 2026/2',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);
        $this->attachWeightedQuestion($exam, $teacher, 20);

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://moodle.example.test',
            'client_id' => 'avaliafa-tool-client-dynamic',
            'deployment_id' => 'deployment-02',
            'platform_name' => 'Moodle',
            'auth_token_url' => 'https://moodle.example.test/mod/lti/token.php',
            'settings' => [],
            'active' => true,
        ]);

        $resourceLink = LtiResourceLink::query()->create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-02',
            'lineitem_url' => 'https://moodle.example.test/lineitems/42',
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
            'lti_registration_id' => $registration->id,
            'lti_resource_link_id' => $resourceLink->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'final_score' => 18.2,
            'grade_published' => true,
        ]);

        $this->app->make(LtiGradeSyncService::class)->sync(
            $session->fresh(['exam.clientSystem', 'student', 'ltiRegistration', 'ltiResourceLink'])
        );

        Http::assertSent(function ($request) use ($registration) {
            $data = $request->data();

            return $request->url() === 'https://moodle.example.test/mod/lti/token.php'
                && ($data['grant_type'] ?? null) === 'client_credentials'
                && ($data['client_assertion_type'] ?? null) === 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer'
                && ($data['client_assertion'] ?? null) !== null
                && ($data['scope'] ?? null) === 'https://purl.imsglobal.org/spec/lti-ags/scope/score'
                && ($data['client_id'] ?? null) === $registration->client_id;
        });

        Http::assertSent(function ($request) use ($student) {
            $data = $request->data();

            return $request->url() === 'https://moodle.example.test/lineitems/42/scores'
                && $request->hasHeader('Authorization', 'Bearer dynamic-ags-token')
                && ($data['userId'] ?? null) === $student->external_id
                && (float) ($data['scoreGiven'] ?? 0) === 91.0;
        });

        $session->refresh();

        $this->assertTrue($session->moodle_synced);
    }

    public function test_lti_grade_passback_normalizes_score_to_moodle_lineitem_scale(): void
    {
        Http::fake([
            'https://moodle.example.test/lineitems/77' => Http::response([
                'id' => 'https://moodle.example.test/lineitems/77',
                'label' => 'AV4',
                'scoreMaximum' => 3.0,
            ], 200),
            'https://moodle.example.test/lineitems/77/scores' => Http::response(['status' => 'ok'], 201),
        ]);

        $system = ClientSystem::query()->create([
            'name' => 'Graduacao',
            'slug' => 'graduacao-normalized',
            'active' => true,
        ]);

        $teacher = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'professor',
            'email' => 'teacher-normalized@example.test',
        ]);

        $student = User::factory()->create([
            'client_system_id' => $system->id,
            'role' => 'student',
            'email' => 'student-normalized@example.test',
            'external_id' => 'moodle-user-300',
        ]);

        $exam = Exam::query()->create([
            'client_system_id' => $system->id,
            'created_by' => $teacher->id,
            'title' => 'AV4 2026/2',
            'status' => 'active',
            'duration_minutes' => 60,
            'max_violations' => 3,
            'webcam_enabled' => false,
            'shuffle_questions' => false,
            'shuffle_choices' => false,
            'passing_score' => 6,
        ]);
        $this->attachWeightedQuestion($exam, $teacher, 20);

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://moodle.example.test',
            'client_id' => 'avaliafa-tool-client-normalized',
            'deployment_id' => 'deployment-03',
            'platform_name' => 'Moodle',
            'settings' => [
                'ags_access_token' => 'ags-token-789',
            ],
            'active' => true,
        ]);

        $resourceLink = LtiResourceLink::query()->create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-03',
            'lineitem_url' => 'https://moodle.example.test/lineitems/77',
        ]);

        $session = ExamSession::query()->create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
            'lti_registration_id' => $registration->id,
            'lti_resource_link_id' => $resourceLink->id,
            'attempt_number' => 1,
            'status' => 'graded',
            'final_score' => 16.0,
            'grade_published' => true,
        ]);

        $this->app->make(LtiGradeSyncService::class)->sync(
            $session->fresh(['exam.clientSystem', 'student', 'ltiRegistration', 'ltiResourceLink'])
        );

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && $request->url() === 'https://moodle.example.test/lineitems/77'
                && $request->hasHeader('Authorization', 'Bearer ags-token-789');
        });

        Http::assertSent(function ($request) use ($student) {
            $data = $request->data();

            return $request->url() === 'https://moodle.example.test/lineitems/77/scores'
                && $request->hasHeader('Authorization', 'Bearer ags-token-789')
                && ($data['userId'] ?? null) === $student->external_id
                && (float) ($data['scoreGiven'] ?? 0) === 2.4
                && (float) ($data['scoreMaximum'] ?? 0) === 3.0;
        });

        $resourceLink->refresh();

        $this->assertSame(3.0, (float) data_get($resourceLink->settings, 'lineitem_score_maximum'));
    }

    private function attachWeightedQuestion(Exam $exam, User $teacher, float $weight): void
    {
        $question = Question::query()->create([
            'client_system_id' => $exam->client_system_id,
            'created_by' => $teacher->id,
            'type' => 'multiple_choice',
            'content' => 'Questao para normalizacao LTI',
            'difficulty' => 'easy',
            'active' => true,
            'visibility_scope' => 'system',
        ]);

        Choice::query()->create([
            'question_id' => $question->id,
            'content' => 'Resposta correta',
            'is_correct' => true,
            'order' => 1,
        ]);

        $exam->questions()->attach($question->id, ['order' => 1, 'weight' => $weight]);
    }
}
