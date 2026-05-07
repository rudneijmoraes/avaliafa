@extends('layouts.app')

@section('title', 'Operações — AvaliaFA')
@section('page-title', 'Operações')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Observabilidade Operacional</h1>
            <p class="page-subtitle">Saúde de integrações Moodle e webhooks com retentativas em tempo real.</p>
        </div>
        <form method="GET" action="{{ route('monitor.index') }}" style="display:flex;gap:8px;align-items:center">
            <select name="hours" class="input" style="height:36px;padding:6px 12px;min-width:120px">
                @foreach([6 => 'Últimas 6h', 24 => 'Últimas 24h', 72 => 'Últimas 72h'] as $value => $label)
                    <option value="{{ $value }}" {{ (int) request('hours', $stats['hours']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Atualizar</button>
        </form>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:16px">
    <div class="card" style="padding:14px">
        <div style="font-size:0.75rem;color:var(--text-secondary)">Webhook sucesso</div>
        <div style="font-size:1.35rem;font-weight:800;color:var(--text-primary)">{{ number_format($stats['webhook_success_rate'], 1, ',', '.') }}%</div>
        <div style="font-size:0.76rem;color:var(--text-muted)">{{ $stats['webhook_delivered'] }} entregues de {{ $stats['webhook_total'] }}</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:0.75rem;color:var(--text-secondary)">Moodle sucesso</div>
        <div style="font-size:1.35rem;font-weight:800;color:var(--text-primary)">{{ number_format($stats['moodle_success_rate'], 1, ',', '.') }}%</div>
        <div style="font-size:0.76rem;color:var(--text-muted)">{{ $stats['moodle_success'] }} sincronizações de {{ $stats['moodle_total'] }}</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:0.75rem;color:var(--text-secondary)">Webhook falhas</div>
        <div style="font-size:1.35rem;font-weight:800;color:{{ $stats['webhook_failed'] > 0 ? 'var(--color-danger)' : 'var(--text-primary)' }}">{{ $stats['webhook_failed'] }}</div>
        <div style="font-size:0.76rem;color:var(--text-muted)">Inclui failed + retrying</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:0.75rem;color:var(--text-secondary)">Moodle falhas</div>
        <div style="font-size:1.35rem;font-weight:800;color:{{ $stats['moodle_failed'] > 0 ? 'var(--color-danger)' : 'var(--text-primary)' }}">{{ $stats['moodle_failed'] }}</div>
        <div style="font-size:0.76rem;color:var(--text-muted)">Inclui failed + retrying</div>
    </div>
    <div class="card" style="padding:14px">
        <div style="font-size:0.75rem;color:var(--text-secondary)">Fila retentativa</div>
        <div style="font-size:1.35rem;font-weight:800;color:var(--text-primary)">{{ $stats['webhook_retrying'] + $stats['moodle_retrying'] }}</div>
        <div style="font-size:0.76rem;color:var(--text-muted)">Webhook {{ $stats['webhook_retrying'] }} · Moodle {{ $stats['moodle_retrying'] }}</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Saúde por Sistema</div>
            <div class="card-subtitle">Consolidação para a janela selecionada.</div>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sistema</th>
                    <th>Webhook</th>
                    <th>Moodle</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($systemHealth as $system)
                @php
                    $totalOps = (int) $system->webhook_total_count + (int) $system->moodle_total_count;
                    $totalFails = (int) $system->webhook_failed_count + (int) $system->moodle_failed_count;
                    $ratio = $totalOps > 0 ? ($totalFails / $totalOps) * 100 : 0;
                    $status = $ratio >= 20 ? 'Crítico' : ($ratio >= 8 ? 'Atenção' : 'Saudável');
                    $badge = $ratio >= 20 ? 'badge-danger' : ($ratio >= 8 ? 'badge-warning' : 'badge-success');
                @endphp
                <tr>
                    <td style="font-weight:700;color:var(--text-primary)">{{ $system->name }}</td>
                    <td>{{ $system->webhook_failed_count }}/{{ $system->webhook_total_count }}</td>
                    <td>{{ $system->moodle_failed_count }}/{{ $system->moodle_total_count }}</td>
                    <td><span class="badge {{ $badge }}">{{ $status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align:center;padding:22px;color:var(--text-muted)">Sem dados para o período selecionado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Últimas falhas de Webhook</div>
                <div class="card-subtitle">Registros mais recentes em erro ou retentativa.</div>
            </div>
        </div>
        <div style="padding:0 16px 16px">
            <div style="display:grid;gap:8px">
                @forelse($recentWebhookFailures as $log)
                <div style="border:1px solid var(--surface-border);border-radius:10px;padding:10px 12px">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                        <strong style="font-size:0.82rem">{{ $log->event }}</strong>
                        <span class="badge {{ $log->status === 'failed' ? 'badge-danger' : 'badge-warning' }}">{{ strtoupper($log->status) }}</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px">{{ $log->clientSystem?->name ?? 'Sistema removido' }} · tentativa {{ $log->retry_count }}</div>
                    <div style="font-size:0.72rem;color:var(--text-muted)">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
                @empty
                <div style="color:var(--text-muted);font-size:0.83rem">Nenhuma falha de webhook encontrada.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Últimas falhas de Moodle</div>
                <div class="card-subtitle">Sincronizações com erro ou pendentes de nova tentativa.</div>
            </div>
        </div>
        <div style="padding:0 16px 16px">
            <div style="display:grid;gap:8px">
                @forelse($recentMoodleFailures as $log)
                <div style="border:1px solid var(--surface-border);border-radius:10px;padding:10px 12px">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                        <strong style="font-size:0.82rem">Sessão #{{ $log->session_id }}</strong>
                        <span class="badge {{ $log->status === 'failed' ? 'badge-danger' : 'badge-warning' }}">{{ strtoupper($log->status) }}</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px">{{ $log->clientSystem?->name ?? 'Sistema removido' }} · tentativa {{ $log->retry_count }}</div>
                    <div style="font-size:0.72rem;color:var(--text-muted)">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                </div>
                @empty
                <div style="color:var(--text-muted);font-size:0.83rem">Nenhuma falha de sincronização encontrada.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
