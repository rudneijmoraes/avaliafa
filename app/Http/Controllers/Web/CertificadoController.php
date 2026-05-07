<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificadoController extends Controller
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

        $query = Certificate::query()
            ->with(['student:id,name,first_name,last_name,cpf,email', 'exam:id,client_system_id,title'])
            ->when(! $user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereHas('exam', fn ($eq) => $eq->where('client_system_id', $user->client_system_id));
            });

        if ($request->filled('busca')) {
            $search = $request->busca;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', '%'.$search.'%')
                            ->orWhere('cpf', 'like', '%'.preg_replace('/\D/', '', $search).'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('exam', fn ($eq) => $eq->where('title', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'valid') {
                $query->whereNull('revoked_at');
            }

            if ($request->status === 'revoked') {
                $query->whereNotNull('revoked_at');
            }
        }

        $certificates = $query
            ->orderByDesc('issued_at')
            ->paginate(20)
            ->withQueryString();

        $statsBase = Certificate::query()
            ->when(! $user->isSuperAdmin(), function ($q) use ($user) {
                $q->whereHas('exam', fn ($eq) => $eq->where('client_system_id', $user->client_system_id));
            });

        $stats = [
            'total' => (clone $statsBase)->count(),
            'valid' => (clone $statsBase)->whereNull('revoked_at')->count(),
            'revoked' => (clone $statsBase)->whereNotNull('revoked_at')->count(),
            'with_pdf' => (clone $statsBase)->whereNotNull('pdf_path')->count(),
        ];

        return view('certificados.index', compact('certificates', 'stats'));
    }

    public function verify(string $code)
    {
        $certificate = Certificate::withTrashed()
            ->with(['student:id,name,first_name,last_name,cpf', 'exam:id,title'])
            ->where('code', $code)
            ->first();

        return view('certificados.verificar', [
            'certificate' => $certificate,
            'isValid' => $certificate?->isValid() ?? false,
        ]);
    }

    public function toggleStatus(Certificate $certificado)
    {
        $this->authorizeCertificateAccess($certificado);

        $isRevoking = is_null($certificado->revoked_at);
        $oldRevokedAt = $certificado->revoked_at?->toIso8601String();

        $certificado->update([
            'revoked_at' => $isRevoking ? now() : null,
        ]);

        $certificado->loadMissing('exam:id,client_system_id');

        $this->auditLogService->log(
            action: $isRevoking ? 'certificate.revoked' : 'certificate.reissued',
            auditable: $certificado,
            oldValues: ['revoked_at' => $oldRevokedAt],
            newValues: ['revoked_at' => $certificado->revoked_at?->toIso8601String()],
            userId: Auth::id(),
            clientSystemId: $certificado->exam?->client_system_id,
        );

        return redirect()
            ->route('certificados.index')
            ->with('success', $isRevoking ? 'Certificado revogado com sucesso.' : 'Certificado reativado com sucesso.');
    }

    public function download(Certificate $certificado): StreamedResponse
    {
        $this->authorizeCertificateAccess($certificado);

        abort_if(empty($certificado->pdf_path) || ! Storage::disk('public')->exists($certificado->pdf_path), 404);

        $filename = 'certificado-'.$certificado->code.'.pdf';

        return Storage::disk('public')->download($certificado->pdf_path, $filename);
    }

    private function authorizeCertificateAccess(Certificate $certificate): void
    {
        $user = $this->currentUser();

        if ($user->isSuperAdmin()) {
            return;
        }

        $certificate->loadMissing('exam:id,client_system_id');

        abort_if($certificate->exam?->client_system_id !== $user->client_system_id, 403);
    }
}
