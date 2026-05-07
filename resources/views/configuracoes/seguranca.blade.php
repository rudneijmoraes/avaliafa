@extends('layouts.app')
@section('title', 'Segurança — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Segurança</h1>
            <p class="page-subtitle">Políticas de senha, sessão e monitoramento de provas.</p>
        </div>
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Voltar
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:16px">
    Verifique os campos do formulário antes de salvar.
</div>
@endif

<form action="{{ route('configuracoes.seguranca.salvar') }}" method="POST">
    @csrf

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Autenticação</div>
                <div class="card-subtitle">Controle de tentativas de login e duração de sessão.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Máx. Tentativas de Login</label>
                <input type="number" name="max_login_attempts" class="input"
                       value="{{ old('max_login_attempts', $settings['max_login_attempts'] ?? '5') }}" min="1" max="20" required>
            </div>
            <div class="form-group">
                <label class="form-label">Bloqueio (minutos)</label>
                <input type="number" name="lockout_duration" class="input"
                       value="{{ old('lockout_duration', $settings['lockout_duration'] ?? '15') }}" min="1" max="120" required>
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Tempo de Sessão (minutos)</label>
                <input type="number" name="session_lifetime" class="input"
                       value="{{ old('session_lifetime', $settings['session_lifetime'] ?? '120') }}" min="5" max="480" required>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Tempo de inatividade até o logout automático.</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Política de Senhas</div>
                <div class="card-subtitle">Requisitos mínimos de complexidade para senhas de usuários.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Tamanho Mínimo</label>
                <input type="number" name="password_min_length" class="input"
                       value="{{ old('password_min_length', $settings['password_min_length'] ?? '8') }}" min="6" max="32" required>
            </div>
            <div class="form-group" style="display:flex;flex-direction:column;justify-content:center;gap:10px;padding-top:8px">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="require_uppercase" value="1"
                           {{ old('require_uppercase', $settings['require_uppercase'] ?? '1') == '1' ? 'checked' : '' }}>
                    Exigir letra maiúscula
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="require_numbers" value="1"
                           {{ old('require_numbers', $settings['require_numbers'] ?? '1') == '1' ? 'checked' : '' }}>
                    Exigir números
                </label>
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="require_symbols" value="1"
                           {{ old('require_symbols', $settings['require_symbols'] ?? '0') == '1' ? 'checked' : '' }}>
                    Exigir caracteres especiais
                </label>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Segurança em Provas</div>
                <div class="card-subtitle">Parâmetros de monitoramento durante a realização das provas.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Máx. Violações antes de Encerrar</label>
                <input type="number" name="exam_max_violations" class="input"
                       value="{{ old('exam_max_violations', $settings['exam_max_violations'] ?? '5') }}" min="1" max="50" required>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px">Número de violações de segurança antes de encerrar a prova automaticamente.</div>
            </div>
            <div class="form-group" style="display:flex;align-items:center;padding-top:8px">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.875rem;color:var(--text-primary);cursor:pointer">
                    <input type="checkbox" name="exam_webcam_required" value="1"
                           {{ old('exam_webcam_required', $settings['exam_webcam_required'] ?? '0') == '1' ? 'checked' : '' }}>
                    Exigir webcam durante as provas
                </label>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px">
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Salvar configurações
        </button>
    </div>
</form>
@endsection
