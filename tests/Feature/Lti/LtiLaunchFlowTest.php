<?php

namespace Tests\Feature\Lti;

use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\User;
use App\Services\Api\SessionTokenService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LtiLaunchFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lti_login_redirects_to_platform_oidc_endpoint_with_state_and_nonce(): void
    {
        [$registration] = $this->createLaunchScenario();

        $response = $this->get(route('lti.login', [
            'iss' => $registration->issuer,
            'client_id' => $registration->client_id,
            'login_hint' => 'student-login-hint',
            'lti_message_hint' => 'message-hint-01',
            'target_link_uri' => route('lti.launch'),
        ]));

        $response->assertRedirect();

        $params = $this->extractRedirectQueryParams($response);

        $this->assertSame($registration->auth_login_url, strtok($response->headers->get('Location'), '?'));
        $this->assertSame('openid', $params['scope'] ?? null);
        $this->assertSame('id_token', $params['response_type'] ?? null);
        $this->assertSame('form_post', $params['response_mode'] ?? null);
        $this->assertSame('none', $params['prompt'] ?? null);
        $this->assertSame($registration->client_id, $params['client_id'] ?? null);
        $this->assertSame(route('lti.launch'), $params['redirect_uri'] ?? null);
        $this->assertSame('student-login-hint', $params['login_hint'] ?? null);
        $this->assertSame('message-hint-01', $params['lti_message_hint'] ?? null);
        $this->assertNotEmpty($params['state'] ?? null);
        $this->assertNotEmpty($params['nonce'] ?? null);

        $storedState = Cache::get('lti_oidc_state:'.$params['state']);

        $this->assertIsArray($storedState);
        $this->assertSame($registration->id, $storedState['registration_id'] ?? null);
        $this->assertSame($params['nonce'], $storedState['nonce'] ?? null);
    }

    public function test_lti_launch_redirects_student_into_exam_without_cpf_prompt(): void
    {
        [$registration, $exam, $student, $resourceLink, $sharedSecret] = $this->createLaunchScenario();

        app()->instance(SessionTokenService::class, new class extends SessionTokenService
        {
            public function generate(ExamSession $session): string
            {
                return 'fake-lti-token-'.$session->id;
            }

            public function getDeepLink(ExamSession $session): string
            {
                return route('exam.start', ['token' => $this->generate($session)]);
            }
        });

        $loginResponse = $this->get(route('lti.login', [
            'iss' => $registration->issuer,
            'client_id' => $registration->client_id,
            'login_hint' => 'student-login-hint',
            'lti_message_hint' => 'message-hint-01',
            'target_link_uri' => route('lti.launch'),
        ]));

        $loginResponse->assertRedirect();

        $loginParams = $this->extractRedirectQueryParams($loginResponse);
        $state = $loginParams['state'] ?? null;
        $nonce = $loginParams['nonce'] ?? null;

        $this->assertNotEmpty($state);
        $this->assertNotEmpty($nonce);

        $idToken = JWT::encode([
            'iss' => $registration->issuer,
            'aud' => $registration->client_id,
            'sub' => $student->external_id,
            'email' => $student->email,
            'given_name' => 'Aluno',
            'family_name' => 'Teste',
            'name' => 'Aluno Teste',
            'nonce' => $nonce,
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => $registration->deployment_id,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                'id' => $resourceLink->resource_link_id,
                'title' => $exam->title,
            ],
            'https://purl.imsglobal.org/spec/lti/claim/context' => [
                'id' => $resourceLink->context_id,
                'label' => $resourceLink->context_label,
                'title' => $resourceLink->context_title,
            ],
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
            ],
            'iat' => time(),
            'exp' => time() + 300,
        ], $sharedSecret, 'HS256', 'test-key-1');

        $response = $this->post(route('lti.launch'), [
            'state' => $state,
            'id_token' => $idToken,
        ]);

        if (! $response->isRedirect()) {
            $body = trim(substr(strip_tags($response->getContent()), 0, 200));

            $this->fail('Launch response was not a redirect. Status: '.$response->getStatusCode().' Body: '.$body);
        }

        $session = ExamSession::query()->first();

        $this->assertNotNull($session);
        $response->assertRedirect(route('exam.start', ['token' => 'fake-lti-token-'.$session->id]));
        $this->assertSame($exam->id, $session->exam_id);
        $this->assertSame($student->id, $session->student_id);
        $this->assertSame('lti', $session->launch_source);
        $this->assertSame($registration->id, $session->lti_registration_id);
        $this->assertSame($resourceLink->id, $session->lti_resource_link_id);
        $this->assertSame('pending', $session->status);
    }

    public function test_lti_launch_rejects_expired_state(): void
    {
        [$registration, $exam, $student, $resourceLink, $sharedSecret] = $this->createLaunchScenario();

        $loginResponse = $this->get(route('lti.login', [
            'iss' => $registration->issuer,
            'client_id' => $registration->client_id,
            'login_hint' => 'student-login-hint',
            'lti_message_hint' => 'message-hint-01',
            'target_link_uri' => route('lti.launch'),
        ]));

        $loginResponse->assertRedirect();

        $loginParams = $this->extractRedirectQueryParams($loginResponse);
        $state = $loginParams['state'] ?? '';
        $nonce = $loginParams['nonce'] ?? '';

        $storedState = Cache::get('lti_oidc_state:'.$state);

        $this->assertIsArray($storedState);

        $storedState['created_at'] = now()->subMinutes(20)->timestamp;

        Cache::put('lti_oidc_state:'.$state, $storedState, 600);

        $idToken = $this->buildLaunchToken($registration, $student, $resourceLink, $exam, $sharedSecret, $nonce);

        $response = $this->post(route('lti.launch'), [
            'state' => $state,
            'id_token' => $idToken,
        ]);

        $response->assertStatus(422);
        $response->assertSeeText('State do launch LTI e invalido ou expirou.');
        $this->assertDatabaseCount('exam_sessions', 0);
    }

    public function test_lti_launch_rejects_unexpected_message_type(): void
    {
        [$registration, $exam, $student, $resourceLink, $sharedSecret] = $this->createLaunchScenario();

        $loginResponse = $this->get(route('lti.login', [
            'iss' => $registration->issuer,
            'client_id' => $registration->client_id,
            'login_hint' => 'student-login-hint',
            'lti_message_hint' => 'message-hint-01',
            'target_link_uri' => route('lti.launch'),
        ]));

        $loginResponse->assertRedirect();

        $loginParams = $this->extractRedirectQueryParams($loginResponse);
        $state = $loginParams['state'] ?? '';
        $nonce = $loginParams['nonce'] ?? '';

        $idToken = $this->buildLaunchToken(
            $registration,
            $student,
            $resourceLink,
            $exam,
            $sharedSecret,
            $nonce,
            'LtiDeepLinkingRequest'
        );

        $response = $this->post(route('lti.launch'), [
            'state' => $state,
            'id_token' => $idToken,
        ]);

        $response->assertStatus(422);
        $response->assertSeeText('message_type do launch LTI e invalido.');
        $this->assertDatabaseCount('exam_sessions', 0);
    }

    public function test_lti_launch_accepts_existing_mapping_even_when_context_id_was_saved_as_empty_string(): void
    {
        [$registration, $exam, $student, $resourceLink, $sharedSecret] = $this->createLaunchScenario();

        $resourceLink->update([
            'context_id' => '',
            'context_label' => '',
            'context_title' => '',
        ]);

        $loginResponse = $this->get(route('lti.login', [
            'iss' => $registration->issuer,
            'client_id' => $registration->client_id,
            'login_hint' => 'student-login-hint',
            'lti_message_hint' => 'message-hint-01',
            'target_link_uri' => route('lti.launch'),
        ]));

        $loginResponse->assertRedirect();

        $loginParams = $this->extractRedirectQueryParams($loginResponse);
        $state = $loginParams['state'] ?? '';
        $nonce = $loginParams['nonce'] ?? '';

        $idToken = JWT::encode([
            'iss' => $registration->issuer,
            'aud' => $registration->client_id,
            'sub' => $student->external_id,
            'email' => $student->email,
            'given_name' => 'Aluno',
            'family_name' => 'Teste',
            'name' => 'Aluno Teste',
            'nonce' => $nonce,
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => $registration->deployment_id,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => 'LtiResourceLinkRequest',
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                'id' => $resourceLink->resource_link_id,
                'title' => $exam->title,
            ],
            'https://purl.imsglobal.org/spec/lti/claim/context' => [
                'id' => 'course-9001',
                'label' => 'GRAD-9001',
                'title' => 'Turma Principal',
            ],
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
            ],
            'iat' => time(),
            'exp' => time() + 300,
        ], $sharedSecret, 'HS256', 'test-key-1');

        $response = $this->post(route('lti.launch'), [
            'state' => $state,
            'id_token' => $idToken,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exam_sessions', [
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'launch_source' => 'lti',
        ]);
    }

    /**
     * @return array{0: LtiRegistration, 1: Exam, 2: User, 3: LtiResourceLink, 4: string}
     */
    private function createLaunchScenario(): array
    {
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

        [$sharedSecret, $jwks] = $this->generateOctetKeyMaterial();

        $registration = LtiRegistration::query()->create([
            'client_system_id' => $system->id,
            'issuer' => 'https://moodle.example.test',
            'client_id' => 'avaliafa-tool-client',
            'deployment_id' => 'deployment-01',
            'platform_name' => 'Moodle',
            'auth_login_url' => 'https://moodle.example.test/mod/lti/auth.php',
            'settings' => ['jwks' => $jwks],
            'active' => true,
        ]);

        $resourceLink = LtiResourceLink::query()->create([
            'lti_registration_id' => $registration->id,
            'exam_id' => $exam->id,
            'resource_link_id' => 'resource-link-01',
            'context_id' => 'course-9001',
            'context_label' => 'TST-9001',
            'context_title' => 'Turma de Teste',
        ]);

        return [$registration, $exam, $student, $resourceLink, $sharedSecret];
    }

    /**
     * @return array<string, string>
     */
    private function extractRedirectQueryParams($response): array
    {
        $location = $response->headers->get('Location') ?? '';
        $query = parse_url($location, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        return is_array($params) ? array_map(fn ($value) => is_string($value) ? $value : '', $params) : [];
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function generateOctetKeyMaterial(): array
    {
        $sharedSecret = 'lti-shared-secret-for-tests-with-sufficient-length-2026';

        return [
            $sharedSecret,
            [
                'keys' => [[
                    'kty' => 'oct',
                    'alg' => 'HS256',
                    'use' => 'sig',
                    'kid' => 'test-key-1',
                    'k' => $this->base64UrlEncode($sharedSecret),
                ]],
            ],
        ];
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function buildLaunchToken(
        LtiRegistration $registration,
        User $student,
        LtiResourceLink $resourceLink,
        Exam $exam,
        string $sharedSecret,
        string $nonce,
        string $messageType = 'LtiResourceLinkRequest',
    ): string {
        return JWT::encode([
            'iss' => $registration->issuer,
            'aud' => $registration->client_id,
            'sub' => $student->external_id,
            'email' => $student->email,
            'given_name' => 'Aluno',
            'family_name' => 'Teste',
            'name' => 'Aluno Teste',
            'nonce' => $nonce,
            'https://purl.imsglobal.org/spec/lti/claim/deployment_id' => $registration->deployment_id,
            'https://purl.imsglobal.org/spec/lti/claim/message_type' => $messageType,
            'https://purl.imsglobal.org/spec/lti/claim/version' => '1.3.0',
            'https://purl.imsglobal.org/spec/lti/claim/resource_link' => [
                'id' => $resourceLink->resource_link_id,
                'title' => $exam->title,
            ],
            'https://purl.imsglobal.org/spec/lti/claim/context' => [
                'id' => $resourceLink->context_id,
                'label' => $resourceLink->context_label,
                'title' => $resourceLink->context_title,
            ],
            'https://purl.imsglobal.org/spec/lti/claim/roles' => [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
            ],
            'iat' => time(),
            'exp' => time() + 300,
        ], $sharedSecret, 'HS256', 'test-key-1');
    }
}
