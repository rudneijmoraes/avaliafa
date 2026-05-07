<?php

namespace App\Jobs;

use App\Events\SuspiciousActivity;
use App\Events\ViolationDetected;
use App\Models\ExamSession;
use App\Models\SecurityEvent;
use App\Services\RiskScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordSecurityEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly ExamSession $session,
        public readonly string $type,
        public readonly array $metadata = [],
    ) {}

    public function queue(): string
    {
        return 'security';
    }

    public function handle(RiskScoreService $riskScoreService): void
    {
        $securityEvent = SecurityEvent::create([
            'session_id' => $this->session->id,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'captured_at' => now(),
        ]);

        $isViolation = $riskScoreService->isViolationType($this->type) && ! $this->session->is_simulation;

        if ($isViolation) {
            $this->session->increment('violation_count');
        }

        $this->session->refresh();
        $newRiskScore = $riskScoreService->calculateForEvent($this->session, $this->type, $this->metadata);
        $this->session->update(['risk_score' => $newRiskScore]);
        $this->session->refresh();

        if ($isViolation) {
            ViolationDetected::dispatch($this->session, $securityEvent);
        }

        if ($riskScoreService->shouldFlagAsSuspicious($newRiskScore, $this->type)) {
            SuspiciousActivity::dispatch(
                $this->session,
                "Risk score threshold reached ({$newRiskScore})",
                [
                    'type' => $this->type,
                    'risk_score' => $newRiskScore,
                    'threshold' => $riskScoreService->suspiciousThreshold(),
                ]
            );
        }
    }
}
