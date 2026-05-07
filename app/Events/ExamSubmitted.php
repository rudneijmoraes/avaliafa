<?php

namespace App\Events;

use App\Models\ExamSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ExamSession $session) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam.'.$this->session->exam_id.'.monitor'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'exam.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'student_id' => $this->session->student_id,
            'final_score' => $this->session->final_score,
            'passed' => $this->session->passed,
            'submitted_at' => $this->session->submitted_at,
        ];
    }
}
