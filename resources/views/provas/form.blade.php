@extends('layouts.app')

@php
    $isEdit = $exam !== null;
    $formAction = $isEdit ? route('provas.update', $exam) : route('provas.store');
    $statusValue = old('status', $exam?->status ?? 'draft');
    $selectedSystem = old('client_system_id', $exam?->client_system_id);
    $selectedDiscipline = old('discipline_id', $exam?->discipline_id);
    $startsAt = old('starts_at', $exam?->starts_at?->format('Y-m-d\TH:i'));
    $endsAt = old('ends_at', $exam?->ends_at?->format('Y-m-d\TH:i'));
    $moodleCourseId = old('moodle_course_id', data_get($exam?->settings, 'moodle.course_id'));
    $moodleActivityId = old('moodle_activity_id', data_get($exam?->settings, 'moodle.activity_id'));
    $instructionsHtml = old('instructions_html', data_get($exam?->settings, 'instructions_html', ''));
@endphp

@section('title', ($isEdit ? 'Editar Prova' : 'Nova Prova') . ' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Prova' : 'Nova Prova')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Editar prova' : 'Criar nova prova' }}</h1>
            <p class="page-subtitle">
                {{ $isEdit ? 'Atualize as configurações da avaliação sem quebrar o padrão visual do sistema.' : 'Defina o escopo, período e regras de aplicação da avaliação.' }}
            </p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @if($isEdit)
            <a href="{{ route('provas.show', $exam) }}" class="btn btn-ghost">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                Ver detalhes
            </a>
            @endif
            <a href="{{ route('provas.index') }}" class="btn btn-secondary">
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
        <div style="font-weight:700;margin-bottom:2px">Revise os campos obrigatórios antes de salvar.</div>
        <div style="font-size:0.8rem;opacity:0.92">Existem {{ $errors->count() }} inconsistências no formulário.</div>
    </div>
