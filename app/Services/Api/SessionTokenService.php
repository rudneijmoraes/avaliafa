<?php

namespace App\Services\Api;

use App\Models\ExamSession;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class SessionTokenService
{
    private const ALGORITHM = 'RS256';

    public function generate(ExamSession $session): string
    {
        $jti = Str::random(64);

        $session->update(['token_jti' => $jti]);

        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $session->student_id,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + ($session->exam->duration_minutes * 60) + 300, // +5 min buffer
            'session_id' => $session->id,
            'exam_id' => $session->exam_id,
        ];

        return JWT::encode($payload, $this->getPrivateKey(), self::ALGORITHM);
    }

    public function decode(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->getPublicKey(), self::ALGORITHM));
        } catch (\Throwable) {
            return null;
        }
    }

    public function getDeepLink(ExamSession $session): string
    {
        $token = $this->generate($session);

        return route('exam.start', ['token' => $token]);
    }

    private function getPrivateKey(): string
    {
        $path = storage_path('oauth-private.key');

        if (! file_exists($path)) {
            throw new \RuntimeException('OAuth private key not found. Run: php artisan passport:keys');
        }

        return file_get_contents($path);
    }

    private function getPublicKey(): string
    {
        $path = storage_path('oauth-public.key');

        if (! file_exists($path)) {
            throw new \RuntimeException('OAuth public key not found. Run: php artisan passport:keys');
        }

        return file_get_contents($path);
    }
}
