@extends('layouts.app')

@section('title', 'Chamados — AvaliaFA')
@section('page-title', 'Chamados')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Central de chamados</h1>
        <p class="page-subtitle">Abra solicitações e acompanhe os registros do seu sistema.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:minmax(320px,0.95fr) minmax(0,1.45fr);gap:18px;align-items:start">
    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Novo chamado</div>
                <div class="card-subtitle">Use este formulário para registrar uma nova solicitação.</div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('chamados.store') }}" style="display:grid;gap:12px">
                @csrf

                @if(auth()->user()->isSuperAdmin())
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Sistema</label>
                    <select name="client_system_id" class="input" required>
                        <option value="">Selecione</option>
                        @foreach($systems as $system)
                        <option value="{{ $system->id }}" @selected(old('client_system_id') == $system->id)>{{ $system->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Assunto</label>
                    <input type="text" name="subject" class="input" value="{{ old('subject') }}" maxlength="180" required>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Prioridade</label>
                    <select name="priority" class="input" required>
                        @foreach(['low' => 'Baixa', 'normal' => 'Normal', 'high' => 'Alta'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Mensagem</label>
                    <textarea name="message" class="input" rows="6" style="height:auto;padding:12px" maxlength="5000" required>{{ old('message') }}</textarea>
                </div>

                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">Abrir chamado</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Chamados registrados</div>
                <div class="card-subtitle">Acompanhe as solicitações abertas, em andamento e encerradas.</div>
            </div>
            <span class="badge badge-primary">{{ $chamados->total() }} total</span>
        </div>
        <div class="card-body" style="padding-bottom:0">
            <form method="GET" action="{{ route('chamados.index') }}" style="display:grid;grid-template-columns:2fr 1fr auto;gap:12px;margin-bottom:16px">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="q" class="input" value="{{ request('q') }}" placeholder="Assunto, protocolo ou mensagem">
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Status</label>
                    <select name="status" class="input">
                        <option value="">Todos</option>
                        @foreach(['open' => 'Aberto', 'in_progress' => 'Em andamento', 'resolved' => 'Resolvido', 'closed' => 'Fechado'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:flex-end;gap:8px">
                    <button type="submit" class="btn btn-secondary">Filtrar</button>
                </div>
            </form>
        </div>

        <div style="padding:0 24px 20px;display:grid;gap:12px">
            @forelse($chamados as $chamado)
            <article style="border:1px solid var(--surface-border);border-radius:14px;padding:16px;background:var(--surface-card)">
                <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
                    <div>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:4px">
                            <span class="badge badge-primary">{{ $chamado->protocol }}</span>
                            <span class="badge {{ match($chamado->status) { 'open' => 'badge-warning', 'in_progress' => 'badge-primary', 'resolved' => 'badge-success', default => 'badge-neutral' } }}">
                                {{ match($chamado->status) { 'open' => 'Aberto', 'in_progress' => 'Em andamento', 'resolved' => 'Resolvido', default => 'Fechado' } }}
                            </span>
                            <span class="badge {{ $chamado->priority === 'high' ? 'badge-danger' : ($chamado->priority === 'low' ? 'badge-neutral' : 'badge-secondary') }}">
                                {{ match($chamado->priority) { 'high' => 'Alta', 'low' => 'Baixa', default => 'Normal' } }}
                            </span>
                        </div>
                        <div style="font-size:1rem;font-weight:800;color:var(--text-primary)">{{ $chamado->subject }}</div>
                        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px">
                            {{ $chamado->creator?->name ?? 'Usuário' }}
                            @if($canManage && $chamado->clientSystem?->name)
                             · {{ $chamado->clientSystem->name }}
                            @endif
                             · {{ optional($chamado->created_at)->format('d/m/Y H:i') }}
                        </div>
                    </div>

                    @if($canManage)
                    <form method="POST" action="{{ route('chamados.status.update', $chamado) }}" style="display:flex;gap:8px;align-items:center">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="input" style="height:38px;min-width:160px;padding:6px 12px">
                            @foreach(['open' => 'Aberto', 'in_progress' => 'Em andamento', 'resolved' => 'Resolvido', 'closed' => 'Fechado'] as $value => $label)
                            <option value="{{ $value }}" @selected($chamado->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-secondary btn-sm">Salvar</button>
                    </form>
                    @endif
                </div>
                <div style="margin-top:12px;font-size:0.9rem;line-height:1.6;color:var(--text-secondary);white-space:pre-wrap">{{ $chamado->message }}</div>
            </article>
            @empty
            <div style="padding:28px;text-align:center;color:var(--text-muted)">Nenhum chamado encontrado.</div>
            @endforelse
        </div>

        <div style="padding:0 24px 20px">
            {{ $chamados->links() }}
        </div>
    </section>
</div>
@endsection
