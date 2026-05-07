@extends('layouts.app')

@section('title', 'Relatórios — AvaliaFA')
@section('page-title', 'Relatórios')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Relatórios de Provas</h1>
            <p class="page-subtitle">Acompanhe desempenho, aprovação e evolução por prova aplicada.</p>
        </div>
        <div>
            <a href="{{ route('relatorios.simulados.geral') }}" class="btn btn-secondary">Relatório geral de simulados</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:24px">
    @php
        $kpis = [
            ['label' => 'Provas', 'value' => $stats['total_exams'], 'color' => '#2563EB', 'bg' => 'rgba(37,99,235,0.1)', 'type' => 'number'],
            ['label' => 'Sessões', 'value' => $stats['total_sessions'], 'color' => '#14B8A6', 'bg' => 'rgba(20,184,166,0.14)', 'type' => 'number'],
            ['label' => 'Corrigidas', 'value' => $stats['graded_sessions'], 'color' => '#8B5CF6', 'bg' => 'rgba(139,92,246,0.14)', 'type' => 'number'],
            ['label' => 'Média', 'value' => $stats['average_score'], 'display' => $stats['average_score'], 'suffix' => '', 'bar_width' => min(100, max(0, $stats['average_score'] * 10)), 'color' => '#F59E0B', 'bg' => '#FEF3C7', 'type' => 'progress'],
            ['label' => 'Taxa aprovação', 'value' => $stats['pass_rate'], 'display' => $stats['pass_rate'], 'suffix' => '%', 'bar_width' => min(100, max(0, $stats['pass_rate'])), 'color' => '#10B981', 'bg' => '#D1FAE5', 'type' => 'progress'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    @if($kpi['type'] === 'progress')
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px">
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary);margin-bottom:8px">{{ $kpi['label'] }}</div>
        <div style="position:relative;height:24px;background:{{ $kpi['bg'] }};border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $kpi['bar_width'] }}%;background:{{ $kpi['color'] }};border-radius:6px;transition:width 1s ease"></div>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:0.75rem;font-weight:700;color:{{ $kpi['color'] }};text-shadow:0 0 2px rgba(255,255,255,0.8)">
                {{ number_format($kpi['display'], 2, ',', '.') }}{{ $kpi['suffix'] }}
            </div>
        </div>
    </div>
    @else
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="font-size:0.95rem;font-weight:800;color:{{ $kpi['color'] }}">{{ $kpi['value'] }}</span>
        </div>
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary)">{{ $kpi['label'] }}</div>
    </div>
    @endif
    @endforeach
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('relatorios.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Título da prova..." style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:140px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Rascunho</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Publicada</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ativa</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Encerrada</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Arquivada</option>
                </select>
            </div>
            @if(auth()->user()->isSuperAdmin() && $systems->count())
            <div style="min-width:170px">
                <label class="form-label" style="margin-bottom:4px">Sistema</label>
                <select name="sistema" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos os sistemas</option>
                    @foreach($systems as $system)
                    <option value="{{ $system->id }}" {{ (string) request('sistema') === (string) $system->id ? 'selected' : '' }}>
                        {{ $system->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['busca', 'status', 'sistema']))
            <a href="{{ route('relatorios.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Prova</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Sessões</th>
                    <th>Corrigidas</th>
                    <th>Aprovadas</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $exam)
                @php
                    $statusMap = [
                        'draft' => ['badge-neutral', 'Rascunho'],
                        'published' => ['badge-warning', 'Publicada'],
                        'active' => ['badge-success', 'Ativa'],
                        'closed' => ['badge-danger', 'Encerrada'],
                        'archived' => ['badge-neutral', 'Arquivada'],
                    ];
                    [$badge, $statusLabel] = $statusMap[$exam->status] ?? ['badge-neutral', ucfirst($exam->status)];
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--text-primary)">{{ $exam->title }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Criada em {{ $exam->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-secondary)">{{ $exam->clientSystem?->name ?? '—' }}</td>
                    <td><span class="badge {{ $badge }}">{{ $statusLabel }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $exam->sessions_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $exam->graded_sessions_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $exam->passed_sessions_count }}</td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:6px">
                            <a href="{{ route('relatorios.prova', $exam) }}" class="btn btn-secondary btn-sm">
                                Ver relatório
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                        Nenhuma prova encontrada para os filtros informados.
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
