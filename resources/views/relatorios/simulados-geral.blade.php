@extends('layouts.app')

@section('title', 'Relatório Geral de Simulados — AvaliaFA')
@section('page-title', 'Relatório Geral de Simulados')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Relatório Geral de Simulados</h1>
            <p class="page-subtitle">Acompanhe desempenho, status e participação de todos os simulados em um só lugar.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('export-panel').style.display=document.getElementById('export-panel').style.display==='none'?'block':'none'">
                Exportar CSV
            </button>
            <a href="{{ route('relatorios.index') }}" class="btn btn-ghost">Voltar</a>
        </div>
    </div>
</div>

@php
$exportColunas = [
    'sessao_id'  => 'ID Sessão',
    'simulado'   => 'Simulado',
    'sistema'    => 'Sistema',
    'estudante'  => 'Nome',
    'cpf'        => 'CPF',
    'email'      => 'E-mail',
    'telefone'   => 'Telefone',
    'tentativa'  => 'Tentativa',
    'status'     => 'Status',
    'nota_final' => 'Nota Final',
    'aprovado'   => 'Aprovado',
    'violacoes'  => 'Violações',
    'risco'      => 'Risco',
    'inicio'     => 'Início',
    'envio'      => 'Envio',
];
$colunasAtivas = request()->has('colunas') ? request('colunas') : array_keys($exportColunas);
@endphp

