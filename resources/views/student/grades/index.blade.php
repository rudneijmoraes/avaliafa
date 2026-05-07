@extends('layouts.app')

@section('title', 'Minhas Notas')
@section('page-title', 'Minhas Notas')

@section('content')
<div class="page-header">
    <h1 class="page-title">Minhas notas</h1>
    <p class="page-subtitle">Consulte suas provas finalizadas, a nota registrada e o tempo gasto em cada tentativa.</p>
</div>

<section class="card" style="margin-bottom:18px">
    <div class="card-header">
        <div>
            <div class="card-title">Simulados recentes para você</div>
            <div class="card-subtitle">Atalhos para simulados ativos que ainda não foram concluídos.</div>
        </div>
    </div>
    <div class="card-body">
        @if($recentSimulados->isEmpty())
        <div style="padding:24px;border:1px dashed var(--surface-border);border-radius:12px;color:var(--text-secondary);text-align:center">
            Nenhum simulado pendente encontrado no momento.
        </div>
        @else
        <div style="display:grid;gap:14px">
            @foreach($recentSimulados as $simulado)
            <article style="border:1px solid var(--surface-border);border-radius:12px;padding:18px 20px;display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap">
                <div style="min-width:220px;flex:1">
                    <div style="font-size:1rem;font-weight:700;color:var(--text-primary)">{{ $simulado['name'] }}</div>
                    <div style="margin-top:6px;font-size:0.82rem;color:var(--text-secondary)">
                        {{ \Illuminate\Support\Str::limit($simulado['description'] ?: 'Simulado disponível para você.', 120) }}
                    </div>
                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
                        <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:var(--surface-muted);font-size:0.72rem;font-weight:700;color:var(--text-secondary)">
                            {{ $simulado['questions_count'] }} questão(ões)
                        </span>
                        @if($simulado['has_pending_registration'])
                        <span style="display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:rgba(245,158,11,0.12);font-size:0.72rem;font-weight:700;color:#B45309">
                            Em andamento
                        </span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <a href="{{ route('simulados.public.inscricao', $simulado['slug']) }}" class="btn btn-secondary">
                        {{ $simulado['has_pending_registration'] ? 'Continuar simulado' : 'Iniciar simulado' }}
                    </a>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </div>
</section>

<section class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Resultados concluídos</div>
            <div class="card-subtitle">{{ $sessions->count() }} prova(s) com nota final disponível</div>
        </div>
    </div>
    <div class="card-body">
        @if($sessions->isEmpty())
        <div style="padding:24px;border:1px dashed var(--surface-border);border-radius:12px;color:var(--text-secondary);text-align:center">
            Nenhuma nota final disponível no momento.
        </div>
        @else
        <div style="display:grid;gap:14px">
            @foreach($sessions as $session)
            <article style="border:1px solid var(--surface-border);border-radius:12px;padding:18px 20px;display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap">
                <div style="min-width:220px;flex:1">
                    <div style="font-size:1rem;font-weight:700;color:var(--text-primary)">{{ $session->exam?->title ?? 'Prova removida' }}</div>
                    <div style="margin-top:6px;font-size:0.82rem;color:var(--text-secondary)">
                        Enviada em {{ $session->submitted_at?->format('d/m/Y H:i') ?? '-' }}
                    </div>
                </div>
                <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Nota final</div>
                        <div
                            style="margin-top:4px;font-size:1.3rem;font-weight:800;color:var(--color-primary-700)"
                            data-final-score="{{ (string) $session->final_score }}"
                        >{{ number_format((float) $session->final_score, 2, ',', '.') }}</div>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Tempo gasto</div>
                        <div style="margin-top:4px;font-size:0.95rem;font-weight:700;color:var(--text-primary)">{{ $session->formattedTimeSpent() }}</div>
                    </div>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        @if($session->is_simulation && $session->exam?->simulado?->status === 'active')
                        <a href="{{ route('simulados.public.inscricao', $session->exam->simulado->slug) }}" class="btn btn-ghost">Reiniciar simulado</a>
                        @endif
                        <a href="{{ route('student.grades.show', $session) }}" class="btn btn-secondary">Ver detalhes</a>
                    </div>
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endsection
