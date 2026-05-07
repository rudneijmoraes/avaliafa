<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\LtiLaunchLog;
use App\Models\LtiRegistration;
use App\Services\Lti\LtiDeepLinkingService;
use App\Services\Lti\LtiLaunchService;
use App\Services\Lti\LtiOidcService;
use App\Services\Api\SessionTokenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LtiToolController extends Controller
{
    public function __construct(
        private readonly LtiOidcService $oidcService,
        private readonly LtiLaunchService $launchService,
        private readonly LtiDeepLinkingService $deepLinkingService,
        private readonly SessionTokenService $tokenService,
    ) {}

    public function login(Request $request): JsonResponse|RedirectResponse|Response
    {
        try {
            Log::info('LTI login endpoint hit', [
                'method' => $request->method(),
                'params' => $request->all(),
                'url' => $request->fullUrl(),
            ]);

            return redirect()->away($this->oidcService->initiateLogin($request));
        } catch (\Throwable $e) {
            Log::error('LTI login failed', [
                'error' => $e->getMessage(),
                'params' => $request->all(),
            ]);

            return response()->view('exam.error', [
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function launch(Request $request): JsonResponse|RedirectResponse|Response
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'tool' => 'AvaliaFA',
                'description' => 'Plataforma de avaliações online com LTI 1.3',
                'lti_version' => '1.3.0',
                'endpoints' => [
                    'login_url' => route('lti.login'),
                    'launch_url' => route('lti.launch'),
                    'jwks_url' => route('lti.jwks'),
                ],
            ]);
        }

        $idToken = (string) $request->input('id_token', '');
        $unverifiedClaims = [];

        try {
            if ($idToken !== '') {
                $unverifiedClaims = $this->oidcService->decodeUnverifiedPayload($idToken);
            }

            $launchContext = $this->oidcService->consumeLaunchState(
                $request,
                (string) $request->input('state', '')
            );

            $result = $this->launchService->launch($request->all(), $launchContext);

            // Se retornou array, professor precisa mapear a prova
            if (is_array($result) && ! empty($result['needs_mapping'])) {
                return $this->showMappingForm($result, $idToken, (string) $request->input('state', ''));
            }

            $session = $result;

            $this->recordLaunchLog($unverifiedClaims, $session->id, true);

            // Se a sessão já foi concluída, redirecionar para o resultado
            if (in_array($session->status, ['graded', 'submitted'])) {
                return redirect()->route('exam.result', ['id' => $session->id]);
            }

            $token = $this->tokenService->generate($session->fresh('exam'));

            return redirect()->route('exam.start', ['token' => $token]);
        } catch (\Throwable $e) {
            $this->recordLaunchLog($unverifiedClaims, null, false, $e->getMessage());

            return response()->view('exam.error', [
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function showMappingForm(array $mappingData, string $idToken, string $state): Response
    {
        $registration = LtiRegistration::find($mappingData['registration_id']);
        $clientSystemId = $registration?->client_system_id;

        $exams = Exam::where('client_system_id', $clientSystemId)
            ->whereIn('status', ['published', 'active'])
            ->orderBy('title')
            ->get(['id', 'title', 'status']);

        // Salva o id_token e state no cache para re-launch depois do mapeamento
        $mappingKey = Str::random(40);
        Cache::put('lti_mapping:'.$mappingKey, [
            'id_token' => $idToken,
            'registration_id' => $mappingData['registration_id'],
            'resource_link_id' => $mappingData['resource_link_id'],
            'context_id' => $mappingData['context_id'] ?? '',
            'context_label' => $mappingData['context_label'] ?? '',
            'context_title' => $mappingData['context_title'] ?? '',
            'lineitem_url' => $mappingData['lineitem_url'] ?? '',
            'lineitems_url' => $mappingData['lineitems_url'] ?? '',
        ], 600);

        return response()->view('exam.lti-map', [
            'exams' => $exams,
            'registrationId' => $mappingData['registration_id'],
            'resourceLinkId' => $mappingData['resource_link_id'],
            'contextId' => $mappingData['context_id'] ?? '',
            'contextLabel' => $mappingData['context_label'] ?? '',
            'contextTitle' => $mappingData['context_title'] ?? '',
            'lineitemUrl' => $mappingData['lineitem_url'] ?? '',
            'lineitemsUrl' => $mappingData['lineitems_url'] ?? '',
            'platformName' => $mappingData['platform_name'] ?? 'Moodle',
            'idToken' => $idToken,
            'state' => $mappingKey,
        ]);
    }

    public function mapResource(Request $request): RedirectResponse|Response
    {
        try {
            $validated = $request->validate([
                'registration_id' => 'required|integer',
                'resource_link_id' => 'required|string',
                'exam_id' => 'required|integer|exists:exams,id',
                'state' => 'required|string',
            ]);

            $mappingKey = $validated['state'];
            $cached = Cache::pull('lti_mapping:'.$mappingKey);

            if (! is_array($cached)) {
                throw new \RuntimeException('Sessao de mapeamento expirou. Tente acessar a atividade no Moodle novamente.');
            }

            // Cria o resource link
            $this->launchService->createResourceLinkMapping(
                (int) $validated['registration_id'],
                $validated['resource_link_id'],
                (int) $validated['exam_id'],
                [
                    'context_id' => $request->input('context_id', ''),
                    'context_label' => $request->input('context_label', ''),
                    'context_title' => $request->input('context_title', ''),
                    'lineitem_url' => $cached['lineitem_url'] ?? '',
                    'lineitems_url' => $cached['lineitems_url'] ?? '',
                ]
            );

            Log::info('LTI resource link mapeado via formulario', [
                'registration_id' => $validated['registration_id'],
                'resource_link_id' => $validated['resource_link_id'],
                'exam_id' => $validated['exam_id'],
            ]);

            // Mostra confirmacao com botao para o professor testar
            return response()->view('exam.lti-map-success', [
                'examId' => $validated['exam_id'],
                'examTitle' => Exam::find($validated['exam_id'])?->title ?? 'Prova',
                'contextTitle' => $request->input('context_title', ''),
            ]);
        } catch (\Throwable $e) {
            Log::error('LTI map resource failed', ['error' => $e->getMessage()]);

            return response()->view('exam.error', [
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function recordLaunchLog(array $claims, ?int $sessionId, bool $success, ?string $error = null): void
    {
        try {
            LtiLaunchLog::query()->create([
                'lti_registration_id' => null,
                'lti_resource_link_id' => null,
                'exam_session_id' => $sessionId,
                'message_type' => (string) ($claims['https://purl.imsglobal.org/spec/lti/claim/message_type'] ?? ''),
                'user_sub' => (string) ($claims['sub'] ?? ''),
                'user_email' => (string) ($claims['email'] ?? ''),
                'roles' => $claims['https://purl.imsglobal.org/spec/lti/claim/roles'] ?? [],
                'claims' => array_diff_key($claims, array_flip(['nonce'])),
                'is_successful' => $success,
                'error_message' => $error,
                'launched_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record LTI launch log', ['error' => $e->getMessage()]);
        }
    }

    public function jwks(): JsonResponse
    {
        return response()->json($this->oidcService->jwks());
    }

    public function deepLinking(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->isProfessor()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validated = $request->validate([
            'lti_registration_id' => 'required|integer|exists:lti_registrations,id',
            'exam_id' => 'required|integer|exists:exams,id',
            'resource_link_id' => 'required|string|max:255',
            'context_id' => 'nullable|string|max:255',
            'context_label' => 'nullable|string|max:255',
            'context_title' => 'nullable|string|max:255',
            'lineitem_url' => 'nullable|url|max:500',
            'lineitems_url' => 'nullable|url|max:500',
        ]);

        try {
            $resourceLink = $this->deepLinkingService->mapResourceLink($user, $validated);

            return response()->json([
                'success' => true,
                'data' => [
                    'resource_link_id' => $resourceLink->id,
                    'exam_id' => $resourceLink->exam_id,
                    'lti_registration_id' => $resourceLink->lti_registration_id,
                    'context_id' => $resourceLink->context_id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
