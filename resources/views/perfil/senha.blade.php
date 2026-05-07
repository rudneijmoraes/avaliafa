@extends('layouts.app')

@section('title', 'Alterar Senha — AvaliaFA')
@section('page-title', 'Alterar Senha')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="max-width:820px">
            <h1 class="page-title" style="margin-bottom:6px">Atualização de senha</h1>
            <p class="page-subtitle" style="line-height:1.6">Use este formulário para trocar sua credencial de acesso mantendo o padrão visual e operacional atual do sistema.</p>
        </div>
        <a href="{{ route('perfil.index') }}" class="btn btn-ghost">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Voltar ao perfil
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Verifique os campos de senha antes de tentar novamente.
</div>
@endif

<div class="perfil-senha-grid" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,0.8fr);gap:20px;align-items:start">
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Credenciais</div>
                <div class="card-subtitle">Informe a senha atual e defina uma nova senha com confirmação.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('perfil.senha.update') }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="senha_atual">Senha atual</label>
                    <input id="senha_atual" type="password" name="senha_atual" class="input @error('senha_atual') input-error @enderror" placeholder="Digite sua senha atual">
                    @error('senha_atual')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="perfil-senha-fields" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="senha_nova">Nova senha</label>
                        <input id="senha_nova" type="password" name="senha_nova" class="input @error('senha_nova') input-error @enderror" placeholder="Mínimo de 6 caracteres">
                        @error('senha_nova')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="senha_nova_confirmation">Confirmar nova senha</label>
                        <input id="senha_nova_confirmation" type="password" name="senha_nova_confirmation" class="input" placeholder="Repita a nova senha">
                    </div>
                </div>
            </div>

            <div class="card-header" style="border-top:1px solid var(--surface-border);border-bottom:none;border-radius:0 0 12px 12px">
                <div style="font-size:0.78rem;color:var(--text-secondary)">A alteração é aplicada imediatamente após validação da senha atual.</div>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Atualizar senha
                </button>
            </div>
        </form>
    </section>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Regras rápidas</div>
                    <div class="card-subtitle">Boas práticas para manter a conta segura.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Confirmação obrigatória</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">A nova senha deve ser repetida exatamente para evitar trocas acidentais.</div>
                </div>
                <div style="padding:12px 14px;border:1px solid var(--surface-border);border-radius:12px;background:var(--surface-bg)">
                    <div style="font-size:0.86rem;font-weight:700;color:var(--text-primary)">Validação mínima</div>
                    <div style="font-size:0.78rem;color:var(--text-secondary);margin-top:4px">O backend exige no mínimo 6 caracteres antes de aceitar a alteração.</div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1100px) {
        .perfil-senha-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 720px) {
        .perfil-senha-fields {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
