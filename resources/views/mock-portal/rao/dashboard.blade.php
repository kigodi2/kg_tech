<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAO Control Centre - {{ $region->name ?? 'Regional' }} Region</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
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
        .adm-brand { display: flex; align-items: center; gap: 12px; }
        .adm-brand-logo { width: 40px !important; height: 40px !important; max-width: 40px !important; max-height: 40px !important; object-fit: contain; flex-shrink: 0; }
        .adm-brand-text { font-weight: 800; font-size: 1.1rem; color: #fff; letter-spacing: -0.5px; line-height: 1.1; }
        .adm-brand-text span { color: var(--tz-yellow); font-size: 0.65rem; display: block; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }

        .adm-nav { padding: 20px 12px; flex: 1; }
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

        .adm-content { padding: 40px; max-width: 1400px; margin: 0; width: 100%; box-sizing: border-box; }

        /* Stats Cards */
        .adm-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .adm-stat {
            background: linear-gradient(160deg, rgba(255,255,255,.03), rgba(255,255,255,.01));
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            padding: 24px;
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
        .adm-stat-value { font-size: 2.2rem; font-weight: 800; color: #fff; margin-bottom: 6px; letter-spacing: -1px; }
        .adm-stat-desc { font-size: 0.75rem; color: rgba(255,255,255,.55); }
        .adm-stat-icon { position: absolute; top: 18px; right: 18px; font-size: 1.35rem; color: rgba(255,255,255,0.14); }
        .adm-stats .adm-stat:nth-child(1){background:linear-gradient(135deg,#111416,#161b1f);border-color:rgba(0,163,221,.15);}
        .adm-stats .adm-stat:nth-child(2){background:linear-gradient(135deg,#003d52,#004f6b);}
        .adm-stats .adm-stat:nth-child(3){background:linear-gradient(135deg,#0a3012,#0e3d17);}
        .adm-stats .adm-stat:nth-child(4){background:linear-gradient(135deg,#3a2e00,#453600);}

        .adm-card {
            background: var(--tz-card);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 14px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform .2s, box-shadow .2s, border-color .2s;
        }
        .adm-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(0,0,0,.5);
            border-color: rgba(187,164,94,.16);
        }
        .adm-card-head { padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
        .adm-card-title { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .adm-card-body { padding: 0; }

        .adm-table-tools {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;
            width: min(100%, 520px);
            flex-wrap: wrap;
        }
        .adm-search-box {
            flex: 1 1 260px;
            position: relative;
            min-width: 220px;
        }
        .adm-search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--tz-text-muted);
            font-size: 0.82rem;
            pointer-events: none;
        }
        .adm-search-input {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 42px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 0.88rem;
            transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .adm-search-input:focus {
            border-color: rgba(0,163,221,.45);
            box-shadow: 0 0 0 3px rgba(0,163,221,.12);
            background: rgba(255,255,255,.045);
        }
        .adm-search-input::placeholder { color: rgba(156,163,175,.9); }
        .adm-search-btn,
        .adm-clear-btn {
            height: 42px;
            padding: 0 16px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.08);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.84rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
            white-space: nowrap;
        }
        .adm-search-btn {
            background: linear-gradient(135deg, rgba(0,163,221,.22), rgba(0,163,221,.34));
            color: #a8ecff;
        }
        .adm-search-btn:hover,
        .adm-clear-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0,0,0,.24);
        }
        .adm-clear-btn {
            background: rgba(255,255,255,.04);
            color: var(--tz-text);
        }

        .adm-pager-wrap {
            margin-top: 20px;
            border-top: 1px solid var(--tz-border);
            padding: 18px 20px;
            background: rgba(255,255,255,0.02);
            overflow-x: auto;
        }
        .adm-pager-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .adm-pager-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .adm-pager-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 32px;
            padding: 0 12px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.08);
            background: rgba(255,255,255,.03);
            color: var(--tz-text);
            font-size: 0.84rem;
            white-space: nowrap;
        }
        .adm-pager-chip i { color: var(--tz-text-muted); font-size: 0.78rem; }
        .adm-pager-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            min-width: max-content;
        }
        .adm-pager-btn,
        .adm-pager-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 38px;
            height: 38px;
            padding: 0 13px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,.08);
            text-decoration: none;
            color: var(--tz-text);
            background: rgba(255,255,255,.02);
            font-size: 0.85rem;
            font-weight: 700;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease, border-color .18s ease;
            white-space: nowrap;
        }
        .adm-pager-btn:hover,
        .adm-pager-num:hover {
            background: rgba(187,164,94,.12);
            color: #f0e6c8;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0,0,0,.24);
        }
        .adm-pager-btn.is-disabled {
            opacity: .35;
            pointer-events: none;
        }
        .adm-pager-num.is-active {
            background: var(--tz-blue);
            border-color: var(--tz-blue);
            color: #fff;
            box-shadow: 0 10px 22px rgba(0,163,221,.28);
        }

        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 16px 24px; font-size: 0.72rem; color: var(--tz-text-muted); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,.08); font-weight: 700; }
        td { padding: 16px 24px; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--tz-text); transition: background .15s ease, color .15s ease; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(187,164,94,.05); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; display: inline-block; }
        .badge-blue { background: rgba(0,163,221,0.15); color: #67d8ff; border: 1px solid rgba(0,163,221,0.25); }
        .badge-green { background: rgba(30,181,58,0.15); color: #6ae086; border: 1px solid rgba(30,181,58,0.25); }
        .badge-red { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.25); }

        .btn-logout { margin-top: auto; padding: 20px; border-top: 1px solid var(--tz-border); }
        
        /* Pagination Override */
        .pagination { display: flex; list-style: none; padding: 0; margin: 0; gap: 8px; }
        .page-item { border: 1px solid var(--tz-border); border-radius: 4px; overflow: hidden; }
        .page-link { display: block; padding: 8px 12px; color: var(--tz-text); text-decoration: none; font-size: 0.85rem; }
        .page-item.active .page-link { background: var(--tz-blue); border-color: var(--tz-blue); color: #fff; }

        .text-center { text-align: center; }

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
            .adm-top-title { font-size: 0.9rem; }
            .adm-table-tools { width: 100%; }
            .adm-search-box { min-width: 100%; }
            .adm-search-btn,
            .adm-clear-btn { flex: 1 1 140px; }
        }
    </style>
</head>
<body>
@php
    $mockPortalManual = [
        'manualId' => 'raoDashboardManual',
        'manualTitle' => 'RAO Portal Guide',
        'manualSummary' => 'This guide helps Regional Academic Officers review district performance, candidate quality, and regional correction workflows safely.',
        'manualPdf' => '/rao_guide.pdf',
        'manualSteps' => [
            ['title' => 'Start from the regional overview', 'body' => 'Review the top statistics to understand the regional position before opening candidate or school-level details.'],
            ['title' => 'Use filters and tabs carefully', 'body' => 'Move through the region, district, school, and candidate sections using the active tab and search tools so you review the correct records.'],
            ['title' => 'Correct or reject with clear intent', 'body' => 'When a record is wrong, either edit it directly or send it back for correction with a precise reason that lower levels can act on immediately.'],
            ['title' => 'Monitor data integrity issues', 'body' => 'Use flagged items and irregularity sections to trace incomplete, duplicate, or suspicious records before they affect final registration quality.'],
            ['title' => 'Drive deadline completion', 'body' => 'Follow up on districts and schools that are behind schedule so all required registration work is completed within the official window.'],
        ],
        'manualNotes' => [
            '<strong>Important:</strong> Regional actions should be well-documented so districts and schools can respond quickly and consistently.',
            '<strong>Download option:</strong> Use the RAO PDF guide for regional monitoring and coordination.'
        ],
    ];
@endphp
@php
    $buildVisiblePages = function ($paginator, int $radius = 2) {
        $current = max(1, (int) $paginator->currentPage());
        $last = max(1, (int) $paginator->lastPage());
        $start = max(1, $current - $radius);
        $end = min($last, $current + $radius);

        if (($end - $start) < ($radius * 2)) {
            $start = max(1, min($start, $last - ($radius * 2)));
            $end = min($last, max($end, 1 + ($radius * 2)));
        }

        return range($start, $end);
    };
@endphp

<div class="adm-shell">
    <!-- Sidebar -->
    <aside class="adm-sidebar">
        <div class="adm-sidebar-head">
            <div class="adm-brand">
                <div class="adm-brand-text">
                    TASIDO 2026
                    <span>Regional Control Centre</span>
                </div>
            </div>
        </div>
        
        <nav class="adm-nav">
            <a href="?tab=overview" class="adm-nav-item {{ $tab === 'overview' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-chart-line"></i></span><span class="nav-label">Overview</span>
            </a>
            <a href="?tab=districts" class="adm-nav-item {{ $tab === 'districts' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-city"></i></span><span class="nav-label">Districts (Councils)</span>
            </a>
            <a href="?tab=users" class="adm-nav-item {{ $tab === 'users' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-user-shield"></i></span><span class="nav-label">District Officers (DAOs)</span>
            </a>
            <a href="?tab=schools" class="adm-nav-item {{ $tab === 'schools' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-school"></i></span><span class="nav-label">School Directory</span>
            </a>
            <a href="?tab=candidates" class="adm-nav-item {{ $tab === 'candidates' ? 'active' : '' }}">
                <span class="nav-ico"><i class="fas fa-users-cog"></i></span><span class="nav-label">Candidate Management</span>
            </a>
            <a href="?tab=errors" class="adm-nav-item {{ $tab === 'errors' ? 'active' : '' }}" style="position:relative;">
                <span class="nav-ico"><i class="fas fa-triangle-exclamation"></i></span><span class="nav-label">Data Integrity</span>
                @if(isset($errorStats) && $errorStats['total'] > 0)
                    <span style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:#ef4444; color:#fff; font-size:0.65rem; font-weight:800; border-radius:10px; padding:2px 7px; min-width:20px; text-align:center;">{{ $errorStats['total'] }}</span>
                @endif
            </a>
        </nav>

        <div class="btn-logout">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="adm-nav-item" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left; font-family: inherit;">
                    <span class="nav-ico"><i class="fas fa-sign-out-alt"></i></span><span class="nav-label">Sign Out</span>
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
                {{ $region->name }} Region Dashboard
            </div>
            <div class="adm-top-user">
                <div class="adm-user-info">
                    <span class="adm-user-name">{{ $user->name }}</span>
                    <span class="adm-user-role">Regional Academic Officer</span>
                </div>
                <div class="adm-user-avatar">
                    {{ substr($user->name, 0, 1) }}
                </div>
            </div>
        </header>

        <div class="adm-content">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div style="background: rgba(30,181,58,0.1); border: 1px solid rgba(30,181,58,0.3); border-radius: 10px; padding: 14px 20px; margin-bottom: 24px; display:flex; align-items:center; gap:12px;">
                    <i class="fas fa-check-circle" style="color: var(--tz-green);"></i>
                    <span style="color: #6ae086; font-weight:600;">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 20px; margin-bottom: 24px; display:flex; align-items:center; gap:12px;">
                    <i class="fas fa-circle-exclamation" style="color: #fca5a5;"></i>
                    <span style="color: #fca5a5; font-weight:600;">{{ session('error') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; padding: 14px 20px; margin-bottom: 24px;">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;"><i class="fas fa-circle-exclamation" style="color: #fca5a5;"></i><span style="color: #fca5a5; font-weight:700;">Please fix the following errors:</span></div>
                    <ul style="margin:0; padding-left:20px; color: #fca5a5; font-size:0.85rem;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <!-- Stats -->
            <div class="adm-stats">
                @foreach($summaryCards as $card)
                    <div class="adm-stat">
                        <div class="adm-stat-label">{{ $card['label'] }}</div>
                        <div class="adm-stat-value" style="color: {{ $card['color'] }};">{{ $card['value'] }}</div>
                        <div class="adm-stat-desc">{{ $card['description'] }}</div>
                        <i class="{{ $card['icon'] }} adm-stat-icon"></i>
                    </div>
                @endforeach
            </div>

            @if($tab === 'overview' || $tab === 'districts')
            <!-- Districts Table -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">District PSLE Registration Status</div>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>District Code</th>
                                <th>District Name</th>
                                <th class="text-center">PSLE Schools</th>
                                <th class="text-center">Candidates (PSLE)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($districtStats as $ds)
                            <tr>
                                <td style="font-family: monospace;">{{ \App\Models\District::normalizeCodeForDisplay($ds->code) }}</td>
                                <td><strong>{{ $ds->name }}</strong></td>
                                <td class="text-center">{{ $ds->schools_count }}</td>
                                <td class="text-center">{{ number_format($ds->candidates_count) }}</td>
                                <td>
                                    @if($ds->candidates_count > 0)
                                        <span class="badge badge-green">In Progress</span>
                                    @else
                                        <span class="badge badge-blue">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            @if($tab === 'users')
            <!-- DAO Users Table -->
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">District Administrative Officers (DAOs)</div>
                    <form action="" method="GET" class="adm-table-tools">
                        <input type="hidden" name="tab" value="users">
                        <div class="adm-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name or email..." class="adm-search-input">
                        </div>
                        <button type="submit" class="adm-search-btn">Search</button>
                        @if(isset($search) && $search)
                            <a href="?tab=users" class="adm-clear-btn"><i class="fas fa-times"></i> Clear</a>
                        @endif
                    </form>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>District</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daoUsers as $idx => $dao)
                            <tr>
                                <td>{{ ($daoUsers->currentPage() - 1) * $daoUsers->perPage() + $idx + 1 }}</td>
                                <td><strong>{{ $dao->name }}</strong></td>
                                <td>{{ $dao->email }}</td>
                                <td>{{ $dao->council->name ?? 'N/A' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-green">Active</span>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn dao-manage-trigger"
                                        style="background: rgba(0,163,221,0.1); color: var(--tz-blue); border: none; padding: 4px 10px; cursor: pointer; position: relative; z-index: 2;"
                                        data-dao-name="{{ e($dao->name) }}"
                                        data-dao-email="{{ e($dao->email) }}"
                                        data-dao-district="{{ e($dao->council->name ?? 'N/A') }}"
                                    >Manage</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="padding: 40px; text-align: center; color: var(--tz-text-muted);">
                                    No DAO users found in this region.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($daoUsers->lastPage() > 1)
                        @php($userPages = $buildVisiblePages($daoUsers))
                        <div class="adm-pager-wrap">
                            <div class="adm-pager-row">
                                <div class="adm-pager-meta">
                                    <span class="adm-pager-chip"><i class="fas fa-layer-group"></i> Page {{ $daoUsers->currentPage() }} of {{ $daoUsers->lastPage() }}</span>
                                    <span class="adm-pager-chip"><i class="fas fa-table-list"></i> Showing {{ $daoUsers->count() }} of {{ $daoUsers->total() }} users</span>
                                </div>
                                <div class="adm-pager-nav">
                                    <a href="{{ $daoUsers->url(1) }}&tab=users" class="adm-pager-btn {{ $daoUsers->onFirstPage() ? 'is-disabled' : '' }}"><i class="fas fa-angles-left"></i></a>
                                    @foreach($userPages as $p)
                                        <a href="{{ $daoUsers->url($p) }}&tab=users" class="adm-pager-num {{ $daoUsers->currentPage() === $p ? 'is-active' : '' }}">{{ $p }}</a>
                                    @endforeach
                                    <a href="{{ $daoUsers->url($daoUsers->lastPage()) }}&tab=users" class="adm-pager-btn {{ $daoUsers->hasMorePages() ? '' : 'is-disabled' }}"><i class="fas fa-angles-right"></i></a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($tab === 'schools')
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">School Directory & Registration</div>
                    <form action="" method="GET" class="adm-table-tools">
                        <input type="hidden" name="tab" value="schools">
                        <div class="adm-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by centre name or number..." class="adm-search-input">
                        </div>
                        <button type="submit" class="adm-search-btn">Search</button>
                        @if(isset($search) && $search)
                            <a href="?tab=schools" class="adm-clear-btn"><i class="fas fa-times"></i> Clear</a>
                        @endif
                    </form>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Centre No.</th>
                                <th>PSLE Centre Name</th>
                                <th>Ownership</th>
                                <th>District</th>
                                <th class="text-center">CANDIDATE</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schoolsList as $sch)
                            <tr>
                                <td style="font-family: monospace;">{{ $sch->code }}</td>
                                <td><strong>{{ $sch->name }}</strong></td>
                                <td>{{ $sch->ownership ?? 'N/A' }}</td>
                                <td>{{ $sch->council->name ?? $sch->district->name ?? 'N/A' }}</td>
                                <td class="text-center">{{ number_format($sch->candidates_count) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-green">Registered</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="padding: 40px; text-align: center; color: var(--tz-text-muted);">
                                    No schools found in this region.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($schoolsList->lastPage() > 1)
                        @php($schPages = $buildVisiblePages($schoolsList))
                        <div class="adm-pager-wrap">
                            <div class="adm-pager-row">
                                <div class="adm-pager-meta">
                                    <span class="adm-pager-chip"><i class="fas fa-layer-group"></i> Page {{ $schoolsList->currentPage() }} of {{ $schoolsList->lastPage() }}</span>
                                    <span class="adm-pager-chip"><i class="fas fa-school"></i> Showing {{ $schoolsList->count() }} of {{ $schoolsList->total() }} schools</span>
                                </div>
                                <div class="adm-pager-nav">
                                    <a href="{{ $schoolsList->url(1) }}&tab=schools" class="adm-pager-btn {{ $schoolsList->onFirstPage() ? 'is-disabled' : '' }}"><i class="fas fa-angles-left"></i></a>
                                    @foreach($schPages as $p)
                                        <a href="{{ $schoolsList->url($p) }}&tab=schools" class="adm-pager-num {{ $schoolsList->currentPage() === $p ? 'is-active' : '' }}">{{ $p }}</a>
                                    @endforeach
                                    <a href="{{ $schoolsList->url($schoolsList->lastPage()) }}&tab=schools" class="adm-pager-btn {{ $schoolsList->hasMorePages() ? '' : 'is-disabled' }}"><i class="fas fa-angles-right"></i></a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($tab === 'errors')
            <!-- Auto-Detected Issues -->
            <div class="adm-card" style="border-left: 4px solid #ef4444;">
                <div class="adm-card-head">
                    <div class="adm-card-title">Auto-Detected Issues ({{ $errorStats['total'] }})</div>
                    <div style="display: flex; gap: 10px;">
                        <span class="badge badge-red">{{ $errorStats['critical'] }} Critical</span>
                        <span class="badge badge-blue">{{ $errorStats['warning'] }} Warnings</span>
                    </div>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Centre No.</th>
                                <th>School</th>
                                <th>Description</th>
                                <th>Recommendation</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($detectedErrors as $error)
                            <tr>
                                <td>
                                    @if($error['type'] === 'Critical')
                                        <span class="badge badge-red">CRITICAL</span>
                                    @else
                                        <span class="badge badge-blue">WARNING</span>
                                    @endif
                                </td>
                                <td style="font-family: monospace;">{{ $error['centre_number'] ?? 'N/A' }}</td>
                                <td><strong>{{ $error['school'] }}</strong></td>
                                <td style="font-size: 0.85rem;">{{ $error['description'] }}</td>
                                <td style="color: var(--tz-text-muted); font-size: 0.8rem;">{{ $error['action'] }}</td>
                                <td>
                                    <button
                                        type="button"
                                        onclick='handleErrorNotify(@json($error))'
                                        class="btn"
                                        style="background: rgba(239,68,68,0.1); color: #fca5a5; border: none; padding: 4px 8px; cursor: pointer;"
                                    >
                                        Notify
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" style="padding: 30px; text-align: center; color: var(--tz-text-muted);">No system errors detected.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($detectedErrors->lastPage() > 1)
                        @php($detectedErrorPages = $buildVisiblePages($detectedErrors))
                        <div class="adm-pager-wrap">
                            <div class="adm-pager-row">
                                <div class="adm-pager-meta">
                                    <span class="adm-pager-chip"><i class="fas fa-layer-group"></i> Page {{ $detectedErrors->currentPage() }} of {{ $detectedErrors->lastPage() }}</span>
                                    <span class="adm-pager-chip"><i class="fas fa-triangle-exclamation"></i> Showing {{ $detectedErrors->count() }} of {{ $detectedErrors->total() }} issues</span>
                                </div>
                                <div class="adm-pager-nav">
                                    <a href="{{ $detectedErrors->url(1) }}&tab=errors" class="adm-pager-btn {{ $detectedErrors->onFirstPage() ? 'is-disabled' : '' }}"><i class="fas fa-angles-left"></i></a>
                                    @foreach($detectedErrorPages as $p)
                                        <a href="{{ $detectedErrors->url($p) }}&tab=errors" class="adm-pager-num {{ $detectedErrors->currentPage() === $p ? 'is-active' : '' }}">{{ $p }}</a>
                                    @endforeach
                                    <a href="{{ $detectedErrors->url($detectedErrors->lastPage()) }}&tab=errors" class="adm-pager-btn {{ $detectedErrors->hasMorePages() ? '' : 'is-disabled' }}"><i class="fas fa-angles-right"></i></a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">Regional Rejections & Corrections</div>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>School</th>
                                <th>Status</th>
                                <th>Rejection Reason</th>
                                <th>Last Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rejectionsList as $err)
                            <tr>
                                <td><strong>{{ $err->full_name }}</strong></td>
                                <td>{{ $err->school->name }}</td>
                                <td>
                                    @if($err->status === 'rejected')
                                        <span class="badge badge-red">Rejected</span>
                                    @else
                                        <span class="badge badge-green">Corrected</span>
                                    @endif
                                </td>
                                <td style="font-size: 0.8rem; color: var(--tz-text-muted);">{{ $err->rejection_reason }}</td>
                                <td style="font-size: 0.8rem;">{{ $err->updated_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="padding: 40px; text-align: center; color: var(--tz-text-muted);">
                                    No rejected records found in the region.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($rejectionsList->lastPage() > 1)
                        @php($errPages = $buildVisiblePages($rejectionsList))
                        <div class="adm-pager-wrap">
                            <div class="adm-pager-row">
                                <div class="adm-pager-meta">
                                    <span class="adm-pager-chip"><i class="fas fa-layer-group"></i> Page {{ $rejectionsList->currentPage() }} of {{ $rejectionsList->lastPage() }}</span>
                                    <span class="adm-pager-chip"><i class="fas fa-rotate-left"></i> Showing {{ $rejectionsList->count() }} of {{ $rejectionsList->total() }} records</span>
                                </div>
                                <div class="adm-pager-nav">
                                    <a href="{{ $rejectionsList->url(1) }}&tab=errors" class="adm-pager-btn {{ $rejectionsList->onFirstPage() ? 'is-disabled' : '' }}"><i class="fas fa-angles-left"></i></a>
                                    @foreach($errPages as $p)
                                        <a href="{{ $rejectionsList->url($p) }}&tab=errors" class="adm-pager-num {{ $rejectionsList->currentPage() === $p ? 'is-active' : '' }}">{{ $p }}</a>
                                    @endforeach
                                    <a href="{{ $rejectionsList->url($rejectionsList->lastPage()) }}&tab=errors" class="adm-pager-btn {{ $rejectionsList->hasMorePages() ? '' : 'is-disabled' }}"><i class="fas fa-angles-right"></i></a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($tab === 'candidates')
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title">Regional Candidate Management</div>
                    <form action="" method="GET" class="adm-table-tools">
                        <input type="hidden" name="tab" value="candidates">
                        <div class="adm-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search by name, index number or PReM..." class="adm-search-input">
                        </div>
                        <button type="submit" class="adm-search-btn">Search</button>
                        @if(isset($search) && $search)
                            <a href="?tab=candidates" class="adm-clear-btn"><i class="fas fa-times"></i> Clear</a>
                        @endif
                    </form>
                </div>
                <div class="adm-card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Index Number</th>
                                <th>PReM No.</th>
                                <th>Full Name</th>
                                <th class="text-center">Sex</th>
                                <th>School</th>
                                <th>District</th>
                                <th class="text-center">Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidatesList as $idx => $cand)
                            <tr>
                                <td>{{ ($candidatesList->currentPage() - 1) * $candidatesList->perPage() + $idx + 1 }}</td>
                                <td style="font-family: monospace; font-weight: 700;">{{ $cand->candidate_id }}</td>
                                <td style="font-family: monospace; color: var(--tz-blue);">{{ $cand->prem_no ?: '---' }}</td>
                                <td><strong>{{ $cand->full_name }}</strong></td>
                                <td class="text-center">{{ $cand->gender }}</td>
                                <td style="font-size: 0.85rem;">{{ $cand->school->name }}</td>
                                <td style="font-size: 0.85rem;">{{ $cand->school->council->name ?? 'N/A' }}</td>
                                <td class="text-center">
                                    @if($cand->status === 'rejected')
                                        <span class="badge badge-red">Rejected</span>
                                    @else
                                        <span class="badge badge-green">Registered</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="viewCandidate({{ json_encode($cand->load('school.district')) }})" class="btn" style="padding: 4px 8px; background: rgba(0,163,221,0.1); color: var(--tz-blue); border: none; cursor:pointer;" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="openEditModal({{ json_encode($cand) }})" class="btn" style="padding: 4px 8px; background: rgba(252,209,22,0.1); color: var(--tz-yellow); border: none; cursor:pointer;" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="openRejectModal({{ $cand->id }}, '{{ addslashes($cand->full_name) }}')" class="btn" style="padding: 4px 8px; background: rgba(239,68,68,0.1); color: #fca5a5; border: none; cursor:pointer;" title="Reject">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <form action="{{ route('mock-portal.rao.candidate.destroy', $cand->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this candidate?')" style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn" style="padding: 4px 8px; background: rgba(239,68,68,0.1); color: #fca5a5; border: none; cursor:pointer;" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" style="padding: 40px; text-align: center; color: var(--tz-text-muted);">No candidates found in this region.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @if($candidatesList->lastPage() > 1)
                        @php($canPages = $buildVisiblePages($candidatesList))
                        <div class="adm-pager-wrap">
                            <div class="adm-pager-row">
                                <div class="adm-pager-meta">
                                    <span class="adm-pager-chip"><i class="fas fa-layer-group"></i> Page {{ $candidatesList->currentPage() }} of {{ $candidatesList->lastPage() }}</span>
                                    <span class="adm-pager-chip"><i class="fas fa-users"></i> Showing {{ $candidatesList->count() }} of {{ $candidatesList->total() }} candidates</span>
                                </div>
                                <div class="adm-pager-nav">
                                    <a href="{{ $candidatesList->url(1) }}&tab=candidates" class="adm-pager-btn {{ $candidatesList->onFirstPage() ? 'is-disabled' : '' }}"><i class="fas fa-angles-left"></i></a>
                                    @foreach($canPages as $p)
                                        <a href="{{ $candidatesList->url($p) }}&tab=candidates" class="adm-pager-num {{ $candidatesList->currentPage() === $p ? 'is-active' : '' }}">{{ $p }}</a>
                                    @endforeach
                                    <a href="{{ $candidatesList->url($candidatesList->lastPage()) }}&tab=candidates" class="adm-pager-btn {{ $candidatesList->hasMorePages() ? '' : 'is-disabled' }}"><i class="fas fa-angles-right"></i></a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </main>
</div>

{{-- Modals --}}
<div id="viewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center; color: #fff;">
    <div style="background:#1a222c; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:400px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;">Candidate Details</h3>
            <button onclick="closeViewModal()" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        <div id="viewContent" style="padding:24px;"></div>
        <div style="padding:16px; background:rgba(255,255,255,0.02); display:flex; justify-content:center;">
            <button onclick="closeViewModal()" style="padding: 8px 16px; background: #374151; border: none; border-radius: 4px; color: #fff; cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center; color: #fff;">
    <div style="background:#1a222c; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:450px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;">Edit Candidate</h3>
            <button onclick="closeEditModal()" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        <form id="editForm" method="POST" style="padding:24px;">
            @csrf
            @method('PUT')
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; margin-bottom:8px;">Examination Number</label>
                <input type="text" name="candidate_id" id="edit_candidate_id" required pattern="PS[0-9]{7}-[0-9]{4}" style="width:100%; padding:10px; background:#111827; border:1px solid #374151; border-radius:8px; color:#fff; outline:none; font-family: inherit;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; margin-bottom:8px;">Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required style="width:100%; padding:10px; background:#111827; border:1px solid #374151; border-radius:8px; color:#fff; outline:none; font-family: inherit;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; margin-bottom:8px;">Sex</label>
                <select name="gender" id="edit_gender" required style="width:100%; padding:10px; background:#111827; border:1px solid #374151; border-radius:8px; color:#fff; outline:none; font-family: inherit;">
                    <option value="M">M</option>
                    <option value="F">F</option>
                </select>
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; margin-bottom:8px;">PReM Number</label>
                <input type="text" name="prem_no" id="edit_prem_no" required pattern="[0-9]{11}" style="width:100%; padding:10px; background:#111827; border:1px solid #374151; border-radius:8px; color:#fff; outline:none; font-family: inherit;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" onclick="closeEditModal()" style="background:none; border:none; color:#9ca3af; font-weight:700; cursor:pointer; font-family: inherit;">Cancel</button>
                <button type="submit" style="padding: 8px 16px; background: var(--tz-blue); border: none; border-radius: 4px; color: #fff; cursor: pointer;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center; color: #fff;">
    <div style="background:#1a222c; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:450px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
            <h3 id="reject_modal_title" style="margin:0; font-size:1.1rem;">Reject Candidate</h3>
            <button onclick="closeRejectModal()" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('mock-portal.rao.candidate.reject') }}" method="POST" style="padding:24px;">
            @csrf
            <input type="hidden" name="id" id="reject_candidate_id">
            <div style="margin-bottom:16px;">
                <p id="reject_candidate_name" style="font-weight:700; color:var(--tz-yellow); margin-bottom:15px;"></p>
                <label style="display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; margin-bottom:8px;">Reason for Rejection</label>
                <textarea id="reject_reason" name="reason" required placeholder="e.g., Incorrect PREM number or misaligned data..." style="width:100%; padding:10px; background:#111827; border:1px solid #374151; border-radius:8px; color:#fff; outline:none; font-family: inherit; min-height: 100px;"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="button" onclick="closeRejectModal()" style="background:none; border:none; color:#9ca3af; font-weight:700; cursor:pointer; font-family: inherit;">Cancel</button>
                <button id="reject_submit_button" type="submit" style="padding: 8px 16px; background: #ef4444; border: none; border-radius: 4px; color: #fff; cursor: pointer;">Reject Candidate</button>
            </div>
        </form>
    </div>
</div>

<div id="daoManageModal" role="dialog" aria-modal="true" aria-labelledby="dao_manage_heading" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1600; align-items:center; justify-content:center; color: #fff;">
    <div class="dao-manage-dialog" style="background:#1a222c; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:440px; overflow:hidden;">
        <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
            <h3 id="dao_manage_heading" style="margin:0; font-size:1.1rem;">District Officer (DAO)</h3>
            <button type="button" onclick="closeDaoManageModal()" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:24px;">
            <div style="display:grid; gap:14px;">
                <div><small style="color:#9ca3af;">Name</small><div id="dao_manage_name" style="font-weight:700; margin-top:4px;"></div></div>
                <div><small style="color:#9ca3af;">Email</small><div id="dao_manage_email" style="font-weight:500; margin-top:4px; word-break:break-all;"></div></div>
                <div><small style="color:#9ca3af;">District</small><div id="dao_manage_district" style="margin-top:4px;"></div></div>
            </div>
            <div style="margin-top:22px; display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end; align-items:center;">
                <button type="button" id="dao_contact_email_btn" style="padding: 8px 14px; background: var(--tz-blue); border: none; border-radius: 8px; color: #fff; font-weight: 600; font-size: 0.875rem; cursor: pointer; font-family: inherit;">
                    Contact by email
                </button>
                <button type="button" onclick="closeDaoManageModal()" style="padding: 8px 14px; background: #374151; border: none; border-radius: 8px; color: #fff; cursor: pointer; font-weight: 600;">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewCandidate(cand) {
        const content = `
            <div style="display:grid; gap:16px;">
                <div><small style="color:#9ca3af;">Index Number</small><div style="font-weight:700; color:var(--tz-blue);">${cand.candidate_id}</div></div>
                <div><small style="color:#9ca3af;">Full Name</small><div style="font-weight:700;">${cand.full_name}</div></div>
                <div><small style="color:#9ca3af;">Sex / Gender</small><div>${cand.gender === 'M' ? 'Male' : 'Female'}</div></div>
                <div><small style="color:#9ca3af;">PReM Number</small><div style="font-family:monospace;">${cand.prem_no || '---'}</div></div>
                <div><small style="color:#9ca3af;">School</small><div>${cand.school.name}</div></div>
                <div><small style="color:#9ca3af;">District</small><div>${cand.school.council ? cand.school.council.name : 'N/A'}</div></div>
                <div><small style="color:#9ca3af;">Status</small><div>${cand.status.toUpperCase()}</div></div>
            </div>
        `;
        document.getElementById('viewContent').innerHTML = content;
        document.getElementById('viewModal').style.display = 'flex';
    }
    function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }

    function openEditModal(cand) {
        document.getElementById('edit_candidate_id').value = cand.candidate_id;
        document.getElementById('edit_full_name').value = cand.full_name;
        document.getElementById('edit_gender').value = cand.gender;
        document.getElementById('edit_prem_no').value = cand.prem_no || '';
        document.getElementById('editForm').action = `/mock-portal/rao/candidate/${cand.id}`;
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

    function openRejectModal(id, name) {
        document.getElementById('reject_candidate_id').value = id;
        document.getElementById('reject_candidate_name').innerText = "Rejecting: " + name;
        document.getElementById('reject_modal_title').innerText = 'Reject Candidate';
        document.getElementById('reject_submit_button').innerText = 'Reject Candidate';
        document.getElementById('reject_reason').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
    }
    function closeRejectModal() { document.getElementById('rejectModal').style.display = 'none'; }

    function openDaoManageModal(info) {
        var modal = document.getElementById('daoManageModal');
        if (!modal) return;
        document.getElementById('dao_manage_name').textContent = info.name || '';
        document.getElementById('dao_manage_email').textContent = info.email || '';
        document.getElementById('dao_manage_district').textContent = info.district || 'N/A';
        var email = (info.email || '').trim().replace(/^mailto:/i, '');
        var subj = encodeURIComponent('IRMS Mock Portal — DAO account');
        modal.dataset.mailtoUrl = email ? ('mailto:' + email + '?subject=' + subj) : '';
        modal.style.display = 'flex';
    }

    function triggerDaoContactEmail() {
        var modal = document.getElementById('daoManageModal');
        var url = modal && modal.dataset.mailtoUrl;
        if (!url) return;
        window.location.href = url;
    }

    function closeDaoManageModal() {
        var modal = document.getElementById('daoManageModal');
        if (modal) modal.style.display = 'none';
    }

    document.addEventListener('click', function(ev) {
        var btn = ev.target.closest('.dao-manage-trigger');
        if (!btn) return;
        ev.preventDefault();
        ev.stopPropagation();
        openDaoManageModal({
            name: btn.getAttribute('data-dao-name') || '',
            email: btn.getAttribute('data-dao-email') || '',
            district: btn.getAttribute('data-dao-district') || 'N/A'
        });
    });

    document.getElementById('daoManageModal')?.addEventListener('click', function(ev) {
        if (ev.target === this) closeDaoManageModal();
    });
    document.getElementById('dao_contact_email_btn')?.addEventListener('click', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        triggerDaoContactEmail();
    });

    function handleErrorNotify(error) {
        if (error.category === 'Candidate') {
            document.getElementById('reject_candidate_id').value = error.id;
            document.getElementById('reject_candidate_name').innerText = 'Notify / request correction for: ' + (error.target_name || error.school || 'Candidate');
            document.getElementById('reject_modal_title').innerText = 'Notify Candidate Correction';
            document.getElementById('reject_submit_button').innerText = 'Send Back for Correction';
            document.getElementById('reject_reason').value = error.description || 'Please review and correct this candidate record.';
            document.getElementById('rejectModal').style.display = 'flex';
            return;
        }

        if (error.category === 'School') {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'schools');
            url.searchParams.set('search', error.search_term || error.school || '');
            window.location.href = url.toString();
        }
    }

    // Sidebar Toggle for Mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        document.querySelector('.adm-sidebar').classList.toggle('open');
    });
</script>
@include('mock-portal.partials.user-manual', $mockPortalManual)
</body>
</html>
