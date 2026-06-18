<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSLE Mark Entry Portal</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --tz-green: #1eb53a;
            --tz-yellow: #fcd116;
            --tz-blue: #00a3dd;
            --tz-black: #000000;
            --tz-bg: #0f1117;
            --tz-card: #101518;
            --tz-card-soft: #161b22;
            --tz-border: rgba(255,255,255,0.08);
            --tz-text: #f0f4f7;
            --tz-text-muted: #9ca3af;
            --tz-gold: #bba45e;
            --tz-gold-soft: rgba(187,164,94,.16);
        }

        html, body { margin: 0; padding: 0; }
        *, *::before, *::after { box-sizing: border-box; }
        body {
            width: 100%;
            min-height: 100vh;
            background: var(--tz-bg);
            color: var(--tz-text);
            overflow-x: hidden;
            font-family: 'Maiandra GD', 'Segoe UI', sans-serif;
        }
        
        .adm-shell {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(180deg,#0d1b2a,#11202e);
        }
        
        /* Sidebar */
        .adm-sidebar {
            width: 280px;
            background: linear-gradient(180deg,#0d1b2a,#11202e);
            border-right: 1px solid rgba(187,164,94,.18);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: 16px 0 40px rgba(0,0,0,.22);
        }
        .adm-sidebar-head {
            padding: 24px;
            border-bottom: 1px solid rgba(187,164,94,.15);
            background: linear-gradient(135deg,rgba(187,164,94,.08),transparent);
        }
        .adm-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .adm-brand-logo { width: 40px !important; height: 40px !important; max-width: 40px !important; max-height: 40px !important; object-fit: contain; flex-shrink: 0; }
        .adm-brand-text { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -0.5px; line-height: 1.1; }
        .adm-brand-text span { color: var(--tz-yellow); font-size: 0.65rem; display: block; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }

        .adm-nav { padding: 12px 12px; flex: 1; overflow-y: auto; }
        .adm-nav::-webkit-scrollbar { width: 3px; }
        .adm-nav::-webkit-scrollbar-track { background: transparent; }
        .adm-nav::-webkit-scrollbar-thumb { background: rgba(187,164,94,0.2); border-radius: 2px; }
        .nav-section-label { padding: 10px 16px 4px; font-size: 0.58rem; color: rgba(255,255,255,0.28); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800; }
        .adm-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: rgba(255,255,255,.65);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .adm-nav-item:hover {
            background: rgba(187,164,94,.12);
            color: #f0e6c8;
            transform: translateX(2px);
        }
        .adm-nav-item.active {
            background: rgba(187,164,94,.18);
            color: #f0e6c8;
            box-shadow: inset 0 0 0 1px rgba(187,164,94,.08);
        }
        .adm-nav-item i { width: 20px; font-size: 1rem; }
        .nav-ico{
            width:30px;
            height:30px;
            border-radius:8px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:.8rem;
            background:rgba(255,255,255,.06);
            flex-shrink:0;
        }
        .adm-nav-item:hover .nav-ico,
        .adm-nav-item.active .nav-ico {
            background: rgba(187,164,94,.2);
            color: var(--tz-gold);
        }

        /* Main Content */
        .adm-main {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #0f1117;
            border-left: 1px solid rgba(187,164,94,.18);
        }
        
        .adm-topbar {
            min-height: 76px;
            background: rgba(15,17,23,.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(187,164,94,.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            position: sticky;
            top: 0;
            z-index: 90;
            gap: 12px;
            flex-wrap: wrap;
        }
        .adm-top-title { font-size: 1.18rem; font-weight: 700; color: #f0e6c8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 60%; }
        .adm-top-user { display: flex; align-items: center; gap: 12px; }
        .adm-user-info { text-align: right; }
        .adm-user-name { font-size: 0.85rem; font-weight: 700; color: #fff; display: block; }
        .adm-user-role { font-size: 0.7rem; color: var(--tz-yellow); text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        .adm-user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--tz-green), #0f7a1e); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; font-size: 0.9rem; }

        .adm-content { padding: 40px; max-width: 1600px; margin: 0 auto; width: 100%; box-sizing: border-box; }

        /* Breadcrumb */
        .adm-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--tz-text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; gap: 8px; align-items: center; }
        .adm-breadcrumb span { color: var(--tz-yellow); }

        /* Page Header */
        .adm-page-header { margin-bottom: 30px; }
        .adm-page-title { font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 8px; letter-spacing: -0.5px; }
        .adm-page-desc { font-size: 0.95rem; color: var(--tz-text-muted); max-width: 800px; line-height: 1.6; }

        /* Stats Cards */
        .adm-stats { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .adm-stat {
            background: linear-gradient(160deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .adm-stat::after {
            content:'';
            position:absolute;
            right:-16px;
            bottom:-16px;
            width:66px;
            height:66px;
            border-radius:50%;
            background:rgba(255,255,255,.04);
        }
        .adm-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0,0,0,.5);
            border-color: rgba(187,164,94,.18);
        }
        .adm-stat-label { font-size: 0.68rem; color: rgba(255,255,255,.72); text-transform: uppercase; font-weight: 700; letter-spacing: .08em; margin-bottom: 8px; }
        .adm-stat-value { font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 6px; letter-spacing: -1px; }
        .adm-stat-icon { position: absolute; top: 18px; right: 18px; font-size: 1.35rem; color: rgba(255,255,255,0.14); }

        .adm-stats .adm-stat:nth-child(4n+1){background:linear-gradient(135deg,#111416,#161b1f);border-color:rgba(0,163,221,.15);}
        .adm-stats .adm-stat:nth-child(4n+2){background:linear-gradient(135deg,#003d52,#004f6b);}
        .adm-stats .adm-stat:nth-child(4n+3){background:linear-gradient(135deg,#0a3012,#0e3d17);}
        .adm-stats .adm-stat:nth-child(4n+4){background:linear-gradient(135deg,#3a2e00,#453600);}

        .adm-card {
            background: var(--tz-card);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }
        .adm-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,.4);
            border-color: rgba(187,164,94,.16);
        }
        .adm-card-head { padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
        .adm-card-title { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .adm-card-body { padding: 0; }

        /* Filters */
        .adm-filters { display: flex; flex-wrap: wrap; gap: 12px; padding: 20px; background: rgba(255,255,255,.02); border-bottom: 1px solid var(--tz-border); }
        .adm-filter-group { flex: 1; min-width: 150px; }
        .adm-filter-label { display: block; font-size: 0.7rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px; }
        .adm-select {
            width: 100%; height: 40px; padding: 0 12px;
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px; color: #fff; font-family: inherit; font-size: 0.85rem;
            outline: none; transition: border-color .2s, box-shadow .2s;
            appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 16px;
        }
        .adm-select:focus { border-color: var(--tz-blue); box-shadow: 0 0 0 3px rgba(0,163,221,.15); }
        .adm-select option { background: var(--tz-bg); color: #fff; }
        .adm-select:disabled { opacity: 0.6; cursor: not-allowed; }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 16px 20px; font-size: 0.72rem; color: var(--tz-text-muted); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,.08); font-weight: 700; white-space: nowrap; }
        td { padding: 16px 20px; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--tz-text); transition: background .15s ease; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(187,164,94,.05); }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
        .badge-blue { background: rgba(0,163,221,0.15); color: #67d8ff; border: 1px solid rgba(0,163,221,0.25); }
        .badge-green { background: rgba(30,181,58,0.15); color: #6ae086; border: 1px solid rgba(30,181,58,0.25); }
        .badge-red { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }
        .badge-yellow { background: rgba(252,209,22,0.15); color: #fde047; border: 1px solid rgba(252,209,22,0.25); }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 8px 16px; border-radius: 8px; font-family: inherit; font-size: 0.85rem; font-weight: 700;
            cursor: pointer; transition: all 0.2s ease; border: none; text-decoration: none; white-space: nowrap;
        }
        .btn-primary { background: linear-gradient(135deg, var(--tz-blue), #0077a3); color: #fff; box-shadow: 0 4px 12px rgba(0,163,221,.3); }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0,163,221,.4); }
        .btn-success { background: linear-gradient(135deg, var(--tz-green), #148028); color: #fff; box-shadow: 0 4px 12px rgba(30,181,58,.3); }
        .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(30,181,58,.4); }
        .btn-outline { background: rgba(255,255,255,.04); color: var(--tz-text); border: 1px solid rgba(255,255,255,.1); }
        .btn-outline:hover { background: rgba(255,255,255,.08); }
        .btn-action { background: rgba(0,163,221,0.1); color: var(--tz-blue); padding: 6px 12px; font-size: 0.8rem; }
        .btn-action:hover { background: rgba(0,163,221,0.2); }
        .btn:disabled, .btn.disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; pointer-events: none; }

        /* Quick Actions Grid */
        .qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 20px; }
        .qa-item {
            background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);
            border-radius: 12px; padding: 20px; text-align: center; color: var(--tz-text);
            text-decoration: none; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 12px;
        }
        .qa-item i { font-size: 1.8rem; color: var(--tz-yellow); }
        .qa-item:hover:not(.disabled) { background: rgba(187,164,94,.08); border-color: rgba(187,164,94,.2); transform: translateY(-2px); }
        .qa-item.disabled { opacity: 0.4; cursor: not-allowed; }
        .qa-item-title { font-weight: 700; font-size: 0.95rem; }
        .qa-item-badge { font-size: 0.65rem; background: rgba(255,255,255,.1); padding: 2px 6px; border-radius: 4px; margin-top: auto; }

        /* Empty State */
        .empty-state { padding: 60px 20px; text-align: center; color: var(--tz-text-muted); }
        .empty-icon { font-size: 3rem; color: rgba(255,255,255,.06); margin-bottom: 15px; }
        .empty-title { font-size: 1.2rem; font-weight: 700; color: var(--tz-text); margin-bottom: 8px; }
        .empty-desc { font-size: 0.9rem; max-width: 400px; margin: 0 auto; }

        .btn-logout { margin-top: auto; padding: 20px; border-top: 1px solid var(--tz-border); }

        /* Responsiveness */
        @media (max-width: 1024px) {
            .adm-sidebar { width: 80px; padding: 0; }
            .adm-brand-text, .adm-user-info, .adm-nav-item .nav-label { display: none; }
            .adm-nav-item { justify-content: center; padding: 12px; }
            .adm-main { margin-left: 80px; }
            .adm-stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .adm-sidebar { display: block; left: -280px; transition: left 0.3s ease; }
            .adm-sidebar.open { left: 0; }
            .adm-main { margin-left: 0; }
            .adm-topbar { padding: 0 20px; }
            #sidebarToggle { display: block !important; }
            .adm-stats { grid-template-columns: 1fr; }
            .adm-content { padding: 20px; }
            .qa-grid { grid-template-columns: 1fr 1fr; }
        }

        /* Simulator Styles */
        .role-simulator {
            background: rgba(187,164,94,0.15);
            border: 1px solid rgba(187,164,94,0.3);
            border-radius: 8px;
            padding: 4px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: 15px;
        }
        .role-simulator label {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--tz-yellow);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sim-select {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            outline: none;
        }
        .sim-select option {
            background: #1a1a1a;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="adm-shell">
    <!-- Sidebar -->
    <aside class="adm-sidebar">
        <div class="adm-sidebar-head">
            <a href="{{ url('/') }}" class="adm-brand">
                <div class="adm-brand-text">
                    TASIDO 2026
                    <span>PSLE Mark Entry</span>
                </div>
            </a>
        </div>
        
        <nav class="adm-nav">
            {{-- ── Back to Admin Centre (Admin only) ── --}}
            @if($isTrulyAdmin)
            <a href="{{ url('/admin/dashboard') }}" class="adm-nav-item" style="color: var(--tz-yellow); border: 1px solid rgba(252,209,22,0.2); background: rgba(252,209,22,0.05); margin-bottom: 15px;">
                <span class="nav-ico"><i class="fas fa-arrow-left"></i></span><span class="nav-label">Admin Centre</span>
            </a>
            @endif

            {{-- ── COMMON: All roles see Dashboard ── --}}
            <a href="{{ url('/mark-entry/psle?view=overview') }}" class="adm-nav-item {{ $currentView === 'overview' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-chart-pie"></i></span><span class="nav-label">Dashboard</span>
            </a>

            {{-- ── MARK ENTRY OFFICER MENU ── --}}
            @if($isMarkOfficer && !$isAdmin)

                {{-- Officers: entry workflow + validation --}}
                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Mark Entry</div>
                <a href="{{ url('/mark-entry/psle?view=start-entry') }}" class="adm-nav-item {{ $currentView === 'start-entry' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-pen-to-square"></i></span><span class="nav-label">Start Entry</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=bulk-import') }}" class="adm-nav-item {{ $currentView === 'bulk-import' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-file-import"></i></span><span class="nav-label">Bulk Import</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=entry-validation') }}" class="adm-nav-item {{ $currentView === 'entry-validation' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-table-list"></i></span><span class="nav-label">Entry Status</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Validation</div>
                <a href="{{ url('/mark-entry/psle?view=missing-marks') }}" class="adm-nav-item {{ $currentView === 'missing-marks' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-circle-exclamation"></i></span><span class="nav-label">Missing Marks</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=validation-errors') }}" class="adm-nav-item {{ $currentView === 'validation-errors' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-triangle-exclamation"></i></span><span class="nav-label">Validation Errors</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=outliers') }}" class="adm-nav-item {{ $currentView === 'outliers' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-chart-line"></i></span><span class="nav-label">Outliers</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Reports</div>
                <a href="{{ url('/mark-entry/psle?view=reports-exports') }}" class="adm-nav-item {{ $currentView === 'reports-exports' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-file-alt"></i></span><span class="nav-label">Reports & Exports</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=monitoring-audit') }}" class="adm-nav-item {{ $currentView === 'monitoring-audit' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-clock"></i></span><span class="nav-label">My Activity</span>
                </a>

            {{-- ── REO MENU ── --}}
            @elseif($isReo && !$isAdmin)

                {{-- REO: region-wide visibility, moderation, submission --}}
                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Region Management</div>
                <a href="{{ url('/mark-entry/psle?view=entry-validation') }}" class="adm-nav-item {{ $currentView === 'entry-validation' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-edit"></i></span><span class="nav-label">Entry & Validation</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=missing-marks') }}" class="adm-nav-item {{ $currentView === 'missing-marks' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-circle-exclamation"></i></span><span class="nav-label">Missing Marks</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=validation-errors') }}" class="adm-nav-item {{ $currentView === 'validation-errors' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-triangle-exclamation"></i></span><span class="nav-label">Validation Errors</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=outliers') }}" class="adm-nav-item {{ $currentView === 'outliers' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-chart-line"></i></span><span class="nav-label">Outliers / Extremity</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Administration</div>
                <a href="{{ url('/mark-entry/psle?view=moderation-review') }}" class="adm-nav-item {{ $currentView === 'moderation-review' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-search"></i></span><span class="nav-label">Moderation & Review</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=submission-locking') }}" class="adm-nav-item {{ $currentView === 'submission-locking' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-lock"></i></span><span class="nav-label">Submission & Locking</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=assignments') }}" class="adm-nav-item {{ $currentView === 'assignments' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-tag"></i></span><span class="nav-label">Manage Assignments</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Reports</div>
                <a href="{{ url('/mark-entry/psle?view=reports-exports') }}" class="adm-nav-item {{ $currentView === 'reports-exports' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-file-alt"></i></span><span class="nav-label">Reports & Exports</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=monitoring-audit') }}" class="adm-nav-item {{ $currentView === 'monitoring-audit' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-clock"></i></span><span class="nav-label">Monitoring & Audit</span>
                </a>

            {{-- ── ADMIN MENU ── --}}
            @else

                {{-- Admin: full access with section grouping --}}
                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Mark Entry</div>
                <a href="{{ url('/mark-entry/psle?view=entry-validation') }}" class="adm-nav-item {{ $currentView === 'entry-validation' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-edit"></i></span><span class="nav-label">Entry & Validation</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=missing-marks') }}" class="adm-nav-item {{ $currentView === 'missing-marks' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-circle-exclamation"></i></span><span class="nav-label">Missing Marks</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=validation-errors') }}" class="adm-nav-item {{ $currentView === 'validation-errors' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-triangle-exclamation"></i></span><span class="nav-label">Validation Errors</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=outliers') }}" class="adm-nav-item {{ $currentView === 'outliers' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-chart-line"></i></span><span class="nav-label">Outliers / Extremity</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Administration</div>
                <a href="{{ url('/mark-entry/psle?view=moderation-review') }}" class="adm-nav-item {{ $currentView === 'moderation-review' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-search"></i></span><span class="nav-label">Moderation & Review</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=submission-locking') }}" class="adm-nav-item {{ $currentView === 'submission-locking' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-lock"></i></span><span class="nav-label">Submission & Locking</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=assignments') }}" class="adm-nav-item {{ $currentView === 'assignments' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-tag"></i></span><span class="nav-label">Manage Assignments</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=subject-panel-assignments') }}" class="adm-nav-item {{ $currentView === 'subject-panel-assignments' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-user-shield"></i></span><span class="nav-label">Subject Panel Assignments</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=user-management') }}" class="adm-nav-item {{ $currentView === 'user-management' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-users-cog"></i></span><span class="nav-label">User Management</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=marking-centres') }}" class="adm-nav-item {{ $currentView === 'marking-centres' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-building"></i></span><span class="nav-label">Marking Centres</span>
                </a>

                <div style="padding: 10px 16px 4px; font-size: 0.6rem; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; font-weight: 800;">Reports</div>
                <a href="{{ url('/mark-entry/psle?view=reports-exports') }}" class="adm-nav-item {{ $currentView === 'reports-exports' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-file-alt"></i></span><span class="nav-label">Reports & Exports</span>
                </a>
                <a href="{{ url('/mark-entry/psle?view=monitoring-audit') }}" class="adm-nav-item {{ $currentView === 'monitoring-audit' ? 'active' : '' }}">
                    <span class="nav-ico"><i class="fas fa-clock"></i></span><span class="nav-label">Monitoring & Audit</span>
                </a>

            @endif
        </nav>

        <div class="btn-logout">
            <form action="{{ route('logout') ?? '#' }}" method="POST">
                @csrf
                <button type="submit" class="adm-nav-item" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left; font-family: inherit;">
                    <span class="nav-ico"><i class="fas fa-sign-out-alt"></i></span><span class="nav-label">Exit Portal</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="adm-main">
        <header class="adm-topbar">
            <button id="sidebarToggle" class="mobile-toggle" style="display:none; background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer; margin-right:15px;">
                <i class="fas fa-bars"></i>
            </button>
            <div class="adm-top-title">
                PSLE Mark Entry Operations
            </div>
            <div class="adm-top-user">
                @if($isTrulyAdmin)
                <div class="role-simulator">
                    <label><i class="fas fa-vial"></i> Testing:</label>
                    <select class="sim-select" onchange="window.location.href='{{ url('/mark-entry/psle') }}?simulate_role=' + this.value">
                        <option value="admin" {{ ($simulatedRole ?? 'admin') == 'admin' ? 'selected' : '' }}>System Admin</option>
                        <option value="reo" {{ ($simulatedRole ?? '') == 'reo' ? 'selected' : '' }}>Regional Officer (REO)</option>
                        <option value="mark_officer" {{ ($simulatedRole ?? '') == 'mark_officer' ? 'selected' : '' }}>Mark Entry Officer</option>
                    </select>
                </div>
                @endif
                <div class="adm-user-info">
                    <span class="adm-user-name">{{ $user->name ?? 'Officer' }}</span>
                    <span class="adm-user-role">{{ $simulatedRole ? strtoupper(str_replace('_', ' ', $simulatedRole)) : ($user->portal_role ?? 'MARK ENTRY') }}</span>
                </div>
                <div class="adm-user-avatar">
                    {{ substr($user->name ?? 'O', 0, 1) }}
                </div>
            </div>
        </header>

        <div class="adm-content">
            @if(isset($hasNoAssignments) && $hasNoAssignments)
                <div class="adm-card">
                    <div class="adm-card-body">
                        <div class="empty-state">
                            <i class="fas fa-lock empty-icon" style="color: #ffb74d;"></i>
                            <div class="empty-title">Access Restricted</div>
                            <div class="empty-desc">No PSLE mark entry assignment has been assigned to you yet. Please contact your Regional Education Officer for an assignment.</div>
                        </div>
                    </div>
                </div>
            @else
                @include('mark-entry.psle.partials.' . $currentView)
            @endif
        </div>
    </main>
</div>

@php
    $pageManuals = [

        'overview' => [
            'manualTitle'   => 'Dashboard — User Guide',
            'manualSummary' => 'The Dashboard gives you a real-time snapshot of PSLE mark-entry activity within your permitted scope. Use the summary cards and subject-status table to identify where action is needed.',
            'manualSteps'   => [
                ['title' => 'Reading the Summary Cards',        'body' => 'Cards at the top show TASIDO Regions, Registered Candidates, PSLE Subjects, Marks Entered, Missing Marks, and Outliers. For REOs and Officers, all counts are automatically restricted to your assigned region.'],
                ['title' => 'Using Scope Filters',              'body' => 'Use the Exam Year, Region, District, School, and Subject dropdowns to narrow the view. Selecting a district auto-populates the Schools list. Filters persist across page loads via the URL query string.'],
                ['title' => 'Subject Entry Status Table',       'body' => 'The table shows every school–subject combination in your scope with Registered, Entered, Missing, ABS, and Invalid counts plus a colour-coded status badge (Not Started / In Progress / Has Errors / Ready for Review).'],
                ['title' => 'Quick Actions',                    'body' => 'Use the Quick Actions grid to jump directly to Start Entry, Bulk Import, Missing Marks, or Reports without navigating the sidebar.'],
                ['title' => 'Data Freshness',                   'body' => 'The dashboard queries live data on every page load. Reload the page after any import or correction to see updated counts.'],
            ],
            'manualNotes'   => [
                '<strong>Tip:</strong> Click a school row in the subject table to jump straight to its entry sheet.',
                'Mark Entry Officers only see data for their assigned region — no cross-region data is ever shown.',
            ],
        ],

        'entry-validation' => [
            'manualTitle'   => 'Entry & Validation — User Guide',
            'manualSummary' => 'The Entry & Validation workspace shows the overall mark-entry progress per school and subject, highlights data quality issues, and lets you drill into specific records that need attention.',
            'manualSteps'   => [
                ['title' => 'Understanding the Progress Table',  'body' => 'Each row represents a School × Subject pair. Registered is the count from candidate registrations. Entered is the count of RawMark records. Missing = Registered − Entered. Invalid counts open validation errors for that pair.'],
                ['title' => 'Filtering the Data',               'body' => 'Apply Exam Year, District, School, and Subject filters to focus on a specific scope. The counts recalculate automatically. Officers are locked to their assigned region.'],
                ['title' => 'Status Badges',                    'body' => 'Not Started — no marks entered yet. In Progress — some marks entered but candidates still missing. Has Errors — open validation errors exist. Ready for Review — all registered candidates have marks and no open errors.'],
                ['title' => 'ABS (Absent) Records',             'body' => 'Candidates marked ABS in the imported CSV count as "entered" but are flagged separately. They do not count as missing but should be reviewed for accuracy.'],
                ['title' => 'Next Steps',                       'body' => 'From this page, use the sidebar to navigate to Missing Marks or Validation Errors for targeted correction. Once all rows show "Ready for Review", the batch can be submitted.'],
            ],
            'manualNotes'   => [
                '<strong>Important:</strong> Marks can only be corrected before the batch is Locked. After locking, contact an Administrator.',
                'The Pagination controls at the bottom let you browse all schools in your scope.',
            ],
        ],

        'start-entry' => [
            'manualTitle'   => 'Start Entry — User Guide',
            'manualSummary' => 'Start Entry allows a Mark Entry Officer to begin entering marks for a specific school and subject by uploading a single CSV file.',
            'manualSteps'   => [
                ['title' => 'Select Assignment',    'body' => 'Choose your Assignment from the dropdown, or manually select an Exam Year, School, and Subject if no assignment exists. Officers are restricted to schools within their assigned region.'],
                ['title' => 'Download the Template','body' => 'Click "Download CSV Template" to get a blank scoresheet pre-filled with the correct headers and registered candidate index numbers for the selected school–subject.'],
                ['title' => 'Fill in the CSV',      'body' => 'Open the template in Excel or LibreOffice. Enter marks in the correct columns (Paper 1, Paper 2, Total, Subject Status). Do not alter column headers or index numbers.'],
                ['title' => 'Upload and Validate',  'body' => 'Click "Upload CSV", select your filled file, and the system validates every row. Errors are displayed inline with row numbers. Correct them in the file and re-upload.'],
                ['title' => 'Commit the Import',    'body' => 'Once validation passes (zero errors), the Commit button becomes active. Click it to save all marks to the database. A batch record is created automatically.'],
            ],
            'manualNotes'   => [
                '<strong>Warning:</strong> Re-uploading a CSV for the same school–subject will update existing marks. Locked batches cannot be overwritten.',
                'Ensure the CSV uses UTF-8 encoding. Save as "CSV (Comma delimited)" — not Excel XLSX.',
            ],
        ],

        'bulk-import' => [
            'manualTitle'   => 'Bulk Import — User Guide',
            'manualSummary' => 'Bulk Import allows uploading a ZIP archive containing multiple CSV scoresheets for an entire school or district in one operation.',
            'manualSteps'   => [
                ['title' => 'Prepare the ZIP',           'body' => 'Each CSV inside the ZIP must follow the standard scoresheet format and be named using the pattern: SCHOOLCODE_SUBJECTCODE.csv (e.g., PS001_MATH.csv). Do not nest folders inside the ZIP.'],
                ['title' => 'Select Scope',              'body' => 'Choose Exam Year and either a single School (School ZIP) or a District (District ZIP). Officers can only import within their assigned region.'],
                ['title' => 'Upload and Validate',       'body' => 'Click "Upload ZIP" and select your archive. The system extracts and validates each CSV individually, reporting errors per file. Review all errors before proceeding.'],
                ['title' => 'Review the Validation Report','body' => 'A summary shows Total Files, Valid Files, Invalid Files, and Total Rows. Expand each file to see row-level errors. Fix the offending CSVs, re-zip, and re-upload.'],
                ['title' => 'Commit the Batch',          'body' => 'Once all files pass validation, click "Commit All". One batch record is created per school–subject pair. Marks are written to the database and are available immediately in the Dashboard.'],
            ],
            'manualNotes'   => [
                '<strong>Limit:</strong> ZIP files must be under 50 MB. For larger datasets, split into multiple ZIPs by district.',
                'Each CSV in the ZIP is validated independently — a single bad file does not block the others from being committed once corrected.',
            ],
        ],

        'missing-marks' => [
            'manualTitle'   => 'Missing Marks — User Guide',
            'manualSummary' => 'The Missing Marks page lists every registered candidate who does not yet have a mark record for one or more PSLE subjects, scoped to your permitted region.',
            'manualSteps'   => [
                ['title' => 'Reading the Candidate List',   'body' => 'Each row shows Candidate Number, Name, Sex, School, Subject, and Assigned Officer. "Missing" means no RawMark record exists for that candidate–subject pair for the active exam year.'],
                ['title' => 'Filtering',                    'body' => 'Use the District, School, and Subject filters to focus on a specific group. Officers see only their region; REOs see their full region.'],
                ['title' => 'Resolving Missing Marks',      'body' => 'To resolve, go to Start Entry or Bulk Import for the relevant school–subject and upload the corrected CSV. The candidate will disappear from this list once a RawMark record is created.'],
                ['title' => 'Absent Candidates',            'body' => 'If a candidate was genuinely absent for an exam, upload a CSV row with Subject Status = ABS. This creates a RawMark record and removes them from the Missing list.'],
                ['title' => 'Export',                       'body' => 'Click "Export CSV" to download the full missing-marks list for offline follow-up or sharing with school heads.'],
            ],
            'manualNotes'   => [
                '<strong>Note:</strong> Missing Marks are calculated against candidate registrations — if a candidate is not registered, they will not appear here.',
                'Resolving all missing marks is a prerequisite before a batch can be submitted for review.',
            ],
        ],

        'validation-errors' => [
            'manualTitle'   => 'Validation Errors — User Guide',
            'manualSummary' => 'Validation Errors shows all open data-quality issues detected during import or manual correction — such as marks above the maximum, negative marks, duplicate entries, and ABS conflicts.',
            'manualSteps'   => [
                ['title' => 'Understanding Error Types',    'body' => 'Invalid Mark — numeric value outside the allowed range. Wrong Format — non-numeric entry. Mark Above Maximum — exceeds the subject maximum. Negative Mark — mark below zero. Duplicate Entry — same candidate–subject imported twice. ABS Conflict — candidate has both a mark and ABS status.'],
                ['title' => 'Severity Levels',             'body' => 'Critical errors must be resolved before submission. High and Medium errors are serious but may be overridden with a documented reason. Low/Warning errors are advisory.'],
                ['title' => 'Resolving an Error',          'body' => 'Click "Resolve" on a row to open the correction form. Enter the correct mark or status, provide a reason, and save. The error status changes to "Resolved" and is recorded in the audit trail.'],
                ['title' => 'Re-importing to Fix',         'body' => 'For bulk errors in the same CSV, it is faster to correct the source file and re-import it via Start Entry. Re-importing will update existing marks and auto-resolve matching validation errors.'],
                ['title' => 'Filtering by Status/Type',    'body' => 'Use the Status filter (Open / Resolved) and Error Type filter to focus on specific categories. The summary cards at the top show counts by severity.'],
            ],
            'manualNotes'   => [
                '<strong>Important:</strong> All corrections are logged in the audit trail with the correcting officer\'s ID and timestamp.',
                'Batches with open Critical errors cannot be submitted. Resolve all critical errors first.',
            ],
        ],

        'outliers' => [
            'manualTitle'   => 'Outliers & Extremity — User Guide',
            'manualSummary' => 'The Outliers section surfaces statistically extreme marks that may indicate data-entry errors or genuine exceptional performance. Each outlier must be reviewed and either verified or corrected.',
            'manualSteps'   => [
                ['title' => 'How Outliers are Detected',    'body' => 'The system uses statistical thresholds (e.g. ±2 standard deviations from the school mean) to flag marks as High Outlier, Low Outlier, or Pattern Outlier. Detection runs automatically after each batch import.'],
                ['title' => 'Reading the Outlier Table',    'body' => 'Each row shows the Candidate, School, Subject, Mark, Outlier Type, Severity, and current Status. Severity is Critical / High / Medium / Low.'],
                ['title' => 'Verifying an Outlier',         'body' => 'If the mark is correct (e.g. a genuinely exceptional student), click "Verify". Provide a short reason (e.g. "Confirmed with school register"). The outlier is marked Verified and will not block submission.'],
                ['title' => 'Correcting an Outlier',        'body' => 'If the mark is wrong, click "Correct", enter the right mark and a reason. The RawMark is updated, a MarkEntryChange record is written, and the outlier is closed.'],
                ['title' => 'Filtering',                    'body' => 'Filter by Severity, Status (Open / Verified / Corrected), and Officer. Officers see only their region\'s outliers; REOs see the full region.'],
            ],
            'manualNotes'   => [
                '<strong>Note:</strong> Unresolved Critical outliers may block batch approval. Work through them before submitting.',
                'All verification and correction actions are permanently recorded in the audit log.',
            ],
        ],

        'moderation-review' => [
            'manualTitle'   => 'Moderation & Review — User Guide',
            'manualSummary' => 'Moderation & Review is the REO / Admin workspace for inspecting submitted batches, approving correct submissions, and rejecting those that need rework.',
            'manualSteps'   => [
                ['title' => 'Batch Lifecycle States',       'body' => 'Draft → Validated → Submitted → Approved / Rejected → Locked. You review batches in Submitted state. Approved batches move to the Locking queue; Rejected batches are returned to the Officer for correction.'],
                ['title' => 'Reviewing a Batch',            'body' => 'Click a batch row to open its detail view. Review the mark distribution, validation errors, outlier summary, and the officer\'s notes. Compare against the school register if needed.'],
                ['title' => 'Approving a Batch',            'body' => 'If the batch is satisfactory, click "Approve". The batch status changes to Approved and becomes available for Locking. An audit event is recorded.'],
                ['title' => 'Rejecting a Batch',            'body' => 'If corrections are needed, click "Reject" and enter a detailed reason. The batch returns to Draft; the Officer is expected to correct and resubmit. The rejection reason is visible to the Officer.'],
                ['title' => 'Filtering Batches',            'body' => 'Use the Region, District, School, Subject, and Status filters to manage your review queue efficiently. Sort by Submitted Date to prioritise oldest submissions.'],
            ],
            'manualNotes'   => [
                '<strong>Important:</strong> REOs can only review batches within their assigned region. Cross-region batch access is blocked at the controller level.',
                'Rejection reasons are mandatory and are logged permanently.',
            ],
        ],

        'submission-locking' => [
            'manualTitle'   => 'Submission & Locking — User Guide',
            'manualSummary' => 'This workspace manages the final stages of the PSLE mark-entry lifecycle: batch submission by Officers and final locking by Administrators.',
            'manualSteps'   => [
                ['title' => 'Submitting a Batch (Officer)',  'body' => 'Once all marks are entered and all validation errors resolved, locate your batch in the table and click "Submit". The batch status changes to Submitted and is placed in the REO review queue.'],
                ['title' => 'What Happens After Submission', 'body' => 'The batch is now read-only for the Officer. The REO or Admin will review it under Moderation & Review. You will be notified if it is Rejected and needs rework.'],
                ['title' => 'Locking a Batch (Admin)',       'body' => 'After a batch is Approved, Administrators can Lock it. Click "Lock" on an Approved batch. Locked batches cannot be edited by anyone. This is the final state before result processing.'],
                ['title' => 'Unlocking (Emergency)',         'body' => 'If a locked batch must be corrected, an Administrator can Unlock it with a documented reason. This action is logged and should be used sparingly.'],
                ['title' => 'Batch Status Summary',          'body' => 'The summary cards show counts of Submitted, Approved, Rejected, and Locked batches for the current scope, helping you track progress across regions.'],
            ],
            'manualNotes'   => [
                '<strong>Warning:</strong> Locking is irreversible without Admin intervention. Confirm the batch is fully correct before locking.',
                'Once locked, marks flow into the result-processing pipeline and cannot be changed without a formal amendment process.',
            ],
        ],

        'reports-exports' => [
            'manualTitle'   => 'Reports & Exports — User Guide',
            'manualSummary' => 'Generate and download official PSLE mark-entry documents, data exports, and audit reports. All exports are scoped to your permitted region and are logged in the audit trail.',
            'manualSteps'   => [
                ['title' => 'Applying Scope Filters',        'body' => 'Use the Exam Year, Region, District, School, and Subject filters before generating any report. The scope shown in the filters is exactly what will appear in the downloaded file.'],
                ['title' => 'Subject Mark Sheet (PDF)',      'body' => 'Select a specific School + Subject, then click "Download" to get a formatted PDF scoresheet. For a full school, select only the School to get a ZIP of all subject PDFs. For a district, select a District to get a district-wide ZIP.'],
                ['title' => 'Entered Marks Verification (PDF)','body' => 'Produces a verification sheet showing marks already in the system. Used for cross-checking against paper records. Requires at least a School filter.'],
                ['title' => 'CSV / Excel Exports',           'body' => 'Missing Marks, Validation Errors, Outliers, Regional Progress, and Officer Activity are available as CSV exports. Click "Export" on the relevant row. Files are named with a timestamp for traceability.'],
                ['title' => 'Export Raw Data',               'body' => 'The "Export Raw Data" button at the top of the reports table downloads all batch records for the current scope as a CSV — useful for custom analysis.'],
            ],
            'manualNotes'   => [
                '<strong>Compliance:</strong> Every export is recorded in the Governance Audit Log with your User ID, IP address, and selected filters.',
                'Mark Entry Officers can only export data within their assigned region. Attempts to export outside scope are blocked.',
            ],
        ],

        'monitoring-audit' => [
            'manualTitle'   => 'Monitoring & Audit — User Guide',
            'manualSummary' => 'The Monitoring & Audit workspace provides real-time visibility into officer activity, regional progress, batch history, and system audit logs.',
            'manualSteps'   => [
                ['title' => 'Summary Cards',                 'body' => 'The top cards show Active Officers, Marks Entered Today, Pending Marks, Submitted Batches, Validation Runs, and Audit Events — all scoped to your permitted region.'],
                ['title' => 'Officer Productivity Table',    'body' => 'Shows each officer\'s Assigned Candidates, Entered Marks, Pending Marks, and Last Activity. Officers see only their own row; REOs see all officers in their region.'],
                ['title' => 'Regional Progress',             'body' => 'A district-level table with progress bars showing the percentage of marks entered per district. Use this to identify districts that need follow-up.'],
                ['title' => 'Recent Activity & Batch Feed',  'body' => 'The side-by-side panels show live system events (imports, validations, submissions) and the most recent batch status changes. Refresh the page to see the latest activity.'],
                ['title' => 'Audit Trail Preview',           'body' => 'Shows the last 20 system events with User, Role, Region, Action, Details, and IP. This is a preview — full audit export is available via Reports & Exports.'],
            ],
            'manualNotes'   => [
                '<strong>Access:</strong> Mark Entry Officers see only their own productivity and regional metrics — no cross-region data.',
                'The Security Observations panel will surface anomalies (e.g. unusually high entry rates or repeated failures) once sufficient data is present.',
            ],
        ],

        'assignments' => [
            'manualTitle'   => 'Manage Assignments — User Guide',
            'manualSummary' => 'Assignments define which Mark Entry Officer is responsible for entering marks for a specific School and Subject in a given Exam Year.',
            'manualSteps'   => [
                ['title' => 'Creating an Assignment',        'body' => 'Click "New Assignment". Select the Exam Year, Region, School, Subject, and Officer. Optionally assign a Marking Centre. Click Save. The Officer will see this assignment in their Start Entry dropdown immediately.'],
                ['title' => 'Editing an Assignment',         'body' => 'Click the edit icon on an existing assignment row to change the assigned Officer or Marking Centre. You cannot change the School or Subject after creation — delete and recreate instead.'],
                ['title' => 'Deactivating an Assignment',    'body' => 'Toggle the Status to Inactive to remove the assignment from the Officer\'s view without deleting the record. Inactive assignments retain their history.'],
                ['title' => 'Assignment Status',             'body' => 'Active — Officer can enter marks. Inactive — hidden from Officer. Completed — marks submitted and approved. The system does not auto-complete; status must be manually updated.'],
                ['title' => 'Bulk Assignment',               'body' => 'Use the "Bulk Assign" button to assign the same Officer to all subjects for a selected School at once, saving time during initial setup.'],
            ],
            'manualNotes'   => [
                '<strong>Note:</strong> REOs can only create assignments within their own region. Admins can assign across all TASIDO regions.',
                'An Officer without an active assignment cannot access Start Entry for that school–subject combination.',
            ],
        ],

        'user-management' => [
            'manualTitle'   => 'User Management — User Guide',
            'manualSummary' => 'Create and manage portal user accounts for Mark Entry Officers, REOs, and Administrators. All user changes are logged in the Governance Audit Log.',
            'manualSteps'   => [
                ['title' => 'Creating a User',               'body' => 'Click "New User". Fill in Name, Email, Role, and Region. A temporary password is generated and shown once — copy it before closing. The user must change it on first login.'],
                ['title' => 'Assigning a Role',              'body' => 'Roles determine portal access. mark_officer = entry-only access within assigned region. reo = regional management access. admin = full portal access.'],
                ['title' => 'Assigning a Region',            'body' => 'Every non-Admin user must have a Region assigned. This drives all data scoping — a user without a region will see the Access Restricted screen.'],
                ['title' => 'Suspending / Activating',       'body' => 'Toggle the user\'s Active status to Suspend (block login) or Activate (restore login). Suspensions are logged with the Admin\'s ID.'],
                ['title' => 'Resetting Passwords',           'body' => 'Click "Reset Password" on a user row to generate a new temporary password. The old password is immediately invalidated.'],
            ],
            'manualNotes'   => [
                '<strong>Security:</strong> Only System Administrators can access User Management. All actions are permanently logged.',
                'Do not share temporary passwords via unencrypted channels. Instruct users to change their password immediately on first login.',
            ],
        ],

        'marking-centres' => [
            'manualTitle'   => 'Marking Centres — User Guide',
            'manualSummary' => 'Marking Centres are physical or virtual locations where groups of Mark Entry Officers work. They help organise assignments and track officer activity by venue.',
            'manualSteps'   => [
                ['title' => 'Creating a Centre',             'body' => 'Click "New Marking Centre". Enter the Centre Name, Region, and optional contact details. Save. The centre is immediately available when creating assignments.'],
                ['title' => 'Assigning Officers to a Centre','body' => 'When creating or editing a user, select their Marking Centre from the dropdown. Officers can only be assigned to centres in their own region.'],
                ['title' => 'Linking to Assignments',        'body' => 'When creating an assignment, optionally select the Marking Centre where the work will be done. This is used in monitoring reports to track centre-level productivity.'],
                ['title' => 'Editing / Deactivating',        'body' => 'Click the edit icon to update a centre\'s name or contact details. Deactivate a centre to prevent new assignments being linked to it — existing assignments are unaffected.'],
            ],
            'manualNotes'   => [
                'Marking Centres are optional — assignments can exist without a centre. However, using centres improves monitoring granularity.',
                'Only System Administrators can create or modify Marking Centres.',
            ],
        ],

    ];

    // Default fallback for any view not listed above
    $manualData = $pageManuals[$currentView] ?? [
        'manualTitle'   => 'PSLE Mark Entry — User Guide',
        'manualSummary' => 'The PSLE Mark Entry Portal is a secure, role-based system within TASIDO 2026 for entering, validating, moderating, and submitting Primary School Leaving Examination marks.',
        'manualSteps'   => [
            ['title' => 'User Roles',           'body' => 'Admin has full access. REO manages their region. Mark Entry Officers enter marks for assigned schools and subjects only.'],
            ['title' => 'Data Scoping',         'body' => 'All data is scoped to your permitted region. You cannot view or export data from other TASIDO regions.'],
            ['title' => 'Getting Help',         'body' => 'Contact your Regional Education Officer or System Administrator if you encounter access restrictions or data issues.'],
        ],
        'manualNotes'   => ['<strong>Tip:</strong> Use the sidebar to navigate to the correct section for the task you need to perform.'],
    ];

    $markEntryManual = array_merge([
        'manualId'          => 'psleMarkEntryManual',
        'manualPdf'         => null,
        'manualButtonLabel' => 'User Manual',
        'manualButtonIcon'  => 'fa-book-open',
    ], $manualData);
@endphp
@include('mock-portal.partials.user-manual', $markEntryManual)

<script>
    // Simple toggle for mobile sidebar if needed
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.adm-sidebar').classList.toggle('open');
    });
</script>
</body>
</html>
