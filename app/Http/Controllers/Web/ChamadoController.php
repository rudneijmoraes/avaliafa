<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chamados\StoreChamadoRequest;
use App\Models\Chamado;
use App\Models\ClientSystem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChamadoController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->currentUser();
        $canManage = $user->canManageChamados();

        $query = Chamado::query()
            ->with(['creator:id,name,email', 'clientSystem:id,name'])
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('client_system_id', $user->client_system_id))
            ->when(! $canManage, fn ($builder) => $builder->where('created_by', $user->id));

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($builder) => $builder
                ->where('subject', 'like', $term)
                ->orWhere('message', 'like', $term)
                ->orWhere('protocol', 'like', $term));
        }

        $chamados = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $systems = $user->isSuperAdmin()
            ? ClientSystem::query()->where('active', true)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('chamados.index', [
            'chamados' => $chamados,
            'systems' => $systems,
            'canManage' => $canManage,
        ]);
    }

    public function store(StoreChamadoRequest $request)
    {
        $user = $this->currentUser();
        $data = $request->validated();

        $clientSystemId = $user->isSuperAdmin()
            ? (int) ($data['client_system_id'] ?? 0)
            : (int) $user->client_system_id;

        abort_if($clientSystemId <= 0, 422, 'Sistema do chamado não identificado.');

        $chamado = Chamado::query()->create([
            'client_system_id' => $clientSystemId,
            'created_by' => $user->id,
            'protocol' => $this->generateProtocol(),
            'subject' => trim((string) $data['subject']),
            'message' => trim((string) $data['message']),
            'status' => 'open',
            'priority' => (string) $data['priority'],
        ]);

        return redirect()
            ->route('chamados.index')
            ->with('success', "Chamado {$chamado->protocol} aberto com sucesso.");
    }

    public function updateStatus(Request $request, Chamado $chamado)
    {
        $user = $this->currentUser();
        abort_unless($user->canManageChamados(), 403);
        $this->authorizeChamadoSystem($chamado, $user);

        $data = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $chamado->update([
            'status' => $data['status'],
        ]);

        return back()->with('success', "Status do chamado {$chamado->protocol} atualizado.");
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if (! $user->canReceiveChamadoNotifications()) {
            return response()->json([
                'items' => [],
                'latest_id' => 0,
            ]);
        }

        $afterId = max(0, (int) $request->integer('after_id', 0));

        $baseQuery = Chamado::query()
            ->when(! $user->isSuperAdmin(), fn ($builder) => $builder->where('client_system_id', $user->client_system_id))
            ->where('created_by', '!=', $user->id);

        $latestId = (int) ($baseQuery->max('id') ?? 0);

        $items = $baseQuery
            ->with(['creator:id,name', 'clientSystem:id,name'])
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (Chamado $chamado) => [
                'id' => $chamado->id,
                'protocol' => $chamado->protocol,
                'subject' => $chamado->subject,
                'message' => $chamado->message,
                'priority' => $chamado->priority,
                'status' => $chamado->status,
                'creator_name' => $chamado->creator?->name ?? 'Usuário',
                'client_system_name' => $chamado->clientSystem?->name,
                'created_at' => optional($chamado->created_at)->toIso8601String(),
                'url' => route('chamados.index'),
            ])
            ->values();

        return response()->json([
            'items' => $items,
            'latest_id' => max($latestId, $afterId),
        ]);
    }

    private function currentUser(): User
    {
        return Auth::user();
    }

    private function authorizeChamadoSystem(Chamado $chamado, User $user): void
    {
        if (! $user->isSuperAdmin() && (int) $chamado->client_system_id !== (int) $user->client_system_id) {
            abort(404);
        }
    }

    private function generateProtocol(): string
    {
        do {
            $protocol = 'CH-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Chamado::query()->where('protocol', $protocol)->exists());

        return $protocol;
    }
}
