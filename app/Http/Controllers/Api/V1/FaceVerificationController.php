<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Services\FaceVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaceVerificationController extends Controller
{
    public function __construct(
        private FaceVerificationService $service
    ) {}

    /**
     * POST /api/v1/exam/sessions/{session}/face-verifications
     *
     * Recebe resultado de verificação do SecureExamEngine.
     * O processamento de IA ocorre no navegador — aqui apenas persistimos.
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $session = ExamSession::findOrFail($id);

        abort_if(
            $session->student_id !== auth()->id(),
            403,
            'Acesso negado.'
        );

        abort_if(
            !$session->requiresFaceVerification(),
            422,
            'Verificação facial não habilitada para esta sessão.'
        );

        $data = $request->validate([
            'type'       => 'required|in:enrollment,check',
            'result'     => 'required|in:approved,failed,no_face,multiple_faces,skipped',
            'confidence' => 'nullable|numeric|min:0|max:1',
            'metadata'   => 'nullable|array',
            'snapshot'   => 'nullable|string|max:500000',
        ]);

        $verification = $this->service->record($session, $data);

        return response()->json([
            'id'         => $verification->id,
            'result'     => $verification->result,
            'confidence' => $verification->confidencePercentage(),
            'action'     => $this->service->resolveAction($session->fresh()),
        ]);
    }

    /**
     * GET /exam/sessions/{id}/face-verifications
     *
     * Histórico de verificações — professor/admin.
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $session = ExamSession::findOrFail($id);

        abort_unless(
            $request->user()?->hasAnyRole(['super_admin', 'admin', 'professor']),
            403
        );

        $verifications = $session->faceVerifications()
            ->select('id', 'type', 'result', 'confidence', 'created_at')
            ->latest()
            ->get()
            ->map(fn ($v) => [
                'id'         => $v->id,
                'type'       => $v->type,
                'result'     => $v->result,
                'confidence' => $v->confidencePercentage(),
                'created_at' => $v->created_at->format('H:i:s'),
            ]);

        return response()->json([
            'face_status'        => $session->face_status,
            'face_checks_failed' => $session->face_checks_failed,
            'verifications'      => $verifications,
        ]);
    }
}
