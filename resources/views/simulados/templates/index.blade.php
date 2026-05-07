@extends('layouts.app')

@section('title', 'Templates de E-mail — Simulados')
@section('page-title', 'Templates de E-mail')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">Templates de E-mail</h1>
            <p class="page-subtitle">Gerencie conteúdos HTML com variáveis dinâmicas para resultados.</p>
        </div>
        <a href="{{ route('simulados.templates.create') }}" class="btn btn-primary btn-sm">Novo template</a>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Assunto</th>
                    <th>Status</th>
                    <th style="text-align:right">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td>{{ $template->subject }}</td>
                    <td><span class="badge {{ $template->active ? 'badge-success' : 'badge-neutral' }}">{{ $template->active ? 'Ativo' : 'Inativo' }}</span></td>
                    <td style="text-align:right"><a class="btn btn-secondary btn-sm" href="{{ route('simulados.templates.edit', $template) }}">Editar</a></td>
                </tr>
                @empty
                <tr><td colspan="4" style="text-align:center;padding:32px;color:var(--text-muted)">Nenhum template cadastrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px">
        {{ $templates->links() }}
    </div>
</div>
@endsection
