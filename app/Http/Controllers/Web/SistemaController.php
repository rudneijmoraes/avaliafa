<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\LtiRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;

class SistemaController extends Controller
{
    public function __construct(private readonly ClientRepository $clientRepository) {}

    private function currentUser(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function ensureSuperAdmin(): void
    {
        abort_if(! $this->currentUser()->isSuperAdmin(), 403);
    }

    public function index(Request $request)
    {
        $this->ensureSuperAdmin();

        $query = ClientSystem::query()
            ->withCount(['users', 'exams']);

        if ($request->filled('busca')) {
            $search = $request->busca;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('ativo')) {
            $query->where('active', $request->ativo === '1');
        }

        $systems = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('sistemas.index', compact('systems'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        $system = null;

        return view('sistemas.form', compact('system'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:80|alpha_dash|unique:client_systems,slug',
            'webhook_url' => 'nullable|url|max:255',
            'allowed_ips' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
            'moodle_url' => 'nullable|url|max:255',
            'moodle_certifier_url' => 'nullable|url|max:255',
            'moodle_token' => 'nullable|string|max:255',
            'moodle_activity_id' => 'nullable|integer|min:0',
            'moodle_course_id' => 'nullable|integer|min:0',
            'moodle_scale' => 'nullable|in:0-10,0-100,percent',
            'lti_enabled' => 'nullable|boolean',
            'lti_issuer' => 'nullable|string|max:255',
            'lti_client_id' => 'nullable|string|max:255',
            'lti_deployment_id' => 'nullable|string|max:255',
            'lti_platform_login_url' => 'nullable|url|max:255',
            'lti_platform_token_url' => 'nullable|url|max:255',
            'lti_platform_keyset_url' => 'nullable|url|max:255',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'show_results_after' => 'nullable|in:submission,graded',
            'certificate_auto_issue' => 'nullable|boolean',
        ]);

        $system = ClientSystem::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'webhook_url' => $data['webhook_url'] ?? null,
            'allowed_ips' => $this->normalizeAllowedIps($data['allowed_ips'] ?? null),
            'moodle_config' => $this->buildMoodleConfig($data),
            'settings' => $this->buildSettings($data),
            'active' => $request->boolean('active', true),
        ]);

        $oauthClient = $this->clientRepository->createClientCredentialsGrantClient($system->name);

        $system->forceFill([
            'client_id' => (string) $oauthClient->getKey(),
            'client_secret' => $oauthClient->plainSecret,
        ])->save();

        $this->syncLtiRegistration($system);

        return redirect()->route('sistemas.index')->with('success', 'Sistema criado com sucesso.');
    }

    public function edit(ClientSystem $sistema)
    {
        $this->ensureSuperAdmin();

        return view('sistemas.form', ['system' => $sistema]);
    }

    public function update(Request $request, ClientSystem $sistema)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:80|alpha_dash|unique:client_systems,slug,'.$sistema->id,
            'webhook_url' => 'nullable|url|max:255',
            'allowed_ips' => 'nullable|string|max:2000',
            'active' => 'nullable|boolean',
            'moodle_url' => 'nullable|url|max:255',
            'moodle_certifier_url' => 'nullable|url|max:255',
            'moodle_token' => 'nullable|string|max:255',
            'moodle_activity_id' => 'nullable|integer|min:0',
            'moodle_course_id' => 'nullable|integer|min:0',
            'moodle_scale' => 'nullable|in:0-10,0-100,percent',
            'lti_enabled' => 'nullable|boolean',
            'lti_issuer' => 'nullable|string|max:255',
            'lti_client_id' => 'nullable|string|max:255',
            'lti_deployment_id' => 'nullable|string|max:255',
            'lti_platform_login_url' => 'nullable|url|max:255',
            'lti_platform_token_url' => 'nullable|url|max:255',
            'lti_platform_keyset_url' => 'nullable|url|max:255',
            'max_attempts' => 'nullable|integer|min:1|max:10',
            'show_results_after' => 'nullable|in:submission,graded',
            'certificate_auto_issue' => 'nullable|boolean',
            'regenerate_credentials' => 'nullable|boolean',
        ]);

        $sistema->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'webhook_url' => $data['webhook_url'] ?? null,
            'allowed_ips' => $this->normalizeAllowedIps($data['allowed_ips'] ?? null),
            'moodle_config' => $this->buildMoodleConfig($data),
            'settings' => $this->buildSettings($data),
            'active' => $request->boolean('active', true),
        ]);

        if ($request->boolean('regenerate_credentials')) {
            $oauthClient = $this->clientRepository->createClientCredentialsGrantClient($sistema->name);

            $sistema->forceFill([
                'client_id' => (string) $oauthClient->getKey(),
                'client_secret' => $oauthClient->plainSecret,
            ])->save();
        }

        $this->syncLtiRegistration($sistema);

        return redirect()->route('sistemas.index')->with('success', 'Sistema atualizado com sucesso.');
    }

    private function normalizeAllowedIps(?string $raw): ?array
    {
        if (! $raw) {
            return null;
        }

        $ips = collect(preg_split('/[\s,;]+/', $raw))
            ->map(fn ($ip) => trim((string) $ip))
            ->filter()
            ->values()
            ->all();

        return empty($ips) ? null : $ips;
    }

    private function buildMoodleConfig(array $data): ?array
    {
        $url = $data['moodle_url'] ?? null;
        $certifierUrl = $data['moodle_certifier_url'] ?? null;
        $token = $data['moodle_token'] ?? null;
        $activityId = $data['moodle_activity_id'] ?? null;
        $courseId = $data['moodle_course_id'] ?? null;
        $scale = $data['moodle_scale'] ?? null;
        $lti = $this->buildLtiConfig($data);

        if (! $url && ! $certifierUrl && ! $token && ! $activityId && ! $courseId && ! $scale && ! $lti) {
            return null;
        }

        $config = [
            'url' => $url ?: null,
            'certifier_url' => $certifierUrl ?: null,
            'token' => $token ?: null,
            'activity_id' => $activityId !== null ? (int) $activityId : 0,
            'course_id' => $courseId !== null ? (int) $courseId : 0,
            'scale' => $scale ?: '0-10',
        ];

        if ($lti) {
            $config['lti'] = $lti;
        }

        return $config;
    }

    /**
     * AJAX: fetch Moodle courses for a given system (or raw url+token).
     */
    public function fetchMoodleCourses(Request $request)
    {
        $this->ensureSuperAdmin();

        $url = $request->input('moodle_url');
        $token = $request->input('moodle_token');

        if ($request->filled('system_id')) {
            $system = ClientSystem::findOrFail($request->input('system_id'));
            $url = $url ?: $system->getMoodleUrl();
            $token = $token ?: $system->getMoodleToken();
        }

        if (empty($url) || empty($token)) {
            return response()->json(['success' => false, 'message' => 'URL e Token do Moodle são obrigatórios.'], 422);
        }

        try {
            $response = Http::timeout(15)->asForm()->post(rtrim($url, '/').'/webservice/rest/server.php', [
                'wstoken' => $token,
                'wsfunction' => 'core_course_get_courses',
                'moodlewsrestformat' => 'json',
            ]);

            $payload = $response->json();

            if (isset($payload['exception'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro Moodle: '.($payload['message'] ?? $payload['exception']),
                ], 422);
            }

            if (! is_array($payload)) {
                return response()->json(['success' => false, 'message' => 'Resposta inesperada do Moodle.'], 422);
            }

            $courses = collect($payload)
                ->filter(fn ($c) => ($c['id'] ?? 0) > 1)
                ->map(fn ($c) => [
                    'id' => $c['id'],
                    'shortname' => $c['shortname'] ?? '',
                    'fullname' => $c['fullname'] ?? '',
                ])
                ->sortBy('fullname')
                ->values()
                ->all();

            return response()->json(['success' => true, 'courses' => $courses]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Falha na conexão: '.$e->getMessage()], 422);
        }
    }

    private function buildSettings(array $data): array
    {
        return [
            'max_attempts' => isset($data['max_attempts']) ? (int) $data['max_attempts'] : 1,
            'show_results_after' => $data['show_results_after'] ?? 'submission',
            'certificate_auto_issue' => (bool) ($data['certificate_auto_issue'] ?? false),
        ];
    }

    private function buildLtiConfig(array $data): ?array
    {
        $enabled = (bool) ($data['lti_enabled'] ?? false);
        $issuer = trim((string) ($data['lti_issuer'] ?? ''));
        $clientId = trim((string) ($data['lti_client_id'] ?? ''));
        $deploymentId = trim((string) ($data['lti_deployment_id'] ?? ''));
        $loginUrl = trim((string) ($data['lti_platform_login_url'] ?? ''));
        $tokenUrl = trim((string) ($data['lti_platform_token_url'] ?? ''));
        $keysetUrl = trim((string) ($data['lti_platform_keyset_url'] ?? ''));

        if (! $enabled && $issuer === '' && $clientId === '' && $deploymentId === '' && $loginUrl === '' && $tokenUrl === '' && $keysetUrl === '') {
            return null;
        }

        return [
            'enabled' => $enabled,
            'issuer' => $issuer !== '' ? $issuer : null,
            'client_id' => $clientId !== '' ? $clientId : null,
            'deployment_id' => $deploymentId !== '' ? $deploymentId : null,
            'platform_login_url' => $loginUrl !== '' ? $loginUrl : null,
            'platform_token_url' => $tokenUrl !== '' ? $tokenUrl : null,
            'platform_keyset_url' => $keysetUrl !== '' ? $keysetUrl : null,
        ];
    }

    private function syncLtiRegistration(ClientSystem $system): void
    {
        $config = $system->getLtiConfig();
        $enabled = (bool) ($config['enabled'] ?? false);
        $issuer = trim((string) ($config['issuer'] ?? ''));
        $clientId = trim((string) ($config['client_id'] ?? ''));
        $deploymentId = $this->nullableString($config['deployment_id'] ?? null);

        if (! $enabled || $issuer === '' || $clientId === '') {
            $system->ltiRegistrations()->update(['active' => false]);

            return;
        }

        $registration = LtiRegistration::query()->updateOrCreate(
            [
                'client_system_id' => $system->id,
                'issuer' => $issuer,
                'client_id' => $clientId,
                'deployment_id' => $deploymentId,
            ],
            [
                'platform_name' => $system->name,
                'auth_login_url' => $this->nullableString($config['platform_login_url'] ?? null),
                'auth_token_url' => $this->nullableString($config['platform_token_url'] ?? null),
                'keyset_url' => $this->nullableString($config['platform_keyset_url'] ?? null),
                'active' => true,
            ]
        );

        $system->ltiRegistrations()
            ->where('id', '!=', $registration->id)
            ->update(['active' => false]);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
