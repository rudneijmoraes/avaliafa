<?php

namespace App\Jobs;

use App\Events\CertificateIssued;
use App\Models\Certificate;
use App\Models\ExamSession;
use App\Services\CertificateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IssueCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly ExamSession $session) {}

    public function queue(): string
    {
        return 'certificates';
    }

    public function handle(CertificateService $certificateService): void
    {
        if ($this->session->certificate()->exists()) {
            return;
        }

        if (! $this->session->passed) {
            return;
        }

        $certificate = Certificate::create([
            'student_id' => $this->session->student_id,
            'exam_id' => $this->session->exam_id,
            'session_id' => $this->session->id,
            'code' => Str::uuid(),
            'final_score' => $this->session->final_score,
            'issued_at' => now(),
        ]);

        $certificate = $certificateService->generatePdf($certificate);

        CertificateIssued::dispatch($certificate);

        Log::info('Certificate issued', [
            'certificate_id' => $certificate->id,
            'session_id' => $this->session->id,
            'student_id' => $this->session->student_id,
            'code' => $certificate->code,
            'pdf_path' => $certificate->pdf_path,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('IssueCertificate failed', [
            'session_id' => $this->session->id,
            'error' => $e->getMessage(),
        ]);
    }
}
