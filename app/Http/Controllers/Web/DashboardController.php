<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\MoodleSyncLog;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStudent()) {
            return redirect()->route('simulados.minha-area');
        }

        $isSuperAdmin = $user->isSuperAdmin();

        $systemId = $isSuperAdmin ? null : $user->client_system_id;

        $examQuery = Exam::query();
        $sessionQuery = ExamSession::query();
        $studentQuery = User::where('role', 'student');
        $questionQuery = Question::query();

        if ($systemId) {
            $examQuery->where('client_system_id', $systemId);
            $studentQuery->where('client_system_id', $systemId);
            $sessionQuery->whereHas('exam', fn ($q) => $q->where('client_system_id', $systemId));
            $questionQuery->where('client_system_id', $systemId);
        }

        $stats = [
            'total_exams' => $examQuery->count(),
            'total_sessions' => $sessionQuery->count(),
            'simulation_attempts' => (clone $sessionQuery)->where('is_simulation', true)->count(),
            'total_students' => $studentQuery->count(),
            'total_questions' => $questionQuery->count(),
            'sessions_today' => (clone $sessionQuery)->whereDate('created_at', today())->count(),
            'active_sessions' => (clone $sessionQuery)->where('status', 'in_progress')->count(),
            'graded_sessions' => (clone $sessionQuery)->where('status', 'graded')->count(),
            'pass_rate' => $this->calcPassRate($sessionQuery),
        ];

        $recentSessions = ExamSession::with(['exam', 'student'])
            ->when($systemId, fn ($q) => $q->whereHas('exam', fn ($eq) => $eq->where('client_system_id', $systemId)))
            ->latest()
            ->limit(10)
            ->get();

        $institutionStats = null;
        $systemRanking = collect();

        if ($isSuperAdmin) {
            $institutionStats = [
                'total_systems' => ClientSystem::count(),
                'active_systems' => ClientSystem::where('active', true)->count(),
                'total_certificates' => Certificate::count(),
                'valid_certificates' => Certificate::whereNull('revoked_at')->count(),
                'webhook_failures_24h' => WebhookLog::whereIn('status', ['failed', 'retrying'])
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
                'moodle_failures_24h' => MoodleSyncLog::whereIn('status', ['failed', 'retrying'])
                    ->where('created_at', '>=', now()->subDay())
                    ->count(),
                'average_risk' => round((float) (ExamSession::where('status', '!=', 'pending')->avg('risk_score') ?? 0), 1),
            ];

            $systemRanking = ClientSystem::query()
                ->select(['client_systems.id', 'client_systems.name', 'client_systems.active'])
                ->addSelect([
                    'sessions_count' => ExamSession::query()
                        ->selectRaw('COUNT(*)')
                        ->join('exams', 'exams.id', '=', 'exam_sessions.exam_id')
                        ->whereColumn('exams.client_system_id', 'client_systems.id'),
                    'graded_count' => ExamSession::query()
                        ->selectRaw('COUNT(*)')
                        ->join('exams', 'exams.id', '=', 'exam_sessions.exam_id')
                        ->where('exam_sessions.status', 'graded')
                        ->whereColumn('exams.client_system_id', 'client_systems.id'),
                ])
                ->withCount([
                    'users as students_count' => fn ($q) => $q->where('role', 'student'),
                    'exams',
                ])
                ->orderByDesc('sessions_count')
                ->limit(6)
                ->get()
                ->map(function ($system) {
                    $graded = (int) ($system->graded_count ?? 0);
                    $passRate = 0.0;

                    if ($graded > 0) {
                        $passed = ExamSession::query()
                            ->join('exams', 'exams.id', '=', 'exam_sessions.exam_id')
                            ->where('exam_sessions.status', 'graded')
                            ->where('exam_sessions.passed', true)
                            ->where('exams.client_system_id', $system->id)
                            ->count();
                        $passRate = round(($passed / $graded) * 100, 1);
                    }

                    return [
                        'id' => $system->id,
                        'name' => $system->name,
                        'active' => (bool) $system->active,
                        'students_count' => (int) $system->students_count,
                        'exams_count' => (int) $system->exams_count,
                        'sessions_count' => (int) ($system->sessions_count ?? 0),
                        'pass_rate' => $passRate,
                    ];
                });
        }

        return view('dashboard', compact('stats', 'recentSessions', 'user', 'isSuperAdmin', 'institutionStats', 'systemRanking'));
    }

    private function calcPassRate($sessionQuery): float
    {
        $graded = (clone $sessionQuery)->where('status', 'graded')->count();
        if ($graded === 0) {
            return 0;
        }
        $passed = (clone $sessionQuery)->where('status', 'graded')->where('passed', true)->count();

        return round(($passed / $graded) * 100, 1);
    }
}
