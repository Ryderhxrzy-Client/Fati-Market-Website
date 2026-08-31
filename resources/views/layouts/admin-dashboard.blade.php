<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · Fati Market Admin</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-token" content="{{ session('admin_token', '') }}">
    <meta name="current-route" content="{{ Route::currentRouteName() }}">

    <style>
        /* ── Tokens ─────────────────────────────────────────────────────
           One vocabulary for the whole console. Pages reach for these
           rather than inventing another shade of grey or another shadow,
           which is how the old screens drifted apart from each other.
        */
        :root {
            --brand-900: #0C3021;
            --brand-800: #10432D;
            --brand-700: #14563A;
            --brand-600: #1A6E49;
            --brand-500: #22885B;
            --brand-100: #DCEFE4;
            --brand-50:  #F0F8F3;

            --ink-900: #101513;
            --ink-800: #1F2A25;
            --ink-700: #33403A;
            --ink-600: #4B5952;
            --ink-500: #6B7A72;
            --ink-400: #94A29B;

            --surface:      #FFFFFF;
            --surface-sunk: #F6F8F7;
            --canvas:       #F2F5F3;
            --line:         #E4E9E6;
            --line-strong:  #D3DAD6;

            --success: #17794A;
            --success-bg: #DCF3E6;
            --warning: #9A6212;
            --warning-bg: #FDF0D5;
            --danger: #B3261E;
            --danger-bg: #FCE8E6;
            --info: #1D4ED8;
            --info-bg: #DEE9FE;
            /* Amber always means loyalty points, as it does in the app. */
            --reward: #A16207;
            --reward-bg: #FEF3C7;

            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;

            --shadow-sm: 0 1px 2px rgba(16, 21, 19, 0.05);
            --shadow: 0 1px 3px rgba(16, 21, 19, 0.07), 0 6px 16px -8px rgba(16, 21, 19, 0.12);
            --shadow-lg: 0 12px 32px -12px rgba(16, 21, 19, 0.28);

            --sidebar-w: 268px;
        }

        * { --tw-ring-offset-color: transparent; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--canvas);
            color: var(--ink-800);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4 { letter-spacing: -0.011em; color: var(--ink-900); }
        /* Pages carried their own font-size on every heading; these are the
           sizes they were all reaching for. */
        h3 { font-size: 16px; font-weight: 650; }
        h4 { font-size: 14px; font-weight: 650; }

        /* Numbers line up down a column instead of wobbling. */
        .tabular, .money, .stat-value { font-variant-numeric: tabular-nums; }

        :focus-visible {
            outline: 2px solid var(--brand-500);
            outline-offset: 2px;
            border-radius: 4px;
        }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-thumb {
            background: var(--line-strong);
            border-radius: 999px;
            border: 3px solid transparent;
            background-clip: content-box;
        }
        ::-webkit-scrollbar-thumb:hover { background: var(--ink-400); background-clip: content-box; }

        /* ── Shell ──────────────────────────────────────────────────── */
        .app-shell { display: flex; height: 100vh; overflow: hidden; }

        .drawer-container {
            width: var(--sidebar-w);
            flex-shrink: 0;
            background: linear-gradient(180deg, var(--brand-900) 0%, var(--brand-800) 100%);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.25s ease;
        }

        .drawer-scroll { flex: 1; overflow-y: auto; padding: 4px 12px 20px; }
        .drawer-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.18); background-clip: content-box; }

        .brand { display: flex; align-items: center; gap: 12px; padding: 20px 20px 16px; }

        .brand-mark {
            width: 40px; height: 40px;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 15px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.22);
            flex-shrink: 0;
        }

        .brand-name { color: #fff; font-weight: 600; font-size: 15px; line-height: 1.2; }
        .brand-sub { color: rgba(255,255,255,0.52); font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase; }

        .drawer-user {
            display: flex; align-items: center; gap: 10px;
            margin: 0 12px 8px;
            padding: 10px 12px;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: var(--radius);
        }
        .drawer-user .name { color: #fff; font-size: 13px; font-weight: 500; }
        .drawer-user .email { color: rgba(255,255,255,0.5); font-size: 11px; }

        .nav-overline {
            padding: 15px 12px 5px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.38);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            width: 100%;
            padding: 9px 12px;
            margin: 1px 0;
            border-radius: 9px;
            font-size: 13.5px;
            font-weight: 500;
            color: rgba(255,255,255,0.72);
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .sidebar-link:hover { background: rgba(255,255,255,0.09); color: #fff; }

        .sidebar-link.active {
            background: #fff;
            color: var(--brand-800);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-link i { width: 17px; text-align: center; font-size: 13px; opacity: 0.9; }
        .sidebar-link.active i { color: var(--brand-600); opacity: 1; }

        /* Submenus animate open on grid rows, so no fixed max-height has to
           be guessed and nothing is clipped when a section grows. */
        .submenu {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.22s ease;
            margin-left: 14px;
            border-left: 1px solid rgba(255,255,255,0.13);
            padding-left: 8px;
        }
        .submenu > div { overflow: hidden; }
        .submenu.open { grid-template-rows: 1fr; }
        .submenu.hidden { grid-template-rows: 0fr; }
        .submenu .sidebar-link { font-size: 13px; padding: 7px 11px; }

        .nav-chevron { margin-left: auto; font-size: 10px; transition: transform 0.22s ease; opacity: 0.6; }
        .sidebar-link[aria-expanded="true"] .nav-chevron { transform: rotate(180deg); }

        .nav-count {
            margin-left: auto;
            background: var(--danger);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px; height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
        }

        .drawer-foot { padding: 12px; border-top: 1px solid rgba(255,255,255,0.1); }
        .drawer-foot .sidebar-link { color: #FFC5C0; }
        .drawer-foot .sidebar-link:hover { background: rgba(255,90,80,0.16); color: #fff; }

        .scrim {
            position: fixed; inset: 0;
            background: rgba(12, 48, 33, 0.5);
            z-index: 39;
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .scrim.open { opacity: 1; pointer-events: auto; }

        @media (max-width: 1024px) {
            .drawer-container {
                position: fixed; left: 0; top: 0; bottom: 0;
                transform: translateX(-100%);
                z-index: 40;
                box-shadow: var(--shadow-lg);
            }
            .drawer-container.open { transform: translateX(0); }
        }

        /* ── Topbar ─────────────────────────────────────────────────── */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 0 24px;
            height: 64px;
            background: rgba(255,255,255,0.88);
            backdrop-filter: saturate(180%) blur(8px);
            border-bottom: 1px solid var(--line);
            flex-shrink: 0;
        }

        .topbar h2 { font-size: 19px; font-weight: 650; margin: 0; }
        .topbar .crumb { font-size: 12px; color: var(--ink-500); }

        .icon-btn {
            width: 36px; height: 36px;
            border-radius: 9px;
            border: 1px solid var(--line);
            background: var(--surface);
            color: var(--ink-600);
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: background-color 0.15s ease, color 0.15s ease;
        }
        .icon-btn:hover { background: var(--surface-sunk); color: var(--ink-900); }

        .avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 13px;
            flex-shrink: 0;
        }

        .menu-pop {
            position: absolute; right: 0; top: calc(100% + 8px);
            min-width: 214px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            padding: 6px;
            z-index: 45;
            display: none;
        }
        .menu-pop.open { display: block; }
        .menu-pop a, .menu-pop button {
            display: flex; align-items: center; gap: 10px;
            width: 100%;
            padding: 8px 10px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            color: var(--ink-800);
            text-decoration: none;
            background: none; border: none; cursor: pointer; text-align: left;
        }
        .menu-pop a:hover, .menu-pop button:hover { background: var(--surface-sunk); }
        .menu-pop i { width: 15px; color: var(--ink-500); }
        .menu-pop .danger, .menu-pop .danger i { color: var(--danger); }

        .app-main { flex: 1; overflow-y: auto; padding: 24px; }
        @media (max-width: 640px) { .app-main { padding: 16px; } .topbar { padding: 0 16px; } }

        /* ── Page furniture ─────────────────────────────────────────── */
        .fm-page-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .fm-page-head h3 { font-size: 18px; font-weight: 650; margin: 0; }
        .fm-page-head p { font-size: 13px; color: var(--ink-500); margin: 2px 0 0; }

        /* The controls that used to sit beside a page heading. The heading
           itself now lives in the topbar, so this is just the toolbar. */
        .fm-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }
        .fm-toolbar .fm-input, .fm-toolbar .fm-select { width: auto; min-width: 190px; }
        .fm-toolbar .fm-search { flex: 0 1 270px; }
        .fm-toolbar .fm-search .fm-input { width: 100%; min-width: 0; }

        .fm-section-title {
            font-size: 11px;
            font-weight: 650;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: var(--ink-500);
            margin-bottom: 10px;
        }

        /* ── Cards ──────────────────────────────────────────────────── */
        .fm-card, .stat-card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .fm-card { overflow: hidden; }

        /* A card you can click into. */
        .fm-card-hover { transition: box-shadow 0.2s ease, transform 0.2s ease; }
        .fm-card-hover:hover { box-shadow: var(--shadow); transform: translateY(-1px); }

        /* A stack of rows that are not a table - the activity log, mostly. */
        .fm-divided > * + * { border-top: 1px solid var(--line); }

        .fm-card-head {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--line);
        }
        .fm-card-head h4 { font-size: 14px; font-weight: 650; margin: 0; }
        .fm-card-body { padding: 18px; }

        .stat-card { padding: 18px; transition: box-shadow 0.2s ease, transform 0.2s ease; }
        .stat-card:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
        .stat-card .stat-value {
            font-size: 26px;
            font-weight: 680;
            color: var(--ink-900);
            margin: 6px 0 0;
            line-height: 1.15;
        }
        .stat-label { color: var(--ink-500); font-size: 12.5px; font-weight: 500; }
        .stat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        /* ── Tables ─────────────────────────────────────────────────── */
        .fm-table-wrap { overflow-x: auto; }
        .fm-table { width: 100%; border-collapse: collapse; }
        .fm-table thead th {
            position: sticky; top: 0; z-index: 1;
            background: var(--surface-sunk);
            padding: 11px 18px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--ink-500);
            border-bottom: 1px solid var(--line);
            white-space: nowrap;
        }
        .fm-table tbody td {
            padding: 13px 18px;
            border-bottom: 1px solid var(--line);
            font-size: 13.5px;
            vertical-align: middle;
            color: var(--ink-700);
        }
        .fm-table tbody tr:last-child td { border-bottom: none; }
        .fm-table tbody tr { transition: background-color 0.12s ease; }
        .fm-table tbody tr:hover { background: var(--brand-50); }
        .fm-table .num { text-align: right; font-variant-numeric: tabular-nums; }

        .cell-title { font-weight: 600; color: var(--ink-900); }
        .cell-sub { font-size: 11.5px; color: var(--ink-500); }

        .thumb {
            width: 40px; height: 40px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--surface-sunk);
            border: 1px solid var(--line);
            display: flex; align-items: center; justify-content: center;
            color: var(--ink-400);
            flex-shrink: 0;
        }

        /* ── Badges ─────────────────────────────────────────────────── */
        .fm-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 600;
            white-space: nowrap;
            background: var(--surface-sunk);
            color: var(--ink-600);
        }
        .fm-badge.success { background: var(--success-bg); color: var(--success); }
        .fm-badge.warning { background: var(--warning-bg); color: var(--warning); }
        .fm-badge.danger  { background: var(--danger-bg);  color: var(--danger); }
        .fm-badge.info    { background: var(--info-bg);    color: var(--info); }
        .fm-badge.brand   { background: var(--brand-100);  color: var(--brand-700); }
        .fm-badge.reward  { background: var(--reward-bg);  color: var(--reward); }

        .admin-badge { display: inline-block; background: var(--reward-bg); color: var(--reward); padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }

        /* ── Buttons ────────────────────────────────────────────────── */
        .fm-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 8px 15px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .fm-btn:disabled { opacity: 0.55; cursor: not-allowed; }
        .fm-btn.primary { background: var(--brand-600); color: #fff; }
        .fm-btn.primary:hover:not(:disabled) { background: var(--brand-700); }
        .fm-btn.ghost { background: var(--surface); color: var(--ink-800); border-color: var(--line-strong); }
        .fm-btn.ghost:hover:not(:disabled) { background: var(--surface-sunk); }
        .fm-btn.danger { background: var(--surface); color: var(--danger); border-color: var(--danger); }
        .fm-btn.danger:hover:not(:disabled) { background: var(--danger-bg); }
        .fm-btn.sm { padding: 6px 11px; font-size: 12px; }

        /* A row action: quiet until you reach for it. */
        .row-btn {
            width: 32px; height: 32px;
            border-radius: var(--radius-sm);
            border: 1px solid transparent;
            background: none;
            color: var(--ink-500);
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer;
            font-size: 13px;
            transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        }
        .row-btn:hover { background: var(--surface-sunk); color: var(--brand-700); border-color: var(--line); }
        .row-btn.danger:hover { background: var(--danger-bg); color: var(--danger); }

        /* ── Inputs ─────────────────────────────────────────────────── */
        .fm-input, .fm-select, .fm-textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius-sm);
            font-size: 13.5px;
            font-family: inherit;
            color: var(--ink-900);
            background: var(--surface);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .fm-input:focus, .fm-select:focus, .fm-textarea:focus {
            outline: none;
            border-color: var(--brand-500);
            box-shadow: 0 0 0 3px var(--brand-100);
        }
        .fm-input::placeholder, .fm-textarea::placeholder { color: var(--ink-400); }
        .fm-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink-700); margin-bottom: 5px; }

        .fm-search { position: relative; display: block; }
        .fm-search i {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            color: var(--ink-400); font-size: 12px; pointer-events: none;
        }
        .fm-search input { padding-left: 32px; }

        /* ── Empty & loading ────────────────────────────────────────── */
        .fm-empty { padding: 56px 24px; text-align: center; color: var(--ink-500); }
        .fm-empty > i { font-size: 30px; color: var(--line-strong); display: block; margin-bottom: 12px; }
        .fm-empty p { font-size: 14px; font-weight: 600; color: var(--ink-600); margin: 0 0 2px; }
        .fm-empty span { font-size: 12.5px; }

        .loading-spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 2px solid var(--brand-100);
            border-top-color: var(--brand-600);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Modals ─────────────────────────────────────────────────── */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(12, 48, 33, 0.42);
            backdrop-filter: blur(3px);
            display: none;
            align-items: center; justify-content: center;
            z-index: 50;
            padding: 24px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.16s ease; }

        .modal {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            max-width: 520px;
            width: 100%;
            max-height: 88vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: popIn 0.18s ease;
        }
        .modal-lg { max-width: 900px; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes popIn { from { opacity: 0; transform: translateY(8px) scale(0.985); } to { opacity: 1; transform: none; } }

        /* ── Toasts ─────────────────────────────────────────────────── */
        #toastContainer {
            position: fixed; bottom: 20px; right: 20px;
            z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
            align-items: flex-end;
        }

        .toast {
            display: flex; align-items: center; gap: 11px;
            padding: 12px 16px;
            min-width: 280px; max-width: 380px;
            border-radius: var(--radius);
            background: var(--surface);
            border: 1px solid var(--line);
            box-shadow: var(--shadow-lg);
            color: var(--ink-800);
            font-size: 13.5px;
            animation: slideIn 0.22s ease;
        }
        .toast i { font-size: 15px; flex-shrink: 0; }
        .toast.success { border-left: 3px solid var(--success); }
        .toast.success i { color: var(--success); }
        .toast.error { border-left: 3px solid var(--danger); }
        .toast.error i { color: var(--danger); }
        .toast.info { border-left: 3px solid var(--brand-600); }
        .toast.info i { color: var(--brand-600); }
        .toast.leaving { animation: slideOut 0.22s ease forwards; }

        @keyframes slideIn { from { transform: translateX(24px); opacity: 0; } to { transform: none; opacity: 1; } }
        /* The old stylesheet animated to `slideOut` without ever defining it,
           so toasts blinked out instead of leaving. */
        @keyframes slideOut { to { transform: translateX(24px); opacity: 0; } }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $profilePic = session('admin_profile_picture');
        $firstName  = session('admin_first_name', 'Admin');
        $lastName   = session('admin_last_name', 'User');
        $email      = session('admin_data.email', 'admin@fatimarket.com');
        $initial    = strtoupper(substr($firstName ?: 'A', 0, 1));
    @endphp

    <div class="app-shell">
        <div class="scrim" id="drawerScrim" onclick="toggleDrawer(false)"></div>

        <!-- ── Sidebar ──────────────────────────────────────────────── -->
        <aside class="drawer-container" id="drawer">
            <div class="brand">
                <div class="brand-mark"><i class="fas fa-store"></i></div>
                <div class="min-w-0">
                    <div class="brand-name">Fati Market</div>
                    <div class="brand-sub">Admin</div>
                </div>
            </div>

            <div class="drawer-user">
                @if($profilePic)
                    <img src="{{ $profilePic }}" alt="" class="avatar" style="width: 32px; height: 32px;">
                @else
                    <div class="avatar" style="width: 32px; height: 32px;">{{ $initial }}</div>
                @endif
                <div class="min-w-0">
                    <div class="name truncate">{{ $firstName }} {{ $lastName }}</div>
                    <div class="email truncate">{{ $email }}</div>
                </div>
            </div>

            <nav class="drawer-scroll">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link" data-route="admin.dashboard">
                    <i class="fas fa-chart-line"></i><span>Dashboard</span>
                </a>

                <div class="nav-overline">Inventory</div>

                <button class="sidebar-link" aria-expanded="false" onclick="toggleMenu(this)">
                    <i class="fas fa-box"></i><span>Items</span>
                    <i class="fas fa-chevron-down nav-chevron"></i>
                </button>
                <div class="submenu"><div>
                    <a href="{{ route('admin.private-offers') }}" class="sidebar-link" data-route="admin.private-offers">
                        <i class="fas fa-inbox"></i><span>Offers to review</span>
                    </a>
                    <a href="{{ route('admin.acquired-items') }}" class="sidebar-link" data-route="admin.acquired-items">
                        <i class="fas fa-warehouse"></i><span>Acquired</span>
                    </a>
                    <a href="{{ route('admin.public-listings') }}" class="sidebar-link" data-route="admin.public-listings">
                        <i class="fas fa-globe"></i><span>Published</span>
                    </a>
                    <a href="{{ route('admin.reserved-items') }}" class="sidebar-link" data-route="admin.reserved-items">
                        <i class="fas fa-clock"></i><span>Reserved</span>
                    </a>
                    <a href="{{ route('admin.sold-items') }}" class="sidebar-link" data-route="admin.sold-items">
                        <i class="fas fa-circle-check"></i><span>Sold</span>
                    </a>
                </div></div>

                <button class="sidebar-link" aria-expanded="false" onclick="toggleMenu(this)">
                    <i class="fas fa-right-left"></i><span>Transactions</span>
                    <i class="fas fa-chevron-down nav-chevron"></i>
                </button>
                <div class="submenu"><div>
                    <a href="{{ route('admin.transactions.history') }}" class="sidebar-link" data-route="admin.transactions.history">
                        <i class="fas fa-receipt"></i><span>All orders</span>
                    </a>
                    <a href="{{ route('admin.transactions.cash') }}" class="sidebar-link" data-route="admin.transactions.cash">
                        <i class="fas fa-money-bill-wave"></i><span>Cash</span>
                    </a>
                    <a href="{{ route('admin.transactions.trade') }}" class="sidebar-link" data-route="admin.transactions.trade">
                        <i class="fas fa-right-left"></i><span>Trade</span>
                    </a>
                    <a href="{{ route('admin.transactions.points-given') }}" class="sidebar-link" data-route="admin.transactions.points-given">
                        <i class="fas fa-arrow-up"></i><span>Points given</span>
                    </a>
                    <a href="{{ route('admin.transactions.points-received') }}" class="sidebar-link" data-route="admin.transactions.points-received">
                        <i class="fas fa-arrow-down"></i><span>Points received</span>
                    </a>
                    <a href="{{ route('admin.transactions.profit') }}" class="sidebar-link" data-route="admin.transactions.profit">
                        <i class="fas fa-sack-dollar"></i><span>Profit summary</span>
                    </a>
                </div></div>

                <div class="nav-overline">Insight</div>

                <button class="sidebar-link" aria-expanded="false" onclick="toggleMenu(this)">
                    <i class="fas fa-chart-bar"></i><span>Reports</span>
                    <i class="fas fa-chevron-down nav-chevron"></i>
                </button>
                <div class="submenu"><div>
                    <a href="{{ route('admin.reports.items-acquired') }}" class="sidebar-link" data-route="admin.reports.items-acquired">
                        <i class="fas fa-shopping-bag"></i><span>Items acquired</span>
                    </a>
                    <a href="{{ route('admin.reports.items-sold') }}" class="sidebar-link" data-route="admin.reports.items-sold">
                        <i class="fas fa-boxes-stacked"></i><span>Items sold</span>
                    </a>
                    <a href="{{ route('admin.reports.profit') }}" class="sidebar-link" data-route="admin.reports.profit">
                        <i class="fas fa-coins"></i><span>Profit from markup</span>
                    </a>
                    <a href="{{ route('admin.reports.categories') }}" class="sidebar-link" data-route="admin.reports.categories">
                        <i class="fas fa-list"></i><span>Top categories</span>
                    </a>
                    <a href="{{ route('admin.reports.users') }}" class="sidebar-link" data-route="admin.reports.users">
                        <i class="fas fa-user-check"></i><span>Active users</span>
                    </a>
                </div></div>

                <a href="{{ route('admin.activity') }}" class="sidebar-link" data-route="admin.activity">
                    <i class="fas fa-clock-rotate-left"></i><span>Activity log</span>
                </a>

                <div class="nav-overline">Manage</div>

                <a href="{{ route('admin.conversations') }}" class="sidebar-link" data-route="admin.conversations">
                    <i class="fas fa-comments"></i><span>Chat</span>
                    <span class="nav-count" id="unreadCount" style="display: none;">0</span>
                </a>
                <a href="{{ route('admin.students') }}" class="sidebar-link" data-route="admin.students">
                    <i class="fas fa-users"></i><span>Students</span>
                </a>
                <a href="{{ route('admin.categories') }}" class="sidebar-link" data-route="admin.categories">
                    <i class="fas fa-tags"></i><span>Categories</span>
                </a>
                <a href="{{ route('admin.settings') }}" class="sidebar-link" data-route="admin.settings">
                    <i class="fas fa-gear"></i><span>Settings</span>
                </a>
            </nav>

            <div class="drawer-foot">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link">
                        <i class="fas fa-arrow-right-from-bracket"></i><span>Sign out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- ── Main ─────────────────────────────────────────────────── -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="topbar">
                <div class="flex items-center gap-3 min-w-0">
                    <button class="icon-btn lg:hidden" onclick="toggleDrawer()" aria-label="Open menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="min-w-0">
                        <h2 class="truncate">@yield('title', 'Dashboard')</h2>
                        <div class="crumb truncate">@yield('subtitle', 'Fati Market administration')</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @yield('actions')

                    <div class="relative">
                        <button onclick="toggleProfileMenu(event)" aria-label="Account">
                            @if($profilePic)
                                <img src="{{ $profilePic }}" alt="" class="avatar">
                            @else
                                <div class="avatar">{{ $initial }}</div>
                            @endif
                        </button>

                        <div class="menu-pop" id="profileMenu">
                            <div style="padding: 8px 10px 10px; border-bottom: 1px solid var(--line); margin-bottom: 4px;">
                                <div style="font-size: 13px; font-weight: 600;">{{ $firstName }} {{ $lastName }}</div>
                                <div style="font-size: 11.5px; color: var(--ink-500);">{{ $email }}</div>
                            </div>
                            <a href="{{ route('admin.profile') }}"><i class="fas fa-user"></i>Profile</a>
                            <a href="{{ route('admin.settings') }}"><i class="fas fa-gear"></i>Settings</a>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="danger"><i class="fas fa-arrow-right-from-bracket"></i>Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-main">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="toastContainer"></div>

    <script>
        function toggleDrawer(force) {
            const drawer = document.getElementById('drawer');
            const scrim = document.getElementById('drawerScrim');
            const open = force === undefined ? !drawer.classList.contains('open') : force;

            drawer.classList.toggle('open', open);
            scrim.classList.toggle('open', open);
        }

        function toggleMenu(button) {
            const submenu = button.nextElementSibling;
            const open = !submenu.classList.contains('open');

            submenu.classList.toggle('open', open);
            submenu.classList.toggle('hidden', !open);
            button.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function toggleProfileMenu(event) {
            event.stopPropagation();
            document.getElementById('profileMenu').classList.toggle('open');
        }

        document.addEventListener('click', () => {
            document.getElementById('profileMenu')?.classList.remove('open');
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            document.getElementById('profileMenu')?.classList.remove('open');
            toggleDrawer(false);
        });

        /**
         * A peso amount, from the decimal strings the API sends.
         *
         * Money is never parsed into arithmetic here - the backend owns every
         * calculation and this only renders what it was handed.
         */
        function fmPeso(amount) {
            if (amount === null || amount === undefined || amount === '') return '—';

            const value = Number(amount);
            if (!isFinite(value)) return '—';

            return '₱' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        /**
         * A transient message. Toasts stack instead of covering each other,
         * and the text is set as text so a message quoting an item title
         * cannot inject markup.
         */
        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const icon = type === 'success' ? 'circle-check'
                : type === 'error' ? 'circle-exclamation'
                : 'circle-info';

            toast.className = `toast ${type}`;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
            toast.innerHTML = `<i class="fas fa-${icon}"></i><span></span>`;
            toast.querySelector('span').textContent = message;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('leaving');
                setTimeout(() => toast.remove(), 240);
            }, 3400);
        }

        document.addEventListener('DOMContentLoaded', function () {
            const currentRoute = document.querySelector('meta[name="current-route"]')?.getAttribute('content') || '';

            // Longest match wins, so "admin.transactions.cash" does not also
            // light up the "admin.transactions" entry.
            let best = null;

            document.querySelectorAll('.sidebar-link[data-route]').forEach(link => {
                const route = link.getAttribute('data-route');

                if (currentRoute === route || currentRoute.startsWith(route + '.')) {
                    if (!best || route.length > best.getAttribute('data-route').length) best = link;
                }
            });

            if (best) {
                best.classList.add('active');

                const submenu = best.closest('.submenu');

                if (submenu) {
                    submenu.classList.add('open');
                    submenu.classList.remove('hidden');
                    submenu.previousElementSibling?.setAttribute('aria-expanded', 'true');
                }
            }

            if (window.innerWidth < 1024) {
                document.querySelectorAll('.drawer-scroll a').forEach(link => {
                    link.addEventListener('click', () => toggleDrawer(false));
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
