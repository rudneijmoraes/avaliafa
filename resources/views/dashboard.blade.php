@extends('layouts.app')

@section('title', 'Dashboard — AvaliaFA')
@section('page-title', 'Dashboard')

@section('content')
<div class="page-header">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px">
        <div>
            <h1 class="page-title">
                Bem-vindo, {{ explode(' ', auth()->user()->name)[0] }}!
            </h1>
            <p class="page-subtitle">
                {{ now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                &mdash; Visão geral do sistema
            </p>
        </div>
        <div style="display:flex; gap:8px">
            <a href="{{ route('provas.create') }}" class="btn btn-ghost btn-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Nova Prova
            </a>
        </div>
    </div>
</div>

{{-- KPI Cards --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; margin-bottom:24px">

    {{-- Total de sessões --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(37,99,235,0.12),rgba(59,130,246,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div class="kpi-label">Sessões Totais</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value">{{ number_format($stats['total_sessions']) }}</div>
            <span class="kpi-badge kpi-badge-up">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                {{ $stats['sessions_today'] }} hoje
            </span>
        </div>
    </div>

    {{-- Tentativas de simulado --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(14,165,233,0.14),rgba(59,130,246,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0EA5E9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                </svg>
            </div>
            <div class="kpi-label">Tentativas de Simulado</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value" data-kpi-simulation-attempts="{{ $stats['simulation_attempts'] }}">{{ number_format($stats['simulation_attempts']) }}</div>
            <span class="kpi-badge" style="background:rgba(14,165,233,0.14);color:#0284C7">
                Repetições incluídas
            </span>
        </div>
    </div>

    {{-- Em andamento --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(245,158,11,0.12),rgba(251,191,36,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="kpi-label">Em Andamento</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value">{{ $stats['active_sessions'] }}</div>
            <span class="badge badge-warning">
                <span class="live-dot" style="width:6px;height:6px"></span>
                Ao vivo
            </span>
        </div>
    </div>

    {{-- Taxa de aprovação --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(16,185,129,0.12),rgba(52,211,153,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="kpi-label">Taxa de Aprovação</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value">{{ $stats['pass_rate'] }}%</div>
            <div style="height:5px;background:var(--surface-border);border-radius:9999px;overflow:hidden;min-width:60px">
                <div style="height:100%;width:{{ $stats['pass_rate'] }}%;background:linear-gradient(90deg,#10B981,#34D399);border-radius:9999px;transition:width 1s ease"></div>
            </div>
        </div>
    </div>

    {{-- Total de provas --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(139,92,246,0.12),rgba(167,139,250,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
            </div>
            <div class="kpi-label">Provas Cadastradas</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value">{{ $stats['total_exams'] }}</div>
            <span class="kpi-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6">
                {{ $stats['total_students'] }} estudantes
            </span>
        </div>
    </div>

    {{-- Total de questões --}}
    <div class="kpi-card card-enter">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
            <div class="kpi-icon" style="background: linear-gradient(135deg,rgba(236,72,153,0.12),rgba(244,114,182,0.08));flex-shrink:0">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="kpi-label">Questões Cadastradas</div>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
            <div class="kpi-value">{{ number_format($stats['total_questions']) }}</div>
            <a href="{{ route('questoes.index') }}" class="kpi-badge" style="background:rgba(236,72,153,0.12);color:#EC4899;text-decoration:none;font-size:0.7rem">
                Ver todas
            </a>
        </div>
    </div>

</div>

@if($isSuperAdmin && $institutionStats)
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <div>
            <div class="card-title">Visão Institucional</div>
            <div class="card-subtitle">Panorama consolidado entre todos os sistemas.</div>
        </div>
    </div>
    <div class="card-body" style="display:grid;gap:16px">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px">
            <div style="padding:12px;border:1px solid var(--surface-border);border-radius:10px;background:var(--surface-bg)">
                <div style="font-size:0.76rem;color:var(--text-secondary)">Sistemas ativos</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--text-primary)">{{ $institutionStats['active_systems'] }}/{{ $institutionStats['total_systems'] }}</div>
            </div>
            <div style="padding:12px;border:1px solid var(--surface-border);border-radius:10px;background:var(--surface-bg)">
                <div style="font-size:0.76rem;color:var(--text-secondary)">Certificados válidos</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--text-primary)">{{ number_format($institutionStats['valid_certificates']) }}</div>
            </div>
            <div style="padding:12px;border:1px solid var(--surface-border);border-radius:10px;background:var(--surface-bg)">
                <div style="font-size:0.76rem;color:var(--text-secondary)">Falhas webhook (24h)</div>
                <div style="font-size:1.2rem;font-weight:800;color:{{ $institutionStats['webhook_failures_24h'] > 0 ? 'var(--color-danger)' : 'var(--text-primary)' }}">{{ $institutionStats['webhook_failures_24h'] }}</div>
            </div>
            <div style="padding:12px;border:1px solid var(--surface-border);border-radius:10px;background:var(--surface-bg)">
                <div style="font-size:0.76rem;color:var(--text-secondary)">Falhas Moodle (24h)</div>
                <div style="font-size:1.2rem;font-weight:800;color:{{ $institutionStats['moodle_failures_24h'] > 0 ? 'var(--color-danger)' : 'var(--text-primary)' }}">{{ $institutionStats['moodle_failures_24h'] }}</div>
            </div>
            <div style="padding:12px;border:1px solid var(--surface-border);border-radius:10px;background:var(--surface-bg)">
                <div style="font-size:0.76rem;color:var(--text-secondary)">Risco médio global</div>
                <div style="font-size:1.2rem;font-weight:800;color:var(--text-primary)">{{ number_format($institutionStats['average_risk'], 1, ',', '.') }}%</div>
            </div>
        </div>

        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Sistema</th>
                        <th>Status</th>
                        <th>Estudantes</th>
                        <th>Provas</th>
                        <th>Sessões</th>
                        <th>Taxa aprovação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($systemRanking as $system)
                    <tr>
                        <td style="font-weight:600;color:var(--text-primary)">{{ $system['name'] }}</td>
                        <td>
                            <span class="badge {{ $system['active'] ? 'badge-success' : 'badge-neutral' }}">{{ $system['active'] ? 'Ativo' : 'Inativo' }}</span>
                        </td>
                        <td>{{ number_format($system['students_count']) }}</td>
                        <td>{{ number_format($system['exams_count']) }}</td>
                        <td>{{ number_format($system['sessions_count']) }}</td>
                        <td>{{ number_format($system['pass_rate'], 1, ',', '.') }}%</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--text-muted);padding:24px 10px">Sem dados institucionais disponíveis.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Tabela de sessões recentes + widget lateral --}}
<div style="display:grid; grid-template-columns:minmax(0,1fr) minmax(280px,340px); gap:16px; align-items:start" class="dashboard-bottom-grid">

    {{-- Tabela --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Sessões Recentes</div>
                <div class="card-subtitle">Últimas 10 atividades de prova</div>
            </div>
            <a href="{{ route('relatorios.index') }}" class="btn btn-ghost btn-sm">Ver tudo</a>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Estudante</th>
                        <th>Prova</th>
                        <th>Status</th>
                        <th>Nota</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSessions as $session)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                @if($session->student?->hasProfilePhoto())
                                <img src="{{ $session->student->profilePhotoUrl() }}" alt="{{ $session->student->name }}"
                                     style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0">
                                @else
                                <div class="avatar" style="width:30px;height:30px;font-size:0.7rem">
                                    {{ mb_strtoupper(mb_substr($session->student?->name ?? '?', 0, 2)) }}
                                </div>
                                @endif
                                <div>
                                    <div style="font-weight:600;font-size:0.875rem">{{ $session->student?->name ?? '—' }}</div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace">
                                        {{ $session->student?->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $session->student->cpf) : '' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td style="max-width:200px">
                            <div style="font-size:0.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $session->exam?->title ?? '—' }}
                            </div>
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'pending'     => ['badge-warning', 'Aguardando'],
                                    'in_progress' => ['badge-primary', 'Em Prova'],
                                    'graded'      => ['badge-success', 'Entregue'],
                                    'expired'     => ['badge-neutral', 'Expirada'],
                                    'terminated'  => ['badge-danger',  'Encerrada'],
                                ];
                                [$cls, $label] = $statusMap[$session->status] ?? ['badge-neutral', ucfirst($session->status)];
                            @endphp
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td>
                            @if($session->final_score !== null)
                                <span style="font-family:'JetBrains Mono',monospace;font-weight:600;color:{{ $session->passed ? 'var(--color-success)' : 'var(--color-danger)' }}">
                                    {{ number_format($session->final_score, 1) }}
                                </span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td style="color:var(--text-secondary);font-size:0.8rem">
                            {{ $session->created_at?->locale('pt_BR')->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:48px;color:var(--text-muted)">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;opacity:0.4">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                            Nenhuma sessão encontrada.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Widget lateral --}}
    <div style="display:flex;flex-direction:column;gap:12px">

        {{-- Início rápido --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Acesso Rápido</div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:8px;padding:16px">
                <a href="{{ route('provas.create') }}" class="btn btn-primary" style="justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Criar Nova Prova
                </a>
                <a href="{{ route('estudantes.create') }}" class="btn btn-secondary" style="justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                    Cadastrar Estudante
                </a>
                <a href="{{ route('relatorios.index') }}" class="btn btn-ghost" style="justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    Ver Relatórios
                </a>
            </div>
        </div>

        {{-- Status do sistema --}}
        <div class="card">
            <div class="card-header">
                <div class="card-title">Status do Sistema</div>
                <span class="badge badge-success">
                    <span class="live-dot" style="width:6px;height:6px"></span>
                    Operacional
                </span>
            </div>
            <div class="card-body" style="padding:16px">
                @php
                    $systemItems = [
                        ['label' => 'API',         'ok' => true],
                        ['label' => 'Banco de Dados','ok' => true],
                        ['label' => 'Filas',        'ok' => true],
                        ['label' => 'Cache',        'ok' => true],
                        ['label' => 'WebSockets',   'ok' => false],
                    ];
                @endphp
                @foreach($systemItems as $item)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;{{ !$loop->last ? 'border-bottom:1px solid var(--surface-border)' : '' }}">
                    <span style="font-size:0.875rem;color:var(--text-secondary)">{{ $item['label'] }}</span>
                    @if($item['ok'])
                        <span style="display:flex;align-items:center;gap:5px;font-size:0.75rem;color:var(--color-success);font-weight:600">
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--color-success);display:inline-block"></span>
                            OK
                        </span>
                    @else
                        <span style="display:flex;align-items:center;gap:5px;font-size:0.75rem;color:var(--text-muted);font-weight:600">
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--text-muted);display:inline-block"></span>
                            Inativo
                        </span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

@push('styles')
<style>
    @media (max-width: 900px) {
        .dashboard-bottom-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
@endsection
