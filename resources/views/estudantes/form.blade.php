@extends('layouts.app')

@php
    $isEdit = filled($student);
    $cpfRaw = $student?->cpf;
    $isRealCpf = $cpfRaw && preg_match('/^\d{11}$/', $cpfRaw);
    $cpfFormatted = $isRealCpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfRaw) : null;
    $hasMoodleCpf = $cpfRaw && preg_match('/^MDL/i', $cpfRaw);
    $moodleUserId = $student?->moodle_user_id;
    $canManageProfile = auth()->user()?->isSuperAdmin() && $isEdit;
    $currentProfile = match($student?->role) {
        'admin' => 'administrador',
        'professor' => 'criador_prova',
        'coordinator' => 'comercial',
        default => 'student',
    };
@endphp

@section('title', ($isEdit ? 'Editar Estudante' : 'Novo Estudante') . ' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Estudante' : 'Novo Estudante')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:820px">
            <h1 class="page-title" style="margin-bottom:6px">{{ $isEdit ? 'Atualizar cadastro do estudante' : 'Cadastrar novo estudante' }}</h1>
            <p class="page-subtitle" style="line-height:1.6">{{ $isEdit ? 'Edite os dados cadastrais mantendo a vinculação correta com o sistema de origem.' : 'Preencha os dados essenciais para liberar o acesso do estudante ao ambiente de provas.' }}</p>
        </div>
        <a href="{{ $isEdit ? route('estudantes.show', $student) : route('estudantes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Voltar
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Verifique os campos destacados antes de continuar.
</div>
@endif

