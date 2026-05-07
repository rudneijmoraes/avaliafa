@extends('layouts.app')

@section('title', 'Ranking de Simulados — AvaliaFA')
@section('page-title', 'Ranking de Simulados')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">🏆 Ranking de Simulados</h1>
        <p class="page-subtitle">Os melhores desempenho dos nossos alunos.</p>
    </div>
</div>

<div class="card">
    <div style="padding:20px">
        @if(empty($ranking))
            <div style="text-align:center;padding:40px;color:var(--text-muted)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px;display:block;opacity:0.4">
                    <path d="M8 21h8m-4-4v4m-4-9l4-4 4 4M4 5h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                </svg>
                <p>Nenhum resultado encontrado ainda.</p>
                <p style="font-size:0.85rem">Complete simulados para aparecer no ranking.</p>
            </div>
        @else
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="border-bottom:2px solid var(--surface-border)">
                        <th style="text-align:center;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">#</th>
                        <th style="text-align:left;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">Aluno</th>
                        <th style="text-align:center;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">Simulados</th>
                        <th style="text-align:center;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">Acertos</th>
                        <th style="text-align:center;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">% Aprov.</th>
                        <th style="text-align:center;padding:10px 8px;font-size:0.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase">Pontos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ranking as $index => $row)
                        <tr style="border-bottom:1px solid var(--surface-border)">
                            <td style="text-align:center;padding:12px 8px">
                                @if($index === 0)
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:#FEF3C7;border-radius:50%;font-size:0.75rem;font-weight:800;color:#92400E">🥇</span>
                                @elseif($index === 1)
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:#E5E7EB;border-radius:50%;font-size:0.75rem;font-weight:800;color:#6B7280">🥈</span>
                                @elseif($index === 2)
                                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:#FDE68A;border-radius:50%;font-size:0.75rem;font-weight:800;color:#92400E">🥉</span>
                                @else
                                    <span style="font-size:0.85rem;font-weight:600;color:var(--text-muted)">{{ $index + 1 }}</span>
                                @endif
                            </td>
                            <td style="padding:12px 8px">
                                <div style="font-weight:600;color:var(--text-primary)">{{ $row['first_name'] }} {{ $row['last_name'] }}</div>
                                @if($showCpf && !empty($row['cpf']))
                                    <div style="font-size:0.75rem;color:var(--text-muted);font-family:monospace">{{ $row['cpf'] }}</div>
                                @endif
                            </td>
                            <td style="text-align:center;padding:12px 8px">
                                <span style="font-weight:700;color:var(--text-primary)">{{ $row['total_simulados'] }}</span>
                            </td>
                            <td style="text-align:center;padding:12px 8px">
                                <span style="font-weight:700;color:#059669">{{ $row['total_correct'] }}/{{ $row['total_questions'] }}</span>
                            </td>
                            <td style="text-align:center;padding:12px 8px">
                                <span style="display:inline-block;padding:4px 8px;background:{{ ($row['percentage_correct'] ?? 0) >= 70 ? '#D1FAE5' : '#FEE2E2' }};color:{{ ($row['percentage_correct'] ?? 0) >= 70 ? '#065F46' : '#991B1B' }};border-radius:6px;font-size:0.8rem;font-weight:700">
                                    {{ $row['percentage_correct'] ?? 0 }}%
                                </span>
                            </td>
                            <td style="text-align:center;padding:12px 8px">
                                <span style="font-weight:800;color:#1D4ED8;font-size:1rem">{{ $row['score'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
