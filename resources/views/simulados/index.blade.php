@extends('layouts.app')

@section('title', 'Simulados — AvaliaFA')
@section('page-title', 'Simulados')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Gestão de Simulados</h1>
            <p class="page-subtitle">Captação, aplicação e análise de desempenho dos candidatos.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('simulados.hubs.index') }}" class="btn btn-secondary btn-sm">Hubs / Ciclos</a>
            <a href="{{ route('simulados.templates.index') }}" class="btn btn-secondary btn-sm">Templates de E-mail</a>
            <a href="{{ route('simulados.create') }}" class="btn btn-primary btn-sm" aria-label="Criar simulado">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo Simulado
            </a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('simulados.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input" placeholder="Nome ou slug do simulado" style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    @foreach(['draft' => 'Rascunho', 'scheduled' => 'Programado', 'active' => 'Ativo', 'inactive' => 'Inativo', 'archived' => 'Arquivado'] as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['q','status']))
            <a href="{{ route('simulados.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Simulado</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Hub</th>
                    <th>Inscritos</th>
                    <th>Tentativas</th>
                    <th>Moodle</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($simulados as $simulado)
                @php
                    $statusLabel = [
                        'draft' => 'Rascunho',
                        'scheduled' => 'Programado',
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                        'archived' => 'Arquivado',
                    ][$simulado->status] ?? '—';
                    $statusBadgeClass = match ($simulado->status) {
                        'active' => 'badge-success',
                        'scheduled' => 'badge-warning',
                        default => 'badge-neutral',
                    };
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:700">{{ $simulado->name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $simulado->slug }}</div>
                    </td>
                    <td>{{ $simulado->clientSystem?->name ?? '—' }}</td>
                    <td><span class="badge {{ $statusBadgeClass }}">{{ $statusLabel }}</span></td>
                    <td>
                        @if($simulado->hub)
                        <a href="{{ route('simulados.hubs.show', $simulado->hub) }}" style="font-size:0.78rem;color:#1D4ED8;text-decoration:none;font-weight:700">
                            {{ $simulado->hub->name }}
                        </a>
                        <div style="font-size:0.72rem;color:var(--text-muted)">{{ $simulado->hub->slug }}</div>
                        @else
                        —
                        @endif
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace">{{ $simulado->registrations_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace" data-simulado-attempts="{{ (int) $simulado->attempts_count }}">{{ (int) $simulado->attempts_count }}</td>
                    <td><span class="badge {{ $simulado->moodle_integration_enabled ? 'badge-warning' : 'badge-neutral' }}">{{ $simulado->moodle_integration_enabled ? 'Ligado' : 'Desligado' }}</span></td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:nowrap;white-space:nowrap">
                            <a href="{{ route('simulados.show', $simulado) }}" title="Abrir simulado" aria-label="Abrir simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EFF6FF;color:#1D4ED8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                            <a href="{{ route('simulados.edit', $simulado) }}" title="Editar simulado" aria-label="Editar simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EFF6FF;color:#1D4ED8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('simulados.duplicate', $simulado) }}" onsubmit="return confirmDelete(this, {title:'Duplicar simulado', message:'Deseja criar uma cópia deste simulado para a próxima semana?', confirmLabel:'Duplicar'})">
                                @csrf
                                <button type="submit" title="Duplicar simulado" aria-label="Duplicar simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EEF2FF;color:#3730A3;display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                        <rect x="9" y="9" width="11" height="11" rx="2"/>
                                        <rect x="4" y="4" width="11" height="11" rx="2"/>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('simulados.status', $simulado) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="{{ $simulado->status === 'active' ? 'Inativar simulado' : 'Ativar simulado' }}" aria-label="{{ $simulado->status === 'active' ? 'Inativar simulado' : 'Ativar simulado' }}" style="width:34px;height:34px;border-radius:9px;border:1px solid #D1D5DB;background:#FFFFFF;color:#334155;display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                        <path d="M12 2v10"/>
                                        <path d="M5.45 5.45a9 9 0 1 0 13.1 0"/>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('simulados.destroy', $simulado) }}" onsubmit="return confirmDelete(this, {title:'Excluir simulado', message:'Deseja excluir este simulado? Esta ação não pode ser desfeita.', confirmLabel:'Excluir'})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Excluir simulado" aria-label="Excluir simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #FCA5A5;background:#FEF2F2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M9 6V4h6v2"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">Nenhum simulado encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px">
        {{ $simulados->links() }}
    </div>
</div>
@endsection
