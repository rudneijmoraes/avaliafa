<?php

namespace App\Services;

use App\Events\ViolationDetected;
use App\Models\ExamSession;
use App\Models\FaceVerification;
use Illuminate\Support\Facades\Storage;

class FaceVerificationService
{
    private float $threshold = 0.60;
    private int $maxFails = 3;

    public function record(ExamSession $session, array $data): FaceVerification
    {
        $snapshotPath = null;

        if (!empty($data['snapshot']) && $data['result'] !== 'approved') {
            $snapshotPath = $this->saveSnapshot($session, $data['snapshot']);
        }

        $verification = FaceVerification::create([
            'exam_session_id' => $session->id,
            'student_id'      => $session->student_id,
            'type'            => $data['type'],
            'result'          => $data['result'],
            'confidence'      => $data['confidence'] ?? null,
            'snapshot_path'   => $snapshotPath,
            'metadata'        => $data['metadata'] ?? null,
        ]);

        $this->updateSessionStatus($session, $verification);

        return $verification;
    }

    public function resolveAction(ExamSession $session): string
    {
        return match ($session->face_status) {
            'failed'  => 'warn',
            'pending' => 'retry',
            default   => 'continue',
        };
    }

    public function getSnapshotUrl(FaceVerification $verification): ?string
    {
        if (!$verification->snapshot_path) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl(
            $verification->snapshot_path,
            now()->addMinutes(15)
        );
    }

    private function updateSessionStatus(ExamSession $session, FaceVerification $v): void
    {
        if ($v->result === 'approved') {
            $session->update([
                'face_status'        => 'verified',
                'face_checks_failed' => 0,
            ]);
            return;
        }

        // skipped não penaliza
        if ($v->result === 'skipped') {
            return;
        }

        $session->increment('face_checks_failed');
        $session->refresh();

        if ($session->face_checks_failed >= $this->maxFails) {
            $session->update(['face_status' => 'failed']);

            $eventType = match ($v->result) {
                'no_face'        => 'face_no_face_detected',
                'multiple_faces' => 'face_multiple_faces',
                default          => 'face_verification_failed',
            };

            app(RiskScoreService::class)->addPoints($session, $eventType);

            event(new ViolationDetected($session, $eventType));
        }
    }

    private function saveSnapshot(ExamSession $session, string $base64): string
    {
        $imageData = base64_decode(
            preg_replace('/^data:image\/\w+;base64,/', '', $base64)
        );

        $path = "face-snapshots/{$session->id}/" . now()->format('His') . '.jpg';
        Storage::disk('private')->put($path, $imageData);

        return $path;
    }
}
