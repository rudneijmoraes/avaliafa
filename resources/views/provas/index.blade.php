@extends('layouts.app')

@section('title', 'Provas — AvaliaFA')
@section('page-title', 'Provas')

@section('content')

{{-- Cabeçalho + KPIs --}}
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Gerenciamento de Provas</h1>
            <p class="page-subtitle">Crie, configure e publique avaliações</p>
        </div>
        <a href="{{ route('provas.create') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nova Prova
        </a>
    </div>
</div>

{{-- KPI mini cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:24px">
    @php
        $kpis = [
            ['label' => 'Total',       'value' => $counts['total'],     'color' => '#2563EB', 'bg' => 'rgba(37,99,235,0.1)'],
            ['label' => 'Rascunho',    'value' => $counts['draft'],     'color' => '#94A3B8', 'bg' => '#F1F5F9'],
            ['label' => 'Publicadas',  'value' => $counts['published'], 'color' => '#F59E0B', 'bg' => '#FEF3C7'],
            ['label' => 'Ativas',      'value' => $counts['active'],    'color' => '#10B981', 'bg' => '#D1FAE5'],
        ];
    @endphp
    @foreach($kpis as $k)
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:{{ $k['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="font-size:1.1rem;font-weight:800;color:{{ $k['color'] }}">{{ $k['value'] }}</span>
        </div>
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary)">{{ $k['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('provas.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Título da prova..." style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:140px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="draft"      {{ request('status') === 'draft'      ? 'selected' : '' }}>Rascunho</option>
                    <option value="published"  {{ request('status') === 'published'  ? 'selected' : '' }}>Publicada</option>
                    <option value="active"     {{ request('status') === 'active'     ? 'selected' : '' }}>Ativa</option>
                    <option value="closed"     {{ request('status') === 'closed'     ? 'selected' : '' }}>Encerrada</option>
                    <option value="archived"   {{ request('status') === 'archived'   ? 'selected' : '' }}>Arquivada</option>
                </select>
            </div>
            @if(isset($disciplines) && $disciplines->count())
            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Disciplina</label>
                <select name="disciplina" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todas</option>
                    @foreach($disciplines as $disc)
                    <option value="{{ $disc->id }}" {{ request('disciplina') == $disc->id ? 'selected' : '' }}>{{ $disc->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if(auth()->user()->isSuperAdmin() && $systems->count())
            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Sistema</label>
                <select name="sistema" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos os sistemas</option>
                    @foreach($systems as $sys)
                    <option value="{{ $sys->id }}" {{ request('sistema') == $sys->id ? 'selected' : '' }}>{{ $sys->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['busca','status','sistema','disciplina']))
            <a href="{{ route('provas.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Prova</th>
                    <th>Disciplina</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Duração</th>
                    <th>Questões</th>
                    <th>Sessões</th>
                    <th>Pontos Mín.</th>
                    <th>Criada em</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $exam)
                @php
                    $statusMap = [
                        'draft'     => ['badge-neutral',  'Rascunho'],
                        'published' => ['badge-warning',  'Publicada'],
                        'active'    => ['badge-success',  'Ativa'],
                        'closed'    => ['badge-danger',   'Encerrada'],
                        'archived'  => ['badge-neutral',  'Arquivada'],
                    ];
                    [$cls, $lbl] = $statusMap[$exam->status] ?? ['badge-neutral', ucfirst($exam->status)];
                @endphp
                <tr>
                    <td>
                        <div>
                            <a href="{{ route('provas.show', $exam) }}" style="font-weight:600;color:var(--color-primary-700);text-decoration:none;font-size:0.875rem">
                                {{ $exam->title }}
                            </a>
                            @if($exam->description)
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:1px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $exam->description }}
                            </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        @if($exam->discipline)
                        <span style="font-size:0.8rem;color:var(--text-secondary)">{{ $exam->discipline->name }}</span>
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($exam->clientSystem)
                        <span style="font-size:0.8rem;color:var(--text-secondary)">{{ $exam->clientSystem->name }}</span>
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $cls }}">{{ $lbl }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $exam->duration_minutes }}min</td>
                    <td style="font-weight:600;color:var(--text-primary)">{{ $exam->questions_count }}</td>
                    <td style="color:var(--text-secondary)">{{ $exam->sessions_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ number_format((float) $exam->passing_score, 2, ',', '.') }}</td>
                    <td style="font-size:0.8rem;color:var(--text-secondary)">{{ $exam->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:4px">
                            <a href="{{ route('provas.show', $exam) }}" class="btn btn-ghost btn-sm" title="Ver detalhes">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="{{ route('provas.edit', $exam) }}" class="btn btn-ghost btn-sm" title="Editar">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <form action="{{ route('provas.destroy', $exam) }}" method="POST" onsubmit="return confirmDelete(this, {title:'Remover prova', message:'Deseja remover esta prova?'})">
                                @csrf @method('DELETE')
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
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <p style="color:var(--text-muted);margin:0;font-size:0.9rem">Nenhuma prova encontrada</p>
                            <a href="{{ route('provas.create') }}" class="btn btn-primary btn-sm">Criar primeira prova</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($exams->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.8rem;color:var(--text-muted)">
            {{ $exams->firstItem() }}–{{ $exams->lastItem() }} de {{ $exams->total() }} provas
        </span>
        <div style="display:flex;gap:4px">
            @if($exams->onFirstPage())
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">‹ Anterior</span>
            @else
                <a href="{{ $exams->previousPageUrl() }}" class="btn btn-ghost btn-sm">‹ Anterior</a>
            @endif
            @if($exams->hasMorePages())
                <a href="{{ $exams->nextPageUrl() }}" class="btn btn-ghost btn-sm">Próxima ›</a>
            @else
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Próxima ›</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection
