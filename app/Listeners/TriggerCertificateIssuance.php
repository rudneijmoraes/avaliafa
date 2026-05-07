<?php

namespace App\Listeners;

use App\Events\GradePublished;
use App\Jobs\IssueCertificate;

class TriggerCertificateIssuance
{
    public function handle(GradePublished $event): void
    {
        $session = $event->session;

        if ($session->is_simulation) {
            return;
        }

        $system = $session->exam->clientSystem;

        $autoCertificate = $system->settings['certificate_auto_issue'] ?? false;

        if ($autoCertificate && $session->passed) {
            IssueCertificate::dispatch($session);
        }
    }
}
