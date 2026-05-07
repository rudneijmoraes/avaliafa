<?php

namespace App\Listeners;

use App\Events\ExamSubmitted;
use App\Models\SimuladoRegistration;
use App\Services\MoodleSyncService;
use App\Services\SimuladoEmailService;

class HandleSimuladoSubmission
{
    public function __construct(
        private readonly SimuladoEmailService $emailService,
        private readonly MoodleSyncService $moodleSyncService,
    ) {}

    public function handle(ExamSubmitted $event): void
    {
        $session = $event->session->loadMissing('exam.simulado');
        $simulado = $session->exam?->simulado;

        if (! $simulado) {
            return;
        }

        $registration = SimuladoRegistration::query()
            ->where('exam_session_id', $session->id)
            ->first();

        if (! $registration) {
            return;
        }

        $questionCount = count($session->scoringQuestionIds());
        $totalCorrect = $session->answers()->where('is_correct', true)->count();
        $totalWrong = max(0, $questionCount - $totalCorrect);
        $percentageCorrect = $questionCount > 0 ? round(($totalCorrect / $questionCount) * 100, 2) : 0;

        $registration->update([
            'status' => 'completed',
            'completed_at' => $session->submitted_at ?? now(),
            'raw_score' => $session->raw_score,
            'final_score' => $session->final_score,
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalWrong,
            'percentage_correct' => $percentageCorrect,
        ]);

        if ($simulado->moodle_integration_enabled) {
            $this->moodleSyncService->sync($session);
        }

        if ($simulado->auto_email_enabled) {
            $this->emailService->sendResultEmail($registration->fresh(['simulado.template', 'participant', 'examSession']));
        }
    }
}
