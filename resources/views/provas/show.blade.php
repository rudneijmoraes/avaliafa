@extends('layouts.app')

@php
    $statusMap = [
        'draft' => ['badge-neutral', 'Rascunho'],
        'published' => ['badge-warning', 'Publicada'],
        'active' => ['badge-success', 'Ativa'],
        'closed' => ['badge-danger', 'Encerrada'],
        'archived' => ['badge-neutral', 'Arquivada'],
    ];
    [$statusClass, $statusLabel] = $statusMap[$exam->status] ?? ['badge-neutral', ucfirst($exam->status)];
    $approvalRate = $sessionsStats['graded'] > 0 ? round(($sessionsStats['passed'] / $sessionsStats['graded']) * 100, 1) : 0;
    $attachQuestionErrors = $errors->getBag('attachQuestion');
    $blockStoreErrors = $errors->getBag('blockStore');
    $blockUpdateErrors = $errors->getBag('blockUpdate');
    $generateLinkErrors = $errors->getBag('generateLink');
    $generatedLink = session('generated_link');
    $shouldOpenLinkModal = $generateLinkErrors->any() || filled($generatedLink);
    $moodleLaunchUrl = route('exam.moodle-launch', $exam);
    $selectedBlockId = old('exam_block_id', $defaultBlockId);
    $selectedQuestionOption = $selectedQuestionOption ?? null;
@endphp

@section('title', $exam->title . ' — AvaliaFA')
@section('page-title', 'Detalhes da Prova')

@section('topbar-actions')
<span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
@endsection

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:880px">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px">
                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                @if($exam->clientSystem)
                <span class="badge badge-primary">{{ $exam->clientSystem->name }}</span>
                @endif
                <span class="badge badge-neutral" style="font-family:'JetBrains Mono',monospace">{{ $exam->duration_minutes }}min</span>
            </div>
            <h1 class="page-title" style="margin-bottom:6px">{{ $exam->title }}</h1>
            <p class="page-subtitle" style="max-width:760px;line-height:1.6">
                {{ $exam->description ?: 'Esta prova ainda não possui descrição detalhada.' }}
            </p>
            @if($errors->has('publish'))
            <div class="alert alert-danger" style="margin-top:14px;max-width:680px">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ $errors->first('publish') }}
            </div>
            @endif
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
            <a href="{{ route('provas.edit', $exam) }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>

            @if($exam->status !== 'active')
            <form method="POST" action="{{ route('provas.publish', $exam) }}">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Publicar
                </button>
            </form>
            @endif

            @if($exam->status !== 'archived')
            <form method="POST" action="{{ route('provas.archive', $exam) }}">
                @csrf
                <button type="submit" class="btn btn-ghost">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    Arquivar
                </button>
            </form>
            @endif

            <a href="{{ route('demo.exam', $exam) }}" class="btn btn-ghost"
               onclick="return confirm('Iniciar modo demonstração? A prova abrirá com SecureExamEngine completo, mas nenhum dado será registrado oficialmente.')">
                🧪 Demonstração
            </a>

            <a href="{{ route('monitor.exam', $exam->id) }}" class="btn btn-ghost">
                <span class="live-dot"></span>
                Monitor
            </a>

            <form method="POST" action="{{ route('provas.destroy', $exam) }}" onsubmit="return confirmDelete(this, {title:'Remover prova', message:'Deseja remover esta prova? Os dados serão preservados (soft delete).'})">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    Deletar
                </button>
            </form>
        </div>
    </div>
</div>

<div class="provas-show-kpis" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px">
    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(37,99,235,0.12);color:#2563EB">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11H3v10h6V11zM15 3H9v18h6V3zM21 7h-6v14h6V7z"/></svg>
        </div>
        <div class="kpi-label">Total de sessões</div>
        <div class="kpi-value">{{ $sessionsStats['total'] }}</div>
        <div class="kpi-badge kpi-badge-up">Aplicações registradas</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(245,158,11,0.14);color:#F59E0B">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="kpi-label">Em andamento</div>
        <div class="kpi-value">{{ $sessionsStats['active'] }}</div>
        <div class="kpi-badge" style="background:var(--color-warning-bg);color:var(--color-warning)">Monitoramento ativo</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(16,185,129,0.12);color:#10B981">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="kpi-label">Aprovados</div>
        <div class="kpi-value">{{ $sessionsStats['passed'] }}</div>
        <div class="kpi-badge kpi-badge-up">Com nota suficiente</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-7"/></svg>
        </div>
        <div class="kpi-label">Taxa de aprovação</div>
        <div class="kpi-value">{{ number_format($approvalRate, 1, ',', '.') }}%</div>
        <div class="kpi-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6">Base: {{ $sessionsStats['graded'] }} corrigidas</div>
    </div>
</div>

