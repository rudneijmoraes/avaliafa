<?php

namespace App\Services;

use App\Models\Simulado;
use Illuminate\Support\Facades\DB;

class SimuladoSchedulingService
{
    public function __construct(
        private readonly SimuladoEmailService $emailService,
    ) {}

    public function process(): array
    {
        $activated = 0;
        $deactivated = 0;
        $emailsSent = 0;

        $dueSimulados = Simulado::query()
            ->with(['exam', 'template'])
            ->where('status', 'scheduled')
            ->whereHas('exam', fn ($query) => $query->whereNotNull('starts_at')->where('starts_at', '<=', now()))
            ->get();

        foreach ($dueSimulados as $simulado) {
            if (! $simulado->exam) {
                continue;
            }

            DB::transaction(function () use ($simulado) {
                $simulado->update(['status' => 'active']);
                $simulado->exam->update(['status' => 'active']);
            });

            $activated++;

            if ($simulado->auto_email_enabled && is_null($simulado->activation_notified_at)) {
                $emailsSent += $this->emailService->sendAvailabilityNotification($simulado->fresh(['template']), true);
                $simulado->update(['activation_notified_at' => now()]);
            }
        }

        $expiredSimulados = Simulado::query()
            ->with('exam')
            ->where('status', 'active')
            ->whereHas('exam', fn ($query) => $query->whereNotNull('ends_at')->where('ends_at', '<', now()))
            ->get();

        foreach ($expiredSimulados as $simulado) {
            if (! $simulado->exam) {
                continue;
            }

            DB::transaction(function () use ($simulado) {
                $simulado->update(['status' => 'inactive']);
                $simulado->exam->update(['status' => 'draft']);
            });

            $deactivated++;
        }

        return [
            'activated' => $activated,
            'deactivated' => $deactivated,
            'emails_sent' => $emailsSent,
        ];
    }
}
