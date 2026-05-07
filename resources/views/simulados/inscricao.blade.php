@php
    $pageTitle = 'Simulados Anasps';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }} — Inscrição</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            --ink: #0F172A;
            --muted: #475569;
            --card-bg: rgba(255,255,255,0.92);
            --card-border: #DBEAFE;
            --head-border: #E2E8F0;
            --field-border: #BFDBFE;
            --field-bg: #FFFFFF;
            --foot-bg: #F8FAFF;
            --foot-border: #CBD5E1;
        }
        body {
            margin: 0;
            font-family: Inter, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 5% -10%, rgba(37, 99, 235, 0.20), transparent 60%),
                radial-gradient(900px 480px at 95% 110%, rgba(14, 165, 233, 0.20), transparent 60%),
                linear-gradient(135deg, #F8FBFF 0%, #EFF6FF 48%, #F8FAFC 100%);
        }
        .wrap {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 28px 18px;
        }
        .card {
            width: 100%;
            max-width: 880px;
            background: var(--card-bg);
            backdrop-filter: blur(8px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            box-shadow: 0 22px 58px rgba(15, 23, 42, 0.14);
            overflow: hidden;
            position: relative;
        }
        .card::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(160deg, rgba(255,255,255,0.18), transparent 35%, rgba(59,130,246,0.06));
        }
        .head {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--head-border);
            background: linear-gradient(120deg, rgba(37,99,235,0.08), rgba(14,165,233,0.10));
        }
