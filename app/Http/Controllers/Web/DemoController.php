<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Simulado;
use App\Services\Api\SessionTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    public function __construct(
        private readonly SessionTokenService $tokenService,
    ) {}

    public function startExamDemo(Exam $exam): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->isProfessor(), 403);

        if (! $user->isSuperAdmin() && $exam->client_system_id !== $user->client_system_id) {
            abort(403);
        }

        $existing = ExamSession::query()
            ->where('exam_id', $exam->id)
            ->where('student_id', $user->id)
            ->where('launch_source', 'demo')
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        $session = $existing ?? ExamSession::query()->create([
            'exam_id'       => $exam->id,
            'student_id'    => $user->id,
            'is_simulation' => true,
            'launch_source' => 'demo',
            'status'        => 'pending',
        ]);

        $session->load('exam');

        return redirect($this->tokenService->getDeepLink($session));
    }

    public function startSimuladoDemo(Simulado $simulado): RedirectResponse
    {
        $user = Auth::user();

        abort_unless($user->isProfessor(), 403);

        if (! $user->isSuperAdmin() && $simulado->client_system_id !== $user->client_system_id) {
            abort(403);
        }

        abort_unless($simulado->exam_id, 404);

        $existing = ExamSession::query()
            ->where('exam_id', $simulado->exam_id)
            ->where('student_id', $user->id)
            ->where('launch_source', 'demo')
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        $session = $existing ?? ExamSession::query()->create([
            'exam_id'       => $simulado->exam_id,
            'student_id'    => $user->id,
            'is_simulation' => true,
            'launch_source' => 'demo',
            'status'        => 'pending',
        ]);

        $session->load('exam');

        return redirect($this->tokenService->getDeepLink($session));
    }
}
