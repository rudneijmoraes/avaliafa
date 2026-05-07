<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\User;
use App\Services\Api\SessionTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    public function __construct(private readonly SessionTokenService $tokenService) {}

    public function store(Request $request, int $examId): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'required|integer',
            'is_simulation' => 'boolean',
        ]);

        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $exam = Exam::where('id', $examId)
            ->where('client_system_id', $clientSystem->id)
            ->firstOrFail();

        if (! $exam->isPublished()) {
            return response()->json([
                'success' => false,
                'message' => 'Exam is not available',
            ], 422);
        }

        $student = User::where('id', $validated['student_id'])
            ->where('client_system_id', $clientSystem->id)
            ->where('role', 'student')
            ->firstOrFail();

        // Check for existing active session
        $activeSession = ExamSession::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->first();

        if ($activeSession) {
            return response()->json([
                'success' => false,
                'message' => 'Student already has an active session for this exam',
                'errors' => ['session_id' => $activeSession->id],
            ], 422);
        }

        $attemptNumber = ExamSession::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->count() + 1;

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'attempt_number' => $attemptNumber,
            'status' => 'pending',
            'expires_at' => now()->addMinutes($exam->duration_minutes + 10),
            'is_simulation' => $validated['is_simulation'] ?? false,
            'face_status' => $exam->face_recognition_enabled ? 'pending' : 'not_required',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'attempt_number' => $session->attempt_number,
                'status' => $session->status,
                'expires_at' => $session->expires_at,
            ],
        ], 201);
    }

    public function token(Request $request, int $sessionId): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $session = ExamSession::whereHas('exam', fn ($q) => $q->where('client_system_id', $clientSystem->id))
            ->where('id', $sessionId)
            ->firstOrFail();

        if (! in_array($session->status, ['pending', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'message' => 'Session is no longer active',
            ], 422);
        }

        $deepLink = $this->tokenService->getDeepLink($session);

        return response()->json([
            'success' => true,
            'data' => [
                'deep_link' => $deepLink,
                'expires_at' => $session->expires_at,
            ],
        ]);
    }

    public function result(Request $request, int $sessionId): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $session = ExamSession::whereHas('exam', fn ($q) => $q->where('client_system_id', $clientSystem->id))
            ->with('student:id,name,email,external_id')
            ->where('id', $sessionId)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'session_id' => $session->id,
                'exam_id' => $session->exam_id,
                'student' => $session->student,
                'attempt_number' => $session->attempt_number,
                'status' => $session->status,
                'final_score' => $session->final_score,
                'passed' => $session->passed,
                'violation_count' => $session->violation_count,
                'started_at' => $session->started_at,
                'submitted_at' => $session->submitted_at,
                'grade_published' => $session->grade_published,
                'moodle_synced' => $session->moodle_synced,
            ],
        ]);
    }
}
