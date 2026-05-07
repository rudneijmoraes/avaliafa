@extends('layouts.app')
@section('title', 'Moodle — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div x-data="moodleSync()" class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Sincronizacao Moodle</h1>
            <p class="page-subtitle">Gerencie o envio de notas para o Moodle. Reenvie manualmente quando necessario.</p>
        </div>
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

{{-- Status cards --}}
<div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-bottom:24px">
    <div class="card">
        <div class="card-body" style="text-align:center;padding:20px">
            <div style="font-size:2rem;font-weight:800;color:var(--color-success)">{{ $totalSynced }}</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Sincronizadas</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:20px">
            <div style="font-size:2rem;font-weight:800;color:{{ $totalPending > 0 ? '#EA580C' : 'var(--text-secondary)' }}">{{ $totalPending }}</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Pendentes</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body" style="text-align:center;padding:20px">
            <div style="font-size:2rem;font-weight:800;color:var(--text-primary)">{{ $totalSynced + $totalPending }}</div>
            <div style="font-size:0.8125rem;color:var(--text-secondary);margin-top:4px">Total com nota</div>
        </div>
    </div>
</div>

{{-- Pending sessions --}}
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <div class="card-title">Sessoes pendentes</div>
            <div class="card-subtitle">Notas publicadas que ainda nao foram enviadas ao Moodle.</div>
        </div>
        @if($totalPending > 0)
        <button class="btn btn-primary" @click="resyncAll()" :disabled="syncing" style="white-space:nowrap;display:inline-flex;align-items:center;gap:6px">
            <svg x-show="!syncing" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            <svg x-show="syncing" x-cloak width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15A9 9 0 1 1 21 6.35"/></svg>
            <span x-text="syncing ? 'Reenviando...' : 'Reenviar todas'"></span>
        </button>
        @endif
    </div>
    <div class="card-body" style="padding:0">
        @if($totalPending === 0)
        <div style="padding:48px 20px;text-align:center;color:var(--text-secondary)">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;opacity:0.4"><polyline points="20 6 9 17 4 12"/></svg>
            <div style="font-size:0.9375rem;font-weight:600">Tudo sincronizado!</div>
            <div style="font-size:0.8125rem;margin-top:4px">Nenhuma nota pendente de envio ao Moodle.</div>
        </div>
        @else
        <div style="overflow-x:auto">
            <table class="data-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="padding:10px 16px">Estudante</th>
                        <th style="padding:10px 16px">Prova</th>
                        <th style="padding:10px 16px">Nota</th>
                        <th style="padding:10px 16px">Ultimo erro</th>
                        <th style="padding:10px 16px;text-align:right">Acao</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $session)
                    <tr x-data="{ status: 'pending', error: '' }" :class="{ 'opacity-50': status === 'synced' }">
                        <td style="padding:10px 16px">
                            <div style="font-weight:600;font-size:0.875rem">{{ $session->student?->first_name }} {{ $session->student?->last_name }}</div>
                            <div style="font-size:0.75rem;color:var(--text-secondary)">{{ $session->student?->email }}</div>
                        </td>
                        <td style="padding:10px 16px;font-size:0.875rem">{{ $session->exam?->title ?? '—' }}</td>
                        <td style="padding:10px 16px">
                            <span style="font-weight:700;font-size:0.875rem">{{ number_format($session->final_score, 2, ',', '') }}</span>
                        </td>
                        <td style="padding:10px 16px;max-width:280px">
                            @php $lastLog = $session->moodleSyncLogs->first(); @endphp
                            @if($lastLog && $lastLog->error_message)
                                <div style="font-size:0.75rem;color:var(--color-error);word-break:break-word" title="{{ $lastLog->error_message }}">
                                    {{ \Illuminate\Support\Str::limit($lastLog->error_message, 80) }}
                                </div>
                                <div style="font-size:0.6875rem;color:var(--text-secondary)">{{ $lastLog->created_at->diffForHumans() }}</div>
                            @else
                                <span style="font-size:0.75rem;color:var(--text-secondary)">—</span>
                            @endif
                        </td>
                        <td style="padding:10px 16px;text-align:right">
                            <span x-show="status === 'synced'" x-cloak style="color:var(--color-success);font-size:0.8125rem;font-weight:600">Enviada</span>
                            <div x-show="status === 'error'" x-cloak>
                                <span style="color:var(--color-error);font-size:0.75rem" x-text="error"></span>
                                <button class="btn btn-ghost" style="font-size:0.75rem;padding:4px 8px;margin-left:4px" @click="resyncOne({{ $session->id }}, $el.closest('tr'))">Tentar</button>
                            </div>
                            <svg x-show="status === 'loading'" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-secondary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15A9 9 0 1 1 21 6.35"/></svg>
                            <button x-show="status === 'pending'" class="btn btn-ghost" style="font-size:0.8125rem;padding:6px 12px;display:inline-flex;align-items:center;gap:4px" @click="resyncOne({{ $session->id }}, $el.closest('tr'))">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                Reenviar
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Result banner --}}
<template x-if="resultMessage">
    <div class="alert" :class="resultSuccess ? 'alert-success' : 'alert-error'" style="margin-top:16px" x-text="resultMessage"></div>
</template>

</div>{{-- close x-data --}}

<style>
[x-cloak] { display: none !important; }
@keyframes spin { to { transform: rotate(360deg) } }
</style>

<script>
function moodleSync() {
    return {
        syncing: false,
        resultMessage: '',
        resultSuccess: false,

        async resyncOne(sessionId, trEl) {
            const scope = Alpine.$data(trEl);
            scope.status = 'loading';
            scope.error = '';

            try {
                const res = await fetch(`/diagnostico/moodle/${sessionId}/resync`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    scope.status = 'synced';
                } else {
                    scope.status = 'error';
                    scope.error = data.message || 'Falha no sync';
                }
            } catch (e) {
                scope.status = 'error';
                scope.error = 'Erro de rede';
            }
        },

        async resyncAll() {
            this.syncing = true;
            this.resultMessage = '';

            try {
                const res = await fetch('{{ route("configuracoes.moodle.resyncAll") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                this.resultSuccess = data.failed === 0;
                this.resultMessage = `${data.success} enviada(s) com sucesso` + (data.failed > 0 ? `, ${data.failed} falharam` : '');

                if (data.success > 0) {
                    setTimeout(() => location.reload(), 2000);
                }
            } catch (e) {
                this.resultSuccess = false;
                this.resultMessage = 'Erro de rede ao reenviar';
            } finally {
                this.syncing = false;
            }
        },
    };
}
</script>
@endsection
