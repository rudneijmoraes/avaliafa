<?php

namespace App\Events;

use App\Models\ExamSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ExamSession $session,
        public readonly string $reason,
        public readonly array $metadata = [],
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam.'.$this->session->exam_id.'.monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'suspicious.activity';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'student_id' => $this->session->student_id,
            'risk_score' => $this->session->risk_score,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ];
    }
}