.head-login-btn {
            height: 36px;
            border-radius: 10px;
            border: 1px solid var(--field-border);
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            font-size: 0.82rem;
            font-weight: 700;
            color: #1D4ED8;
            background: #EFF6FF;
            text-decoration: none;
            white-space: nowrap;
        }
        .brand {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .brand-logo {
            width: 180px;
            height: 72px;
            border-radius: 12px;
            background: #FFFFFF;
            border: 1px solid #DBEAFE;
            box-shadow: 0 8px 20px rgba(37,99,235,0.18);
            object-fit: contain;
            padding: 8px;
        }
        .brand-title {
            margin: 0;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #1D4ED8;
        }
        .brand-subtitle {
            margin: 2px 0 0;
            font-size: 0.82rem;
            color: var(--muted);
        }
        .title {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--ink);
        }
        .subtitle {
            margin: 8px 0 0;
            color: #334155;
            font-size: 0.95rem;
        }
        .content { padding: 22px 28px 24px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 14px; }
        label { font-size: 0.78rem; color: var(--muted); display: block; margin-bottom: 6px; font-weight: 600; }
        .robot-check {
            margin-top: 14px;
            padding: 12px 14px;
            border: 1px solid var(--card-border);
            border-radius: 12px;
            background: #F8FAFF;
            display: grid;
            gap: 8px;
        }
        .robot-check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--ink);
            font-weight: 600;
        }
        .robot-check-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 0;
            accent-color: #2563EB;
        }
        .selection-card {
            margin-bottom: 16px;
            padding: 14px;
            border: 1px solid var(--card-border);
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(239, 246, 255, 0.92), rgba(255, 255, 255, 0.98));
        }
        .selection-kicker {
            margin: 0 0 4px;
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #1D4ED8;
        }
        .selection-copy {
            margin: 0 0 10px;
            font-size: 0.84rem;
            color: var(--muted);
            line-height: 1.45;
        }
        select.input,
        input {
            width: 100%;
            height: 44px;
            border: 1px solid var(--field-border);
            border-radius: 12px;
            padding: 0 12px;
            font-size: 0.92rem;
            background: var(--field-bg);
            outline: none;
            transition: all .16s ease;
        }
        select.input {
            appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, #64748B 50%),
                linear-gradient(135deg, #64748B 50%, transparent 50%);
            background-position:
                calc(100% - 18px) calc(50% - 3px),
                calc(100% - 12px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-right: 36px;
        }
        select.input:focus,
        input:focus {
            border-color: #60A5FA;
            box-shadow: 0 0 0 3px rgba(59,130,246,.15);
        }
        .actions { margin-top: 16px; display: flex; justify-content: flex-end; }
        button {
            height: 44px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: #FFFFFF;
            font-weight: 800;
            padding: 0 20px;
            cursor: pointer;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.34);
        }
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            box-shadow: none;
        }
        .foot {
            margin-top: 16px;
            font-size: 0.8rem;
            color: var(--muted);
            background: var(--foot-bg);
            border: 1px dashed var(--foot-border);
            border-radius: 10px;
            padding: 10px 12px;
        }
        .banner {
            margin-top: 16px;
            font-size: 0.85rem;
            color: var(--ink);
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 10px;
            padding: 12px 14px;
            line-height: 1.5;
        }
        .banner h3 {
            margin: 0 0 6px;
            font-size: 0.9rem;
            color: #1D4ED8;
        }
        .banner p {
            margin: 0;
        }
        .banner img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        .platform-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.78rem;
            font-weight: 500;
            color: #94A3B8;
            padding: 10px 12px 2px;
        }
        .errors {
            margin-bottom: 12px;
            font-size: 0.82rem;
            color: #B91C1C;
            background: #FEF2F2;
            border: 1px solid #FECACA;
            padding: 10px 12px;
            border-radius: 10px;
        }
        .warning {
            margin-bottom: 12px;
            font-size: 0.82rem;
            color: #92400E;
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            padding: 10px 12px;
            border-radius: 10px;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --ink: #E2E8F0;
                --muted: #94A3B8;
                --card-bg: rgba(15, 23, 42, 0.78);
                --card-border: rgba(59, 130, 246, 0.34);
                --head-border: rgba(148, 163, 184, 0.24);
                --field-border: rgba(96, 165, 250, 0.46);
                --field-bg: rgba(15, 23, 42, 0.62);
                --foot-bg: rgba(30, 41, 59, 0.54);
                --foot-border: rgba(148, 163, 184, 0.42);
            }
            body {
                background:
                    radial-gradient(1200px 620px at 5% -10%, rgba(37, 99, 235, 0.38), transparent 60%),
                    radial-gradient(900px 500px at 95% 110%, rgba(8, 145, 178, 0.34), transparent 60%),
                    linear-gradient(135deg, #020617 0%, #0B1120 45%, #111827 100%);
            }
            .head {
                background: linear-gradient(120deg, rgba(37,99,235,0.20), rgba(6,182,212,0.18));
            }
            .brand-logo {
                background: rgba(255,255,255,0.94);
            }
            .brand-subtitle,
            .subtitle {
                color: #CBD5E1;
            }
            input {
                color: #E2E8F0;
            }
            input::placeholder {
                color: #94A3B8;
            }
            .errors {
                background: rgba(127, 29, 29, 0.45);
                border-color: rgba(239, 68, 68, 0.5);
                color: #FCA5A5;
            }
        }
        @media (max-width: 640px) {
            .head, .content { padding-left: 18px; padding-right: 18px; }
            .brand-logo { width: 150px; height: 60px; }
            .title { font-size: 1.2rem; }
            .actions { justify-content: stretch; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="head">
            <div class="brand">
                <img
                    src="{{ asset('imagem/logo-anasps.png') }}"
                    alt="Anasps"
                    class="brand-logo"
                >
            </div>
            <h1 class="title">{{ $pageTitle }}</h1>
            <p class="subtitle">Preencha seus dados para acessar os simulados disponíveis deste ciclo.</p>
        </div>
        <div class="content">
            <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
                @auth
                    <a href="{{ route('simulados.minha-area') }}" class="head-login-btn">Entrar na minha área</a>
                @else
                    <a href="{{ route('login', ['simulado' => $selectedSimulado->slug ?? $simulado->slug]) }}" class="head-login-btn">Já tenho cadastro, fazer login</a>
                @endauth
            </div>

            @if ($errors->any())
                <div class="errors">{{ $errors->first() }}</div>
            @endif
            @if(isset($hasQuestions) && ! $hasQuestions)
                <div class="warning">Este simulado ainda não possui questões vinculadas. Solicite a configuração com a equipe responsável antes de iniciar.</div>
            @endif
            @if(!empty($viewerProgress))
                <div style="margin-bottom:14px;padding:14px;border:1px solid var(--card-border);border-radius:16px;background:#F8FAFF">
                    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center">
                        <div>
                            <div style="font-size:0.78rem;font-weight:800;color:#1D4ED8;text-transform:uppercase;letter-spacing:.06em">Seu progresso neste ciclo</div>
                            <div style="font-size:0.9rem;color:var(--ink);margin-top:4px">{{ $viewerProgress['completed_count'] }} concluído(s) · {{ $viewerProgress['pending_count'] }} pendente(s)</div>
                        </div>
                        <div style="font-size:1rem;font-weight:800;color:#0F172A">{{ number_format((float) $viewerProgress['progress_percentage'], 1, ',', '.') }}%</div>
                    </div>
                    <div style="margin-top:10px;height:8px;background:#DBEAFE;border-radius:999px;overflow:hidden">
                        <div style="height:100%;width:{{ min(100, max(0, (float) $viewerProgress['progress_percentage'])) }}%;background:linear-gradient(90deg,#2563EB,#10B981)"></div>
                    </div>
                </div>
            @endif
            <form method="POST" action="{{ route('simulados.public.inscricao.store', $baseSimulado->slug ?? $simulado->slug) }}">
                @csrf
                <input type="hidden" name="simulado_slug" value="{{ $selectedSimulado->slug ?? $simulado->slug }}">
                <input type="hidden" name="captcha_token" value="{{ $captchaToken }}">
                <div class="grid">
                    <div><label>Nome</label><input name="first_name" value="{{ old('first_name') }}" required></div>
                    <div><label>Sobrenome</label><input name="last_name" value="{{ old('last_name') }}" required></div>
                    <div><label>E-mail</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                    <div><label>Telefone</label><input id="phone" name="phone" value="{{ old('phone') }}" required></div>
                    <div><label>CPF</label><input id="cpf" name="cpf" value="{{ old('cpf') }}" required></div>
                    <div><label>Crie uma senha</label><input type="password" name="password" autocomplete="new-password" required></div>
                    <div><label>Confirme a senha</label><input type="password" name="password_confirmation" autocomplete="new-password" required></div>
                    <div><label>Quanto é {{ $captchaFirst }} + {{ $captchaSecond }}?</label><input type="number" min="0" max="99" name="captcha_answer" value="{{ old('captcha_answer') }}" required></div>
                </div>
                <div class="robot-check">
                    <label class="robot-check-row">
                        <input type="checkbox" name="robot_confirm" value="1" @checked(old('robot_confirm')) required>
                        <span>Não sou robô</span>
                    </label>
                    <div style="font-size:0.78rem;color:var(--muted)">Confirme a caixa e resolva a operação para validar o acesso.</div>
                </div>
                <div class="actions">
                    <button type="submit" {{ isset($hasQuestions) && ! $hasQuestions ? 'disabled' : '' }}>Finalizar cadastro e entrar</button>
                </div>
            </form>
            <div class="foot">Ao finalizar o cadastro, você entrará automaticamente na sua área de simulados.</div>
        </div>
    </div>
    @if($inscriptionBanner = \App\Models\Setting::get('simulados', 'inscription_banner'))
        <div class="banner" style="width:100%;max-width:880px;margin:16px auto 0">
            @if($bannerImageUrl = \App\Models\Setting::get('simulados', 'banner_image_url'))
                <img src="{{ $bannerImageUrl }}" alt="Banner do simulado" style="margin-bottom:10px;border-radius:8px">
            @endif
            {!! $inscriptionBanner !!}
        </div>
    @endif
    <footer class="platform-footer">@include('partials.system-footer-text')</footer>
</div>
<script>
(() => {
    const cpfInput = document.getElementById('cpf');
    const phoneInput = document.getElementById('phone');

    const onlyDigits = (value) => value.replace(/\D/g, '');

    const formatCpf = (value) => {
        const digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 3) return digits;
        if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
        if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
    };

    const formatPhone = (value) => {
        const digits = onlyDigits(value).slice(0, 11);
        if (digits.length <= 2) return digits ? `(${digits}` : '';
        if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    };

    if (cpfInput) {
        cpfInput.addEventListener('input', () => {
            cpfInput.value = formatCpf(cpfInput.value);
        });
        cpfInput.value = formatCpf(cpfInput.value);
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', () => {
            phoneInput.value = formatPhone(phoneInput.value);
        });
        phoneInput.value = formatPhone(phoneInput.value);
    }
})();
</script>
</body>
</html>
