@extends('layouts.app')

@section('title', 'Detalhe da Nota')
@section('page-title', 'Detalhe da Nota')

@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap">
    <div>
        <h1 class="page-title">{{ $exam->title }}</h1>
        <p class="page-subtitle">Resumo da tentativa, nota final registrada e respostas enviadas.</p>
    </div>
    <a href="{{ route('student.grades.index') }}" class="btn btn-ghost">Voltar</a>
</div>

<section class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:18px">
        <div>
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Nota final</div>
            <div
                style="margin-top:6px;font-size:1.5rem;font-weight:800;color:var(--color-primary-700)"
                data-final-score="{{ (string) $session->final_score }}"
            >{{ number_format((float) $session->final_score, 2, ',', '.') }}</div>
        </div>
        <div>
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Tempo gasto</div>
            <div style="margin-top:6px;font-size:1rem;font-weight:700;color:var(--text-primary)">{{ $session->formattedTimeSpent() }}</div>
        </div>
        <div>
            <div style="font-size:0.72rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Envio</div>
            <div style="margin-top:6px;font-size:1rem;font-weight:700;color:var(--text-primary)">{{ $session->submitted_at?->format('d/m/Y H:i') ?? '-' }}</div>
        </div>
    </div>
</section>

<section class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Respostas registradas</div>
            <div class="card-subtitle">{{ $answers->count() }} questao(oes) respondida(s)</div>
        </div>
    </div>
    <div class="card-body">
        <div style="display:grid;gap:16px">
            @forelse($answers as $answer)
            <article style="border:1px solid var(--surface-border);border-radius:12px;padding:18px 20px">
                <div style="font-size:0.95rem;font-weight:700;color:var(--text-primary)">{!! $answer->question?->content !!}</div>

                <div style="margin-top:14px;font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em">Sua resposta</div>

                @if($answer->question?->type === 'multiple_answer')
                    @php
                        $selectedIds = collect($answer->choice_ids ?? [])->map(fn ($id) => (int) $id)->all();
                    @endphp
                    <div style="margin-top:8px;display:grid;gap:8px">
                        @foreach($answer->question?->choices ?? [] as $choice)
                        <div style="padding:10px 12px;border-radius:10px;border:1px solid {{ in_array($choice->id, $selectedIds, true) ? 'var(--color-primary-100)' : 'var(--surface-border)' }};background:{{ in_array($choice->id, $selectedIds, true) ? 'var(--color-primary-50)' : 'transparent' }}">
                            {{ $choice->content }}
                        </div>
                        @endforeach
                    </div>
                @elseif($answer->choice)
                    <div style="margin-top:8px;padding:10px 12px;border-radius:10px;border:1px solid var(--color-primary-100);background:var(--color-primary-50);font-weight:600;color:var(--text-primary)">
                        {{ $answer->choice->content }}
                    </div>
                @elseif($answer->text_answer)
                    <div style="margin-top:8px;padding:10px 12px;border-radius:10px;border:1px solid var(--surface-border);background:var(--surface-bg);color:var(--text-primary)">
                        {{ $answer->text_answer }}
                    </div>
                @elseif($answer->order_answer)
                    <div style="margin-top:8px;padding:10px 12px;border-radius:10px;border:1px solid var(--surface-border);background:var(--surface-bg);color:var(--text-primary)">
                        {{ implode(' → ', $answer->order_answer) }}
                    </div>
                @else
                    <div style="margin-top:8px;color:var(--text-secondary)">Nenhuma resposta registrada.</div>
                @endif
            </article>
            @empty
            <div style="padding:24px;border:1px dashed var(--surface-border);border-radius:12px;color:var(--text-secondary);text-align:center">
                Nenhuma resposta disponivel para esta tentativa.
            </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
