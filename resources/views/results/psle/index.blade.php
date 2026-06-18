<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSLE Results Administration Portal</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            background: #080c10;
        }
        
        /* Sidebar */
        .adm-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #0d1520, #090e15);
            border-right: 1px solid rgba(187,164,94,0.12);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 100;
            box-shadow: 10px 0 30px rgba(0,0,0,0.3);
        }
        .adm-sidebar-head {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(187,164,94,0.12);
            background: linear-gradient(135deg, rgba(187,164,94,0.05), transparent);
        }
        .adm-brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .adm-brand-logo { width: 38px; height: 38px; object-fit: contain; flex-shrink: 0; }
        .adm-brand-text { font-weight: 800; font-size: 1.15rem; color: #ffffff; letter-spacing: -0.5px; line-height: 1.1; }
        .adm-brand-text span { color: var(--tz-yellow); font-size: 0.62rem; display: block; margin-top: 4px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; }
        
        .adm-nav { padding: 20px 14px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; }
        .adm-nav::-webkit-scrollbar { width: 4px; }
        .adm-nav::-webkit-scrollbar-track { background: transparent; }
        .adm-nav::-webkit-scrollbar-thumb { background: rgba(187,164,94,0.15); border-radius: 4px; }
        
        .nav-section-label { 
            padding: 12px 12px 6px; 
            font-size: 0.65rem; 
            color: rgba(255,255,255,0.3); 
            text-transform: uppercase; 
            letter-spacing: 1.8px; 
            font-weight: 700; 
            border-bottom: 1px solid rgba(255,255,255,0.03);
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .nav-section-label:first-of-type { margin-top: 0; }
        
        .adm-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border-left: 3px solid transparent;
        }
        .adm-nav-item:hover {
            background: rgba(187,164,94,0.08);
            color: #ffffff;
            transform: translateX(3px);
            border-left-color: rgba(187,164,94,0.3);
        }
        .adm-nav-item.active {
            background: linear-gradient(90deg, rgba(187,164,94,0.15) 0%, rgba(187,164,94,0.02) 100%);
            color: #ffd875;
            font-weight: 700;
            border-left-color: var(--tz-gold);
            box-shadow: inset 1px 0 0 rgba(255,255,255,0.02);
        }
        .adm-nav-item i { width: 16px; font-size: 0.95rem; }
        .nav-ico {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.6);
            flex-shrink: 0;
            transition: all 0.2s;
        }
        .adm-nav-item:hover .nav-ico {
            background: rgba(187,164,94,0.16);
            color: #ffffff;
        }
        .adm-nav-item.active .nav-ico {
            background: rgba(187,164,94,0.25);
            color: var(--tz-yellow);
        }
        
        .btn-logout {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.05);
            margin-top: auto;
        }

        /* Main Content */
        .adm-main {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            background: #090d13;
        }
        
        .adm-topbar {
            background: rgba(13, 21, 32, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(187,164,94,0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 32px;
            position: sticky;
            top: 0;
            z-index: 90;
            gap: 20px;
        }
        .adm-top-left {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .adm-top-title { 
            font-size: 1.3rem; 
            font-weight: 800; 
            color: #ffffff; 
            letter-spacing: -0.3px;
        }
        .adm-top-subtitle {
            font-size: 0.78rem;
            color: var(--tz-text-muted);
        }
        .adm-top-subtitle .highlight-year {
            color: var(--tz-yellow);
            font-weight: 700;
        }
        
        .adm-top-user { 
            display: flex; 
            align-items: center; 
            gap: 16px; 
        }
        
        .adm-user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-left: 16px;
            border-left: 1px solid rgba(255,255,255,0.1);
        }
        
        .adm-user-info { text-align: right; }
        .adm-user-name { font-size: 0.88rem; font-weight: 700; color: #ffffff; display: block; }
        .adm-user-role { font-size: 0.68rem; color: var(--tz-yellow); text-transform: uppercase; font-weight: 700; letter-spacing: 0.8px; }
        .adm-user-avatar { 
            width: 38px; 
            height: 38px; 
            background: linear-gradient(135deg, var(--tz-blue), #025979); 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 800; 
            color: #ffffff; 
            font-size: 0.95rem; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .adm-content { 
            padding: 32px; 
            max-width: 1400px; 
            margin: 0 auto; 
            width: 100%; 
            box-sizing: border-box; 
        }

        /* Breadcrumb */
        .adm-breadcrumb { 
            font-size: 0.72rem; 
            font-weight: 700; 
            color: var(--tz-text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            margin-bottom: 20px; 
            display: flex; 
            gap: 6px; 
            align-items: center; 
        }
        .adm-breadcrumb span { color: var(--tz-yellow); }
        .adm-breadcrumb i { font-size: 0.6rem; opacity: 0.5; }

        /* Page Header */
        .adm-page-header { 
            margin-bottom: 28px; 
            padding: 24px; 
            background: linear-gradient(135deg, rgba(13, 21, 32, 0.6) 0%, rgba(9, 14, 21, 0.4) 100%);
            border: 1px solid rgba(187,164,94,0.12);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .adm-page-title { font-size: 1.7rem; font-weight: 800; color: #ffffff; margin-bottom: 6px; letter-spacing: -0.5px; }
        .adm-page-desc { font-size: 0.9rem; color: var(--tz-text-muted); line-height: 1.5; margin: 0; }

        /* Stats Cards */
        .adm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 28px; }
        .adm-stat {
            background: linear-gradient(135deg, #121924, #0e131b);
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            transition: all 0.25s ease;
        }
        .adm-stat::after {
            content: '';
            position: absolute;
            right: -10px;
            bottom: -10px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.02);
        }
        .adm-stat:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border-color: rgba(187,164,94,0.25);
        }
        .adm-stat-label { font-size: 0.68rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.08em; margin-bottom: 6px; }
        .adm-stat-value { font-size: 1.8rem; font-weight: 800; color: #ffffff; margin-bottom: 2px; letter-spacing: -0.5px; }
        .adm-stat-icon { position: absolute; top: 20px; right: 20px; font-size: 1.25rem; }

        .adm-stats .adm-stat:nth-child(1) { border-left: 3px solid var(--tz-blue); }
        .adm-stats .adm-stat:nth-child(1) .adm-stat-icon { color: var(--tz-blue); opacity: 0.35; }
        .adm-stats .adm-stat:nth-child(2) { border-left: 3px solid var(--tz-yellow); }
        .adm-stats .adm-stat:nth-child(2) .adm-stat-icon { color: var(--tz-yellow); opacity: 0.35; }
        .adm-stats .adm-stat:nth-child(3) { border-left: 3px solid var(--tz-green); }
        .adm-stats .adm-stat:nth-child(3) .adm-stat-icon { color: var(--tz-green); opacity: 0.35; }
        .adm-stats .adm-stat:nth-child(4) { border-left: 3px solid var(--tz-gold); }
        .adm-stats .adm-stat:nth-child(4) .adm-stat-icon { color: var(--tz-gold); opacity: 0.35; }

        .adm-card {
            background: #111823;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            margin-bottom: 28px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            transition: all 0.25s ease;
            overflow: hidden;
        }
        .adm-card:hover {
            border-color: rgba(187,164,94,0.18);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .adm-card-head { 
            padding: 16px 22px; 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: linear-gradient(90deg, rgba(255,255,255,0.01) 0%, transparent 100%);
        }
        .adm-card-title { font-size: 1.05rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px; margin: 0; }
        .adm-card-title i { color: var(--tz-yellow); font-size: 0.95rem; }
        .adm-card-body { padding: 22px; }

        /* Filters */
        .adm-filters { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 16px; 
            padding: 20px 24px; 
            background: rgba(255,255,255,0.02); 
            border-bottom: 1px solid rgba(255,255,255,0.05); 
        }
        .adm-filter-group { flex: 1; min-width: 160px; }
        .adm-filter-label { display: block; font-size: 0.68rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.8px; }
        
        .adm-select {
            width: 100%; 
            height: 38px; 
            padding: 0 12px;
            background: rgba(255,255,255,0.04); 
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px; 
            color: #ffffff; 
            font-family: inherit; 
            font-size: 0.82rem;
            outline: none; 
            transition: all 0.2s;
            appearance: none; 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); 
            background-repeat: no-repeat; 
            background-position: right 12px center; 
            background-size: 14px;
        }
        .adm-select:focus { 
            border-color: var(--tz-blue); 
            background-color: rgba(255,255,255,0.06); 
            box-shadow: 0 0 0 3px rgba(0,163,221,0.15); 
        }
        .adm-select option { background: #0f1520; color: #ffffff; }

        .adm-select.year-selector {
            width: auto;
            min-width: 110px;
            height: 36px;
            padding: 0 28px 0 12px;
            background-position: right 10px center;
            border-color: rgba(187,164,94,0.3);
            background-color: rgba(187,164,94,0.05);
            font-weight: 700;
            color: #ffd875;
        }
        .adm-select.year-selector:focus {
            border-color: var(--tz-yellow);
            box-shadow: 0 0 0 3px rgba(252,209,22,0.15);
        }

        .adm-input {
            width: 100%; 
            height: 38px; 
            padding: 0 12px;
            background: rgba(255,255,255,0.04); 
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px; 
            color: #ffffff; 
            font-family: inherit; 
            font-size: 0.82rem;
            outline: none; 
            transition: all 0.2s;
        }
        .adm-input:focus { 
            border-color: var(--tz-blue); 
            background-color: rgba(255,255,255,0.06); 
            box-shadow: 0 0 0 3px rgba(0,163,221,0.15); 
        }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { 
            padding: 14px 18px; 
            font-size: 0.7rem; 
            color: var(--tz-text-muted); 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            border-bottom: 1px solid rgba(255,255,255,0.08); 
            font-weight: 700; 
            white-space: nowrap; 
            background: rgba(255,255,255,0.01);
        }
        td { 
            padding: 14px 18px; 
            font-size: 0.88rem; 
            border-bottom: 1px solid rgba(255,255,255,0.03); 
            color: rgba(255,255,255,0.85); 
            transition: all 0.15s ease; 
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(187,164,94,0.04); color: #ffffff; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
        .badge-blue { background: rgba(0,163,221,0.12); color: #67d8ff; border: 1px solid rgba(0,163,221,0.2); }
        .badge-green { background: rgba(30,181,58,0.12); color: #6ae086; border: 1px solid rgba(30,181,58,0.2); }
        .badge-red { background: rgba(239,68,68,0.12); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }
        .badge-yellow { background: rgba(252,209,22,0.12); color: #fde047; border: 1px solid rgba(252,209,22,0.2); }

        /* Buttons */
        .btn {
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 8px;
            padding: 9px 18px; 
            border-radius: 6px; 
            font-family: inherit; 
            font-size: 0.82rem; 
            font-weight: 700;
            cursor: pointer; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); 
            border: 1px solid transparent; 
            text-decoration: none; 
            white-space: nowrap;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, var(--tz-blue), #0077a3); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(0,163,221,0.2); 
        }
        .btn-primary:hover { 
            transform: translateY(-1.5px); 
            box-shadow: 0 6px 16px rgba(0,163,221,0.35); 
            filter: brightness(1.1);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, var(--tz-green), #148028); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(30,181,58,0.2); 
        }
        .btn-success:hover { 
            transform: translateY(-1.5px); 
            box-shadow: 0 6px 16px rgba(30,181,58,0.35); 
            filter: brightness(1.1);
        }
        
        .btn-warning { 
            background: linear-gradient(135deg, var(--tz-gold), #8c6e26); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(187,164,94,0.2); 
        }
        .btn-warning:hover { 
            transform: translateY(-1.5px); 
            box-shadow: 0 6px 16px rgba(187,164,94,0.35); 
            filter: brightness(1.1);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444, #b91c1c); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(239,68,68,0.2); 
        }
        .btn-danger:hover { 
            transform: translateY(-1.5px); 
            box-shadow: 0 6px 16px rgba(239,68,68,0.35); 
            filter: brightness(1.1);
        }
        
        .btn-outline { 
            background: rgba(255,255,255,0.02); 
            color: rgba(255,255,255,0.85); 
            border: 1px solid rgba(255,255,255,0.1); 
        }
        .btn-outline:hover { 
            background: rgba(255,255,255,0.06); 
            color: #ffffff;
            border-color: rgba(255,255,255,0.2);
            transform: translateY(-1px);
        }
        
        .btn-action { 
            background: rgba(0,163,221,0.08); 
            color: var(--tz-blue); 
            padding: 6px 12px; 
            font-size: 0.78rem; 
            border-radius: 4px;
        }
        .btn-action:hover { 
            background: rgba(0,163,221,0.16); 
            color: #67d8ff;
        }

        /* Quick Actions Grid */
        .qa-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; padding: 20px; }
        .qa-item {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);
            border-radius: 10px; padding: 20px; text-align: center; color: var(--tz-text);
            text-decoration: none; transition: all 0.2s ease; display: flex; flex-direction: column; align-items: center; gap: 12px;
        }
        .qa-item i { font-size: 1.6rem; color: var(--tz-yellow); }
        .qa-item:hover:not(.disabled) { background: rgba(187,164,94,0.06); border-color: rgba(187,164,94,0.2); transform: translateY(-2px); }
        .qa-item.disabled { opacity: 0.35; cursor: not-allowed; }
        .qa-item-title { font-weight: 700; font-size: 0.9rem; }

        /* Pagination style */
        .adm-pagination { display: flex; justify-content: space-between; align-items: center; padding: 18px 22px; border-top: 1px solid rgba(255,255,255,0.05); }
        .pagination-info { font-size: 0.82rem; color: var(--tz-text-muted); }
        .pagination-links { display: flex; gap: 6px; }
        .page-link { 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            min-width: 32px; 
            height: 32px; 
            padding: 0 10px; 
            border-radius: 6px; 
            background: rgba(255,255,255,0.03); 
            border: 1px solid rgba(255,255,255,0.06); 
            color: rgba(255,255,255,0.85); 
            text-decoration: none; 
            font-size: 0.82rem; 
            font-weight: 700; 
            transition: all 0.2s; 
        }
        .page-link:hover { background: rgba(187,164,94,0.12); border-color: rgba(187,164,94,0.4); color: #ffffff; }
        .page-link.active { background: var(--tz-gold); border-color: var(--tz-gold); color: #0d1520; }
        .page-link.disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }

        @media (max-width: 1200px) {
            .adm-stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 1024px) {
            .adm-sidebar { width: 80px; }
            .adm-brand-text, .adm-user-info, .adm-nav-item .nav-label { display: none; }
            .adm-nav-item { justify-content: center; padding: 12px; }
            .adm-main { margin-left: 80px; }
            .adm-top-subtitle { display: none; }
        }

        @media (max-width: 768px) {
            .adm-sidebar { display: none; }
            .adm-main { margin-left: 0; }
            .adm-stats { grid-template-columns: 1fr; }
            .adm-content { padding: 20px; }
            .adm-topbar { padding: 14px 20px; }
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
                    <span>PSLE Results Admin</span>
                </div>
            </a>
        </div>
        
        <nav class="adm-nav">
            <div class="nav-section-label">Main Administration</div>
            <a href="{{ route('results.psle.dashboard', ['view' => 'overview']) }}" class="adm-nav-item {{ $view === 'overview' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-chart-pie"></i></span>
                <span class="nav-label">Overview</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'processing']) }}" class="adm-nav-item {{ $view === 'processing' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-gears"></i></span>
                <span class="nav-label">Results Processing</span>
            </a>
            
            <div class="nav-section-label">Results Performance</div>
            <a href="{{ route('results.psle.dashboard', ['view' => 'candidate-results']) }}" class="adm-nav-item {{ $view === 'candidate-results' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-user-graduate"></i></span>
                <span class="nav-label">Candidate Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'school-results']) }}" class="adm-nav-item {{ $view === 'school-results' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-school"></i></span>
                <span class="nav-label">School Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'district-results']) }}" class="adm-nav-item {{ $view === 'district-results' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                <span class="nav-label">District Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'regional-results']) }}" class="adm-nav-item {{ $view === 'regional-results' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-earth-africa"></i></span>
                <span class="nav-label">Regional Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'subject-performance']) }}" class="adm-nav-item {{ $view === 'subject-performance' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-book-open"></i></span>
                <span class="nav-label">Subject Performance</span>
            </a>

            <div class="nav-section-label">External Integration</div>
            <a href="{{ route('public.results.psle.regions', ['examYear' => $examYear->year_label ?? 2026]) }}" target="_blank" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-globe"></i></span>
                <span class="nav-label">Public Results Portal <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.65rem; opacity: 0.7;"></i></span>
            </a>
            <a href="{{ route('evaluations.psle.index') }}" target="_blank" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-clipboard-check"></i></span>
                <span class="nav-label">Evaluations System <i class="fa-solid fa-arrow-up-right-from-square" style="font-size: 0.65rem; opacity: 0.7;"></i></span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'reports']) }}" class="adm-nav-item {{ $view === 'reports' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-file-pdf"></i></span>
                <span class="nav-label">PDF Reports Exporter</span>
            </a>

            <div class="nav-section-label">Security & Audit</div>
            <a href="{{ route('results.psle.dashboard', ['view' => 'audit']) }}" class="adm-nav-item {{ $view === 'audit' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fa-solid fa-shield-halved"></i></span>
                <span class="nav-label">Audit Logs</span>
            </a>
        </nav>
        
        <div class="btn-logout">
            <a href="{{ url('/') }}" class="btn btn-outline" style="width: 100%;">
                <i class="fa-solid fa-arrow-left"></i> Exit to Main
            </a>
        </div>
    </aside>
    
    <!-- Main Window -->
    <main class="adm-main">
        <!-- Top Bar -->
        <header class="adm-topbar">
            <div class="adm-top-left">
                <div class="adm-top-title">
                    PSLE Results Administration Control Centre
                </div>
                <div class="adm-top-subtitle">
                    Exam Year Context: <span class="highlight-year">{{ $examYear->year_label }}</span> | Administering calculations & snapshots for Tabora, Singida, Iringa, and Dodoma regions.
                </div>
            </div>
            
            <div class="adm-top-user">
                <!-- Year Selector Form -->
                <form action="{{ route('results.psle.dashboard') }}" method="GET" style="display: flex; gap: 8px; align-items: center;">
                    <input type="hidden" name="view" value="{{ $view }}">
                    <select name="exam_year_id" onchange="this.form.submit()" class="adm-select year-selector">
                        @foreach($examYears as $year)
                            <option value="{{ $year->id }}" {{ $examYear->id == $year->id ? 'selected' : '' }}>
                                {{ $year->year_label }}
                            </option>
                        @endforeach
                    </select>
                </form>
 
                <div class="adm-user-profile">
                    <div class="adm-user-info">
                        <span class="adm-user-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                        <span class="adm-user-role">System Admin</span>
                    </div>
                    <div class="adm-user-avatar">
                        {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                    </div>
                </div>
            </div>
        </header>
        
        <!-- View Content -->
        <div class="adm-content">
            <!-- Breadcrumbs -->
            <div class="adm-breadcrumb">
                Admin Dashboard <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i> PSLE Results <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i> <span>{{ ucfirst(str_replace('-', ' ', $view)) }}</span>
            </div>

            <!-- Page Header -->
            <div class="adm-page-header">
                <h1 class="adm-page-title">
                    @if($view === 'overview') Primary School Examination Results Dashboard
                    @elseif($view === 'processing') Results Lifecycle Snapshots Processing
                    @elseif($view === 'candidate-results') Candidate Results & Marks Breakdown
                    @elseif($view === 'school-results') Primary School Academic Performance & Ranks
                    @elseif($view === 'district-results') District Performance & Pass Rates
                    @elseif($view === 'regional-results') TASIDO Regional Performance Standing
                    @elseif($view === 'subject-performance') Core Subjects Grading Distributions
                    @elseif($view === 'reports') Official PDF Reports & ZIP Exporter
                    @elseif($view === 'audit') Audit Logs and Lifecycle Tracking
                    @endif
                </h1>
                <p class="adm-page-desc">
                    Year: <strong>{{ $examYear->year_label }}</strong>. Administering result lists, data validations, dry processing runs, and official snapshot distributions inside the Tabora, Singida, Iringa, and Dodoma regions.
                </p>
            </div>

            <!-- Metrics Widgets Grid -->
            <div class="adm-stats">
                <div class="adm-stat">
                    <div class="adm-stat-label">TASIDO Regions</div>
                    <div class="adm-stat-value">{{ $metrics['regions'] }}</div>
                    <i class="fa-solid fa-earth-africa adm-stat-icon" style="color: #67d8ff;"></i>
                </div>
                <div class="adm-stat">
                    <div class="adm-stat-label">Primary Schools</div>
                    <div class="adm-stat-value">{{ $metrics['schools'] }}</div>
                    <i class="fa-solid fa-school adm-stat-icon" style="color: #fde047;"></i>
                </div>
                <div class="adm-stat">
                    <div class="adm-stat-label">Registered Candidates</div>
                    <div class="adm-stat-value">{{ number_format($metrics['registered']) }}</div>
                    <i class="fa-solid fa-user-graduate adm-stat-icon" style="color: #6ae086;"></i>
                </div>
                <div class="adm-stat">
                    <div class="adm-stat-label">Completion Status</div>
                    <div class="adm-stat-value">{{ number_format($metrics['complete']) }} <span style="font-size: 0.8rem; font-weight: normal; color: var(--tz-text-muted);">Entered</span></div>
                    <i class="fa-solid fa-circle-check adm-stat-icon" style="color: #bba45e;"></i>
                </div>
            </div>

            <!-- Main view partial loader -->
            @php
                $view = $view ?? request('view', 'processing');

                $partials = [
                    'overview' => 'results.psle.partials.overview',
                    'processing' => 'results.psle.partials.processing',
                    'summary' => 'results.psle.partials.processing',
                    'candidate-results' => 'results.psle.partials.candidate-results',
                    'candidates' => 'results.psle.partials.candidate-results',
                    'school-results' => 'results.psle.partials.school-results',
                    'schools' => 'results.psle.partials.school-results',
                    'school' => 'results.psle.partials.school-results',
                    'district-results' => 'results.psle.partials.district-results',
                    'districts' => 'results.psle.partials.district-results',
                    'regional-results' => 'results.psle.partials.regional-results',
                    'subject-performance' => 'results.psle.partials.subject-performance',
                    'reports' => 'results.psle.partials.reports',
                    'audit' => 'results.psle.partials.audit',
                ];

                $partial = $partials[$view] ?? 'results.psle.partials.processing';
            @endphp

            @include($partial)
        </div>
    </main>
</div>

</body>
</html>