<div class="estudantes-form-grid" style="display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,0.85fr);gap:20px;align-items:start">
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Dados do estudante</div>
                <div class="card-subtitle">Cadastro principal usado no login e no vínculo com provas.</div>
            </div>
        </div>

        <form method="POST" action="{{ $isEdit ? route('estudantes.update', $student) : route('estudantes.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="card-body">
                <div class="estudantes-form-row" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Nome <span style="color:var(--color-danger)">*</span></label>
                        <input id="first_name" type="text" name="first_name"
                               class="input @error('first_name') input-error @enderror"
                               value="{{ old('first_name', $student?->first_name) }}"
                               placeholder="Ex.: Maria Fernanda">
                        @error('first_name')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Sobrenome <span style="color:var(--color-danger)">*</span></label>
                        <input id="last_name" type="text" name="last_name"
                               class="input @error('last_name') input-error @enderror"
                               value="{{ old('last_name', $student?->last_name) }}"
                               placeholder="Ex.: Lopes da Silva">
                        @error('last_name')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="estudantes-form-row" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF @if(!$isEdit)<span style="color:var(--color-danger)">*</span>@endif</label>
                        @if($isEdit && $isRealCpf)
                        {{-- CPF real — não editável --}}
                        <input type="text" class="input" value="{{ $cpfFormatted }}" disabled>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">O CPF não é editado neste fluxo para preservar a identidade do estudante.</div>
                        @elseif($isEdit && $hasMoodleCpf)
                        {{-- CPF gerado pelo Moodle (MDL...) — permitir corrigir --}}
                        <input id="cpf" type="text" name="cpf"
                               class="input @error('cpf') input-error @enderror"
                               value="{{ old('cpf') }}"
                               placeholder="Informe o CPF real (11 dígitos)">
                        <div style="font-size:0.75rem;color:var(--color-warning);margin-top:4px">
                            O identificador atual (<strong>{{ $cpfRaw }}</strong>) foi gerado automaticamente pelo Moodle. Informe o CPF real do aluno para corrigir.
                        </div>
                        @elseif($isEdit)
                        {{-- Outro valor não-CPF --}}
                        <input type="text" class="input" value="{{ $cpfRaw }}" disabled>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Identificador do estudante.</div>
                        @else
                        {{-- Novo cadastro --}}
                        <input id="cpf" type="text" name="cpf" class="input @error('cpf') input-error @enderror" value="{{ old('cpf') }}" placeholder="Somente números (11 dígitos)">
                        @endif
                        @error('cpf')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" type="email" name="email" class="input @error('email') input-error @enderror" value="{{ old('email', $student?->email) }}" placeholder="aluno@instituicao.edu.br">
                        @error('email')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="estudantes-form-row" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefone / WhatsApp</label>
                        <input id="phone" type="text" name="phone" class="input @error('phone') input-error @enderror" value="{{ old('phone', $student?->phone) }}" placeholder="(11) 99999-9999">
                        @error('phone')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if($isEdit && $hasMoodleCpf)
                <div class="estudantes-form-row" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label">ID Moodle</label>
                        <input type="text" class="input" value="{{ $moodleUserId ?? preg_replace('/^MDL0*/', '', $cpfRaw) }}" disabled style="background:var(--surface-bg);color:var(--text-secondary)">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Identificador numérico do usuário no Moodle (não editável).</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username Moodle</label>
                        <input type="text" class="input" value="{{ $cpfRaw }}" disabled style="background:var(--surface-bg);color:var(--text-secondary)">
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Identificador gerado que será substituído pelo CPF real.</div>
                    </div>
                </div>
                @endif

                <div class="estudantes-form-row" style="display:grid;grid-template-columns:repeat({{ $canManageProfile ? 3 : 2 }},minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="client_system_id">Sistema</label>
                        <select id="client_system_id" name="client_system_id" class="input @error('client_system_id') input-error @enderror">
                            <option value="">Selecione</option>
                            @foreach($systems as $system)
                            <option value="{{ $system->id }}" @selected((string) old('client_system_id', $student?->client_system_id) === (string) $system->id)>{{ $system->name }}</option>
                            @endforeach
                        </select>
                        @error('client_system_id')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="active">Status do acesso</label>
                        <select id="active" name="active" class="input">
                            <option value="1" @selected((string) old('active', $student?->active ?? true) === '1')>Ativo</option>
                            <option value="0" @selected((string) old('active', $student?->active) === '0')>Inativo</option>
                        </select>
                    </div>

                    @if($canManageProfile)
                    <div class="form-group">
                        <label class="form-label" for="profile">Perfil do usuário</label>
                        <select id="profile" name="profile" class="input @error('profile') input-error @enderror">
                            <option value="student" @selected(old('profile', $currentProfile) === 'student')>Estudante</option>
                            <option value="administrador" @selected(old('profile', $currentProfile) === 'administrador')>Administrador</option>
                            <option value="criador_prova" @selected(old('profile', $currentProfile) === 'criador_prova')>Criador de Prova</option>
                            <option value="comercial" @selected(old('profile', $currentProfile) === 'comercial')>Comercial</option>
                        </select>
                        @error('profile')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Ao trocar para um perfil interno, este usuário passa a ser gerenciado em Perfis de Acesso.</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card-header" style="border-top:1px solid var(--surface-border);border-bottom:none;border-radius:0 0 12px 12px">
                <div style="font-size:0.78rem;color:var(--text-secondary)">
                    {{ $isEdit ? 'As alterações serão aplicadas imediatamente ao cadastro.' : 'Ao salvar, a senha inicial do estudante será o CPF sem formatação.' }}
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <a href="{{ $isEdit ? route('estudantes.show', $student) : route('estudantes.index') }}" class="btn btn-ghost">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $isEdit ? 'Salvar alterações' : 'Cadastrar estudante' }}
                    </button>
                </div>
            </div>
        </form>
    </section>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Orientações</div>
                    <div class="card-subtitle">Pontos importantes para o cadastro.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">CPF numérico</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Use apenas os 11 dígitos para manter compatibilidade com o login e com importações futuras.</div>
                </div>
                @if($isEdit && $hasMoodleCpf)
                <div style="padding:12px 14px;border:1px solid var(--color-warning);border-radius:12px;background:var(--color-warning-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Aluno importado do Moodle</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">O campo CPF contém um identificador gerado ({{ $cpfRaw }}). Informe o CPF real do aluno — no Moodle, o campo "Identificação de usuário" (idnumber) contém o CPF/RA.</div>
                </div>
                @endif
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Vínculo institucional</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Cada estudante precisa estar associado ao sistema correto para respeitar o isolamento multi-sistema.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Estado de acesso</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">Cadastros inativos permanecem no histórico, mas não devem receber novas aplicações.</div>
                </div>
            </div>
        </section>

        @if($isEdit)
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Registro atual</div>
                    <div class="card-subtitle">Resumo rápido do estudante em edição.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:14px">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">{{ $isRealCpf ? 'CPF' : ($hasMoodleCpf ? 'ID Moodle' : 'Identificador') }}</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $cpfFormatted ?? $cpfRaw }}</div>
                </div>
                @if($moodleUserId)
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Moodle User ID</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary);font-family:'JetBrains Mono',monospace">{{ $moodleUserId }}</div>
                </div>
                @endif
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Sessões registradas</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $student->examSessions()->count() }}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Status</div>
                    <div style="margin-top:4px"><span class="badge {{ $student->active ? 'badge-success' : 'badge-neutral' }}">{{ $student->active ? 'Ativo' : 'Inativo' }}</span></div>
                </div>
            </div>
        </section>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1100px) {
        .estudantes-form-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 720px) {
        .estudantes-form-row {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
