@extends('layouts.app')

@php
    $isEdit = $question !== null;
    $formAction = $isEdit ? route('questoes.update', $question) : route('questoes.store');
    $selectedSystem = old('client_system_id', $question?->client_system_id);
    $selectedDiscipline = old('discipline_id', $question?->discipline_id);
    $selectedType = old('type', $question?->type ?? 'multiple_choice');
    $selectedDifficulty = old('difficulty', $question?->difficulty ?? 'medium');
    $isActive = old('active', $question?->active ?? true);
    $tagsValue = old('tags', is_array($question?->tags) ? implode(', ', $question->tags) : '');
    $initialChoices = collect(old('choices', $question?->choices?->map(fn ($choice) => [
        'text' => $choice->content ?? $choice->text ?? '',
        'is_correct' => (bool) $choice->is_correct,
    ])->toArray() ?? [
        ['text' => '', 'is_correct' => true],
        ['text' => '', 'is_correct' => false],
    ]))->values()->all();
@endphp

@section('title', ($isEdit ? 'Editar Questão' : 'Nova Questão') . ' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Questão' : 'Nova Questão')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Editar questão' : 'Criar nova questão' }}</h1>
            <p class="page-subtitle">Monte enunciados claros, defina o tipo e configure as alternativas com consistência visual.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($isEdit)
            <a href="{{ route('questoes.show', $question) }}" class="btn btn-ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Ver questão
            </a>
            @endif
            <a href="{{ route('questoes.index') }}" class="btn btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar
            </a>
        </div>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <div>
        <div style="font-weight:700;margin-bottom:2px">Revise o cadastro da questão antes de salvar.</div>
        <div style="font-size:0.8rem;opacity:0.92">Existem {{ $errors->count() }} inconsistências no formulário.</div>
    </div>
</div>
@endif

