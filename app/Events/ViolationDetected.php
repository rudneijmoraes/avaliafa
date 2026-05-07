<?php

namespace App\Events;

use App\Models\ExamSession;
use App\Models\SecurityEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ViolationDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ExamSession $session,
        public readonly SecurityEvent $securityEvent,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam.'.$this->session->exam_id.'.monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'violation.detected';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'student_id' => $this->session->student_id,
            'violation_type' => $this->securityEvent->type,
            'violation_count' => $this->session->violation_count,
            'risk_score' => $this->session->risk_score,
            'captured_at' => $this->securityEvent->captured_at,
        ];
    }
}
