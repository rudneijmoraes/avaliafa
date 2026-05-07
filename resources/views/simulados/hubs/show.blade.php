@extends('layouts.app')

@section('title', ($hub->name ?? 'Hub').' — Hubs de Simulados')
@section('page-title', 'Hub de Simulados')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $hub->name }}</h1>
            <p class="page-subtitle">{{ $hub->description ?: 'Hub para concentrar vários simulados em um único link público.' }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('simulados.hubs.index') }}" class="btn btn-secondary btn-sm">Voltar</a>
            <a href="{{ route('simulados.hubs.edit', $hub) }}" class="btn btn-primary btn-sm">Editar hub</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:16px">
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Slug público</div><div style="font-size:1.15rem;font-weight:800;color:var(--text-primary)">{{ $hub->slug }}</div></div></div>
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Status</div><div style="font-size:1.15rem;font-weight:800;color:var(--text-primary)">{{ ucfirst($hub->status) }}</div></div></div>
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Simulados vinculados</div><div style="font-size:1.15rem;font-weight:800;color:var(--text-primary)">{{ $hub->simulados->count() }}</div></div></div>
</div>

<div class="card">
    <div style="padding:16px 20px;border-bottom:1px solid var(--surface-border);font-weight:700;color:var(--text-primary)">
        Simulados deste hub
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Simulado</th>
                    <th>Status</th>
                    <th>Inscrições</th>
                    <th style="text-align:right">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($hub->simulados as $simulado)
                <tr>
                    <td>{{ (int) $simulado->hub_order }}</td>
                    <td>
                        <div style="font-weight:700">{{ $simulado->name }}</div>
                        <div style="font-size:.75rem;color:var(--text-muted)">{{ $simulado->slug }}</div>
                    </td>
                    <td><span class="badge {{ $simulado->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ ucfirst($simulado->status) }}</span></td>
                    <td>{{ (int) $simulado->registrations->count() }}</td>
                    <td style="text-align:right">
                        <a href="{{ route('simulados.show', $simulado) }}" class="btn btn-secondary btn-sm">Abrir simulado</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;padding:28px;color:var(--text-muted)">Nenhum simulado vinculado a este hub ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
