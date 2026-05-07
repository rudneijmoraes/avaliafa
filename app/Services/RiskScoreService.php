<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\SecurityEvent;

class RiskScoreService
{
    private const SUSPICIOUS_THRESHOLD = 60;

    private const VIOLATION_TYPES = [
        'fullscreen_exit',
        'fullscreen_denied',
        'tab_switch',
        'window_blur',
        'inactivity_timeout',
        'possible_second_monitor',
        'webcam_unavailable',
        'webcam_obstructed',
    ];

    private const EVENT_WEIGHTS = [
        'fullscreen_exit' => 15,
        'fullscreen_denied' => 10,
        'tab_switch' => 20,
        'window_blur' => 10,
        'shortcut_blocked' => 5,
        'right_click_blocked' => 2,
        'inactivity_warning' => 5,
        'inactivity_timeout' => 15,
        'possible_second_monitor' => 25,
        'webcam_unavailable' => 20,
        'webcam_obstructed' => 20,
        'connection_lost' => 5,
        'connection_restored' => -2,
        'violation_warning' => 5,
        'violation_limit_reached' => 20,
        'face_verification_failed' => 30,
        'face_no_face_detected' => 20,
        'face_multiple_faces' => 35,
    ];

    public function isViolationType(string $eventType): bool
    {
        return in_array($eventType, self::VIOLATION_TYPES, true);
    }

    public function suspiciousThreshold(): int
    {
        return self::SUSPICIOUS_THRESHOLD;
    }

    public function calculateForEvent(ExamSession $session, string $eventType, array $metadata = []): int
    {
        $base = (int) (self::EVENT_WEIGHTS[$eventType] ?? 0);
        $repeatFactor = $this->repeatFactor($session, $eventType);
        $violationBoost = $this->violationBoost($session, $metadata);
        $delta = (int) round(($base * $repeatFactor) + $violationBoost);

        $newRisk = ((int) $session->risk_score) + $delta;

        return max(0, min(100, $newRisk));
    }

    public function shouldFlagAsSuspicious(int $riskScore, string $eventType): bool
    {
        if ($riskScore >= self::SUSPICIOUS_THRESHOLD) {
            return true;
        }

        return in_array($eventType, ['possible_second_monitor', 'webcam_unavailable', 'webcam_obstructed'], true) && $riskScore >= 45;
    }

    public function addPoints(ExamSession $session, string $eventType): int
    {
        $points = self::EVENT_WEIGHTS[$eventType] ?? 0;
        $newScore = min(100, ((int) $session->risk_score) + $points);
        $session->update(['risk_score' => $newScore]);

        return $newScore;
    }

    private function repeatFactor(ExamSession $session, string $eventType): float
    {
        $recentSameEventCount = SecurityEvent::query()
            ->where('session_id', $session->id)
            ->where('type', $eventType)
            ->where('captured_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentSameEventCount >= 5) {
            return 1.5;
        }

        if ($recentSameEventCount >= 3) {
            return 1.25;
        }

        return 1.0;
    }

    private function violationBoost(ExamSession $session, array $metadata): int
    {
        $count = max((int) $session->violation_count, (int) ($metadata['violation_count'] ?? 0));

        if ($count >= 7) {
            return 12;
        }

        if ($count >= 4) {
            return 6;
        }

        if ($count >= 2) {
            return 3;
        }

        return 0;
    }
}