<form method="POST" action="{{ $formAction }}" x-data="questaoForm(@js($initialChoices), @js($selectedType))">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="questoes-form-grid" style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(310px,0.9fr);gap:20px;align-items:start">
        <div style="display:grid;gap:20px">
            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Enunciado e contexto</div>
                        <div class="card-subtitle">Conteúdo principal, explicação e classificação da questão.</div>
                    </div>
                    <span class="badge badge-primary">{{ $isEdit ? 'Edição' : 'Nova questão' }}</span>
                </div>
                <div class="card-body">
                    <div class="questoes-form-meta" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="client_system_id" class="form-label">Sistema</label>
                            <select id="client_system_id" name="client_system_id" class="input {{ $errors->has('client_system_id') ? 'input-error' : '' }}">
                                <option value="">Selecione um sistema</option>
                                @foreach($systems as $system)
                                <option value="{{ $system->id }}" @selected((string) $selectedSystem === (string) $system->id)>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            @error('client_system_id')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0" x-data="{ createNew: {{ old('new_discipline_name') ? 'true' : 'false' }} }">
                            <label for="discipline_id" class="form-label">Disciplina</label>
                            <div x-show="!createNew">
                                <select id="discipline_id" name="discipline_id" class="input" x-on:change="if($el.value === '__new__') { $el.value = ''; createNew = true; $nextTick(() => $refs.newDiscName.focus()) }">
                                    <option value="">Sem disciplina</option>
                                    <option value="__new__">+ Criar nova disciplina</option>
                                    @foreach($disciplines ?? [] as $disc)
                                    <option value="{{ $disc->id }}" data-system="{{ $disc->client_system_id }}" @selected((string) $selectedDiscipline === (string) $disc->id)>{{ $disc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div x-show="createNew" x-cloak style="display:flex;gap:6px;align-items:center">
                                <input x-ref="newDiscName" type="text" name="new_discipline_name" value="{{ old('new_discipline_name') }}" class="input {{ $errors->has('new_discipline_name') ? 'input-error' : '' }}" placeholder="Nome da nova disciplina" maxlength="255" style="flex:1">
                                <button type="button" class="btn btn-ghost btn-sm" @click="createNew = false" style="white-space:nowrap;font-size:0.75rem;padding:5px 10px" title="Voltar para lista">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            @error('new_discipline_name')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="questoes-form-meta" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:16px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="type" class="form-label">Tipo</label>
                            <select id="type" name="type" x-model="type" class="input {{ $errors->has('type') ? 'input-error' : '' }}">
                                <option value="multiple_choice">Múltipla escolha</option>
                                <option value="true_false">Verdadeiro/Falso</option>
                                <option value="multiple_answer">Múltiplas respostas</option>
                            </select>
                            @error('type')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0">
                            <label for="difficulty" class="form-label">Dificuldade</label>
                            <select id="difficulty" name="difficulty" class="input {{ $errors->has('difficulty') ? 'input-error' : '' }}">
                                <option value="easy" @selected($selectedDifficulty === 'easy')>Fácil</option>
                                <option value="medium" @selected($selectedDifficulty === 'medium')>Média</option>
                                <option value="hard" @selected($selectedDifficulty === 'hard')>Difícil</option>
                            </select>
                            @error('difficulty')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="divider">

                    <div class="form-group">
                        <label class="form-label">Enunciado</label>
                        <div id="content-editor" class="quill-editor {{ $errors->has('content') ? 'quill-error' : '' }}" style="min-height:190px"></div>
                        <input type="hidden" id="content" name="content" value="{{ old('content', $question?->content) }}">
                        @error('content')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Explicação / feedback</label>
                        <div id="explanation-editor" class="quill-editor" style="min-height:120px"></div>
                        <input type="hidden" id="explanation" name="explanation" value="{{ old('explanation', $question?->explanation) }}">
                        @error('explanation')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label for="tags" class="form-label">Tags</label>
                        <input id="tags" type="text" name="tags" value="{{ $tagsValue }}" class="input {{ $errors->has('tags') ? 'input-error' : '' }}" placeholder="constitucional, cidadania, av1">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:5px">Use vírgulas para separar os termos de indexação.</div>
                        @error('tags')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Alternativas</div>
                        <div class="card-subtitle">Configure as opções e sinalize a resposta correta conforme o tipo selecionado.</div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" @click="addChoice()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Adicionar alternativa
                    </button>
                </div>
                <div class="card-body">
                    <div class="alert alert-info" style="margin-bottom:16px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span x-text="helperText"></span>
                    </div>

                    <div style="display:grid;gap:12px">
                        <template x-for="(choice, index) in choices" :key="index">
                            <div style="border:1px solid var(--surface-border);border-radius:14px;padding:14px 16px;background:var(--surface-bg)">
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <span class="badge badge-neutral" x-text="'Opção ' + String.fromCharCode(65 + index)"></span>
                                        <button type="button" @click="toggleCorrect(index)" class="btn btn-ghost btn-sm" :style="choice.is_correct ? 'color:var(--color-success);border-color:rgba(16,185,129,0.25);background:var(--color-success-bg)' : ''">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                            <span x-text="choice.is_correct ? 'Correta' : 'Marcar correta'"></span>
                                        </button>
                                    </div>
                                    <button type="button" class="btn btn-ghost btn-sm" @click="removeChoice(index)" x-show="choices.length > 2" style="color:var(--color-danger)">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                        Remover
                                    </button>
                                </div>

                                <textarea class="input" :name="`choices[${index}][text]`" x-model="choice.text" rows="3" placeholder="Digite o texto desta alternativa." style="resize:vertical;min-height:88px"></textarea>
                                <input type="hidden" :name="`choices[${index}][is_correct]`" :value="choice.is_correct ? 1 : 0">
                            </div>
                        </template>
                    </div>

                    @error('choices')<div class="form-error" style="margin-top:10px">{{ $message }}</div>@enderror
                    @error('choices.*.text')<div class="form-error" style="margin-top:10px">{{ $message }}</div>@enderror
                </div>
            </section>
        </div>

        <div style="display:grid;gap:20px">
            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Status da questão</div>
                        <div class="card-subtitle">Controle de visibilidade e checklist de publicação.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div x-data="{ enabled: @js((bool) $isActive) }" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 16px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                        <div>
                            <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary)">Questão ativa</div>
                            <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px;line-height:1.55">Questões inativas não devem entrar em fluxos de composição e seleção operacional.</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                            <span class="badge" :class="enabled ? 'badge-success' : 'badge-neutral'" x-text="enabled ? 'Ativa' : 'Inativa'"></span>
                            <button type="button" @click="enabled = !enabled" :style="enabled ? 'background:var(--color-primary-600)' : 'background:#CBD5E1'" style="width:46px;height:26px;border:none;border-radius:9999px;position:relative;cursor:pointer;padding:0">
                                <span :style="enabled ? 'transform:translateX(20px)' : 'transform:translateX(0)'" style="position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:9999px;background:white;box-shadow:0 2px 6px rgba(15,23,42,0.2);transition:transform 0.16s ease"></span>
                            </button>
                            <input type="hidden" name="active" :value="enabled ? '1' : '0'">
                        </div>
                    </div>

                    <hr class="divider">

                    <div style="display:grid;gap:10px">
                        <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);font-size:0.8rem;color:var(--text-secondary)">
                            <strong style="color:var(--text-primary)">Tipos suportados agora:</strong> múltipla escolha, verdadeiro/falso e múltiplas respostas.
                        </div>
                        <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);font-size:0.8rem;color:var(--text-secondary)">
                            <strong style="color:var(--text-primary)">Regra visual:</strong> preserve clareza do enunciado, equilíbrio de texto e contraste adequado entre estados.
                        </div>
                    </div>

                    <hr class="divider">

                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <button type="submit" class="btn btn-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            {{ $isEdit ? 'Salvar alterações' : 'Criar questão' }}
                        </button>
                        <a href="{{ $isEdit ? route('questoes.show', $question) : route('questoes.index') }}" class="btn btn-ghost">Cancelar</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
    @media (max-width: 1100px) {
        .questoes-form-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 760px) {
        .questoes-form-meta {
            grid-template-columns: 1fr !important;
        }
    }

    .quill-editor {
        border: 1px solid var(--surface-border, #e2e8f0);
        border-radius: 10px;
        background: #fff;
        font-size: 0.93rem;
        font-family: inherit;
    }

    .quill-error {
        border-color: var(--color-danger, #ef4444);
    }

    .quill-editor .ql-toolbar {
        border: none;
        border-bottom: 1px solid var(--surface-border, #e2e8f0);
        border-radius: 10px 10px 0 0;
        background: var(--surface-bg, #f8faff);
    }

    .quill-editor .ql-container {
        border: none;
        border-radius: 0 0 10px 10px;
        font-family: inherit;
        font-size: 0.93rem;
    }

    .quill-editor .ql-editor {
        min-height: 140px;
        line-height: 1.65;
    }

    .quill-editor img {
        max-width: 100%;
        border-radius: 6px;
        margin: 4px 0;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    // ── Quill editor — Enunciado e Explicação ────────────────────────────────
    (function() {
        const uploadUrl    = @json(route('questoes.upload-imagem'));
        const csrfToken    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        function imageUploadHandler(editorInstance) {
            const input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/jpeg,image/png,image/gif,image/webp');
            input.click();

            input.addEventListener('change', async () => {
                const file = input.files[0];
                if (!file) return;

                const form = new FormData();
                form.append('image', file);

                try {
                    const res  = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: form,
                    });
                    const data = await res.json();

                    if (data.url) {
                        const range = editorInstance.getSelection(true);
                        editorInstance.insertEmbed(range.index, 'image', data.url);
                        editorInstance.setSelection(range.index + 1);
                    }
                } catch (e) {
                    alert('Erro ao enviar imagem. Tente novamente.');
                }
            });
        }

        function initQuill(editorId, hiddenId, toolbarFull) {
            const toolbarOptions = toolbarFull
                ? [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean'],
                  ]
                : [
                    ['bold', 'italic', 'underline'],
                    ['link', 'image'],
                    ['clean'],
                  ];

            const quill = new Quill('#' + editorId, {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            image: function() { imageUploadHandler(this.quill); },
                        },
                    },
                },
                placeholder: editorId === 'content-editor'
                    ? 'Digite o enunciado completo da questão...'
                    : 'Opcional: explique a resposta correta ou o racional pedagógico...',
            });

            const hiddenInput = document.getElementById(hiddenId);

            // Popula editor com conteúdo existente
            const existing = hiddenInput.value;
            if (existing) {
                quill.clipboard.dangerouslyPasteHTML(existing);
            }

            // Sincroniza hidden input ao alterar
            quill.on('text-change', () => {
                hiddenInput.value = quill.getSemanticHTML();
            });

            return quill;
        }

        initQuill('content-editor',     'content',     true);
        initQuill('explanation-editor', 'explanation', false);
    })();

    // Filtrar disciplinas pelo sistema selecionado
    (function() {
        const systemSelect = document.getElementById('client_system_id');
        const discSelect = document.getElementById('discipline_id');
        if (!systemSelect || !discSelect) return;

        const allOptions = Array.from(discSelect.querySelectorAll('option[data-system]'));

        function filterDisciplines() {
            const systemId = systemSelect.value;
            const selectedVal = discSelect.value;

            allOptions.forEach(opt => opt.remove());
            allOptions.forEach(opt => {
                if (!systemId || opt.dataset.system === systemId) {
                    discSelect.appendChild(opt);
                }
            });

            const stillExists = Array.from(discSelect.options).some(o => o.value === selectedVal);
            discSelect.value = stillExists ? selectedVal : '';
        }

        systemSelect.addEventListener('change', filterDisciplines);
        filterDisciplines();
    })();

    function questaoForm(initialChoices, initialType) {
        return {
            type: initialType,
            choices: initialChoices.length ? initialChoices : [
                { text: '', is_correct: true },
                { text: '', is_correct: false },
            ],

            get helperText() {
                if (this.type === 'multiple_answer') {
                    return 'Neste tipo, múltiplas alternativas corretas são permitidas.';
                }

                if (this.type === 'true_false') {
                    return 'Use duas alternativas objetivas e mantenha apenas uma resposta correta.';
                }

                return 'Para múltipla escolha, mantenha apenas uma alternativa correta.';
            },

            normalizeChoices() {
                if (this.type === 'true_false' && this.choices.length < 2) {
                    this.choices = [
                        { text: 'Verdadeiro', is_correct: true },
                        { text: 'Falso', is_correct: false },
                    ];
                }

                if (this.type !== 'multiple_answer') {
                    let firstCorrectFound = false;
                    this.choices = this.choices.map((choice, index) => {
                        const nextChoice = { ...choice };

                        if (nextChoice.is_correct && !firstCorrectFound) {
                            firstCorrectFound = true;
                            nextChoice.is_correct = true;
                        } else {
                            nextChoice.is_correct = false;
                        }

                        if (!firstCorrectFound && index === this.choices.length - 1) {
                            nextChoice.is_correct = true;
                        }

                        return nextChoice;
                    });
                }
            },

            addChoice() {
                this.choices.push({ text: '', is_correct: false });
            },

            removeChoice(index) {
                if (this.choices.length <= 2) {
                    return;
                }

                this.choices.splice(index, 1);
                this.normalizeChoices();
            },

            toggleCorrect(index) {
                if (this.type === 'multiple_answer') {
                    this.choices[index].is_correct = !this.choices[index].is_correct;
                    return;
                }

                this.choices = this.choices.map((choice, choiceIndex) => ({
                    ...choice,
                    is_correct: choiceIndex === index,
                }));
            },

            init() {
                this.normalizeChoices();
                this.$watch('type', () => this.normalizeChoices());
            },
        };
    }
</script>
@endpush
