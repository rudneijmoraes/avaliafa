<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SecureExamEngine — configuração via meta tag --}}
    @isset($session)
    @php
        $snapshotIntervalSeconds = (int) data_get($session->exam->settings ?? [], 'snapshot_interval_seconds', 60);
        $snapshotIntervalMs = max(60, $snapshotIntervalSeconds) * 1000;
        $isSimulation = (bool) ($session->is_simulation ?? false);
        $simuladoSettings = (array) data_get($session->exam->settings ?? [], 'simulado', []);
        $fullscreenRequired = $isSimulation ? (bool) data_get($simuladoSettings, 'fullscreen_enabled', true) : true;
        $faceEnabled = $session->requiresFaceVerification();
        $faceReferenceUrl = $faceEnabled && $session->student->face_reference_photo
            ? \Illuminate\Support\Facades\Storage::disk('private')->temporaryUrl(
                $session->student->face_reference_photo,
                now()->addHours(3)
              )
            : '';
    @endphp
    <meta name="avalia-fa-exam"
          data-session-id="{{ $session->id }}"
          data-max-violations="{{ $session->exam->max_violations ?? 3 }}"
          data-snapshot-interval="{{ $snapshotIntervalMs }}"
          data-webcam-enabled="{{ $session->exam->requiresWebcam() ? 'true' : 'false' }}"
          data-fullscreen-required="{{ $fullscreenRequired ? 'true' : 'false' }}"
          data-simulation-mode="{{ $isSimulation ? 'true' : 'false' }}"
          data-inactivity-warning="120"
          data-inactivity-timeout="180"
          data-api-base="{{ url('/exam') }}"
          data-face-recognition-enabled="{{ $faceEnabled ? 'true' : 'false' }}"
          data-face-reference-url="{{ $faceReferenceUrl }}"
          data-face-check-interval-ms="120000">
    @endisset

    <title>@yield('exam-title', 'Prova') — AvaliaFA</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --color-primary-700: #1D4ED8;
            --color-primary-600: #2563EB;
            --color-primary-500: #3B82F6;
            --color-primary-100: #DBEAFE;
            --color-primary-50:  #EFF6FF;
            --color-success:     #10B981;
            --color-success-bg:  #D1FAE5;
            --color-warning:     #F59E0B;
            --color-warning-bg:  #FEF3C7;
            --color-danger:      #EF4444;
            --color-danger-bg:   #FEE2E2;
            /* Cores específicas da tela de prova — reduz fadiga visual */
            --exam-bg:           #F8FAFF;
            --exam-topbar:       #FFFFFF;
            --exam-sidebar:      #FFFFFF;
            --exam-question-bg:  #FFFFFF;
            --exam-border:       #E2E8F0;
            --text-primary:      #0F172A;
            --text-secondary:    #64748B;
            --text-muted:        #94A3B8;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--exam-bg);
            color: var(--text-primary);
            /* Previnir seleção durante prova */
            user-select: none;
            -webkit-user-select: none;
        }

        /* Topbar da prova */
        .exam-topbar {
            position: fixed; top: 0; left: 0; right: 0;
            height: 56px;
            background: var(--exam-topbar);
            border-bottom: 1px solid var(--exam-border);
            display: flex; align-items: center;
            padding: 0 20px; gap: 16px; z-index: 100;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .exam-logo {
            display: flex; align-items: center; gap: 8px;
            font-weight: 800; font-size: 1rem; color: #1D4ED8;
            text-decoration: none; flex-shrink: 0;
        }
        .exam-logo-icon {
            width: 28px; height: 28px;
            background: linear-gradient(135deg, #1D4ED8, #3B82F6);
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 13px;
        }
        .exam-title-bar {
            flex: 1; overflow: hidden;
        }
        .exam-title-bar-name {
            font-size: 0.875rem; font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .exam-title-bar-sub {
            font-size: 0.7rem; color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .exam-topbar-right {
            display: flex; align-items: center; gap: 12px; flex-shrink: 0;
        }

        /* Timer */
        .exam-timer {
            display: flex; align-items: center; gap: 6px;
            background: var(--exam-bg);
            border: 1.5px solid var(--exam-border);
            border-radius: 8px; padding: 5px 12px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.125rem; font-weight: 600;
            color: var(--text-primary);
            min-width: 96px; justify-content: center;
        }
        .exam-timer.warning { border-color: var(--color-warning); color: var(--color-warning); background: var(--color-warning-bg); }
        .exam-timer.urgent  { border-color: var(--color-danger);  color: var(--color-danger);  background: var(--color-danger-bg); animation: pulse-timer 1s ease-in-out infinite; }
        @keyframes pulse-timer { 0%,100% { opacity: 1; } 50% { opacity: 0.6; } }

        /* Progresso no topbar */
        .exam-progress-bar {
            position: fixed; top: 56px; left: 0; right: 0;
            height: 3px; background: var(--exam-border); z-index: 99;
        }
        .exam-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1D4ED8, #3B82F6, #14B8A6);
            transition: width 0.5s ease;
        }

        /* Layout principal */
        .exam-layout {
            display: flex;
            padding-top: 59px; /* topbar + progress bar */
            min-height: 100vh;
        }

        /* Sidebar de navegação */
        .exam-sidebar {
            width: 260px; flex-shrink: 0;
            background: var(--exam-sidebar);
            border-right: 1px solid var(--exam-border);
            padding: 16px;
            position: sticky; top: 59px;
            height: calc(100vh - 59px);
            overflow-y: auto; overflow-x: hidden;
        }
        .exam-sidebar-label {
            font-size: 0.65rem; font-weight: 700;
            color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em;
            margin-bottom: 10px; padding: 0 2px;
        }
        .exam-nav-grid {
            display: grid; grid-template-columns: repeat(5, 1fr);
            gap: 6px; margin-bottom: 16px;
        }
        .exam-nav-btn {
            aspect-ratio: 1; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 600;
            cursor: pointer; border: 1.5px solid var(--exam-border);
            background: var(--exam-bg); color: var(--text-secondary);
            transition: all 0.15s;
        }
        .exam-nav-btn:hover { border-color: var(--color-primary-500); color: var(--color-primary-600); background: var(--color-primary-50); }
        .exam-nav-btn.current { border-color: var(--color-primary-500); background: var(--color-primary-600); color: white; }
        .exam-nav-btn.answered { border-color: var(--color-success); background: var(--color-success-bg); color: var(--color-success); }
        .exam-nav-btn.unanswered { border-color: var(--exam-border); background: var(--exam-bg); }

        /* Status badges */
        .exam-status-row {
            display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; padding-bottom: 16px;
            border-bottom: 1px solid var(--exam-border);
        }
        .exam-status-item { display: flex; align-items: center; gap: 5px; font-size: 0.72rem; color: var(--text-secondary); }
        .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* Botão entregar */
        .btn-submit-exam {
            width: 100%; padding: 11px;
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: white; border: none; border-radius: 10px;
            font-size: 0.875rem; font-weight: 700;
            cursor: pointer; font-family: 'Inter', Arial, sans-serif;
            box-shadow: 0 3px 10px rgba(29,78,216,0.35);
            transition: all 0.2s;
        }
        .btn-submit-exam:hover { transform: translateY(-1px); box-shadow: 0 5px 16px rgba(29,78,216,0.45); }

        /* Área principal de questão */
        .exam-main {
            flex: 1; padding: 24px;
            max-width: 800px; margin: 0 auto;
        }
        .question-card {
            background: var(--exam-question-bg);
            border-radius: 14px;
            border: 1px solid var(--exam-border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }
        .question-header {
            padding: 16px 24px;
            background: linear-gradient(135deg, rgba(29,78,216,0.04) 0%, transparent 100%);
            border-bottom: 1px solid var(--exam-border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .question-number {
            font-size: 0.8rem; font-weight: 700;
            color: var(--color-primary-600);
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .question-body { padding: 24px; }
        .question-text {
            font-size: 1rem; line-height: 1.75;
            color: var(--text-primary); margin-bottom: 24px;
        }
        .question-text img { max-width: 100%; border-radius: 8px; margin: 12px 0; }

        /* Alternativas */
        .choice-list { display: flex; flex-direction: column; gap: 10px; }
        .choice-item {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 16px; border-radius: 10px;
            border: 1.5px solid var(--exam-border);
            cursor: pointer; transition: all 0.15s;
            background: var(--exam-bg);
        }
        .choice-item:hover {
            border-color: var(--color-primary-400);
            background: var(--color-primary-50);
        }
        .choice-item.selected {
            border-color: var(--color-primary-500);
            background: var(--color-primary-50);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .choice-radio {
            width: 18px; height: 18px; border-radius: 50%;
            border: 2px solid var(--exam-border);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 2px;
            transition: border-color 0.15s;
        }
        .choice-item:hover .choice-radio { border-color: var(--color-primary-400); }
        .choice-item.selected .choice-radio {
            border-color: var(--color-primary-600);
            background: var(--color-primary-600);
        }
        .choice-item.selected .choice-radio::after {
            content: '';
            width: 6px; height: 6px;
            border-radius: 50%; background: white;
        }
        .choice-letter {
            min-width: 24px; height: 24px;
            border-radius: 6px;
            background: var(--exam-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700;
            color: var(--text-secondary); flex-shrink: 0;
            transition: all 0.15s;
        }
        .choice-item.selected .choice-letter {
            background: var(--color-primary-600); color: white;
        }
        .choice-text {
            font-size: 0.9375rem; line-height: 1.6;
            color: var(--text-primary); flex: 1;
        }

        /* Navegação inferior */
        .question-nav {
            padding: 16px 24px;
            border-top: 1px solid var(--exam-border);
            display: flex; justify-content: space-between; align-items: center;
            gap: 12px;
        }
        .btn-nav {
            display: flex; align-items: center; gap: 6px;
            padding: 9px 18px; border-radius: 8px;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; border: 1.5px solid var(--exam-border);
            background: var(--exam-bg); color: var(--text-primary);
            font-family: 'Inter', Arial, sans-serif;
            transition: all 0.15s;
        }
        .btn-nav:hover { border-color: var(--color-primary-400); background: var(--color-primary-50); color: var(--color-primary-700); }
        .btn-nav:disabled { opacity: 0.4; cursor: not-allowed; }

        /* Auto-save indicator */
        .autosave-indicator {
            display: flex; align-items: center; gap: 5px;
            font-size: 0.72rem; color: var(--text-muted);
        }

        /* Modal de confirmação */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            z-index: 200; display: flex; align-items: center; justify-content: center;
            padding: 16px;
        }
        .modal-box {
            background: var(--exam-question-bg);
            border-radius: 16px; padding: 32px;
            max-width: 480px; width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .modal-box.modal-box-wide {
            max-width: 760px;
            padding: 28px;
        }
        .submit-modal-layout {
            display:grid;
            grid-template-columns:minmax(0,1.15fr) minmax(240px,0.85fr);
            gap:20px;
            align-items:start;
        }
        .submit-modal-copy {
            min-width: 0;
        }
        .submit-modal-aside {
            background: linear-gradient(180deg, #F8FBFF 0%, #EEF4FF 100%);
            border: 1px solid #DBEAFE;
            border-radius: 14px;
            padding: 18px;
        }
        .submit-modal-actions {
            display:flex;
            gap:10px;
            margin-top:20px;
        }
        .exam-modal-btn {
            flex: 1;
            min-height: 48px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            border: 1.5px solid transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.18s ease;
        }
        .exam-modal-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .exam-modal-btn-secondary {
            background: #FFFFFF;
            color: #64748B;
            border-color: #E2E8F0;
        }
        .exam-modal-btn-secondary:hover:not(:disabled) {
            border-color: #CBD5E1;
            background: #F8FAFC;
        }
        .exam-modal-btn-primary {
            background: linear-gradient(135deg, #1D4ED8, #2563EB);
            color: #FFFFFF;
            box-shadow: 0 3px 10px rgba(29,78,216,0.35);
        }
        .exam-modal-btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(29,78,216,0.28);
        }
        .exam-modal-btn-danger {
            background: #FFFFFF;
            color: #EF4444;
            border-color: #EF4444;
        }
        .exam-modal-btn-danger:hover:not(:disabled) {
            background: #EF4444;
            color: #FFFFFF;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
        }

        /* Warning toast */
        .exam-warning-toast {
            position: fixed; top: 72px; left: 50%; transform: translateX(-50%);
            background: var(--color-warning-bg);
            border: 2px solid var(--color-warning);
            border-radius: 12px; padding: 14px 20px;
            z-index: 150; max-width: 480px; width: calc(100% - 32px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            animation: toastIn 0.3s ease;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-12px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .exam-sidebar { display: none; }
            .exam-main { padding: 16px; }
            .exam-topbar {
                padding: 0 10px;
                gap: 8px;
            }
            .exam-logo span {
                display: none;
            }
            .exam-title-bar-name {
                font-size: 0.8rem;
            }
            .exam-title-bar-sub {
                display: none;
            }
            .exam-topbar-right {
                gap: 8px;
            }
            .exam-progress-meta {
                display: none;
            }
            .exam-timer {
                min-width: 86px;
                padding: 4px 10px;
                font-size: 1rem;
            }
            .autosave-indicator span {
                display: none;
            }
            .modal-box.modal-box-wide {
                max-width: 520px;
                padding: 22px;
            }
            .submit-modal-layout {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .submit-modal-actions {
                flex-direction: column;
            }
        }

        /* Scrollbar fina */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--exam-border); border-radius: 9999px; }
        .global-platform-footer {
            border-top: 1px solid var(--exam-border);
            background: var(--exam-topbar);
            color: var(--text-secondary);
            text-align: center;
            font-size: 0.76rem;
            font-weight: 600;
            padding: 10px 14px;
        }
    </style>

    @stack('head')
</head>
<body>

{{-- Warning toast de violação --}}
<div id="exam-warning-modal" class="exam-warning-toast" style="display:none">
    <div style="display:flex;align-items:flex-start;gap:12px">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <div>
            <p style="font-weight:700;color:#92400E;margin:0;font-size:0.875rem" data-title></p>
            <p style="font-size:0.8rem;color:#B45309;margin:5px 0 0" data-message></p>
        </div>
    </div>
</div>

@yield('body')
<footer class="global-platform-footer">@include('partials.system-footer-text')</footer>

{{-- face-api.js — carregado apenas quando reconhecimento facial está ativo --}}
@isset($session)
@if($session->requiresFaceVerification())
<script src="{{ asset('vendor/face-api/face-api.min.js') }}"></script>
@endif
@endisset

{{-- SecureExamEngine v2 --}}
<script src="{{ route('assets.secure-exam-engine') }}"></script>
@stack('scripts')
</body>
</html>
