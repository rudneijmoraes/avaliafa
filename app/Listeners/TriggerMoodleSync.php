<?php

namespace App\Listeners;

use App\Events\GradePublished;
use App\Jobs\SyncGradeToMoodle;
use App\Jobs\SyncLtiGrade;
use Illuminate\Support\Facades\Log;

class TriggerMoodleSync
{
    public function handle(GradePublished $event): void
    {
        if ($event->session->is_simulation) {
            return;
        }

        try {
            $session = $event->session;
            $system = $session->exam->clientSystem;
            $hasRestIntegration = $system && $system->hasMoodleIntegration();

            if ($session->wasLaunchedFromLti()) {
                Log::info('TriggerMoodleSync: LTI grade sync', ['session_id' => $session->id]);

                try {
                    SyncLtiGrade::dispatchSync($session);

                    // Check if LTI sync actually succeeded
                    $session->refresh();
                    if ($session->moodle_synced) {
                        return;
                    }

                    Log::warning('TriggerMoodleSync: LTI sync did not mark session as synced, falling back to REST', [
                        'session_id' => $session->id,
                    ]);
                } catch (\Throwable $ltiError) {
                    Log::warning('TriggerMoodleSync: LTI AGS failed, falling back to REST', [
                        'session_id' => $session->id,
                        'lti_error' => $ltiError->getMessage(),
                    ]);
                }

                // Fallback to REST API when LTI AGS fails
                if ($hasRestIntegration) {
                    Log::info('TriggerMoodleSync: REST fallback after LTI failure', ['session_id' => $session->id]);
                    SyncGradeToMoodle::dispatchSync($session);

                    return;
                }

                return;
            }

            if ($hasRestIntegration) {
                Log::info('TriggerMoodleSync: REST grade sync', ['session_id' => $session->id]);
                SyncGradeToMoodle::dispatchSync($session);
            } else {
                Log::info('TriggerMoodleSync: no Moodle integration configured', [
                    'session_id' => $session->id,
                    'system_id' => $system?->id,
                ]);
            }
        } catch (\Throwable $e) {
            // Never let a sync failure break the exam submission flow
            Log::error('TriggerMoodleSync: sync failed (non-blocking)', [
                'session_id' => $event->session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
