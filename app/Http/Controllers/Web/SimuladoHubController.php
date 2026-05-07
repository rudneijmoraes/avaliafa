<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\SimuladoHub;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SimuladoHubController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = SimuladoHub::query()
            ->with(['clientSystem', 'simulados'])
            ->withCount('simulados');

        if (! $user->isSuperAdmin()) {
            $query->where('client_system_id', $user->client_system_id);
        }

        if ($request->filled('q')) {
            $term = '%'.$request->string('q').'%';
            $query->where(fn ($hubQuery) => $hubQuery
                ->where('name', 'like', $term)
                ->orWhere('slug', 'like', $term));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $hubs = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('simulados.hubs.index', compact('hubs'));
    }

    public function create()
    {
        $user = Auth::user();
        $systems = $user->isSuperAdmin()
            ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
            : ClientSystem::query()->whereKey($user->client_system_id)->get();

        $hub = null;

        return view('simulados.hubs.form', compact('hub', 'systems'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $data = $this->validateHub($request, $user);

        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        $hub = SimuladoHub::query()->create($data);

        return redirect()
            ->route('simulados.hubs.show', $hub)
            ->with('success', 'Hub de simulados criado com sucesso.');
    }

    public function show(SimuladoHub $hub)
    {
        $this->authorizeHub($hub);

        $hub->load([
            'clientSystem',
            'simulados' => fn ($query) => $query
                ->with(['exam', 'registrations'])
                ->orderBy('hub_order')
                ->orderBy('id'),
        ]);

        return view('simulados.hubs.show', compact('hub'));
    }

    public function edit(SimuladoHub $hub)
    {
        $this->authorizeHub($hub);

        $user = Auth::user();
        $systems = $user->isSuperAdmin()
            ? ClientSystem::query()->where('active', true)->orderBy('name')->get()
            : ClientSystem::query()->whereKey($user->client_system_id)->get();

        return view('simulados.hubs.form', compact('hub', 'systems'));
    }

    public function update(Request $request, SimuladoHub $hub)
    {
        $this->authorizeHub($hub);

        $user = Auth::user();
        $data = $this->validateHub($request, $user, $hub);

        if (! $user->isSuperAdmin()) {
            $data['client_system_id'] = $user->client_system_id;
        }

        $hub->update($data);

        return redirect()
            ->route('simulados.hubs.show', $hub)
            ->with('success', 'Hub de simulados atualizado com sucesso.');
    }

    private function validateHub(Request $request, $user, ?SimuladoHub $hub = null): array
    {
        $data = $request->validate([
            'client_system_id' => 'required|exists:client_systems,id',
            'name' => 'required|string|max:180',
            'slug' => 'required|string|max:160',
            'landing_title' => 'nullable|string|max:180',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,inactive,archived',
            'sort_order' => 'nullable|integer|min:1|max:999',
        ]);

        $data['slug'] = Str::slug((string) $data['slug']);
        $data['landing_title'] = trim((string) ($data['landing_title'] ?? '')) ?: $data['name'];
        $data['sort_order'] = (int) ($data['sort_order'] ?? 1);

        $slugExists = SimuladoHub::query()
            ->where('client_system_id', $user->isSuperAdmin() ? $data['client_system_id'] : $user->client_system_id)
            ->where('slug', $data['slug'])
            ->when($hub, fn ($query) => $query->whereKeyNot($hub->id))
            ->exists();

        if ($slugExists) {
            throw ValidationException::withMessages([
                'slug' => 'Já existe um hub com esse slug neste sistema.',
            ]);
        }

        return $data;
    }

    private function authorizeHub(SimuladoHub $hub): void
    {
        $user = Auth::user();

        if (! $user->isSuperAdmin() && (int) $hub->client_system_id !== (int) $user->client_system_id) {
            abort(404);
        }
    }
}
