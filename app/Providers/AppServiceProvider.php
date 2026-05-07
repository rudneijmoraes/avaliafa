<?php

namespace App\Providers;

use App\Events\CertificateIssued;
use App\Events\ExamStarted;
use App\Events\ExamSubmitted;
use App\Events\GradePublished;
use App\Events\SessionExpired;
use App\Events\SuspiciousActivity;
use App\Listeners\RecordCertificateIssuedAudit;
use App\Listeners\RecordGradePublishedAudit;
use App\Listeners\SendExamWebhook;
use App\Listeners\TriggerCertificateIssuance;
use App\Listeners\TriggerMoodleSync;
use App\Models\ClientSystem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $timezone = (string) config('app.timezone', 'America/Sao_Paulo');

        try {
            $configuredTimezone = trim((string) Setting::get('geral', 'timezone', $timezone));
            if ($configuredTimezone !== '') {
                $timezone = $configuredTimezone;
            }
        } catch (\Throwable) {
        }

        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);

        Request::macro('clientSystem', function (): ?ClientSystem {
            return $this->attributes->get('_client_system');
        });

        Event::listen(GradePublished::class, TriggerMoodleSync::class);
        Event::listen(GradePublished::class, TriggerCertificateIssuance::class);
        Event::listen(GradePublished::class, SendExamWebhook::class);
        Event::listen(GradePublished::class, RecordGradePublishedAudit::class);

        Event::listen(ExamStarted::class, SendExamWebhook::class);
        Event::listen(ExamSubmitted::class, SendExamWebhook::class);
        Event::listen(CertificateIssued::class, SendExamWebhook::class);
        Event::listen(CertificateIssued::class, RecordCertificateIssuedAudit::class);
        Event::listen(SessionExpired::class, SendExamWebhook::class);
        Event::listen(SuspiciousActivity::class, SendExamWebhook::class);
    }
}
