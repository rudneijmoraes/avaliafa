<?php

namespace App\Http\Middleware;

use App\Models\ClientSystem;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class AuthApiClient
{
    public function __construct(
        private readonly ResourceServer $resourceServer,
        private readonly ClientRepository $clientRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->bearerToken()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            $psrRequest = (new PsrHttpFactory)->createRequest($request);
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $oauthClientId = $psrRequest->getAttribute('oauth_client_id');

        if (! is_string($oauthClientId) || $oauthClientId === '') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $oauthClient = $this->clientRepository->findActive($oauthClientId);

        if (! $oauthClient || ! $oauthClient->hasGrantType('client_credentials')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $clientSystem = ClientSystem::query()
            ->where('client_id', $oauthClientId)
            ->where('active', true)
            ->first();

        if (! $clientSystem) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('_client_system', $clientSystem);
        $request->attributes->set('_oauth_client_id', $oauthClientId);
        $request->attributes->set('_oauth_access_token_id', $psrRequest->getAttribute('oauth_access_token_id'));

        return $next($request);
    }
}
