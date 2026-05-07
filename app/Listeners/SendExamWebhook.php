<?php

namespace App\Listeners;

use App\Services\WebhookService;

class SendExamWebhook
{
    public function __construct(private readonly WebhookService $webhookService) {}

    public function handle(object $event): void
    {
        $this->webhookService->dispatchForEvent($event);
    }
}
