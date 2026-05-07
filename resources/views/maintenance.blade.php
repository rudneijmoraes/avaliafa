<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema em Manutenção — AvaliaFA</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1E3A5F 0%, #2563EB 60%, #1D4ED8 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #fff;
            padding: 24px;
        }

        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .icon {
            width: 72px;
            height: 72px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }

        h1 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        p {
            font-size: 1rem;
            line-height: 1.6;
            opacity: 0.85;
            margin-bottom: 8px;
        }

        .sub {
            font-size: 0.85rem;
            opacity: 0.6;
            margin-top: 28px;
        }

        .logo {
            margin-bottom: 32px;
            opacity: 0.9;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 600;
        }

        a.login-link {
            display: inline-block;
            margin-top: 28px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 2px;
            transition: color 0.15s;
        }
        a.login-link:hover { color: rgba(255,255,255,0.9); }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">AvaliaFA</div>

        <div class="icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>

        <h1>Sistema em Manutenção</h1>
        <p>Estamos realizando melhorias no sistema.</p>
        <p>Em breve estaremos de volta!</p>

        <p class="sub">Se precisar de suporte, entre em contato com a equipe.</p>

        <a href="{{ route('login') }}" class="login-link">Acesso administrativo</a>
    </div>
</body>
</html>
