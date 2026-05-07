@extends('layouts.app')
@section('title', 'E-mail e SMTP — AvaliaFA')
@section('page-title', 'Configurações')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">E-mail e SMTP</h1>
            <p class="page-subtitle">Configure o servidor de envio de e-mails para relatórios e notificações.</p>
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

<form action="{{ route('configuracoes.email.salvar') }}" method="POST">
    @csrf

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Servidor SMTP</div>
                <div class="card-subtitle">Dados de conexão com o servidor de e-mail.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">Host SMTP</label>
                <input type="text" name="smtp_host" class="input @error('smtp_host') input-error @enderror"
                       value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}"
                       placeholder="smtp.gmail.com" required>
                @error('smtp_host')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Porta</label>
                <input type="number" name="smtp_port" class="input @error('smtp_port') input-error @enderror"
                       value="{{ old('smtp_port', $settings['smtp_port'] ?? '587') }}" required>
                @error('smtp_port')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Usuário</label>
                <input type="text" name="smtp_username" class="input"
                       value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}"
                       placeholder="usuario@gmail.com" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Senha</label>
                <input type="password" name="smtp_password" class="input"
                       value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}"
                       placeholder="********" autocomplete="new-password">
            </div>
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">Criptografia</label>
                <select name="smtp_encryption" class="input">
                    <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (Recomendado)</option>
                    <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                    <option value="none" {{ ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' }}>Nenhuma</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <div>
                <div class="card-title">Remetente</div>
                <div class="card-subtitle">Dados que aparecerão como remetente nos e-mails enviados.</div>
            </div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group">
                <label class="form-label">E-mail Remetente</label>
                <input type="email" name="mail_from_address" class="input @error('mail_from_address') input-error @enderror"
                       value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}"
                       placeholder="noreply@faculdade.edu.br" required>
                @error('mail_from_address')<div class="form-error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Nome Remetente</label>
                <input type="text" name="mail_from_name" class="input @error('mail_from_name') input-error @enderror"
                       value="{{ old('mail_from_name', $settings['mail_from_name'] ?? 'AvaliaFA') }}" required>
                @error('mail_from_name')<div class="form-error">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:20px">
        <a href="{{ route('configuracoes.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Salvar SMTP
        </button>
    </div>
</form>

{{-- Testar E-mail --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Testar Envio</div>
            <div class="card-subtitle">Envie um e-mail de teste para verificar a configuração.</div>
        </div>
    </div>
    <form action="{{ route('configuracoes.email.testar') }}" method="POST">
        @csrf
        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
            <div class="form-group" style="grid-column:1/-1">
                <label class="form-label">E-mail de Destino</label>
                <div style="display:flex;gap:10px">
                    <input type="email" name="test_email" class="input"
                           placeholder="seu@email.com" required style="flex:1">
                    <button type="submit" class="btn btn-secondary" style="white-space:nowrap">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Enviar Teste
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
