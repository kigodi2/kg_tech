<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PSLE Public Results')</title>
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <style>
        :root { --irms-font: 'Maiandra GD', "Ubuntu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        html, body, body * { font-family: var(--irms-font) !important; }
        input, button, select, textarea { font-family: var(--irms-font) !important; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #0f172a;
            background:
                radial-gradient(circle at top right, rgba(59,130,246,0.14), transparent 24%),
                radial-gradient(circle at bottom left, rgba(16,185,129,0.12), transparent 28%),
                #f8fafc;
        }
        a { color: inherit; text-decoration: none; }
        .page-shell { min-height: 100vh; }
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            backdrop-filter: blur(12px);
            background: rgba(255,255,255,0.9);
            border-bottom: 1px solid rgba(148,163,184,0.2);
            box-shadow: 0 10px 30px rgba(15,23,42,0.05);
        }
        .topbar-inner,
        .content {
            width: min(1240px, calc(100vw - 32px));
            margin: 0 auto;
        }
        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 0;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            letter-spacing: 0.24em;
            font-size: 12px;
            font-weight: 800;
            color: #2563eb;
        }
        .eyebrow-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, #10b981, #34d399);
        }
        .page-title {
            margin: 10px 0 0;
            font-size: clamp(28px, 4vw, 44px);
            line-height: 1.05;
            font-weight: 900;
            color: #0f172a;
        }
        .page-copy {
            margin: 8px 0 0;
            max-width: 720px;
            font-size: 15px;
            color: #475569;
        }
        .top-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
        }
        .top-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border-radius: 14px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 800;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }
        .top-btn:hover { transform: translateY(-1px); }
        .top-btn.secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }
        .top-btn.primary {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #fff;
            box-shadow: 0 16px 34px rgba(15,23,42,.16);
        }
        .content { padding: 28px 0 40px; }
        .hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 34px;
            color: #fff;
            background: linear-gradient(135deg, #020617 0%, #0f172a 35%, #1d4ed8 100%);
            box-shadow: 0 28px 50px rgba(15,23,42,0.16);
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(96,165,250,0.32), transparent 28%),
                radial-gradient(circle at bottom left, rgba(52,211,153,0.20), transparent 25%);
            pointer-events: none;
        }
        .hero-grid {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.85fr);
            gap: 24px;
            align-items: stretch;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 999px;
            background: rgba(255,255,255,.10);
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: #dbeafe;
        }
        .hero h2 {
            margin: 18px 0 0;
            font-size: clamp(30px, 4vw, 52px);
            line-height: 1.02;
            font-weight: 900;
        }
        .hero p {
            margin: 16px 0 0;
            max-width: 700px;
            font-size: 16px;
            line-height: 1.8;
            color: rgba(255,255,255,.85);
        }
        .hero-panel {
            display: grid;
            gap: 14px;
        }
        .glass-card {
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 24px;
            padding: 22px;
            background: rgba(255,255,255,.10);
            backdrop-filter: blur(10px);
        }
        .glass-card small {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: #bfdbfe;
            font-weight: 800;
        }
        .glass-card strong {
            display: block;
            margin-top: 10px;
            font-size: 28px;
            line-height: 1;
        }
        .glass-card span {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            color: rgba(255,255,255,.84);
            line-height: 1.6;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 22px;
        }
        .stat-card {
            border: 1px solid rgba(226,232,240,.95);
            border-radius: 26px;
            background: #fff;
            padding: 24px;
            box-shadow: 0 16px 35px rgba(148,163,184,.12);
        }
        .stat-card small {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        .stat-card strong {
            display: block;
            margin-top: 16px;
            font-size: 44px;
            line-height: 1;
            color: #0f172a;
        }
        .stat-card span {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
        }
        .toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            margin-top: 24px;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            background: rgba(255,255,255,.88);
            box-shadow: 0 18px 35px rgba(148,163,184,.10);
            padding: 22px;
        }
        .toolbar-left h3 {
            margin: 0;
            font-size: 24px;
            color: #0f172a;
        }
        .toolbar-left p {
            margin: 10px 0 0;
            font-size: 14px;
            line-height: 1.7;
            color: #64748b;
        }
        .toolbar-right {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: min(100%, 280px);
        }
        .search-row {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        .search-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 14px;
            color: #0f172a;
            background: #fff;
            box-shadow: inset 0 1px 2px rgba(15,23,42,.04);
        }
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,.12);
        }
        .search-btn {
            border: none;
            border-radius: 16px;
            padding: 14px 18px;
            font-size: 14px;
            font-weight: 800;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(37,99,235,.18);
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #2563eb;
            font-size: 14px;
            font-weight: 800;
        }
        .alpha-wrap {
            margin-top: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background: #fff;
            padding: 18px 20px;
            box-shadow: 0 16px 35px rgba(148,163,184,.08);
        }
        .alpha-title {
            display: block;
            margin-bottom: 14px;
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }
        .alpha-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .alpha-link {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #f8fafc;
            color: #334155;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 800;
            transition: all .16s ease;
        }
        .alpha-link.active,
        .alpha-link:hover {
            border-color: #2563eb;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-top: 22px;
        }
        .portal-card {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-height: 180px;
            overflow: hidden;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            padding: 22px;
            padding-top: 28px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 20px 35px rgba(148,163,184,.10);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }
        .portal-card:hover {
            transform: translateY(-4px);
            border-color: #bfdbfe;
            box-shadow: 0 28px 42px rgba(59,130,246,.12);
        }
        .portal-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-start, #2563eb);
        }
        .portal-card:nth-child(4n+1) { --accent-start: #2563eb; }
        .portal-card:nth-child(4n+1) .card-index { background: #eff6ff; }
        .portal-card:nth-child(4n+2) { --accent-start: #059669; }
        .portal-card:nth-child(4n+2) .card-index { background: #ecfdf5; }
        .portal-card:nth-child(4n+3) { --accent-start: #7c3aed; }
        .portal-card:nth-child(4n+3) .card-index { background: #f5f3ff; }
        .portal-card:nth-child(4n+4) { --accent-start: #ea580c; }
        .portal-card:nth-child(4n+4) .card-index { background: #fff7ed; }
        .card-index {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 900;
            color: var(--accent-start, #2563eb);
            background: #eff6ff;
        }
        .card-label {
            margin: 0;
            font-size: 20px;
            line-height: 1.35;
            font-weight: 900;
            color: #0f172a;
            text-transform: uppercase;
        }
        .card-copy {
            margin: 0;
            font-size: 14px;
            line-height: 1.7;
            color: #64748b;
        }
        .card-link {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 800;
            color: var(--accent-start, #2563eb);
        }
        .no-results,
        .empty-state {
            margin-top: 20px;
            border: 1px dashed #cbd5e1;
            border-radius: 24px;
            padding: 22px;
            text-align: center;
            font-size: 14px;
            color: #64748b;
            background: rgba(255,255,255,.75);
        }
        .no-results { display: none; }
        .result-sections {
            display: grid;
            gap: 18px;
            margin-top: 22px;
        }
        .result-card {
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 16px 35px rgba(148,163,184,.10);
            overflow: hidden;
        }
        .result-card-header {
            padding: 18px 22px;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .result-card-body {
            padding: 18px 22px 22px;
            overflow-x: auto;
        }
        .summary-stack {
            display: grid;
            gap: 10px;
            color: #334155;
            font-size: 15px;
            line-height: 1.7;
        }
        .summary-stack strong { color: #0f172a; }
        .summary-grade {
            font-weight: 900;
        }
        .table-shell {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }
        .table-shell th,
        .table-shell td {
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            font-size: 14px;
            vertical-align: top;
        }
        .table-shell th {
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: 800;
            text-align: left;
        }
        .table-shell td.center,
        .table-shell th.center {
            text-align: center;
        }
        @media (max-width: 1100px) {
            .hero-grid,
            .stats-grid,
            .toolbar,
            .cards-grid { grid-template-columns: 1fr 1fr; }
            .toolbar { grid-template-columns: 1fr; }
        }
        @media (max-width: 720px) {
            .topbar-inner,
            .search-row,
            .stats-grid,
            .cards-grid { grid-template-columns: 1fr; }
            .topbar-inner,
            .hero-grid { display: block; }
            .top-actions { justify-content: stretch; margin-top: 14px; }
            .top-btn { justify-content: center; width: 100%; }
            .hero,
            .toolbar,
            .alpha-wrap,
            .stat-card,
            .portal-card,
            .result-card-body { padding: 20px; }
            .toolbar-right { min-width: 100%; }
            .search-row { flex-direction: column; }
            .search-btn { width: 100%; }
            .table-shell { min-width: 720px; }
        }
    </style>
</head>
<body>
<div class="page-shell">
    <header class="topbar">
        <div class="topbar-inner">
            <div>
                <span class="eyebrow"><span class="eyebrow-dot"></span>@yield('eyebrow', 'PSLE Public Results Workspace')</span>
                <h1 class="page-title">@yield('page_title', 'PSLE Public Results')</h1>
                <p class="page-copy">@yield('page_copy', 'Browse the public PSLE hierarchy and open the exact result page you need with the same frame and presentation style as the portal workspace.')</p>
            </div>
            <div class="top-actions">
                @yield('top_actions')
            </div>
        </div>
    </header>

    <main class="content">
        <section class="hero">
            <div class="hero-grid">
                <div>
                    <span class="hero-badge">@yield('hero_badge', 'Professional Reporting Portal')</span>
                    <h2>@yield('hero_title', 'A faster way to browse official public results.')</h2>
                    <p>@yield('hero_copy', 'Move from regions to districts, schools, and detailed results inside one consistent portal frame.') </p>
                </div>
                <div class="hero-panel">
                    @yield('hero_panel')
                </div>
            </div>
        </section>

        @yield('stats')
        @yield('toolbar')
        @yield('alpha')
        @yield('content')
    </main>
</div>

@yield('scripts')
</body>
</html>
