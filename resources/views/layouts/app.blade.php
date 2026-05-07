<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AvaliaFA')</title>

    {{-- Anti-flash de tema --}}
    <script>
        (function () {
            const t = localStorage.getItem('avalia-theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    {{-- Fontes --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- TailwindCSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: ['class', '[data-theme="dark"]'],
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Inter"', 'Arial', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                }
            }
        }
    </script>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }

        /* ═══════════════════════════════════════════════════════════
           DESIGN SYSTEM — AvaliaFA
        ═══════════════════════════════════════════════════════════ */
        :root {
            --color-primary-900: #0F2044;
            --color-primary-800: #1E3A5F;
            --color-primary-700: #1D4ED8;
            --color-primary-600: #2563EB;
            --color-primary-500: #3B82F6;
            --color-primary-400: #60A5FA;
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
            --surface-sidebar:   #FFFFFF;
            --surface-border:    #E2E8F0;
            --surface-input:     #FFFFFF;
            --text-primary:      #0F172A;
            --text-secondary:    #64748B;
            --text-muted:        #94A3B8;
            --text-inverse:      #FFFFFF;
            --sidebar-width:     248px;
            --topbar-height:     60px;
        }

        [data-theme="dark"] {
            --color-primary-50:  #172554;
            --color-primary-100: #1E3A5F;
            --color-primary-400: #1D4ED8;
            --color-primary-500: #2563EB;
            --color-primary-600: #3B82F6;
            --color-primary-700: #60A5FA;
            --color-success:     #34D399;
            --color-success-bg:  #064E3B;
            --color-warning:     #FBBF24;
            --color-warning-bg:  #78350F;
            --color-danger:      #F87171;
            --color-danger-bg:   #7F1D1D;
            --color-info:        #FB923C;
            --color-info-bg:     #7C2D12;
            --surface-bg:        #0F172A;
            --surface-card:      #1E293B;
            --surface-sidebar:   #0F172A;
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
        html, body { margin: 0; padding: 0; height: 100%; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--surface-bg);
            color: var(--text-primary);
        }

        /* ── Sidebar ─────────────────────────────────────── */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--surface-sidebar);
            border-right: 1px solid var(--surface-border);
            display: flex; flex-direction: column;
            overflow-y: auto; overflow-x: hidden;
            z-index: 50;
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .sidebar-logo {
            padding: 20px 20px 16px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--surface-border);
            flex-shrink: 0;
        }
        .sidebar-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, #1D4ED8 0%, #3B82F6 100%);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 800; font-size: 16px;
            flex-shrink: 0; box-shadow: 0 2px 8px rgba(29,78,216,0.35);
        }
        .sidebar-logo-text {
            font-size: 1.125rem; font-weight: 800;
            color: var(--color-primary-800);
            letter-spacing: -0.02em;
        }
        [data-theme="dark"] .sidebar-logo-text { color: var(--color-primary-700); }
        .sidebar-subtitle {
            font-size: 0.6rem; font-weight: 500;
            color: var(--text-muted); letter-spacing: 0.05em;
            text-transform: uppercase; margin-top: 1px;
        }
        .sidebar-section-label {
            padding: 16px 20px 5px;
            font-size: 0.65rem; font-weight: 700;
            color: var(--text-muted); letter-spacing: 0.1em;
            text-transform: uppercase; flex-shrink: 0;
        }
        .sidebar-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; margin: 1px 8px;
            border-radius: 8px;
            font-size: 0.875rem; font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none; cursor: pointer;
            background: none; border: none; width: calc(100% - 16px);
            text-align: left;
        }
        .sidebar-item:hover {
            background: var(--color-primary-50);
            color: var(--color-primary-700);
        }
        .sidebar-item.active {
            background: linear-gradient(90deg, rgba(37,99,235,0.12) 0%, rgba(37,99,235,0.04) 100%);
            color: var(--color-primary-700);
            font-weight: 600;
        }
        [data-theme="dark"] .sidebar-item.active {
            background: rgba(96,165,250,0.15);
        }
        .sidebar-item svg { flex-shrink: 0; opacity: 0.8; }
        .sidebar-item.active svg { opacity: 1; }
        .sidebar-badge {
            margin-left: auto;
            background: var(--color-primary-500); color: white;
            font-size: 0.65rem; font-weight: 700;
            padding: 2px 7px; border-radius: 9999px;
        }
        .sidebar-footer {
            margin-top: auto; padding: 8px;
            border-top: 1px solid var(--surface-border);
            flex-shrink: 0;
        }
        .sidebar-user {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px; border-radius: 8px;
            cursor: default; margin-bottom: 4px;
        }

        /* ── Avatar ─────────────────────────────────────── */
        .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, #1D4ED8 0%, #8B5CF6 100%);
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 0.75rem; font-weight: 700;
            flex-shrink: 0;
        }
        .avatar-lg { width: 44px; height: 44px; font-size: 1rem; }
        .avatar-name { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); line-height: 1.2; }
        .avatar-role { font-size: 0.7rem; color: var(--text-muted); margin-top: 1px; }

        /* ── Topbar ─────────────────────────────────────── */
        .topbar {
            position: fixed; top: 0; right: 0;
            left: var(--sidebar-width);
            height: var(--topbar-height);
            background: var(--surface-card);
            border-bottom: 1px solid var(--surface-border);
            display: flex; align-items: center;
            padding: 0 24px; gap: 12px; z-index: 40;
        }
        .topbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .topbar-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
        .btn-icon {
            width: 34px; height: 34px; border-radius: 8px;
            border: 1px solid var(--surface-border);
            background: var(--surface-card);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: var(--text-secondary);
        }
        .btn-icon:hover { background: var(--color-primary-50); color: var(--color-primary-700); border-color: var(--color-primary-100); }
        .topbar-notification { position: relative; display: inline-flex; }
        .topbar-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #2563EB;
            color: #FFFFFF;
            font-size: 0.64rem;
            font-weight: 800;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.30);
        }
        .topbar-panel {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: min(360px, calc(100vw - 32px));
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
            padding: 10px;
            display: none;
            z-index: 80;
        }
        .topbar-panel.open { display: block; }
        .topbar-panel-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0 0 8px;
            padding: 4px 6px 8px;
            border-bottom: 1px solid var(--surface-border);
        }
        .topbar-panel-list {
            display: grid;
            gap: 8px;
            max-height: 280px;
            overflow: auto;
            padding: 2px;
        }
        .topbar-panel-item {
            display: block;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--surface-border);
            border-radius: 12px;
            padding: 10px 12px;
            background: var(--surface-muted);
        }
        .topbar-panel-item:hover {
            border-color: var(--color-primary-200);
            background: var(--color-primary-50);
        }
        .topbar-panel-item-title {
            font-size: 0.83rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 3px;
        }
        .topbar-panel-item-copy {
            font-size: 0.76rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.4;
        }
        .topbar-panel-empty {
            font-size: 0.78rem;
            color: var(--text-muted);
            padding: 12px 10px;
            text-align: center;
        }
        .topbar-panel-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 8px;
            margin-top: 8px;
            border-top: 1px solid var(--surface-border);
        }
        .topbar-panel-link {
            height: 34px;
            padding: 0 12px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--color-primary-700);
            text-decoration: none;
            background: var(--color-primary-50);
            border: 1px solid var(--color-primary-100);
        }

        /* ── Content area ──────────────────────────────── */
        .content-area {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            min-height: 100vh;
        }
        .page-content { padding: 28px; width: 100%; }
        .global-platform-footer {
            margin-left: var(--sidebar-width);
            border-top: 1px solid var(--surface-border);
            background: var(--surface-card);
            color: var(--text-secondary);
            text-align: center;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 12px 16px;
        }
        .page-header { margin-bottom: 24px; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0; }
        .page-subtitle { font-size: 0.875rem; color: var(--text-secondary); margin-top: 4px; }

        /* ── Cards ──────────────────────────────────────── */
        .card {
            background: var(--surface-card);
            border-radius: 12px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .card-body { padding: 20px 24px; }
        .card-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--surface-border);
            display: flex; align-items: center; justify-content: space-between;
            border-radius: 12px 12px 0 0;
        }
        .card-title { font-size: 0.9375rem; font-weight: 600; color: var(--text-primary); }
        .card-subtitle { font-size: 0.8125rem; color: var(--text-secondary); margin-top: 2px; }

        /* ── KPI Cards ──────────────────────────────────── */
        .kpi-card {
            background: var(--surface-card);
            border-radius: 12px;
            border: 1px solid var(--surface-border);
            padding: 20px 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative; overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, #1D4ED8, #3B82F6);
            opacity: 0; transition: opacity 0.2s;
        }
        .kpi-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .kpi-card:hover::before { opacity: 1; }
        .kpi-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
        }
        .kpi-value { font-size: 2.25rem; font-weight: 700; color: var(--text-primary); margin: 6px 0; line-height: 1; }
        .kpi-label { font-size: 0.8125rem; font-weight: 500; color: var(--text-secondary); }
        .kpi-badge {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 2px 8px; border-radius: 9999px;
            font-size: 0.72rem; font-weight: 600; margin-top: 8px;
        }
        .kpi-badge-up   { color: var(--color-success); background: var(--color-success-bg); }
        .kpi-badge-down { color: var(--color-danger);  background: var(--color-danger-bg); }

        /* ── Badges ─────────────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px; border-radius: 9999px;
            font-size: 0.72rem; font-weight: 600; white-space: nowrap;
        }
        .badge-success { background: var(--color-success-bg); color: var(--color-success); }
        .badge-warning { background: var(--color-warning-bg); color: var(--color-warning); }
        .badge-danger  { background: var(--color-danger-bg);  color: var(--color-danger); }
        .badge-info    { background: var(--color-info-bg);    color: var(--color-info); }
        .badge-primary { background: var(--color-primary-100); color: var(--color-primary-700); }
        .badge-neutral { background: #F1F5F9; color: #64748B; }
        [data-theme="dark"] .badge-neutral { background: #334155; color: #94A3B8; }

        /* ── Botões ─────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 18px; border-radius: 8px;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; border: none; outline: none;
            transition: all 0.15s ease; text-decoration: none;
            white-space: nowrap; font-family: inherit;
        }
        .btn:active { transform: scale(0.97); }
        .btn-primary   { background: var(--color-primary-600); color: #fff; box-shadow: 0 2px 6px rgba(37,99,235,0.35); }
        .btn-primary:hover { background: var(--color-primary-700); box-shadow: 0 4px 12px rgba(37,99,235,0.4); }
        .btn-secondary { background: var(--color-primary-50); color: var(--color-primary-700); border: 1px solid var(--color-primary-100); }
        .btn-secondary:hover { background: var(--color-primary-100); }
        .btn-danger    { background: var(--color-danger-bg); color: var(--color-danger); border: 1px solid transparent; }
        .btn-danger:hover { background: var(--color-danger); color: #fff; }
        .btn-ghost     { background: transparent; color: var(--text-secondary); border: 1px solid var(--surface-border); }
        .btn-ghost:hover { background: var(--color-primary-50); color: var(--color-primary-700); border-color: var(--color-primary-100); }
        .btn-sm { padding: 5px 12px; font-size: 0.75rem; border-radius: 6px; }
        .btn-lg { padding: 12px 28px; font-size: 1rem; border-radius: 10px; }

        /* ── Inputs ─────────────────────────────────────── */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 6px; }
        .input {
            width: 100%; padding: 10px 14px;
            background: var(--surface-input);
            border: 1.5px solid var(--surface-border);
            border-radius: 8px;
            font-size: 0.875rem; color: var(--text-primary);
            font-family: 'Inter', Arial, sans-serif;
            outline: none; transition: border-color 0.15s, box-shadow 0.15s;
        }
        .input:focus {
            border-color: var(--color-primary-500);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .input::placeholder { color: var(--text-muted); }
        .input-error { border-color: var(--color-danger); }
        .input-error:focus { box-shadow: 0 0 0 3px rgba(239,68,68,0.12); }
        .form-error { font-size: 0.75rem; color: var(--color-danger); margin-top: 4px; }

        /* ── Tabela ─────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .data-table th {
            padding: 10px 16px; text-align: left;
            font-size: 0.7rem; font-weight: 700;
            color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.07em;
            border-bottom: 2px solid var(--surface-border);
        }
        .data-table td { padding: 12px 16px; color: var(--text-primary); border-bottom: 1px solid var(--surface-border); }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover td { background: var(--color-primary-50); }
        [data-theme="dark"] .data-table tr:hover td { background: rgba(51,65,85,0.4); }

        /* ── Alertas ────────────────────────────────────── */
        .alert {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 16px; border-radius: 10px;
            font-size: 0.875rem; font-weight: 500;
            margin-bottom: 16px;
        }
        .alert-success { background: var(--color-success-bg); color: var(--color-success); border: 1px solid rgba(16,185,129,0.2); }
        .alert-error   { background: var(--color-danger-bg);  color: var(--color-danger);  border: 1px solid rgba(239,68,68,0.2); }
        .alert-warning { background: var(--color-warning-bg); color: var(--color-warning); border: 1px solid rgba(245,158,11,0.2); }
        .alert-info    { background: var(--color-primary-50); color: var(--color-primary-700); border: 1px solid var(--color-primary-100); }

        /* ── Animações ──────────────────────────────────── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card-enter { animation: fadeInUp 0.35s ease forwards; }
        .card-enter:nth-child(1) { animation-delay: 0ms; }
        .card-enter:nth-child(2) { animation-delay: 70ms; }
        .card-enter:nth-child(3) { animation-delay: 140ms; }
        .card-enter:nth-child(4) { animation-delay: 210ms; }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(1.5); }
        }
        .live-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: var(--color-success);
            animation: pulse-dot 2s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes shimmer {
            0%   { background-position: -800px 0; }
            100% { background-position:  800px 0; }
        }
        .skeleton {
            border-radius: 8px;
            background: linear-gradient(90deg, var(--surface-border) 25%, var(--surface-bg) 50%, var(--surface-border) 75%);
            background-size: 800px 100%;
            animation: shimmer 1.6s infinite;
        }

        /* ── Divisor ────────────────────────────────────── */
        .divider { border: none; border-top: 1px solid var(--surface-border); margin: 20px 0; }

        /* ── Mobile ─────────────────────────────────────── */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); box-shadow: none; }
            .sidebar.open { transform: translateX(0); box-shadow: 8px 0 24px rgba(0,0,0,0.15); }
            .topbar { left: 0; }
            .content-area { margin-left: 0; }
            .page-content { padding: 16px; }
            .global-platform-footer { margin-left: 0; }
            .topbar {
                padding: 0 12px;
                gap: 8px;
            }
            .topbar-title {
                font-size: 0.92rem;
            }
            .profile-btn {
                padding: 2px 4px;
                gap: 6px;
            }
            .profile-btn-text {
                display: none;
            }
            .profile-btn > svg {
                display: none;
            }
        }

        /* ── Profile Dropdown ──────────────────────────── */
        .profile-btn {
            display: flex; align-items: center; gap: 8px;
            background: none; border: none; cursor: pointer;
            padding: 4px 6px 4px 4px; border-radius: 10px;
            transition: background 0.15s;
        }
        .profile-btn:hover { background: var(--color-primary-50); }
        .profile-btn-text { text-align: left; }
        .profile-btn-name { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary); line-height: 1.2; }
        .profile-btn-role { font-size: 0.65rem; color: var(--text-muted); }
        .profile-dropdown {
            position: absolute; right: 0; top: calc(100% + 8px);
            width: 230px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
            z-index: 200; overflow: hidden;
        }
        .profile-dropdown-header {
            padding: 16px;
            background: linear-gradient(135deg, var(--color-primary-50), var(--surface-bg));
            border-bottom: 1px solid var(--surface-border);
        }
        .profile-menu-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 14px; border-radius: 8px; margin: 2px 6px;
            font-size: 0.875rem; font-weight: 500; color: var(--text-secondary);
            text-decoration: none; cursor: pointer;
            background: none; border: none; width: calc(100% - 12px);
            text-align: left; font-family: inherit;
            transition: background 0.12s, color 0.12s;
        }
        .profile-menu-item:hover { background: var(--color-primary-50); color: var(--color-primary-700); }
        .profile-menu-item.danger:hover { background: var(--color-danger-bg); color: var(--color-danger); }

        /* ── Scrollbar personalizada ────────────────────── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--surface-border); border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ── Toast ──────────────────────────────────────── */
        .toast-wrap {
            position: fixed; top: 24px; right: 24px;
            z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
            pointer-events: none;
            max-width: min(420px, calc(100vw - 32px));
        }
        .toast {
            pointer-events: all;
            display: flex; align-items: flex-start; gap: 12px;
            padding: 14px 16px;
            border-radius: 12px;
            background: var(--surface-card);
            border: 1px solid var(--surface-border);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
            font-size: 0.875rem; font-weight: 500;
            color: var(--text-primary);
            min-width: 280px;
            position: relative; overflow: hidden;
        }
        .toast-icon { flex-shrink: 0; margin-top: 1px; }
        .toast-body { flex: 1; min-width: 0; }
        .toast-title { font-weight: 700; font-size: 0.8125rem; margin-bottom: 2px; }
        .toast-msg   { font-size: 0.8125rem; color: var(--text-secondary); line-height: 1.45; }
        .toast-close {
            flex-shrink: 0; background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: 0; line-height: 1;
            transition: color 0.15s;
        }
        .toast-close:hover { color: var(--text-primary); }
        .toast-progress {
            position: absolute; bottom: 0; left: 0; height: 3px; border-radius: 0 0 0 12px;
            transition: width linear;
        }
        .toast-success .toast-progress { background: var(--color-success); }
        .toast-error   .toast-progress { background: var(--color-danger); }
        .toast-warning .toast-progress { background: var(--color-warning); }
        .toast-info    .toast-progress { background: var(--color-primary-500); }
        .toast-success .toast-icon { color: var(--color-success); }
        .toast-error   .toast-icon { color: var(--color-danger); }
        .toast-warning .toast-icon { color: var(--color-warning); }
        .toast-info    .toast-icon { color: var(--color-primary-600); }
    </style>

    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">A</div>
        <div>
            <div class="sidebar-logo-text">AvaliaFA</div>
            <div class="sidebar-subtitle">Faculdade Anasps</div>
        </div>
    </div>

    @php($currentUser = auth()->user())
    @php($canManageExams = $currentUser?->canManageExams())
    @php($canAccessReports = $currentUser?->canAccessReports())
    @php($canAccessSimuladoCrm = $currentUser?->canAccessSimuladoCrm())
    @php($canAccessMonitoring = $currentUser?->canAccessMonitoring())
    @php($maintenanceModeActive = \App\Http\Middleware\MaintenanceMode::isActive())
    @php($canManagePeople = $currentUser?->canManagePeople())
    @php($isSuperAdmin = $currentUser?->isSuperAdmin())
    @php($rankingEnabled = \App\Models\Setting::get('simulados', 'ranking_enabled'))
    @php($canAccessRanking = $rankingEnabled && $currentUser?->isAdmin())

    @if($currentUser?->isStudent())
    <div class="sidebar-section-label">Aluno</div>
    <a href="{{ route('simulados.minha-area') }}" class="sidebar-item {{ request()->routeIs('simulados.minha-area') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/>
        </svg>
        Meus Simulados
    </a>
    @php($hasTips = \App\Models\TeacherTip::active()->forSystem($currentUser->client_system_id)->exists())
    @php($hasMaterials = \App\Models\LearningMaterial::active()->forSystem($currentUser->client_system_id)->exists())

    @if($hasTips && !$currentUser->isStudent())
    <a href="{{ route('student.tips.index') }}" class="sidebar-item {{ request()->routeIs('student.tips.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2a7 7 0 0 1 7 7c0 5-7 13-7 13S5 14 5 9a7 7 0 0 1 7-7z"/>
            <circle cx="12" cy="9" r="2.5"/>
        </svg>
        Dicas do Professor
    </a>
    @endif

    @if($hasMaterials)
    <a href="{{ route('student.materials.index') }}" class="sidebar-item {{ request()->routeIs('student.materials.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
        </svg>
        Materiais
    </a>
    @endif
    @if($canAccessRanking)
    <a href="{{ route('simulados.ranking') }}" class="sidebar-item {{ request()->routeIs('simulados.ranking') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 21h8m-4-4v4m-4-9l4-4 4 4M4 5h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z"/>
        </svg>
        Ranking
    </a>
    @endif
    @else
    <div class="sidebar-section-label">Principal</div>
    <a href="{{ route('dashboard') }}" class="sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
        </svg>
        Dashboard
    </a>

    @if($canManageExams)
    <div class="sidebar-section-label">Provas</div>
    <a href="{{ route('provas.index') }}" class="sidebar-item {{ request()->routeIs('provas.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        Provas
    </a>
    <a href="{{ route('questoes.index') }}" class="sidebar-item {{ request()->routeIs('questoes.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        Questoes
    </a>
    @endif

    @if($canAccessReports || $canAccessSimuladoCrm || $canAccessMonitoring)
    <div class="sidebar-section-label">Operacao</div>
    @if($canAccessReports)
    <a href="{{ route('relatorios.index') }}" class="sidebar-item {{ request()->routeIs('relatorios.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="5"/><rect x="12" y="9" width="3" height="8"/><rect x="17" y="6" width="3" height="11"/>
        </svg>
        Relatorios
    </a>
    @endif
    @if($canAccessSimuladoCrm)
    <a href="{{ route('simulados.index') }}" class="sidebar-item {{ request()->routeIs('simulados.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 4h18v4H3z"/><path d="M3 10h18v10H3z"/><path d="M8 14h3"/><path d="M13 14h3"/><path d="M8 18h8"/>
        </svg>
        Simulados
    </a>
    @endif
    @if($canAccessRanking && $currentUser?->isAdmin())
    <a href="{{ route('simulados.ranking') }}" class="sidebar-item {{ request()->routeIs('simulados.ranking') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 21h8m-4-4v4m-4-9l4-4 4 4M4 5h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V6a1 1 0 011-1z"/>
        </svg>
        Ranking
    </a>
    @endif
    @if($canAccessMonitoring)
    <a href="{{ route('monitor.index') }}" class="sidebar-item {{ request()->routeIs('monitor.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
        </svg>
        Monitoramento
    </a>
    @endif
    @endif

    @if($canManagePeople)
    <div class="sidebar-section-label">Pessoas</div>
    <a href="{{ route('estudantes.index') }}" class="sidebar-item {{ request()->routeIs('estudantes.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
        Estudantes
    </a>
    <a href="{{ route('certificados.index') }}" class="sidebar-item {{ request()->routeIs('certificados.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="7"/>
            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
        </svg>
        Certificados
    </a>
    @endif
    @if($isSuperAdmin)
    <div class="sidebar-section-label">Administração</div>
    <a href="{{ route('sistemas.index') }}" class="sidebar-item {{ request()->routeIs('sistemas.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
            <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
        Sistemas
    </a>
    <a href="{{ route('configuracoes.index') }}" class="sidebar-item {{ request()->routeIs('configuracoes.*') ? 'active' : '' }}">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
        </svg>
        Configuracoes
    </a>
    <a href="/horizon" target="_blank" class="sidebar-item">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <polyline points="12 6 12 12 16 14"/>
        </svg>
        Horizon
        <span class="sidebar-badge" style="background:#8B5CF6">↗</span>
    </a>
    <form method="POST" action="{{ route('admin.manutencao.toggle') }}" style="margin:4px 8px 0">
        @csrf
        <button type="submit"
            style="width:100%;display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:8px;border:1px solid {{ $maintenanceModeActive ? '#FCA5A5' : '#D1FAE5' }};background:{{ $maintenanceModeActive ? '#FEF2F2' : '#F0FDF4' }};color:{{ $maintenanceModeActive ? '#DC2626' : '#15803D' }};font-size:0.78rem;font-weight:600;cursor:pointer;text-align:left">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                @if($maintenanceModeActive)
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                @else
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <polyline points="9 12 11 14 15 10"/>
                @endif
            </svg>
            {{ $maintenanceModeActive ? 'Desativar Manutenção' : 'Modo Manutenção' }}
        </button>
    </form>
    @endif
    @endif

    <div class="sidebar-footer">
        <div style="padding:10px 12px;font-size:0.65rem;color:var(--text-muted);text-align:center">
            AvaliaFA · Faculdade Anasps
        </div>
    </div>
