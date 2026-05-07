@extends('layouts.app')

@section('title', 'Hubs de Simulados — AvaliaFA')
@section('page-title', 'Hubs de Simulados')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Hubs / ciclos de simulados</h1>
            <p class="page-subtitle">Agrupe vários simulados em um único link público e acompanhe o ciclo.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('simulados.index') }}" class="btn btn-secondary btn-sm">Voltar para simulados</a>
            <a href="{{ route('simulados.hubs.create') }}" class="btn btn-primary btn-sm">Novo hub</a>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('simulados.hubs.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input" placeholder="Nome ou slug do hub" style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    @foreach(['draft' => 'Rascunho', 'active' => 'Ativo', 'inactive' => 'Inativo', 'archived' => 'Arquivado'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['q', 'status']))
            <a href="{{ route('simulados.hubs.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Hub</th>
                    <th>Sistema</th>
                    <th>Status</th>
                    <th>Simulados</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hubs as $hub)
                <tr>
                    <td>
                        <div style="font-weight:700">{{ $hub->name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $hub->slug }}</div>
                    </td>
                    <td>{{ $hub->clientSystem?->name ?? '—' }}</td>
                    <td><span class="badge {{ $hub->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($hub->status) }}</span></td>
                    <td style="font-family:'JetBrains Mono',monospace">{{ (int) $hub->simulados_count }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:nowrap;white-space:nowrap">
                            <a href="{{ route('simulados.hubs.show', $hub) }}" class="btn btn-ghost btn-sm">Abrir</a>
                            <a href="{{ route('simulados.hubs.edit', $hub) }}" class="btn btn-secondary btn-sm">Editar</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted)">Nenhum hub encontrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px">
        {{ $hubs->links() }}
    </div>
</div>
@endsection
