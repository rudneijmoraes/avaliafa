@extends('layouts.app')

@section('title', 'Sistemas — AvaliaFA')
@section('page-title', 'Sistemas')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Módulo de Sistemas</h1>
            <p class="page-subtitle">Gerencie os sistemas consumidores da API e suas configurações de integração.</p>
        </div>
        <a href="{{ route('sistemas.create') }}" class="btn btn-primary">
            Novo Sistema
        </a>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('sistemas.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Nome ou slug do sistema..." style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:140px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="ativo" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="1" {{ request('ativo') === '1' ? 'selected' : '' }}>Ativo</option>
                    <option value="0" {{ request('ativo') === '0' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['busca', 'ativo']))
            <a href="{{ route('sistemas.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sistema</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Moodle</th>
                    <th>Webhook</th>
                    <th>Usuários</th>
                    <th>Provas</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($systems as $system)
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--text-primary)">{{ $system->name }}</div>
                        <div style="font-size:0.74rem;color:var(--text-muted)">Criado em {{ $system->created_at->format('d/m/Y H:i') }}</div>
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem">{{ $system->slug }}</td>
                    <td>
                        @if($system->active)
                        <span class="badge badge-success">Ativo</span>
                        @else
                        <span class="badge badge-neutral">Inativo</span>
                        @endif
                    </td>
                    <td>
                        @if($system->hasMoodleIntegration())
                        <span class="badge badge-success">Configurado</span>
                        @else
                        <span class="badge badge-neutral">Não configurado</span>
                        @endif
                    </td>
                    <td style="font-size:0.78rem;color:var(--text-secondary)">
                        {{ $system->webhook_url ? 'Ativo' : 'Não definido' }}
                    </td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem">{{ $system->users_count }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem">{{ $system->exams_count }}</td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:6px">
                            <a href="{{ route('sistemas.edit', $system) }}" class="btn btn-secondary btn-sm">
                                Editar
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                        Nenhum sistema encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($systems->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.8rem;color:var(--text-muted)">
            {{ $systems->firstItem() }}–{{ $systems->lastItem() }} de {{ $systems->total() }} sistemas
        </span>
        <div style="display:flex;gap:4px">
            @if($systems->onFirstPage())
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">‹ Anterior</span>
            @else
                <a href="{{ $systems->previousPageUrl() }}" class="btn btn-ghost btn-sm">‹ Anterior</a>
            @endif
            @if($systems->hasMorePages())
                <a href="{{ $systems->nextPageUrl() }}" class="btn btn-ghost btn-sm">Próxima ›</a>
            @else
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Próxima ›</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
