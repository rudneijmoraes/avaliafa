<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Discipline;
use App\Models\Exam;
use App\Models\ExamBlock;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamVersion;
use App\Models\Question;
use App\Models\User;
use App\Services\Api\SessionTokenService;
use App\Services\AuditLogService;
use App\Services\MoodleEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProvaController extends Controller
{
    public function __construct(
        private readonly SessionTokenService $tokenService,
        private readonly AuditLogService $auditLogService,
        private readonly MoodleEnrollmentService $enrollmentService,
    ) {}

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $hasDisciplines = \Illuminate\Support\Facades\Schema::hasTable('disciplines');
        $eagerLoad = $hasDisciplines
            ? ['clientSystem', 'creator', 'discipline']
            : ['clientSystem', 'creator'];
        $query = Exam::with($eagerLoad)
            ->withCount(['questions', 'sessions']);

        if (! $user->isSuperAdmin()) {
            $query->where('client_system_id', $user->client_system_id);
        }

        if ($request->filled('busca')) {
            $query->where('title', 'like', '%'.$request->busca.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sistema') && $user->isSuperAdmin()) {
            $query->where('client_system_id', $request->sistema);
        }

        if ($request->filled('disciplina')) {
            $query->where('discipline_id', $request->disciplina);
        }

        $exams = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        $systems = $user->isSuperAdmin()
            ? ClientSystem::where('active', true)->orderBy('name')->get()
            : collect();

        $disciplines = Discipline::safeAll($user->isSuperAdmin() ? null : $user->client_system_id, false);

        $counts = [
            'total' => Exam::when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))->count(),
            'draft' => Exam::when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))->where('status', 'draft')->count(),
            'active' => Exam::when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))->where('status', 'active')->count(),
            'published' => Exam::when(! $user->isSuperAdmin(), fn ($q) => $q->where('client_system_id', $user->client_system_id))->where('status', 'published')->count(),
        ];

        return view('provas.index', compact('exams', 'systems', 'disciplines', 'counts'));
    }

    public function create()
    {
        /** @var User $user */
        $user = Auth::user();

        $systems = $user->isSuperAdmin()
            ? ClientSystem::where('active', true)->orderBy('name')->get()
            : ClientSystem::whereKey($user->client_system_id)->get();

        $disciplines = Discipline::safeAll($user->isSuperAdmin() ? null : $user->client_system_id, false);

        $exam = null;

        return view('provas.form', compact('systems', 'disciplines', 'exam'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'instructions_html' => 'nullable|string',
            'client_system_id' => 'required|exists:client_systems,id',
            'discipline_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== '__new__' && ! \App\Models\Discipline::where('id', $value)->exists()) {
                    $fail('A disciplina selecionada é inválida.');
                }
            }],
            'new_discipline_name' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:5|max:600',
            'passing_score' => 'required|numeric|min:0|max:9999.99',
            'max_violations' => 'required|integer|min:0|max:20',
            'moodle_course_id' => 'nullable|integer|min:0',
            'moodle_activity_id' => 'nullable|integer|min:0',
            'webcam_enabled' => 'nullable|boolean',
            'face_recognition_enabled' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_choices' => 'nullable|boolean',
            'snapshot_interval_seconds' => 'required|integer|min:60|max:600',
            'branding_watermark_text' => 'nullable|string|max:80',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:draft,published,active,closed,archived',
        ]);

        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        if (in_array($data['discipline_id'] ?? null, ['__new__', 'new', ''], true)) {
            $data['discipline_id'] = null;
        }
        $data['discipline_id'] = $this->resolveDisciplineId($data);

        $data['created_by'] = Auth::id();
        $data['webcam_enabled'] = $request->boolean('webcam_enabled');
        $data['face_recognition_enabled'] = $request->boolean('face_recognition_enabled');
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_choices'] = $request->boolean('shuffle_choices');
        $data['settings'] = $this->buildExamSettings($request);

        unset($data['new_discipline_name']);
        $exam = Exam::create($data);
        $exam->blocks()->create([
            'title' => 'Bloco 1',
            'base_text' => null,
            'order' => 1,
        ]);

        $this->auditLogService->log(
            action: 'exam.created',
            auditable: $exam,
            newValues: [
                'exam_id' => $exam->id,
                'title' => $exam->title,
                'status' => $exam->status,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->route('provas.show', $exam)->with('success', 'Prova criada com sucesso!');
    }

    public function show(Request $request, Exam $exam)
    {
        try {
            $this->authorizeExamAccess($exam);

            $exam->load([
                'clientSystem',
                'creator',
                'questions.choices',
                'latestVersion',
                'blocks' => fn ($query) => $query
                    ->withCount('examQuestions')
                    ->with([
                        'examQuestions' => fn ($blockQuestions) => $blockQuestions
                            ->with(['question.choices'])
                            ->orderBy('order'),
                    ])
                    ->orderBy('order'),
            ]);
            $exam->loadCount(['questions', 'sessions', 'blocks']);

            $attachedQuestionIds = $exam->examQuestions()
                ->pluck('question_id');
            /** @var User $user */
            $user = Auth::user();
            $selectedQuestionOption = null;
            $oldQuestionId = (int) old('question_id', 0);

            if ($oldQuestionId > 0) {
                $selectedQuestion = $this->buildAvailableQuestionSearchQuery($exam, $user, collect())
                    ->where('questions.id', $oldQuestionId)
                    ->first();

                if ($selectedQuestion) {
                    $selectedQuestionOption = $this->formatQuestionSearchResult($selectedQuestion);
                }
            }

            $eligibleStudents = User::query()
                ->where('client_system_id', $exam->client_system_id)
                ->where('role', 'student')
                ->where('active', true)
                ->orderBy('name')
                ->limit(100)
                ->get(['id', 'name', 'cpf']);

            $sessionsStats = [
                'total' => $exam->sessions()->count(),
                'active' => $exam->sessions()->where('status', 'in_progress')->count(),
                'graded' => $exam->sessions()->where('status', 'graded')->count(),
                'passed' => $exam->sessions()->where('passed', true)->count(),
                'failed' => $exam->sessions()->where('passed', false)->whereIn('status', ['graded'])->count(),
            ];

            $nextBlockOrder = ((int) $exam->blocks->max('order')) + 1;
            $defaultBlockId = $exam->blocks->first()?->id;

            $html = view('provas.show', compact('exam', 'sessionsStats', 'eligibleStudents', 'nextBlockOrder', 'defaultBlockId', 'selectedQuestionOption'))->render();

            return response($html);
        } catch (\Throwable $e) {
            report($e);
            \Log::error('Erro ao abrir prova ID '.$exam->id.': '.$e->getMessage().' | Arquivo: '.$e->getFile().':'.$e->getLine());

            return redirect()->route('provas.index')
                ->with('error', 'Não foi possível abrir a prova. Erro: '.$e->getMessage());
        }
    }

    public function searchQuestions(Request $request, Exam $exam): \Illuminate\Http\JsonResponse
    {
        $this->authorizeExamAccess($exam);

        /** @var User $user */
        $user = Auth::user();
        $term = trim((string) $request->query('term', ''));

        if ($term === '') {
            return response()->json([
                'results' => [],
            ]);
        }

        $attachedQuestionIds = $exam->examQuestions()->pluck('question_id');

        $questions = $this->buildAvailableQuestionSearchQuery($exam, $user, $attachedQuestionIds, $term)
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $questions
                ->map(fn (Question $question) => $this->formatQuestionSearchResult($question))
                ->values()
                ->all(),
        ]);
    }

    public function edit(Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        /** @var User $user */
        $user = Auth::user();

        $systems = $user->isSuperAdmin()
            ? ClientSystem::where('active', true)->orderBy('name')->get()
            : ClientSystem::whereKey($user->client_system_id)->get();

        $disciplines = Discipline::safeAll($user->isSuperAdmin() ? null : $user->client_system_id, false);

        return view('provas.form', compact('systems', 'disciplines', 'exam'));
    }

    public function update(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        /** @var User $user */
        $user = Auth::user();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'instructions_html' => 'nullable|string',
            'client_system_id' => 'required|exists:client_systems,id',
            'discipline_id' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && $value !== '__new__' && ! \App\Models\Discipline::where('id', $value)->exists()) {
                    $fail('A disciplina selecionada é inválida.');
                }
            }],
            'new_discipline_name' => 'nullable|string|max:255',
            'duration_minutes' => 'required|integer|min:5|max:600',
            'passing_score' => 'required|numeric|min:0|max:9999.99',
            'max_violations' => 'required|integer|min:0|max:20',
            'moodle_course_id' => 'nullable|integer|min:0',
            'moodle_activity_id' => 'nullable|integer|min:0',
            'webcam_enabled' => 'nullable|boolean',
            'face_recognition_enabled' => 'nullable|boolean',
            'shuffle_questions' => 'nullable|boolean',
            'shuffle_choices' => 'nullable|boolean',
            'snapshot_interval_seconds' => 'required|integer|min:60|max:600',
            'branding_watermark_text' => 'nullable|string|max:80',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:draft,published,active,closed,archived',
        ]);

        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        if (in_array($data['discipline_id'] ?? null, ['__new__', 'new', ''], true)) {
            $data['discipline_id'] = null;
        }
        $data['discipline_id'] = $this->resolveDisciplineId($data);

        $data['webcam_enabled'] = $request->boolean('webcam_enabled');
        $data['face_recognition_enabled'] = $request->boolean('face_recognition_enabled');
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['shuffle_choices'] = $request->boolean('shuffle_choices');
        $data['settings'] = $this->buildExamSettings($request, $exam);

        unset($data['new_discipline_name']);
        $exam->update($data);

        return redirect()->route('provas.show', $exam)->with('success', 'Prova atualizada com sucesso!');
    }

    public function destroy(Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        // Protege provas com sessões avaliadas (auditoria) — ignora sessões de demonstração
        $gradedCount = $exam->sessions()
            ->whereIn('status', ['graded', 'submitted'])
            ->where('is_simulation', false)
            ->count();

        if ($gradedCount > 0) {
            return back()->with('error', "Esta prova possui {$gradedCount} sessão(ões) avaliada(s) e não pode ser excluída por questões de auditoria. Arquive-a em vez de excluir.");
        }

        $oldValues = [
            'exam_id' => $exam->id,
            'title' => $exam->title,
            'status' => $exam->status,
            'sessions_count' => $exam->sessions()->count(),
        ];

        $exam->delete();

        $this->auditLogService->log(
            action: 'exam.deleted',
            auditable: $exam,
            oldValues: $oldValues,
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
        );

        return redirect()->route('provas.index')->with('success', 'Prova removida.');
    }

    public function publish(Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        if (! $exam->questions()->exists()) {
            return back()->withErrors([
                'publish' => 'Adicione pelo menos uma questão antes de publicar a prova.',
            ]);
        }

        $oldStatus = $exam->status;

        $version = DB::transaction(function () use ($exam) {
            $version = $this->createExamVersion($exam);

            $exam->update(['status' => 'active']);

            return $version;
        });

        $this->auditLogService->log(
            action: 'exam.published',
            auditable: $exam,
            oldValues: ['status' => $oldStatus],
            newValues: [
                'status' => 'active',
                'version_number' => $version->version_number,
                'exam_version_id' => $version->id,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
        );

        return back()->with('success', "Prova publicada e disponível para aplicação! Versão {$version->version_number} gerada.");
    }

    public function archive(Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        $exam->update(['status' => 'archived']);

        return back()->with('success', 'Prova arquivada.');
    }

    public function storeBlock(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);
        $lockResponse = $this->ensureQuestionEditingAllowed($exam);
        if ($lockResponse) {
            return $lockResponse;
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:120',
            'base_text' => 'nullable|string',
            'order' => 'required|integer|min:1|max:999',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'blockStore')->withInput();
        }

        $validated = $validator->validated();

        $block = $exam->blocks()->create([
            'title' => trim((string) ($validated['title'] ?? '')) ?: 'Bloco '.$validated['order'],
            'base_text' => $this->sanitizeRichText($validated['base_text'] ?? null),
            'order' => (int) $validated['order'],
        ]);

        $this->normalizeBlockOrder($exam);

        $this->auditLogService->log(
            action: 'exam.block.created',
            auditable: $exam,
            newValues: [
                'block_id' => $block->id,
                'title' => $block->title,
                'order' => $block->order,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return back()->with('success', 'Bloco criado com sucesso.');
    }

    public function updateBlock(Request $request, Exam $exam, ExamBlock $block)
    {
        $this->authorizeExamAccess($exam);
        $lockResponse = $this->ensureQuestionEditingAllowed($exam);
        if ($lockResponse) {
            return $lockResponse;
        }

        if ($block->exam_id !== $exam->id) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:120',
            'base_text' => 'nullable|string',
            'order' => 'required|integer|min:1|max:999',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'blockUpdate')->withInput();
        }

        $validated = $validator->validated();

        $oldValues = [
            'title' => $block->title,
            'order' => $block->order,
        ];

        $block->update([
            'title' => trim((string) ($validated['title'] ?? '')) ?: 'Bloco '.$validated['order'],
            'base_text' => $this->sanitizeRichText($validated['base_text'] ?? null),
            'order' => (int) $validated['order'],
        ]);

        $this->normalizeBlockOrder($exam);

        $this->auditLogService->log(
            action: 'exam.block.updated',
            auditable: $exam,
            oldValues: $oldValues,
            newValues: [
                'block_id' => $block->id,
                'title' => $block->title,
                'order' => $block->order,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return back()->with('success', 'Bloco atualizado com sucesso.');
    }

    public function destroyBlock(Exam $exam, ExamBlock $block)
    {
        $this->authorizeExamAccess($exam);
        $lockResponse = $this->ensureQuestionEditingAllowed($exam);
        if ($lockResponse) {
            return $lockResponse;
        }

        if ($block->exam_id !== $exam->id) {
            abort(404);
        }

        if ($block->examQuestions()->exists()) {
            return back()->with('error', 'Remova ou mova as questões deste bloco antes de excluí-lo.');
        }

        $oldValues = [
            'block_id' => $block->id,
            'title' => $block->title,
            'order' => $block->order,
        ];

        $block->delete();
        $this->normalizeBlockOrder($exam);

        $this->auditLogService->log(
            action: 'exam.block.deleted',
            auditable: $exam,
            oldValues: $oldValues,
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
        );

        return back()->with('success', 'Bloco removido com sucesso.');
    }

    public function attachQuestion(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);
        $lockResponse = $this->ensureQuestionEditingAllowed($exam);
        if ($lockResponse) {
            return $lockResponse;
        }

        $validator = Validator::make($request->all(), [
            'exam_block_id' => 'required|integer',
            'question_id' => 'required|integer',
            'order' => 'required|integer|min:1|max:999',
            'weight' => 'required|numeric|min:0.01|max:999.99',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'attachQuestion')->withInput();
        }

        $validated = $validator->validated();
        /** @var User $user */
        $user = Auth::user();

        // First try same-system question
        $question = Question::query()
            ->where('id', $validated['question_id'])
            ->visibleForUser($user)
            ->first();

        if (! $question) {
            return back()->withErrors([
                'question_id' => 'Questão não encontrada ou sem permissão de acesso.',
            ], 'attachQuestion')->withInput();
        }

        $block = $exam->blocks()
            ->whereKey($validated['exam_block_id'])
            ->first();

        if (! $block) {
            return back()->withErrors([
                'exam_block_id' => 'Selecione um bloco válido para vincular a questão.',
            ], 'attachQuestion')->withInput();
        }

        // If question belongs to a different system, copy it to the exam's system
        if ($question->client_system_id !== $exam->client_system_id) {
            $question = $this->copyQuestionToSystem($question, $exam->client_system_id, $user->id);
        }

        $alreadyAttached = $exam->questions()->where('questions.id', $question->id)->exists();

        DB::transaction(function () use ($exam, $question, $validated, $alreadyAttached, $request) {
            if ($alreadyAttached) {
                $exam->questions()->updateExistingPivot($question->id, [
                    'exam_block_id' => $validated['exam_block_id'],
                    'order' => $validated['order'],
                    'weight' => $validated['weight'],
                ]);
                $this->normalizeQuestionOrder($exam);

                $this->auditLogService->log(
                    action: 'exam.question.updated',
                    auditable: $exam,
                    newValues: [
                        'exam_block_id' => (int) $validated['exam_block_id'],
                        'question_id' => $question->id,
                        'order' => (int) $validated['order'],
                        'weight' => (float) $validated['weight'],
                    ],
                    userId: Auth::id(),
                    clientSystemId: $exam->client_system_id,
                    ipAddress: $request->ip(),
                    userAgent: $request->userAgent(),
                );

                return;
            }

            $exam->questions()->attach($question->id, [
                'exam_block_id' => $validated['exam_block_id'],
                'order' => $validated['order'],
                'weight' => $validated['weight'],
            ]);

            $this->normalizeQuestionOrder($exam);

            $this->auditLogService->log(
                action: 'exam.question.added',
                auditable: $exam,
                newValues: [
                    'exam_block_id' => (int) $validated['exam_block_id'],
                    'question_id' => $question->id,
                    'order' => (int) $validated['order'],
                    'weight' => (float) $validated['weight'],
                ],
                userId: Auth::id(),
                clientSystemId: $exam->client_system_id,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );
        });

        return back()->with('success', $alreadyAttached ? 'Questão atualizada na prova.' : 'Questão vinculada à prova com sucesso!');
    }

    public function detachQuestion(Exam $exam, Question $question)
    {
        $this->authorizeExamAccess($exam);
        $lockResponse = $this->ensureQuestionEditingAllowed($exam);
        if ($lockResponse) {
            return $lockResponse;
        }

        $pivot = $exam->questions()->where('questions.id', $question->id)->first()?->pivot;

        if (! $pivot) {
            abort(404);
        }

        $exam->questions()->detach($question->id);
        $this->normalizeQuestionOrder($exam);

        $this->auditLogService->log(
            action: 'exam.question.removed',
            auditable: $exam,
            oldValues: [
                'exam_block_id' => $pivot?->exam_block_id,
                'question_id' => $question->id,
                'order' => $pivot?->order,
                'weight' => $pivot?->weight,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
        );

        return back()->with('success', 'Questão removida da prova.');
    }

    public function generateLink(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        $validator = Validator::make($request->all(), [
            'student_reference' => 'required|string|max:50',
            'attempt_number' => 'required|integer|min:1|max:99',
            'is_simulation' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'generateLink')->withInput();
        }

        if (! $exam->isPublished()) {
            return back()->withErrors([
                'student_reference' => 'A prova precisa estar publicada ou ativa para gerar links.',
            ], 'generateLink')->withInput();
        }

        $validated = $validator->validated();
        $student = $this->resolveStudentReference($exam, $validated['student_reference']);

        if (! $student) {
            return back()->withErrors([
                'student_reference' => 'Nenhum estudante encontrado para o CPF ou ID informado.',
            ], 'generateLink')->withInput();
        }

        $activeSession = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($activeSession && $activeSession->attempt_number !== (int) $validated['attempt_number']) {
            return back()->withErrors([
                'attempt_number' => "Já existe uma sessão ativa para este estudante na tentativa {$activeSession->attempt_number}.",
            ], 'generateLink')->withInput();
        }

        $version = $exam->latestVersion ?: $this->createExamVersion($exam);

        if (! $activeSession) {
            $attemptAlreadyUsed = ExamSession::query()
                ->where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->where('attempt_number', $validated['attempt_number'])
                ->exists();

            if ($attemptAlreadyUsed) {
                return back()->withErrors([
                    'attempt_number' => 'Essa tentativa já foi utilizada para este estudante nesta prova.',
                ], 'generateLink')->withInput();
            }

            $activeSession = ExamSession::create([
                'exam_id' => $exam->id,
                'exam_version_id' => $version?->id,
                'student_id' => $student->id,
                'attempt_number' => $validated['attempt_number'],
                'status' => 'pending',
                'expires_at' => now()->addMinutes($exam->duration_minutes + 10),
                'is_simulation' => $request->boolean('is_simulation'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'face_status' => $exam->face_recognition_enabled ? 'pending' : 'not_required',
            ]);
        } elseif (! $activeSession->exam_version_id && $version) {
            $activeSession->update(['exam_version_id' => $version->id]);
        }

        try {
            $deepLink = $this->tokenService->getDeepLink($activeSession->fresh('exam'));
        } catch (\Throwable $e) {
            return back()->withErrors([
                'student_reference' => $e->getMessage(),
            ], 'generateLink')->withInput();
        }

        return redirect()
            ->route('provas.show', $exam)
            ->with('success', 'Link JWT gerado com sucesso.')
            ->with('generated_link', $deepLink)
            ->with('generated_student_name', $student->name)
            ->with('generated_student_reference', $student->cpf)
            ->with('generated_attempt_number', $activeSession->attempt_number)
            ->with('generated_session_id', $activeSession->id)
            ->with('generated_is_simulation', $activeSession->is_simulation);
    }

    public function generateBulkLinks(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);

        if (! $exam->isPublished()) {
            return response()->json([
                'success' => false,
                'error' => 'A prova precisa estar publicada ou ativa para gerar links.',
            ], 422);
        }

        $extraCourseIds = array_filter(
            array_map('intval', (array) $request->input('extra_course_ids', [])),
            fn ($id) => $id > 0
        );

        try {
            $moodleUsers = $this->enrollmentService->fetchEnrolledStudents($exam, $extraCourseIds);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        if (empty($moodleUsers)) {
            return response()->json([
                'success' => false,
                'error' => 'Nenhum estudante com papel de aluno encontrado no curso Moodle.',
            ], 422);
        }

        $results = $this->enrollmentService->generateBulkLinks(
            $exam,
            $moodleUsers,
            $request->ip(),
            $request->userAgent(),
        );

        $this->auditLogService->log(
            action: 'exam.bulk_links_generated',
            auditable: $exam,
            newValues: [
                'moodle_students_found' => count($moodleUsers),
                'links_generated' => collect($results)->whereIn('status', ['link_generated', 'created'])->count(),
                'already_active' => collect($results)->where('status', 'already_active')->count(),
                'errors' => collect($results)->where('status', 'error')->count(),
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response()->json([
            'success' => true,
            'data' => $results,
            'summary' => [
                'total' => count($results),
                'generated' => collect($results)->whereIn('status', ['link_generated', 'created'])->count(),
                'already_active' => collect($results)->where('status', 'already_active')->count(),
                'auto_created' => collect($results)->where('status', 'created')->count(),
                'errors' => collect($results)->where('status', 'error')->count(),
            ],
        ]);
    }

    private function authorizeExamAccess(Exam $exam): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isSuperAdmin() && $user->client_system_id !== $exam->client_system_id) {
            abort(404);
        }
    }

    private function buildExamSettings(Request $request, ?Exam $exam = null): ?array
    {
        $settings = $exam?->settings ?? [];
        $moodle = $settings['moodle'] ?? [];

        $courseId = $request->filled('moodle_course_id') ? (int) $request->input('moodle_course_id') : null;
        $activityId = $request->filled('moodle_activity_id') ? (int) $request->input('moodle_activity_id') : null;

        if ($courseId !== null) {
            $moodle['course_id'] = $courseId;
        } else {
            unset($moodle['course_id']);
        }

        if ($activityId !== null) {
            $moodle['activity_id'] = $activityId;
        } else {
            unset($moodle['activity_id']);
        }

        if (empty($moodle)) {
            unset($settings['moodle']);
        } else {
            $settings['moodle'] = $moodle;
        }

        $settings['snapshot_interval_seconds'] = (int) $request->input(
            'snapshot_interval_seconds',
            data_get($settings, 'snapshot_interval_seconds', 60)
        );

        $brandingWatermarkText = trim((string) $request->input('branding_watermark_text', data_get($settings, 'branding_watermark_text', '')));

        if ($brandingWatermarkText !== '') {
            $settings['branding_watermark_text'] = $brandingWatermarkText;
        } else {
            unset($settings['branding_watermark_text']);
        }

        $instructionsHtml = $this->sanitizeRichText($request->input('instructions_html'));

        if ($instructionsHtml) {
            $settings['instructions_html'] = $instructionsHtml;
        } else {
            unset($settings['instructions_html']);
        }

        return empty($settings) ? null : $settings;
    }

    private function ensureQuestionEditingAllowed(Exam $exam): ?\Illuminate\Http\RedirectResponse
    {
        if ($exam->status !== 'archived') {
            return null;
        }

        return back()->with('error', 'Não é possível alterar blocos ou questões de uma prova arquivada.');
    }

    private function resolveStudentReference(Exam $exam, string $reference): ?User
    {
        $normalized = preg_replace('/\D+/', '', $reference);

        return User::query()
            ->where('client_system_id', $exam->client_system_id)
            ->where('role', 'student')
            ->where(function ($query) use ($reference, $normalized) {
                $query->where('id', $reference);

                if ($normalized !== '') {
                    $query->orWhere('cpf', $normalized);
                }
            })
            ->first();
    }

    private function createExamVersion(Exam $exam): ExamVersion
    {
        $exam->loadMissing(['questions.choices', 'clientSystem', 'creator', 'blocks.examQuestions.question.choices']);

        $nextVersion = ((int) $exam->versions()->max('version_number')) + 1;

        return $exam->versions()->create([
            'version_number' => $nextVersion,
            'snapshot' => $this->buildExamSnapshot($exam),
            'questions_count' => $exam->questions->count(),
            'published_by' => Auth::id(),
            'published_at' => now(),
        ]);
    }

    private function buildExamSnapshot(Exam $exam): array
    {
        $blocks = $exam->blocks
            ->sortBy('order')
            ->values();

        return [
            'exam' => [
                'id' => $exam->id,
                'client_system_id' => $exam->client_system_id,
                'title' => $exam->title,
                'description' => $exam->description,
                'status' => $exam->status,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'max_violations' => $exam->max_violations,
                'webcam_enabled' => $exam->webcam_enabled,
                'shuffle_questions' => $exam->shuffle_questions,
                'shuffle_choices' => $exam->shuffle_choices,
                'instructions_html' => data_get($exam->settings ?? [], 'instructions_html'),
                'snapshot_interval_seconds' => (int) data_get($exam->settings ?? [], 'snapshot_interval_seconds', 60),
                'starts_at' => $exam->starts_at?->toIso8601String(),
                'ends_at' => $exam->ends_at?->toIso8601String(),
            ],
            'blocks' => $blocks
                ->values()
                ->map(function (ExamBlock $block) {
                    return [
                        'id' => $block->id,
                        'title' => $block->title,
                        'base_text' => $block->base_text,
                        'order' => $block->order,
                        'questions' => $block->examQuestions
                            ->sortBy('order')
                            ->values()
                            ->map(fn (ExamQuestion $examQuestion) => $this->serializeSnapshotQuestion($examQuestion))
                            ->filter()
                            ->values()
                            ->all(),
                    ];
                })
                ->all(),
            'questions' => $blocks
                ->flatMap(fn (ExamBlock $block) => $block->examQuestions
                    ->sortBy('order')
                    ->values()
                    ->map(fn (ExamQuestion $examQuestion) => $this->serializeSnapshotQuestion($examQuestion)))
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function serializeSnapshotQuestion(ExamQuestion $examQuestion): ?array
    {
        $question = $examQuestion->question;

        if (! $question) {
            return null;
        }

        return [
            'id' => $question->id,
            'type' => $question->type,
            'content' => $question->content,
            'explanation' => $question->explanation,
            'difficulty' => $question->difficulty,
            'tags' => $question->tags,
            'owner_department' => $question->owner_department,
            'visibility_scope' => $question->visibility_scope,
            'pivot' => [
                'exam_block_id' => $examQuestion->exam_block_id,
                'order' => $examQuestion->order,
                'weight' => (float) $examQuestion->weight,
            ],
            'choices' => $question->choices->map(fn ($choice) => [
                'id' => $choice->id,
                'text' => $choice->content,
                'is_correct' => $choice->is_correct,
                'order' => $choice->order,
            ])->values()->all(),
        ];
    }

    private function copyQuestionToSystem(Question $original, int $targetSystemId, int $userId): Question
    {
        $copy = Question::create([
            'client_system_id' => $targetSystemId,
            'created_by' => $userId,
            'type' => $original->type,
            'content' => $original->content,
            'explanation' => $original->explanation,
            'difficulty' => $original->difficulty,
            'tags' => $original->tags,
            'active' => $original->active,
            'owner_department' => $original->owner_department,
            'visibility_scope' => 'system',
        ]);

        foreach ($original->choices as $choice) {
            $copy->choices()->create([
                'content' => $choice->content,
                'is_correct' => $choice->is_correct,
                'order' => $choice->order,
            ]);
        }

        return $copy;
    }

    private function resolveDisciplineId(array $data): ?int
    {
        $newName = trim($data['new_discipline_name'] ?? '');

        if ($newName !== '') {
            $discipline = Discipline::findOrCreateByName($newName, (int) $data['client_system_id']);

            return $discipline->id;
        }

        return $data['discipline_id'] ?: null;
    }

    private function normalizeQuestionOrder(Exam $exam): void
    {
        $groupedExamQuestions = $exam->examQuestions()
            ->select(['id', 'exam_block_id', 'order'])
            ->orderBy('exam_block_id')
            ->orderBy('order')
            ->get()
            ->groupBy(fn (ExamQuestion $examQuestion) => $examQuestion->exam_block_id ?: 'none');

        foreach ($groupedExamQuestions as $examQuestions) {
            foreach ($examQuestions->values() as $index => $examQuestion) {
                $exam->examQuestions()
                    ->whereKey($examQuestion->id)
                    ->update(['order' => $index + 1]);
            }
        }
    }

    private function normalizeBlockOrder(Exam $exam): void
    {
        $blockIds = $exam->blocks()
            ->orderBy('order')
            ->pluck('id')
            ->values();

        foreach ($blockIds as $index => $blockId) {
            $exam->blocks()
                ->whereKey($blockId)
                ->update(['order' => $index + 1]);
        }
    }

    private function buildAvailableQuestionSearchQuery(Exam $exam, User $user, $excludedQuestionIds, string $term = ''): \Illuminate\Database\Eloquent\Builder
    {
        $normalizedTermId = preg_replace('/\D+/', '', $term);

        return Question::query()
            ->where(function ($query) use ($exam, $user) {
                $query->where('client_system_id', $exam->client_system_id);

                if ($user->isSuperAdmin()) {
                    $query->orWhereNotNull('id');
                } else {
                    $query->orWhere('visibility_scope', 'global');
                }
            })
            ->visibleForUser($user)
            ->when(collect($excludedQuestionIds)->isNotEmpty(), fn ($query) => $query->whereNotIn('questions.id', collect($excludedQuestionIds)->values()->all()))
            ->when($term !== '', function ($query) use ($term, $normalizedTermId) {
                $query->where(function ($searchQuery) use ($term, $normalizedTermId) {
                    if ($normalizedTermId !== '') {
                        $searchQuery->orWhere('questions.id', (int) $normalizedTermId);
                    }

                    $searchQuery->orWhere('questions.content', 'like', '%'.$term.'%')
                        ->orWhere('questions.explanation', 'like', '%'.$term.'%')
                        ->orWhere('questions.tags', 'like', '%'.$term.'%');
                });
            })
            ->withCount('choices')
            ->with('clientSystem:id,name')
            ->orderByDesc('created_at');
    }

    private function formatQuestionSearchResult(Question $question): array
    {
        $snippet = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $question->content)) ?? ''), 180);

        return [
            'id' => $question->id,
            'label' => '#'.$question->id.' · '.$snippet,
            'snippet' => $snippet,
            'type_label' => match ($question->type) {
                'multiple_choice' => 'Múltipla escolha',
                'true_false' => 'Verdadeiro/Falso',
                'multiple_answer' => 'Múltiplas respostas',
                'essay' => 'Dissertativa',
                'ordering' => 'Ordenação',
                default => ucfirst((string) $question->type),
            },
            'choices_count' => (int) $question->choices_count,
        ];
    }

    private function sanitizeRichText(?string $html): ?string
    {
        $value = trim((string) $html);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value) ?? $value;
        $value = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $value) ?? $value;
        $value = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $value) ?? $value;
        $value = preg_replace("/on\w+\s*=\s*'[^']*'/i", '', $value) ?? $value;
        $value = preg_replace('/javascript:/i', '', $value) ?? $value;

        if (! class_exists(\DOMDocument::class)) {
            $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4><a><span><div>';

            return strip_tags($value, $allowed);
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML(
            '<?xml encoding="utf-8" ?><body>'.$value.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        if (! $loaded) {
            return strip_tags($value, '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h2><h3><h4><a><span><div>');
        }

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return null;
        }

        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'h4', 'a', 'span', 'div'];
        $this->sanitizeRichTextNode($body, $allowedTags);

        $htmlOutput = '';
        foreach ($body->childNodes as $childNode) {
            $htmlOutput .= $document->saveHTML($childNode);
        }

        $htmlOutput = trim((string) $htmlOutput);

        return $htmlOutput !== '' ? $htmlOutput : null;
    }

    private function sanitizeRichTextNode(\DOMNode $node, array $allowedTags): void
    {
        if (! $node->hasChildNodes()) {
            return;
        }

        foreach (collect(iterator_to_array($node->childNodes)) as $childNode) {
            if ($childNode instanceof \DOMElement) {
                if (! in_array($childNode->tagName, $allowedTags, true)) {
                    while ($childNode->firstChild) {
                        $node->insertBefore($childNode->firstChild, $childNode);
                    }
                    $node->removeChild($childNode);

                    continue;
                }

                $this->sanitizeRichTextAttributes($childNode);
                $this->sanitizeRichTextNode($childNode, $allowedTags);
            }
        }
    }

    private function sanitizeRichTextAttributes(\DOMElement $element): void
    {
        $allowedAttributes = match ($element->tagName) {
            'a' => ['href', 'target', 'rel'],
            'p', 'div', 'span', 'blockquote', 'h2', 'h3', 'h4' => ['style'],
            default => [],
        };

        foreach (collect(iterator_to_array($element->attributes ?? [])) as $attribute) {
            $attributeName = $attribute->nodeName;

            if (! in_array($attributeName, $allowedAttributes, true)) {
                $element->removeAttribute($attributeName);

                continue;
            }

            if ($attributeName === 'href') {
                $href = trim((string) $attribute->nodeValue);

                if ($href === '' || preg_match('/^\s*javascript:/i', $href)) {
                    $element->removeAttribute('href');
                }
            }

            if ($attributeName === 'style') {
                $sanitizedStyle = $this->sanitizeRichTextStyle((string) $attribute->nodeValue);

                if ($sanitizedStyle === '') {
                    $element->removeAttribute('style');
                } else {
                    $element->setAttribute('style', $sanitizedStyle);
                }
            }
        }

        if ($element->tagName === 'a') {
            if ($element->hasAttribute('href')) {
                $element->setAttribute('target', '_blank');
                $element->setAttribute('rel', 'noopener noreferrer');
            } else {
                $element->removeAttribute('target');
                $element->removeAttribute('rel');
            }
        }
    }

    private function sanitizeRichTextStyle(string $style): string
    {
        $allowedRules = [];

        foreach (explode(';', $style) as $rule) {
            [$property, $value] = array_pad(explode(':', $rule, 2), 2, null);
            $property = strtolower(trim((string) $property));
            $value = trim((string) $value);

            if ($property === '' || $value === '') {
                continue;
            }

            if ($property === 'text-align' && in_array(strtolower($value), ['left', 'center', 'right', 'justify'], true)) {
                $allowedRules[] = 'text-align: '.strtolower($value);
            }
        }

        return implode('; ', array_unique($allowedRules));
    }
}
