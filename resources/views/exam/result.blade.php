<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prova Enviada - AvaliaFA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            padding: 28px;
            background:
                radial-gradient(circle at top left, rgba(255,255,255,0.12) 0%, transparent 28%),
                linear-gradient(135deg, #0F2044 0%, #1D4ED8 58%, #0EA5E9 100%);
            color: #0F172A;
        }
        .result-shell {
            min-height: calc(100vh - 56px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .result-card {
            width: min(1120px, 100%);
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(219,234,254,0.8);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 100px rgba(15,32,68,0.28);
            backdrop-filter: blur(10px);
            animation: cardIn 0.45s cubic-bezier(0.34, 1.4, 0.64, 1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .hero {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            padding: 28px 34px;
            background: linear-gradient(135deg, #EFF6FF 0%, #DCEAFE 100%);
            border-bottom: 1px solid rgba(191,219,254,0.9);
        }
        .hero-icon {
            width: 76px;
            height: 76px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1D4ED8, #0EA5E9);
            box-shadow: 0 14px 30px rgba(29,78,216,0.28);
            flex-shrink: 0;
        }
        .hero-eyebrow {
            margin: 0 0 6px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #2563EB;
        }
        .hero-title {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.35rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #0F172A;
            line-height: 1.05;
        }
        .hero-subtitle {
            margin: 10px 0 0;
            max-width: 720px;
            font-size: 1rem;
            line-height: 1.65;
            color: #334155;
        }
        .result-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.95fr);
            gap: 22px;
            padding: 26px;
            background: linear-gradient(180deg, rgba(255,255,255,0.96) 0%, #F8FBFF 100%);
        }
        .result-main,
        .result-side {
            display: grid;
            gap: 18px;
            align-content: start;
        }
        .panel {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 22px;
            padding: 22px 24px;
            box-shadow: 0 10px 28px rgba(15,23,42,0.05);
        }
        .panel-title {
            margin: 0 0 8px;
            font-size: 0.95rem;
            font-weight: 800;
            color: #1D4ED8;
        }
        .panel-copy {
            margin: 0;
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.65;
        }
        .details-panel .details-title {
            margin: 0 0 14px;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #94A3B8;
        }
        .details-panel.compact {
            padding: 18px 20px;
        }
        .details-panel.compact .details-title {
            margin-bottom: 10px;
        }
        .details-panel.compact .detail-row {
            padding: 10px 0;
        }
        .detail-row {
            display: grid;
            grid-template-columns: minmax(120px, 150px) minmax(0, 1fr);
            align-items: center;
            gap: 18px;
            padding: 14px 0;
            border-bottom: 1px solid #E2E8F0;
            font-size: 0.96rem;
        }
        .detail-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .detail-label {
            color: #64748B;
        }
        .detail-value {
            color: #0F172A;
            font-weight: 700;
            text-align: right;
            justify-self: end;
        }
        .detail-student {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .detail-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #DBEAFE;
        }
        .mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.84rem;
        }
        .metrics-grid {
            display: grid;
            gap: 14px;
        }
        .metric-card {
            background: linear-gradient(135deg, #F8FAFC 0%, #EEF4FF 100%);
            border: 1px solid #DBEAFE;
            border-radius: 22px;
            padding: 22px;
            min-height: 162px;
        }
        .metric-label {
            margin: 0;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748B;
        }
        .metric-value {
            margin: 10px 0 0;
            font-size: 2.35rem;
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #0F172A;
            line-height: 1;
        }
        .metric-value.score {
            color: #1D4ED8;
        }
        .metric-hint {
            margin: 10px 0 0;
            font-size: 0.9rem;
            line-height: 1.55;
            color: #64748B;
        }
        .actions-panel {
            display: grid;
            gap: 14px;
        }
        .actions-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: #0F172A;
        }
        .actions-copy {
            margin: 0;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #64748B;
        }
        .button-stack {
            display: grid;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            min-height: 52px;
            padding: 12px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-size: 0.96rem;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: #FFFFFF;
            box-shadow: 0 10px 24px rgba(29,78,216,0.22);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(29,78,216,0.3);
        }
        .btn-secondary {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(29,78,216,0.12);
        }
        .footer-copy {
            margin: 6px 0 0;
            font-size: 0.78rem;
            line-height: 1.6;
            color: #94A3B8;
            text-align: center;
        }
        .simulation-banner {
            background: #FFFBEB;
            border: 1px solid #FDE68A;
            color: #92400E;
        }
        .simulation-contact-card {
            display: grid;
            gap: 14px;
            align-content: start;
            background: linear-gradient(135deg, #FFFDF7 0%, #F8FAFC 100%);
            border: 1px solid #E2E8F0;
        }
        .simulation-contact-brand {
            height: 104px;
            border-radius: 16px;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 10px;
        }
        .simulation-contact-brand img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .simulation-contact-content {
            display: grid;
            gap: 10px;
            align-content: start;
        }
        .simulation-contact-copy {
            margin: 0;
            font-size: 0.84rem;
            color: #475569;
            line-height: 1.45;
            text-align: left;
        }
        .simulation-contact-list {
            display: grid;
            gap: 5px;
            margin: 0;
        }
        .simulation-contact-item {
            font-size: 0.78rem;
            line-height: 1.45;
            color: #334155;
        }
        .simulation-contact-item strong {
            color: #0F172A;
        }
        .btn-inline {
            width: auto;
            min-height: 40px;
            padding: 9px 14px;
            display: inline-flex;
            justify-self: start;
            font-size: 0.88rem;
            border-radius: 10px;
        }
        @media (max-width: 920px) {
            body {
                padding: 16px;
            }
            .result-shell {
                min-height: calc(100vh - 32px);
            }
            .result-grid {
                grid-template-columns: 1fr;
                padding: 18px;
            }
        }
        @media (max-width: 680px) {
            .hero {
                grid-template-columns: 1fr;
                text-align: center;
                justify-items: center;
                padding: 24px 20px;
            }
            .hero-subtitle {
                font-size: 0.94rem;
            }
            .panel,
            .metric-card {
                padding: 18px;
                border-radius: 18px;
            }
            .detail-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }
            .detail-value {
                justify-self: start;
                text-align: left;
            }
            .metric-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    @php
        $isSimulation = (bool) $session->is_simulation;
        $heroTitle = $isSimulation ? 'Simulado Anasps Concluído com Sucesso' : 'Obrigado por concluir sua prova';
        $heroSubtitle = $isSimulation
            ? 'Seu envio foi registrado no sistema AvaliaFA.'
            : 'Seu envio foi registrado com sucesso no AvaliaFA para a prova <strong>'.$exam->title.'</strong>.';
        $panelTitle = $isSimulation ? 'Contexto do simulado' : 'Próxima etapa institucional';
        $panelCopy = $isSimulation
            ? 'Este é um ambiente de simulação, não aprovação/reprovação, emissão de certificado.'
            : 'A correção e a divulgação serão realizadas pelos canais oficiais da instituição. Esta tela não exibe status de aprovação, reprovação ou certificado.';
        $metricLabel = $isSimulation ? 'Pontuação do simulado' : 'Nota final';
        $metricHint = $isSimulation
            ? 'Pontuação exibida apenas para referência do participante neste simulado.'
            : 'Nota registrada imediatamente ao concluir a prova no AvaliaFA.';
        $actionsTitle = $isSimulation ? 'Próximos passos no simulado' : 'Consultar resultados';
        $actionsCopy = $isSimulation
            ? 'Use os atalhos abaixo para revisar sua tentativa no contexto do simulado.'
            : 'Use os atalhos abaixo para conferir a mesma tentativa no ambiente que precisar.';
        $primaryLabel = $isSimulation
            ? ($postExamDestination['label'] ?? 'Voltar para meu painel')
            : $postExamDestination['label'];
    @endphp
    <div class="result-shell">
        <div class="result-card">
            @if($session->launch_source === 'demo')
            <div style="background:#FEF9C3;border-bottom:1px solid #FDE047;padding:10px 24px;display:flex;align-items:center;gap:10px;font-size:.875rem;color:#713F12">
                <span style="font-size:1.1rem">🧪</span>
                <span><strong>Modo demonstração</strong> — Este resultado <strong>não foi registrado</strong> oficialmente. Nenhuma nota, certificado ou sincronização com Moodle foi gerada.</span>
            </div>
            @endif
            <header class="hero">
                <div class="hero-icon">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div>
                    <p class="hero-eyebrow">Entrega confirmada</p>
                    <h1 class="hero-title">{{ $heroTitle }}</h1>
                    <p class="hero-subtitle">{!! $heroSubtitle !!}</p>
                </div>
            </header>

            <div class="result-grid">
                <main class="result-main">
                    @unless($isSimulation)
                    <section class="panel">
                        <p class="panel-title">{{ $panelTitle }}</p>
                        <p class="panel-copy">{{ $panelCopy }}</p>
                    </section>
                    @endunless

                    <section class="panel details-panel {{ $isSimulation ? 'compact' : '' }}">
                        <p class="details-title">Resumo do envio</p>

                        <div class="detail-row">
                            <span class="detail-label">Estudante</span>
                            <span class="detail-value detail-student">
                                @if($student->hasProfilePhoto())
                                <img src="{{ $student->profilePhotoUrl() }}" alt="{{ $student->name }}" class="detail-avatar">
                                @endif
                                {{ $student->name }}
                            </span>
                        </div>

                        @if($student->cpf && preg_match('/^\d{11}$/', $student->cpf))
                        <div class="detail-row">
                            <span class="detail-label">CPF</span>
                            <span class="detail-value mono">
                                {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}
                            </span>
                        </div>
                        @elseif($student->moodle_user_id)
                        <div class="detail-row">
                            <span class="detail-label">ID Moodle</span>
                            <span class="detail-value mono">{{ $student->moodle_user_id }}</span>
                        </div>
                        @endif

                        <div class="detail-row">
                            <span class="detail-label">Início</span>
                            <span class="detail-value">{{ $session->started_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Envio</span>
                            <span class="detail-value">{{ $session->submitted_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Tentativa</span>
                            <span class="detail-value">{{ $session->attempt_number }}ª</span>
                        </div>
                    </section>

                    @if($session->is_simulation)
                    <section class="panel simulation-contact-card">
                        <div class="simulation-contact-brand">
                            <img src="{{ asset('imagem/triade-anasps-horizontal.png') }}" alt="Tríade Anasps">
                        </div>
                        <div class="simulation-contact-content">
                            <p class="simulation-contact-copy">
                                <strong>Conheça a Anasps</strong>
                            </p>
                            <div class="simulation-contact-list">
                                <div class="simulation-contact-item"><strong>Endereço:</strong> SCS Qd 03 Bl. "A" Loja 74/78 Edifício ANASPS – Brasília – DF</div>
                                <div class="simulation-contact-item"><strong>Telefone:</strong> (61) 3321-5651</div>
                                <div class="simulation-contact-item"><strong>E-mail:</strong> anasps@anasps.org.br</div>
                            </div>
                            <a href="https://filie-se.anasps.org.br/" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-inline">
                                Associe-se
                            </a>
                        </div>
                    </section>
                    @endif

                </main>

                <aside class="result-side">
                    <div class="metrics-grid">
                        <section class="metric-card">
                            <p class="metric-label">{{ $metricLabel }}</p>
                            <p class="metric-value score" data-final-score="{{ (string) $session->final_score }}">
                                {{ $session->final_score !== null ? number_format((float) $session->final_score, 2, ',', '.') : '-' }}
                            </p>
                            <p class="metric-hint">{{ $metricHint }}</p>
                        </section>

                        <section class="metric-card">
                            <p class="metric-label">Tempo gasto</p>
                            <p class="metric-value" style="font-size:2rem">{{ $session->formattedTimeSpent() }}</p>
                            <p class="metric-hint">Duração total calculada entre o início e o envio da tentativa.</p>
                        </section>
                    </div>

                    <section class="panel actions-panel">
                        <p class="actions-title">{{ $actionsTitle }}</p>
                        <p class="actions-copy">{{ $actionsCopy }}</p>

                        <div class="button-stack">
                            @auth
                                @if(! $isSimulation && auth()->id() === $student->id && auth()->user()->isStudent())
                                <a href="{{ route('student.grades.show', $session) }}" class="btn btn-secondary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                    </svg>
                                    Ver detalhes no AvaliaFA
                                </a>
                                @endif
                            @endauth

                            <a href="{{ $postExamDestination['url'] }}" class="btn btn-primary">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                                {{ $primaryLabel }}
                            </a>
                        </div>

                        <p class="footer-copy">@include('partials.system-footer-text')</p>
                    </section>

                </aside>
            </div>
        </div>
    </div>
</body>
</html>
