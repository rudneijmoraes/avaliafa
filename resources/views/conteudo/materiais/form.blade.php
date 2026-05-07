@extends('layouts.app')

@php
    $isEdit = $material !== null;
@endphp

@section('title', ($isEdit ? 'Editar Material' : 'Novo Material') . ' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Material' : 'Novo Material')

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .ql-container { font-family: 'Inter', Arial, sans-serif; font-size: 0.875rem; }
    .ql-toolbar { border-radius: 8px 8px 0 0 !important; border-color: var(--surface-border) !important; background: var(--surface-bg); }
    .ql-container { border-radius: 0 0 8px 8px !important; border-color: var(--surface-border) !important; background: var(--surface-input); min-height: 120px; }
    .ql-editor { min-height: 120px; color: var(--text-primary); }
    [data-theme="dark"] .ql-toolbar { background: #1e293b; }
    [data-theme="dark"] .ql-container { background: #1e293b; }
    [data-theme="dark"] .ql-stroke { stroke: var(--text-secondary) !important; }
    [data-theme="dark"] .ql-fill { fill: var(--text-secondary) !important; }
    [data-theme="dark"] .ql-picker-label { color: var(--text-secondary) !important; }
    [data-theme="dark"] .ql-editor.ql-blank::before { color: var(--text-muted); }
    .cover-preview {
        width: 100%;
        border-radius: 8px;
        border: 1px solid var(--surface-border);
        object-fit: cover;
        max-height: 160px;
        display: block;
    }
    .toggle-switch {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }
    .toggle-switch input[type="checkbox"] { display: none; }
    .toggle-track {
        width: 44px; height: 24px;
        border-radius: 9999px;
        background: var(--surface-border);
        transition: background 0.2s;
        position: relative;
        flex-shrink: 0;
    }
    .toggle-track::after {
        content: '';
        position: absolute;
        top: 3px; left: 3px;
        width: 18px; height: 18px;
        border-radius: 50%;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        transition: left 0.2s;
    }
    input[type="checkbox"]:checked + .toggle-track {
        background: var(--color-primary-600);
    }
    input[type="checkbox"]:checked + .toggle-track::after {
        left: 23px;
    }
    .checkbox-card {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border: 1.5px solid var(--surface-border);
        border-radius: 8px;
        cursor: pointer;
        transition: border-color 0.15s, background 0.15s;
    }
    .checkbox-card:hover { border-color: var(--color-primary-400); background: var(--color-primary-50); }
    .checkbox-card input:checked ~ .checkbox-card-label { color: var(--color-primary-700); font-weight: 600; }
    .checkbox-card input[type="checkbox"] {
        width: 16px; height: 16px; accent-color: var(--color-primary-600); flex-shrink: 0;
    }
    .checkbox-card-label { font-size: 0.875rem; color: var(--text-primary); }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Editar Material' : 'Novo Material Complementar' }}</h1>
            <p class="page-subtitle">{{ $isEdit ? 'Atualize as informações do material complementar.' : 'Adicione um novo material de apoio para os alunos.' }}</p>
        </div>
        <a href="{{ route('conteudo.materiais.index') }}" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Voltar
        </a>
    </div>
</div>

{{-- Erros de validação --}}
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <div style="font-weight:700;margin-bottom:4px">Revise o formulário antes de salvar.</div>
        <ul style="margin:0;padding-left:16px;font-size:0.82rem;opacity:0.92">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<form
    method="POST"
    action="{{ $isEdit ? route('conteudo.materiais.update', $material) : route('conteudo.materiais.store') }}"
    enctype="multipart/form-data"
    x-data="materialForm()"
    @submit="syncQuill()"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start" class="form-layout">

        {{-- Coluna principal --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Informações principais --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informações do Material</span>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label" for="title">
                            Título <span style="color:var(--color-danger)">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            class="input {{ $errors->has('title') ? 'input-error' : '' }}"
                            value="{{ old('title', $material?->title) }}"
                            maxlength="180"
                            required
                            placeholder="Ex: Guia de estudos para o ENADE"
                        >
                        @error('title')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Descrição (Quill) --}}
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Descrição</label>
                        <div id="quill-editor" style="background:var(--surface-input)">{{ old('description', $material?->description) }}</div>
                        <textarea name="description" id="description-hidden" style="display:none">{{ old('description', $material?->description) }}</textarea>
                        @error('description')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Link do arquivo --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Arquivo / Link</span>
                </div>
                <div class="card-body">

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="file_url">
                            Link do arquivo <span style="color:var(--color-danger)">*</span>
                        </label>
                        <input
                            type="url"
                            id="file_url"
                            name="file_url"
                            class="input {{ $errors->has('file_url') ? 'input-error' : '' }}"
                            value="{{ old('file_url', $material?->file_url) }}"
                            required
                            placeholder="Link do Google Drive"
                        >
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">
                            Cole o link compartilhável do Google Drive, OneDrive ou outro serviço de armazenamento.
                        </p>
                        @error('file_url')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

        </div>

        {{-- Coluna lateral --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Publicação --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Publicação</span>
                </div>
                <div class="card-body">

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Status</label>
                        <label class="toggle-switch">
                            <input
                                type="checkbox"
                                name="active"
                                value="1"
                                id="active"
                                {{ old('active', $isEdit ? ($material->active ? '1' : '') : '1') ? 'checked' : '' }}
                            >
                            <span class="toggle-track"></span>
                            <span style="font-size:0.875rem;color:var(--text-secondary)">
                                Material ativo (visível no portal)
                            </span>
                        </label>
                    </div>

                </div>
            </div>

            {{-- Capa --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Imagem de Capa</span>
                </div>
                <div class="card-body">

                    @if($isEdit && $material->cover_image)
                    <div style="margin-bottom:12px">
                        <p style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);margin-bottom:6px">Capa atual</p>
                        <img
                            src="{{ $material->cover_url }}"
                            alt="Capa atual"
                            class="cover-preview"
                        >
                    </div>
                    @endif

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="cover_image">
                            {{ $isEdit && $material->cover_image ? 'Substituir capa' : 'Imagem de capa' }}
                        </label>
                        <input
                            type="file"
                            id="cover_image"
                            name="cover_image"
                            class="input {{ $errors->has('cover_image') ? 'input-error' : '' }}"
                            accept=".jpg,.jpeg,.png,.webp"
                            style="padding:6px 10px;height:auto"
                        >
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">
                            JPG, JPEG, PNG ou WebP. Máximo 2 MB. Opcional.
                        </p>
                        @error('cover_image')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- Setores --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Setores / Sistemas</span>
                </div>
                <div class="card-body">
                    @if($systems->isEmpty())
                        <p style="font-size:0.84rem;color:var(--text-muted)">Nenhum sistema ativo encontrado.</p>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px">
                            @foreach($systems as $system)
                            @php
                                $checked = in_array(
                                    $system->id,
                                    old('systems', $isEdit ? $material->systems->pluck('id')->toArray() : [])
                                );
                            @endphp
                            <label class="checkbox-card">
                                <input
                                    type="checkbox"
                                    name="systems[]"
                                    value="{{ $system->id }}"
                                    {{ $checked ? 'checked' : '' }}
                                >
                                <span class="checkbox-card-label">{{ $system->name }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('systems')
                        <p class="form-error" style="margin-top:6px">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
            </div>

            {{-- Botões --}}
            <div style="display:flex;flex-direction:column;gap:8px">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Salvar Material
                </button>
                <a href="{{ route('conteudo.materiais.index') }}" class="btn btn-ghost" style="width:100%;justify-content:center">
                    Cancelar
                </a>
            </div>

        </div>
    </div>

</form>

{{-- Responsividade do grid do formulário --}}
<style>
@media (max-width: 900px) {
    .form-layout {
        grid-template-columns: 1fr !important;
    }
}
</style>

@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function () {
    const quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Descreva o conteúdo do material (opcional)...',
        modules: {
            toolbar: [
                ['bold', 'italic'],
                [{ list: 'bullet' }, { list: 'ordered' }],
                ['link'],
                ['clean'],
            ],
        },
    });

    // Sincronizar com textarea hidden no submit
    window._syncQuill = function () {
        const hidden = document.getElementById('description-hidden');
        if (hidden) {
            hidden.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
        }
    };
})();
</script>
<script>
function materialForm() {
    return {
        syncQuill() {
            if (typeof window._syncQuill === 'function') {
                window._syncQuill();
            }
        },
    };
}
</script>
@endpush
