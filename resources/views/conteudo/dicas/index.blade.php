@extends('layouts.app')

@section('title', 'Dicas do Professor — AvaliaFA')
@section('page-title', 'Dicas do Professor')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">Dicas do Professor</h1>
            <p class="page-subtitle">Gerencie os vídeos e materiais de apoio exibidos no portal do aluno.</p>
        </div>
        <a href="{{ route('conteudo.dicas.create') }}" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nova Dica
        </a>
    </div>
</div>

{{-- Filtros --}}
<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('conteudo.dicas.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
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
            <a href="{{ route('conteudo.dicas.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabela --}}
<div class="card">
    <div style="overflow-x:auto">
        @if($tips->isEmpty())
        <div style="padding:48px 24px;text-align:center">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);margin:0 auto 12px;display:block"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
            <p style="color:var(--text-muted);font-size:0.9rem;margin:0">Nenhuma dica cadastrada.</p>
            <a href="{{ route('conteudo.dicas.create') }}" class="btn btn-primary" style="margin-top:16px;display:inline-flex">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Criar primeira dica
            </a>
        </div>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px">Ordem</th>
                    <th>Título</th>
                    <th>Setores</th>
                    <th style="width:100px">Tipo</th>
                    <th style="width:80px">Ativo</th>
                    <th>Criado por</th>
                    <th style="text-align:right;width:120px">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tips as $tip)
                <tr>
                    <td>
                        <span style="font-family:'JetBrains Mono',monospace;font-size:0.82rem;color:var(--text-secondary)">
                            {{ $tip->order }}
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary)">
                            {{ \Illuminate\Support\Str::limit($tip->title, 60) }}
                        </div>
                    </td>
                    <td>
                        @php
                            $systemNames = $tip->systems->pluck('name');
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
                        @php
                            $isYoutube = str_contains($tip->video_url ?? '', 'youtube.com') || str_contains($tip->video_url ?? '', 'youtu.be');
                            $isVimeo   = str_contains($tip->video_url ?? '', 'vimeo.com');
                        @endphp
                        @if($isYoutube)
                            <span class="badge badge-primary">YouTube</span>
                        @elseif($isVimeo)
                            <span class="badge" style="background:#1e3a5f;color:#93c5fd">Vimeo</span>
                        @else
                            <span class="badge badge-neutral">Outro</span>
                        @endif
                    </td>
                    <td>
                        @if($tip->active)
                            <span class="badge badge-success">Sim</span>
                        @else
                            <span class="badge badge-neutral">Não</span>
                        @endif
                    </td>
                    <td style="font-size:0.84rem;color:var(--text-secondary)">
                        {{ $tip->creator->name ?? '—' }}
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:6px;align-items:center">
                            <a href="{{ route('conteudo.dicas.edit', $tip) }}" class="btn btn-secondary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Editar
                            </a>
                            <form method="POST" action="{{ route('conteudo.dicas.destroy', $tip) }}"
                                  onsubmit="return confirmDelete(this, { title: 'Excluir dica', message: 'A dica &quot;{{ addslashes(\Illuminate\Support\Str::limit($tip->title, 40)) }}&quot; será removida permanentemente. Esta ação não pode ser desfeita.' })">
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

    @if($tips->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border)">
        {{ $tips->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
