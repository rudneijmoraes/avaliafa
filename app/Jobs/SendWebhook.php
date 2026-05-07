<?php

namespace App\Jobs;

use App\Models\ClientSystem;
use App\Models\WebhookLog;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public readonly ClientSystem $clientSystem,
        public readonly string $event,
        public readonly array $payload,
    ) {}

    public function queue(): string
    {
        return 'webhooks';
    }

    public function handle(WebhookService $webhookService): void
    {
        if (! $this->clientSystem->webhook_url) {
            return;
        }

        $log = WebhookLog::create([
            'client_system_id' => $this->clientSystem->id,
            'event' => $this->event,
            'payload' => $this->payload,
            'status' => 'pending',
        ]);

        try {
            $response = $webhookService->send($this->clientSystem, $this->event, $this->payload);

            $log->update([
                'status_code' => $response->status(),
                'status' => $response->successful() ? 'delivered' : 'failed',
                'retry_count' => $this->attempts() - 1,
                'delivered_at' => $response->successful() ? now() : null,
            ]);

            if (! $response->successful()) {
                throw new \RuntimeException("Webhook returned HTTP {$response->status()}");
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => $this->attempts() >= $this->tries ? 'failed' : 'retrying',
                'retry_count' => $this->attempts(),
            ]);

            Log::warning('Webhook delivery failed', [
                'event' => $this->event,
                'client' => $this->clientSystem->slug,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
