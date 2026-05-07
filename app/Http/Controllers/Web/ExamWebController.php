<?php

namespace App\Http\Controllers\Web;

use App\Events\ExamStarted;
use App\Events\ExamSubmitted;
use App\Http\Controllers\Controller;
use App\Jobs\RecordSecurityEvent;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\SimuladoRegistration;
use App\Models\Snapshot;
use App\Services\Api\SessionTokenService;
use App\Services\ParallelSessionGuard;
use App\Services\SmartShuffleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ExamWebController extends Controller
{
    public function __construct(
        private readonly SessionTokenService $tokenService,
        private readonly ParallelSessionGuard $sessionGuard,
        private readonly SmartShuffleService $shuffleService,
    ) {}

    public function moodleLaunch(Request $request, Exam $exam): View
    {
        abort_unless($exam->isPublished(), 404);

        return view('exam.moodle-launch', [
            'exam' => $exam,
        ]);
    }

    public function startFromMoodle(Request $request, Exam $exam): \Illuminate\Http\RedirectResponse
    {
        abort_unless($exam->isPublished(), 404);

        $validated = $request->validate([
            'student_reference' => 'required|string|max:50',
        ]);

        $session = $this->findSessionForMoodleLaunch($exam, $validated['student_reference']);

        if (! $session) {
            return back()
                ->withErrors(['student_reference' => 'Nenhuma sessão liberada foi encontrada para este CPF nesta prova.'])
                ->withInput();
        }

        $token = $this->tokenService->generate($session->fresh('exam'));

        return redirect()->route('exam.start', ['token' => $token]);
    }

    /**
     * Entry point from deep link.
     * Validates JWT and shows the "Iniciar Prova" screen.
     */
    public function start(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $token = $request->query('token');
        $payload = $this->tokenService->decode($token ?? '');

        if (! $payload) {
            return view('exam.error', ['message' => 'Link de acesso inválido ou expirado.']);
        }

        $session = ExamSession::with(['exam', 'student'])
            ->where('id', $payload->session_id)
            ->where('token_jti', $payload->jti)
            ->whereIn('status', ['pending', 'in_progress'])
            ->firstOrFail();

        if ($session->isExpired()) {
            $session->update(['status' => 'expired']);

            return view('exam.error', ['message' => 'O tempo desta sessão expirou.']);
        }

        // Store session in web session for subsequent requests
        $request->session()->put('exam_session_id', $session->id);
        $request->session()->put('exam_session_jwt', $payload->jti);

        $simulado = null;
        if ($session->is_simulation) {
            $simulado = SimuladoRegistration::query()
                ->where('exam_session_id', $session->id)
                ->with('simulado')
                ->first()
                ?->simulado;
        }

        return view('exam.start', [
            'session' => $session,
            'exam' => $session->exam,
            'student' => $session->student,
            'questionsCount' => $session->exam->questions()->count(),
            'simulado' => $simulado,
        ]);
    }

    /**
     * Confirm start — transition to in_progress, build question/choice order.
     */
    public function confirmStart(Request $request, int $sessionId): \Illuminate\Http\RedirectResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            return redirect()->route('exam.error')->with('message', 'Sessão inválida.');
        }

        // Parallel session guard
        if (! $this->sessionGuard->acquire($session)) {
            return view('exam.error', ['message' => 'Você já possui outra sessão ativa para esta prova.']);
        }

        // Build shuffle order
        $questionOrder = $this->shuffleService->buildQuestionOrder($session->exam, $session);
        $choiceOrder = $this->shuffleService->buildChoiceOrder($session->exam, $questionOrder);

        $session->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes($session->exam->duration_minutes),
            'question_order' => $questionOrder,
            'choice_order' => $choiceOrder,
        ]);

        ExamStarted::dispatch($session);

        return redirect()->route('exam.show', $sessionId);
    }

    /**
     * Show the exam (questions).
     */
    public function show(Request $request, int $sessionId): View|\Illuminate\Http\RedirectResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session || $session->status !== 'in_progress') {
            if ($session && in_array($session->status, ['submitted', 'graded'], true)) {
                return redirect()->route('exam.result', $session->id);
            }

            return redirect()->route('exam.error')
                ->with('message', 'Esta tentativa não está mais ativa. Verifique seus simulados para iniciar uma nova tentativa.');
        }

        if ($session->isExpired()) {
            $session->update(['status' => 'expired']);

            return view('exam.error', ['message' => 'O tempo da prova expirou.']);
        }

        // Load questions in the shuffled order
        $questionIds = $session->question_order ?? $session->exam->questions()->pluck('questions.id')->toArray();
        $choiceOrder = $session->choice_order ?? [];

        $questionsRaw = $session->exam->questions()
            ->with('choices')
            ->whereIn('questions.id', $questionIds)
            ->get()
            ->keyBy('id');

        // Rebuild in correct order with shuffled choices
        $questions = collect($questionIds)->map(function ($id) use ($questionsRaw, $choiceOrder) {
            $q = $questionsRaw[$id] ?? null;
            if (! $q) {
                return null;
            }

            if (isset($choiceOrder[$id])) {
                $orderedChoices = collect($choiceOrder[$id])
                    ->map(fn ($cid) => $q->choices->firstWhere('id', $cid))
                    ->filter();
                $q->setRelation('choices', $orderedChoices->values());
            }

            return $q;
        })->filter()->values();

        $questionBlocks = $this->buildQuestionBlocks($session->exam, $questions);

        // Load saved answers
        $savedAnswers = $session->answers()
            ->get()
            ->mapWithKeys(fn ($a) => [
                $a->question_id => $a->choice_ids ?? $a->choice_id,
            ]);

        return view('exam.show', [
            'session' => $session,
            'exam' => $session->exam,
            'student' => $session->student,
            'questions' => $questions,
            'questionBlocks' => $questionBlocks,
            'savedAnswers' => $savedAnswers,
        ]);
    }

    /**
     * Save progress (auto-save endpoint).
     */
    public function saveProgress(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session || $session->status !== 'in_progress') {
            return response()->json(['success' => false], 422);
        }

        $rawAnswers = $request->input('answers', []);
        $allowedQuestionIds = $this->allowedQuestionIdsForSession($session);
        $answers = $this->normalizeAnswersPayload(is_array($rawAnswers) ? $rawAnswers : [], $allowedQuestionIds);

        foreach ($answers as $questionId => $value) {
            $session->answers()->updateOrCreate(
                ['question_id' => $questionId],
                [
                    'choice_id' => is_int($value) ? $value : null,
                    'choice_ids' => is_array($value) && $value !== [] ? $value : null,
                ]
            );
        }

        // Refresh session TTL in Redis guard
        $this->sessionGuard->refresh($session);

        return response()->json(['success' => true]);
    }

    /**
     * Record a security event from the browser.
     */
    public function securityEvent(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            return response()->json(['success' => false], 422);
        }

        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'metadata' => 'nullable|array',
        ]);

        RecordSecurityEvent::dispatch(
            $session,
            $validated['type'],
            $validated['metadata'] ?? []
        );

        return response()->json(['success' => true]);
    }

    /**
     * Store device fingerprint.
     */
    public function storeFingerprint(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            return response()->json(['success' => false], 422);
        }

        $session->update([
            'device_fingerprint' => $request->input('fingerprint'),
            'device_metadata' => $request->input('metadata'),
        ]);

        return response()->json(['success' => true]);
    }

    public function storeSnapshot(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            Log::warning('Snapshot upload failed: session not resolved', [
                'requested_session_id' => $sessionId,
                'stored_session_id' => $request->session()->get('exam_session_id'),
                'has_session' => $request->hasSession(),
                'is_secure' => $request->isSecure(),
            ]);

            return response()->json(['success' => false, 'reason' => 'session_not_resolved'], 422);
        }

        if ($session->status !== 'in_progress') {
            return response()->json(['success' => false, 'reason' => 'session_not_active'], 422);
        }

        $validated = $request->validate([
            'snapshot' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'trigger' => 'nullable|in:start,scheduled,violation,end',
            'captured_at' => 'nullable|date',
        ]);

        $file = $validated['snapshot'];
        $hash = hash_file('sha256', $file->getRealPath());
        $storedPath = Storage::disk('public')->putFile("snapshots/{$session->id}", $file);

        Snapshot::query()->create([
            'session_id' => $session->id,
            'path' => $storedPath,
            'sha256_hash' => $hash,
            'trigger' => $validated['trigger'] ?? 'scheduled',
            'captured_at' => $validated['captured_at'] ?? now(),
        ]);

        Log::info('Snapshot stored', [
            'session_id' => $session->id,
            'path' => $storedPath,
            'trigger' => $validated['trigger'] ?? 'scheduled',
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Store profile photo captured from webcam during exam start.
     */
    public function storeProfilePhoto(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            return response()->json(['success' => false, 'message' => 'Sessão inválida.'], 422);
        }

        $request->validate([
            'photo' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $student = $session->student;
        $oldPath = $student->profile_photo_path;

        // Remove old photo if exists
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $storedPath = Storage::disk('public')->putFileAs(
            "profile-photos/{$student->id}",
            $request->file('photo'),
            'photo.jpg'
        );

        $student->update(['profile_photo_path' => $storedPath]);
        $student->refresh();

        return response()->json([
            'success' => true,
            'photo_url' => $student->profilePhotoUrl(),
        ]);
    }

    /**
     * Salva foto de referência facial capturada pelo próprio aluno ao iniciar a prova.
     * Usada quando nenhuma foto de referência foi cadastrada pelo admin.
     * Salva em face-references/{user_id}/reference.jpg (disco private) para auditoria.
     */
    public function storeFaceSelfReference(Request $request, int $sessionId): \Illuminate\Http\JsonResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session) {
            return response()->json(['success' => false], 422);
        }

        $request->validate([
            'snapshot' => 'required|string|max:500000',
        ]);

        $student = $session->student;

        // Só salva se não tiver foto de referência (não sobrescreve a do admin)
        if ($student->face_reference_photo) {
            return response()->json(['success' => true, 'message' => 'already_set']);
        }

        $imageData = base64_decode(
            preg_replace('/^data:image\/\w+;base64,/', '', $request->snapshot)
        );

        $path = "face-references/{$student->id}/reference.jpg";
        Storage::disk('private')->put($path, $imageData);
        $student->update(['face_reference_photo' => $path]);

        return response()->json(['success' => true]);
    }

    /**
     * Submit the exam.
     */
    public function submit(Request $request, int $sessionId): \Illuminate\Http\RedirectResponse
    {
        $session = $this->resolveSession($request, $sessionId);

        if (! $session || ! in_array($session->status, ['in_progress', 'expired'])) {
            return redirect()->route('exam.error');
        }

        $decodedAnswers = json_decode($request->input('answers', '{}'), true);
        $allowedQuestionIds = $this->allowedQuestionIdsForSession($session);
        $answers = $this->normalizeAnswersPayload(is_array($decodedAnswers) ? $decodedAnswers : [], $allowedQuestionIds);

        // Save all answers
        foreach ($answers as $questionId => $value) {
            $session->answers()->updateOrCreate(
                ['question_id' => $questionId],
                [
                    'choice_id' => is_int($value) ? $value : null,
                    'choice_ids' => is_array($value) && $value !== [] ? $value : null,
                ]
            );
        }

        // Grade the exam
        $this->gradeSession($session);

        $session->update([
            'status' => 'graded',
            'submitted_at' => now(),
        ]);

        $this->sessionGuard->release($session);

        ExamSubmitted::dispatch($session->fresh());

        $showResultImmediately = (bool) data_get($session->exam?->settings ?? [], 'simulado.show_result_immediately', true);
        if ($session->is_simulation && ! $showResultImmediately) {
            return redirect()->route('simulados.minha-area')
                ->with('success', 'Simulado concluído com sucesso');
        }

        return redirect()->route('exam.result', $sessionId);
    }

    /**
     * Show exam result.
     */
    public function result(Request $request, int $sessionId): View
    {
        $session = ExamSession::with(['exam.clientSystem', 'student', 'certificate', 'ltiResourceLink.registration'])
            ->findOrFail($sessionId);

        return view('exam.result', [
            'session' => $session,
            'exam' => $session->exam,
            'student' => $session->student,
            'certificate' => $session->certificate,
            'postExamDestination' => $this->resolvePostExamDestination($session),
        ]);
    }

    public function enterDashboard(Request $request, int $sessionId): \Illuminate\Http\RedirectResponse
    {
        $session = ExamSession::with('student')->findOrFail($sessionId);

        if (! $session->is_simulation) {
            return redirect()->route('exam.result', $session->id);
        }

        $storedSessionId = (int) $request->session()->get('exam_session_id', 0);
        $storedJwt = (string) $request->session()->get('exam_session_jwt', '');

        if (
            $storedSessionId !== (int) $session->id ||
            $storedJwt === '' ||
            $storedJwt !== (string) $session->token_jti
        ) {
            return redirect()->route('login')
                ->with('error', 'Nao foi possivel validar sua sessao de prova para acesso automatico.');
        }

        $student = $session->student;

        if (! $student || ! $student->isStudent() || ! $student->active) {
            return redirect()->route('login')
                ->with('error', 'Conta de estudante indisponivel para acesso automatico.');
        }

        if (! Auth::check() || Auth::id() !== $student->id) {
            Auth::login($student);
            $request->session()->regenerate();
        }

        return redirect()->route('dashboard');
    }

    /**
     * Show terminated screen.
     */
    public function terminated(Request $request, int $sessionId): View
    {
        $session = ExamSession::with(['exam'])->findOrFail($sessionId);
        $this->sessionGuard->release($session);

        return view('exam.error', [
            'message' => $session->is_simulation
                ? 'Simulado encerrado. Neste modo, violações não impactam relatórios oficiais.'
                : 'A prova foi encerrada por excesso de violações.',
        ]);
    }

    /**
     * Monitor panel (professor).
     */
    public function monitor(Request $request, int $examId): View
    {
        $exam = \App\Models\Exam::with('clientSystem')->findOrFail($examId);

        $sessions = ExamSession::with([
            'student:id,name,email,profile_photo_path',
            'snapshots' => fn ($query) => $query->latest('captured_at')->limit(12),
            'securityEvents' => fn ($query) => $query->latest('captured_at')->limit(10),
        ])
            ->withCount('snapshots')
            ->where('exam_id', $examId)
            ->whereIn('status', ['pending', 'in_progress', 'submitted', 'graded', 'expired', 'terminated'])
            ->orderByRaw("
                case status
                    when 'in_progress' then 0
                    when 'graded' then 1
                    when 'submitted' then 2
                    when 'expired' then 3
                    when 'terminated' then 4
                    when 'pending' then 5
                    else 6
                end
            ")
            ->orderByDesc('submitted_at')
            ->orderByDesc('started_at')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'student_name' => $s->student?->name,
                'student_email' => $s->student?->email,
                'student_photo' => $s->student?->profilePhotoUrl(),
                'status' => $s->status,
                'violation_count' => $s->violation_count,
                'risk_score' => $s->risk_score,
                'final_score' => $s->final_score,
                'started_at' => $s->started_at?->toIso8601String(),
                'submitted_at' => $s->submitted_at?->toIso8601String(),
                'updated_at' => $s->updated_at?->toIso8601String(),
                'snapshots_count' => $s->snapshots_count,
                'snapshots' => $s->snapshots->map(fn ($snapshot) => [
                    'id' => $snapshot->id,
                    'url' => $snapshot->fileUrl(),
                    'trigger' => $snapshot->trigger,
                    'captured_at' => $snapshot->captured_at?->toIso8601String(),
                    'sha256_hash' => $snapshot->sha256_hash,
                ])->values(),
                'security_events' => $s->securityEvents->map(fn ($event) => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'captured_at' => $event->captured_at?->toIso8601String(),
                    'metadata' => $event->metadata ?? [],
                ])->values(),
                'last_event' => $s->securityEvents->first()
                    ? ($s->securityEvents->first()->type.' - '.$s->securityEvents->first()->captured_at?->format('H:i:s'))
                    : null,
            ]);

        return view('monitor.index', [
            'exam' => $exam,
            'sessions' => $sessions,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function resolveSession(Request $request, int $sessionId): ?ExamSession
    {
        $storedId = $request->session()->get('exam_session_id');

        if ($storedId !== $sessionId) {
            return null;
        }

        return ExamSession::with(['exam.clientSystem', 'student'])
            ->find($sessionId);
    }

    private function findSessionForMoodleLaunch(Exam $exam, string $reference): ?ExamSession
    {
        $normalized = preg_replace('/\D+/', '', $reference);

        return ExamSession::with(['exam', 'student'])
            ->where('exam_id', $exam->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->whereHas('student', function ($query) use ($exam, $reference, $normalized) {
                $query->where('client_system_id', $exam->client_system_id)
                    ->where('role', 'student')
                    ->where(function ($studentQuery) use ($reference, $normalized) {
                        $studentQuery->where('id', $reference);

                        if ($normalized !== '') {
                            $studentQuery->orWhere('cpf', $normalized);
                        }
                    });
            })
            ->orderByRaw("case when status = 'in_progress' then 0 else 1 end")
            ->orderByDesc('attempt_number')
            ->first();
    }

    private function buildQuestionBlocks(Exam $exam, \Illuminate\Support\Collection $questions): \Illuminate\Support\Collection
    {
        $exam->loadMissing([
            'blocks' => fn ($query) => $query
                ->with([
                    'examQuestions' => fn ($blockQuestions) => $blockQuestions->orderBy('order'),
                ])
                ->orderBy('order'),
        ]);

        if ($exam->blocks->isEmpty()) {
            return collect();
        }

        $questionsById = $questions->keyBy('id');

        return $exam->blocks
            ->sortBy('order')
            ->map(function ($block) use ($questionsById) {
                $blockQuestions = $block->examQuestions
                    ->sortBy('order')
                    ->map(fn ($examQuestion) => $questionsById->get($examQuestion->question_id))
                    ->filter()
                    ->values();

                if ($blockQuestions->isEmpty()) {
                    return null;
                }

                return (object) [
                    'id' => $block->id,
                    'title' => $block->title,
                    'base_text' => $block->base_text,
                    'order' => $block->order,
                    'questions' => $blockQuestions,
                ];
            })
            ->filter()
            ->values();
    }

    private function gradeSession(ExamSession $session): void
    {
        $exam = $session->exam;
        $answers = $session->answers()->with(['question.choices'])->get();
        $weights = $exam->examQuestions()
            ->pluck('weight', 'question_id')
            ->map(fn ($weight) => (float) $weight);
        $earnedScore = 0;

        foreach ($answers as $answer) {
            $q = $answer->question;
            $weight = (float) ($weights[$q->id] ?? 1);
            $correct = false;

            if ($q->type === 'multiple_choice' || $q->type === 'true_false') {
                $correctChoiceId = $q->choices->firstWhere('is_correct', true)?->id;
                $answeredChoiceId = filled($answer->choice_id) ? (int) $answer->choice_id : null;
                $expectedChoiceId = filled($correctChoiceId) ? (int) $correctChoiceId : null;
                $correct = ! is_null($answeredChoiceId) && ! is_null($expectedChoiceId) && $answeredChoiceId === $expectedChoiceId;
            } elseif ($q->type === 'multiple_answer') {
                $correctIds = $q->choices->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
                $answeredIds = collect($answer->choice_ids ?? [])->sort()->values()->toArray();
                $correct = $correctIds === $answeredIds;
            }

            $score = $correct ? $weight : 0;
            $questionExplanation = trim((string) ($q->explanation ?? ''));
            $feedbackText = $questionExplanation !== ''
                ? $questionExplanation
                : ($correct ? 'Resposta correta.' : 'Resposta incorreta.');

            $answer->update([
                'is_correct' => $correct,
                'score' => $score,
                'feedback' => $feedbackText,
            ]);
            $earnedScore += $score;
        }

        $rawScore = round($earnedScore, 2);
        $finalScore = $rawScore;
        $passed = $finalScore >= $exam->passing_score;

        $session->update([
            'raw_score' => $rawScore,
            'final_score' => $finalScore,
            'passed' => $passed,
            'grade_published' => true,
        ]);

        \App\Events\GradePublished::dispatch($session->fresh());
    }

    private function allowedQuestionIdsForSession(ExamSession $session): array
    {
        $ids = collect($session->question_order ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        return $session->exam->questions()->pluck('questions.id')->map(fn ($id) => (int) $id)->all();
    }

    private function normalizeAnswersPayload(array $answers, array $allowedQuestionIds): array
    {
        $allowed = array_flip($allowedQuestionIds);
        $normalized = [];

        foreach ($answers as $rawQuestionId => $value) {
            $questionId = $this->extractQuestionId($rawQuestionId);

            if (! $questionId || ! isset($allowed[$questionId])) {
                continue;
            }

            $normalized[$questionId] = $this->normalizeAnswerValue($value);
        }

        return $normalized;
    }

    private function resolvePostExamDestination(ExamSession $session): array
    {
        if ($session->is_simulation) {
            $sameStudentAuthenticated = Auth::check() && Auth::id() === (int) $session->student_id;

            return [
                'url' => $sameStudentAuthenticated
                    ? route('simulados.minha-area')
                    : route('exam.enter-dashboard', $session->id),
                'label' => $sameStudentAuthenticated
                    ? 'Voltar para meu painel'
                    : 'Entrar no sistema',
            ];
        }

        $returnUrl = trim((string) data_get($session->ltiResourceLink?->settings, 'launch_presentation.return_url', ''));

        if ($returnUrl !== '') {
            return [
                'url' => $returnUrl,
                'label' => 'Ver minhas notas no Moodle',
            ];
        }

        $baseUrl = trim((string) ($session->ltiResourceLink?->registration?->issuer ?: $session->exam?->clientSystem?->getMoodleUrl()));

        if ($baseUrl !== '') {
            $courseId = (int) (data_get($session->exam?->settings, 'moodle.course_id')
                ?: data_get($session->exam?->clientSystem?->moodle_config, 'course_id')
                ?: 0);

            $path = $courseId > 0
                ? '/grade/report/user/index.php?id='.$courseId
                : '/grade/report/overview/index.php';

            return [
                'url' => rtrim($baseUrl, '/').$path,
                'label' => 'Ver minhas notas no Moodle',
            ];
        }

        return [
            'url' => url('/'),
            'label' => 'Voltar para Tela Inicial',
        ];
    }

    private function extractQuestionId(mixed $rawQuestionId): ?int
    {
        if (is_int($rawQuestionId) && $rawQuestionId > 0) {
            return $rawQuestionId;
        }

        if (is_string($rawQuestionId)) {
            if (ctype_digit($rawQuestionId)) {
                $id = (int) $rawQuestionId;

                return $id > 0 ? $id : null;
            }

            if (preg_match('/(\d+)/', $rawQuestionId, $matches) === 1) {
                $id = (int) $matches[1];

                return $id > 0 ? $id : null;
            }
        }

        return null;
    }

    private function normalizeAnswerValue(mixed $value): int|array|null
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        if (is_array($value)) {
            $ids = collect($value)
                ->map(function ($item) {
                    if (is_int($item)) {
                        return $item;
                    }

                    if (is_string($item) && ctype_digit($item)) {
                        return (int) $item;
                    }

                    return null;
                })
                ->filter(fn ($id) => is_int($id) && $id > 0)
                ->unique()
                ->values()
                ->all();

            return $ids;
        }

        return null;
    }
}
