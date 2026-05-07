@extends('layouts.app')

@section('title', 'Configurações - Perfis de Acesso')
@section('page-title', 'Perfis de Acesso')

@section('content')
<div class="page-header">
    <h1 class="page-title">Perfis de Acesso por Setor</h1>
    <p class="page-subtitle">Cadastre e gerencie perfis: Administrador, Criador de Prova e Comercial.</p>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-header">
        <div>
            <div class="card-title">Novo cadastro de acesso</div>
            <div class="card-subtitle">Crie usuários internos com permissões por setor.</div>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('configuracoes.acessos.salvar') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
            @csrf
            <div>
                <label class="form-label">Nome</label>
                <input class="input" name="first_name" value="{{ old('first_name') }}" required>
            </div>
            <div>
                <label class="form-label">Sobrenome</label>
                <input class="input" name="last_name" value="{{ old('last_name') }}" required>
            </div>
            <div>
                <label class="form-label">E-mail</label>
                <input class="input" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div>
                <label class="form-label">CPF</label>
                <input class="input" name="cpf" value="{{ old('cpf') }}" required>
            </div>
            <div>
                <label class="form-label">Senha inicial</label>
                <input class="input" type="password" name="password" required>
            </div>
            <div>
                <label class="form-label">Perfil</label>
                <select class="input" name="profile" required>
                    <option value="administrador" {{ old('profile') === 'administrador' ? 'selected' : '' }}>Administrador</option>
                    <option value="criador_prova" {{ old('profile') === 'criador_prova' ? 'selected' : '' }}>Criador de Prova</option>
                    <option value="comercial" {{ old('profile') === 'comercial' ? 'selected' : '' }}>Comercial</option>
                </select>
            </div>
            <div>
                <label class="form-label">Sistema</label>
                <select class="input" name="client_system_id">
                    <option value="">Sem vínculo específico</option>
                    @foreach($systems as $system)
                    <option value="{{ $system->id }}" {{ (string) old('client_system_id') === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:flex-end">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--text-secondary)">
                    <input type="checkbox" name="active" value="1" {{ old('active', '1') ? 'checked' : '' }}>
                    Usuário ativo
                </label>
            </div>
            <div style="display:flex;align-items:flex-end;justify-content:flex-end;grid-column:1/-1">
                <button class="btn btn-primary" type="submit">Salvar perfil</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Perfis cadastrados</div>
            <div class="card-subtitle">Atualize perfil e status de acesso dos setores internos.</div>
        </div>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>E-mail</th>
                    <th>CPF</th>
                    <th>Sistema</th>
                    <th>Perfil</th>
                    <th>Status</th>
                    <th style="text-align:right">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $profile = match($user->role) {
                        'admin' => 'administrador',
                        'professor' => 'criador_prova',
                        default => 'comercial',
                    };
                    $profileLabel = match($user->role) {
                        'admin' => 'Administrador',
                        'professor' => 'Criador de Prova',
                        default => 'Comercial',
                    };
                @endphp
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $user->cpf) : '-' }}</td>
                    <td>{{ $user->clientSystem?->name ?? '-' }}</td>
                    <td><span class="badge badge-primary">{{ $profileLabel }}</span></td>
                    <td>
                        <span class="badge {{ $user->active ? 'badge-success' : 'badge-danger' }}">
                            {{ $user->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('configuracoes.acessos.atualizar', $user) }}" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                            @csrf
                            @method('PATCH')
                            <select class="input" name="profile" style="width:170px;height:34px;padding:4px 10px">
                                <option value="administrador" {{ $profile === 'administrador' ? 'selected' : '' }}>Administrador</option>
                                <option value="criador_prova" {{ $profile === 'criador_prova' ? 'selected' : '' }}>Criador de Prova</option>
                                <option value="comercial" {{ $profile === 'comercial' ? 'selected' : '' }}>Comercial</option>
                            </select>
                            <select class="input" name="client_system_id" style="width:170px;height:34px;padding:4px 10px">
                                <option value="">Sem vínculo específico</option>
                                @foreach($systems as $system)
                                <option value="{{ $system->id }}" {{ (string) $user->client_system_id === (string) $system->id ? 'selected' : '' }}>{{ $system->name }}</option>
                                @endforeach
                            </select>
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--text-secondary)">
                                <input type="checkbox" name="active" value="1" {{ $user->active ? 'checked' : '' }}>
                                Ativo
                            </label>
                            <button class="btn btn-secondary btn-sm" type="submit">Atualizar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:28px;color:var(--text-muted)">Nenhum perfil setorial cadastrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:12px 20px">
        {{ $users->links() }}
    </div>
</div>
@endsection

