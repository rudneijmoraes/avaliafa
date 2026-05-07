<?php

namespace App\Listeners;

use App\Events\GradePublished;
use App\Services\AuditLogService;

class RecordGradePublishedAudit
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function handle(GradePublished $event): void
    {
        $session = $event->session->loadMissing('exam');

        $this->auditLogService->log(
            action: 'grade.published',
            auditable: $session,
            newValues: [
                'session_id' => $session->id,
                'exam_id' => $session->exam_id,
                'student_id' => $session->student_id,
                'final_score' => $session->final_score,
                'passed' => $session->passed,
            ],
            clientSystemId: $session->exam?->client_system_id,
        );
    }
}
