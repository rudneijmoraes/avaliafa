@extends('layouts.app')

@php
    $statusClass = $estudante->active ? 'badge-success' : 'badge-neutral';
    $statusLabel = $estudante->active ? 'Ativo' : 'Inativo';

    $sessionStatusMap = [
        'pending' => ['badge-warning', 'Pendente'],
        'in_progress' => ['badge-primary', 'Em andamento'],
        'submitted' => ['badge-success', 'Enviada'],
        'expired' => ['badge-neutral', 'Expirada'],
        'terminated' => ['badge-danger', 'Encerrada'],
        'graded' => ['badge-success', 'Corrigida'],
    ];

    $gradedSessions = $sessions->whereIn('status', ['submitted', 'graded']);
    $approvedSessions = $sessions->where('passed', true)->count();
    $averageScore = $gradedSessions->whereNotNull('final_score')->avg('final_score');

    $cpfRaw = $estudante->cpf;
    $isRealCpf = $cpfRaw && preg_match('/^\d{11}$/', $cpfRaw);
    $cpfFormatted = $isRealCpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfRaw) : null;
    $moodleId = (!$isRealCpf && $cpfRaw) ? $cpfRaw : null;
@endphp

@section('title', $estudante->name . ' — AvaliaFA')
@section('page-title', 'Perfil do Estudante')

@section('topbar-actions')
<span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
@endsection

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="display:flex;align-items:flex-start;gap:14px;max-width:880px">
            <div style="position:relative">
                @if($estudante->hasProfilePhoto())
                <img src="{{ $estudante->profilePhotoUrl() }}" alt="{{ $estudante->name }}"
                     style="width:88px;height:88px;border-radius:10px;object-fit:cover;border:3px solid var(--color-primary-200)">
                @else
                <div style="width:88px;height:88px;border-radius:10px;background:var(--color-primary-100);color:var(--color-primary-700);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:800;letter-spacing:0.04em">{{ mb_strtoupper(mb_substr($estudante->name, 0, 2)) }}</div>
                @endif
                <button type="button" onclick="document.getElementById('foto-input').click()"
                        style="position:absolute;bottom:-4px;right:-4px;width:28px;height:28px;border-radius:50%;background:var(--color-primary-600);border:2px solid #fff;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0"
                        title="Trocar foto">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                    </svg>
                </button>
                <form id="foto-form" method="POST" action="{{ route('estudantes.foto', $estudante) }}" enctype="multipart/form-data" style="display:none">
                    @csrf
                    <input type="file" id="foto-input" name="foto" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('foto-form').submit()">
                </form>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    @if($estudante->clientSystem)
                    <span class="badge badge-primary">{{ $estudante->clientSystem->name }}</span>
                    @endif
                </div>
                <h1 class="page-title" style="margin-bottom:6px">{{ $estudante->name }}</h1>
                <p class="page-subtitle" style="line-height:1.6">
                    {{ $estudante->email ?: 'Sem email cadastrado' }}
                    @if($cpfFormatted)
                    · <span style="font-family:'JetBrains Mono',monospace">{{ $cpfFormatted }}</span>
                    @endif
                    @if($moodleId)
                    · <span style="font-family:'JetBrains Mono',monospace">{{ $moodleId }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('estudantes.edit', $estudante) }}" class="btn btn-primary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Editar
            </a>
            <a href="{{ route('estudantes.index') }}" class="btn btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Voltar
            </a>
            @if($isRealCpf)
            <form method="POST" action="{{ route('estudantes.reset-senha', $estudante) }}" onsubmit="return confirm('Redefinir a senha de {{ $estudante->name }} para o CPF sem formatação?')">
                @csrf
                <button type="submit" class="btn btn-secondary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Resetar Senha
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('estudantes.destroy', $estudante) }}" onsubmit="return confirmDelete(this, {title:'Remover estudante', message:'Deseja remover este estudante? Os dados serão preservados (soft delete).'})">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                    Deletar
                </button>
            </form>
        </div>
    </div>
</div>

<div class="estudantes-show-kpis" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:22px">
    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(37,99,235,0.12);color:#2563EB">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
        </div>
        <div class="kpi-label">Sessões totais</div>
        <div class="kpi-value">{{ $estudante->exam_sessions_count }}</div>
        <div class="kpi-badge" style="background:var(--color-primary-100);color:var(--color-primary-700)">Histórico consolidado</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(16,185,129,0.12);color:#10B981">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="kpi-label">Aprovações</div>
        <div class="kpi-value">{{ $approvedSessions }}</div>
        <div class="kpi-badge kpi-badge-up">Nos últimos registros</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(139,92,246,0.12);color:#8B5CF6">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-7"/></svg>
        </div>
        <div class="kpi-label">Média recente</div>
        <div class="kpi-value">{{ $averageScore !== null ? number_format((float) $averageScore, 1, ',', '.') : '—' }}</div>
        <div class="kpi-badge" style="background:rgba(139,92,246,0.12);color:#8B5CF6">Notas finais</div>
    </div>

    <div class="kpi-card card-enter">
        <div class="kpi-icon" style="background:rgba(245,158,11,0.14);color:#F59E0B">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div class="kpi-label">Última atividade</div>
        <div class="kpi-value" style="font-size:1.2rem">{{ optional($sessions->first()?->created_at)->format('d/m') ?? '—' }}</div>
        <div class="kpi-badge" style="background:var(--color-warning-bg);color:var(--color-warning)">{{ optional($sessions->first()?->created_at)?->diffForHumans() ?? 'Sem sessões' }}</div>
    </div>
