@extends('layouts.app')

@section('title', 'Minha Área de Simulados')
@section('page-title', 'Minha Área de Simulados')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Minha Área de Simulados</h1>
        <p class="page-subtitle">Acompanhe seu histórico de inscrições, tentativas e desempenho.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-bottom:16px">
    @forelse($hubSummaries ?? [] as $hubSummary)
    <a href="{{ route('simulados.minha-area.hub', $hubSummary['slug']) }}" class="card" style="text-decoration:none;color:inherit">
        <div style="padding:18px;display:grid;gap:12px">
            <div>
                <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ $hubSummary['name'] }}</div>
                <div style="font-size:0.8rem;color:var(--text-secondary);margin-top:4px">
                    {{ $hubSummary['completed'] }} concluído(s) · {{ $hubSummary['pending'] }} pendente(s)
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px">
                <div style="padding:10px 12px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.72rem;color:var(--text-muted)">Progresso</div>
                    <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ number_format((float) $hubSummary['progress_percentage'], 1, ',', '.') }}%</div>
                </div>
                <div style="padding:10px 12px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.72rem;color:var(--text-muted)">Média</div>
                    <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ number_format((float) $hubSummary['average_percentage'], 1, ',', '.') }}%</div>
                </div>
                <div style="padding:10px 12px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.72rem;color:var(--text-muted)">Melhor</div>
                    <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ number_format((float) $hubSummary['best_percentage'], 1, ',', '.') }}%</div>
                </div>
            </div>
            <div style="height:8px;border-radius:999px;background:#E5E7EB;overflow:hidden">
                <div style="height:100%;background:linear-gradient(90deg,#2563EB,#10B981);width:{{ min(100, max(0, (float) $hubSummary['progress_percentage'])) }}%"></div>
            </div>
        </div>
    </a>
    @empty
    @endforelse
</div>

<div class="card">
    <div style="padding:14px 18px;border-bottom:1px solid var(--surface-border);font-weight:700;color:var(--text-primary)">
        Todos os Simulados
    </div>
    <div style="overflow-x:auto">
        <table class="data-table simulados-table">
            <thead>
                <tr>
                    <th>Simulado</th>
                    <th>Status</th>
                    <th>Tentativas</th>
                    <th>Nota</th>
                    <th>% Acertos</th>
                    <th>Concluído em</th>
                    <th style="text-align:right">Ação</th>
                    <th>Dica</th>
                </tr>
            </thead>
            <tbody>
                @forelse($simuladoRows as $row)
                @php
                    $registration = $row['registration'];
                    $simulado = $row['simulado'];
                    $tip = $tipsBySimuladoId[$simulado?->id] ?? null;
                    $sessionStatus = $registration?->examSession?->status ?? null;

                    if ($row['type'] === 'available') {
                        $displayStatus = 'available';
                    } elseif (! is_null($registration?->completed_at) || in_array($sessionStatus, ['submitted', 'graded'], true) || in_array($registration?->status, ['completed', 'email_sent'], true)) {
                        $displayStatus = 'completed';
                    } elseif ($sessionStatus === 'in_progress') {
                        $displayStatus = 'in_progress';
                    } else {
                        $displayStatus = 'registered';
                    }

                    $displayLabel = [
                        'available'   => 'Disponível',
                        'registered'  => 'Disponível',
                        'in_progress' => 'Em andamento',
                        'completed'   => 'Concluído',
                    ];
                    $displayBadge = [
                        'available'   => 'badge-neutral',
                        'registered'  => 'badge-neutral',
                        'in_progress' => 'badge-info',
                        'completed'   => 'badge-success',
                    ];
                @endphp
                <tr>
                    <td>{{ $simulado?->name ?? '-' }}</td>
                    <td>
                        <span class="badge {{ $displayBadge[$displayStatus] ?? 'badge-neutral' }}">
                            {{ $displayLabel[$displayStatus] ?? 'Disponível' }}
                        </span>
                    </td>
                    <td>{{ $registration ? (int) ($attemptsByRegistrationId[$registration->id] ?? 0) : '-' }}</td>
                    <td>{{ $registration && $registration->completed_at ? number_format((float) $registration->final_score, 2, ',', '.') : '-' }}</td>
                    <td>{{ $registration && $registration->completed_at ? number_format((float) $registration->percentage_correct, 2, ',', '.') . '%' : '-' }}</td>
                    <td>{{ $registration ? (optional($registration->completed_at)->format('d/m/Y H:i') ?? '-') : '-' }}</td>
                    <td style="text-align:right;white-space:nowrap">
                        @if($registration && is_null($registration->completed_at) && $simulado?->status === 'active' && $registration->examSession?->status === 'in_progress')
                            @php
                                $simSlug = $simulado?->hub?->slug
                                    ?: trim((string) data_get($simulado?->settings ?? [], 'public_hub_slug', $simulado?->slug));
                                $simSlug = $simSlug !== '' ? $simSlug : $simulado?->slug;
                                $simQuery = $simulado?->slug ? '?simulado=' . $simulado->slug . '&from=painel' : '?from=painel';
                            @endphp
                            <a class="btn btn-primary btn-sm" href="{{ route('simulados.public.inscricao', $simSlug) . $simQuery }}" style="margin-right:6px">Continuar</a>
                        @endif

                        @if($registration && ! is_null($registration->completed_at))
                            @if($simulado?->status === 'active' && !($retryBlockedByRegistrationId[$registration->id] ?? false))
                                <form method="POST" action="{{ route('simulados.retry', $simulado) }}" style="display:inline-block;margin-right:6px">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">Fazer novamente</button>
                                </form>
                            @elseif($retryBlockedByRegistrationId[$registration->id] ?? false)
                                <span class="badge badge-neutral" style="margin-right:6px">Limite atingido</span>
                            @endif

                            @if($registration->exam_session_id)
                                <a class="btn btn-secondary btn-sm" href="{{ route('exam.result', $registration->exam_session_id) }}" style="margin-right:6px">Ver resultado</a>
                                <a class="btn btn-secondary btn-sm" href="{{ route('simulados.review-pdf', $simulado) }}" target="_blank">Baixar Gabarito</a>
                            @endif
                        @endif

                        @if($registration && is_null($registration->completed_at) && $simulado?->status === 'active' && $registration->examSession?->status !== 'in_progress')
                            @php
                                $entrySlug = $simulado?->hub?->slug
                                    ?: trim((string) data_get($simulado?->settings ?? [], 'public_hub_slug', $simulado?->slug));
                                $entrySlug = $entrySlug !== '' ? $entrySlug : $simulado?->slug;
                                $entryQuery = '?simulado=' . $simulado->slug . '&from=painel';
                            @endphp
                            <a class="btn btn-primary btn-sm" href="{{ route('simulados.public.inscricao', $entrySlug) . $entryQuery }}" style="margin-right:6px">Iniciar simulado agora</a>
                        @endif

                        @if($row['type'] === 'available')
                            @php
                                $entrySlug = $simulado?->hub?->slug
                                    ?: trim((string) data_get($simulado?->settings ?? [], 'public_hub_slug', $simulado?->slug));
                                $entrySlug = $entrySlug !== '' ? $entrySlug : $simulado?->slug;
                                $entryQuery = '?simulado=' . $simulado->slug . '&from=painel';
                            @endphp
                            <a class="btn btn-primary btn-sm" href="{{ route('simulados.public.inscricao', $entrySlug) . $entryQuery }}">Iniciar simulado agora</a>
                        @endif
                    </td>
                    <td style="white-space:nowrap">
                        @if($tip && $tip->embed_url)
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm"
                            style="color:var(--color-primary-600);border-color:var(--color-primary-600)"
                            onclick="openTipModal('tip-modal-{{ $simulado->id }}')"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                            Dica do professor
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                @endforelse

                @if(($simuladoRows ?? collect())->isEmpty())
                <tr><td colspan="8" style="text-align:center;padding:28px;color:var(--text-muted)">Nenhum simulado disponível no momento.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px;font-size:.82rem;color:var(--text-muted)">
        Total: {{ ($simuladoRows ?? collect())->count() }} simulado(s)
    </div>