</aside>

{{-- Topbar --}}
<header class="topbar">
    <button class="btn-icon" id="sidebar-toggle" style="display:none" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <span class="topbar-title">@yield('page-title', 'Dashboard')</span>

    <div class="topbar-actions">
        @hasSection('topbar-actions')
            @yield('topbar-actions')
        @endif

        {{-- Dark mode toggle --}}
        <button
            class="btn-icon"
            title="Alternar tema"
            x-data="{ dark: document.documentElement.getAttribute('data-theme') === 'dark' }"
            @click="dark = !dark; localStorage.setItem('avalia-theme', dark ? 'dark' : 'light'); document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light')"
        >
            <svg x-show="!dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
            <svg x-show="dark" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"/>
                <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
        </button>

        <div class="topbar-notification" id="simulado-topbar-notification">
            <button class="btn-icon" id="simulado-notification-button" title="Novos simulados disponíveis">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </button>
            <span class="topbar-badge" id="simulado-notification-badge">0</span>
            <div class="topbar-panel" id="simulado-notification-panel">
                <div class="topbar-panel-title">Novos simulados disponíveis</div>
                <div class="topbar-panel-list" id="simulado-notification-list">
                    <div class="topbar-panel-empty">Nenhum novo simulado disponível no momento.</div>
                </div>
                <div class="topbar-panel-footer">
                    <a href="{{ auth()->check() && auth()->user()->isStudent() ? route('simulados.minha-area') : route('simulados.index') }}" class="topbar-panel-link">
                        Abrir simulados
                    </a>
                </div>
            </div>
        </div>

        @auth
        <div x-data="{ open: false }" style="position:relative">
            <button class="profile-btn" @click="open = !open" @click.outside="open = false">
                <div class="avatar" style="width:32px;height:32px;font-size:0.7rem;flex-shrink:0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="profile-btn-text">
                    <div class="profile-btn-name">{{ explode(' ', auth()->user()->name)[0] }}</div>
                    <div class="profile-btn-role">{{ match(auth()->user()->role) {
                        'super_admin' => 'Super Admin',
                        'admin'       => 'Administrador',
                        'coordinator' => 'Comercial',
                        'professor'   => 'Professor',
                        'student'     => 'Estudante',
                        default       => ucfirst(auth()->user()->role ?? '')
                    } }}</div>
                </div>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="color:var(--text-muted);flex-shrink:0;transition:transform 0.2s" :style="open ? 'transform:rotate(180deg)' : ''"><polyline points="6 9 12 15 18 9"/></svg>
            </button>

            <div class="profile-dropdown" x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                style="display:none"
            >
                {{-- Header --}}
                <div class="profile-dropdown-header">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="avatar avatar-lg">{{ mb_strtoupper(mb_substr(auth()->user()->name ?? 'U', 0, 2)) }}</div>
                        <div>
                            <div style="font-size:0.9rem;font-weight:700;color:var(--text-primary)">{{ auth()->user()->name }}</div>
                            @if(auth()->user()->cpf)
                            <div style="font-size:0.72rem;color:var(--text-muted);font-family:'JetBrains Mono',monospace">
                                {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', auth()->user()->cpf) }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Menu --}}
                <div style="padding:6px">
                    <a href="{{ route('perfil.index') }}" class="profile-menu-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Editar Perfil
                    </a>
                    <a href="{{ route('perfil.senha') }}" class="profile-menu-item">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Alterar Senha
                    </a>
                </div>

                {{-- Logout --}}
                <div style="border-top:1px solid var(--surface-border);padding:6px">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="profile-menu-item danger">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Sair do sistema
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endauth
    </div>
</header>

{{-- Overlay mobile --}}
<div id="sidebar-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:49"
     onclick="document.getElementById('sidebar').classList.remove('open'); this.style.display='none'"></div>

{{-- Main content --}}
<main class="content-area">
    @if($maintenanceModeActive)
    <div style="background:#FEF3C7;border-bottom:2px solid #F59E0B;padding:10px 28px;display:flex;align-items:center;gap:10px;font-size:0.82rem;font-weight:600;color:#92400E">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Sistema em modo de manutenção — alunos e visitantes veem tela de manutenção. Apenas administradores têm acesso.
        <form method="POST" action="{{ route('admin.manutencao.toggle') }}" style="margin-left:auto;flex-shrink:0">
            @csrf
            <button type="submit" style="background:#F59E0B;color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:0.78rem;font-weight:700;cursor:pointer">Desativar agora</button>
        </form>
    </div>
    @endif
    <div class="page-content">
        @yield('content')
    </div>
</main>
<footer class="global-platform-footer">@include('partials.system-footer-text')</footer>

<script>
    // Mobile sidebar toggle
    const mq = window.matchMedia('(max-width: 768px)');
    function handleMq(e) {
        document.getElementById('sidebar-toggle').style.display = e.matches ? 'flex' : 'none';
    }
    mq.addEventListener('change', handleMq);
    handleMq(mq);

    // Fechar sidebar ao clicar fora (mobile)
    document.getElementById('sidebar')?.addEventListener('transitionend', function () {
        const isOpen = this.classList.contains('open');
        document.getElementById('sidebar-overlay').style.display = isOpen ? 'block' : 'none';
    });

    (function () {
        const root = document.getElementById('simulado-topbar-notification');
        const button = document.getElementById('simulado-notification-button');
        const badge = document.getElementById('simulado-notification-badge');
        const panel = document.getElementById('simulado-notification-panel');
        const list = document.getElementById('simulado-notification-list');

        if (! root || ! button || ! badge || ! panel || ! list) {
            return;
        }

        const config = {
            enabled: @json((bool) (auth()->check() && auth()->user()->isStudent())),
            feedUrl: @json(auth()->check() ? route('simulados.notifications') : null),
            userId: @json(auth()->id()),
        };

        if (! config.enabled || ! config.feedUrl) {
            return;
        }

        const latestKey = `avalia-simulados-available-latest-${config.userId}`;
        const unseenKey = `avalia-simulados-available-unseen-${config.userId}`;
        let latestSeenId = Number(localStorage.getItem(latestKey) || 0);
        let unseenCount = Number(localStorage.getItem(unseenKey) || 0);
        let initialized = latestSeenId > 0;
        let items = [];

        const persistState = () => {
            localStorage.setItem(latestKey, String(latestSeenId));
            localStorage.setItem(unseenKey, String(unseenCount));
        };

        const updateBadge = () => {
            if (unseenCount > 0) {
                badge.textContent = unseenCount > 99 ? '99+' : String(unseenCount);
                badge.style.display = 'inline-flex';
            } else {
                badge.style.display = 'none';
            }
        };

        const renderItems = () => {
            list.innerHTML = '';

            if (items.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'topbar-panel-empty';
                empty.textContent = 'Nenhum novo simulado disponível no momento.';
                list.appendChild(empty);
                return;
            }

            items.forEach((item) => {
                const link = document.createElement('a');
                link.className = 'topbar-panel-item';
                link.href = item.url;

                const title = document.createElement('p');
                title.className = 'topbar-panel-item-title';
                title.textContent = item.name;

                const copy = document.createElement('p');
                copy.className = 'topbar-panel-item-copy';
                copy.textContent = [item.hub_name, item.weekly_label].filter(Boolean).join(' · ') || 'Novo simulado disponível';

                link.appendChild(title);
                link.appendChild(copy);
                list.appendChild(link);
            });
        };

        const markAsSeen = () => {
            unseenCount = 0;
            persistState();
            updateBadge();
        };

        const poll = async () => {
            try {
                const response = await fetch(`${config.feedUrl}?after_id=${latestSeenId}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                const fetchedItems = Array.isArray(data.items) ? data.items : [];
                const latestId = Number(data.latest_id || latestSeenId || 0);

                if (! initialized) {
                    latestSeenId = latestId;
                    unseenCount = 0;
                    initialized = true;
                    persistState();
                    updateBadge();
                    renderItems();
                    return;
                }

                if (fetchedItems.length > 0) {
                    latestSeenId = Math.max(latestSeenId, latestId);
                    unseenCount += fetchedItems.length;
                    items = [...fetchedItems, ...items].slice(0, 10);
                    persistState();
                    updateBadge();
                    renderItems();
                }
            } catch (error) {
            }
        };

        button.addEventListener('click', (event) => {
            event.preventDefault();
            panel.classList.toggle('open');
            if (panel.classList.contains('open')) {
                markAsSeen();
            }
        });

        document.addEventListener('click', (event) => {
            if (! root.contains(event.target)) {
                panel.classList.remove('open');
            }
        });

        renderItems();
        updateBadge();
        poll();
        setInterval(poll, 15000);
    })();
</script>

<div class="toast-wrap" id="toast-root">
    @foreach(collect([
        ['type' => 'success', 'msg' => session('success')],
        ['type' => 'error', 'msg' => session('error')],
        ['type' => 'warning', 'msg' => session('warning')],
        ['type' => 'info', 'msg' => session('info')],
        ['type' => 'success', 'msg' => session('status')],
        ['type' => 'info', 'msg' => session('message')],
    ])->filter(fn ($toast) => filled($toast['msg']))->values() as $index => $toast)
        <div class="toast toast-{{ $toast['type'] }}" data-toast-id="{{ $index + 1 }}">
            <span class="toast-icon">
                @switch($toast['type'])
                    @case('success')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>
                        @break
                    @case('error')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        @break
                    @case('warning')
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        @break
                    @default
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                @endswitch
            </span>
            <div class="toast-body">
                <div class="toast-title">{{ match($toast['type']) { 'success' => 'Sucesso', 'error' => 'Erro', 'warning' => 'Atenção', default => 'Informação' } }}</div>
                <div class="toast-msg">{{ $toast['msg'] }}</div>
            </div>
            <button class="toast-close" type="button" aria-label="Fechar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="toast-progress" style="width:100%"></div>
        </div>
    @endforeach
</div>

<script>
const __initAppToasts = () => {
    const root = document.getElementById('toast-root');
    if (!root) {
        console.error('Toast root element not found');
        return;
    }

    const icons = {
        success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg>',
        error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    };

    const titles = {
        success: 'Sucesso',
        error: 'Erro',
        warning: 'Atenção',
        info: 'Informação',
    };

    let sequence = root.querySelectorAll('.toast').length;

    const dismissToast = (toast, timer) => {
        if (!toast || toast.dataset.closing === '1') return;
        toast.dataset.closing = '1';
        if (timer) {
            clearInterval(timer);
        }
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(24px) scale(0.96)';
        setTimeout(() => toast.remove(), 260);
    };

    const activateToast = (toast, duration = 5000) => {
        if (!toast) return;
        toast.style.transition = 'opacity 0.22s ease, transform 0.22s ease';
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0) scale(1)';
        });
        const progressBar = toast.querySelector('.toast-progress');
        const startedAt = Date.now();
        const timer = window.setInterval(() => {
            const elapsed = Date.now() - startedAt;
            const progress = Math.max(0, 100 - (elapsed / duration) * 100);
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }
            if (elapsed >= duration) {
                dismissToast(toast, timer);
            }
        }, 100);

        const closeButton = toast.querySelector('.toast-close');
        if (closeButton) {
            closeButton.addEventListener('click', () => dismissToast(toast, timer));
        }

        return timer;
    };

    const createToast = (type, message, duration = 5000) => {
        if (!message) return;

        sequence += 1;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.dataset.toastId = String(sequence);
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(24px) scale(0.96)';
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] ?? icons.info}</span>
            <div class="toast-body">
                <div class="toast-title">${titles[type] ?? titles.info}</div>
                <div class="toast-msg"></div>
            </div>
            <button class="toast-close" type="button" aria-label="Fechar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="toast-progress" style="width:100%"></div>
        `;

        const msgElement = toast.querySelector('.toast-msg');
        if (msgElement) {
            msgElement.textContent = String(message);
        }
        root.appendChild(toast);
        activateToast(toast, duration);
    };

    root.querySelectorAll('.toast').forEach((toast) => {
        activateToast(toast);
    });

    window.appToast = createToast;
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', __initAppToasts, { once: true });
} else {
    __initAppToasts();
}
</script>

@stack('scripts')

{{-- Modal global de confirmação --}}
<div x-data="confirmModal()" @confirm-delete.window="show($event.detail)"
     x-show="open" x-cloak
     style="position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;">
    {{-- Backdrop --}}
    <div @click="cancel()"
         x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(15,23,42,0.5);"></div>
    {{-- Card centralizado com position absolute + transform --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:420px;max-width:calc(100vw - 40px);background:var(--surface-card);border:1px solid var(--surface-border);border-radius:18px;box-shadow:0 20px 50px rgba(15,23,42,0.25);overflow:hidden;">
        <div style="padding:24px 24px 0">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div style="font-size:1rem;font-weight:700;color:var(--text-primary)" x-text="title"></div>
                    <div style="font-size:0.84rem;color:var(--text-secondary);margin-top:2px" x-text="message"></div>
                </div>
            </div>
        </div>
        <div style="padding:16px 24px 20px;display:flex;justify-content:flex-end;gap:8px">
            <button type="button" class="btn btn-ghost" @click="cancel()" style="min-width:90px">Cancelar</button>
            <button type="button" class="btn btn-danger" @click="proceed()" style="min-width:90px">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                <span x-text="confirmLabel"></span>
            </button>
        </div>
    </div>
</div>

<script>
function confirmModal() {
    return {
        open: false,
        title: '',
        message: '',
        confirmLabel: 'Remover',
        formEl: null,
        show(detail) {
            this.title = detail.title || 'Confirmar exclusão';
            this.message = detail.message || 'Esta ação não pode ser desfeita.';
            this.confirmLabel = detail.confirmLabel || 'Remover';
            this.formEl = detail.form || null;
            this.open = true;
        },
        cancel() {
            this.open = false;
            this.formEl = null;
        },
        proceed() {
            this.open = false;
            if (this.formEl) {
                this.formEl._confirmBypass = true;
                this.formEl.requestSubmit();
            }
        },
    };
}

function confirmDelete(form, opts) {
    if (form._confirmBypass) {
        form._confirmBypass = false;
        return true;
    }
    window.dispatchEvent(new CustomEvent('confirm-delete', {
        detail: {
            form: form,
            title: opts.title || 'Confirmar exclusão',
            message: opts.message || 'Esta ação não pode ser desfeita.',
            confirmLabel: opts.confirmLabel || 'Remover',
        }
    }));
    return false;
}
</script>
</body>
</html>
