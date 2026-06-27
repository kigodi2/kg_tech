<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PSLE Results Administration Portal - Reports</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        .btn-logout { margin-top: auto; padding: 20px; }

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
        .adm-user-avatar { width: 36px; height: 36px; background: linear-gradient(135deg, var(--tz-blue), #005a7d); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #fff; font-size: 0.9rem; }
        
        .adm-content {
            padding: 40px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            box-sizing: border-box;
        }

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
            overflow: hidden;
        }
        .adm-card:hover {
            box-shadow: 0 12px 32px rgba(0,0,0,.4);
            border-color: rgba(187,164,94,.16);
        }
        .adm-card-head { padding: 18px 22px; border-bottom: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center; gap: 14px; flex-wrap: wrap; }
        .adm-card-title { font-size: 1.1rem; font-weight: 700; color: #fff; }
        .adm-card-body { padding: 20px; }

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

        .adm-input {
            width: 100%; height: 40px; padding: 0 12px;
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 8px; color: #fff; font-family: inherit; font-size: 0.85rem;
            outline: none; transition: border-color .2s, box-shadow .2s;
        }
        .adm-input:focus { border-color: var(--tz-blue); box-shadow: 0 0 0 3px rgba(0,163,221,.15); }

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
        .btn-warning { background: linear-gradient(135deg, var(--tz-gold), #8c6e26); color: #fff; box-shadow: 0 4px 12px rgba(187,164,94,.3); }
        .btn-warning:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(187,164,94,.4); }
        .btn-danger { background: linear-gradient(135deg, #ef4444, #b91c1c); color: #fff; box-shadow: 0 4px 12px rgba(239,68,68,.3); }
        .btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(239,68,68,.4); }
        .btn-outline { background: rgba(255,255,255,.04); color: var(--tz-text); border: 1px solid rgba(255,255,255,.1); }
        .btn-outline:hover { background: rgba(255,255,255,.08); }
        .btn-action { background: rgba(0,163,221,0.1); color: var(--tz-blue); padding: 6px 12px; font-size: 0.8rem; }
        .btn-action:hover { background: rgba(0,163,221,0.2); }

        @media (max-width: 1024px) {
            .adm-sidebar { width: 80px; }
            .adm-brand-text, .adm-user-info, .adm-nav-item .nav-label { display: none; }
            .adm-nav-item { justify-content: center; padding: 12px; }
            .adm-main { margin-left: 80px; }
            .adm-stats { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .adm-sidebar { display: none; }
            .adm-main { margin-left: 0; }
            .adm-stats { grid-template-columns: 1fr; }
            .adm-content { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="adm-shell">
    
    <!-- Sidebar Navigation -->
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
            <a href="{{ route('results.psle.dashboard', ['view' => 'overview']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-chart-pie"></i></span>
                <span class="nav-label">Overview</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'processing']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-gears"></i></span>
                <span class="nav-label">Results Processing</span>
            </a>
            
            <div class="nav-section-label">Results Performance</div>
            <a href="{{ route('results.psle.dashboard', ['view' => 'candidate-results']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-user-graduate"></i></span>
                <span class="nav-label">Candidate Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'school-results']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-school"></i></span>
                <span class="nav-label">School Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'district-results']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-map-location-dot"></i></span>
                <span class="nav-label">District Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'regional-results']) }}" class="adm-nav-item">
                <span class="nav-ico"><i class="fa-solid fa-earth-africa"></i></span>
                <span class="nav-label">Regional Results</span>
            </a>
            <a href="{{ route('results.psle.dashboard', ['view' => 'subject-performance']) }}" class="adm-nav-item">
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
            <a href="{{ route('results.psle.reports.index') }}" class="adm-nav-item active">
                <span class="nav-ico"><i class="fa-solid fa-file-pdf"></i></span>
                <span class="nav-label">PDF Reports Exporter</span>
            </a>

            <div class="nav-section-label">Security & Audit</div>
            <a href="{{ route('results.psle.dashboard', ['view' => 'audit']) }}" class="adm-nav-item">
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

    <!-- Main Workspace Area -->
    <main class="adm-main">
        
        <!-- Top Control Bar -->
        <header class="adm-topbar">
            <div class="adm-top-title">
                PSLE RESULTS ADMINISTRATION CONTROL CENTRE
            </div>
            
            <div class="adm-top-user">
                <!-- Year Selector Form -->
                <form action="{{ route('results.psle.reports.index') }}" method="GET" style="display: flex; gap: 8px; align-items: center; margin-right: 15px;">
                    @if(request()->filled('region_id'))
                        <input type="hidden" name="region_id" value="{{ request('region_id') }}">
                    @endif
                    @if(request()->filled('district_id'))
                        <input type="hidden" name="district_id" value="{{ request('district_id') }}">
                    @endif
                    @if(request()->filled('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    <select name="exam_year_id" onchange="this.form.submit()" class="adm-select" style="width: auto; min-width: 100px; height: 36px; padding: 0 10px 0 8px;">
                        @foreach($examYears as $y)
                            <option value="{{ $y->id }}" {{ $examYear->id == $y->id ? 'selected' : '' }}>
                                {{ $y->year_label }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <div class="adm-user-info">
                    <span class="adm-user-name">{{ auth()->user()->name ?? 'Administrator' }}</span>
                    <span class="adm-user-role">System Admin</span>
                </div>
                <div class="adm-user-avatar">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
            </div>
        </header>

        <!-- View Content Inside Main Body -->
        <div class="adm-content">
            
            <!-- Breadcrumbs -->
            <div class="adm-breadcrumb">
                Admin Dashboard <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i> PSLE Results <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i> <span>Reports</span>
            </div>

            <!-- Page Title -->
            <div class="adm-page-header">
                <h1 class="adm-page-title">Official PDF Reports & ZIP Exporter</h1>
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

            <!-- Main Workspace View Partial -->
            @include('results.psle.partials.reports')
        </div>
    </main>
</div>

</body>
</html>