</div>
{{-- Modais de dica do professor --}}
@foreach(array_merge($registrations->all(), ($availableSimulados ?? collect())->all()) as $item)
@php
    $simId  = $item instanceof \App\Models\SimuladoRegistration ? $item->simulado_id : $item->id;
    $simNome = $item instanceof \App\Models\SimuladoRegistration ? ($item->simulado?->name ?? '') : $item->name;
    $modalTip = $tipsBySimuladoId[$simId] ?? null;
@endphp
@if($modalTip && $modalTip->embed_url)
<div id="tip-modal-{{ $simId }}"
     style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);align-items:center;justify-content:center;padding:20px"
     onclick="if(event.target===this) closeTipModal('tip-modal-{{ $simId }}')">
    <div style="background:var(--surface-card);border-radius:16px;width:min(720px,100%);overflow:hidden;box-shadow:0 32px 80px rgba(0,0,0,0.4)">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--surface-border)">
            <div>
                <div style="font-size:0.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--color-primary-600);margin-bottom:2px">{{ $simNome }}</div>
                <div style="font-weight:700;color:var(--text-primary);font-size:0.95rem">{{ $modalTip->title }}</div>
            </div>
            <button type="button" onclick="closeTipModal('tip-modal-{{ $simId }}')" style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:4px">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden">
            <iframe
                id="tip-iframe-{{ $simId }}"
                src=""
                data-src="{{ $modalTip->embed_url }}"
                style="position:absolute;top:0;left:0;width:100%;height:100%;border:none"
                allowfullscreen
                referrerpolicy="strict-origin-when-cross-origin"
            ></iframe>
        </div>
        @if($modalTip->description)
        <div style="padding:16px 20px;font-size:0.875rem;color:var(--text-secondary);line-height:1.6;border-top:1px solid var(--surface-border)">
            {!! $modalTip->description !!}
        </div>
        @endif
    </div>
</div>
@endif
@endforeach

<style>
@media (max-width: 640px) {
    .simulados-table th:nth-child(2),
    .simulados-table td:nth-child(2),
    .simulados-table th:nth-child(3),
    .simulados-table td:nth-child(3),
    .simulados-table th:nth-child(4),
    .simulados-table td:nth-child(4),
    .simulados-table th:nth-child(5),
    .simulados-table td:nth-child(5),
    .simulados-table th:nth-child(6),
    .simulados-table td:nth-child(6) {
        display: none;
    }
    .simulados-table th:nth-child(7),
    .simulados-table td:nth-child(7) {
        text-align: left;
    }
}
</style>
<script>
function openTipModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    const iframe = modal.querySelector('iframe');
    if (iframe && !iframe.src) iframe.src = iframe.dataset.src;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeTipModal(id) {
    const modal = document.getElementById(id);
    if (!modal) return;
    const iframe = modal.querySelector('iframe');
    if (iframe) iframe.src = '';
    modal.style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('[id^="tip-modal-"]').forEach(m => {
        if (m.style.display === 'flex') closeTipModal(m.id);
    });
});
</script>
@endsection
