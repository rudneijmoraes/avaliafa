@extends('layouts.app')

@section('title', 'Certificados — AvaliaFA')
@section('page-title', 'Certificados')

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
        <div>
            <h1 class="page-title">Certificados Emitidos</h1>
            <p class="page-subtitle">Consulte certificados, status de validade e link público de verificação.</p>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:24px">
    @php
        $kpis = [
            ['label' => 'Total', 'value' => $stats['total'], 'color' => '#2563EB', 'bg' => 'rgba(37,99,235,0.1)'],
            ['label' => 'Válidos', 'value' => $stats['valid'], 'color' => '#10B981', 'bg' => '#D1FAE5'],
            ['label' => 'Revogados', 'value' => $stats['revoked'], 'color' => '#EF4444', 'bg' => '#FEE2E2'],
            ['label' => 'Com PDF', 'value' => $stats['with_pdf'], 'color' => '#8B5CF6', 'bg' => 'rgba(139,92,246,0.14)'],
        ];
    @endphp
    @foreach($kpis as $kpi)
    <div style="background:var(--surface-card);border:1px solid var(--surface-border);border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:12px">
        <div style="width:36px;height:36px;border-radius:9px;background:{{ $kpi['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span style="font-size:1rem;font-weight:800;color:{{ $kpi['color'] }}">{{ $kpi['value'] }}</span>
        </div>
        <div style="font-size:0.8125rem;font-weight:500;color:var(--text-secondary)">{{ $kpi['label'] }}</div>
    </div>
    @endforeach
</div>

<div class="card" style="margin-bottom:16px">
    <div style="padding:14px 20px">
        <form method="GET" action="{{ route('certificados.index') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="margin-bottom:4px">Busca</label>
                <input type="text" name="busca" value="{{ request('busca') }}" class="input" placeholder="Código, estudante, CPF, email ou prova..." style="height:36px;padding:7px 12px">
            </div>
            <div style="min-width:150px">
                <label class="form-label" style="margin-bottom:4px">Status</label>
                <select name="status" class="input" style="height:36px;padding:6px 12px">
                    <option value="">Todos</option>
                    <option value="valid" {{ request('status') === 'valid' ? 'selected' : '' }}>Válido</option>
                    <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revogado</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="height:36px">Filtrar</button>
            @if(request()->hasAny(['busca', 'status']))
            <a href="{{ route('certificados.index') }}" class="btn btn-ghost btn-sm" style="height:36px">Limpar</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Estudante</th>
                    <th>Prova</th>
                    <th>Nota</th>
                    <th>Emissão</th>
                    <th>Status</th>
                    <th style="text-align:right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $certificate)
                <tr>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.78rem">{{ $certificate->code }}</td>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary)">{{ $certificate->student?->name ?? 'Estudante removido' }}</div>
                        <div style="font-size:0.74rem;color:var(--text-muted)">{{ $certificate->student?->cpf ?? 'Sem CPF' }}</div>
                    </td>
                    <td style="font-size:0.82rem;color:var(--text-secondary)">{{ $certificate->exam?->title ?? 'Prova removida' }}</td>
                    <td style="font-family:'JetBrains Mono',monospace;font-size:0.8rem">{{ number_format((float) $certificate->final_score, 2, ',', '.') }}</td>
                    <td style="font-size:0.78rem;color:var(--text-secondary)">{{ $certificate->issued_at?->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($certificate->isValid())
                        <span class="badge badge-success">Válido</span>
                        @else
                        <span class="badge badge-danger">Revogado</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;justify-content:flex-end;gap:6px">
                            <form method="POST" action="{{ route('certificados.status', $certificate) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-sm">
                                    {{ $certificate->isValid() ? 'Revogar' : 'Reativar' }}
                                </button>
                            </form>
                            @if($certificate->pdf_path)
                            <a href="{{ route('certificados.download', $certificate) }}" class="btn btn-ghost btn-sm">
                                PDF
                            </a>
                            @endif
                            <a href="{{ route('certificados.verify', $certificate->code) }}" target="_blank" class="btn btn-secondary btn-sm">
                                Verificar
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:48px 16px;color:var(--text-muted)">
                        Nenhum certificado encontrado.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($certificates->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--surface-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="font-size:0.8rem;color:var(--text-muted)">
            {{ $certificates->firstItem() }}–{{ $certificates->lastItem() }} de {{ $certificates->total() }} certificados
        </span>
        <div style="display:flex;gap:4px">
            @if($certificates->onFirstPage())
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">‹ Anterior</span>
            @else
                <a href="{{ $certificates->previousPageUrl() }}" class="btn btn-ghost btn-sm">‹ Anterior</a>
            @endif
            @if($certificates->hasMorePages())
                <a href="{{ $certificates->nextPageUrl() }}" class="btn btn-ghost btn-sm">Próxima ›</a>
            @else
                <span class="btn btn-ghost btn-sm" style="opacity:0.4;cursor:default">Próxima ›</span>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
