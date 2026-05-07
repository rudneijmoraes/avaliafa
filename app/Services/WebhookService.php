<?php

namespace App\Services;

use App\Jobs\SendWebhook;
use App\Models\ClientSystem;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WebhookService
{
    public function dispatchForEvent(object $event): void
    {
        if (isset($event->session) && $event->session->is_simulation) {
            return;
        }

        $eventName = class_basename($event);
        $payload = $this->buildPayload($event);

        if (empty($payload)) {
            return;
        }

        $clientSystem = $this->resolveClientSystem($event);

        if (! $clientSystem || ! $clientSystem->webhook_url) {
            return;
        }

        SendWebhook::dispatch($clientSystem, $eventName, $payload);
    }

    public function send(ClientSystem $clientSystem, string $event, array $payload): Response
    {
        return Http::timeout(15)
            ->withHeaders([
                'X-AvaliaFA-Event' => $event,
                'X-AvaliaFA-Signature' => $this->generateSignature($clientSystem, $payload),
                'Content-Type' => 'application/json',
            ])
            ->post((string) $clientSystem->webhook_url, $payload);
    }

    public function buildPayload(object $event): array
    {
        if (isset($event->session)) {
            $session = $event->session;

            return [
                'session_id' => $session->id,
                'exam_id' => $session->exam_id,
                'student_id' => $session->student_id,
                'status' => $session->status,
                'final_score' => $session->final_score,
                'passed' => $session->passed,
                'occurred_at' => now()->toIso8601String(),
            ];
        }

        if (isset($event->certificate)) {
            $certificate = $event->certificate;

            return [
                'certificate_id' => $certificate->id,
                'code' => $certificate->code,
                'student_id' => $certificate->student_id,
                'exam_id' => $certificate->exam_id,
                'final_score' => $certificate->final_score,
                'issued_at' => $certificate->issued_at->toIso8601String(),
            ];
        }

        return [];
    }

    public function resolveClientSystem(object $event): ?ClientSystem
    {
        if (isset($event->session)) {
            return $event->session->exam->clientSystem;
        }

        if (isset($event->certificate)) {
            return $event->certificate->exam->clientSystem;
        }

        return null;
    }

    private function generateSignature(ClientSystem $clientSystem, array $payload): string
    {
        $secret = $clientSystem->client_secret ?? '';

        return 'sha256='.hash_hmac('sha256', json_encode($payload), $secret);
    }
}
