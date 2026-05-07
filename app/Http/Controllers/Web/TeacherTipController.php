<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\Simulado;
use App\Models\TeacherTip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherTipController extends Controller
{
    public function index(Request $request): View
    {
        $query = TeacherTip::with('systems', 'creator')
            ->orderBy('order')
            ->orderByDesc('created_at');

        if ($request->filled('sistema')) {
            $query->whereHas('systems', fn ($q) => $q->where('client_systems.id', $request->sistema));
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('active', (bool) $request->status);
        }

        $tips = $query->paginate(20)->withQueryString();
        $systems = ClientSystem::orderBy('name')->get();
        $filters = $request->only('sistema', 'status');

        return view('conteudo.dicas.index', compact('tips', 'systems', 'filters'));
    }

    public function create(): View
    {
        $systems = ClientSystem::orderBy('name')->get();
        $simulados = Simulado::where('status', 'active')->orderBy('name')->get();
        $tip = null;

        return view('conteudo.dicas.form', compact('systems', 'simulados', 'tip'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'video_url'   => ['required', 'url'],
            'systems'     => ['required', 'array'],
            'systems.*'   => ['exists:client_systems,id'],
            'simulado_id' => ['nullable', 'exists:simulados,id'],
            'active'      => ['boolean'],
            'order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['video_type'] = TeacherTip::detectVideoType($request->video_url);
        $validated['created_by'] = auth()->id();

        $tip = TeacherTip::create($validated);
        $tip->systems()->sync($request->systems);

        return redirect()->route('conteudo.dicas.index')
            ->with('success', 'Dica criada com sucesso.');
    }

    public function edit(TeacherTip $tip): View
    {
        $tip->load('systems', 'simulado');
        $systems = ClientSystem::orderBy('name')->get();
        $simulados = Simulado::where('status', 'active')->orderBy('name')->get();

        return view('conteudo.dicas.form', compact('tip', 'systems', 'simulados'));
    }

    public function update(Request $request, TeacherTip $tip): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'video_url'   => ['required', 'url'],
            'systems'     => ['required', 'array'],
            'systems.*'   => ['exists:client_systems,id'],
            'simulado_id' => ['nullable', 'exists:simulados,id'],
            'active'      => ['boolean'],
            'order'       => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['video_type'] = TeacherTip::detectVideoType($request->video_url);

        $tip->update($validated);
        $tip->systems()->sync($request->systems);

        return redirect()->route('conteudo.dicas.index')
            ->with('success', 'Dica atualizada.');
    }

    public function destroy(TeacherTip $tip): RedirectResponse
    {
        $tip->delete();

        return redirect()->route('conteudo.dicas.index')
            ->with('success', 'Dica removida.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'items'          => ['required', 'array'],
            'items.*.id'     => ['exists:teacher_tips,id'],
            'items.*.order'  => ['integer'],
        ]);

        foreach ($request->items as $item) {
            TeacherTip::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['ok' => true]);
    }
}
