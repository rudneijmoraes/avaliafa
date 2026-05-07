<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Entrar - AvaliaFA</title>

    <script>
        (function () {
            const t = localStorage.getItem('avalia-theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: ['class', '[data-theme="dark"]'] }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --color-primary-900: #0F2044;
            --color-primary-700: #1D4ED8;
            --color-primary-600: #2563EB;
            --color-primary-100: #DBEAFE;
            --color-primary-50:  #EFF6FF;
            --color-danger:      #EF4444;
            --color-danger-bg:   #FEE2E2;
            --surface-card:      #FFFFFF;
            --surface-border:    #E2E8F0;
            --surface-input:     #FFFFFF;
            --text-primary:      #0F172A;
            --text-secondary:    #64748B;
            --text-muted:        #94A3B8;
        }
        [data-theme="dark"] {
            --color-primary-50:  #172554;
            --color-primary-100: #1E3A5F;
            --color-primary-600: #3B82F6;
            --color-primary-700: #60A5FA;
            --color-danger:      #F87171;
            --color-danger-bg:   #7F1D1D;
            --surface-card:      #1E293B;
            --surface-border:    #334155;
            --surface-input:     #1E293B;
            --text-primary:      #F1F5F9;
            --text-secondary:    #94A3B8;
            --text-muted:        #64748B;
        }
        *, *::before, *::after {
            box-sizing: border-box;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.15s ease;
        }
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            padding: 20px;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.12) 0%, transparent 24%),
                linear-gradient(135deg, #0F2044 0%, #1D4ED8 52%, #0EA5E9 100%);
            color: var(--text-primary);
        }
        .login-shell {
            min-height: calc(100vh - 40px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-layout {
            width: min(1120px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(400px, 480px);
            gap: 22px;
            align-items: stretch;
        }
        .login-aside {
            padding: 34px;
            border-radius: 28px;
            color: #FFFFFF;
            background: linear-gradient(145deg, rgba(15,32,68,0.82) 0%, rgba(29,78,216,0.72) 100%);
            border: 1px solid rgba(191,219,254,0.22);
            box-shadow: 0 26px 90px rgba(15,32,68,0.28);
        }
        .brand-mark {
            width: min(240px, 100%);
            min-height: 64px;
            display: flex;
            align-items: center;
        }
        .brand-mark img {
            display: block;
            width: 100%;
            max-width: 220px;
            height: auto;
            object-fit: contain;
        }
        .brand-eyebrow {
            margin: 24px 0 8px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(219,234,254,0.82);
        }
        .brand-title {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            line-height: 1;
            letter-spacing: -0.04em;
        }
        .brand-copy {
            margin: 14px 0 0;
            font-size: 1rem;
            line-height: 1.7;
            color: rgba(239,246,255,0.86);
        }
        .feature-list {
            margin: 28px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }
        .feature-item {
            display: grid;
            grid-template-columns: 40px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.12);
        }
        .feature-title {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 700;
        }
        .feature-copy {
            margin: 4px 0 0;
            font-size: 0.84rem;
            line-height: 1.55;
            color: rgba(239,246,255,0.78);
        }
        .login-card {
            position: relative;
            background: var(--surface-card);
            border-radius: 28px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 26px 90px rgba(15,23,42,0.2);
            padding: 32px;
            animation: cardIn 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .theme-toggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 100;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.16);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.22);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
        }
        .login-title {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-primary);
        }
        .login-subtitle {
            margin: 8px 0 22px;
            font-size: 0.92rem;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        .login-info,
        .alert-error-box {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 18px;
        }
        .login-info {
            background: var(--color-primary-50);
            border: 1px solid var(--color-primary-100);
            font-size: 0.84rem;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        .login-info-highlight {
            background: linear-gradient(135deg, rgba(37,99,235,0.10) 0%, rgba(14,165,233,0.08) 100%);
            border: 1px solid rgba(37,99,235,0.22);
            box-shadow: 0 10px 24px rgba(37,99,235,0.08);
        }
        .alert-error-box {
            background: var(--color-danger-bg);
            border: 1px solid rgba(239,68,68,0.2);
            font-size: 0.875rem;
            color: var(--color-danger);
        }
        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 7px;
        }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            background: var(--surface-input);
            border: 1.5px solid var(--surface-border);
            border-radius: 12px;
            font-size: 0.9rem;
            color: var(--text-primary);
            font-family: 'Inter', Arial, sans-serif;
            outline: none;
        }
        .input:focus {
            border-color: var(--color-primary-600);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .input-error { border-color: var(--color-danger) !important; }
        .input-mono { font-family: 'JetBrains Mono', monospace; letter-spacing: 0.08em; }
        .cpf-preview {
            height: 16px;
            margin-top: 5px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .cpf-preview.has-value { color: var(--color-primary-600); }
        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            font-size: 0.84rem;
        }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            cursor: pointer;
        }
        .link-inline {
            color: var(--color-primary-600);
            text-decoration: none;
            font-weight: 600;
        }
        .btn-login {
            width: 100%;
            min-height: 52px;
            padding: 13px 18px;
            margin-top: 8px;
            border: none;
            border-radius: 14px;
            background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 100%);
            color: white;
            font-size: 0.96rem;
            font-weight: 700;
            font-family: 'Inter', Arial, sans-serif;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(29,78,216,0.28);
        }
        .btn-login:hover { transform: translateY(-2px); }
        .btn-login:disabled { opacity: 0.68; cursor: not-allowed; transform: none; }
        .login-footer {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--surface-border);
            text-align: center;
            font-size: 0.78rem;
            line-height: 1.65;
            color: var(--text-muted);
        }
        @media (max-width: 980px) {
            body { padding: 16px; }
            .login-shell { min-height: calc(100vh - 32px); }
            .login-layout { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .login-card,
            .login-aside { padding: 22px 18px; border-radius: 22px; }
            .login-options { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <button
        x-data="{ dark: document.documentElement.getAttribute('data-theme') === 'dark' }"
        @click="dark = !dark; localStorage.setItem('avalia-theme', dark ? 'dark' : 'light'); document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')"
        class="theme-toggle"
        :title="dark ? 'Modo claro' : 'Modo escuro'"
    >
        <svg x-show="!dark" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
        </svg>
        <svg x-show="dark" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="5"/>
            <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        </svg>
    </button>

    <div class="login-shell">
        <div class="login-layout">
            <aside class="login-aside">
                <div class="brand-mark">
                    <img src="{{ asset('imagem/logo-deitada-transparente.png') }}" alt="Logo da Faculdade Anasps">
                </div>
                <!-- <p class="brand-eyebrow">Faculdade Anasps</p> -->
                <h1 class="brand-title">AvaliaFA</h1>
                <p class="brand-copy">
                    Entre no ambiente de avaliação para acompanhar provas, consultar notas e acessar os fluxos oficiais da instituição com melhor leitura no desktop.
                </p>
                <ul class="feature-list">
                    <li class="feature-item">
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                            </svg>
                        </div>
                        <div>
                            <p class="feature-title">Painel do aluno simplificado</p>
                            <p class="feature-copy">Acesso direto a provas e notas, sem menus operacionais para quem entra com CPF.</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div>
                            <p class="feature-title">Consulta nos dois ambientes</p>
                            <p class="feature-copy">Resultado no AvaliaFA e continuidade no Moodle quando a atividade exigir validação institucional.</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <div class="feature-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="feature-title">Fluxo protegido de prova</p>
                            <p class="feature-copy">Identificação, checklist e inicio de prova continuam integrados ao mesmo padrão visual.</p>
                        </div>
                    </li>
                </ul>
            </aside>

            @php $simuladoSlug ??= ''; @endphp
            <section class="login-card" x-data="{
                cpf: '',
                cpfFormatted: '',
                showPass: false,
                loading: false,
                formatCpf(value) {
                    const digits = value.replace(/\D/g, '').slice(0, 11);
                    this.cpf = digits;
                    if (digits.length <= 3) this.cpfFormatted = digits;
                    else if (digits.length <= 6) this.cpfFormatted = digits.slice(0,3) + '.' + digits.slice(3);
                    else if (digits.length <= 9) this.cpfFormatted = digits.slice(0,3) + '.' + digits.slice(3,6) + '.' + digits.slice(6);
                    else this.cpfFormatted = digits.slice(0,3) + '.' + digits.slice(3,6) + '.' + digits.slice(6,9) + '-' + digits.slice(9,11);
                }
            }">
                <h2 class="login-title">Entrar no sistema</h2>
                <!-- <p class="login-subtitle">Use seu CPF e sua senha para continuar no ambiente de avaliações da Faculdade Anasps. No primeiro acesso, a senha padrão e o CPF sem formatação.</p> -->

                <div class="login-info login-info-highlight">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;color:var(--color-primary-600)">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <span>Acesse com seu <strong>CPF</strong>. <br>No primeiro acesso, utilize como senha o <strong>CPF sem formatação</strong>.</span>
                </div>

                @if(\App\Http\Middleware\MaintenanceMode::isActive())
                <div style="background:#FEF3C7;border:1px solid #F59E0B;border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;gap:10px;align-items:flex-start;font-size:0.82rem;color:#92400E">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span><strong>Sistema em manutenção.</strong><br>O acesso para alunos está temporariamente indisponível. Tente novamente em breve.</span>
                </div>
                @endif

                @if($errors->any() || session('error'))
                <div class="alert-error-box">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    @if($errors->has('cpf'))
                        {{ $errors->first('cpf') }}
                    @elseif(session('error'))
                        {{ session('error') }}
                    @else
                        CPF ou senha incorretos. Verifique seus dados e tente novamente.
                    @endif
                </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" @submit="loading = true">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="cpf">CPF</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </span>
                            <input
                                id="cpf" name="cpf" type="text"
                                class="input input-mono {{ $errors->has('cpf') ? 'input-error' : '' }}"
                                placeholder="000.000.000-00"
                                maxlength="14"
                                autocomplete="username"
                                autofocus
                                :value="cpfFormatted"
                                @input="formatCpf($event.target.value); $event.target.value = cpfFormatted"
                                @keypress="if(!/[\d]/.test($event.key) && $event.key !== 'Backspace') $event.preventDefault()"
                                required
                            >
                        </div>
                        <input type="hidden" name="cpf_digits" :value="cpf">
                        <div class="cpf-preview" :class="{ 'has-value': cpfFormatted.length > 0 }">
                            <span x-show="cpfFormatted.length === 0">Digite seu CPF para identificação</span>
                            <span x-show="cpfFormatted.length > 0 && cpfFormatted.length < 14">CPF: <span x-text="cpfFormatted"></span></span>
                            <span x-show="cpfFormatted.length === 14">✓ CPF: <span x-text="cpfFormatted"></span></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Senha</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </span>
                            <input
                                id="password" name="password"
                                :type="showPass ? 'text' : 'password'"
                                class="input {{ $errors->any() ? 'input-error' : '' }}"
                                style="padding-right:44px"
                                placeholder="Sua senha"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                @click="showPass = !showPass"
                                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0"
                            >
                                <svg x-show="!showPass" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg x-show="showPass" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="login-options">
                        <label class="remember-row">
                            <input type="checkbox" name="remember" style="width:15px;height:15px;accent-color:var(--color-primary-600)">
                            Manter conectado
                        </label>
                        <a href="#" class="link-inline">Esqueci a senha</a>
                    </div>

                    <button type="submit" class="btn-login" :disabled="loading">
                        <span x-show="!loading">Entrar no sistema</span>
                        <span x-show="loading" style="display:flex;align-items:center;gap:8px;justify-content:center">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="animation:spin 0.8s linear infinite">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                            Autenticando...
                        </span>
                    </button>
                </form>

                @if(!empty($simuladoSlug))
                <div style="margin-top:20px;padding-top:18px;border-top:1px solid var(--surface-border)">
                    <p style="margin:0 0 12px;font-size:0.84rem;color:var(--text-secondary);text-align:center">Ainda não tem cadastro?</p>
                    <a href="{{ route('simulados.public.inscricao', $simuladoSlug) }}?simulado={{ urlencode($simuladoSlug) }}"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;min-height:46px;padding:11px 18px;border-radius:12px;border:1.5px solid var(--color-primary-600);color:var(--color-primary-600);font-weight:700;font-size:0.9rem;text-decoration:none;background:var(--color-primary-50);font-family:'Inter',Arial,sans-serif">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                            <line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                        Criar cadastro para o simulado
                    </a>
                </div>
                @endif

                <div class="login-footer">
                    <p style="margin:0">@include('partials.system-footer-text')</p>
                    <p style="margin:4px 0 0">Problemas? <a href="mailto:suporteaoaluno@faculdadeanasps.com.br" class="link-inline">suporteaoaluno@faculdadeanasps.com.br</a></p>
                </div>
            </section>
        </div>
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</body>
</html>
