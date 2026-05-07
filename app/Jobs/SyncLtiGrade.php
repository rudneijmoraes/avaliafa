<?php

namespace App\Jobs;

use App\Models\ExamSession;
use App\Services\Lti\LtiGradeSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLtiGrade implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public readonly ExamSession $session)
    {
        $this->onQueue('moodle-sync');
    }

    public function handle(LtiGradeSyncService $service): void
    {
        $service->sync($this->session);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncLtiGrade failed permanently', [
            'session_id' => $this->session->id,
            'error' => $e->getMessage(),
        ]);
    }
}
