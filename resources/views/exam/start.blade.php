@php
    $isSimulation = (bool) $session->is_simulation;
    $simuladoSettings = (array) data_get($exam->settings ?? [], 'simulado', []);
    if ($isSimulation && isset($simulado) && $simulado) {
        $fullscreenRequired = (bool) $simulado->fullscreen_enabled;
        $capturePhotoEnabled = (bool) $simulado->capture_photo_enabled;
    } else {
        $fullscreenRequired = $isSimulation ? (bool) data_get($simuladoSettings, 'fullscreen_enabled', true) : true;
        $capturePhotoEnabled = $isSimulation ? (bool) data_get($simuladoSettings, 'capture_photo_enabled', false) : ! $student->hasProfilePhoto();
    }
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Prova — AvaliaFA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0; font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0F2044 0%, #1D4ED8 55%, #0EA5E9 100%);
            display: flex; align-items: flex-start; justify-content: center;
            padding: 20px 18px 32px; position: relative;
            overflow-y: auto; overflow-x: hidden;
        }
        body::before {
            content: '';
            position: absolute; inset: -40%;
            background: radial-gradient(ellipse at 30% 50%, rgba(139,92,246,0.25) 0%, transparent 60%),
                        radial-gradient(ellipse at 75% 80%, rgba(20,184,166,0.2) 0%, transparent 60%);
            pointer-events: none;
        }
        .start-shell {
            position: relative;
            z-index: 1;
            width: min(1180px, 100%);
        }
        .start-card {
            position: relative;
            background: #fff; border-radius: 20px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.25), 0 8px 20px rgba(0,0,0,0.1);
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
            animation: cardIn 0.5s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(24px) scale(0.95); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-top {
            background: linear-gradient(135deg, #0F2044 0%, #1D4ED8 100%);
            border-radius: 20px 20px 0 0; padding: 18px 22px;
            text-align: center; color: white;
        }
        .exam-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin: 0 auto 14px;
            flex-wrap: wrap;
        }
        .exam-brand-badge,
        .exam-brand-main {
            width: 148px;
            height: 76px;
            background: #FFFFFF;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.28);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 14px;
            box-shadow: 0 10px 24px rgba(15, 32, 68, 0.18);
        }
        .exam-brand-badge img,
        .exam-brand-main img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .card-bottom { padding: 18px; }
        .start-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.38fr) minmax(320px, 0.82fr);
            gap: 18px;
            align-items: start;
        }
        .start-main, .start-side {
            display: grid;
            gap: 12px;
            align-content: start;
        }

        .stat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .stat-item {
            background: #F8FAFF; border: 1px solid #E2E8F0; border-radius: 10px;
            padding: 10px 8px; text-align: center;
        }
        .stat-label { font-size: 0.625rem; font-weight: 700; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.06em; }
        .stat-value { font-size: 1.375rem; font-weight: 800; color: #0F172A; margin-top: 2px; line-height: 1; }
        .stat-unit  { font-size: 0.7rem; font-weight: 400; color: #64748B; }
        .stat-grid-simulado { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }

        .rules-box {
            background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px;
            padding: 12px;
        }
        .rules-title { font-size: 0.8125rem; font-weight: 700; color: #92400E; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
        .rules-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 4px; }
        .rules-list li { font-size: 0.75rem; color: #78350F; display: flex; align-items: flex-start; gap: 6px; line-height: 1.4; }
        .rules-list li::before { content: '→'; color: #F59E0B; font-weight: 700; flex-shrink: 0; }

        .student-bar {
            display: flex; align-items: center; gap: 10px;
            background: #EFF6FF; border: 1px solid #DBEAFE; border-radius: 10px;
            padding: 10px 12px;
        }
        .student-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #1D4ED8, #8B5CF6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.8rem; font-weight: 700; flex-shrink: 0;
        }
        .student-name { font-size: 0.875rem; font-weight: 600; color: #0F172A; }
        .student-cpf  { font-size: 0.72rem; color: #64748B; font-family: 'JetBrains Mono', monospace; margin-top: 1px; }

        .btn-start {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: white; border: none; border-radius: 10px;
            font-size: 0.9375rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', Arial, sans-serif;
            box-shadow: 0 4px 16px rgba(29,78,216,0.4);
            transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 6px 24px rgba(29,78,216,0.5); }
        .btn-start:active { transform: translateY(0) scale(0.98); }
        .btn-start:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .simulation-pill {
            margin: 10px auto 0;
            width: fit-content;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #FEF3C7;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .action-box {
            position: sticky;
            top: 18px;
            background: linear-gradient(180deg, #F8FBFF 0%, #EEF4FF 100%);
            border: 1px solid #BFDBFE;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 12px 32px rgba(15, 32, 68, 0.12);
        }
        .action-title {
            font-size: 0.875rem;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 3px;
        }
        .action-copy {
            font-size: 0.75rem;
            color: #475569;
            line-height: 1.4;
            margin: 0 0 12px;
        }
        .action-copy strong {
            color: #1D4ED8;
        }
        .action-footer {
            text-align: center;
            font-size: 0.75rem;
            color: #64748B;
            margin: 12px 0 0;
        }
        /* Photo capture styles */
        .photo-overlay {
            position: fixed; inset: 0; z-index: 50;
            background: rgba(15, 32, 68, 0.85); backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
        }
        .photo-card {
            background: #fff; border-radius: 20px;
            box-shadow: 0 24px 80px rgba(0,0,0,0.3);
            max-width: 420px; width: 100%; overflow: hidden;
            animation: cardIn 0.4s cubic-bezier(0.34,1.4,0.64,1) forwards;
        }
        .photo-card-top {
            background: linear-gradient(135deg, #7C3AED 0%, #8B5CF6 100%);
            padding: 20px; text-align: center; color: white;
        }
        .photo-card-body { padding: 20px; }
        .webcam-container {
            position: relative; width: 100%; aspect-ratio: 4/3;
            background: #0F172A; border-radius: 12px; overflow: hidden;
            margin-bottom: 16px;
        }
        .webcam-container video, .webcam-container canvas, .webcam-container img {
            width: 100%; height: 100%; object-fit: cover;
            border-radius: 12px;
        }
        .webcam-guide {
            position: absolute; inset: 0;
            display: flex; align-items: center; justify-content: center;
            pointer-events: none;
        }
        .webcam-guide-circle {
            width: 55%; aspect-ratio: 1; border-radius: 50%;
            border: 2px dashed rgba(255,255,255,0.4);
        }
        .photo-btn {
            width: 100%; padding: 12px; border: none; border-radius: 10px;
            font-size: 0.875rem; font-weight: 700; cursor: pointer;
            font-family: 'Inter', Arial, sans-serif; transition: all 0.2s;
        }
        .photo-btn-primary {
            background: linear-gradient(135deg, #7C3AED, #8B5CF6); color: white;
            box-shadow: 0 4px 16px rgba(124,58,237,0.4);
        }
        .photo-btn-primary:hover { transform: translateY(-1px); }
        .photo-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .photo-btn-secondary {
            background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0;
            margin-top: 8px;
        }
        .photo-btn-secondary:hover { background: #E2E8F0; }
        .photo-saving {
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        @media (max-width: 768px) {
            .start-layout { grid-template-columns: 1fr; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            body { padding: 16px 12px 28px; }
            .card-top { padding: 20px 18px; }
            .card-bottom { padding: 16px; }
            .action-box { top: 0; padding: 12px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); gap: 6px; }
            .exam-brand { gap: 10px; }
            .exam-brand-badge,
            .exam-brand-main {
                width: min(136px, 40vw);
                height: 68px;
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body x-data="startGate()" x-init="init()">

    {{-- Photo capture overlay --}}
    @if($capturePhotoEnabled && !$student->hasProfilePhoto())
    <div class="photo-overlay" x-show="showPhotoCapture" x-cloak>
        <div class="photo-card">
            <div class="photo-card-top">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 8px;display:block">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                    <circle cx="12" cy="13" r="4"/>
                </svg>
                <h2 style="font-size:1.125rem;font-weight:800;margin:0">Foto de Identificação</h2>
                <p style="font-size:0.8rem;opacity:0.8;margin:6px 0 0">Tire uma foto sua para identificação no sistema</p>
            </div>
            <div class="photo-card-body">
                {{-- Webcam preview --}}
                <div class="webcam-container" x-show="!capturedPhotoUrl">
                    <video x-ref="photoVideo" autoplay playsinline muted style="transform:scaleX(-1)"></video>
                    <div class="webcam-guide">
                        <div class="webcam-guide-circle"></div>
                    </div>
                </div>

                {{-- Captured photo preview --}}
                <div class="webcam-container" x-show="capturedPhotoUrl" style="border:3px solid #10B981">
                    <img :src="capturedPhotoUrl" alt="Foto capturada" style="transform:scaleX(-1)">
                </div>

                <canvas x-ref="photoCanvas" width="320" height="240" style="display:none"></canvas>

                {{-- Capture button --}}
                <template x-if="!capturedPhotoUrl && !photoSaving">
                    <button type="button" class="photo-btn photo-btn-primary" @click="capturePhoto()" :disabled="!photoStreamReady">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="0" style="display:inline;vertical-align:-2px;margin-right:4px">
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                        Capturar Foto
                    </button>
                </template>

                {{-- Confirm / Retake buttons --}}
                <template x-if="capturedPhotoUrl && !photoSaving">
                    <div>
                        <button type="button" class="photo-btn photo-btn-primary" @click="confirmPhoto()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="display:inline;vertical-align:-2px;margin-right:4px">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                            Confirmar e Continuar
                        </button>
                        <button type="button" class="photo-btn photo-btn-secondary" @click="retakePhoto()">
                            Capturar Novamente
                        </button>
                    </div>
                </template>

                {{-- Saving state --}}
                <template x-if="photoSaving">
                    <button type="button" class="photo-btn photo-btn-primary" disabled>
                        <span class="photo-saving">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="animation:spin 0.8s linear infinite">
                                <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                            </svg>
                            Salvando foto...
                        </span>
                    </button>
                </template>

                {{-- Error --}}
                <p x-show="photoError" x-text="photoError" style="color:#DC2626;font-size:0.75rem;margin:8px 0 0;text-align:center"></p>
            </div>
        </div>
    </div>
    @endif

    <div class="start-shell">
    <div class="start-card">

        {{-- Topo com gradiente --}}
        <div class="card-top">
            <div class="exam-brand">
                <div class="exam-brand-badge">
                    <img src="{{ asset('imagem/logo-anasps.png') }}" alt="Logo Anasps">
                </div>
                <div class="exam-brand-main">
                    <img src="{{ asset('imagem/logo-deitada-transparente.png') }}" alt="Faculdade Anasps">
                </div>
            </div>
            <h1 style="font-size:1.375rem;font-weight:800;margin:0;letter-spacing:-0.02em">{{ $exam->title }}</h1>
            <p style="font-size:0.875rem;opacity:0.7;margin:6px 0 0">{{ $exam->clientSystem?->name ?? 'Faculdade Anasps' }}</p>
            @if($session->is_simulation)
            <div class="simulation-pill">Modo Simulado</div>
            @endif
        </div>

        {{-- Conteúdo --}}
        <div class="card-bottom">
        <div class="start-layout">

            <div class="start-main">
            {{-- Regras --}}
            @if(! $session->is_simulation)
            <div class="rules-box">
                <div class="rules-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    Leia antes de começar
                </div>
                <ul class="rules-list">
                    <li>A prova será monitorada em tempo real pelo sistema AvaliaFA</li>
                    <li x-show="!isMobileDevice && fullscreenRequired">Mantenha a janela em <strong>tela cheia</strong> o tempo todo</li>
                    <li x-show="!isMobileDevice && !fullscreenRequired">Modo <strong>tela cheia</strong> disponível (só se a pessoa quiser)</li>
                    <li>Não minimize, troque de aba ou alterne durante a prova</li>
                    @if($exam->webcam_enabled ?? false)
                    <li>Sua <strong>câmera</strong> será ativada para monitoramento fotográfico</li>
                    @endif
                    <li>O progresso é salvo automaticamente a cada 30 segundos</li>
                    <li>O sistema poderá encerrar automaticamente a prova em caso de reincidência de violações</li>
                </ul>
            </div>
            @endif

            {{-- Checklist de requisitos do sistema --}}
            <div class="checklist-box" style="background:#F0F9FF;border:1px solid #BAE6FD;border-radius:10px;padding:12px;margin-bottom:14px;">
                <div style="font-size:0.8125rem;font-weight:700;color:#0369A1;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0369A1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    {{ $session->is_simulation ? 'Orientações rápidas' : 'Verificação de segurança' }}
                </div>

                @if($session->is_simulation)
                <div class="check-row" style="display:flex;align-items:flex-start;gap:8px;padding:4px 0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-top:3px;flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size:0.78rem;color:#0F766E;line-height:1.45">Leia as questões com calma e revise antes de finalizar.</span>
                </div>
                <div class="check-row" style="display:flex;align-items:flex-start;gap:8px;padding:4px 0;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-top:3px;flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size:0.78rem;color:#0F766E;line-height:1.45">Ao clicar em iniciar, o cronômetro começa imediatamente.</span>
                </div>
                @else
                {{-- Tela cheia --}}
                <div class="check-row" x-show="!isMobileDevice" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #E0F2FE;">
                    <div class="check-icon" :class="fullscreenOk ? 'check-ok' : 'check-pending'">
                        <template x-if="fullscreenOk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </template>
                        <template x-if="!fullscreenOk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>
                        </template>
                    </div>
                    <span style="font-size:0.75rem;" :style="fullscreenOk ? 'color:#065F46;font-weight:600' : 'color:#64748B'">
                        Modo tela cheia disponível
                    </span>
                </div>

                {{-- Webcam --}}
                @if($exam->webcam_enabled ?? false)
                <div class="check-row" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #E0F2FE;">
                    <div class="check-icon" :class="webcamOk ? 'check-ok' : (webcamError ? 'check-fail' : 'check-pending')">
                        <template x-if="webcamOk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </template>
                        <template x-if="webcamError">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </template>
                        <template x-if="!webcamOk && !webcamError">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2.5" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                        </template>
                    </div>
                    <span style="font-size:0.75rem;" :style="webcamOk ? 'color:#065F46;font-weight:600' : (webcamError ? 'color:#991B1B;font-weight:600' : 'color:#64748B')">
                        <span x-show="!webcamOk && !webcamError">Verificando camera...</span>
                        <span x-show="webcamOk">Camera detectada</span>
                        <span x-show="webcamError" x-text="webcamErrorMessage"></span>
                    </span>
                </div>
                @endif

                {{-- Navegador compatível --}}
                <div class="check-row" style="display:flex;align-items:center;gap:8px;padding:6px 0;">
                    <div class="check-icon" :class="browserOk ? 'check-ok' : 'check-fail'">
                        <template x-if="browserOk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </template>
                        <template x-if="!browserOk">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </template>
                    </div>
                    <span style="font-size:0.75rem;" :style="browserOk ? 'color:#065F46;font-weight:600' : 'color:#991B1B;font-weight:600'">
                        <span x-show="browserOk">Navegador compatível</span>
                        <span x-show="!browserOk">Navegador não suporta modo seguro de prova</span>
                    </span>
                </div>
                @endif
            </div>

            {{-- Aceite das regras --}}
            <label style="display:flex;align-items:flex-start;gap:8px;margin-bottom:14px;cursor:pointer;padding:10px 12px;background:#F8FAFF;border:1px solid #E2E8F0;border-radius:10px;transition:border-color 0.15s;"
                   :style="accepted ? 'border-color:#2563EB;background:#EFF6FF' : ''">
                <input type="checkbox" x-model="accepted"
                       style="width:16px;height:16px;margin-top:1px;accent-color:#2563EB;flex-shrink:0;cursor:pointer;">
                <span style="font-size:0.75rem;color:#334155;line-height:1.4;">
                    @if($session->is_simulation)
                    Declaro estar de acordo com o uso do sistema de simulados e com os termos da plataforma.
                    @else
                    Li e compreendo as regras acima. Declaro estar ciente de que a prova será monitorada e que comportamentos suspeitos serão registrados.
                    @endif
                </span>
            </label>
            </div>

            <aside class="start-side">
            {{-- Identidade do aluno --}}
            <div class="student-bar">
                @if($student->hasProfilePhoto())
                <img src="{{ $student->profilePhotoUrl() }}" alt="{{ $student->name }}"
                     style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid #BFDBFE">
                @else
                <div class="student-avatar" x-bind:style="studentPhotoUrl ? 'padding:0;overflow:hidden' : ''">
                    <template x-if="studentPhotoUrl">
                        <img :src="studentPhotoUrl" alt="{{ $student->name }}" style="width:100%;height:100%;object-fit:cover">
                    </template>
                    <template x-if="!studentPhotoUrl">
                        <span>{{ mb_strtoupper(mb_substr($student->name, 0, 2)) }}</span>
                    </template>
                </div>
                @endif
                <div>
                    <div class="student-name">{{ $student->name }}</div>
                    @if($student->cpf && preg_match('/^\d{11}$/', $student->cpf))
                    <div class="student-cpf">
                        CPF: {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $student->cpf) }}
                    </div>
                    @elseif($student->moodle_user_id)
                    <div class="student-cpf">
                        ID Moodle: {{ $student->moodle_user_id }}
                    </div>
                    @endif
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;flex-shrink:0">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>

            {{-- Stats da prova --}}
            <div class="stat-grid {{ $session->is_simulation ? 'stat-grid-simulado' : '' }}">
                <div class="stat-item">
                    <div class="stat-label">Duração</div>
                    <div class="stat-value">
                        {{ $exam->duration_minutes }}<span class="stat-unit"> min</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Questões</div>
                    <div class="stat-value">{{ $questionsCount }}</div>
                </div>
                @if(! $session->is_simulation)
                <div class="stat-item">
                    <div class="stat-label">Tentativa</div>
                    <div class="stat-value">
                        {{ $session->attempt_number }}<span class="stat-unit">ª</span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Nota Mínima</div>
                    <div class="stat-value">
                        {{ number_format((float) $exam->passing_score, 2, ',', '.') }}
                    </div>
                </div>
                @endif
            </div>

            {{-- Botão iniciar --}}
            <div class="action-box">
                <p class="action-title">{{ $session->is_simulation ? 'Tudo pronto para o simulado' : 'Tudo pronto para iniciar' }}</p>
                <p class="action-copy" x-show="canStart">
                    O botão principal fica nesta caixa para continuar visível. <strong>Ao iniciar, o cronômetro começa imediatamente.</strong>
                </p>
                <p class="action-copy" x-show="!canStart">
                    Conclua os requisitos acima para liberar o início. Esta caixa mantém a ação principal destacada.
                </p>

                <form method="POST" action="{{ route('exam.confirm-start', $session->id) }}" @submit="loading = true">
                @csrf
                <button type="submit" class="btn-start" :disabled="!canStart || loading">
                    <span x-show="!loading">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="0" style="display:inline">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        {{ $session->is_simulation ? 'Iniciar Simulado Agora' : 'Iniciar Prova Agora' }}
                    </span>
                    <span x-show="loading" style="display:flex;align-items:center;gap:8px">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="animation:spin 0.8s linear infinite">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        Preparando ambiente seguro...
                    </span>
                </button>
                </form>

            @if($exam->webcam_enabled ?? false)
            <button type="button"
                    x-show="webcamError && !loading"
                    @click="runChecks()"
                    style="width:100%;margin-top:10px;padding:11px 14px;border:1px solid #BFDBFE;border-radius:10px;background:#EFF6FF;color:#1D4ED8;font-size:0.875rem;font-weight:700;cursor:pointer;">
                    Verificar camera novamente
                </button>
                @endif

                <p class="action-footer">
                    Ao iniciar, o cronômetro começará imediatamente
                </p>

                @if($session->is_simulation && auth()->check())
                <a href="{{ route('simulados.minha-area') }}"
                   style="display:block;margin-top:12px;text-align:center;padding:10px 14px;border:1px solid #CBD5E1;border-radius:10px;background:#F8FAFF;color:#475569;font-size:0.82rem;font-weight:600;text-decoration:none;">
                    ← Voltar ao meu painel
                </a>
                @endif
            </div>
            </aside>
        </div>
    </div>
    </div>

    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .check-ok     { color: #10B981; }
        .check-fail   { color: #EF4444; }
        .check-pending { color: #94A3B8; }
    </style>

    <script>
        function inspectWebcamFrame(video) {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d', { willReadFrequently: true });

            if (!context) {
                return { obstructed: false, averageBrightness: 0, dynamicRange: 0 };
            }

            canvas.width = 160;
            canvas.height = 120;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
            let totalBrightness = 0;
            let minBrightness = 255;
            let maxBrightness = 0;
            let samples = 0;

            for (let index = 0; index < pixels.length; index += 16) {
                const brightness = (pixels[index] + pixels[index + 1] + pixels[index + 2]) / 3;
                totalBrightness += brightness;
                minBrightness = Math.min(minBrightness, brightness);
                maxBrightness = Math.max(maxBrightness, brightness);
                samples++;
            }

            const averageBrightness = samples > 0 ? totalBrightness / samples : 0;
            const dynamicRange = maxBrightness - minBrightness;
            const obstructed = dynamicRange < 18 && (averageBrightness < 32 || averageBrightness > 223);

            return { obstructed, averageBrightness, dynamicRange };
        }

        async function validateWebcamVisibility(stream) {
            const video = document.createElement('video');
            video.muted = true;
            video.playsInline = true;
            video.srcObject = stream;

            const waitForFrame = async () => {
                await video.play();
                await new Promise((resolve) => window.setTimeout(resolve, 260));
                return inspectWebcamFrame(video);
            };

            try {
                if (video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
                    return await waitForFrame();
                }

                await new Promise((resolve, reject) => {
                    video.onloadedmetadata = resolve;
                    video.onerror = reject;
                });

                return await waitForFrame();
            } finally {
                video.pause();
                video.srcObject = null;
            }
        }

        function startGate() {
            const webcamRequired = {{ ($exam->webcam_enabled ?? false) ? 'true' : 'false' }};
            const fullscreenBaseRequired = {{ $fullscreenRequired ? 'true' : 'false' }};
            const capturePhotoRequired = {{ $capturePhotoEnabled ? 'true' : 'false' }};
            const hasProfilePhoto = {{ $student->hasProfilePhoto() ? 'true' : 'false' }};
            const needsPhoto = capturePhotoRequired && !hasProfilePhoto;
            const sessionId = {{ $session->id }};
            const csrfToken = '{{ csrf_token() }}';

            return {
                loading: false,
                accepted: false,
                isMobileDevice: false,
                fullscreenOk: false,
                webcamOk: false,
                webcamError: false,
                webcamErrorMessage: 'Camera nao disponivel - permita o acesso e clique em verificar camera novamente.',
                browserOk: false,

                // Photo capture state
                showPhotoCapture: needsPhoto,
                photoStream: null,
                photoStreamReady: false,
                capturedPhotoUrl: null,
                capturedBlob: null,
                photoSaving: false,
                photoError: '',
                studentPhotoUrl: null,

                get canStart() {
                    if (!this.accepted) return false;
                    if (this.effectiveFullscreenRequired && !this.fullscreenOk) return false;
                    if (!this.browserOk) return false;
                    if (webcamRequired && !this.webcamOk) return false;
                    return true;
                },

                get effectiveFullscreenRequired() {
                    return fullscreenBaseRequired && !this.isMobileDevice;
                },

                async init() {
                    this.isMobileDevice = this.detectMobileDevice();
                    if (needsPhoto) {
                        await this.startPhotoStream();
                    }
                    this.runChecks();
                },

                detectMobileDevice() {
                    const ua = navigator.userAgent || '';
                    const mobileUa = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);
                    const narrowViewport = window.matchMedia('(max-width: 768px)').matches;

                    return mobileUa || narrowViewport;
                },

                async startPhotoStream() {
                    try {
                        this.photoStream = await navigator.mediaDevices.getUserMedia({
                            video: { width: { ideal: 320 }, height: { ideal: 240 }, facingMode: 'user' },
                            audio: false
                        });
                        this.$nextTick(() => {
                            const video = this.$refs.photoVideo;
                            if (video) {
                                video.srcObject = this.photoStream;
                                video.onloadedmetadata = () => { this.photoStreamReady = true; };
                            }
                        });
                    } catch (e) {
                        this.photoError = 'Nao foi possivel acessar a camera. Permita o acesso e recarregue a pagina.';
                    }
                },

                capturePhoto() {
                    const video = this.$refs.photoVideo;
                    const canvas = this.$refs.photoCanvas;
                    if (!video || !canvas) return;

                    const ctx = canvas.getContext('2d');
                    ctx.save();
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    ctx.restore();

                    canvas.toBlob((blob) => {
                        this.capturedBlob = blob;
                        this.capturedPhotoUrl = URL.createObjectURL(blob);
                    }, 'image/jpeg', 0.8);
                },

                retakePhoto() {
                    if (this.capturedPhotoUrl) {
                        URL.revokeObjectURL(this.capturedPhotoUrl);
                    }
                    this.capturedPhotoUrl = null;
                    this.capturedBlob = null;
                    this.photoError = '';
                },

                async confirmPhoto() {
                    if (!this.capturedBlob) return;
                    this.photoSaving = true;
                    this.photoError = '';

                    try {
                        const formData = new FormData();
                        formData.append('photo', this.capturedBlob, 'photo.jpg');

                        const response = await fetch(`/exam/sessions/${sessionId}/profile-photo`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken },
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.studentPhotoUrl = data.photo_url;
                            this.stopPhotoStream();
                            this.showPhotoCapture = false;
                        } else {
                            this.photoError = data.message || 'Erro ao salvar a foto. Tente novamente.';
                        }
                    } catch (e) {
                        this.photoError = 'Erro de conexao. Tente novamente.';
                    } finally {
                        this.photoSaving = false;
                    }
                },

                stopPhotoStream() {
                    if (this.photoStream) {
                        this.photoStream.getTracks().forEach(t => t.stop());
                        this.photoStream = null;
                    }
                },

                async runChecks() {
                    this.webcamError = false;
                    this.webcamOk = false;
                    this.webcamErrorMessage = 'Camera nao disponivel - permita o acesso e clique em verificar camera novamente.';

                    const fullscreenSupported = !!(
                        document.documentElement.requestFullscreen ||
                        document.documentElement.webkitRequestFullscreen ||
                        document.documentElement.mozRequestFullScreen
                    );

                    this.fullscreenOk = this.effectiveFullscreenRequired ? fullscreenSupported : true;
                    this.browserOk = ('hidden' in document) && (!this.effectiveFullscreenRequired || fullscreenSupported);

                    if (webcamRequired) {
                        let stream = null;

                        try {
                            stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                            const inspection = await validateWebcamVisibility(stream);

                            if (inspection.obstructed) {
                                this.webcamError = true;
                                this.webcamErrorMessage = 'Camera obstruida - remova a tampa ou melhore a iluminacao e verifique camera novamente.';
                                return;
                            }

                            this.webcamOk = true;
                        } catch (error) {
                            this.webcamError = true;
                            this.webcamErrorMessage = 'Camera nao disponivel - permita o acesso e clique em verificar camera novamente.';
                        } finally {
                            stream?.getTracks().forEach((track) => track.stop());
                        }
                    } else {
                        this.webcamOk = true;
                    }
                }
            };
        }
    </script>
</body>
</html>
