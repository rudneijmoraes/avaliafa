<?php

namespace App\Listeners;

use App\Events\CertificateIssued;
use App\Services\AuditLogService;

class RecordCertificateIssuedAudit
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function handle(CertificateIssued $event): void
    {
        $certificate = $event->certificate->loadMissing('exam');

        $this->auditLogService->log(
            action: 'certificate.issued',
            auditable: $certificate,
            newValues: [
                'certificate_id' => $certificate->id,
                'code' => $certificate->code,
                'student_id' => $certificate->student_id,
                'exam_id' => $certificate->exam_id,
                'session_id' => $certificate->session_id,
            ],
            clientSystemId: $certificate->exam?->client_system_id,
        );
    }
}
