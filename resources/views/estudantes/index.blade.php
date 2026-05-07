@extends('layouts.app')

@php
    $activeStudents = $students->where('active', true)->count();
    $inactiveStudents = $students->where('active', false)->count();
    $studentsWithSessions = $students->where('exam_sessions_count', '>', 0)->count();
@endphp

@section('title', 'Estudantes — AvaliaFA')
@section('page-title', 'Estudantes')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:820px">
            <h1 class="page-title" style="margin-bottom:6px">Base de estudantes</h1>
            <p class="page-subtitle" style="line-height:1.6">Gerencie cadastros, acompanhe a vinculação por sistema e consulte rapidamente o histórico de participação em provas.</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <a href="{{ route('estudantes.import.form') }}" class="btn btn-secondary" style="display:flex;align-items:center;gap:6px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Importar CSV
            </a>
            <a href="{{ route('estudantes.create') }}" class="btn btn-primary" style="display:flex;align-items:center;gap:6px">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo estudante
            </a>
        </div>
    </div>
</div>

<div class="estudantes-kpis" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px">
    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(37,99,235,0.12);color:#2563EB">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
        </div>
        <div class="kpi-label">Registros nesta página</div>
        <div class="kpi-value">{{ $students->count() }}</div>
        <div class="kpi-badge" style="background:var(--color-primary-100);color:var(--color-primary-700)">Página {{ $students->currentPage() }}</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(16,185,129,0.12);color:#10B981">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="kpi-label">Ativos</div>
        <div class="kpi-value">{{ $activeStudents }}</div>
        <div class="kpi-badge kpi-badge-up">Prontos para acesso</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(239,68,68,0.12);color:#EF4444">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div class="kpi-label">Inativos</div>
        <div class="kpi-value">{{ $inactiveStudents }}</div>
        <div class="kpi-badge kpi-badge-down">Sem acesso liberado</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
        </div>
        <div class="kpi-label">Com sessões</div>
        <div class="kpi-value">{{ $studentsWithSessions }}</div>
        <div class="kpi-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6">Atividade registrada</div>
    </div>
</div>

<div class="estudantes-grid" style="display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,0.8fr);gap:20px;align-items:start">
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Lista de estudantes</div>
                <div class="card-subtitle">Busca por nome, CPF ou email com filtros por sistema e status.</div>
            </div>
            <span class="badge badge-primary">{{ $students->total() }} no total</span>
        </div>

        <div class="card-body" style="padding-bottom:0">
            <form method="GET" action="{{ route('estudantes.index') }}" class="estudantes-filter-grid" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:12px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busca" class="input" value="{{ request('busca') }}" placeholder="Nome, CPF ou email">
                </div>

                @if(auth()->user()->isSuperAdmin())
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Sistema</label>
                    <select name="sistema" class="input">
                        <option value="">Todos</option>
                        @foreach($systems as $system)
                        <option value="{{ $system->id }}" @selected((string) request('sistema') === (string) $system->id)>{{ $system->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Status</label>
                    <select name="ativo" class="input">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('ativo') === '1')>Ativos</option>
                        <option value="0" @selected(request('ativo') === '0')>Inativos</option>
                    </select>
                </div>

                <div style="display:flex;align-items:end;gap:8px">
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                    @if(request()->hasAny(['busca', 'sistema', 'ativo']))
                    <a href="{{ route('estudantes.index') }}" class="btn btn-ghost">Limpar</a>
                    @endif
                </div>
            </form>
        </div>

        <div style="overflow-x:auto;margin-top:16px">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Estudante</th>
                        <th>Sistema</th>
                        <th>Sessões</th>
                        <th>Status</th>
                        <th>Cadastro</th>
                        <th style="text-align:right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="avatar" style="width:34px;height:34px;font-size:0.72rem">
                                    {{ mb_strtoupper(mb_substr($student->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight:700;color:var(--text-primary)">{{ $student->name }}</div>
                                    <div style="font-size:0.74rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace">
                                        {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}
                                    </div>
                                    <div style="font-size:0.76rem;color:var(--text-secondary)">{{ $student->email ?: 'Sem email cadastrado' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $student->clientSystem?->name ?? 'Não vinculado' }}</span>
                        </td>
                        <td>
                            <span style="font-family:'JetBrains Mono',monospace;font-weight:700">{{ $student->exam_sessions_count }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $student->active ? 'badge-success' : 'badge-neutral' }}">{{ $student->active ? 'Ativo' : 'Inativo' }}</span>
                        </td>
                        <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-secondary);font-family:'JetBrains Mono',monospace">
                            {{ $student->created_at?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td>
                            <div style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap">
                                <a href="{{ route('estudantes.show', $student) }}" class="btn btn-ghost btn-sm">Ver</a>
                                <a href="{{ route('estudantes.edit', $student) }}" class="btn btn-secondary btn-sm">Editar</a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:44px 16px;text-align:center">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.28"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                                <div style="font-size:0.92rem;font-weight:700;color:var(--text-primary)">Nenhum estudante encontrado</div>
                                <div style="font-size:0.8rem;color:var(--text-muted)">Ajuste os filtros ou cadastre um novo estudante para começar.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
        <div style="padding:16px 24px;border-top:1px solid var(--surface-border)">
            {{ $students->links() }}
        </div>
        @endif
    </section>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Resumo operacional</div>
                    <div class="card-subtitle">Leitura rápida do recorte atual da listagem.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:14px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Busca ativa</span>
                    <span class="badge {{ request('busca') ? 'badge-primary' : 'badge-neutral' }}">{{ request('busca') ?: 'Nenhuma' }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Filtro de status</span>
                    <span class="badge badge-neutral">{{ request('ativo') === '1' ? 'Ativos' : (request('ativo') === '0' ? 'Inativos' : 'Todos') }}</span>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Sistema selecionado</span>
                    <span class="badge badge-primary">{{ optional($systems->firstWhere('id', (int) request('sistema')))->name ?: 'Todos' }}</span>
                </div>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Total de estudantes</div>
                    <div class="card-subtitle">Contagem geral por sistema.</div>
                </div>
                <span class="badge badge-primary">{{ $totalStudents }}</span>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                @foreach($systemStats as $systemName => $count)
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">{{ $systemName }}</span>
                    <span class="badge badge-neutral" style="font-weight:700;font-size:0.82rem">{{ number_format($count) }}</span>
                </div>
                @endforeach
                @if($systemStats->isEmpty())
                <div style="font-size:0.84rem;color:var(--text-muted);text-align:center;padding:8px 0">Nenhum sistema encontrado</div>
                @endif
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Boas práticas</div>
                    <div class="card-subtitle">Diretrizes alinhadas ao fluxo atual do sistema.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Senha inicial</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">O cadastro cria o estudante com senha inicial igual ao CPF sem formatação.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Multi-sistema</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Super admins podem alternar a visão por sistema; demais perfis seguem o vínculo institucional.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Importação em lote</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Use o botão "Importar CSV" para cadastrar múltiplos estudantes de uma vez com modelo pronto para download.</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1180px) {
        .estudantes-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .estudantes-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 760px) {
        .estudantes-kpis,
        .estudantes-filter-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
