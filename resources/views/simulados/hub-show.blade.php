@extends('layouts.app')

@section('title', ($hubSummary['name'] ?? 'Ciclo de Simulados') . ' — Minha Área')
@section('page-title', 'Ciclo de Simulados')

@section('content')
<div class="page-header">
    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $hubSummary['name'] ?? 'Ciclo de Simulados' }}</h1>
            <p class="page-subtitle">{{ $hubSummary['description'] ?: 'Acompanhe os simulados pendentes, concluídos e seu percentual de acerto neste ciclo.' }}</p>
        </div>
        <a href="{{ route('simulados.minha-area') }}" class="btn btn-secondary">Voltar</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:16px">
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Total</div><div style="font-size:1.4rem;font-weight:800;color:var(--text-primary)">{{ $hubSummary['total'] ?? 0 }}</div></div></div>
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Concluídos</div><div style="font-size:1.4rem;font-weight:800;color:var(--text-primary)">{{ $hubSummary['completed'] ?? 0 }}</div></div></div>
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Pendentes</div><div style="font-size:1.4rem;font-weight:800;color:var(--text-primary)">{{ $hubSummary['pending'] ?? 0 }}</div></div></div>
    <div class="card"><div style="padding:18px"><div style="font-size:.75rem;color:var(--text-muted)">Média de acerto</div><div style="font-size:1.4rem;font-weight:800;color:var(--text-primary)">{{ number_format((float) ($hubSummary['average_percentage'] ?? 0), 1, ',', '.') }}%</div></div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:18px;border-bottom:1px solid var(--surface-border);font-weight:700;color:var(--text-primary)">Simulados pendentes ou em andamento</div>
    <div style="padding:18px;display:grid;gap:14px">
        @forelse($pendingRegistrations as $registration)
        <div style="padding:16px;border:1px solid var(--surface-border);border-radius:14px;background:var(--surface-bg);display:grid;gap:12px">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
                <div>
                    <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ $registration->simulado?->name ?? '-' }}</div>
                    <div style="font-size:.8rem;color:var(--text-secondary)">{{ $registration->simulado?->description ?: 'Simulado disponível neste ciclo.' }}</div>
                </div>
                <span class="badge badge-neutral">{{ strtoupper($registration->status) }}</span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <span class="badge badge-primary">Tentativas {{ (int) ($attemptsByRegistrationId[$registration->id] ?? 0) }}</span>
                <span class="badge badge-neutral">% acerto {{ number_format((float) $registration->percentage_correct, 1, ',', '.') }}%</span>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if($registration->simulado?->status === 'active' && !($retryBlockedByRegistrationId[$registration->id] ?? false))
                <form method="POST" action="{{ route('simulados.retry', $registration->simulado) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Iniciar / Continuar</button>
                </form>
                @elseif($retryBlockedByRegistrationId[$registration->id] ?? false)
                <span class="badge badge-neutral">Limite de tentativas atingido</span>
                @endif

                @if($registration->exam_session_id)
                <a class="btn btn-secondary btn-sm" href="{{ route('exam.result', $registration->exam_session_id) }}">Ver resultado</a>
                @endif
            </div>
        </div>
        @empty
        <div style="padding:24px;text-align:center;color:var(--text-muted)">Nenhum simulado pendente neste ciclo.</div>
        @endforelse
    </div>
</div>

<div class="card">
    <div style="padding:18px;border-bottom:1px solid var(--surface-border);font-weight:700;color:var(--text-primary)">Simulados concluídos</div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Simulado</th>
                    <th>Status</th>
                    <th>Tentativas</th>
                    <th>Nota</th>
                    <th>% Acertos</th>
                    <th>Concluído em</th>
                    <th style="text-align:right">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($completedRegistrations as $registration)
                <tr>
                    <td>{{ $registration->simulado?->name ?? '-' }}</td>
                    <td><span class="badge badge-neutral">{{ strtoupper($registration->status) }}</span></td>
                    <td>{{ (int) ($attemptsByRegistrationId[$registration->id] ?? 0) }}</td>
                    <td>{{ number_format((float) $registration->final_score, 2, ',', '.') }}</td>
                    <td>{{ number_format((float) $registration->percentage_correct, 2, ',', '.') }}%</td>
                    <td>{{ optional($registration->completed_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td style="text-align:right;white-space:nowrap">
                        @if($registration->simulado?->status === 'active' && !($retryBlockedByRegistrationId[$registration->id] ?? false))
                        <form method="POST" action="{{ route('simulados.retry', $registration->simulado) }}" style="display:inline-block;margin-right:6px">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm">Refazer</button>
                        </form>
                        @elseif($retryBlockedByRegistrationId[$registration->id] ?? false)
                        <span class="badge badge-neutral" style="margin-right:6px">Limite atingido</span>
                        @endif
                        @if($registration->exam_session_id)
                        <a class="btn btn-secondary btn-sm" href="{{ route('exam.result', $registration->exam_session_id) }}" style="margin-right:6px">Ver resultado</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('simulados.review-pdf', $registration->simulado) }}" target="_blank">Ver respostas (PDF)</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted)">Nenhum simulado concluído ainda neste ciclo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
