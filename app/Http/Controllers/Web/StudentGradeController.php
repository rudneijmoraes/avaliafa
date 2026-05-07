<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Models\Simulado;
use App\Models\SimuladoParticipant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StudentGradeController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $student = auth()->user();

        if (! $student || ! $student->isStudent()) {
            return redirect()->route('dashboard');
        }

        $sessions = ExamSession::query()
            ->with('exam.simulado')
            ->where('student_id', $student->id)
            ->where('status', 'graded')
            ->whereNotNull('final_score')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $participantIds = SimuladoParticipant::query()
            ->where('client_system_id', $student->client_system_id)
            ->where(function ($query) use ($student) {
                $query->where('user_id', $student->id);

                $cpf = preg_replace('/\D/', '', (string) $student->cpf);
                if ($cpf !== '') {
                    $query->orWhere('cpf', $cpf);
                }
            })
            ->pluck('id');

        $completedSimuladoIds = collect();
        $pendingRegistrationsBySimuladoId = collect();

        if ($participantIds->isNotEmpty()) {
            $registrations = \App\Models\SimuladoRegistration::query()
                ->whereIn('participant_id', $participantIds)
                ->orderByDesc('id')
                ->get();

            $completedSimuladoIds = $registrations
                ->filter(fn ($registration) => ! is_null($registration->completed_at))
                ->pluck('simulado_id')
                ->unique()
                ->values();

            $pendingRegistrationsBySimuladoId = $registrations
                ->filter(fn ($registration) => is_null($registration->completed_at))
                ->unique('simulado_id')
                ->keyBy('simulado_id');
        }

        $recentSimulados = Simulado::query()
            ->with('exam')
            ->where('client_system_id', $student->client_system_id)
            ->where('status', 'active')
            ->whereHas('exam.questions')
            ->when($completedSimuladoIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $completedSimuladoIds))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(function (Simulado $simulado) use ($pendingRegistrationsBySimuladoId) {
                $registration = $pendingRegistrationsBySimuladoId->get($simulado->id);

                return [
                    'id' => $simulado->id,
                    'slug' => $simulado->slug,
                    'name' => $simulado->name,
                    'description' => $simulado->description,
                    'questions_count' => $simulado->exam?->questions()->count() ?? 0,
                    'has_pending_registration' => ! is_null($registration),
                ];
            });

        return view('student.grades.index', [
            'student' => $student,
            'sessions' => $sessions,
            'recentSimulados' => $recentSimulados,
        ]);
    }

    public function show(int $sessionId): View|RedirectResponse
    {
        $student = auth()->user();

        if (! $student || ! $student->isStudent()) {
            return redirect()->route('dashboard');
        }

        $session = ExamSession::query()
            ->with([
                'exam',
                'answers.question.choices',
                'answers.choice',
            ])
            ->where('student_id', $student->id)
            ->where('status', 'graded')
            ->whereNotNull('final_score')
            ->findOrFail($sessionId);

        return view('student.grades.show', [
            'student' => $student,
            'session' => $session,
            'exam' => $session->exam,
            'answers' => $session->answers->sortBy('question_id')->values(),
        ]);
    }
}
