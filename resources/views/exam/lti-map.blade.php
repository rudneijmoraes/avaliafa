<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vincular Prova - AvaliaFA</title>

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
                radial-gradient(circle at top left, rgba(99,102,241,0.12) 0%, transparent 25%),
                linear-gradient(135deg, #0F172A 0%, #1E1B4B 55%, #312E81 100%);
            color: #0F172A;
        }
        .map-shell {
            min-height: calc(100vh - 48px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .map-layout {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(340px, 0.95fr);
            gap: 20px;
        }
        .map-story,
        .map-card {
            border-radius: 26px;
            overflow: hidden;
            box-shadow: 0 24px 80px rgba(0,0,0,0.22);
        }
        .map-story {
            padding: 34px;
            color: white;
            background: linear-gradient(145deg, rgba(30,27,75,0.84) 0%, rgba(79,70,229,0.7) 100%);
            border: 1px solid rgba(199,210,254,0.2);
        }
        .story-icon {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4F46E5, #6366F1);
        }
        .story-eyebrow {
            margin: 24px 0 8px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(224,231,255,0.8);
        }
        .story-title {
            margin: 0;
            font-size: clamp(2rem, 3.2vw, 2.8rem);
            line-height: 1.04;
            letter-spacing: -0.04em;
        }
        .story-copy {
            margin: 14px 0 0;
            font-size: 0.98rem;
            line-height: 1.7;
            color: rgba(238,242,255,0.82);
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
            color: rgba(238,242,255,0.8);
        }
        .map-card {
            background: white;
            border: 1px solid #C7D2FE;
            animation: cardIn 0.45s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .map-card-top {
            padding: 28px 28px 22px;
            background: linear-gradient(135deg, #EEF2FF, #E0E7FF);
            border-bottom: 1px solid #C7D2FE;
        }
        .map-title { margin: 0; font-size: 1.45rem; font-weight: 800; color: #312E81; }
        .map-subtitle { margin: 8px 0 0; font-size: 0.9rem; line-height: 1.6; color: #4F46E5; }
        .map-body { padding: 24px 28px 28px; }
        .map-info {
            padding: 14px;
            margin-bottom: 18px;
            border-radius: 16px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            font-size: 0.84rem;
            color: #475569;
        }
        .map-info-row { display: flex; gap: 8px; margin-bottom: 6px; }
        .map-info-row:last-child { margin-bottom: 0; }
        .map-info-label { font-weight: 700; color: #334155; min-width: 100px; flex-shrink: 0; }
        .map-info-value { color: #64748B; word-break: break-all; }
        .form-group { margin-bottom: 18px; }
        .form-label { display: block; font-size: 0.84rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .form-select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            font-size: 0.9rem;
            font-family: inherit;
            color: #1E293B;
            background: white;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
        .form-select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.15);
        }
        .map-actions { display: grid; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 50px;
            padding: 12px 18px;
            border: 0;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #4F46E5, #4338CA);
            color: white;
        }
        .btn-primary:disabled { background: #A5B4FC; cursor: not-allowed; }
        .map-footer {
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #F1F5F9;
            font-size: 0.76rem;
            color: #94A3B8;
            text-align: center;
        }
        @media (max-width: 900px) {
            body { padding: 16px; }
            .map-shell { min-height: calc(100vh - 32px); }
            .map-layout { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="map-shell">
        <div class="map-layout">
            <section class="map-story">
                <div class="story-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                    </svg>
                </div>
                <p class="story-eyebrow">Integracao LTI</p>
                <h1 class="story-title">Vincule a atividade do Moodle com mais contexto</h1>
                <p class="story-copy">
                    A tela agora separa contexto institucional e acao operacional. Isso evita a leitura espremida e deixa a escolha da prova mais clara no desktop.
                </p>
                <ul class="story-points">
                    <li class="story-point">Confira curso, plataforma e resource link antes de salvar.</li>
                    <li class="story-point">Selecione a prova correta do AvaliaFA para os proximos acessos reaproveitarem este vinculo.</li>
                    <li class="story-point">Depois de salvar, o retorno ao Moodle continua no fluxo ja associado a prova escolhida.</li>
                </ul>
            </section>

            <section class="map-card">
                <div class="map-card-top">
                    <h1 class="map-title">Vincular Atividade do Moodle</h1>
                    <p class="map-subtitle">Selecione a prova do AvaliaFA que corresponde a esta atividade.</p>
                </div>
                <div class="map-body">
                    <div class="map-info">
                        @if(!empty($contextTitle))
                        <div class="map-info-row">
                            <span class="map-info-label">Curso:</span>
                            <span class="map-info-value">{{ $contextTitle }}</span>
                        </div>
                        @endif
                        @if(!empty($contextLabel))
                        <div class="map-info-row">
                            <span class="map-info-label">Codigo:</span>
                            <span class="map-info-value">{{ $contextLabel }}</span>
                        </div>
                        @endif
                        <div class="map-info-row">
                            <span class="map-info-label">Plataforma:</span>
                            <span class="map-info-value">{{ $platformName ?? 'Moodle' }}</span>
                        </div>
                        <div class="map-info-row">
                            <span class="map-info-label">Resource Link:</span>
                            <span class="map-info-value">{{ $resourceLinkId }}</span>
                        </div>
                    </div>

                    <form action="{{ route('lti.map-resource') }}" method="POST">
                        @csrf
                        <input type="hidden" name="registration_id" value="{{ $registrationId }}">
                        <input type="hidden" name="resource_link_id" value="{{ $resourceLinkId }}">
                        <input type="hidden" name="context_id" value="{{ $contextId }}">
                        <input type="hidden" name="context_label" value="{{ $contextLabel }}">
                        <input type="hidden" name="context_title" value="{{ $contextTitle }}">
                        <input type="hidden" name="lineitem_url" value="{{ $lineitemUrl }}">
                        <input type="hidden" name="lineitems_url" value="{{ $lineitemsUrl }}">
                        <input type="hidden" name="id_token" value="{{ $idToken }}">
                        <input type="hidden" name="state" value="{{ $state }}">

                        <div class="form-group">
                            <label class="form-label">Selecione a prova do AvaliaFA</label>
                            <select name="exam_id" class="form-select" required id="exam-select">
                                <option value="">- Escolha uma prova -</option>
                                @foreach($exams as $exam)
                                    <option value="{{ $exam->id }}">{{ $exam->title }} - {{ $exam->status === 'published' ? 'Publicada' : ($exam->status === 'active' ? 'Ativa' : ucfirst($exam->status)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="map-actions">
                            <button type="submit" class="btn btn-primary" id="btn-save" disabled>
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Vincular e continuar
                            </button>
                        </div>
                    </form>

                    <div class="map-footer">
                        AvaliaFA - Faculdade Anasps
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
    document.getElementById('exam-select').addEventListener('change', function() {
        document.getElementById('btn-save').disabled = !this.value;
    });
    </script>
</body>
</html>
