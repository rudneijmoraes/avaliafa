<?php

namespace App\Services;

use App\Models\ExamSession;
use Illuminate\Support\Facades\Cache;

class ParallelSessionGuard
{
    private const TTL_SECONDS = 3600; // 1 hour

    private function key(int $studentId, int $examId): string
    {
        return "active_session:{$studentId}:{$examId}";
    }

    public function acquire(ExamSession $session): bool
    {
        $key = $this->key($session->student_id, $session->exam_id);
        $existing = Cache::get($key);

        // Allow same session to re-acquire (reconnect)
        if ($existing && $existing !== $session->id) {
            return false;
        }

        Cache::put($key, $session->id, self::TTL_SECONDS);

        return true;
    }

    public function release(ExamSession $session): void
    {
        $key = $this->key($session->student_id, $session->exam_id);

        // Only release if this session owns the lock
        if (Cache::get($key) === $session->id) {
            Cache::forget($key);
        }
    }

    public function isBlocked(int $studentId, int $examId, int $currentSessionId): bool
    {
        $existing = Cache::get($this->key($studentId, $examId));

        return $existing !== null && $existing !== $currentSessionId;
    }

    public function refresh(ExamSession $session): void
    {
        $key = $this->key($session->student_id, $session->exam_id);

        if (Cache::get($key) === $session->id) {
            Cache::put($key, $session->id, self::TTL_SECONDS);
        }
    }

    public function getActiveSessionId(int $studentId, int $examId): ?int
    {
        return Cache::get($this->key($studentId, $examId));
    }
}