</div>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="provas-form-grid" style="display:grid;grid-template-columns:minmax(0,1.55fr) minmax(300px,0.95fr);gap:20px;align-items:start">
        <div style="display:grid;gap:20px">
            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Informações principais</div>
                        <div class="card-subtitle">Identificação da prova e contexto acadêmico.</div>
                    </div>
                    <span class="badge badge-primary">{{ $isEdit ? 'Edição' : 'Criação' }}</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title" class="form-label">Título da prova</label>
                        <input id="title" type="text" name="title" value="{{ old('title', $exam?->title) }}" class="input {{ $errors->has('title') ? 'input-error' : '' }}" maxlength="255" placeholder="Ex.: AV1 - Direito Constitucional">
                        @error('title')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea id="description" name="description" rows="5" class="input {{ $errors->has('description') ? 'input-error' : '' }}" maxlength="2000" placeholder="Descreva a finalidade, instruções ou observações desta prova." style="resize:vertical;min-height:132px">{{ old('description', $exam?->description) }}</textarea>
                        @error('description')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Parâmetros de aplicação</div>
                        <div class="card-subtitle">Tempo, pontuação mínima, tolerância a violações e período.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="provas-form-triplet" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="duration_minutes" class="form-label">Duração (minutos)</label>
                            <input id="duration_minutes" type="number" min="5" max="600" name="duration_minutes" value="{{ old('duration_minutes', $exam?->duration_minutes ?? 60) }}" class="input {{ $errors->has('duration_minutes') ? 'input-error' : '' }}">
                            @error('duration_minutes')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0">
                            <label for="passing_score" class="form-label">Pontuação mínima</label>
                            <input id="passing_score" type="number" min="0" step="0.01" name="passing_score" value="{{ old('passing_score', $exam?->passing_score ?? 6) }}" class="input {{ $errors->has('passing_score') ? 'input-error' : '' }}">
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px">Informe a pontuação total necessária para a prova.</div>
                            @error('passing_score')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0">
                            <label for="max_violations" class="form-label">Violações máximas</label>
                            <input id="max_violations" type="number" min="0" max="20" name="max_violations" value="{{ old('max_violations', $exam?->max_violations ?? 3) }}" class="input {{ $errors->has('max_violations') ? 'input-error' : '' }}">
                            @error('max_violations')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <hr class="divider">

                    <div class="provas-form-dates" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
                        <div class="form-group" style="margin-bottom:0">
                            <label for="starts_at" class="form-label">Início da aplicação</label>
                            <input id="starts_at" type="datetime-local" name="starts_at" value="{{ $startsAt }}" class="input {{ $errors->has('starts_at') ? 'input-error' : '' }}">
                            @error('starts_at')<div class="form-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group" style="margin-bottom:0">
                            <label for="ends_at" class="form-label">Fim da aplicação</label>
                            <input id="ends_at" type="datetime-local" name="ends_at" value="{{ $endsAt }}" class="input {{ $errors->has('ends_at') ? 'input-error' : '' }}">
                            @error('ends_at')<div class="form-error">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Orientações da prova</div>
                        <div class="card-subtitle">Conteúdo exibido no topo da prova, antes das perguntas.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Bloco de orientações</label>
                        <div class="rich-editor-shell">
                            <div class="rich-editor-toolbar">
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="bold">Negrito</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="italic">Itálico</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="underline">Sublinhado</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="formatBlock" data-rich-value="h3">Título</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="insertUnorderedList">Lista</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="insertOrderedList">Numerada</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="justifyLeft">Esquerda</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="justifyCenter">Centro</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="justifyRight">Direita</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="justifyFull">Justificar</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="createLink">Link</button>
                                <button type="button" class="btn btn-ghost btn-sm" data-rich-command="removeFormat">Limpar</button>
                            </div>
                            <div class="rich-editor-input" contenteditable="true" data-rich-editor="exam-instructions-editor">{!! $instructionsHtml !!}</div>
                            <textarea name="instructions_html" data-rich-target="exam-instructions-editor" style="display:none">{{ $instructionsHtml }}</textarea>
                        </div>
                        <div style="font-size:0.74rem;color:var(--text-muted);margin-top:8px">
                            Esse conteúdo aparece antes das questões para orientar o aluno sobre regras e instruções específicas da prova.
                        </div>
                        @error('instructions_html')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
        </div>

        <div style="display:grid;gap:20px">
            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Contexto institucional</div>
                        <div class="card-subtitle">Sistema responsável e estágio da prova.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="client_system_id" class="form-label">Sistema</label>
                        <select id="client_system_id" name="client_system_id" class="input {{ $errors->has('client_system_id') ? 'input-error' : '' }}">
                            <option value="">Selecione um sistema</option>
                            @foreach($systems as $system)
                            <option value="{{ $system->id }}" @selected((string) $selectedSystem === (string) $system->id)>{{ $system->name }}</option>
                            @endforeach
                        </select>
                        @error('client_system_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" x-data="{ createNew: {{ old('new_discipline_name') ? 'true' : 'false' }} }">
                        <label for="discipline_id" class="form-label">Disciplina</label>
                        <div x-show="!createNew">
                            <select id="discipline_id" name="discipline_id" class="input {{ $errors->has('discipline_id') ? 'input-error' : '' }}" x-on:change="if($el.value === '__new__') { $el.value = ''; createNew = true; $nextTick(() => $refs.newDiscName.focus()) }">
                                <option value="">Nenhuma (opcional)</option>
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
                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px">Filtrada pelo sistema. Ou crie uma nova direto aqui.</div>
                        @error('discipline_id')<div class="form-error">{{ $message }}</div>@enderror
                        @error('new_discipline_name')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="input {{ $errors->has('status') ? 'input-error' : '' }}">
                            <option value="draft" @selected($statusValue === 'draft')>Rascunho</option>
                            <option value="published" @selected($statusValue === 'published')>Publicada</option>
                            <option value="active" @selected($statusValue === 'active')>Ativa</option>
                            <option value="closed" @selected($statusValue === 'closed')>Encerrada</option>
                            <option value="archived" @selected($statusValue === 'archived')>Arquivada</option>
                        </select>
                        @error('status')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Integração Moodle</div>
                        <div class="card-subtitle">Sobrescreva curso e atividade para sincronização desta prova.</div>
                    </div>
                </div>
                <div class="card-body" x-data="provaMoodleCourses()" x-init="init()">
                    <div class="form-group" style="margin-bottom:12px">
                        <label for="moodle_course_id" class="form-label">Curso Moodle</label>
                        <div style="display:flex;gap:8px">
                            <select id="moodle_course_id" name="moodle_course_id" class="input {{ $errors->has('moodle_course_id') ? 'input-error' : '' }}" style="flex:1">
                                <option value="">Usar padrão do sistema</option>
                                <template x-if="courses.length === 0 && currentCourseId">
                                    <option :value="currentCourseId" selected x-text="'ID ' + currentCourseId + ' (clique Carregar)'"></option>
                                </template>
                                <template x-for="c in courses" :key="c.id">
                                    <option :value="c.id" :selected="c.id == currentCourseId" x-text="c.fullname + ' (' + c.shortname + ')'"></option>
                                </template>
                            </select>
                            <button type="button" class="btn btn-secondary" style="white-space:nowrap;padding:6px 12px;font-size:0.78rem" @click="fetchCourses()" :disabled="loading">
                                <span x-show="!loading">Carregar</span>
                                <span x-show="loading">...</span>
                            </button>
                        </div>
                        <div x-show="error" style="color:var(--color-danger);font-size:0.78rem;margin-top:4px" x-text="error"></div>
                        <div x-show="courses.length > 0" style="color:var(--color-success);font-size:0.78rem;margin-top:4px" x-text="courses.length + ' cursos'"></div>
                        @error('moodle_course_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label for="moodle_activity_id" class="form-label">Activity ID</label>
                        <input id="moodle_activity_id" type="number" min="0" name="moodle_activity_id" value="{{ $moodleActivityId }}" class="input {{ $errors->has('moodle_activity_id') ? 'input-error' : '' }}" placeholder="Opcional (resolve automaticamente)">
                        @error('moodle_activity_id')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Configurações de segurança</div>
                        <div class="card-subtitle">Defina como a prova se comporta para o aluno.</div>
                    </div>
                </div>
                <div class="card-body" style="display:grid;gap:14px">
                    @php
                        $snapshotIntervalSeconds = old('snapshot_interval_seconds', data_get($exam?->settings ?? [], 'snapshot_interval_seconds', 60));
                        $toggles = [
                            ['name' => 'webcam_enabled', 'label' => 'Exigir webcam', 'description' => 'Solicita captura de webcam e evidencia visual durante a prova.', 'value' => old('webcam_enabled', $exam?->webcam_enabled ?? false)],
                            ['name' => 'face_recognition_enabled', 'label' => 'Reconhecimento facial', 'description' => 'Verifica a identidade do aluno por biometria facial a cada 2 minutos. Exige webcam e foto de referência cadastrada.', 'value' => old('face_recognition_enabled', $exam?->face_recognition_enabled ?? false)],
                            ['name' => 'shuffle_questions', 'label' => 'Embaralhar questões', 'description' => 'Altera a ordem das questões para cada sessão.', 'value' => old('shuffle_questions', $exam?->shuffle_questions ?? true)],
                            ['name' => 'shuffle_choices', 'label' => 'Embaralhar alternativas', 'description' => 'Mistura a ordem das alternativas nas questões objetivas.', 'value' => old('shuffle_choices', $exam?->shuffle_choices ?? true)],
                        ];
                    @endphp

                    @foreach($toggles as $toggle)
                    <label for="{{ $toggle['name'] }}" style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 16px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);cursor:pointer">
                        <div style="flex:1">
                            <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary)">{{ $toggle['label'] }}</div>
                            <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px;line-height:1.55">{{ $toggle['description'] }}</div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                            <span class="badge {{ (bool) $toggle['value'] ? 'badge-success' : 'badge-neutral' }}">{{ (bool) $toggle['value'] ? 'Ativado' : 'Desativado' }}</span>
                            <input
                                id="{{ $toggle['name'] }}"
                                type="checkbox"
                                name="{{ $toggle['name'] }}"
                                value="1"
                                {{ (bool) $toggle['value'] ? 'checked' : '' }}
                                style="width:20px;height:20px;accent-color:var(--color-primary-600);cursor:pointer;flex-shrink:0"
                            >
                        </div>
                    </label>
                    @endforeach

                    <div style="display:grid;gap:6px;padding:14px 16px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);">
                        <label for="snapshot_interval_seconds" style="font-size:0.9rem;font-weight:700;color:var(--text-primary)">Intervalo dos snapshots</label>
                        <div style="font-size:0.78rem;color:var(--text-secondary);line-height:1.55">
                            Define a frequencia da evidencia visual da webcam durante a prova. O padrao recomendado para auditoria e 60 segundos.
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;max-width:240px">
                            <input id="snapshot_interval_seconds" type="number" min="60" max="600" step="10" name="snapshot_interval_seconds" value="{{ $snapshotIntervalSeconds }}" class="input {{ $errors->has('snapshot_interval_seconds') ? 'input-error' : '' }}">
                            <span style="font-size:0.82rem;font-weight:700;color:var(--text-secondary)">segundos</span>
                        </div>
                        @error('snapshot_interval_seconds')<div class="form-error">{{ $message }}</div>@enderror
                    </div>

                    @php
                        $brandingWatermarkText = old('branding_watermark_text', data_get($exam?->settings ?? [], 'branding_watermark_text', ''));
                    @endphp
                    <div style="display:grid;gap:6px;padding:14px 16px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);">
                        <label for="branding_watermark_text" style="font-size:0.9rem;font-weight:700;color:var(--text-primary)">Marca institucional no topo</label>
                        <div style="font-size:0.78rem;color:var(--text-secondary);line-height:1.55">
                            Exibe um nome centralizado e discreto no topo das orientações, textos-base e questões. Exemplo: Anasps.
                        </div>
                        <div style="max-width:280px">
                            <input id="branding_watermark_text" type="text" maxlength="80" name="branding_watermark_text" value="{{ $brandingWatermarkText }}" class="input {{ $errors->has('branding_watermark_text') ? 'input-error' : '' }}" placeholder="Ex.: Anasps">
                        </div>
                        @error('branding_watermark_text')<div class="form-error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Resumo de publicação</div>
                        <div class="card-subtitle">Revisão rápida antes de salvar.</div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:grid;gap:12px">
                        <div style="display:flex;justify-content:space-between;gap:12px;font-size:0.82rem">
                            <span style="color:var(--text-secondary)">Status inicial</span>
                            <span style="font-weight:700;color:var(--text-primary)">{{ match($statusValue) { 'draft' => 'Rascunho', 'published' => 'Publicada', 'active' => 'Ativa', 'closed' => 'Encerrada', 'archived' => 'Arquivada', default => ucfirst($statusValue) } }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:12px;font-size:0.82rem">
                            <span style="color:var(--text-secondary)">Sistema selecionado</span>
                            <span style="font-weight:700;color:var(--text-primary)">{{ optional($systems->firstWhere('id', $selectedSystem))->name ?? 'Não definido' }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;gap:12px;font-size:0.82rem">
                            <span style="color:var(--text-secondary)">Estratégia recomendada</span>
                            <span style="font-weight:700;color:var(--text-primary)">Salvar e revisar questões</span>
                        </div>
                    </div>

                    <hr class="divider">

                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <button type="submit" class="btn btn-primary">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            {{ $isEdit ? 'Salvar alterações' : 'Criar prova' }}
                        </button>
                        <a href="{{ $isEdit ? route('provas.show', $exam) : route('provas.index') }}" class="btn btn-ghost">Cancelar</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</form>
@endsection

@push('styles')
<style>
    .rich-editor-shell {
        border: 1px solid var(--surface-border);
        border-radius: 12px;
        background: var(--surface-card);
        overflow: hidden;
    }

    .rich-editor-toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        padding: 10px;
        border-bottom: 1px solid var(--surface-border);
        background: var(--surface-bg);
    }

    .rich-editor-input {
        min-height: 180px;
        padding: 14px 16px;
        outline: none;
        line-height: 1.7;
        color: var(--text-primary);
    }

    .rich-editor-input:empty::before {
        content: 'Digite as orientações da prova aqui...';
        color: var(--text-muted);
    }
</style>
@endpush

@push('scripts')
<script>
function initExamFormRichEditors() {
    document.querySelectorAll('[data-rich-editor]').forEach((editor) => {
        const key = editor.getAttribute('data-rich-editor');
        const target = document.querySelector(`[data-rich-target="${key}"]`);

        if (!target || editor.dataset.richInitialized === '1') {
            return;
        }

        editor.innerHTML = target.value || editor.innerHTML || '';
        editor.dataset.richInitialized = '1';

        const sync = () => {
            target.value = editor.innerHTML.trim();
        };

        editor.addEventListener('input', sync);
        sync();
    });

    document.querySelectorAll('[data-rich-command]').forEach((button) => {
        if (button.dataset.richBound === '1') {
            return;
        }

        button.dataset.richBound = '1';
        button.addEventListener('click', () => {
            const shell = button.closest('.rich-editor-shell');
            const editor = shell ? shell.querySelector('[data-rich-editor]') : null;

            if (!editor) {
                return;
            }

            editor.focus();

            const command = button.getAttribute('data-rich-command');
            let value = button.getAttribute('data-rich-value');

            if (command === 'createLink') {
                value = window.prompt('Informe a URL do link');
                if (!value) {
                    return;
                }
            }

            document.execCommand(command, false, value);
            editor.dispatchEvent(new Event('input'));
        });
    });
}

document.addEventListener('DOMContentLoaded', initExamFormRichEditors);
</script>
@endpush

@push('styles')
<style>
    @media (max-width: 1100px) {
        .provas-form-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 720px) {
        .provas-form-triplet,
        .provas-form-dates {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
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

function provaMoodleCourses() {
    return {
        courses: [],
        loading: false,
        error: '',
        currentCourseId: {{ $moodleCourseId ?: 'null' }},
        init() {},
        async fetchCourses() {
            this.loading = true;
            this.error = '';
            const systemId = document.getElementById('client_system_id')?.value;
            if (!systemId) {
                this.error = 'Selecione um sistema primeiro.';
                this.loading = false;
                return;
            }
            try {
                const resp = await fetch('{{ route("sistemas.moodle-courses") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ system_id: systemId }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.courses = data.courses;
                } else {
                    this.error = data.message || 'Erro ao buscar cursos.';
                }
            } catch (e) {
                this.error = 'Falha: ' + e.message;
            }
            this.loading = false;
        },
    };
}
</script>
@endpush
