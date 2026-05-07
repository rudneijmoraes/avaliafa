@extends('layouts.app')

@section('title', 'Materiais Complementares — AvaliaFA')
@section('page-title', 'Materiais Complementares')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">Materiais Complementares</h1>
            <p class="page-subtitle">Gerencie os materiais de apoio disponibilizados aos alunos no portal.</p>
        </div>
        <a href="{{ route('conteudo.materiais.create') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Novo Material
        </a>
    </div>
</div>

{{-- Flash success --}}
@if(session('success'))
<div style="display:flex;align-items:flex-start;gap:10px;padding:14px 18px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;margin-bottom:16px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <span style="font-size:0.875rem;font-weight:500">{{ session('success') }}</span>
</div>
@endif

{{-- Filtros --}}
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('conteudo.materiais.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="margin-bottom:4px">Setor / Sistema</label>
                <select name="sistema" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos os setores</option>
                    @foreach($systems as $system)
                        <option value="{{ $system->id }}" {{ (string) ($filters['sistema'] ?? '') === (string) $system->id ? 'selected' : '' }}>
                            {{ $system->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:160px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="1" {{ ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Ativo</option>
                    <option value="0" {{ ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Filtrar
            </button>
            @if(!empty($filters['sistema']) || !empty($filters['status']))
            <a href="{{ route('conteudo.materiais.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($materials->isEmpty())
        <div style="padding:48px 24px;text-align:center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);margin:0 auto 12px;display:block"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            <p style="color:var(--text-muted);font-size:0.9rem;margin:0">Nenhum material cadastrado.</p>
            <a href="{{ route('conteudo.materiais.create') }}" class="btn btn-primary" style="margin-top:16px;display:inline-flex">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Criar primeiro material
            </a>
        </div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Setores</th>
                    <th style="width:80px">Capa</th>
                    <th style="width:80px">Ativo</th>
                    <th style="width:110px">Criado em</th>
                    <th>Criado por</th>
                    <th style="text-align:right;width:140px">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materials as $material)
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary)">
                            {{ \Illuminate\Support\Str::limit($material->title, 60) }}
                        </div>
                    </td>
                    <td>
                        @php
                            $systemNames = $material->systems->pluck('name');
                        @endphp
                        @if($systemNames->isEmpty())
                            <span style="color:var(--text-muted);font-size:0.82rem">—</span>
                        @else
                            <div style="display:flex;flex-wrap:wrap;gap:4px">
                                @foreach($systemNames as $sname)
                                    <span class="badge badge-primary" style="font-size:0.68rem">{{ $sname }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($material->cover_image)
                            <span class="badge badge-success">Sim</span>
                        @else
                            <span style="color:var(--text-muted);font-size:0.82rem">—</span>
                        @endif
                    </td>
                    <td>
                        @if($material->active)
                            <span class="badge badge-success">Sim</span>
                        @else
                            <span class="badge badge-neutral">Não</span>
                        @endif
                    </td>
                    <td style="font-size:0.84rem;color:var(--text-secondary)">
                        {{ $material->created_at->format('d/m/Y') }}
                    </td>
                    <td style="font-size:0.84rem;color:var(--text-secondary)">
                        {{ $material->creator->name ?? '—' }}
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:6px;align-items:center">
                            <a href="{{ route('conteudo.materiais.edit', $material) }}" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Editar
                            </a>
                            <form method="POST" action="{{ route('conteudo.materiais.destroy', $material) }}"
                                  onsubmit="return confirmDelete(this, { title: 'Excluir material', message: 'O material &quot;{{ addslashes(\Illuminate\Support\Str::limit($material->title, 40)) }}&quot; será removido permanentemente. Esta ação não pode ser desfeita.' })">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if($materials->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border)">
        {{ $materials->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
