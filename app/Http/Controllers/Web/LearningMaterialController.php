<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ClientSystem;
use App\Models\LearningMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $query = LearningMaterial::with('systems', 'creator')
            ->orderByDesc('created_at');

        if ($request->filled('sistema')) {
            $query->whereHas('systems', fn ($q) => $q->where('client_systems.id', $request->sistema));
        }

        if ($request->filled('status') && in_array($request->status, ['0', '1'])) {
            $query->where('active', (bool) $request->status);
        }

        $materials = $query->paginate(20)->withQueryString();
        $systems = ClientSystem::orderBy('name')->get();
        $filters = $request->only('sistema', 'status');

        return view('conteudo.materiais.index', compact('materials', 'systems', 'filters'));
    }

    public function create(): View
    {
        $systems = ClientSystem::orderBy('name')->get();
        $material = null;

        return view('conteudo.materiais.form', compact('material', 'systems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'file_url'    => ['required', 'url'],
            'systems'     => ['required', 'array'],
            'systems.*'   => ['exists:client_systems,id'],
            'active'      => ['boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $validated['created_by'] = auth()->id();
        unset($validated['systems'], $validated['cover_image']);

        $material = LearningMaterial::create($validated);

        if ($request->hasFile('cover_image')) {
            $ext = $request->file('cover_image')->getClientOriginalExtension();
            $path = "material-covers/{$material->id}/cover.{$ext}";
            Storage::disk('public')->putFileAs(
                "material-covers/{$material->id}",
                $request->file('cover_image'),
                "cover.{$ext}"
            );
            $material->cover_image = $path;
            $material->save();
        }

        $material->systems()->sync($request->systems);

        return redirect()->route('conteudo.materiais.index')
            ->with('success', 'Material criado com sucesso.');
    }

    public function edit(LearningMaterial $material): View
    {
        $material->load('systems');
        $systems = ClientSystem::orderBy('name')->get();

        return view('conteudo.materiais.form', compact('material', 'systems'));
    }

    public function update(Request $request, LearningMaterial $material): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'file_url'    => ['required', 'url'],
            'systems'     => ['required', 'array'],
            'systems.*'   => ['exists:client_systems,id'],
            'active'      => ['boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        unset($validated['systems'], $validated['cover_image']);

        if ($request->hasFile('cover_image')) {
            if ($material->cover_image) {
                Storage::disk('public')->delete($material->cover_image);
            }

            $ext = $request->file('cover_image')->getClientOriginalExtension();
            $path = "material-covers/{$material->id}/cover.{$ext}";
            Storage::disk('public')->putFileAs(
                "material-covers/{$material->id}",
                $request->file('cover_image'),
                "cover.{$ext}"
            );
            $validated['cover_image'] = $path;
        }

        $material->update($validated);
        $material->systems()->sync($request->systems);

        return redirect()->route('conteudo.materiais.index')
            ->with('success', 'Material atualizado.');
    }

    public function destroy(LearningMaterial $material): RedirectResponse
    {
        if ($material->cover_image) {
            Storage::disk('public')->delete($material->cover_image);
        }

        $material->delete();

        return redirect()->route('conteudo.materiais.index')
            ->with('success', 'Material removido.');
    }
}
