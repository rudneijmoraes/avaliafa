@extends('layouts.app')

@php
    $isEdit = $tip !== null;
@endphp

@section('title', ($isEdit ? 'Editar Dica' : 'Nova Dica') . ' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Dica' : 'Nova Dica')

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
    .video-preview-wrapper {
        position: relative;
        width: 100%;
        padding-top: 56.25%; /* 16:9 */
        background: var(--surface-bg);
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--surface-border);
    }
    .video-preview-wrapper iframe {
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        border: none;
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
            <h1 class="page-title">{{ $isEdit ? 'Editar Dica' : 'Nova Dica do Professor' }}</h1>
            <p class="page-subtitle">{{ $isEdit ? 'Atualize as informações da dica em vídeo.' : 'Adicione um novo vídeo de dica para exibição no portal do aluno.' }}</p>
        </div>
        <a href="{{ route('conteudo.dicas.index') }}" class="btn btn-secondary">
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
    action="{{ $isEdit ? route('conteudo.dicas.update', $tip) : route('conteudo.dicas.store') }}"
    x-data="dicaForm()"
    @submit="syncQuill()"
>
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start" class="form-layout">

        {{-- Coluna principal --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Título --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Informações da Dica</span>
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
                            value="{{ old('title', $tip?->title) }}"
                            maxlength="180"
                            required
                            placeholder="Ex: Como estudar para provas discursivas"
                        >
                        @error('title')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Descrição (Quill) --}}
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Descrição</label>
                        <div id="quill-editor" style="background:var(--surface-input)">{{ old('description', $tip?->description) }}</div>
                        <textarea name="description" id="description-hidden" style="display:none">{{ old('description', $tip?->description) }}</textarea>
                        @error('description')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                </div>
            </div>

            {{-- URL do Vídeo + Preview --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Vídeo</span>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label" for="video_url">
                            URL do Vídeo <span style="color:var(--color-danger)">*</span>
                        </label>
                        <input
                            type="url"
                            id="video_url"
                            name="video_url"
                            class="input {{ $errors->has('video_url') ? 'input-error' : '' }}"
                            value="{{ old('video_url', $tip?->video_url) }}"
                            x-model="videoUrl"
                            @input="updateEmbed()"
                            required
                            placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/..."
                        >
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">
                            Suportado: YouTube (youtube.com, youtu.be) e Vimeo (vimeo.com)
                        </p>
                        @error('video_url')
                        <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Preview ao vivo --}}
                    <div x-show="embedUrl" x-cloak style="margin-top:4px">
                        <p style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:8px">Prévia do vídeo</p>
                        <div class="video-preview-wrapper">
                            <iframe
                                :src="embedUrl"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                                title="Prévia do vídeo"
                            ></iframe>
                        </div>
                    </div>

                    <div x-show="videoUrl && !embedUrl" x-cloak>
                        <div class="alert alert-warning" style="margin-top:8px;margin-bottom:0">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            URL não reconhecida. Verifique se é um link válido do YouTube ou Vimeo.
                        </div>
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

                    {{-- Ativo --}}
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <label class="toggle-switch">
                            <input
                                type="checkbox"
                                name="active"
                                value="1"
                                id="active"
                                {{ old('active', $isEdit ? ($tip->active ? '1' : '') : '1') ? 'checked' : '' }}
                            >
                            <span class="toggle-track"></span>
                            <span style="font-size:0.875rem;color:var(--text-secondary)">
                                Dica ativa (visível no portal)
                            </span>
                        </label>
                    </div>

                    {{-- Ordem --}}
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="order">Ordem de exibição</label>
                        <input
                            type="number"
                            id="order"
                            name="order"
                            class="input {{ $errors->has('order') ? 'input-error' : '' }}"
                            value="{{ old('order', $tip?->order ?? 0) }}"
                            min="0"
                            placeholder="0"
                        >
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">
                            Número menor = exibido primeiro.
                        </p>
                        @error('order')
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
                                    old('systems', $isEdit ? $tip->systems->pluck('id')->toArray() : [])
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

            {{-- Simulado vinculado --}}
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Simulado vinculado</span>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="simulado_id">Simulado (opcional)</label>
                        <select id="simulado_id" name="simulado_id" class="input">
                            <option value="">— Nenhum (dica geral) —</option>
                            @foreach($simulados as $sim)
                                <option value="{{ $sim->id }}" {{ old('simulado_id', $tip?->simulado_id) == $sim->id ? 'selected' : '' }}>
                                    {{ $sim->name }}
                                </option>
                            @endforeach
                        </select>
                        <p style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">
                            Quando vinculada, o nome do simulado aparece na dica do aluno.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Botões --}}
            <div style="display:flex;flex-direction:column;gap:8px">
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Salvar Dica
                </button>
                <a href="{{ route('conteudo.dicas.index') }}" class="btn btn-ghost" style="width:100%;justify-content:center">
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
        placeholder: 'Descreva o conteúdo da dica (opcional)...',
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
function dicaForm() {
    return {
        videoUrl: document.getElementById('video_url') ? document.getElementById('video_url').value : '',
        embedUrl: '',

        init() {
            this.updateEmbed();
        },

        updateEmbed() {
            const url = this.videoUrl.trim();
            if (!url) {
                this.embedUrl = '';
                return;
            }

            // YouTube: youtube.com/watch?v=ID ou youtu.be/ID
            const ytMatch = url.match(
                /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_\-]{11})/
            );
            if (ytMatch) {
                this.embedUrl = `https://www.youtube.com/embed/${ytMatch[1]}`;
                return;
            }

            // Vimeo: vimeo.com/ID
            const vimeoMatch = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
            if (vimeoMatch) {
                this.embedUrl = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
                return;
            }

            this.embedUrl = '';
        },

        syncQuill() {
            if (typeof window._syncQuill === 'function') {
                window._syncQuill();
            }
        },
    };
}
</script>
@endpush
