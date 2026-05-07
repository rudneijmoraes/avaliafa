@extends('layouts.app')

@section('title', 'Meu Perfil — AvaliaFA')
@section('page-title', 'Meu Perfil')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div style="display:flex;align-items:flex-start;gap:14px;max-width:860px">
            <div style="position:relative">
                @if($user->hasProfilePhoto())
                <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->name }}"
                     style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:3px solid var(--color-primary-200)">
                @else
                <div class="avatar avatar-lg">{{ mb_strtoupper(mb_substr(trim(($firstName ?? '').' '.($lastName ?? '')) ?: ($user->name ?: 'U'), 0, 2)) }}</div>
                @endif
                <button type="button" onclick="document.getElementById('perfil-foto-input').click()"
                        style="position:absolute;bottom:-2px;right:-2px;width:22px;height:22px;border-radius:50%;background:var(--color-primary-600);border:2px solid #fff;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0"
                        title="Trocar foto">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                    </svg>
                </button>
                <form id="perfil-foto-form" method="POST" action="{{ route('perfil.foto') }}" enctype="multipart/form-data" style="display:none">
                    @csrf
                    <input type="file" id="perfil-foto-input" name="foto" accept="image/jpeg,image/png,image/webp" onchange="document.getElementById('perfil-foto-form').submit()">
                </form>
            </div>
            <div>
                <h1 class="page-title" style="margin-bottom:6px">Informações da conta</h1>
                <p class="page-subtitle" style="line-height:1.6">Atualize seus dados pessoais sem sair do padrão visual atual do painel administrativo.</p>
            </div>
        </div>
        <a href="{{ route('perfil.senha') }}" class="btn btn-secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Alterar senha
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-error">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Verifique os campos do perfil antes de salvar.
</div>
@endif

<div class="perfil-grid" style="display:grid;grid-template-columns:minmax(0,1.1fr) minmax(300px,0.8fr);gap:20px;align-items:start">
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Dados pessoais</div>
                <div class="card-subtitle">Informações usadas na identificação dentro da plataforma.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('perfil.update') }}">
            @csrf
            @method('PUT')

            <div class="card-body">
                <div class="perfil-fixed-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Nome</label>
                        <input id="first_name" type="text" name="first_name" class="input @error('first_name') input-error @enderror" value="{{ old('first_name', $firstName) }}" placeholder="Seu nome">
                        @error('first_name')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Sobrenome</label>
                        <input id="last_name" type="text" name="last_name" class="input @error('last_name') input-error @enderror" value="{{ old('last_name', $lastName) }}" placeholder="Seu sobrenome">
                        @error('last_name')
                        <div class="form-error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="input @error('email') input-error @enderror" value="{{ old('email', $user->email) }}" placeholder="seu.email@instituicao.edu.br">
                    @error('email')
                    <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="perfil-fixed-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                    <div class="form-group">
                        <label class="form-label">CPF</label>
                        <input type="text" class="input" value="{{ $user->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $user->cpf) : 'Não informado' }}" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Perfil</label>
                        <input type="text" class="input" value="{{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Administrador', 'coordinator' => 'Coordenador', 'professor' => 'Professor', 'student' => 'Estudante', default => ucfirst($user->role) } }}" disabled>
                    </div>
                </div>
            </div>

            <div class="card-header" style="border-top:1px solid var(--surface-border);border-bottom:none;border-radius:0 0 12px 12px">
                <div style="font-size:0.78rem;color:var(--text-secondary)">Os dados de identificação institucional permanecem preservados neste fluxo.</div>
                <button type="submit" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Salvar perfil
                </button>
            </div>
        </form>
    </section>

    <div style="display:grid;gap:20px">
        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Conta atual</div>
                    <div class="card-subtitle">Resumo do usuário autenticado.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:14px">
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Nome completo</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ trim(($firstName ?? '') . ' ' . ($lastName ?? '')) }}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Email</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $user->email ?: 'Não informado' }}</div>
                </div>
                <div>
                    <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Sistema</div>
                    <div style="margin-top:4px;font-size:0.92rem;font-weight:700;color:var(--text-primary)">{{ $user->clientSystem?->name ?? 'Conta institucional global' }}</div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Segurança</div>
                    <div class="card-subtitle">Ações rápidas sobre a conta.</div>
                </div>
            </div>
            <div class="card-body" style="display:grid;gap:10px">
                <a href="{{ route('perfil.senha') }}" class="btn btn-secondary" style="justify-content:center">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Alterar senha
                </a>
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 1100px) {
        .perfil-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 720px) {
        .perfil-fixed-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endpush
