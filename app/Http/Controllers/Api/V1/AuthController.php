<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Services\AuditLogService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;

class AuthController extends Controller
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly Kernel $kernel,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => 'required|in:client_credentials',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $clientSystem = ClientSystem::query()
            ->where('client_id', $validated['client_id'])
            ->where('active', true)
            ->first();

        if (! $clientSystem || ! is_string($clientSystem->client_secret)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $oauthClient = $this->clientRepository->findActive($validated['client_id']);

        if (! $oauthClient || ! $oauthClient->hasGrantType('client_credentials')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $clientSecretMatches = hash_equals($clientSystem->client_secret, $validated['client_secret']);
        $passportSecretMatches = Hash::check($validated['client_secret'], (string) $oauthClient->getRawOriginal('secret'));

        if (! $clientSecretMatches || ! $passportSecretMatches) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $passportRequest = HttpRequest::create('/oauth/token', 'POST', [
            'grant_type' => $validated['grant_type'],
            'client_id' => $validated['client_id'],
            'client_secret' => $validated['client_secret'],
            'scope' => '',
        ]);

        $passportRequest->headers->set('Accept', 'application/json');

        $passportResponse = $this->kernel->handle($passportRequest);
        $payload = json_decode($passportResponse->getContent(), true);

        if ($passportResponse->getStatusCode() !== 200 || ! is_array($payload)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $this->auditLogService->log(
            action: 'api.token.generated',
            auditable: $clientSystem,
            newValues: [
                'client_system_id' => $clientSystem->id,
                'client_id' => $clientSystem->client_id,
                'grant_type' => $validated['grant_type'],
            ],
            clientSystemId: $clientSystem->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => [
                'access_token' => $payload['access_token'],
                'token_type' => $payload['token_type'],
                'expires_in' => $payload['expires_in'],
            ],
        ]);
    }
}
