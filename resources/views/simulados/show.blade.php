@extends('layouts.app')

@section('title', $simulado->name.' — Simulado')
@section('page-title', 'Simulado')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">{{ $simulado->name }}</h1>
            <p class="page-subtitle">{{ $simulado->description ?: 'Sem descrição cadastrada.' }}</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-ghost btn-sm" href="{{ route('simulados.index') }}">Voltar</a>
            @if($broadcastStudentCount > 0)
            @php
                $bProcessing = $simulado->broadcast_total_target > 0 && ! $simulado->broadcast_sent_at;
                $bDone       = (bool) $simulado->broadcast_sent_at;
            @endphp
            <form method="POST" action="{{ route('simulados.broadcast-all', $simulado) }}"
                  onsubmit="return confirm('Enviar e-mail de divulgação para TODOS os {{ $broadcastStudentCount }} aluno(s) cadastrados no sistema?\n\nEsta ação envia independente de inscrição no simulado.')">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm"
                        @if($bProcessing) disabled @endif
                        style="@if($bDone) border-color:#D1FAE5;background:#F0FDF4;color:#15803D; @elseif($bProcessing) opacity:0.6;cursor:not-allowed; @endif">
                    @if($bProcessing)
                        ⏳ Processando… ({{ $simulado->broadcast_total_target }} alunos)
                    @elseif($bDone)
                        📢 Anunciar novamente
                        <span style="font-size:0.7rem;margin-left:4px;font-weight:400">
                            · último: {{ $simulado->broadcast_sent_at->format('d/m H:i') }}
                            — {{ $simulado->broadcast_total_sent }}/{{ $simulado->broadcast_total_target }} enviados
                        </span>
                    @else
                        📢 Anunciar para todos ({{ $broadcastStudentCount }})
                    @endif
                </button>
            </form>
            @endif
            @if($pendingEmailCount > 0)
            <form method="POST" action="{{ route('simulados.notify-pending', $simulado) }}"
                  onsubmit="return confirm('Reenviar e-mail para {{ $pendingEmailCount }} participante(s) inscrito(s) que ainda não receberam?')">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    📧 Notificar inscritos ({{ $pendingEmailCount }})
                </button>
            </form>
            @endif
            <a href="{{ route('demo.simulado', $simulado) }}" class="btn btn-secondary btn-sm"
               onclick="return confirm('Iniciar modo demonstração? O simulado abrirá com SecureExamEngine completo, mas nenhum dado será registrado oficialmente.')">
                🧪 Demonstração
            </a>
            <a class="btn btn-secondary btn-sm" target="_blank" href="{{ route('simulados.public.inscricao', $simulado->slug) }}?simulado={{ $simulado->slug }}">Link de inscrição</a>
            <a class="btn btn-secondary btn-sm" href="{{ route('simulados.export.excel', $simulado) }}">Exportar Excel</a>
            <a class="btn btn-secondary btn-sm" target="_blank" rel="noopener" href="{{ route('simulados.export.pdf', $simulado) }}">Exportar PDF</a>
            <a href="{{ route('simulados.edit', $simulado) }}" title="Editar simulado" aria-label="Editar simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EFF6FF;color:#1D4ED8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                    <path d="M12 20h9"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                </svg>
            </a>
            <form method="POST" action="{{ route('simulados.destroy', $simulado) }}" onsubmit="return confirmDelete(this, {title:'Excluir simulado', message:'Deseja excluir este simulado? Esta ação não pode ser desfeita.', confirmLabel:'Excluir'})">
                @csrf
                @method('DELETE')
                <button type="submit" title="Excluir simulado" aria-label="Excluir simulado" style="width:34px;height:34px;border-radius:9px;border:1px solid #FCA5A5;background:#FEF2F2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14H6L5 6"/>
                        <path d="M10 11v6M14 11v6"/>
                        <path d="M9 6V4h6v2"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom:16px;background:linear-gradient(120deg,#EFF6FF,#FFFFFF);border:1px solid #BFDBFE">
    <div style="padding:14px 20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <img src="{{ asset('storage/logo-deitada-transparente.png') }}" alt="Faculdade Anasps" style="height:32px;max-width:220px;object-fit:contain">
        <div style="font-size:0.84rem;color:#334155">Relatório personalizado do simulado com dados completos dos participantes.</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:18px">
    @php
        $simuladoStatusMap = [
            'draft' => 'Rascunho',
            'scheduled' => 'Programado',
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'archived' => 'Arquivado',
        ];
    @endphp
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Status</div><div style="font-weight:700">{{ $simuladoStatusMap[$simulado->status] ?? '—' }}</div></div>
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Duração</div><div style="font-weight:700">{{ $simulado->exam?->duration_minutes ? $simulado->exam->duration_minutes.' min' : '—' }}</div></div>
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Nota de corte</div><div style="font-weight:700">{{ $simulado->exam?->passing_score !== null ? number_format((float)$simulado->exam->passing_score, 2, ',', '.') : '—' }}</div></div>
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Início programado</div><div style="font-weight:700">{{ optional($simulado->exam?->starts_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Fim programado</div><div style="font-weight:700">{{ optional($simulado->exam?->ends_at)->format('d/m/Y H:i') ?? '—' }}</div></div>
    <div class="card" style="padding:12px 14px"><div style="font-size:0.75rem;color:var(--text-muted)">Integração Moodle</div><div style="font-weight:700">{{ $simulado->moodle_integration_enabled ? 'Ativa' : 'Inativa' }}</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('simulados.show', $simulado) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:240px">
                <label class="form-label" style="margin-bottom:4px">Busca CRM</label>
                <input type="text" name="q" value="{{ request('q') }}" class="input" placeholder="Nome, CPF, e-mail ou telefone" style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:170px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    @foreach(['registered' => 'Inscrito', 'in_progress' => 'Em andamento', 'completed' => 'Concluído', 'email_sent' => 'E-mail enviado', 'cancelled' => 'Cancelado'] as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        @php $provasFeitasPorParticipante = $provasFeitasPorParticipante ?? []; @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th>Participante</th>
                    <th>Sessão</th>
                    <th>Tentativa</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th>Notif.</th>
                    <th>Tentativas</th>
                    <th>Nota</th>
                    <th>% Acertos</th>
                    <th>Acertos</th>
                    <th>Erros</th>
                    <th>Inscrição</th>
                    <th>Conclusão</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($registrations as $registration)
                @php
                    $participant = $registration->participant;
                    if (! $participant) {
                        continue;
                    }
                    $participantName = trim((string) ((data_get($participant, 'first_name', '')).' '.(data_get($participant, 'last_name', ''))));
                    $registrationStatusMap = [
                        'registered' => 'Inscrito',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluído',
                        'email_sent' => 'E-mail enviado',
                        'cancelled' => 'Cancelado',
                    ];
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:700">{{ $participantName }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">CPF: {{ $participant->cpf }}</div>
                    </td>
                    <td>{{ $registration->examSession?->id ? '#'.$registration->examSession->id : '—' }}</td>
                    <td>{{ $registration->examSession?->attempt_number ? $registration->examSession->attempt_number.'ª' : '—' }}</td>
                    <td>{{ $participant->email ?: '—' }}</td>
                    <td>{{ $participant->phone ?: '—' }}</td>
                    <td><span class="badge badge-neutral">{{ $registrationStatusMap[$registration->status] ?? '—' }}</span></td>
                    <td>
                        @if($registration->email_sent_at)
                            <span title="{{ $registration->email_sent_at->format('d/m/Y H:i') }}" style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:#16A34A;font-weight:600">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:13px;height:13px;stroke-width:2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ $registration->email_sent_at->format('d/m H:i') }}
                            </span>
                        @else
                            <span style="font-size:0.75rem;color:#94A3B8">Pendente</span>
                        @endif
                    </td>
                    <td>{{ (int) ($provasFeitasPorParticipante[$participant->id] ?? 0) }}</td>
                    <td>{{ number_format((float) $registration->final_score, 2, ',', '.') }}</td>
                    <td>{{ number_format((float) $registration->percentage_correct, 2, ',', '.') }}%</td>
                    <td>{{ (int) ($registration->total_correct ?? 0) }}</td>
                    <td>{{ (int) ($registration->total_wrong ?? 0) }}</td>
                    <td>{{ optional($registration->registered_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>{{ optional($registration->completed_at)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:nowrap;white-space:nowrap">
                            @if($participant->email)
                            <form method="POST" action="{{ route('simulados.participants.notify', [$simulado, $participant]) }}"
                                  onsubmit="return confirm('Reenviar e-mail de disponibilidade para {{ addslashes($participant->email) }}?')">
                                @csrf
                                <button type="submit" title="Enviar/reenviar e-mail" aria-label="Enviar e-mail"
                                        style="width:34px;height:34px;border-radius:9px;border:1px solid {{ $registration->email_sent_at ? '#D1FAE5' : '#FDE68A' }};background:{{ $registration->email_sent_at ? '#F0FDF4' : '#FFFBEB' }};color:{{ $registration->email_sent_at ? '#16A34A' : '#B45309' }};display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;stroke-width:2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                        <polyline points="22,6 12,13 2,6"/>
                                    </svg>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('simulados.participants.edit', [$simulado, $participant]) }}" title="Editar participante" aria-label="Editar participante" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EFF6FF;color:#1D4ED8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                            </a>
                            @if($participant->user_id)
                            <a href="{{ route('estudantes.edit', $participant->user_id) }}" title="Editar usuário" aria-label="Editar usuário" style="width:34px;height:34px;border-radius:9px;border:1px solid #D1D5DB;background:#FFFFFF;color:#334155;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                    <path d="M20 21a8 8 0 1 0-16 0"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </a>
                            @endif
                            <a href="{{ route('simulados.participants.export.pdf', [$simulado, $participant]) }}" target="_blank" rel="noopener" title="PDF personalizado" aria-label="PDF personalizado" style="width:34px;height:34px;border-radius:9px;border:1px solid #BFDBFE;background:#EFF6FF;color:#1D4ED8;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                    <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"/>
                                    <path d="M14 2v5h5"/>
                                    <path d="M9 13h6M9 17h6"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('simulados.participants.destroy', [$simulado, $participant]) }}" onsubmit="return confirmDelete(this, {title:'Remover participante', message:'Deseja remover este participante do simulado?', confirmLabel:'Remover'})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" title="Excluir participante" aria-label="Excluir participante" style="width:34px;height:34px;border-radius:9px;border:1px solid #FCA5A5;background:#FEF2F2;color:#DC2626;display:inline-flex;align-items:center;justify-content:center;cursor:pointer">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;stroke-width:2">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                        <path d="M9 6V4h6v2"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="14" style="text-align:center;padding:32px;color:var(--text-muted)">Nenhum participante encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px">
        {{ $registrations->links() }}
    </div>
</div>
@endsection
