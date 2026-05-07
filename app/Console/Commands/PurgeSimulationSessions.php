<?php

namespace App\Console\Commands;

use App\Models\ExamSession;
use Illuminate\Console\Command;

class PurgeSimulationSessions extends Command
{
    protected $signature = 'exam-simulations:purge {--hours=24}';

    protected $description = 'Remove sessões de simulação antigas';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $threshold = now()->subHours($hours);
        $deleted = 0;

        ExamSession::query()
            ->where('is_simulation', true)
            ->where('created_at', '<=', $threshold)
            ->where('status', '!=', 'in_progress')
            ->chunkById(200, function ($sessions) use (&$deleted) {
                foreach ($sessions as $session) {
                    $session->forceDelete();
                    $deleted++;
                }
            });

        $this->info("Sessões simuladas removidas: {$deleted}");

        return self::SUCCESS;
    }
}
