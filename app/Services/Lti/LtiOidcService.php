<?php

namespace App\Services\Lti;

use App\Models\ClientSystem;
use App\Models\LtiRegistration;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LtiOidcService
{
    private const STATE_TTL_SECONDS = 600;


    public function jwks(): array
    {
        $publicKeyPath = config('lti.public_key_path', storage_path('oauth-public.key'));

        if (! is_file($publicKeyPath)) {
            return ['keys' => []];
        }

        $publicKeyPem = file_get_contents($publicKeyPath);
        $publicKey = openssl_pkey_get_public($publicKeyPem);

        if ($publicKey === false) {
            return ['keys' => []];
        }

        $details = openssl_pkey_get_details($publicKey);

        if (! is_array($details) || ($details['type'] ?? -1) !== OPENSSL_KEYTYPE_RSA) {
            return ['keys' => []];
        }

        $rsa = $details['rsa'];
        $keyId = config('lti.tool_key_id', 'avaliafa-lti');

        return [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'alg' => 'RS256',
                    'use' => 'sig',
                    'kid' => $keyId,
                    'n' => rtrim(strtr(base64_encode($rsa['n']), '+/', '-_'), '='),
                    'e' => rtrim(strtr(base64_encode($rsa['e']), '+/', '-_'), '='),
                ],
            ],
        ];
    }

    public function initiateLogin(Request $request): string
    {
        $issuer = trim((string) $request->input('iss', ''));
        $clientId = trim((string) $request->input('client_id', ''));
        $targetLinkUri = trim((string) $request->input('target_link_uri', route('lti.launch')));

        Log::info('LTI OIDC login attempt', [
            'iss' => $issuer,
            'client_id' => $clientId,
            'target_link_uri' => $targetLinkUri,
            'all_params' => $request->all(),
            'method' => $request->method(),
        ]);

        if ($issuer === '' || $clientId === '') {
            throw new \RuntimeException('Issuer e client_id sao obrigatorios para iniciar o login OIDC. Recebido: iss='.$issuer.', client_id='.$clientId);
        }

        $registration = LtiRegistration::query()
            ->where('issuer', $issuer)
            ->where('client_id', $clientId)
            ->where('active', true)
            ->first();

        // Fallback: busca em ClientSystem.moodle_config.lti e auto-cria LtiRegistration
        if (! $registration) {
            $registration = $this->findOrCreateFromClientSystem($issuer, $clientId);
        }

        if (! $registration) {
            // Log detalhado para debug
            $allRegs = LtiRegistration::all(['id', 'issuer', 'client_id', 'active'])->toArray();
            $allSystems = ClientSystem::where('active', true)->get()->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'lti_config' => $s->getLtiConfig(),
            ])->toArray();

            Log::error('LTI Registration not found', [
                'searched_issuer' => $issuer,
                'searched_client_id' => $clientId,
                'existing_registrations' => $allRegs,
                'existing_systems_lti' => $allSystems,
            ]);

            throw new \RuntimeException('Nenhum registro LTI ativo foi encontrado para este login OIDC. Issuer='.$issuer.', ClientID='.$clientId);
        }

        if (empty($registration->auth_login_url)) {
            throw new \RuntimeException('OIDC Login URL nao configurada para o registro LTI.');
        }

        $state = Str::random(40);
        $nonce = Str::random(40);

        // Usa Cache ao invés de session — SameSite=Lax bloqueia cookies em POST cross-site (Moodle → AvaliaFA)
        Cache::put('lti_oidc_state:'.$state, [
            'registration_id' => $registration->id,
            'nonce' => $nonce,
            'target_link_uri' => $targetLinkUri,
            'login_hint' => $request->input('login_hint'),
            'lti_message_hint' => $request->input('lti_message_hint'),
            'created_at' => now()->timestamp,
        ], self::STATE_TTL_SECONDS);

        $params = array_filter([
            'scope' => 'openid',
            'response_type' => 'id_token',
            'response_mode' => 'form_post',
            'prompt' => 'none',
            'client_id' => $registration->client_id,
            'redirect_uri' => route('lti.launch'),
            'login_hint' => $request->input('login_hint'),
            'lti_message_hint' => $request->input('lti_message_hint'),
            'state' => $state,
            'nonce' => $nonce,
        ], fn ($value) => $value !== null && $value !== '');

        return $registration->auth_login_url.'?'.http_build_query($params);
    }

    public function consumeLaunchState(Request $request, string $state): array
    {
        $state = trim($state);

        if ($state === '') {
            throw new \RuntimeException('State do launch LTI nao informado.');
        }

        // Lê do Cache (pull = get + forget)
        $stored = Cache::pull('lti_oidc_state:'.$state);

        if (! is_array($stored)) {
            throw new \RuntimeException('State do launch LTI e invalido ou expirou.');
        }

        $createdAt = (int) ($stored['created_at'] ?? 0);

        if ($createdAt <= 0 || $createdAt < now()->subSeconds(self::STATE_TTL_SECONDS)->timestamp) {
            throw new \RuntimeException('State do launch LTI e invalido ou expirou.');
        }

        return $stored;
    }

    public function decodeUnverifiedPayload(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new \RuntimeException('id_token LTI invalido.');
        }

        $decoded = json_decode($this->base64UrlDecode($parts[1]), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Payload LTI invalido.');
        }

        return $decoded;
    }

    public function decodeVerifiedToken(string $idToken, LtiRegistration $registration): object
    {
        $header = $this->decodeHeader($idToken);
        $keys = JWK::parseKeySet($this->resolveKeySet($registration));
        $kid = $header['kid'] ?? null;
        $key = $kid !== null ? ($keys[$kid] ?? null) : null;

        if (! $key instanceof Key) {
            $key = reset($keys);
        }

        if (! $key instanceof Key) {
            throw new \RuntimeException('Nenhuma chave valida foi encontrada para o registro LTI.');
        }

        // Tolerância de 120s para diferença de relógio entre Moodle e AvaliaFA
        JWT::$leeway = 120;

        return JWT::decode($idToken, $key);
    }

    public function resolveKeySet(LtiRegistration $registration): array
    {
        $settings = $registration->settings ?? [];
        $inlineKeySet = $settings['jwks'] ?? $settings['public_jwks'] ?? null;

        if (is_array($inlineKeySet) && isset($inlineKeySet['keys']) && is_array($inlineKeySet['keys'])) {
            return $inlineKeySet;
        }

        if (empty($registration->keyset_url)) {
            throw new \RuntimeException('Keyset URL nao configurada para o registro LTI.');
        }

        $cacheKey = 'lti_jwks_'.md5($registration->keyset_url);

        $payload = Cache::remember($cacheKey, 300, function () use ($registration) {
            $response = Http::timeout(10)->acceptJson()->get($registration->keyset_url);

            if (! $response->successful()) {
                throw new \RuntimeException('Falha ao carregar o keyset da plataforma LTI.');
            }

            $data = $response->json();

            if (! is_array($data) || ! isset($data['keys']) || ! is_array($data['keys'])) {
                throw new \RuntimeException('Keyset LTI invalido.');
            }

            return $data;
        });

        return $payload;
    }

    private function decodeHeader(string $idToken): array
    {
        $parts = explode('.', $idToken);

        if (count($parts) !== 3) {
            throw new \RuntimeException('id_token LTI invalido.');
        }

        $decoded = json_decode($this->base64UrlDecode($parts[0]), true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('Cabecalho LTI invalido.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'));
    }

    /**
     * Busca configuração LTI em ClientSystem.moodle_config.lti e auto-cria LtiRegistration.
     */
    private function findOrCreateFromClientSystem(string $issuer, string $clientId): ?LtiRegistration
    {
        $systems = ClientSystem::where('active', true)->get();

        foreach ($systems as $system) {
            $lti = $system->getLtiConfig();

            if (empty($lti['issuer']) || empty($lti['client_id'])) {
                continue;
            }

            if (rtrim($lti['issuer'], '/') !== rtrim($issuer, '/') || $lti['client_id'] !== $clientId) {
                continue;
            }

            // Encontrou — auto-cria o LtiRegistration
            $baseUrl = rtrim($lti['issuer'], '/');

            $registration = LtiRegistration::create([
                'client_system_id' => $system->id,
                'issuer'           => $lti['issuer'],
                'client_id'        => $lti['client_id'],
                'deployment_id'    => $lti['deployment_id'] ?? null,
                'platform_name'    => $system->name . ' (Moodle)',
                'auth_login_url'   => $lti['platform_login_url'] ?? $baseUrl . '/mod/lti/auth.php',
                'auth_token_url'   => $lti['platform_token_url'] ?? $baseUrl . '/mod/lti/token.php',
                'keyset_url'       => $lti['platform_keyset_url'] ?? $baseUrl . '/mod/lti/certs.php',
                'active'           => true,
            ]);

            Log::info('LtiRegistration auto-criado a partir de ClientSystem', [
                'registration_id' => $registration->id,
                'client_system_id' => $system->id,
                'issuer' => $issuer,
                'client_id' => $clientId,
            ]);

            return $registration;
        }

        return null;
    }
}