</div>

<div class="estudantes-show-grid" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(320px,0.85fr);gap:20px;align-items:start">
    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Últimas sessões</div>
                    <div class="card-subtitle">Até 10 aplicações recentes vinculadas a este estudante.</div>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Prova</th>
                            <th>Status</th>
                            <th>Nota</th>
                            <th>Violações</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                        @php([$badgeClass, $badgeLabel] = $sessionStatusMap[$session->status] ?? ['badge-neutral', ucfirst($session->status)])
                        <tr>
                            <td>
                                <div style="font-weight:700;color:var(--text-primary)">{{ $session->exam?->title ?? 'Prova indisponível' }}</div>
                                <div style="font-size:0.74rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace">Tentativa #{{ $session->attempt_number }}</div>
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                            <td>
                                @if($session->final_score !== null)
                                <span style="font-family:'JetBrains Mono',monospace;font-weight:700;color:{{ $session->passed ? 'var(--color-success)' : 'var(--color-danger)' }}">{{ number_format((float) $session->final_score, 1, ',', '.') }}</span>
                                @else
                                <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td><span style="font-family:'JetBrains Mono',monospace">{{ $session->violation_count }}</span></td>
                            <td style="color:var(--text-secondary)">{{ $session->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="padding:44px 16px;text-align:center">
                                <div style="display:flex;flex-direction:column;align-items:center;gap:10px">
                                    <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.28"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <div style="font-size:0.92rem;font-weight:700;color:var(--text-primary)">Sem histórico de prova</div>
                                    <div style="font-size:0.8rem;color:var(--text-muted)">Este estudante ainda não possui sessões registradas.</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Dados cadastrais</div>
                    <div class="card-subtitle">Identificação e vínculo institucional.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:14px">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">CPF</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $cpfFormatted ?? 'Não informado' }}</div>
                </div>
                @if($moodleId)
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Username Moodle</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $moodleId }}</div>
                </div>
                @endif
                @if($estudante->moodle_user_id)
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Moodle User ID</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $estudante->moodle_user_id }}</div>
                </div>
                @endif
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Email</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $estudante->email ?: 'Não informado' }}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Sistema</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $estudante->clientSystem?->name ?? 'Não vinculado' }}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Criado em</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $estudante->created_at?->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Leitura rápida</div>
                    <div class="card-subtitle">Indicadores úteis para operação acadêmica.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Situação de acesso</span>
                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Sessões com nota</span>
                    <span class="badge badge-primary">{{ $gradedSessions->count() }}</span>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span style="font-size:0.84rem;color:var(--text-secondary)">Aprovado em</span>
                    <span class="badge badge-success">{{ $approvedSessions }}</span>
                </div>
            </div>
        </section>

        {{-- Reconhecimento Facial --}}
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Reconhecimento Facial</div>
                    <div class="card-subtitle">Foto de referência para verificação de identidade nas provas.</div>
                </div>
                @if($estudante->face_reference_photo)
                    <span class="badge badge-success">Foto cadastrada</span>
                @else
                    <span class="badge badge-warning">Sem foto</span>
                @endif
            </div>
            <div class="card-body" style="display:grid;gap:14px">
                @if($estudante->face_reference_photo)
                    <p style="font-size:0.84rem;color:var(--text-secondary);margin:0">
                        Foto de referência cadastrada. Utilizada para verificação durante as provas habilitadas com reconhecimento facial.
                    </p>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <button class="btn btn-sm btn-outline"
                                onclick="document.getElementById('face-photo-input').click()">
                            Trocar Foto
                        </button>
                        <form method="POST"
                              action="{{ route('estudantes.face-reference.destroy', $estudante) }}"
                              onsubmit="return confirm('Remover a foto de referência de {{ $estudante->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger-outline">
                                Remover
                            </button>
                        </form>
                    </div>
                @else
                    <p style="font-size:0.84rem;color:var(--text-secondary);margin:0">
                        Nenhuma foto cadastrada. Cadastre uma foto para habilitar o reconhecimento facial nas provas.
                    </p>
                    <div>
                        <button class="btn btn-sm btn-primary"
                                onclick="document.getElementById('face-photo-input').click()">
                            Cadastrar Foto
                        </button>
                    </div>
                @endif

                <form id="face-photo-form"
                      method="POST"
                      action="{{ route('estudantes.face-reference.update', $estudante) }}"
                      enctype="multipart/form-data"
                      style="display:none">
                    @csrf
                    <input type="file"
                           id="face-photo-input"
                           name="face_photo"
                           accept="image/jpeg,image/png"
                           onchange="document.getElementById('face-photo-form').submit()">
                </form>
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1180px) {
        .estudantes-show-kpis {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .estudantes-show-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 760px) {
        .estudantes-show-kpis {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
