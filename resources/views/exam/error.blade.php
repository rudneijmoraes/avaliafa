<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Invalido - AvaliaFA</title>

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
                radial-gradient(circle at top left, rgba(248,113,113,0.14) 0%, transparent 26%),
                linear-gradient(135deg, #0F172A 0%, #1E1B4B 55%, #312E81 100%);
            color: #0F172A;
        }
        .error-shell {
            min-height: calc(100vh - 48px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-layout {
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 20px;
        }
        .error-story,
        .error-card {
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0,0,0,0.22);
        }
        .error-story {
            padding: 34px;
            color: white;
            background: linear-gradient(145deg, rgba(15,23,42,0.84) 0%, rgba(49,46,129,0.76) 100%);
            border: 1px solid rgba(248,113,113,0.2);
        }
        .story-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #EF4444, #F87171);
        }
        .story-eyebrow {
            margin: 24px 0 8px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(254,226,226,0.78);
        }
        .story-title {
            margin: 0;
            font-size: clamp(2rem, 3.4vw, 2.8rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
        }
        .story-copy {
            margin: 14px 0 0;
            font-size: 0.98rem;
            line-height: 1.7;
            color: rgba(248,250,252,0.84);
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
            color: rgba(248,250,252,0.82);
        }
        .error-card {
            background: white;
            border: 1px solid rgba(254,202,202,0.9);
            animation: cardIn 0.45s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .error-card-top {
            padding: 30px 28px 24px;
            background: linear-gradient(135deg, #FEF2F2, #FEE2E2);
            border-bottom: 1px solid #FECACA;
        }
        .error-card-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
            color: #7F1D1D;
        }
        .error-card-body {
            padding: 24px 28px 28px;
        }
        .error-message {
            margin: 0 0 18px;
            font-size: 0.95rem;
            line-height: 1.7;
            color: #475569;
        }
        .error-note {
            padding: 14px 16px;
            margin-bottom: 18px;
            border-radius: 16px;
            background: #F8FAFF;
            border: 1px solid #E2E8F0;
            font-size: 0.84rem;
            line-height: 1.6;
            color: #64748B;
        }
        .error-actions {
            display: grid;
            gap: 10px;
        }
        .error-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 12px 16px;
            border-radius: 14px;
            border: 0;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
        }
        .error-btn-primary {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: #FFFFFF;
        }
        .error-btn-secondary {
            background: #EEF2FF;
            color: #3730A3;
            border: 1px solid #C7D2FE;
        }
        .error-footer {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #F1F5F9;
            font-size: 0.75rem;
            color: #94A3B8;
            text-align: center;
        }
        @media (max-width: 900px) {
            body { padding: 16px; }
            .error-shell { min-height: calc(100vh - 32px); }
            .error-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="error-shell">
        <div class="error-layout">
            <section class="error-story">
                <div class="story-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                </div>
                <p class="story-eyebrow">Fluxo interrompido</p>
                <h1 class="story-title">Nao foi possivel continuar esta tentativa</h1>
                <p class="story-copy">
                    Algo impediu o acesso a esta sessao de prova. Verifique a mensagem ao lado para entender o que aconteceu.
                </p>
                <ul class="story-points">
                    <li class="story-point">Se voce ja concluiu o simulado, acesse "Meus Simulados" para ver seu resultado.</li>
                    <li class="story-point">Se a sessao expirou ou o link e invalido, acesse "Meus Simulados" e inicie uma nova tentativa.</li>
                    <li class="story-point">Se o problema persistir, entre em contato com a instituicao.</li>
                </ul>
            </section>

            <section class="error-card">
                <div class="error-card-top">
                    <h1 class="error-card-title">Acesso Invalido</h1>
                </div>
                <div class="error-card-body">
                    <p class="error-message">
                        {{ $message ?? 'Ocorreu um erro ao acessar a prova.' }}
                    </p>
                    <div class="error-note">
                        Se o problema persistir, entre em contato com a instituicao ou solicite um novo link de acesso.
                    </div>
                    <div class="error-actions">
                        <a href="{{ route('simulados.minha-area') }}" class="error-btn error-btn-primary">Ir para Meus Simulados</a>
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="error-btn error-btn-secondary">Voltar</a>
                    </div>
                    <div class="error-footer">
                        AvaliaFA - Faculdade Anasps
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
