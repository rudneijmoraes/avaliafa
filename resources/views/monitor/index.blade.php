<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitor — {{ $exam->title }} — AvaliaFA</title>

    <script>
        (function () {
            const t = localStorage.getItem('avalia-theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: ['class', '[data-theme="dark"]'] }</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --color-primary-800: #1E3A5F;
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
            --color-info:        #F97316;
            --color-info-bg:     #FFEDD5;
            --surface-bg:        #F1F5F9;
            --surface-card:      #FFFFFF;
            --surface-border:    #E2E8F0;
            --text-primary:      #0F172A;
            --text-secondary:    #64748B;
            --text-muted:        #94A3B8;
        }
        [data-theme="dark"] {
            --color-primary-50:  #172554;
            --color-primary-100: #1E3A5F;
            --color-primary-500: #2563EB;
            --color-primary-600: #3B82F6;
            --color-primary-700: #60A5FA;
            --color-success:     #34D399;
            --color-success-bg:  #064E3B;
            --color-danger:      #F87171;
            --color-danger-bg:   #7F1D1D;
            --color-info:        #FB923C;
            --color-info-bg:     #7C2D12;
            --surface-bg:        #0F172A;
            --surface-card:      #1E293B;
            --surface-border:    #334155;
            --text-primary:      #F1F5F9;
            --text-secondary:    #94A3B8;
            --text-muted:        #64748B;
        }
        *, *::before, *::after {
            box-sizing: border-box;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.15s ease;
        }
        body {
            margin: 0; font-family: 'Inter', Arial, sans-serif;
            background: var(--surface-bg); color: var(--text-primary); min-height: 100vh;
        }

        /* Topbar */
        .monitor-topbar {
            background: linear-gradient(135deg, #0F2044 0%, #1D4ED8 100%);
            padding: 0 24px; height: 64px;
            display: flex; align-items: center; gap: 20px;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 2px 12px rgba(0,0,0,0.2);
        }
        .monitor-logo {
            display: flex; align-items: center; gap: 10px;
            flex-shrink: 0;
        }
        .monitor-logo-icon {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,0.15); backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 15px;
        }
        .monitor-title { color: white; }
        .monitor-exam-name { font-size: 1rem; font-weight: 700; line-height: 1.2; }
        .monitor-exam-sub  { font-size: 0.72rem; opacity: 0.65; }
        .monitor-live-badge {
            display: flex; align-items: center; gap: 6px;
            background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4);
            border-radius: 9999px; padding: 4px 12px;
            font-size: 0.75rem; font-weight: 700; color: #34D399;
            flex-shrink: 0;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.5); }
        }
        .live-dot { width: 7px; height: 7px; border-radius: 50%; background: #34D399; animation: pulse-dot 2s ease-in-out infinite; }
        .monitor-stats {
            display: flex; align-items: center; gap: 24px;
            margin-left: auto; flex-shrink: 0;
        }
        .monitor-stat { text-align: center; }
        .monitor-stat-value { font-size: 1.5rem; font-weight: 800; color: white; line-height: 1; }
        .monitor-stat-label { font-size: 0.65rem; opacity: 0.6; color: white; text-transform: uppercase; letter-spacing: 0.06em; }

        /* KPI bar */
        .kpi-bar {
            background: var(--surface-card);
            border-bottom: 1px solid var(--surface-border);
            display: flex; overflow-x: auto;
        }
        .kpi-bar-item {
            flex: 1; min-width: 120px; padding: 14px 20px;
            border-right: 1px solid var(--surface-border);
            text-align: center;
        }
        .kpi-bar-item:last-child { border-right: none; }
        .kpi-bar-value { font-size: 1.5rem; font-weight: 800; color: var(--text-primary); }
        .kpi-bar-label { font-size: 0.7rem; color: var(--text-muted); font-weight: 500; margin-top: 2px; text-transform: uppercase; letter-spacing: 0.06em; }

        /* Conteúdo */
        .monitor-content { padding: 20px; max-width: 1600px; margin: 0 auto; }

        /* Filtros */
        .filter-bar {
            display: flex; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;
        }
        .filter-btn {
            padding: 6px 14px; border-radius: 9999px;
            font-size: 0.8rem; font-weight: 600; cursor: pointer;
            border: 1.5px solid var(--surface-border);
            background: var(--surface-card); color: var(--text-secondary);
            transition: all 0.15s; font-family: inherit;
        }
        .filter-btn.active { border-color: var(--color-primary-500); background: var(--color-primary-50); color: var(--color-primary-700); }
        .filter-btn.active-green  { background: var(--color-success-bg); color: var(--color-success); border-color: var(--color-success); }
        .filter-btn.active-orange { background: var(--color-info-bg); color: var(--color-info); border-color: var(--color-info); }
        .filter-btn.active-red    { background: var(--color-danger-bg); color: var(--color-danger); border-color: var(--color-danger); }

        /* Cards de alunos */
        .student-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 12px;
        }
        .student-card {
            background: var(--surface-card);
            border-radius: 12px;
            border: 2px solid var(--surface-border);
            padding: 16px; cursor: default;
            transition: all 0.2s ease;
        }
        .student-card.risk-low    { border-color: var(--surface-border); }
        .student-card.risk-medium { border-color: #FDE68A; box-shadow: 0 2px 8px rgba(245,158,11,0.1); }
        .student-card.risk-high   { border-color: #FDBA74; box-shadow: 0 2px 12px rgba(249,115,22,0.15); }
        .student-card.risk-critical { border-color: var(--color-danger); box-shadow: 0 2px 16px rgba(239,68,68,0.2); animation: critical-pulse 2s ease-in-out infinite; }
        @keyframes critical-pulse { 0%,100% { box-shadow: 0 2px 16px rgba(239,68,68,0.2); } 50% { box-shadow: 0 4px 24px rgba(239,68,68,0.4); } }
        .student-card.submitted { opacity: 0.75; }

        .student-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; gap: 8px; }
        .student-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #1D4ED8, #8B5CF6);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
        }
        .student-name { font-size: 0.875rem; font-weight: 700; color: var(--text-primary); }
        .student-email { font-size: 0.7rem; color: var(--text-muted); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 160px; }

        .risk-badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 8px; border-radius: 9999px;
            font-size: 0.68rem; font-weight: 700; white-space: nowrap; flex-shrink: 0;
        }
        .risk-low      { background: var(--color-success-bg); color: var(--color-success); }
        .risk-medium   { background: var(--color-warning-bg); color: var(--color-warning); }
        .risk-high     { background: var(--color-info-bg); color: var(--color-info); }
        .risk-critical { background: var(--color-danger-bg); color: var(--color-danger); }

        .status-row { display: flex; align-items: center; gap: 6px; margin-bottom: 12px; }
        .status-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .status-dot.active { background: var(--color-success); animation: pulse-dot 2s infinite; }
        .status-dot.idle   { background: var(--text-muted); }
        .status-text { font-size: 0.8rem; color: var(--text-secondary); }

        .metric-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 6px; margin-bottom: 10px; }
        .metric-item {
            background: var(--surface-bg); border-radius: 8px;
            padding: 8px; text-align: center;
        }
        .metric-label { font-size: 0.6rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
        .metric-value { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-top: 2px; font-family: 'JetBrains Mono', monospace; }

        /* Barra de risco */
        .risk-bar-wrap { margin-top: 8px; }
        .risk-bar-bg { height: 4px; background: var(--surface-border); border-radius: 9999px; overflow: hidden; }
        .risk-bar-fill { height: 100%; border-radius: 9999px; transition: width 0.5s ease; }

        /* Último evento */
        .last-event {
            font-size: 0.72rem; color: var(--text-muted);
            padding-top: 8px; border-top: 1px solid var(--surface-border);
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        /* Tabela detalhada */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .data-table th { padding: 10px 14px; text-align: left; font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.07em; border-bottom: 2px solid var(--surface-border); }
        .data-table td { padding: 11px 14px; color: var(--text-primary); border-bottom: 1px solid var(--surface-border); }
        .data-table tr:hover td { background: var(--color-primary-50); }
        [data-theme="dark"] .data-table tr:hover td { background: rgba(51,65,85,0.4); }

        /* Toggle view */
        .view-toggle {
            display: flex; background: var(--surface-bg); border: 1px solid var(--surface-border); border-radius: 8px; overflow: hidden; margin-left: auto;
        }
        .view-btn { padding: 6px 12px; border: none; background: none; cursor: pointer; color: var(--text-secondary); transition: all 0.15s; }
        .view-btn.active { background: var(--color-primary-600); color: white; }
        .monitor-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(340px, 0.95fr);
            gap: 18px;
            align-items: start;
        }
        .audit-panel {
            position: sticky;
            top: 84px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .audit-panel-header {
            padding: 18px 18px 14px;
            border-bottom: 1px solid var(--surface-border);
            background: linear-gradient(135deg, var(--color-primary-50), transparent);
        }
        .audit-panel-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: var(--text-primary);
        }
        .audit-panel-subtitle {
            font-size: 0.78rem;
            color: var(--text-secondary);
            margin-top: 4px;
            line-height: 1.5;
        }
        .audit-panel-body {
            padding: 16px 18px 18px;
            display: grid;
            gap: 16px;
        }
        .audit-session-card {
            padding: 14px;
            border: 1px solid var(--surface-border);
            border-radius: 14px;
            background: var(--surface-bg);
        }
        .audit-section-title {
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
        .audit-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .audit-meta-item {
            padding: 10px;
            border-radius: 12px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
        }
        .audit-meta-label {
            font-size: 0.66rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .audit-meta-value {
            margin-top: 4px;
            font-size: 0.84rem;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'JetBrains Mono', monospace;
        }
        .snapshot-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .snapshot-card {
            border: 1px solid var(--surface-border);
            border-radius: 14px;
            overflow: hidden;
            background: var(--surface-card);
        }
        .snapshot-image {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            background: #0F172A;
        }
        .snapshot-caption {
            padding: 10px 10px 12px;
            display: grid;
            gap: 4px;
            font-size: 0.7rem;
            color: var(--text-secondary);
        }
        .snapshot-trigger {
            display: inline-flex;
            width: fit-content;
            padding: 3px 8px;
            border-radius: 9999px;
            background: var(--color-primary-50);
            color: var(--color-primary-700);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .timeline-list {
            display: grid;
            gap: 10px;
        }
        .timeline-item {
            border-left: 3px solid var(--surface-border);
            padding: 0 0 0 12px;
        }
        .timeline-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .timeline-meta {
            font-size: 0.72rem;
            color: var(--text-secondary);
            margin-top: 3px;
            line-height: 1.5;
        }
        .select-audit-btn {
            margin-top: 12px;
            width: 100%;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1px solid var(--surface-border);
            background: var(--surface-bg);
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
        }
        .select-audit-btn.active {
            border-color: var(--color-primary-500);
            background: var(--color-primary-50);
            color: var(--color-primary-700);
        }
        .audit-empty {
            padding: 18px;
            border-radius: 14px;
            border: 1px dashed var(--surface-border);
            color: var(--text-secondary);
            background: var(--surface-bg);
            font-size: 0.8rem;
            line-height: 1.6;
        }
        @media (max-width: 1180px) {
            .monitor-layout {
                grid-template-columns: 1fr;
            }
            .audit-panel {
                position: static;
            }
        }
        @media (max-width: 720px) {
            .snapshot-grid,
            .audit-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body x-data="monitorApp()" x-init="init()">

    {{-- Topbar --}}
    <header class="monitor-topbar">
        <a href="{{ route('monitor.index') }}" style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;flex-shrink:0;text-decoration:none;transition:background 0.15s" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'" title="Voltar ao monitoramento">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        </a>

        <div class="monitor-logo">
            <div class="monitor-logo-icon">A</div>
            <div class="monitor-title">
                <div class="monitor-exam-name">{{ Str::limit($exam->title, 40) }}</div>
                <div class="monitor-exam-sub">Painel de Monitoramento</div>
            </div>
        </div>

        <div class="monitor-live-badge">
            <span class="live-dot"></span>
            AO VIVO &mdash; <span x-text="stats.active"></span> em prova
        </div>

        <div class="monitor-stats">
            <div class="monitor-stat">
                <div class="monitor-stat-value" x-text="stats.active"></div>
                <div class="monitor-stat-label">Em Prova</div>
            </div>
            <div class="monitor-stat">
                <div class="monitor-stat-value" x-text="stats.submitted"></div>
                <div class="monitor-stat-label">Entregues</div>
            </div>
            <div class="monitor-stat">
                <div class="monitor-stat-value" style="color:#FB923C" x-text="stats.totalViolations"></div>
                <div class="monitor-stat-label">Violações</div>
            </div>
            <div class="monitor-stat">
                <div class="monitor-stat-value" style="color:#EF4444" x-text="stats.critical"></div>
                <div class="monitor-stat-label">Críticos</div>
            </div>
        </div>

        {{-- Dark mode --}}
        <button
            x-data="{ dark: document.documentElement.getAttribute('data-theme') === 'dark' }"
            @click="dark=!dark; localStorage.setItem('avalia-theme',dark?'dark':'light'); document.documentElement.setAttribute('data-theme',dark?'dark':'light')"
            style="width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;cursor:pointer;color:white;flex-shrink:0"
        >
            <svg x-show="!dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <svg x-show="dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
            </svg>
        </button>
    </header>

    {{-- Conteúdo --}}
    <div class="monitor-content">

        {{-- Filtros e toggle de view --}}
        <div class="filter-bar">
            <button class="filter-btn" :class="filter === 'all' ? 'active' : ''" @click="filter = 'all'">
                Todos (<span x-text="sessions.length"></span>)
            </button>
            <button class="filter-btn" :class="filter === 'in_progress' ? 'active-green' : ''" @click="filter = 'in_progress'">
                Em Prova (<span x-text="stats.active"></span>)
            </button>
            <button class="filter-btn" :class="filter === 'violations' ? 'active-orange' : ''" @click="filter = 'violations'">
                Com Violações
            </button>
            <button class="filter-btn" :class="filter === 'critical' ? 'active-red' : ''" @click="filter = 'critical'">
                Risco Crítico (<span x-text="stats.critical"></span>)
            </button>
            <button class="filter-btn" :class="filter === 'submitted' ? 'active' : ''" @click="filter = 'submitted'">
                Entregues (<span x-text="stats.submitted"></span>)
            </button>

            <div class="view-toggle" style="margin-left:auto">
                <button class="view-btn" :class="viewMode === 'cards' ? 'active' : ''" @click="viewMode = 'cards'" title="Cards">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                </button>
                <button class="view-btn" :class="viewMode === 'table' ? 'active' : ''" @click="viewMode = 'table'" title="Tabela">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- View: Cards --}}
        <div x-show="viewMode === 'cards'">
            <div class="student-grid">
                <template x-for="s in filteredSessions" :key="s.id">
                    <div class="student-card"
                         :class="{
                             'risk-critical': s.risk_score >= 60,
                             'risk-high':     s.risk_score >= 40 && s.risk_score < 60,
                             'risk-medium':   s.risk_score >= 20 && s.risk_score < 40,
                             'risk-low':      s.risk_score < 20,
                             'submitted':     s.status === 'graded' || s.status === 'submitted',
                         }">

                        <div class="student-header">
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;flex:1">
                                <template x-if="s.student_photo">
                                    <img :src="s.student_photo" :alt="s.student_name" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
                                </template>
                                <template x-if="!s.student_photo">
                                    <div class="student-avatar" x-text="initials(s.student_name)"></div>
                                </template>
                                <div style="min-width:0">
                                    <div class="student-name" x-text="s.student_name || '—'"></div>
                                    <div class="student-email" x-text="s.student_email || ''"></div>
                                </div>
                            </div>
                            <span class="risk-badge"
                                  :class="{
                                      'risk-critical': s.risk_score >= 60,
                                      'risk-high':     s.risk_score >= 40 && s.risk_score < 60,
                                      'risk-medium':   s.risk_score >= 20 && s.risk_score < 40,
                                      'risk-low':      s.risk_score < 20,
                                  }"
                                  x-text="riskLabel(s.risk_score)">
                            </span>
                        </div>

                        <div class="status-row">
                            <span class="status-dot" :class="s.status === 'in_progress' ? 'active' : 'idle'"></span>
                            <span class="status-text" x-text="statusLabel(s.status)"></span>
                        </div>

                        <div class="metric-grid">
                            <div class="metric-item">
                                <div class="metric-label">Risco</div>
                                <div class="metric-value" :style="riskColor(s.risk_score)" x-text="s.risk_score + '%'"></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Violações</div>
                                <div class="metric-value" :style="s.violation_count > 0 ? 'color:var(--color-info)' : ''" x-text="s.violation_count"></div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-label">Nota</div>
                                <div class="metric-value" x-text="s.final_score !== null ? Number(s.final_score).toFixed(1) : '—'"></div>
                            </div>
                        </div>

                        {{-- Barra de risco --}}
                        <div class="risk-bar-wrap">
                            <div class="risk-bar-bg">
                                <div class="risk-bar-fill"
                                     :style="'width:' + s.risk_score + '%;background:' + (s.risk_score >= 60 ? '#EF4444' : s.risk_score >= 40 ? '#F97316' : s.risk_score >= 20 ? '#F59E0B' : '#10B981')">
                                </div>
                            </div>
                        </div>

                        <div class="last-event" x-show="s.last_event" x-text="s.last_event"></div>
                        <button type="button"
                                class="select-audit-btn"
                                :class="{ 'active': selectedSessionId === s.id }"
                                @click="selectSession(s.id)">
                            Abrir auditoria da sessao
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="filteredSessions.length === 0"
                 style="text-align:center;padding:64px;color:var(--text-muted)">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 12px;display:block;opacity:0.4">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <p style="font-weight:600;margin:0">Nenhuma sessão encontrada</p>
            </div>
        </div>

        {{-- View: Tabela --}}
        <div x-show="viewMode === 'table'" style="background:var(--surface-card);border-radius:12px;border:1px solid var(--surface-border);overflow:hidden">
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estudante</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Risco</th>
                            <th>Violações</th>
                            <th>Nota</th>
                            <th>Capturas</th>
                            <th>Inicio</th>
                            <th>Auditoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="s in filteredSessions" :key="s.id">
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px">
                                        <template x-if="s.student_photo">
                                            <img :src="s.student_photo" :alt="s.student_name" style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0">
                                        </template>
                                        <template x-if="!s.student_photo">
                                            <div class="student-avatar" style="width:28px;height:28px;font-size:0.65rem" x-text="initials(s.student_name)"></div>
                                        </template>
                                        <span style="font-weight:600" x-text="s.student_name || '—'"></span>
                                    </div>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text-muted)" x-text="s.student_email || '—'"></td>
                                <td>
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;font-weight:600;padding:3px 10px;border-radius:9999px"
                                          :style="statusStyle(s.status)"
                                          x-text="statusLabel(s.status)">
                                    </span>
                                </td>
                                <td>
                                    <span class="risk-badge"
                                          :class="{
                                              'risk-critical': s.risk_score >= 60,
                                              'risk-high':     s.risk_score >= 40 && s.risk_score < 60,
                                              'risk-medium':   s.risk_score >= 20 && s.risk_score < 40,
                                              'risk-low':      s.risk_score < 20,
                                          }"
                                          x-text="s.risk_score + '%'">
                                    </span>
                                </td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:600"
                                    :style="s.violation_count > 0 ? 'color:var(--color-info)' : ''"
                                    x-text="s.violation_count">
                                </td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:600"
                                    x-text="s.final_score !== null ? Number(s.final_score).toFixed(1) : '—'">
                                </td>
                                <td style="font-family:'JetBrains Mono',monospace;font-weight:600" x-text="s.snapshots_count ?? 0"></td>
                                <td style="font-size:0.8rem;color:var(--text-secondary)" x-text="s.started_at ? new Date(s.started_at).toLocaleTimeString('pt-BR') : '—'"></td>
                                <td>
                                    <button type="button"
                                            class="select-audit-btn"
                                            :class="{ 'active': selectedSessionId === s.id }"
                                            @click="selectSession(s.id)">
                                        Ver auditoria
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <section id="audit-panel" class="audit-panel" style="margin-top:18px">
            <div class="audit-panel-header">
                <div class="audit-panel-title">Auditoria da sessao</div>
                <div class="audit-panel-subtitle">
                    Evidencias visuais e trilha de seguranca da prova para comprovar o monitoramento do aluno.
                </div>
            </div>

            <div class="audit-panel-body" x-show="selectedSession">
                <div class="audit-session-card">
                    <div style="display:flex;align-items:flex-start;gap:10px">
                        <template x-if="selectedSession?.student_photo">
                            <img :src="selectedSession.student_photo" :alt="selectedSession.student_name" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0">
                        </template>
                        <template x-if="!selectedSession?.student_photo">
                            <div class="student-avatar" x-text="initials(selectedSession?.student_name)"></div>
                        </template>
                        <div style="min-width:0;flex:1">
                            <div style="font-size:0.95rem;font-weight:800;color:var(--text-primary)" x-text="selectedSession?.student_name || '-'"></div>
                            <div style="font-size:0.76rem;color:var(--text-secondary);margin-top:3px" x-text="selectedSession?.student_email || ''"></div>
                        </div>
                        <span class="risk-badge"
                              :class="{
                                  'risk-critical': (selectedSession?.risk_score ?? 0) >= 60,
                                  'risk-high':     (selectedSession?.risk_score ?? 0) >= 40 && (selectedSession?.risk_score ?? 0) < 60,
                                  'risk-medium':   (selectedSession?.risk_score ?? 0) >= 20 && (selectedSession?.risk_score ?? 0) < 40,
                                  'risk-low':      (selectedSession?.risk_score ?? 0) < 20,
                              }"
                              x-text="riskLabel(selectedSession?.risk_score ?? 0)">
                        </span>
                    </div>

                    <div class="audit-meta-grid" style="margin-top:14px">
                        <div class="audit-meta-item">
                            <div class="audit-meta-label">Status</div>
                            <div class="audit-meta-value" x-text="statusLabel(selectedSession?.status)"></div>
                        </div>
                        <div class="audit-meta-item">
                            <div class="audit-meta-label">Capturas</div>
                            <div class="audit-meta-value" x-text="selectedSession?.snapshots_count ?? 0"></div>
                        </div>
                        <div class="audit-meta-item">
                            <div class="audit-meta-label">Violacoes</div>
                            <div class="audit-meta-value" x-text="selectedSession?.violation_count ?? 0"></div>
                        </div>
                        <div class="audit-meta-item">
                            <div class="audit-meta-label">Ultima atividade</div>
                            <div class="audit-meta-value" x-text="formatDateTime(selectedSession?.submitted_at || selectedSession?.started_at)"></div>
                        </div>
                    </div>
                </div>

                <section>
                    <div class="audit-section-title">Capturas de auditoria</div>
                    <div class="snapshot-grid" x-show="(selectedSession?.snapshots || []).length > 0">
                        <template x-for="snapshot in (selectedSession?.snapshots || [])" :key="snapshot.id">
                            <a class="snapshot-card" :href="snapshot.url" target="_blank" rel="noopener noreferrer">
                                <img class="snapshot-image" :src="snapshot.url" alt="Snapshot de auditoria">
                                <div class="snapshot-caption">
                                    <span class="snapshot-trigger" x-text="triggerLabel(snapshot.trigger)"></span>
                                    <span x-text="formatDateTime(snapshot.captured_at)"></span>
                                    <span style="font-family:'JetBrains Mono',monospace" x-text="'Hash ' + shortHash(snapshot.sha256_hash)"></span>
                                </div>
                            </a>
                        </template>
                    </div>
                    <div class="audit-empty" x-show="(selectedSession?.snapshots || []).length === 0">
                        Ainda nao existem capturas de auditoria disponiveis para esta sessao.
                    </div>
                </section>

                <section>
                    <div class="audit-section-title">Trilha de seguranca</div>
                    <div class="timeline-list" x-show="(selectedSession?.security_events || []).length > 0">
                        <template x-for="event in (selectedSession?.security_events || [])" :key="event.id">
                            <div class="timeline-item">
                                <div class="timeline-title" x-text="eventLabel(event.type)"></div>
                                <div class="timeline-meta" x-text="formatDateTime(event.captured_at)"></div>
                                <div class="timeline-meta" x-show="event.metadata && Object.keys(event.metadata).length > 0" x-text="eventMetadata(event.metadata)"></div>
                            </div>
                        </template>
                    </div>
                    <div class="audit-empty" x-show="(selectedSession?.security_events || []).length === 0">
                        Nenhum evento de seguranca registrado para esta sessao ate o momento.
                    </div>
                </section>
            </div>

            <div class="audit-panel-body" x-show="!selectedSession">
                <div class="audit-empty">
                    Selecione uma sessao no painel para visualizar as capturas de auditoria e os eventos de seguranca.
                </div>
            </div>
        </section>

    </div>

    <script>
    function monitorApp() {
        return {
            sessions:  @json($sessions),
            filter:    'all',
            viewMode:  'cards',
            selectedSessionId: null,

            get stats() {
                return {
                    active:          this.sessions.filter(s => s.status === 'in_progress').length,
                    submitted:       this.sessions.filter(s => ['graded','submitted'].includes(s.status)).length,
                    totalViolations: this.sessions.reduce((acc, s) => acc + (s.violation_count || 0), 0),
                    critical:        this.sessions.filter(s => s.risk_score >= 60).length,
                };
            },

            get filteredSessions() {
                return this.sessions.filter(s => {
                    if (this.filter === 'all')        return true;
                    if (this.filter === 'violations') return s.violation_count > 0;
                    if (this.filter === 'critical')   return s.risk_score >= 60;
                    if (this.filter === 'submitted')  return ['graded','submitted'].includes(s.status);
                    return s.status === this.filter;
                });
            },

            get selectedSession() {
                if (this.selectedSessionId === null) {
                    return null;
                }

                return this.find(this.selectedSessionId) ?? null;
            },

            sessionPriority(session) {
                const rank = {
                    in_progress: 0,
                    graded: 1,
                    submitted: 2,
                    expired: 3,
                    terminated: 4,
                    pending: 5,
                };

                return rank[session?.status] ?? 99;
            },

            sessionActivityStamp(session) {
                const candidate = session?.submitted_at || session?.started_at || session?.updated_at || null;

                if (!candidate) return 0;

                const parsed = Date.parse(candidate);

                return Number.isNaN(parsed) ? 0 : parsed;
            },

            preferredSession(sessions) {
                if (!Array.isArray(sessions) || sessions.length === 0) {
                    return null;
                }

                return [...sessions].sort((left, right) => {
                    const priorityDelta = this.sessionPriority(left) - this.sessionPriority(right);

                    if (priorityDelta !== 0) {
                        return priorityDelta;
                    }

                    return this.sessionActivityStamp(right) - this.sessionActivityStamp(left);
                })[0] ?? null;
            },

            initials(name) {
                if (!name) return '?';
                return name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
            },

            riskLabel(score) {
                if (score >= 60) return '🔴 Crítico';
                if (score >= 40) return '🟠 Alto';
                if (score >= 20) return '🟡 Médio';
                return '🟢 Baixo';
            },

            riskColor(score) {
                if (score >= 60) return 'color:var(--color-danger)';
                if (score >= 40) return 'color:var(--color-info)';
                if (score >= 20) return 'color:var(--color-warning)';
                return 'color:var(--color-success)';
            },

            statusLabel(status) {
                const map = {
                    pending:     'Aguardando',
                    in_progress: 'Em Prova',
                    graded:      'Entregue',
                    submitted:   'Entregue',
                    expired:     'Expirada',
                    terminated:  'Encerrada',
                };
                return map[status] || status;
            },

            statusStyle(status) {
                const map = {
                    pending:     'background:var(--color-warning-bg);color:var(--color-warning)',
                    in_progress: 'background:var(--color-primary-50);color:var(--color-primary-700)',
                    graded:      'background:var(--color-success-bg);color:var(--color-success)',
                    submitted:   'background:var(--color-success-bg);color:var(--color-success)',
                    expired:     'background:#F1F5F9;color:#64748B',
                    terminated:  'background:var(--color-danger-bg);color:var(--color-danger)',
                };
                return map[status] || '';
            },

            triggerLabel(trigger) {
                const map = {
                    start: 'Inicio',
                    scheduled: 'Agendada',
                    violation: 'Violacao',
                    end: 'Entrega',
                };
                return map[trigger] || trigger || 'Captura';
            },

            eventLabel(type) {
                const map = {
                    fullscreen_exit: 'Saida de tela cheia',
                    fullscreen_denied: 'Tela cheia negada',
                    tab_switch: 'Troca de aba',
                    window_blur: 'Perda de foco',
                    shortcut_blocked: 'Atalho bloqueado',
                    right_click_blocked: 'Clique direito bloqueado',
                    inactivity_warning: 'Aviso de inatividade',
                    inactivity_timeout: 'Violacao por inatividade',
                    possible_second_monitor: 'Possivel segundo monitor',
                    webcam_unavailable: 'Camera indisponivel',
                    webcam_obstructed: 'Camera obstruida',
                    connection_lost: 'Conexao perdida',
                    violation_limit_reached: 'Limite de violacoes atingido',
                    voluntary_exit: 'Saida voluntaria',
                };
                return map[type] || type || 'Evento';
            },

            eventMetadata(metadata) {
                if (!metadata || typeof metadata !== 'object') return '';

                return Object.entries(metadata)
                    .slice(0, 3)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join(' • ');
            },

            formatDateTime(value) {
                if (!value) return '—';
                return new Date(value).toLocaleString('pt-BR');
            },

            shortHash(hash) {
                if (!hash) return '-';
                return `${hash.slice(0, 8)}...`;
            },

            selectSession(id) {
                this.selectedSessionId = id;
                this.$nextTick(() => {
                    const panel = document.getElementById('audit-panel');
                    if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            },

            init() {
                // Não selecionar nenhuma sessão automaticamente;
                // o usuário precisa clicar em "Abrir auditoria da sessao".
                this.selectedSessionId = null;
                this.connectWebSocket();
                setInterval(() => { /* tick for live clock if needed */ }, 1000);
            },

            connectWebSocket() {
                if (typeof Echo === 'undefined') return;
                const echo = new Echo({
                    broadcaster: 'reverb',
                    key:         '{{ config("broadcasting.connections.reverb.key") }}',
                    wsHost:      '{{ config("broadcasting.connections.reverb.options.host", "localhost") }}',
                    wsPort:      {{ config("broadcasting.connections.reverb.options.port", 8080) }},
                    forceTLS:    false,
                    enabledTransports: ['ws'],
                });
                echo.private('exam.{{ $exam->id }}.monitor')
                    .listen('.exam.started',    e => this.onStarted(e))
                    .listen('.exam.submitted',  e => this.onSubmitted(e))
                    .listen('.violation.detected', e => this.onViolation(e))
                    .listen('.session.expired', e => this.onExpired(e))
                    .listen('.suspicious.activity', e => this.onSuspicious(e));
            },

            find(id) { return this.sessions.find(s => s.id === id); },

            onStarted(e) {
                const s = this.find(e.session_id);
                if (s) { s.status = 'in_progress'; s.started_at = e.started_at; }
            },
            onSubmitted(e) {
                const s = this.find(e.session_id);
                if (s) { s.status = 'graded'; s.final_score = e.final_score; s.submitted_at = e.submitted_at; }
            },
            onViolation(e) {
                const s = this.find(e.session_id);
                if (s) {
                    s.violation_count = e.violation_count;
                    s.risk_score      = e.risk_score;
                    s.last_event      = e.violation_type + ' — ' + new Date().toLocaleTimeString('pt-BR');
                    s.security_events = [
                        {
                            id: `live-${Date.now()}`,
                            type: e.violation_type,
                            captured_at: e.captured_at,
                            metadata: { violation_count: e.violation_count, risk_score: e.risk_score },
                        },
                        ...(s.security_events || []),
                    ].slice(0, 10);
                }
            },
            onExpired(e) {
                const s = this.find(e.session_id);
                if (s) s.status = 'expired';
            },
            onSuspicious(e) {
                const s = this.find(e.session_id);
                if (s) { s.risk_score = e.risk_score; s.last_event = '⚠ ' + e.reason; }
            },
        };
    }
    </script>
</body>
</html>