<div class="provas-show-grid" style="display:grid;grid-template-columns:minmax(0,1.4fr) minmax(320px,0.9fr);gap:20px;align-items:start">
    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Blocos de questões</div>
                    <div class="card-subtitle">Organize textos-base e grupos de questões dentro da mesma prova.</div>
                </div>
                <span class="badge badge-primary">{{ $exam->blocks_count }} bloco(s)</span>
            </div>
            <div class="card-body" style="padding-top:0">
                <form method="POST" action="{{ route('provas.blocks.store', $exam) }}" style="display:grid;gap:12px">
                    @csrf
                    <div class="provas-show-block-form-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 120px auto;gap:12px;align-items:end">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Título do bloco</label>
                            <input type="text" name="title" maxlength="120" class="input {{ $blockStoreErrors->has('title') ? 'input-error' : '' }}" value="{{ old('title') }}" placeholder="Ex.: Texto 1">
                            @if($blockStoreErrors->has('title'))
                            <div class="form-error">{{ $blockStoreErrors->first('title') }}</div>
                            @endif
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Ordem</label>
                            <input type="number" name="order" min="1" max="999" class="input {{ $blockStoreErrors->has('order') ? 'input-error' : '' }}" value="{{ old('order', $nextBlockOrder) }}">
                            @if($blockStoreErrors->has('order'))
                            <div class="form-error">{{ $blockStoreErrors->first('order') }}</div>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-secondary" style="height:44px">Criar bloco</button>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Texto-base</label>
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
                            <div class="rich-editor-input" contenteditable="true" data-rich-editor="block-store-editor">{!! old('base_text') !!}</div>
                            <textarea name="base_text" data-rich-target="block-store-editor" style="display:none">{{ old('base_text') }}</textarea>
                        </div>
                        @if($blockStoreErrors->has('base_text'))
                        <div class="form-error">{{ $blockStoreErrors->first('base_text') }}</div>
                        @endif
                    </div>
                </form>
            </div>
            <div class="card-body" style="padding-top:0;display:grid;gap:14px">
                @forelse($exam->blocks as $block)
                <details class="provas-block-details" {{ $blockUpdateErrors->any() ? 'open' : '' }}>
                    <summary class="provas-block-summary">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="badge badge-primary">Bloco {{ $block->order }}</span>
                            <span style="font-weight:700;color:var(--text-primary)">{{ $block->title ?: 'Sem título' }}</span>
                            <span style="font-size:0.78rem;color:var(--text-muted)">{{ $block->exam_questions_count }} questão(ões)</span>
                        </div>
                        <span style="font-size:0.78rem;color:var(--text-muted)">Expandir</span>
                    </summary>
                    <div class="provas-block-content">
                        <form method="POST" action="{{ route('provas.blocks.update', [$exam, $block]) }}" style="display:grid;gap:12px">
                            @csrf
                            @method('PUT')
                            <div class="provas-show-block-form-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 120px;gap:12px;align-items:end">
                                <div class="form-group" style="margin-bottom:0">
                                    <label class="form-label">Título do bloco</label>
                                    <input type="text" name="title" maxlength="120" class="input" value="{{ old('title', $block->title) }}" placeholder="Ex.: Texto 1">
                                </div>
                                <div class="form-group" style="margin-bottom:0">
                                    <label class="form-label">Ordem</label>
                                    <input type="number" name="order" min="1" max="999" class="input" value="{{ old('order', $block->order) }}">
                                </div>
                            </div>
                            <div class="form-group" style="margin-bottom:0">
                                <label class="form-label">Texto-base</label>
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
                                    <div class="rich-editor-input" contenteditable="true" data-rich-editor="block-editor-{{ $block->id }}">{!! old('base_text', $block->base_text) !!}</div>
                                    <textarea name="base_text" data-rich-target="block-editor-{{ $block->id }}" style="display:none">{{ old('base_text', $block->base_text) }}</textarea>
                                </div>
                            </div>
                            <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap">
                                <div style="font-size:0.78rem;color:var(--text-muted)">As questões vinculadas a este bloco aparecem logo abaixo do texto-base para o aluno.</div>
                                <div style="display:flex;gap:8px;flex-wrap:wrap">
                                    <button type="submit" class="btn btn-primary btn-sm">Salvar bloco</button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('provas.blocks.destroy', [$exam, $block]) }}" onsubmit="return confirmDelete(this, {title:'Excluir bloco', message:'Deseja remover este bloco? Ele precisa estar sem questões vinculadas.'})" style="margin-top:10px">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">Excluir bloco</button>
                        </form>
                    </div>
                </details>
                @empty
                <div style="padding:20px;border:1px dashed var(--surface-border);border-radius:12px;text-align:center;color:var(--text-muted)">
                    Nenhum bloco cadastrado ainda.
                </div>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Questões vinculadas</div>
                    <div class="card-subtitle">Estrutura atual do caderno, ordem aplicada e pesos por questão.</div>
                </div>
                <span class="badge badge-primary">{{ $exam->questions_count }} questão(ões)</span>
            </div>
            <div class="card-body" style="padding-top:0">
                <form method="POST" action="{{ route('provas.questions.store', $exam) }}" class="provas-show-attach-grid" style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(180px,0.9fr) 110px 110px auto;gap:12px;align-items:end">
                    @csrf
                    <div class="form-group provas-live-question-search" style="margin-bottom:0;position:relative"
                         data-search-url="{{ route('provas.questions.search', $exam) }}"
                         data-selected-id="{{ old('question_id', data_get($selectedQuestionOption, 'id', '')) }}"
                         data-selected-label="{{ old('question_label', data_get($selectedQuestionOption, 'label', '')) }}"
                         data-selected-snippet="{{ data_get($selectedQuestionOption, 'snippet', '') }}">
                        <label class="form-label">Adicionar questão</label>
                        <input type="hidden" name="question_id" value="{{ old('question_id', data_get($selectedQuestionOption, 'id', '')) }}">
                        <input type="hidden" name="question_label" value="{{ old('question_label', data_get($selectedQuestionOption, 'label', '')) }}">
                        <input
                            type="text"
                            class="input provas-live-question-search-input {{ $attachQuestionErrors->has('question_id') ? 'input-error' : '' }}"
                            value="{{ old('question_label', data_get($selectedQuestionOption, 'label', '')) }}"
                            placeholder="Digite ID, trecho do enunciado, feedback ou tag"
                            autocomplete="off"
                        >
                        <div class="provas-live-question-search-results" hidden></div>
                        <div class="provas-live-question-search-preview" @if(! filled(data_get($selectedQuestionOption, 'snippet'))) hidden @endif>
                            {{ data_get($selectedQuestionOption, 'snippet') }}
                        </div>
                        @if($attachQuestionErrors->has('question_id'))
                        <div class="form-error">{{ $attachQuestionErrors->first('question_id') }}</div>
                        @endif
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Bloco</label>
                        <select name="exam_block_id" class="input {{ $attachQuestionErrors->has('exam_block_id') ? 'input-error' : '' }}">
                            <option value="">Selecione um bloco</option>
                            @foreach($exam->blocks as $block)
                            <option value="{{ $block->id }}" {{ (string) $selectedBlockId === (string) $block->id ? 'selected' : '' }}>{{ $block->order }} · {{ $block->title ?: 'Sem título' }}</option>
                            @endforeach
                        </select>
                        @if($attachQuestionErrors->has('exam_block_id'))
                        <div class="form-error">{{ $attachQuestionErrors->first('exam_block_id') }}</div>
                        @endif
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Ordem</label>
                        <input type="number" name="order" min="1" max="999" class="input {{ $attachQuestionErrors->has('order') ? 'input-error' : '' }}" value="{{ old('order', $exam->questions_count + 1) }}">
                        @if($attachQuestionErrors->has('order'))
                        <div class="form-error">{{ $attachQuestionErrors->first('order') }}</div>
                        @endif
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Peso</label>
                        <input type="number" name="weight" min="0.01" step="0.01" class="input {{ $attachQuestionErrors->has('weight') ? 'input-error' : '' }}" value="{{ old('weight', '1.00') }}">
                        @if($attachQuestionErrors->has('weight'))
                        <div class="form-error">{{ $attachQuestionErrors->first('weight') }}</div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-secondary" style="height:44px">Vincular</button>
                </form>
                <div style="margin-top:12px;font-size:0.8rem;color:var(--text-muted)">
                    Digite para buscar em tempo real entre as questões disponíveis e selecione uma opção da lista.
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bloco</th>
                            <th>Enunciado</th>
                            <th>Tipo</th>
                            <th>Peso</th>
                            <th>Alternativas</th>
                            <th style="text-align:right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exam->questions as $index => $question)
                        <tr>
                            <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-secondary)">{{ $question->pivot->order ?: $index + 1 }}</td>
                            <td style="color:var(--text-secondary)">
                                @php
                                    $questionBlock = $exam->blocks->firstWhere('id', $question->pivot->exam_block_id);
                                @endphp
                                <div style="font-size:0.78rem;font-weight:700;color:var(--text-primary)">{{ $questionBlock?->title ?: 'Sem bloco' }}</div>
                                <div style="font-size:0.72rem;color:var(--text-muted)">#{{ $question->pivot->exam_block_id ?: '—' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600;color:var(--text-primary);max-width:560px">{{ \Illuminate\Support\Str::limit(strip_tags($question->content), 120) }}</div>
                                @if($question->difficulty)
                                <div style="margin-top:5px">
                                    <span class="badge {{ match($question->difficulty) { 'easy' => 'badge-success', 'medium' => 'badge-warning', 'hard' => 'badge-danger', default => 'badge-neutral' } }}">
                                        {{ match($question->difficulty) { 'easy' => 'Fácil', 'medium' => 'Média', 'hard' => 'Difícil', default => ucfirst($question->difficulty) } }}
                                    </span>
                                </div>
                                @endif
                            </td>
                            <td style="color:var(--text-secondary)">{{ match($question->type) { 'multiple_choice' => 'Múltipla escolha', 'true_false' => 'Verdadeiro/Falso', 'multiple_answer' => 'Múltiplas respostas', 'essay' => 'Dissertativa', 'ordering' => 'Ordenação', default => ucfirst($question->type) } }}</td>
                            <td style="font-family:'JetBrains Mono',monospace">{{ number_format((float) $question->pivot->weight, 2, ',', '.') }}</td>
                            <td style="color:var(--text-secondary)">{{ $question->choices->count() }}</td>
                            <td>
                                <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap">
                                    <form method="POST" action="{{ route('provas.questions.store', $exam) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                                        @csrf
                                        <input type="hidden" name="question_id" value="{{ $question->id }}">
                                        <select name="exam_block_id" class="input" style="width:150px;height:36px;padding:6px 10px">
                                            @foreach($exam->blocks as $block)
                                            <option value="{{ $block->id }}" {{ (string) $question->pivot->exam_block_id === (string) $block->id ? 'selected' : '' }}>{{ $block->order }} · {{ $block->title ?: 'Sem título' }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="order" min="1" max="999" class="input" value="{{ $question->pivot->order ?: $index + 1 }}" style="width:82px;height:36px;padding:6px 10px">
                                        <input type="number" name="weight" min="0.01" step="0.01" class="input" value="{{ number_format((float) $question->pivot->weight, 2, '.', '') }}" style="width:92px;height:36px;padding:6px 10px">
                                        <button type="submit" class="btn btn-ghost btn-sm">Atualizar</button>
                                    </form>
                                    <form method="POST" action="{{ route('provas.questions.destroy', [$exam, $question]) }}" onsubmit="return confirmDelete(this, {title:'Remover questão', message:'Deseja remover esta questão da prova?'})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Remover</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" style="padding:42px 16px;text-align:center">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.28"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                    <div style="font-size:0.92rem;font-weight:700;color:var(--text-primary)">Nenhuma questão vinculada</div>
                                    <div style="font-size:0.8rem;color:var(--text-muted)">Use o formulário acima para montar o caderno desta prova com ordem e peso individual.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Acesso do aluno</div>
                    <div class="card-subtitle">Gere uma sessão pendente e o deep link JWT para envio ao estudante.</div>
                </div>
            </div>
            <div class="card-body" x-data="{ modalOpen: {{ $shouldOpenLinkModal ? 'true' : 'false' }} }">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
                    <div style="max-width:620px">
                        <div style="font-size:0.92rem;font-weight:700;color:var(--text-primary);margin-bottom:6px">Gerar link da prova</div>
                        <div style="font-size:0.82rem;color:var(--text-secondary);line-height:1.6">Informe o CPF ou ID do estudante, escolha a tentativa e gere o link de acesso sem sair desta tela.</div>
                    </div>
                    <button type="button" class="btn btn-secondary" @click="modalOpen = true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 10l4.55-2.27A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-1.45.89L15 14"/><rect x="3" y="6" width="12" height="12" rx="2" ry="2"/></svg>
                        Gerar link
                    </button>
                </div>

                <div x-show="modalOpen" x-transition.opacity style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.45);z-index:120;padding:20px">
                    <div style="max-width:560px;margin:6vh auto 0;background:var(--surface-card);border:1px solid var(--surface-border);border-radius:18px;box-shadow:0 16px 40px rgba(15,23,42,0.22);overflow:hidden">
                        <div class="card-header" style="border-bottom:1px solid var(--surface-border)">
                            <div>
                                <div class="card-title">Gerar link da prova</div>
                                <div class="card-subtitle">Crie ou reutilize uma sessão pendente para o estudante informado.</div>
                            </div>
                            <button type="button" class="btn-icon" @click="modalOpen = false">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        <div class="card-body">
                            @if($generateLinkErrors->any())
                            <div class="alert alert-danger" style="margin-bottom:18px">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                {{ $generateLinkErrors->first() }}
                            </div>
                            @endif

                            <form method="POST" action="{{ route('provas.generate-link', $exam) }}">
                                @csrf
                                <datalist id="students-list-{{ $exam->id }}">
                                    @foreach($eligibleStudents as $student)
                                    <option value="{{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}">{{ $student->name }} · ID {{ $student->id }}</option>
                                    <option value="{{ $student->id }}">{{ $student->name }} · CPF {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}</option>
                                    @endforeach
                                </datalist>

                                <div class="provas-show-modal-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                                    <div class="form-group" style="margin-bottom:0">
                                        <label class="form-label">CPF ou ID do estudante</label>
                                        <input type="text" name="student_reference" list="students-list-{{ $exam->id }}" class="input {{ $generateLinkErrors->has('student_reference') ? 'input-error' : '' }}" placeholder="000.000.000-00 ou ID" value="{{ old('student_reference', session('generated_student_reference')) }}">
                                        @if($generateLinkErrors->has('student_reference'))
                                        <div class="form-error">{{ $generateLinkErrors->first('student_reference') }}</div>
                                        @endif
                                    </div>
                                    <div class="form-group" style="margin-bottom:0">
                                        <label class="form-label">Tentativa</label>
                                        <input type="number" name="attempt_number" min="1" max="99" class="input {{ $generateLinkErrors->has('attempt_number') ? 'input-error' : '' }}" value="{{ old('attempt_number', session('generated_attempt_number', 1)) }}">
                                        @if($generateLinkErrors->has('attempt_number'))
                                        <div class="form-error">{{ $generateLinkErrors->first('attempt_number') }}</div>
                                        @endif
                                    </div>
                                </div>

                                <label style="display:flex;align-items:center;gap:10px;margin-top:14px;padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg);cursor:pointer">
                                    <input type="checkbox" name="is_simulation" value="1" {{ old('is_simulation', session('generated_is_simulation')) ? 'checked' : '' }}>
                                    <span style="font-size:0.84rem;color:var(--text-primary)">Gerar como simulação (sem Moodle, sem certificado e fora dos relatórios principais)</span>
                                </label>

                                <div class="form-group" style="margin-top:14px;margin-bottom:0">
                                    <label class="form-label">Link gerado</label>
                                    <textarea id="generated-link-{{ $exam->id }}" class="input" rows="4" readonly style="resize:none">{{ $generatedLink }}</textarea>
                                </div>

                                @if($generatedLink)
                                <div style="margin-top:10px;padding:12px 14px;border:1px solid rgba(16,185,129,0.2);border-radius:12px;background:rgba(16,185,129,0.08)">
                                    <div style="font-size:0.82rem;font-weight:700;color:#047857">Sessão #{{ session('generated_session_id') }} pronta para envio</div>
                                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">
                                        Estudante: {{ session('generated_student_name') }} · Tentativa {{ session('generated_attempt_number') }}
                                    </div>
                                </div>
                                @endif

                                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:18px;flex-wrap:wrap">
                                    @if($generatedLink)
                                    <button type="button" class="btn btn-secondary" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('generated-link-{{ $exam->id }}').value)">Copiar link</button>
                                    @endif
                                    <button type="button" class="btn btn-ghost" @click="modalOpen = false">Fechar</button>
                                    <button type="submit" class="btn btn-primary">Gerar link</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if($exam->hasMoodleConfig() && $exam->isPublished())
        <section class="card" x-data="bulkMoodleLinks()">
            <div class="card-header">
                <div>
                    <div class="card-title">Gerar links para turma (Moodle)</div>
                    <div class="card-subtitle">Busca alunos matriculados no curso Moodle vinculado e gera links em lote.</div>
                </div>
                <button type="button" class="btn btn-secondary" @click="generate()" :disabled="loading">
                    <template x-if="loading">
                        <span style="display:flex;align-items:center;gap:6px">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                            Buscando no Moodle...
                        </span>
                    </template>
                    <template x-if="!loading">
                        <span style="display:flex;align-items:center;gap:6px">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            Gerar links da turma
                        </span>
                    </template>
                </button>
            </div>
            <div class="card-body">
                <div style="margin-bottom:14px">
                    <div style="font-size:0.84rem;color:var(--text-secondary);line-height:1.6;margin-bottom:12px">
                        Selecione os cursos do Moodle para buscar alunos matriculados e gerar links de acesso.
                    </div>
                    <div style="margin-bottom:14px;padding:14px;border:1px solid rgba(37,99,235,0.16);border-radius:12px;background:rgba(37,99,235,0.06)">
                        <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary);margin-bottom:6px">URL única para atividade no Moodle</div>
                        <div style="font-size:0.78rem;color:var(--text-secondary);line-height:1.6;margin-bottom:10px">
                            Cole esta URL na atividade do Moodle para abrir a prova no AvaliaFA.
                        </div>
                        <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                            <textarea id="moodle-launch-url-{{ $exam->id }}" class="input" rows="2" readonly style="flex:1;min-width:260px;resize:none">{{ route('exam.moodle-launch', $exam) }}</textarea>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('moodle-launch-url-{{ $exam->id }}').value)">
                                Copiar URL
                            </button>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:flex-start;flex-wrap:wrap">
                        <div style="flex:1;min-width:260px">
                            <div style="display:flex;gap:8px;margin-bottom:8px">
                                <button type="button" class="btn btn-secondary" style="padding:6px 12px;font-size:0.78rem;white-space:nowrap" @click="fetchCourses()" :disabled="loadingCourses">
                                    <span x-show="!loadingCourses">Carregar cursos do Moodle</span>
                                    <span x-show="loadingCourses">Buscando...</span>
                                </button>
                                <span x-show="moodleCourses.length > 0" style="font-size:0.78rem;color:var(--color-success);display:flex;align-items:center" x-text="moodleCourses.length + ' cursos'"></span>
                            </div>
                            <div x-show="courseError" style="color:var(--color-danger);font-size:0.78rem;margin-bottom:8px" x-text="courseError"></div>
                            <div x-show="moodleCourses.length > 0" style="max-height:300px;overflow-y:auto;border:1px solid var(--surface-border);border-radius:10px">
                                <template x-for="(c, idx) in moodleCourses" :key="c.id">
                                    <label style="display:flex;align-items:center;gap:10px;padding:9px 14px;cursor:pointer;font-size:0.84rem;border-bottom:1px solid var(--surface-border);transition:background 0.12s"
                                           :style="(selectedCourseIds.includes(c.id) ? 'background:var(--color-primary-50);' : 'background:var(--surface-card);') + (idx === moodleCourses.length - 1 ? 'border-bottom:none;' : '')">
                                        <input type="checkbox" :value="c.id" @change="toggleCourse(c.id)" :checked="selectedCourseIds.includes(c.id)" style="accent-color:var(--color-primary-600);width:16px;height:16px;flex-shrink:0">
                                        <span style="color:var(--text-primary);font-weight:600" x-text="c.fullname"></span>
                                        <span style="font-size:0.72rem;color:var(--text-muted);margin-left:auto;white-space:nowrap" x-text="c.shortname"></span>
                                    </label>
                                </template>
                            </div>
                            <div x-show="moodleCourses.length === 0 && !loadingCourses" style="font-size:0.8rem;color:var(--text-muted);padding:12px;border:1px dashed var(--surface-border);border-radius:10px;text-align:center">
                                Clique em "Carregar cursos" para listar os cursos disponíveis no Moodle.
                            </div>
                        </div>
                    </div>
                </div>

                <template x-if="error">
                    <div class="alert alert-danger" style="margin-bottom:14px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span x-text="error"></span>
                    </div>
                </template>

                <template x-if="generated">
                    <div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;background:var(--surface-bg);color:var(--text-secondary);border:1px solid var(--surface-border)">
                                <span x-text="summary.total"></span> encontrados
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;background:rgba(16,185,129,0.1);color:#047857;border:1px solid rgba(16,185,129,0.2)">
                                <span x-text="summary.generated"></span> links gerados
                            </span>
                            <template x-if="summary.auto_created > 0">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;background:rgba(59,130,246,0.1);color:#1d4ed8;border:1px solid rgba(59,130,246,0.2)">
                                    <span x-text="summary.auto_created"></span> auto-criados
                                </span>
                            </template>
                            <template x-if="summary.already_active > 0">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;background:rgba(245,158,11,0.1);color:#b45309;border:1px solid rgba(245,158,11,0.2)">
                                    <span x-text="summary.already_active"></span> já ativos
                                </span>
                            </template>
                            <template x-if="summary.errors > 0">
                                <span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:8px;font-size:0.78rem;font-weight:600;background:rgba(239,68,68,0.1);color:#dc2626;border:1px solid rgba(239,68,68,0.2)">
                                    <span x-text="summary.errors"></span> erros
                                </span>
                            </template>
                        </div>

                        <div style="display:flex;gap:8px;margin-bottom:14px">
                            <button type="button" class="btn btn-secondary btn-sm" @click="copyAll()" style="font-size:0.8rem">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                Copiar todos os links
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm" @click="exportCsv()" style="font-size:0.8rem">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                Exportar CSV
                            </button>
                        </div>

                        <div style="max-height:420px;overflow-y:auto;border:1px solid var(--surface-border);border-radius:12px">
                            <table class="data-table" style="margin:0">
                                <thead>
                                    <tr>
                                        <th style="width:36px">#</th>
                                        <th>Estudante</th>
                                        <th style="width:110px">Status</th>
                                        <th style="width:90px;text-align:center">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(r, idx) in results" :key="r.moodle_user_id">
                                        <tr>
                                            <td style="font-size:0.78rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace" x-text="idx + 1"></td>
                                            <td>
                                                <div style="font-weight:600;font-size:0.84rem;color:var(--text-primary)" x-text="r.student_name || r.moodle_name"></div>
                                                <div style="font-size:0.76rem;color:var(--text-muted)" x-text="r.moodle_email"></div>
                                            </td>
                                            <td>
                                                <span x-show="r.status === 'link_generated'" style="display:inline-flex;padding:3px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(16,185,129,0.1);color:#047857">Gerado</span>
                                                <span x-show="r.status === 'created'" style="display:inline-flex;padding:3px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(59,130,246,0.1);color:#1d4ed8">Criado</span>
                                                <span x-show="r.status === 'already_active'" style="display:inline-flex;padding:3px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(245,158,11,0.1);color:#b45309">Já ativo</span>
                                                <span x-show="r.status === 'error'" style="display:inline-flex;padding:3px 8px;border-radius:6px;font-size:0.72rem;font-weight:600;background:rgba(239,68,68,0.1);color:#dc2626" x-text="'Erro'" :title="r.message"></span>
                                            </td>
                                            <td style="text-align:center">
                                                <button x-show="r.link" type="button" class="btn btn-ghost btn-sm" @click="copyOne(r.link)" style="font-size:0.76rem;padding:4px 10px">
                                                    Copiar
                                                </button>
                                                <span x-show="!r.link" style="font-size:0.76rem;color:var(--text-muted)">—</span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>
        </section>
        @endif
    </div>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Configurações da prova</div>
                    <div class="card-subtitle">Parâmetros operacionais e de segurança.</div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:14px">
                    <div style="display:flex;justify-content:space-between;gap:14px;font-size:0.84rem"><span style="color:var(--text-secondary)">Duração</span><span style="font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $exam->duration_minutes }} min</span></div>
                    <div style="display:flex;justify-content:space-between;gap:14px;font-size:0.84rem"><span style="color:var(--text-secondary)">Pontuação mínima</span><span style="font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ number_format((float) $exam->passing_score, 2, ',', '.') }}</span></div>
                    <div style="display:flex;justify-content:space-between;gap:14px;font-size:0.84rem"><span style="color:var(--text-secondary)">Violações máximas</span><span style="font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $exam->max_violations }}</span></div>
                    <div style="display:flex;justify-content:space-between;gap:14px;font-size:0.84rem">
                        <span style="color:var(--text-secondary)">Período</span>
                        <span style="text-align:right;font-weight:700;color:var(--text-primary)">
                            {{ $exam->starts_at?->format('d/m/Y H:i') ?? 'Sem início' }}<br>
                            <span style="font-weight:500;color:var(--text-muted)">até {{ $exam->ends_at?->format('d/m/Y H:i') ?? 'indefinido' }}</span>
                        </span>
                    </div>
                </div>

                <hr class="divider">

                <div style="display:grid;gap:10px">
                    @php
                        $flags = [
                            ['label' => 'Webcam habilitada', 'value' => $exam->webcam_enabled],
                            ['label' => 'Embaralhar questões', 'value' => $exam->shuffle_questions],
                            ['label' => 'Embaralhar alternativas', 'value' => $exam->shuffle_choices],
                        ];
                    @endphp
                    @foreach($flags as $flag)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <span style="font-size:0.84rem;color:var(--text-secondary)">{{ $flag['label'] }}</span>
                        <span class="badge {{ $flag['value'] ? 'badge-success' : 'badge-neutral' }}">{{ $flag['value'] ? 'Sim' : 'Não' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
 
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Metadados</div>
                    <div class="card-subtitle">Origem e trilha operacional do registro.</div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:14px">
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Criador</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $exam->creator?->name ?? 'Não informado' }}</div>
                        <div style="font-size:0.78rem;color:var(--text-secondary)">{{ $exam->creator?->email ?? 'Sem email cadastrado' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Criada em</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $exam->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Última atualização</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $exam->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Versão vigente</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $exam->latestVersion?->version_number ? 'v' . $exam->latestVersion->version_number : 'Ainda não publicada' }}</div>
                        <div style="font-size:0.78rem;color:var(--text-secondary)">{{ $exam->latestVersion?->published_at?->format('d/m/Y H:i') ?? 'Snapshot será gerado na publicação.' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Próximos encaixes</div>
                    <div class="card-subtitle">Itens previstos na documentação e o estado atual de cada um.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Vincular questões</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Adicionar, remover e reajustar peso e ordem das questões já está disponível nesta tela.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Gerar link JWT</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">O modal agora cria sessão pendente e gera o deep link via `SessionTokenService`.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Versão imutável</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Cada publicação gera um snapshot em `exam_versions` para preservar o estado aplicado.</div>
                </div>
            </div>
        </section>
    </div>
</div>

@endsection

@push('styles')
<style>
    @media (max-width: 1180px) {
        .provas-show-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .provas-show-grid {
            grid-template-columns: 1fr !important;
        }

        .provas-show-search-grid,
        .provas-show-block-form-grid,
        .provas-show-attach-grid {
            grid-template-columns: 1fr 1fr !important;
        }
    }

    @media (max-width: 720px) {
        .provas-show-kpis,
        .provas-show-modal-grid,
        .provas-show-search-grid,
        .provas-show-block-form-grid,
        .provas-show-attach-grid {
            grid-template-columns: 1fr !important;
        }
    }

    .provas-block-details {
        border: 1px solid var(--surface-border);
        border-radius: 14px;
        background: var(--surface-bg);
    }

    .provas-block-summary {
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 16px;
        cursor: pointer;
    }

    .provas-block-summary::-webkit-details-marker {
        display: none;
    }

    .provas-block-content {
        padding: 0 16px 16px;
        border-top: 1px solid var(--surface-border);
    }

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
        content: 'Digite o texto-base do bloco aqui...';
        color: var(--text-muted);
    }

    .provas-live-question-search-results {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        z-index: 30;
        max-height: 320px;
        overflow-y: auto;
        border: 1px solid var(--surface-border);
        border-radius: 14px;
        background: var(--surface-card);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.16);
        padding: 8px;
    }

    .provas-live-question-search-option {
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 10px 12px;
        border-radius: 10px;
        cursor: pointer;
        display: grid;
        gap: 4px;
    }

    .provas-live-question-search-option:hover,
    .provas-live-question-search-option.is-active {
        background: var(--surface-bg);
    }

    .provas-live-question-search-option-title {
        font-size: 0.82rem;
        font-weight: 700;
        color: var(--text-primary);
    }

    .provas-live-question-search-option-snippet {
        font-size: 0.76rem;
        color: var(--text-secondary);
        line-height: 1.5;
    }

    .provas-live-question-search-preview {
        margin-top: 8px;
        padding: 10px 12px;
        border: 1px solid var(--surface-border);
        border-radius: 12px;
        background: var(--surface-bg);
        font-size: 0.76rem;
        color: var(--text-secondary);
        line-height: 1.55;
    }
