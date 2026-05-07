<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use App\Models\SimuladoRegistration;
use App\Models\TeacherTip;
use App\Services\Api\SessionTokenService;
use App\Services\SimuladoReviewPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimuladoAlunoController extends Controller
{
    public function __construct(
        private readonly SessionTokenService $tokenService,
        private readonly SimuladoReviewPdfService $reviewPdfService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $registrations = SimuladoRegistration::query()
            ->with(['simulado.exam', 'simulado.hub', 'examSession', 'participant'])
            ->whereHas('participant', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        $attemptsByRegistrationId = [];
        $retryBlockedByRegistrationId = [];
        $attemptsByExamId = $this->completedSimulationAttemptsByExamId((int) $user->id);

        foreach ($registrations as $registration) {
            $examId = (int) ($registration->simulado?->exam_id ?? 0);
            $attempts = $examId > 0
                ? (int) ($attemptsByExamId[$examId] ?? 0)
                : 0;
            $attemptsByRegistrationId[$registration->id] = $attempts;
            $maxAttempts = $this->resolveMaxAttempts($registration->simulado);
            $retryBlockedByRegistrationId[$registration->id] = $maxAttempts > 0 && $attempts >= $maxAttempts;
        }

        $completedRegistrations = $registrations
            ->filter(fn ($registration) => ! is_null($registration->completed_at))
            ->values();

        $pendingRegistrations = $registrations
            ->filter(fn ($registration) => is_null($registration->completed_at))
            ->values();

        $hubSummaries = $this->buildHubSummaries($registrations);

        $participantSystems = SimuladoParticipant::query()
            ->where('user_id', $user->id)
            ->pluck('client_system_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($participantSystems->isEmpty() && $user->client_system_id) {
            $participantSystems = collect([(int) $user->client_system_id]);
        }

        $registeredSimuladoIds = $registrations->pluck('simulado_id')->filter()->unique()->values();

        $availableSimulados = collect();
        if ($participantSystems->isNotEmpty()) {
            $availableSimulados = Simulado::query()
                ->with(['exam', 'hub'])
                ->whereIn('client_system_id', $participantSystems)
                ->where('status', 'active')
                ->whereHas('exam.questions')
                ->when($registeredSimuladoIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $registeredSimuladoIds))
                ->get();
        }

        $registrations = $registrations
            ->sort(function (SimuladoRegistration $left, SimuladoRegistration $right) {
                $createdAtCompare = ($right->simulado?->created_at?->getTimestamp() ?? 0) <=> ($left->simulado?->created_at?->getTimestamp() ?? 0);
                if ($createdAtCompare !== 0) {
                    return $createdAtCompare;
                }

                return (int) ($right->simulado?->id ?? 0) <=> (int) ($left->simulado?->id ?? 0);
            })
            ->values();

        $availableSimulados = $availableSimulados
            ->sort(function (Simulado $left, Simulado $right) {
                $createdAtCompare = ($right->created_at?->getTimestamp() ?? 0) <=> ($left->created_at?->getTimestamp() ?? 0);
                if ($createdAtCompare !== 0) {
                    return $createdAtCompare;
                }

                return (int) $right->id <=> (int) $left->id;
            })
            ->values();

        $simuladoRows = $this->buildSimuladoRows($registrations, $availableSimulados);

        $tipsBySimuladoId = TeacherTip::with('simulado')
            ->active()
            ->whereNotNull('simulado_id')
            ->get()
            ->keyBy('simulado_id');

        return view('simulados.minha-area', compact(
            'registrations',
            'completedRegistrations',
            'pendingRegistrations',
            'attemptsByRegistrationId',
            'retryBlockedByRegistrationId',
            'hubSummaries',
            'availableSimulados',
            'simuladoRows',
            'tipsBySimuladoId'
        ));
    }

    public function showHub(string $slug)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $registrations = SimuladoRegistration::query()
            ->with(['simulado.exam', 'simulado.hub', 'examSession', 'participant'])
            ->whereHas('participant', fn ($query) => $query->where('user_id', $user->id))
            ->get()
            ->filter(function (SimuladoRegistration $registration) use ($slug) {
                $hubSlug = $registration->simulado?->hub?->slug
                    ?: trim((string) data_get($registration->simulado?->settings ?? [], 'public_hub_slug', $registration->simulado?->slug));

                return $hubSlug === $slug;
            })
            ->values();

        abort_if($registrations->isEmpty(), 404);

        $completedRegistrations = $registrations
            ->filter(fn ($registration) => ! is_null($registration->completed_at))
            ->values();

        $pendingRegistrations = $registrations
            ->filter(fn ($registration) => is_null($registration->completed_at))
            ->values();

        $attemptsByRegistrationId = [];
        $retryBlockedByRegistrationId = [];
        $attemptsByExamId = $this->completedSimulationAttemptsByExamId((int) $user->id);

        foreach ($registrations as $registration) {
            $examId = (int) ($registration->simulado?->exam_id ?? 0);
            $attempts = $examId > 0
                ? (int) ($attemptsByExamId[$examId] ?? 0)
                : 0;
            $attemptsByRegistrationId[$registration->id] = $attempts;
            $maxAttempts = $this->resolveMaxAttempts($registration->simulado);
            $retryBlockedByRegistrationId[$registration->id] = $maxAttempts > 0 && $attempts >= $maxAttempts;
        }

        $hubSummary = $this->buildHubSummaries($registrations)->first();

        return view('simulados.hub-show', compact(
            'registrations',
            'completedRegistrations',
            'pendingRegistrations',
            'attemptsByRegistrationId',
            'retryBlockedByRegistrationId',
            'hubSummary'
        ));
    }

    public function retry(Request $request, Simulado $simulado)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $simulado->loadMissing('exam');

        if (! $simulado->exam || $simulado->status !== 'active') {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', 'Este simulado nao esta disponivel para nova tentativa no momento.');
        }

        if (! $simulado->exam->questions()->exists()) {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', 'Este simulado ainda nao possui questoes vinculadas.');
        }

        $participant = SimuladoParticipant::query()
            ->where('client_system_id', $simulado->client_system_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $participant) {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', 'Nao foi possivel localizar seu cadastro neste simulado.');
        }

        $registration = SimuladoRegistration::query()->firstOrCreate(
            [
                'simulado_id' => $simulado->id,
                'participant_id' => $participant->id,
            ],
            [
                'status' => 'registered',
                'registered_at' => now(),
            ]
        );

        $activeSession = ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByDesc('attempt_number')
            ->first();

        if ($activeSession) {
            $registration->update([
                'exam_session_id' => $activeSession->id,
                'status' => 'in_progress',
                'started_at' => $registration->started_at ?? now(),
            ]);

            return redirect()->away($this->tokenService->getDeepLink($activeSession));
        }

        $maxAttempts = $this->resolveMaxAttempts($simulado);
        $attemptsCount = $this->completedSimulationAttemptsCount((int) $user->id, (int) $simulado->exam_id);

        if ($maxAttempts > 0 && $attemptsCount >= $maxAttempts) {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', "Limite de tentativas atingido para este simulado ({$maxAttempts}).");
        }

        $nextAttempt = (int) ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $user->id)
            ->max('attempt_number') + 1;

        $session = ExamSession::query()->create([
            'exam_id' => $simulado->exam_id,
            'student_id' => $user->id,
            'attempt_number' => $nextAttempt,
            'status' => 'pending',
            'expires_at' => now()->addMinutes((int) $simulado->exam->duration_minutes + 30),
            'is_simulation' => true,
            'launch_source' => 'simulado',
        ]);

        $registration->update([
            'exam_session_id' => $session->id,
            'status' => 'registered',
            'started_at' => now(),
            'registered_at' => $registration->registered_at ?? now(),
        ]);

        $participant->update([
            'last_access_at' => now(),
        ]);

        return redirect()->away($this->tokenService->getDeepLink($session));
    }

    public function reviewPdf(Request $request, Simulado $simulado)
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $registration = SimuladoRegistration::query()
            ->with(['simulado.exam.questions.choices', 'examSession.answers', 'participant'])
            ->where('simulado_id', $simulado->id)
            ->whereHas('participant', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('exam_session_id')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();

        if (! $registration) {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', 'Nenhum resultado disponível para gerar o PDF de revisão.');
        }

        $session = $registration->examSession;
        if (! $session || ! in_array($session->status, ['submitted', 'graded'], true)) {
            return redirect()
                ->route('simulados.minha-area')
                ->with('error', 'O PDF de revisão só fica disponível após a conclusão do simulado.');
        }

        $reviewData = $this->reviewPdfService->build($registration);

        $participant = $registration->participant;
        $fullName = trim(($participant->first_name ?? '').' '.($participant->last_name ?? ''));
        $cpf = preg_replace('/\D/', '', $participant->cpf ?? '');
        $cpfFormatted = strlen($cpf) === 11
            ? sprintf('%s.***.***-%s', substr($cpf, 0, 3), substr($cpf, -2))
            : ($participant->cpf ?? '');

        $logoAnasps = $this->imageToDataUri(public_path('imagem/logo-anasps.png'));
        $logoFaculdade = $this->imageToDataUri(public_path('imagem/logo-deitada-transparente.png'));

        $html = view('pdf.simulado-review', [
            'simulado' => $registration->simulado,
            'participantName' => $fullName,
            'participantCpf' => $cpfFormatted,
            'session' => $session,
            'questions' => $reviewData['questions'],
            'correctCount' => $reviewData['correct_count'],
            'wrongCount' => $reviewData['wrong_count'],
            'totalCount' => $reviewData['total_count'],
            'percentage' => $reviewData['percentage'],
            'completedAt' => $registration->completed_at,
            'logoAnasps' => $logoAnasps,
            'logoFaculdade' => $logoFaculdade,
        ])->render();

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('AvaliaFA');
        $pdf->SetAuthor('AvaliaFA - Sistema de Simulados');
        $pdf->SetTitle("Revisão - {$registration->simulado->name}");
        $pdf->setHeaderFont(['helvetica', '', 8]);
        $pdf->setFooterFont(['helvetica', '', 7]);
        $pdf->SetPrintHeader(false);
        $pdf->SetMargins(9, 8, 9);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 14);

        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        return response()->make(
            $pdf->Output("revisao-{$simulado->id}-{$session->id}.pdf", 'I'),
            200,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        if (! $user->isStudent()) {
            return response()->json([
                'items' => [],
                'latest_id' => 0,
            ]);
        }

        $afterId = max(0, (int) $request->integer('after_id', 0));

        $participantSystems = SimuladoParticipant::query()
            ->where('user_id', $user->id)
            ->pluck('client_system_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        if ($participantSystems->isEmpty() && $user->client_system_id) {
            $participantSystems = collect([(int) $user->client_system_id]);
        }

        if ($participantSystems->isEmpty()) {
            return response()->json([
                'items' => [],
                'latest_id' => 0,
            ]);
        }

        $registrations = SimuladoRegistration::query()
            ->with(['simulado.hub'])
            ->whereHas('participant', fn ($query) => $query->where('user_id', $user->id))
            ->get();

        $registeredSimuladoIds = $registrations
            ->pluck('simulado_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();

        $trackedHubSlugs = $registrations
            ->map(function (SimuladoRegistration $registration) {
                $simulado = $registration->simulado;

                return $simulado?->hub?->slug
                    ?: trim((string) data_get($simulado?->settings ?? [], 'public_hub_slug', $simulado?->slug));
            })
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values();

        $availableSimulados = Simulado::query()
            ->with(['hub:id,slug,name,landing_title', 'exam:id'])
            ->whereIn('client_system_id', $participantSystems)
            ->where('status', 'active')
            ->whereHas('exam.questions')
            ->when($registeredSimuladoIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $registeredSimuladoIds))
            ->get()
            ->filter(function (Simulado $simulado) use ($trackedHubSlugs) {
                if ($trackedHubSlugs->isEmpty()) {
                    return true;
                }

                $hubSlug = $simulado->hub?->slug
                    ?: trim((string) data_get($simulado->settings ?? [], 'public_hub_slug', $simulado->slug));

                return $trackedHubSlugs->contains($hubSlug);
            })
            ->sortByDesc('id')
            ->values();

        $latestId = (int) ($availableSimulados->max('id') ?? 0);

        $items = $availableSimulados
            ->filter(fn (Simulado $simulado) => $simulado->id > $afterId)
            ->take(12)
            ->map(function (Simulado $simulado) {
                $entrySlug = $simulado->hub?->slug
                    ?: trim((string) data_get($simulado->settings ?? [], 'public_hub_slug', $simulado->slug));

                $entrySlug = $entrySlug !== '' ? $entrySlug : $simulado->slug;
                $weeklyLabel = trim((string) data_get($simulado->settings ?? [], 'weekly_label', ''));

                return [
                    'id' => $simulado->id,
                    'name' => $simulado->name,
                    'weekly_label' => $weeklyLabel,
                    'hub_name' => $simulado->hub?->landing_title ?: $simulado->hub?->name,
                    'url' => route('simulados.public.inscricao', $entrySlug),
                ];
            })
            ->values();

        return response()->json([
            'items' => $items,
            'latest_id' => max($latestId, $afterId),
        ]);
    }

    private function resolveMaxAttempts(?Simulado $simulado): int
    {
        return max(0, (int) data_get($simulado?->settings ?? [], 'max_attempts', 0));
    }

    private function completedSimulationAttemptsByExamId(int $userId): array
    {
        return ExamSession::query()
            ->where('student_id', $userId)
            ->where('is_simulation', true)
            ->whereIn('status', ['submitted', 'graded'])
            ->selectRaw('exam_id, COUNT(*) as total_attempts')
            ->groupBy('exam_id')
            ->pluck('total_attempts', 'exam_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function completedSimulationAttemptsCount(int $userId, int $examId): int
    {
        return (int) ExamSession::query()
            ->where('student_id', $userId)
            ->where('exam_id', $examId)
            ->where('is_simulation', true)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();
    }

    private function buildSimuladoRows($registrations, $availableSimulados)
    {
        return $registrations
            ->map(function (SimuladoRegistration $registration) {
                return [
                    'type' => 'registration',
                    'registration' => $registration,
                    'simulado' => $registration->simulado,
                ];
            })
            ->concat($availableSimulados->map(function (Simulado $simulado) {
                return [
                    'type' => 'available',
                    'registration' => null,
                    'simulado' => $simulado,
                ];
            }))
            ->sort(function (array $left, array $right) {
                $leftCreatedAt = $left['simulado']?->created_at?->getTimestamp() ?? 0;
                $rightCreatedAt = $right['simulado']?->created_at?->getTimestamp() ?? 0;
                $createdAtCompare = $rightCreatedAt <=> $leftCreatedAt;

                if ($createdAtCompare !== 0) {
                    return $createdAtCompare;
                }

                return (int) ($right['simulado']?->id ?? 0) <=> (int) ($left['simulado']?->id ?? 0);
            })
            ->values();
    }

    private function imageToDataUri(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $binary = @file_get_contents($path);

        if ($binary === false || $binary === '') {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function buildHubSummaries($registrations)
    {
        return $registrations
            ->groupBy(function (SimuladoRegistration $registration) {
                return $registration->simulado?->hub?->slug
                    ?: trim((string) data_get($registration->simulado?->settings ?? [], 'public_hub_slug', $registration->simulado?->slug));
            })
            ->map(function ($hubRegistrations, $hubSlug) {
                $hub = $hubRegistrations->first()?->simulado?->hub;
                $completed = $hubRegistrations->filter(fn ($registration) => ! is_null($registration->completed_at))->values();
                $pending = $hubRegistrations->filter(fn ($registration) => is_null($registration->completed_at))->values();
                $averagePercentage = round((float) $completed->avg(fn ($registration) => (float) $registration->percentage_correct), 1);
                $bestPercentage = round((float) $completed->max(fn ($registration) => (float) $registration->percentage_correct), 1);

                return [
                    'slug' => $hubSlug,
                    'name' => $hub?->name ?: ($hubRegistrations->first()?->simulado?->name ?? 'Ciclo de simulados'),
                    'description' => $hub?->description,
                    'landing_title' => $hub?->landing_title,
                    'total' => $hubRegistrations->count(),
                    'completed' => $completed->count(),
                    'pending' => $pending->count(),
                    'average_percentage' => $averagePercentage,
                    'best_percentage' => $bestPercentage,
                    'progress_percentage' => $hubRegistrations->count() > 0
                        ? round(($completed->count() / $hubRegistrations->count()) * 100, 1)
                        : 0,
                    'registrations' => $hubRegistrations->values(),
                ];
            })
            ->sortBy('name')
            ->values();
    }
}
