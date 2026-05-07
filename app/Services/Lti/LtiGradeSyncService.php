<?php

namespace App\Services\Lti;

use App\Models\ExamSession;
use App\Models\LtiRegistration;
use App\Models\LtiResourceLink;
use App\Models\MoodleSyncLog;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LtiGradeSyncService
{
    public function sync(ExamSession $session): bool
    {
        $session->loadMissing([
            'exam.clientSystem',
            'student',
            'ltiRegistration',
            'ltiResourceLink',
        ]);

        $clientSystem = $session->exam?->clientSystem;
        $registration = $session->ltiRegistration;
        $resourceLink = $session->ltiResourceLink;

        if (! $clientSystem || ! $registration || ! $resourceLink) {
            return false;
        }

        $log = MoodleSyncLog::query()->create([
            'session_id' => $session->id,
            'client_system_id' => $clientSystem->id,
            'status' => 'pending',
            'grade_sent' => $session->final_score,
        ]);

        try {
            $lineitemUrl = rtrim((string) ($resourceLink->lineitem_url ?? ''), '/');
            $accessToken = $this->resolveAccessToken($registration);
            $userId = $this->resolveUserId($session);

            if ($lineitemUrl === '') {
                throw new \RuntimeException('lineitem_url nao configurada para o resource link LTI.');
            }

            if ($accessToken === '') {
                throw new \RuntimeException('AGS access token nao configurado para o registro LTI.');
            }

            if ($userId === null) {
                throw new \RuntimeException('Nao foi possivel resolver o userId do estudante para AGS.');
            }

            [$scoreGiven, $scoreMaximum] = $this->normalizeScore($session, $resourceLink, $accessToken);

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->asJson()
                ->post($lineitemUrl.'/scores', [
                    'userId' => $userId,
                    'scoreGiven' => $scoreGiven,
                    'scoreMaximum' => $scoreMaximum,
                    'activityProgress' => 'Completed',
                    'gradingProgress' => 'FullyGraded',
                    'timestamp' => now()->toIso8601String(),
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('AGS returned unexpected response: '.$response->body());
            }

            $log->update([
                'status' => 'success',
                'moodle_response' => $response->json() ?? ['status' => $response->body()],
                'synced_at' => now(),
            ]);

            $session->update(['moodle_synced' => true]);

            return true;
        } catch (\Throwable $e) {
            Log::error('LTI AGS sync failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count' => $log->retry_count + 1,
            ]);

            return false;
        }
    }

    /**
     * AvaliaFA calcula a nota final na escala 0..10.
     * O AGS deve receber scoreGiven/scoreMaximum na escala da atividade Moodle.
     *
     * @return array{0: float, 1: float}
     */
    private function normalizeScore(ExamSession $session, LtiResourceLink $resourceLink, string $accessToken): array
    {
        $score = (float) $session->final_score;
        $scoreMaximum = $this->resolveScoreMaximum($resourceLink, $accessToken);
        $scoreGiven = $session->exam->normalizeScore($score, $scoreMaximum, $session->scoringQuestionIds());

        return [$scoreGiven, $scoreMaximum];
    }

    private function resolveScoreMaximum(LtiResourceLink $resourceLink, string $accessToken): float
    {
        $storedMaximum = (float) data_get($resourceLink->settings, 'lineitem_score_maximum', 0);

        if ($storedMaximum > 0) {
            return $storedMaximum;
        }

        $lineitemUrl = trim((string) ($resourceLink->lineitem_url ?? ''));

        if ($lineitemUrl === '') {
            return 100.0;
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->get($lineitemUrl);

            if (! $response->successful()) {
                return 100.0;
            }

            $payload = $response->json();
            $scoreMaximum = (float) ($payload['scoreMaximum'] ?? 0);

            if ($scoreMaximum <= 0) {
                return 100.0;
            }

            $settings = is_array($resourceLink->settings) ? $resourceLink->settings : [];
            $settings['lineitem_score_maximum'] = $scoreMaximum;
            $resourceLink->update(['settings' => $settings]);

            return $scoreMaximum;
        } catch (\Throwable $e) {
            Log::warning('Nao foi possivel resolver scoreMaximum do line item LTI.', [
                'lti_resource_link_id' => $resourceLink->id,
                'lineitem_url' => $lineitemUrl,
                'error' => $e->getMessage(),
            ]);

            return 100.0;
        }
    }

    private function resolveUserId(ExamSession $session): ?string
    {
        $student = $session->student;

        foreach ([$student?->external_id, $student?->moodle_user_id, $student?->email] as $value) {
            $normalized = trim((string) $value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function resolveAccessToken(LtiRegistration $registration): string
    {
        $staticToken = trim((string) data_get($registration->settings, 'ags_access_token', ''));

        if ($staticToken !== '') {
            return $staticToken;
        }

        $tokenUrl = trim((string) ($registration->auth_token_url ?? ''));

        if ($tokenUrl === '') {
            throw new \RuntimeException('AGS access token nao configurado para o registro LTI.');
        }

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $registration->client_id,
            'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
            'client_assertion' => $this->buildClientAssertion($registration, $tokenUrl),
            'scope' => (string) data_get($registration->settings, 'ags_scope', 'https://purl.imsglobal.org/spec/lti-ags/scope/score'),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Falha ao obter token AGS: '.$response->body());
        }

        $payload = $response->json();
        $accessToken = trim((string) ($payload['access_token'] ?? ''));

        if ($accessToken === '') {
            throw new \RuntimeException('Resposta do token AGS nao trouxe access_token.');
        }

        return $accessToken;
    }

    private function buildClientAssertion(LtiRegistration $registration, string $audience): string
    {
        $privateKey = file_get_contents(config('lti.private_key_path', storage_path('oauth-private.key')));

        if (! is_string($privateKey) || trim($privateKey) === '') {
            throw new \RuntimeException('OAuth private key nao encontrada para assinar client_assertion do AGS.');
        }

        $now = time();
        $keyId = (string) data_get($registration->settings, 'tool_key_id', config('lti.tool_key_id', 'avaliafa-lti'));

        return JWT::encode([
            'iss' => $registration->client_id,
            'sub' => $registration->client_id,
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + 300,
            'jti' => bin2hex(random_bytes(16)),
        ], $privateKey, 'RS256', $keyId);
    }
}
