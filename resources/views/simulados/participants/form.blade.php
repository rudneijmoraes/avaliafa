@extends('layouts.app')

@section('title', 'Editar Participante — Simulado')
@section('page-title', 'Editar Participante')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Editar Participante</h1>
        <p class="page-subtitle">Atualize os dados do participante vinculado ao simulado {{ $simulado->name }}.</p>
    </div>
</div>

<div class="card" style="padding:20px">
    <form method="POST" action="{{ route('simulados.participants.update', [$simulado, $participant]) }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        @csrf
        @method('PUT')

        <div>
            <label class="form-label">Nome</label>
            <input class="input" name="first_name" value="{{ old('first_name', $participant->first_name) }}" required>
        </div>
        <div>
            <label class="form-label">Sobrenome</label>
            <input class="input" name="last_name" value="{{ old('last_name', $participant->last_name) }}" required>
        </div>
        <div>
            <label class="form-label">E-mail</label>
            <input class="input" type="email" name="email" value="{{ old('email', $participant->email) }}" required>
        </div>
        <div>
            <label class="form-label">Telefone</label>
            <input class="input" name="phone" value="{{ old('phone', $participant->phone) }}" required>
        </div>
        <div>
            <label class="form-label">CPF</label>
            <input class="input" name="cpf" value="{{ old('cpf', $participant->cpf) }}" required>
        </div>

        <div style="grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px">
            <form method="POST" action="{{ route('simulados.participants.destroy', [$simulado, $participant]) }}" onsubmit="return confirmDelete(this, {title:'Remover participante', message:'Deseja remover este participante do simulado?', confirmLabel:'Remover'})" style="margin-right:auto">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger btn-sm" type="submit">Excluir participante</button>
            </form>
            <a href="{{ route('simulados.participants.export.pdf', [$simulado, $participant]) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">PDF personalizado</a>
            <a href="{{ route('simulados.show', $simulado) }}" class="btn btn-ghost btn-sm">Cancelar</a>
            <button class="btn btn-primary btn-sm" type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
