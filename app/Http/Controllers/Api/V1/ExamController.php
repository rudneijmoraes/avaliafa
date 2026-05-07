<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $exams = Exam::where('client_system_id', $clientSystem->id)
            ->whereIn('status', ['published', 'active'])
            ->select(['id', 'title', 'description', 'status', 'duration_minutes', 'passing_score', 'starts_at', 'ends_at'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $exams,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $exam = Exam::where('id', $id)
            ->where('client_system_id', $clientSystem->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'description' => $exam->description,
                'status' => $exam->status,
                'duration_minutes' => $exam->duration_minutes,
                'passing_score' => $exam->passing_score,
                'max_violations' => $exam->max_violations,
                'webcam_enabled' => $exam->webcam_enabled,
                'shuffle_questions' => $exam->shuffle_questions,
                'shuffle_choices' => $exam->shuffle_choices,
                'questions_count' => $exam->questions()->count(),
                'starts_at' => $exam->starts_at,
                'ends_at' => $exam->ends_at,
            ],
        ]);
    }

    public function results(Request $request, int $id): JsonResponse
    {
        /** @var ClientSystem $clientSystem */
        $clientSystem = $request->get('_client_system');

        $exam = Exam::where('id', $id)
            ->where('client_system_id', $clientSystem->id)
            ->firstOrFail();

        $sessions = $exam->sessions()
            ->with('student:id,name,email,external_id')
            ->whereNotNull('final_score')
            ->select(['id', 'student_id', 'attempt_number', 'status', 'final_score', 'passed', 'submitted_at'])
            ->orderByDesc('submitted_at')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }
}