<div id="export-panel" style="display:none;margin-bottom:16px">
    <div class="card">
        <div style="padding:14px 20px;border-bottom:1px solid var(--surface-border);font-weight:700;color:var(--text-primary)">
            Configurar exportação CSV
        </div>
        <form method="GET" action="{{ route('relatorios.simulados.geral.export') }}">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="hidden" name="resultado" value="{{ request('resultado') }}">
            <input type="hidden" name="busca" value="{{ request('busca') }}">
            @if(auth()->user()->isSuperAdmin())
            <input type="hidden" name="sistema" value="{{ request('sistema') }}">
            @endif
            <div style="padding:16px 20px">
                <div style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:12px">Selecione as colunas que deseja incluir no arquivo CSV:</div>
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">
                    @foreach($exportColunas as $key => $label)
                    <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--surface-border);border-radius:8px;cursor:pointer;background:var(--surface-bg);font-size:0.83rem;user-select:none">
                        <input type="checkbox" name="colunas[]" value="{{ $key }}" {{ in_array($key, $colunasAtivas) ? 'checked' : '' }} style="accent-color:var(--color-primary-600)">
                        {{ $label }}
                    </label>
                    @endforeach
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    <button type="submit" class="btn btn-primary btn-sm">Baixar CSV</button>
                    <button type="button" onclick="document.querySelectorAll('#export-panel input[type=checkbox]').forEach(c=>c.checked=true)" class="btn btn-ghost btn-sm">Marcar tudo</button>
                    <button type="button" onclick="document.querySelectorAll('#export-panel input[type=checkbox]').forEach(c=>c.checked=false)" class="btn btn-ghost btn-sm">Desmarcar tudo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:24px">
    @php
        $kpis = [
            ['label' => 'Sessões', 'value' => $stats['total_sessions'], 'color' => '#2563EB', 'bg' => 'rgba(37,99,235,0.1)', 'type' => 'number'],
            ['label' => 'Em prova', 'value' => $stats['in_progress'], 'color' => '#F59E0B', 'bg' => '#FEF3C7', 'type' => 'number'],
            ['label' => 'Enviadas', 'value' => $stats['submitted'], 'color' => '#6366F1', 'bg' => 'rgba(99,102,241,0.12)', 'type' => 'number'],
            ['label' => 'Corrigidas', 'value' => $stats['graded'], 'color' => '#8B5CF6', 'bg' => 'rgba(139,92,246,0.14)', 'type' => 'number'],
            ['label' => 'Média', 'value' => $stats['average_score'], 'display' => $stats['average_score'], 'suffix' => '', 'bar_width' => min(100, max(0, $stats['average_score'] * 10)), 'color' => '#14B8A6', 'bg' => 'rgba(20,184,166,0.14)', 'type' => 'progress'],
            ['label' => 'Aprovação', 'value' => $stats['pass_rate'], 'display' => $stats['pass_rate'], 'suffix' => '%', 'bar_width' => min(100, max(0, $stats['pass_rate'])), 'color' => '#10B981', 'bg' => '#D1FAE5', 'type' => 'progress'],
            ['label' => 'Risco médio', 'value' => $stats['avg_risk'], 'display' => $stats['avg_risk'], 'suffix' => '', 'bar_width' => min(100, max(0, $stats['avg_risk'])), 'color' => '#F97316', 'bg' => '#FFEDD5', 'type' => 'progress'],
            ['label' => 'Violações', 'value' => $stats['total_violations'], 'color' => '#EF4444', 'bg' => '#FEE2E2', 'type' => 'number'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    @if($kpi['type'] === 'progress')
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px">
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary);margin-bottom:8px">{{ $kpi['label'] }}</div>
        <div style="position:relative;height:24px;background:{{ $kpi['bg'] }};border-radius:6px;overflow:hidden">
            <div style="height:100%;width:{{ $kpi['bar_width'] }}%;background:{{ $kpi['color'] }};border-radius:6px;transition:width 1s ease"></div>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:0.75rem;font-weight:700;color:{{ $kpi['color'] }};text-shadow:0 0 2px rgba(255,255,255,0.8)">
                {{ $kpi['label'] === 'Risco médio' ? number_format($kpi['display'], 1, ',', '.') : number_format($kpi['display'], 2, ',', '.') }}{{ $kpi['suffix'] }}
            </div>
        </div>
    </div>
    @else
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="font-size:0.92rem;font-weight:800;color:{{ $kpi['color'] }}">{{ $kpi['value'] }}</span>
        </div>
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary)">{{ $kpi['label'] }}</div>
    </div>
    @endif
    @endforeach
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('relatorios.simulados.geral') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Buscar estudante</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Nome, CPF, e-mail ou telefone..." style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:140px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    @foreach(['pending' => 'Pendente', 'in_progress' => 'Em prova', 'submitted' => 'Enviada', 'expired' => 'Expirada', 'terminated' => 'Encerrada', 'graded' => 'Corrigida'] as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:150px">
                <label class="form-label" style="margin-bottom:4px">Resultado</label>
                <select name="resultado" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="aprovado" {{ request('resultado') === 'aprovado' ? 'selected' : '' }}>Aprovado</option>
                    <option value="reprovado" {{ request('resultado') === 'reprovado' ? 'selected' : '' }}>Reprovado</option>
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
            @if(request()->hasAny(['busca', 'status', 'resultado', 'sistema']))
            <a href="{{ route('relatorios.simulados.geral') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sessão</th>
                    <th>Estudante</th>
                    <th>Simulado</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Nota</th>
                    <th>Resultado</th>
                    <th>Violações</th>
                    <th>Risco</th>
                    <th>Início</th>
                    <th>Envio</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                @php
                    $studentName = trim((string) ($session->student?->name
                        ?? trim(($session->student?->first_name ?? '') . ' ' . ($session->student?->last_name ?? ''))));
                    $statusMap = [
                        'pending' => ['badge-warning', 'Pendente'],
                        'in_progress' => ['badge-primary', 'Em prova'],
                        'submitted' => ['badge-info', 'Enviada'],
                        'expired' => ['badge-neutral', 'Expirada'],
                        'terminated' => ['badge-danger', 'Encerrada'],
                        'graded' => ['badge-success', 'Corrigida'],
                    ];
                    [$statusClass, $statusLabel] = $statusMap[$session->status] ?? ['badge-neutral', ucfirst($session->status)];
                @endphp
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem">#{{ $session->id }}</td>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary)">{{ $studentName !== '' ? $studentName : 'Estudante removido' }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">
                            {{ $session->student?->email ?: 'Sem email' }}
                            @php $reportPhone = $phonesByUserId[$session->student_id] ?? null; @endphp
                            @if($reportPhone)
                            <span style="margin-left:8px">• {{ $reportPhone }}</span>
                            @endif
                        </div>
                    </td>
                    <td>{{ $session->exam?->simulado?->name ?? ($session->exam?->title ?? '—') }}</td>
                    <td>{{ $session->exam?->clientSystem?->name ?? '—' }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $session->final_score !== null ? number_format((float) $session->final_score, 2, ',', '.') : '—' }}</td>
                    <td>
                        @if($session->passed === null)
                        <span class="badge badge-neutral">—</span>
                        @elseif($session->passed)
                        <span class="badge badge-success">Aprovado</span>
                        @else
                        <span class="badge badge-danger">Reprovado</span>
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $session->violation_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ $session->risk_score }}</td>
                    <td style="font-size:0.78rem;color:var(--text-secondary)">{{ $session->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td style="font-size:0.78rem;color:var(--text-secondary)">{{ $session->submitted_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        @if($session->student_id)
                        <a href="{{ route('estudantes.edit', $session->student_id) }}" class="btn btn-ghost btn-sm" style="height:30px;padding:0 10px">Editar usuário</a>
                        @else
                        <span style="font-size:0.75rem;color:var(--text-muted)">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                        Nenhuma sessão encontrada para os filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.8rem;color:var(--text-muted)">
            {{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} de {{ $sessions->total() }} sessões
        </span>
        <div style="display:flex;gap:4px">
            @if($sessions->onFirstPage())
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">‹ Anterior</span>
            @else
                <a href="{{ $sessions->previousPageUrl() }}" class="btn btn-ghost btn-sm">‹ Anterior</a>
            @endif
            @if($sessions->hasMorePages())
                <a href="{{ $sessions->nextPageUrl() }}" class="btn btn-ghost btn-sm">Próxima ›</a>
            @else
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Próxima ›</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