</style>
@endpush

@push('scripts')
<script>
function bulkMoodleLinks() {
    return {
        loading: false,
        generated: false,
        error: null,
        results: [],
        summary: {},
        moodleCourses: [],
        selectedCourseIds: [],
        loadingCourses: false,
        courseError: '',
        toggleCourse(id) {
            const idx = this.selectedCourseIds.indexOf(id);
            if (idx >= 0) this.selectedCourseIds.splice(idx, 1);
            else this.selectedCourseIds.push(id);
        },
        async fetchCourses() {
            this.loadingCourses = true;
            this.courseError = '';
            try {
                const resp = await fetch('{{ route("sistemas.moodle-courses") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ system_id: {{ $exam->client_system_id }} }),
                });
                const data = await resp.json();
                if (data.success) {
                    this.moodleCourses = data.courses;
                    // Pre-select the exam's configured course
                    const examCourseId = {{ data_get($exam->settings, 'moodle.course_id') ?: ($exam->clientSystem?->moodle_config['course_id'] ?? 0) }};
                    if (examCourseId > 0 && !this.selectedCourseIds.includes(examCourseId)) {
                        this.selectedCourseIds.push(examCourseId);
                    }
                } else {
                    this.courseError = data.message || 'Erro ao buscar cursos.';
                }
            } catch (e) {
                this.courseError = 'Falha: ' + e.message;
            }
            this.loadingCourses = false;
        },
        async generate() {
            this.loading = true;
            this.error = null;
            this.results = [];
            this.generated = false;
            if (this.selectedCourseIds.length === 0 && this.moodleCourses.length > 0) {
                this.error = 'Selecione ao menos um curso.';
                this.loading = false;
                return;
            }
            try {
                const res = await fetch('{{ route("provas.generate-bulk-links", $exam) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ extra_course_ids: this.selectedCourseIds }),
                });
                const json = await res.json();
                if (!json.success) {
                    this.error = json.error || 'Erro desconhecido.';
                } else {
                    this.results = json.data;
                    this.summary = json.summary;
                    this.generated = true;
                }
            } catch (e) {
                this.error = 'Falha de conexão: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
        copyOne(link) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link);
            }
        },
        copyAll() {
            const lines = this.results.filter(r => r.link).map(r => r.student_name + '\t' + r.link).join('\n');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(lines);
            }
        },
        exportCsv() {
            const header = 'nome,email,moodle_id,status,link\n';
            const rows = this.results.map(r => {
                const name = (r.student_name || r.moodle_name || '').replace(/"/g, '""');
                const email = (r.moodle_email || '').replace(/"/g, '""');
                const link = (r.link || '').replace(/"/g, '""');
                return '"' + name + '","' + email + '",' + r.moodle_user_id + ',' + r.status + ',"' + link + '"';
            }).join('\n');
            const blob = new Blob(['\uFEFF' + header + rows], { type: 'text/csv;charset=utf-8' });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'links-moodle-prova-{{ $exam->id }}.csv';
            a.click();
            URL.revokeObjectURL(a.href);
        }
    };
}

function initRichEditors() {
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

function initQuestionLiveSearch() {
    document.querySelectorAll('.provas-live-question-search').forEach((wrapper) => {
        if (wrapper.dataset.initialized === '1') {
            return;
        }

        wrapper.dataset.initialized = '1';

        const endpoint = wrapper.dataset.searchUrl;
        const input = wrapper.querySelector('.provas-live-question-search-input');
        const hiddenId = wrapper.querySelector('input[name="question_id"]');
        const hiddenLabel = wrapper.querySelector('input[name="question_label"]');
        const results = wrapper.querySelector('.provas-live-question-search-results');
        const preview = wrapper.querySelector('.provas-live-question-search-preview');
        let activeIndex = -1;
        let currentResults = [];
        let debounceHandle = null;

        const closeResults = () => {
            results.hidden = true;
            results.innerHTML = '';
            activeIndex = -1;
            currentResults = [];
        };

        const setSelection = (item) => {
            hiddenId.value = item ? item.id : '';
            hiddenLabel.value = item ? item.label : '';
            input.value = item ? item.label : '';
            preview.textContent = item ? item.snippet : '';
            preview.hidden = !item || !item.snippet;
            closeResults();
        };

        const renderResults = (items) => {
            currentResults = items;
            results.innerHTML = '';

            if (items.length === 0) {
                results.hidden = false;
                const empty = document.createElement('div');
                empty.style.padding = '12px';
                empty.style.fontSize = '0.78rem';
                empty.style.color = 'var(--text-muted)';
                empty.textContent = 'Nenhuma questão encontrada.';
                results.appendChild(empty);
                return;
            }

            items.forEach((item, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'provas-live-question-search-option';
                button.innerHTML = `<span class="provas-live-question-search-option-title">${item.label}</span><span class="provas-live-question-search-option-snippet">${item.snippet}</span>`;
                button.addEventListener('click', () => setSelection(item));
                if (index === activeIndex) {
                    button.classList.add('is-active');
                }
                results.appendChild(button);
            });

            results.hidden = false;
        };

        const runSearch = async (term) => {
            const normalized = term.trim();

            if (normalized === '') {
                closeResults();
                return;
            }

            if (!/^\d+$/.test(normalized) && normalized.length < 2) {
                closeResults();
                return;
            }

            try {
                const response = await fetch(`${endpoint}?term=${encodeURIComponent(normalized)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json();
                activeIndex = -1;
                renderResults(Array.isArray(data.results) ? data.results : []);
            } catch (error) {
                results.hidden = false;
                results.innerHTML = '<div style="padding:12px;font-size:0.78rem;color:#DC2626">Não foi possível buscar as questões agora.</div>';
            }
        };

        input.addEventListener('input', () => {
            hiddenId.value = '';
            hiddenLabel.value = input.value;
            preview.hidden = true;
            preview.textContent = '';

            clearTimeout(debounceHandle);
            debounceHandle = setTimeout(() => runSearch(input.value), 220);
        });

        input.addEventListener('keydown', (event) => {
            if (results.hidden || currentResults.length === 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = (activeIndex + 1) % currentResults.length;
                renderResults(currentResults);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = activeIndex <= 0 ? currentResults.length - 1 : activeIndex - 1;
                renderResults(currentResults);
                return;
            }

            if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                setSelection(currentResults[activeIndex]);
                return;
            }

            if (event.key === 'Escape') {
                closeResults();
            }
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                closeResults();
            }
        });

        const initialId = wrapper.dataset.selectedId;
        const initialLabel = wrapper.dataset.selectedLabel;
        const initialSnippet = wrapper.dataset.selectedSnippet;

        if (initialId && initialLabel) {
            setSelection({
                id: initialId,
                label: initialLabel,
                snippet: initialSnippet,
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initRichEditors();
    initQuestionLiveSearch();
});
</script>
@endpush
