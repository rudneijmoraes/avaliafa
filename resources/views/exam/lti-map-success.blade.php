<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vinculo Configurado - AvaliaFA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            padding: 24px;
            font-family: 'Inter', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(16,185,129,0.12) 0%, transparent 24%),
                linear-gradient(135deg, #0F172A 0%, #14532D 58%, #047857 100%);
            color: #0F172A;
        }
        .success-shell {
            min-height: calc(100vh - 48px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-layout {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.02fr) minmax(300px, 0.9fr);
            gap: 20px;
        }
        .success-story,
        .success-card {
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0,0,0,0.22);
        }
        .success-story {
            padding: 34px;
            color: white;
            background: linear-gradient(145deg, rgba(6,95,70,0.84) 0%, rgba(16,185,129,0.64) 100%);
            border: 1px solid rgba(167,243,208,0.22);
        }
        .story-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #059669, #10B981);
        }
        .story-eyebrow {
            margin: 24px 0 8px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(220,252,231,0.78);
        }
        .story-title {
            margin: 0;
            font-size: clamp(2rem, 3.2vw, 2.7rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
        }
        .story-copy {
            margin: 14px 0 0;
            font-size: 0.98rem;
            line-height: 1.7;
            color: rgba(236,253,245,0.82);
        }
        .story-points {
            margin: 28px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }
        .story-point {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            font-size: 0.88rem;
            line-height: 1.6;
            color: rgba(236,253,245,0.8);
        }
        .success-card {
            background: white;
            border: 1px solid #A7F3D0;
            animation: cardIn 0.45s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .success-card-top {
            padding: 28px 28px 22px;
            background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
            border-bottom: 1px solid #A7F3D0;
        }
        .success-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #065F46; }
        .success-body { padding: 24px 28px 28px; }
        .success-message {
            margin: 0 0 18px;
            font-size: 0.94rem;
            line-height: 1.7;
            color: #475569;
        }
        .success-detail {
            padding: 16px;
            margin-bottom: 18px;
            border-radius: 16px;
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            font-size: 0.84rem;
            line-height: 1.6;
            color: #166534;
        }
        .success-note {
            margin: 0;
            font-size: 0.84rem;
            line-height: 1.65;
            color: #64748B;
        }
        .success-footer {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #F1F5F9;
            font-size: 0.76rem;
            color: #94A3B8;
            text-align: center;
        }
        @media (max-width: 900px) {
            body { padding: 16px; }
            .success-shell { min-height: calc(100vh - 32px); }
            .success-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="success-shell">
        <div class="success-layout">
            <section class="success-story">
                <div class="story-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <p class="story-eyebrow">Vinculo salvo</p>
                <h1 class="story-title">Atividade conectada com sucesso ao AvaliaFA</h1>
                <p class="story-copy">
                    A confirmacao final agora aparece em composicao horizontal, com explicacao de um lado e resumo da configuracao do outro.
                </p>
                <ul class="story-points">
                    <li class="story-point">Os proximos acessos dos alunos ja podem reaproveitar o vinculo salvo.</li>
                    <li class="story-point">A prova vinculada fica registrada no contexto da integracao para o fluxo do Moodle.</li>
                    <li class="story-point">A janela pode ser fechada assim que a conferencia for concluida.</li>
                </ul>
            </section>

            <section class="success-card">
                <div class="success-card-top">
                    <h1 class="success-title">Vinculo Configurado</h1>
                </div>
                <div class="success-body">
                    <p class="success-message">
                        A atividade do Moodle foi vinculada com sucesso a prova do AvaliaFA.
                    </p>
                    <div class="success-detail">
                        <strong>{{ $examTitle }}</strong>
                        @if(!empty($contextTitle))
                            <br><span style="font-size:0.75rem;color:#15803D">Curso: {{ $contextTitle }}</span>
                        @endif
                    </div>
                    <p class="success-note" style="margin-bottom:14px">
                        A partir de agora, todos os alunos que acessarem esta atividade no Moodle serao direcionados automaticamente para esta prova.
                    </p>
                    <p class="success-note">
                        Voce pode fechar esta janela e voltar ao Moodle.
                    </p>
                    <div class="success-footer">
                        AvaliaFA - Faculdade Anasps
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
