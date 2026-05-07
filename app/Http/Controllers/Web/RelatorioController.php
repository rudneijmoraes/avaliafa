<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\SimuladoParticipant;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelatorioController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    private function currentUser(): User
    {
        /** @var User */
        return Auth::user();
    }

    public function index(Request $request)
    {
        $user = $this->currentUser();

        $query = Exam::query()
            ->with('clientSystem')
            ->withCount(['sessions' => fn ($q) => $q->where('is_simulation', false)])
            ->withCount([
                'sessions as graded_sessions_count' => fn ($q) => $q->where('status', 'graded')->where('is_simulation', false),
                'sessions as passed_sessions_count' => fn ($q) => $q->where('passed', true)->where('is_simulation', false),
            ]);

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
            $query->where('client_system_id', (int) $request->sistema);
        }

        $exams = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $systems = $user->isSuperAdmin() ? ClientSystem::active()->get() : collect();

        $examBase = Exam::query();
        $sessionBase = ExamSession::query()->whereHas('exam')->where('is_simulation', false);

        if (! $user->isSuperAdmin()) {
            $examBase->where('client_system_id', $user->client_system_id);
            $sessionBase->whereHas('exam', fn ($q) => $q->where('client_system_id', $user->client_system_id));
        }

        $stats = [
            'total_exams' => (clone $examBase)->count(),
            'total_sessions' => (clone $sessionBase)->count(),
            'graded_sessions' => (clone $sessionBase)->where('status', 'graded')->count(),
            'average_score' => round((float) ((clone $sessionBase)->whereNotNull('final_score')->avg('final_score') ?? 0), 2),
            'pass_rate' => $this->calcPassRate(clone $sessionBase),
        ];

        return view('relatorios.index', compact('exams', 'systems', 'stats'));
    }

    public function showGeneralSimulations(Request $request)
    {
        $user = $this->currentUser();
        $sessionsQuery = $this->generalSimulationSessionsQuery($request, $user);

        $sessions = $sessionsQuery
            ->paginate(20)
            ->withQueryString();

        $phonesByUserId = SimuladoParticipant::query()
            ->whereIn('user_id', $sessions->pluck('student_id')->filter()->unique()->all())
            ->pluck('phone', 'user_id');

        $statsQuery = $this->baseSimulationSessionsScope($user);
        if ($request->filled('sistema') && $user->isSuperAdmin()) {
            $statsQuery->whereHas('exam', fn ($examQuery) => $examQuery->where('client_system_id', (int) $request->sistema));
        }
        $this->applySessionFilters($statsQuery, $request);

        $stats = $this->buildSessionStats($statsQuery);
        $systems = $user->isSuperAdmin() ? ClientSystem::active()->get() : collect();

        return view('relatorios.simulados-geral', compact('sessions', 'stats', 'phonesByUserId', 'systems'));
    }

    public function exportGeneralSimulationsCsv(Request $request): StreamedResponse
    {
        $sessionsQuery = $this->generalSimulationSessionsQuery($request, $this->currentUser());

        $allColumns = [
            'sessao_id'  => 'sessao_id',
            'simulado'   => 'simulado',
            'sistema'    => 'sistema',
            'estudante'  => 'estudante',
            'cpf'        => 'cpf',
            'email'      => 'email',
            'telefone'   => 'telefone',
            'tentativa'  => 'tentativa',
            'status'     => 'status',
            'nota_final' => 'nota_final',
            'aprovado'   => 'aprovado',
            'violacoes'  => 'violacoes',
            'risco'      => 'risco',
            'inicio'     => 'inicio',
            'envio'      => 'envio',
        ];

        $selectedKeys = $request->has('colunas')
            ? array_intersect($request->input('colunas', []), array_keys($allColumns))
            : array_keys($allColumns);

        if (empty($selectedKeys)) {
            $selectedKeys = array_keys($allColumns);
        }

        $filename = 'relatorio-geral-simulados-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sessionsQuery, $selectedKeys) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, $selectedKeys, ';');

            $sessionsQuery->chunk(200, function ($sessions) use ($output, $selectedKeys) {
                $needsPhone = in_array('telefone', $selectedKeys, true);
                $phonesByUserId = $needsPhone
                    ? SimuladoParticipant::query()
                        ->whereIn('user_id', $sessions->pluck('student_id')->filter()->unique()->all())
                        ->pluck('phone', 'user_id')
                    : collect();

                foreach ($sessions as $session) {
                    $student = $session->student;
                    $allValues = [
                        'sessao_id'  => $session->id,
                        'simulado'   => $session->exam?->simulado?->name ?? ($session->exam?->title ?? ''),
                        'sistema'    => $session->exam?->clientSystem?->name ?? '',
                        'estudante'  => $student?->name ?? '',
                        'cpf'        => $student?->cpf ?? '',
                        'email'      => $student?->email ?? '',
                        'telefone'   => $phonesByUserId->get($session->student_id, ''),
                        'tentativa'  => $session->attempt_number,
                        'status'     => $session->status,
                        'nota_final' => $session->final_score,
                        'aprovado'   => $session->passed ? 'sim' : 'nao',
                        'violacoes'  => $session->violation_count,
                        'risco'      => $session->risk_score,
                        'inicio'     => $session->started_at?->format('Y-m-d H:i:s'),
                        'envio'      => $session->submitted_at?->format('Y-m-d H:i:s'),
                    ];

                    $row = array_map(fn ($key) => $allValues[$key] ?? '', $selectedKeys);
                    fputcsv($output, $row, ';');
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function showExam(Request $request, Exam $exam)
    {
        $this->authorizeExamAccess($exam);
        $includeSimulations = $this->resolveIncludeSimulations($request, $exam);

        $sessionsQuery = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->with('student:id,name,first_name,last_name,cpf,email')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        if (! $includeSimulations) {
            $sessionsQuery->where('is_simulation', false);
        }

        $this->applySessionFilters($sessionsQuery, $request);

        $sessions = $sessionsQuery
            ->paginate(20)
            ->withQueryString();

        $phonesByUserId = SimuladoParticipant::query()
            ->whereIn('user_id', $sessions->pluck('student_id')->filter()->unique()->all())
            ->pluck('phone', 'user_id');

        $statsQuery = ExamSession::query()->where('exam_id', $exam->id);

        if (! $includeSimulations) {
            $statsQuery->where('is_simulation', false);
        }

        $stats = $this->buildSessionStats($statsQuery);
        $stats['pass_rate'] = $this->calcPassRateByExam($exam, $includeSimulations);

        return view('relatorios.prova', compact('exam', 'sessions', 'stats', 'includeSimulations', 'phonesByUserId'));
    }

    public function exportExamCsv(Request $request, Exam $exam): StreamedResponse
    {
        $this->authorizeExamAccess($exam);
        $includeSimulations = $this->resolveIncludeSimulations($request, $exam);

        $sessionsQuery = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->with('student:id,name,first_name,last_name,cpf,email')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        if (! $includeSimulations) {
            $sessionsQuery->where('is_simulation', false);
        }

        $this->applySessionFilters($sessionsQuery, $request);

        $allColumns = [
            'sessao_id'  => 'sessao_id',
            'estudante'  => 'estudante',
            'cpf'        => 'cpf',
            'email'      => 'email',
            'telefone'   => 'telefone',
            'tentativa'  => 'tentativa',
            'status'     => 'status',
            'nota_final' => 'nota_final',
            'aprovado'   => 'aprovado',
            'violacoes'  => 'violacoes',
            'risco'      => 'risco',
            'simulado'   => 'simulado',
            'inicio'     => 'inicio',
            'envio'      => 'envio',
        ];

        $selectedKeys = $request->has('colunas')
            ? array_intersect($request->input('colunas', []), array_keys($allColumns))
            : array_keys($allColumns);

        if (empty($selectedKeys)) {
            $selectedKeys = array_keys($allColumns);
        }

        $filename = 'relatorio-prova-'.$exam->id.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sessionsQuery, $selectedKeys) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, $selectedKeys, ';');

            $sessionsQuery->chunk(200, function ($sessions) use ($output, $selectedKeys) {
                $needsPhone = in_array('telefone', $selectedKeys);
                $phonesByUserId = $needsPhone
                    ? SimuladoParticipant::query()
                        ->whereIn('user_id', $sessions->pluck('student_id')->filter()->unique()->all())
                        ->pluck('phone', 'user_id')
                    : collect();

                foreach ($sessions as $session) {
                    $student = $session->student;
                    $allValues = [
                        'sessao_id'  => $session->id,
                        'estudante'  => $student?->name ?? '',
                        'cpf'        => $student?->cpf ?? '',
                        'email'      => $student?->email ?? '',
                        'telefone'   => $phonesByUserId->get($session->student_id, ''),
                        'tentativa'  => $session->attempt_number,
                        'status'     => $session->status,
                        'nota_final' => $session->final_score,
                        'aprovado'   => $session->passed ? 'sim' : 'nao',
                        'violacoes'  => $session->violation_count,
                        'risco'      => $session->risk_score,
                        'simulado'   => $session->is_simulation ? 'sim' : 'nao',
                        'inicio'     => $session->started_at?->format('Y-m-d H:i:s'),
                        'envio'      => $session->submitted_at?->format('Y-m-d H:i:s'),
                    ];

                    $row = array_map(fn ($key) => $allValues[$key] ?? '', $selectedKeys);
                    fputcsv($output, $row, ';');
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function updateGrade(Request $request, Exam $exam, ExamSession $session): \Illuminate\Http\RedirectResponse
    {
        $this->authorizeExamAccess($exam);

        abort_if($session->exam_id !== $exam->id, 404);

        $validated = $request->validate([
            'final_score' => 'required|numeric|min:0|max:9999.99',
        ]);

        if (! in_array($session->status, ['submitted', 'graded'])) {
            return back()->with('error', 'Somente sessões enviadas ou corrigidas podem ter nota ajustada manualmente.');
        }

        $oldValues = [
            'raw_score' => $session->raw_score,
            'final_score' => $session->final_score,
            'passed' => $session->passed,
            'status' => $session->status,
        ];

        $finalScore = round((float) $validated['final_score'], 2);
        $passed = $finalScore >= $exam->passing_score;

        $session->update([
            'raw_score' => $finalScore,
            'final_score' => $finalScore,
            'passed' => $passed,
            'status' => 'graded',
            'grade_published' => true,
        ]);

        $this->auditLogService->log(
            action: 'grade.updated',
            auditable: $session,
            oldValues: $oldValues,
            newValues: [
                'raw_score' => $session->raw_score,
                'final_score' => $session->final_score,
                'passed' => $session->passed,
                'status' => $session->status,
            ],
            userId: Auth::id(),
            clientSystemId: $exam->client_system_id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return back()->with('success', 'Nota ajustada com sucesso.');
    }

    private function applySessionFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('resultado')) {
            if ($request->resultado === 'aprovado') {
                $query->where('passed', true);
            }

            if ($request->resultado === 'reprovado') {
                $query->where('passed', false);
            }
        }

        if ($request->filled('busca')) {
            $search = trim((string) $request->busca);
            $normalizedCpf = preg_replace('/\D/', '', $search);
            $tokens = collect(preg_split('/\s+/', $search) ?: [])
                ->filter(fn ($token) => is_string($token) && $token !== '')
                ->values()
                ->all();

            $query->where(function ($baseQuery) use ($search, $normalizedCpf, $tokens) {
                $baseQuery->whereHas('student', function ($q) use ($search, $normalizedCpf, $tokens) {
                    $q->where(function ($studentQuery) use ($search, $normalizedCpf, $tokens) {
                        $studentQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');

                        if ($normalizedCpf !== '') {
                            $studentQuery->orWhere('cpf', 'like', '%'.$normalizedCpf.'%');
                        }

                        if ($tokens !== []) {
                            $studentQuery->orWhere(function ($tokenQuery) use ($tokens) {
                                foreach ($tokens as $token) {
                                    $tokenQuery->where(function ($partQuery) use ($token) {
                                        $partQuery->where('name', 'like', '%'.$token.'%')
                                            ->orWhere('first_name', 'like', '%'.$token.'%')
                                            ->orWhere('last_name', 'like', '%'.$token.'%');
                                    });
                                }
                            });
                        }
                    });
                });

                $phoneDigits = preg_replace('/\D/', '', $search);
                $phoneSearch = $phoneDigits !== '' ? $phoneDigits : $search;

                $baseQuery->orWhereExists(function ($phoneQuery) use ($phoneSearch) {
                    $phoneQuery->selectRaw('1')
                        ->from('simulado_participants')
                        ->whereColumn('simulado_participants.user_id', 'exam_sessions.student_id')
                        ->where('simulado_participants.phone', 'like', '%'.$phoneSearch.'%');
                });
            });
        }
    }

    private function authorizeExamAccess(Exam $exam): void
    {
        $user = $this->currentUser();
        if ($user->isSuperAdmin()) {
            return;
        }

        abort_if($exam->client_system_id !== $user->client_system_id, 403);
    }

    private function baseSimulationSessionsScope(User $user)
    {
        $query = ExamSession::query()
            ->where('is_simulation', true)
            ->whereHas('exam.simulado')
            ->with([
                'student:id,name,first_name,last_name,cpf,email',
                'exam:id,title,client_system_id',
                'exam.clientSystem:id,name',
                'exam.simulado:id,exam_id,name',
            ]);

        if (! $user->isSuperAdmin()) {
            $query->whereHas('exam', fn ($examQuery) => $examQuery->where('client_system_id', $user->client_system_id));
        }

        return $query;
    }

    private function generalSimulationSessionsQuery(Request $request, User $user)
    {
        $query = $this->baseSimulationSessionsScope($user)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        if ($request->filled('sistema') && $user->isSuperAdmin()) {
            $query->whereHas('exam', fn ($examQuery) => $examQuery->where('client_system_id', (int) $request->sistema));
        }

        $this->applySessionFilters($query, $request);

        return $query;
    }

    private function buildSessionStats($statsQuery): array
    {
        return [
            'total_sessions' => (clone $statsQuery)->count(),
            'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
            'submitted' => (clone $statsQuery)->where('status', 'submitted')->count(),
            'graded' => (clone $statsQuery)->where('status', 'graded')->count(),
            'average_score' => round((float) ((clone $statsQuery)->whereNotNull('final_score')->avg('final_score') ?? 0), 2),
            'pass_rate' => $this->calcPassRate(clone $statsQuery),
            'avg_risk' => round((float) ((clone $statsQuery)->avg('risk_score') ?? 0), 1),
            'total_violations' => (int) ((clone $statsQuery)->sum('violation_count') ?? 0),
        ];
    }

    private function resolveIncludeSimulations(Request $request, Exam $exam): bool
    {
        if ($request->has('incluir_simulados')) {
            return $request->boolean('incluir_simulados');
        }

        return $this->examHasOnlySimulationSessions($exam);
    }

    private function examHasOnlySimulationSessions(Exam $exam): bool
    {
        $baseQuery = ExamSession::query()->where('exam_id', $exam->id);

        $hasOfficialSessions = (clone $baseQuery)
            ->where('is_simulation', false)
            ->exists();

        if ($hasOfficialSessions) {
            return false;
        }

        return (clone $baseQuery)
            ->where('is_simulation', true)
            ->exists();
    }

    private function calcPassRate($query): float
    {
        $total = (clone $query)->where('status', 'graded')->count();
        if ($total === 0) {
            return 0.0;
        }

        $passed = (clone $query)->where('status', 'graded')->where('passed', true)->count();

        return round(($passed / $total) * 100, 2);
    }

    private function calcPassRateByExam(Exam $exam, bool $includeSimulations = false): float
    {
        $query = ExamSession::query()->where('exam_id', $exam->id)->where('status', 'graded');

        if (! $includeSimulations) {
            $query->where('is_simulation', false);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            return 0.0;
        }

        $passed = (clone $query)->where('passed', true)->count();

        return round(($passed / $total) * 100, 2);
    }
}
