@extends('layouts.app')

@php
    $difficultyClass = match($questao->difficulty) {
        'easy' => 'badge-success',
        'medium' => 'badge-warning',
        'hard' => 'badge-danger',
        default => 'badge-neutral',
    };
@endphp

@section('title', 'Questão — AvaliaFA')
@section('page-title', 'Detalhes da Questão')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:900px">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                <span class="badge {{ $difficultyClass }}">{{ match($questao->difficulty) { 'easy' => 'Fácil', 'medium' => 'Média', 'hard' => 'Difícil', default => ucfirst($questao->difficulty) } }}</span>
                <span class="badge badge-primary">{{ match($questao->type) { 'multiple_choice' => 'Múltipla escolha', 'true_false' => 'Verdadeiro/Falso', 'multiple_answer' => 'Múltiplas respostas', default => ucfirst($questao->type) } }}</span>
                <span class="badge {{ $questao->active ? 'badge-success' : 'badge-neutral' }}">{{ $questao->active ? 'Ativa' : 'Inativa' }}</span>
            </div>
            <h1 class="page-title" style="margin-bottom:6px">Questão cadastrada</h1>
            <p class="page-subtitle" style="line-height:1.6;max-width:760px">Visualização formatada do enunciado, alternativas e metadados pedagógicos.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('questoes.edit', $questao) }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            <a href="{{ route('questoes.index') }}" class="btn btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar
            </a>
        </div>
    </div>
</div>

<div class="questoes-show-grid" style="display:grid;grid-template-columns:minmax(0,1.35fr) minmax(300px,0.85fr);gap:20px;align-items:start">
    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Enunciado</div>
                    <div class="card-subtitle">Conteúdo principal apresentado ao aluno.</div>
                </div>
            </div>
            <div class="card-body">
                <div class="question-html-content" style="font-size:0.98rem;line-height:1.85;color:var(--text-primary)">{!! $questao->content !!}</div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Alternativas</div>
                    <div class="card-subtitle">As respostas corretas são destacadas visualmente.</div>
                </div>
                <span class="badge badge-primary">{{ $questao->choices->count() }} opção(ões)</span>
            </div>
            <div class="card-body" style="display:grid;gap:12px">
                @foreach($questao->choices as $index => $choice)
                <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;border:1px solid {{ $choice->is_correct ? 'rgba(16,185,129,0.28)' : 'var(--surface-border)' }};border-radius:14px;background:{{ $choice->is_correct ? 'var(--color-success-bg)' : 'var(--surface-bg)' }}">
                    <div style="width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:800;background:{{ $choice->is_correct ? 'var(--color-success)' : 'var(--surface-card)' }};color:{{ $choice->is_correct ? 'white' : 'var(--text-secondary)' }};border:1px solid {{ $choice->is_correct ? 'transparent' : 'var(--surface-border)' }};flex-shrink:0">
                        {{ chr(65 + $index) }}
                    </div>
                    <div style="flex:1">
                        <div style="font-size:0.92rem;font-weight:700;color:var(--text-primary);margin-bottom:4px">{!! $choice->content ?? $choice->text ?? 'Alternativa sem conteúdo' !!}</div>
                        <span class="badge {{ $choice->is_correct ? 'badge-success' : 'badge-neutral' }}">{{ $choice->is_correct ? 'Resposta correta' : 'Distrator' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        @if($questao->explanation)
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Explicação</div>
                    <div class="card-subtitle">Feedback pedagógico configurado para a questão.</div>
                </div>
            </div>
            <div class="card-body">
                <div class="question-html-content" style="font-size:0.92rem;line-height:1.8;color:var(--text-secondary)">{!! $questao->explanation !!}</div>
            </div>
        </section>
        @endif
    </div>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Metadados</div>
                    <div class="card-subtitle">Dados institucionais e autoria.</div>
                </div>
            </div>
            <div class="card-body">
                <div style="display:grid;gap:14px">
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Sistema</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $questao->clientSystem?->name ?? 'Não informado' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Criador</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $questao->creator?->name ?? 'Não informado' }}</div>
                        <div style="font-size:0.78rem;color:var(--text-secondary)">{{ $questao->creator?->email ?? 'Sem email cadastrado' }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Versão</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $questao->version }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Criada em</div>
                        <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $questao->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Tags e classificação</div>
                    <div class="card-subtitle">Marcadores para busca e composição futura.</div>
                </div>
            </div>
            <div class="card-body">
                @if(!empty($questao->tags))
                <div style="display:flex;flex-wrap:wrap;gap:8px">
                    @foreach($questao->tags as $tag)
                    <span class="badge badge-primary">{{ $tag }}</span>
                    @endforeach
                </div>
                @else
                <div style="font-size:0.82rem;color:var(--text-muted)">Nenhuma tag cadastrada para esta questão.</div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1100px) {
        .questoes-show-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
