@extends('layouts.app')

@php
    $isEdit = $hub !== null;
    $action = $isEdit ? route('simulados.hubs.update', $hub) : route('simulados.hubs.store');
@endphp

@section('title', ($isEdit ? 'Editar Hub' : 'Novo Hub').' — AvaliaFA')
@section('page-title', $isEdit ? 'Editar Hub' : 'Novo Hub')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
        <div>
            <h1 class="page-title">{{ $isEdit ? 'Editar hub / ciclo' : 'Novo hub / ciclo' }}</h1>
            <p class="page-subtitle">Centralize vários simulados em um único link público.</p>
        </div>
        <a href="{{ $isEdit ? route('simulados.hubs.show', $hub) : route('simulados.hubs.index') }}" class="btn btn-secondary btn-sm">Voltar</a>
    </div>
</div>

<div class="card" style="padding:20px">
    <form method="POST" action="{{ $action }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px">
        @csrf
        @if($isEdit)
        @method('PUT')
        @endif

        <div>
            <label class="form-label">Sistema</label>
            <select class="input" name="client_system_id" required {{ auth()->user()->isSuperAdmin() ? '' : 'disabled' }}>
                @foreach($systems as $system)
                <option value="{{ $system->id }}" @selected((string) old('client_system_id', $hub?->client_system_id ?? auth()->user()->client_system_id) === (string) $system->id)>{{ $system->name }}</option>
                @endforeach
            </select>
            @if(! auth()->user()->isSuperAdmin())
            <input type="hidden" name="client_system_id" value="{{ old('client_system_id', $hub?->client_system_id ?? auth()->user()->client_system_id) }}">
            @endif
        </div>

        <div>
            <label class="form-label">Status</label>
            <select class="input" name="status" required>
                @foreach(['draft' => 'Rascunho', 'active' => 'Ativo', 'inactive' => 'Inativo', 'archived' => 'Arquivado'] as $key => $label)
                <option value="{{ $key }}" @selected(old('status', $hub?->status ?? 'active') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="form-label">Ordem</label>
            <input class="input" type="number" min="1" max="999" name="sort_order" value="{{ old('sort_order', $hub?->sort_order ?? 1) }}" required>
        </div>

        <div style="grid-column:1/-1">
            <label class="form-label">Nome do hub</label>
            <input class="input" name="name" value="{{ old('name', $hub?->name) }}" required>
        </div>

        <div>
            <label class="form-label">Slug público</label>
            <input class="input" name="slug" value="{{ old('slug', $hub?->slug) }}" placeholder="ex: concurso-inss-2026" required>
        </div>

        <div>
            <label class="form-label">Título da landing</label>
            <input class="input" name="landing_title" value="{{ old('landing_title', $hub?->landing_title) }}" placeholder="Ex.: Ciclo CNU 2026">
        </div>

        <div style="grid-column:1/-1">
            <label class="form-label">Descrição</label>
            <textarea class="input" name="description" rows="4">{{ old('description', $hub?->description) }}</textarea>
        </div>

        <div style="grid-column:1/-1;display:flex;gap:8px;justify-content:flex-end">
            <a href="{{ $isEdit ? route('simulados.hubs.show', $hub) : route('simulados.hubs.index') }}" class="btn btn-ghost btn-sm">Cancelar</a>
            <button class="btn btn-primary btn-sm" type="submit">Salvar</button>
        </div>
    </form>
</div>
@endsection
