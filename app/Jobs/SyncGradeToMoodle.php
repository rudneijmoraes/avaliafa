<?php

namespace App\Jobs;

use App\Models\ExamSession;
use App\Services\MoodleSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGradeToMoodle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 60;

    public function __construct(public readonly ExamSession $session)
    {
        $this->onQueue('moodle-sync');
    }

    public function handle(MoodleSyncService $service): void
    {
        $service->sync($this->session);
    }

    public function failed(\Throwable $e): void
    {
        \Log::error('SyncGradeToMoodle failed permanently', [
            'session_id' => $this->session->id,
            'error' => $e->getMessage(),
        ]);
    }
}
