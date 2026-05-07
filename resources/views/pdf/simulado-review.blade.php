<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9.8pt; color: #1a1a1a; line-height: 1.3; }
        .sheet { width: 100%; }
        .header { border: 1px solid #DBEAFE; border-radius: 7px; padding: 5px 8px 6px; margin-bottom: 6px; background: #FFFFFF; }
        .brand-wrap { width: 100%; text-align: center; margin: 0; line-height: 1; }
        .brand-inline { display: inline-block; }
        .brand-logo { height: 16px; width: auto; display: inline-block; vertical-align: middle; }
        .brand-logo + .brand-logo { margin-left: 40px; }
        .header h1 { font-size: 12pt; margin: 3px 0 0; letter-spacing: 0.01em; line-height: 1.1; color: #1E3A8A; text-align: center; }
        .header p { font-size: 8.2pt; color: #475569; text-align: center; margin-top: 1px; }
        .info-box { background: #F8FBFF; border: 1px solid #DBEAFE; border-radius: 8px; padding: 6px 8px; margin-bottom: 6px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-cell { width: 33.33%; padding: 3px 5px; border-right: 1px solid #E0EDFF; vertical-align: top; }
        .info-cell:last-child { border-right: 0; }
        .info-label { font-weight: bold; color: #0369A1; font-size: 7.6pt; text-transform: uppercase; letter-spacing: 0.04em; }
        .info-value { color: #1a1a1a; }
        .stats-strip { margin: 2px 0 12px; padding: 2px 0 4px; }
        .stats-table { width: 100%; border-collapse: separate; border-spacing: 10px 0; margin: 0; table-layout: fixed; }
        .stats-table td { width: 25%; vertical-align: top; }
        .stat-box { background: #FFFFFF; border: 1px solid #D8E3F2; border-radius: 8px; padding: 8px 6px 9px; text-align: center; min-height: 56px; }
        .stat-value { font-size: 13.5pt; font-weight: bold; color: #1D4ED8; line-height: 1; margin-bottom: 6px; }
        .stat-label { font-size: 7.2pt; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; line-height: 1; }
        .stat-box.correct { border-top: 2px solid #10B981; }
        .stat-box.correct .stat-value { color: #059669; }
        .stat-box.wrong { border-top: 2px solid #EF4444; }
        .stat-box.wrong .stat-value { color: #DC2626; }
        .stat-box.neutral { border-top: 2px solid #2563EB; }
        .question { margin: 4px 0 6px; border: 1px solid #E2E8F0; border-radius: 7px; overflow: hidden; }
        .question-header { padding: 5px 8px; background: #F8FAFF; border-bottom: 1px solid #E2E8F0; }
        .question-header-table { width: 100%; border-collapse: collapse; }
        .question-header-right { text-align: right; }
        .question-number { font-weight: bold; color: #1D4ED8; }
        .question-status { font-size: 7.3pt; font-weight: bold; padding: 2px 7px; border-radius: 20px; }
        .status-correct { background: #D1FAE5; color: #065F46; }
        .status-wrong { background: #FEE2E2; color: #991B1B; }
        .status-unanswered { background: #F3F4F6; color: #6B7280; }
        .question-body { padding: 6px 8px; }
        .question-content { margin-bottom: 4px; font-size: 9.2pt; }
        .choices { margin-left: 2px; }
        .choice-table { width: 100%; border-collapse: collapse; }
        .choice-row { border-bottom: 1px dashed #E2E8F0; }
        .choice-row:last-child { border-bottom: 0; }
        .choice-cell-marker { width: 21px; padding: 3px 2px 3px 0; vertical-align: top; }
        .choice-cell-text { padding: 3px 0; font-size: 8.9pt; }
        .choice-marker { width: 14px; height: 14px; border-radius: 50%; border: 1.3px solid #CBD5E1; display: inline-flex; align-items: center; justify-content: center; font-size: 7.2pt; font-weight: bold; flex-shrink: 0; }
        .choice.correct { border-color: #059669; background: #D1FAE5; color: #065F46; }
        .choice.wrong { border-color: #DC2626; background: #FEE2E2; color: #991B1B; }
        .footer { margin-top: 6px; padding-top: 5px; border-top: 1px solid #E2E8F0; text-align: center; color: #64748B; font-size: 7.4pt; }
        .badge-correct { color: #059669; font-weight: bold; }
        .badge-wrong { color: #DC2626; font-weight: bold; }
        .comment-box { margin-top: 4px; padding: 4px 6px; border: 1px solid #DBEAFE; background: #F8FAFF; border-radius: 5px; }
        .comment-title { font-size: 7.3pt; font-weight: bold; color: #1D4ED8; margin-bottom: 1px; text-transform: uppercase; }
        .comment-text { font-size: 8.3pt; color: #334155; line-height: 1.25; }
    </style>
</head>
<body>
    <div class="sheet">
    <div class="header">
        <div class="brand-wrap">
            <span class="brand-inline">
                @if(filled($logoAnasps ?? null))
                    <img src="{{ $logoAnasps }}" alt="Anasps" class="brand-logo" style="height:16px;width:auto;vertical-align:middle;margin:0">
                @endif
                @if(filled($logoFaculdade ?? null))
                    <img src="{{ $logoFaculdade }}" alt="Faculdade Anasps" class="brand-logo" style="height:16px;width:auto;vertical-align:middle;margin:0 0 0 40px">
                @endif
            </span>
        </div>
        <h1>Revisão · {{ $simulado->name }}</h1>
        <p>Gerado em {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="info-box">
        <table class="info-table">
            <tr>
                <td class="info-cell">
                    <div class="info-label">Aluno</div>
                    <div class="info-value">{{ $participantName }}</div>
                </td>
                <td class="info-cell">
                    <div class="info-label">CPF</div>
                    <div class="info-value">{{ $participantCpf }}</div>
                </td>
                <td class="info-cell">
                    <div class="info-label">Concluído em</div>
                    <div class="info-value">{{ $completedAt ? $completedAt->format('d/m/Y H:i') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="stats-strip">
        <table class="stats-table">
            <tr>
                <td>
                    <div class="stat-box correct">
                        <div class="stat-value">{{ $correctCount }}</div>
                        <div class="stat-label">Acertos</div>
                    </div>
                </td>
                <td>
                    <div class="stat-box wrong">
                        <div class="stat-value">{{ $wrongCount }}</div>
                        <div class="stat-label">Erros</div>
                    </div>
                </td>
                <td>
                    <div class="stat-box neutral">
                        <div class="stat-value">{{ $totalCount }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </td>
                <td>
                    <div class="stat-box neutral">
                        <div class="stat-value">{{ $percentage }}%</div>
                        <div class="stat-label">% Acerto</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @foreach($questions as $index => $question)
        @php
            $hasAnswer = $question['answer'] !== null;
            $isCorrect = $hasAnswer && ($question['answer']['is_correct'] ?? false);
        @endphp
        <div class="question">
            <div class="question-header">
                <table class="question-header-table">
                    <tr>
                        <td><span class="question-number">Questão {{ $index + 1 }}</span></td>
                        <td class="question-header-right">
                            @if(!$hasAnswer)
                                <span class="question-status status-unanswered">Não respondida</span>
                            @elseif($isCorrect)
                                <span class="question-status status-correct">Correta</span>
                            @else
                                <span class="question-status status-wrong">Incorreta</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            <div class="question-body">
                <div class="question-content">{!! $question['content'] !!}</div>
                <div class="choices">
                    <table class="choice-table">
                        @foreach($question['choices'] as $choice)
                            @php
                                $selectedChoiceIds = collect(data_get($question, 'answer.selected_choice_ids', []))
                                    ->map(fn ($choiceId) => (int) $choiceId)
                                    ->all();
                                $isSelected = $hasAnswer && in_array((int) $choice['id'], $selectedChoiceIds, true);
                                $isCorrectChoice = $choice['is_correct'];
                                $choiceClass = '';
                                if ($isSelected && $isCorrectChoice) $choiceClass = 'correct';
                                elseif ($isSelected && !$isCorrectChoice) $choiceClass = 'wrong';
                                elseif ($isCorrectChoice) $choiceClass = 'correct';
                            @endphp
                            <tr class="choice-row">
                                <td class="choice-cell-marker">
                                    <span class="choice-marker {{ $choiceClass }}">{{ $loop->iteration }}</span>
                                </td>
                                <td class="choice-cell-text">
                                    {!! $choice['content'] !!}
                                    @if($isSelected && $isCorrectChoice)
                                        <span class="badge-correct">✓ Sua resposta (correta)</span>
                                    @elseif($isSelected && !$isCorrectChoice)
                                        <span class="badge-wrong">✗ Sua resposta</span>
                                    @elseif($isCorrectChoice)
                                        <span class="badge-correct">✓ Correta</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
                @if(filled($question['explanation'] ?? null))
                    <div class="comment-box">
                        <div class="comment-title">Comentário da questão</div>
                        <div class="comment-text">{!! $question['explanation'] !!}</div>
                    </div>
                @endif
                @if(filled(data_get($question, 'answer.feedback')))
                    <div class="comment-box">
                        <div class="comment-title">Comentário da correção</div>
                        <div class="comment-text">{!! data_get($question, 'answer.feedback') !!}</div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="footer">
        <p>AvaliaFA - Sistema de Simulados da Faculdade Anasps</p>
        <p>Este documento é apenas para fins de revisão e não possui valor oficial.</p>
    </div>
    </div>
</body>
</html>
