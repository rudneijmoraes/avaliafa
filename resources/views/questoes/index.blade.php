@extends('layouts.app')

@section('title', 'Questões — AvaliaFA')
@section('page-title', 'Questões')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">Banco de Questões</h1>
            <p class="page-subtitle">Organize enunciados, dificuldades e alternativas por sistema.</p>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('questoes.import.form') }}" class="btn btn-secondary" style="display:flex;align-items:center;gap:6px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Importar
            </a>
            <a href="{{ route('questoes.create') }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nova Questão
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('questoes.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Enunciado da questão..." style="height:36px;padding:7px 12px">
            </div>

            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Tipo</label>
                <select name="tipo" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="multiple_choice" @selected(request('tipo') === 'multiple_choice')>Múltipla escolha</option>
                    <option value="true_false" @selected(request('tipo') === 'true_false')>Verdadeiro/Falso</option>
                    <option value="multiple_answer" @selected(request('tipo') === 'multiple_answer')>Múltiplas respostas</option>
                </select>
            </div>

            <div style="min-width:150px">
                <label class="form-label" style="margin-bottom:4px">Dificuldade</label>
                <select name="dificuldade" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todas</option>
                    <option value="easy" @selected(request('dificuldade') === 'easy')>Fácil</option>
                    <option value="medium" @selected(request('dificuldade') === 'medium')>Média</option>
                    <option value="hard" @selected(request('dificuldade') === 'hard')>Difícil</option>
                </select>
            </div>

            @if(isset($disciplines) && $disciplines->count())
            <div style="min-width:170px">
                <label class="form-label" style="margin-bottom:4px">Disciplina</label>
                <select name="disciplina" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todas</option>
                    @foreach($disciplines as $disc)
                    <option value="{{ $disc->id }}" @selected((string) request('disciplina') === (string) $disc->id)>{{ $disc->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if(auth()->user()->isSuperAdmin() && $systems->count())
            <div style="min-width:170px">
                <label class="form-label" style="margin-bottom:4px">Sistema</label>
                <select name="sistema" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos os sistemas</option>
                    @foreach($systems as $system)
                    <option value="{{ $system->id }}" @selected((string) request('sistema') === (string) $system->id)>{{ $system->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['busca', 'tipo', 'dificuldade', 'sistema', 'disciplina']))
            <a href="{{ route('questoes.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px">ID</th>
                    <th>Enunciado</th>
                    <th>Tipo</th>
                    <th>Dificuldade</th>
                    <th>Alternativas</th>
                    <th>Resposta</th>
                    <th>Disciplina</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($questions as $questao)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem;color:var(--text-muted);white-space:nowrap">#{{ $questao->id }}</td>
                    <td>
                        <div style="max-width:420px">
                            <a href="{{ route('questoes.show', $questao) }}" style="font-weight:700;color:var(--color-primary-700);text-decoration:none">
                                {{ \Illuminate\Support\Str::limit(strip_tags($questao->content), 90) }}
                            </a>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">
                                Criada por {{ $questao->creator?->name ?? 'Usuário não identificado' }}
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--text-secondary)">{{ match($questao->type) {
                        'multiple_choice' => 'Múltipla escolha',
                        'true_false' => 'Verdadeiro/Falso',
                        'multiple_answer' => 'Múltiplas respostas',
                        default => ucfirst($questao->type),
                    } }}</td>
                    <td>
                        <span class="badge {{ match($questao->difficulty) {
                            'easy' => 'badge-success',
                            'medium' => 'badge-warning',
                            'hard' => 'badge-danger',
                            default => 'badge-neutral',
                        } }}">
                            {{ match($questao->difficulty) {
                                'easy' => 'Fácil',
                                'medium' => 'Média',
                                'hard' => 'Difícil',
                                default => ucfirst($questao->difficulty),
                            } }}
                        </span>
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;color:var(--text-secondary)">{{ $questao->choices_count }}</td>
                    <td style="white-space:nowrap">
                        @php
                            $letters = range('A', 'Z');
                            $allChoices = $questao->choices->sortBy('order')->values();
                            $correct = $questao->correctChoices->sortBy('order')->values();
                        @endphp
                        @if($questao->type === 'true_false')
                            @foreach($correct as $c)
                                <span class="badge badge-success" style="font-size:0.72rem">{{ $c->content === '1' || strtolower($c->content) === 'true' || strtolower($c->content) === 'verdadeiro' ? 'Verdadeiro' : 'Falso' }}</span>
                            @endforeach
                        @else
                            @foreach($correct as $c)
                                @php $idx = $allChoices->search(fn($ch) => $ch->id === $c->id); @endphp
                                <span class="badge badge-success" style="font-size:0.72rem;margin-right:2px">{{ $idx !== false ? ($letters[$idx] ?? '?') : '?' }}</span>
                            @endforeach
                        @endif
                        @if($correct->isEmpty())
                            <span style="color:var(--text-muted);font-size:0.78rem">—</span>
                        @endif
                    </td>
                    <td style="color:var(--text-secondary)">{{ $questao->discipline?->name ?? '—' }}</td>
                    <td style="color:var(--text-secondary)">{{ $questao->clientSystem?->name ?? '—' }}</td>
                    <td><span class="badge {{ $questao->active ? 'badge-success' : 'badge-neutral' }}">{{ $questao->active ? 'Ativa' : 'Inativa' }}</span></td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:4px">
                            <a href="{{ route('questoes.show', $questao) }}" class="btn btn-ghost btn-sm" title="Ver">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="{{ route('questoes.edit', $questao) }}" class="btn btn-ghost btn-sm" title="Editar">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('questoes.destroy', $questao) }}" method="POST" onsubmit="return confirmDelete(this, {title:'Remover questão', message:'Deseja remover esta questão?'})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm" title="Remover" style="color:var(--color-danger)">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:56px 16px">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:12px">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <p style="color:var(--text-muted);margin:0;font-size:0.9rem">Nenhuma questão encontrada</p>
                            <a href="{{ route('questoes.create') }}" class="btn btn-primary btn-sm">Criar primeira questão</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($questions->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.8rem;color:var(--text-muted)">{{ $questions->firstItem() }}–{{ $questions->lastItem() }} de {{ $questions->total() }} questões</span>
        <div style="display:flex;gap:4px">
            @if($questions->onFirstPage())
            <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">‹ Anterior</span>
            @else
            <a href="{{ $questions->previousPageUrl() }}" class="btn btn-ghost btn-sm">‹ Anterior</a>
            @endif
            @if($questions->hasMorePages())
            <a href="{{ $questions->nextPageUrl() }}" class="btn btn-ghost btn-sm">Próxima ›</a>
            @else
            <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Próxima ›</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
