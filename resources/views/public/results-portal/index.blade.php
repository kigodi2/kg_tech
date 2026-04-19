<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ data_get($meta, 'title', 'Zonal IRMS Portal') }}</title>

  <meta name="description" content="{{ data_get($meta, 'description', 'Examination Results') }}">
  <meta name="keywords" content="{{ data_get($meta, 'keywords', 'results, mock, NECTA') }}">
  <meta name="author" content="{{ data_get($meta, 'author', 'Examination Board') }}">

  @php
    $defaultLogo = asset('images/emblem.png');
    $leftLogo = data_get($meta, 'left_logo_url', $defaultLogo);
    $rightLogo = data_get($meta, 'right_logo_url', $defaultLogo);
    $entries = $entries ?? collect();
    $columnsCount = max((int) data_get($meta, 'columns', 3), 1);
    $modernPortal = data_get($meta, 'portal_variant') === 'professional-evaluation';
    $statsLabel = data_get($meta, 'stats_label', 'Entries');
  @endphp

  @if($rightLogo)
    <link rel="icon" type="image/png" href="{{ $rightLogo }}">
    <link rel="apple-touch-icon" href="{{ $rightLogo }}">
  @endif
  <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">

  <style>
    :root { --irms-font: 'Maiandra GD', "Ubuntu Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
    html, body, body * { font-family: var(--irms-font) !important; }
    input, button, select, textarea { font-family: var(--irms-font) !important; }

    @if($modernPortal)
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
      .search-btn:hover { filter: brightness(1.03); }
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
        grid-template-columns: repeat({{ $columnsCount }}, minmax(0, 1fr));
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
      .portal-card:nth-child(4n+1) { --accent-start: #2563eb; --accent-end: #60a5fa; }
      .portal-card:nth-child(4n+1) .card-index { background: #eff6ff; }
      .portal-card:nth-child(4n+2) { --accent-start: #059669; --accent-end: #34d399; }
      .portal-card:nth-child(4n+2) .card-index { background: #ecfdf5; }
      .portal-card:nth-child(4n+3) { --accent-start: #7c3aed; --accent-end: #a78bfa; }
      .portal-card:nth-child(4n+3) .card-index { background: #f5f3ff; }
      .portal-card:nth-child(4n+4) { --accent-start: #ea580c; --accent-end: #fb923c; }
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
      .empty-state,
      .no-results {
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
        .top-actions,
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
        .portal-card { padding: 20px; }
        .toolbar-right { min-width: 100%; }
        .search-row { flex-direction: column; }
        .search-btn { width: 100%; }
      }
    @else
      body { background: #fff; background-image: url('{{ data_get($meta, 'background_url', '') }}'); background-size: cover; background-position: center; background-attachment: fixed; background-repeat: no-repeat; position: relative; }
      body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255,255,255,.85); z-index: -1; }
      .site-header { background: #004080; color: #fff; padding: 12px 18px; border-bottom: 4px solid #ffcc00; }
      .site-header-top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
      .site-header-center { text-align: center; flex: 1; }
      .site-title { font-size: 24px; font-weight: 800; letter-spacing: .5px; color: #ffb300; line-height: 1.1; text-shadow: 0 0 6px rgba(255, 80, 0, 0.7), 0 0 12px rgba(255, 140, 0, 0.5); }
      .site-subtitle { font-size: 24px; color: #ffb300; line-height: 1.1; text-shadow: 0 0 6px rgba(255, 80, 0, 0.7), 0 0 12px rgba(255, 140, 0, 0.5); }
      .header-right-text { font-size: 24px; margin-top: 2px; color: #fff; line-height: 1.1; }
      .header-places { font-size: 24px; margin-top: 2px; color: #fff; opacity: .95; line-height: 1.15; }
      .header-logo { max-height: 96px; width: auto; }
      .announcement { margin-top: 8px; font-size: 13px; font-weight: bold; overflow: hidden; position: relative; }
      .announcement-track { display: inline-flex; align-items: center; white-space: nowrap; min-width: max-content; padding-left: 100%; animation: portalTicker 22s linear infinite; }
      .announcement-track:hover { animation-play-state: paused; }
      .announcement-copy { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; padding-right: 56px; }
      .announcement .fire-icon { color: #ff6b00; font-weight: 900; display: inline-block; animation: flameBlink 0.9s ease-in-out infinite; transform-origin: center bottom; }
      .announcement .fire-text { color: #ffb300; font-weight: 800; text-shadow: 0 0 6px rgba(255, 102, 0, 0.6); }
      @keyframes portalTicker {
        from { transform: translateX(0); }
        to { transform: translateX(-100%); }
      }
      @keyframes flameBlink {
        0%, 100% { opacity: 1; transform: scale(1) rotate(-2deg); text-shadow: 0 0 4px rgba(255, 90, 0, 0.55); }
        50% { opacity: 0.7; transform: scale(1.15) rotate(2deg); text-shadow: 0 0 10px rgba(255, 160, 0, 0.95); }
      }
      .filters-wrapper { margin-top: 15px; text-align: center; }
      .header-search { margin: 10px auto 0; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: center; width: 100%; max-width: 1240px; }
      .header-search input { flex: 1 1 840px; max-width: 980px; width: 100%; padding: 5px 8px; border-radius: 3px; border: 1px solid #ccc; font-size: 13px; }
      .header-search button { padding: 5px 10px; border-radius: 3px; border: none; background: #ffcc00; color: #000; font-weight: bold; cursor: pointer; font-size: 13px; }
      .header-search button:hover { background: #ffdd33; }
      .alpha-filter-bar { margin-top: 8px; font-size: 13px; }
      .alpha-label { display: block; margin: 4px 0; font-weight: bold; }
      .alpha-link { border: 1px solid #000080; background: #dbe9ff; color: #0000cc; font-weight: bold; padding: 2px 6px; margin: 0 1px 2px 1px; cursor: pointer; font-size: 12px; }
      .alpha-link.active { background: #ffcc00; color: #000; }
      .no-results { margin-top: 10px; font-size: 13px; color: #800040; display: none; }
      .back-nav { margin: 0; background: #eef0f3; padding: 10px 14px; text-align: left; }
      .back-link-btn { display: inline-flex; align-items: center; gap: 8px; color: #2563eb; text-decoration: none; font-weight: 700; font-size: 16px; line-height: 1; }
      .back-link-btn .back-arrow { font-size: 14px; line-height: 1; }
      .back-link-btn:hover { text-decoration: underline; }
      .container { display: grid; gap: 0; margin-top: 15px; width: min(95vw, 1380px); margin-left: auto; margin-right: auto; border: 3px double #999; background: #fff; }
      .column { border-left: 1px solid #999; }
      .column:first-child { border-left: none; }
      .blue { background: #cfe8ff; }
      .pink { background: #ffd6e7; }
      .green { background: #d8f5d0; }
      .item { padding: 4px 6px; border-bottom: 1px solid #999; font-size: 15px; }
      .item a { text-decoration: none; color: #000080; text-transform: uppercase; }
      .item a:hover { text-decoration: underline; }
      @media (max-width: 768px) {
        .header-logo { display: block; max-height: 58px; }
        .site-title { font-size: 14px; }
        .site-subtitle { font-size: 14px; }
        .header-right-text { font-size: 14px; }
        .header-places { font-size: 14px; }
        .container { max-width: 100%; overflow-x: auto; }
        .item { font-size: 13px; padding: 3px 4px; }
      }
    @endif
  </style>
</head>

<body>
@if($modernPortal)
  <div class="page-shell">
    <header class="topbar">
      <div class="topbar-inner">
        <div>
          <span class="eyebrow"><span class="eyebrow-dot"></span>{{ data_get($meta, 'eyebrow', 'ACSEE Evaluation Workspace') }}</span>
          <h1 class="page-title">{{ data_get($meta, 'header_title', 'ACSEE Evaluations') }}</h1>
          <p class="page-copy">{{ data_get($meta, 'announcement', 'Review the available entries below and use the search and alphabet filters to reach the exact report you need.') }}</p>
        </div>

        <div class="top-actions">
          @if(data_get($meta, 'back_url'))
            <a href="{{ data_get($meta, 'back_url') }}" class="top-btn secondary">
              <span>&larr;</span>
              <span>{{ data_get($meta, 'back_label', 'Back') }}</span>
            </a>
          @endif
          @if(data_get($meta, 'primary_action_url'))
            <a href="{{ data_get($meta, 'primary_action_url') }}" class="top-btn primary">
              <span>{{ data_get($meta, 'primary_action_label', 'Open') }}</span>
              <span>&rarr;</span>
            </a>
          @endif
        </div>
      </div>
    </header>

    <main class="content">
      <section class="hero">
        <div class="hero-grid">
          <div>
            <span class="hero-badge">{{ data_get($meta, 'hero_badge', 'Professional Reporting Portal') }}</span>
            <h2>{{ data_get($meta, 'hero_title', 'A faster way to browse official evaluation reports.') }}</h2>
            <p>{{ data_get($meta, 'hero_copy', 'This workspace is organized for clarity, speed, and decision-ready navigation. Search directly, filter by alphabet, and open the right evaluation entry with confidence.') }}</p>
          </div>

          <div class="hero-panel">
            <div class="glass-card">
              <small>{{ $statsLabel }}</small>
              <strong>{{ $entries->count() }}</strong>
              <span>{{ data_get($meta, 'stats_copy', 'Available items are arranged in a cleaner, more accessible format for review teams and supervisors.') }}</span>
            </div>
            <div class="glass-card">
              <small>{{ data_get($meta, 'support_label', 'Search Experience') }}</small>
              <strong>{{ data_get($meta, 'support_value', 'Smart Filter') }}</strong>
              <span>{{ data_get($meta, 'support_copy', 'Use keywords or alphabet shortcuts to narrow the list instantly without leaving the page.') }}</span>
            </div>
          </div>
        </div>
      </section>

      <section class="stats-grid">
        <article class="stat-card">
          <small><span>{{ $statsLabel }}</span><span>01</span></small>
          <strong>{{ $entries->count() }}</strong>
          <span>{{ data_get($meta, 'stats_card_one', 'Total entries currently available in this portal view.') }}</span>
        </article>
        <article class="stat-card">
          <small><span>{{ data_get($meta, 'stats_title_two', 'Layout') }}</span><span>02</span></small>
          <strong>{{ $columnsCount }}</strong>
          <span>{{ data_get($meta, 'stats_card_two', 'Balanced card columns for easier scanning across desktop and laptop screens.') }}</span>
        </article>
        <article class="stat-card">
          <small><span>{{ data_get($meta, 'stats_title_three', 'Mode') }}</span><span>03</span></small>
          <strong>{{ data_get($meta, 'stats_value_three', 'Premium') }}</strong>
          <span>{{ data_get($meta, 'stats_card_three', 'Improved hierarchy, cleaner spacing, and stronger action visibility.') }}</span>
        </article>
        <article class="stat-card">
          <small><span>{{ data_get($meta, 'stats_title_four', 'Flow') }}</span><span>04</span></small>
          <strong>{{ data_get($meta, 'stats_value_four', 'Direct') }}</strong>
          <span>{{ data_get($meta, 'stats_card_four', 'Search, scan, and open the target report with fewer visual distractions.') }}</span>
        </article>
      </section>

      <section class="toolbar">
        <div class="toolbar-left">
          <h3>{{ data_get($meta, 'toolbar_title', 'Browse available entries') }}</h3>
          <p>{{ data_get($meta, 'toolbar_copy', 'Use the search field to find a specific report or region, then refine with the alphabet shortcuts below.') }}</p>
          @if(data_get($meta, 'back_url'))
            <div style="margin-top: 14px;">
              <a href="{{ data_get($meta, 'back_url') }}" class="back-link">&larr; {{ data_get($meta, 'back_label', 'Back') }}</a>
            </div>
          @endif
        </div>

        <div class="toolbar-right">
          <div class="search-row">
            <input type="text" id="schoolSearch" class="search-input" placeholder="{{ data_get($meta, 'search_placeholder', 'Search from the list') }}">
            <button type="button" class="search-btn" onclick="applyFilters()">Search</button>
          </div>
        </div>
      </section>

      <section class="alpha-wrap">
        <span class="alpha-title">{{ data_get($meta, 'alpha_label', 'Filter by alphabet') }}</span>
        <div class="alpha-actions" id="alphaLetters">
          <button class="alpha-link active" data-letter="ALL">{{ data_get($meta, 'alpha_all_label', 'ALL ENTRIES') }}</button>
        </div>
      </section>

      <section class="cards-grid" id="schoolsContainer">
        @forelse($entries->values() as $index => $it)
          <a href="{{ $it['url'] }}" class="portal-card item" data-label="{{ strtoupper($it['label']) }}">
            <span class="card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
            <h3 class="card-label">{{ $it['label'] }}</h3>
            <p class="card-copy">{{ data_get($it, 'description', data_get($meta, 'entry_copy', 'Open this entry to view the detailed report or next evaluation level.')) }}</p>
            <span class="card-link">Open entry &rarr;</span>
          </a>
        @empty
          <div class="empty-state">No entries are available for this portal yet.</div>
        @endforelse
      </section>

      <div id="noResults" class="no-results">No matching entry was found for the current search criteria. Please refine your keywords and try again.</div>
    </main>
  </div>
@else
  <header class="site-header">
    <div class="site-header-top">
      @if($leftLogo)<img src="{{ $leftLogo }}" class="header-logo" alt="Left Logo">@endif

      <div class="site-header-center">
        <div class="header-right-text">{{ data_get($meta, 'header_top', "PRIME MINISTER'S OFFICE") }}</div>
        <div class="site-subtitle">{{ data_get($meta, 'header_subtitle', 'REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT') }}</div>
        <div class="header-places">{{ data_get($meta, 'header_places', 'TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, TABORA, LINDI AND MTWARA') }}</div>
        <div class="site-title">{{ data_get($meta, 'header_title', 'FORM SIX ZONAL JOINT MOCK RESULTS - FEBRUARY, 2026') }}</div>
        <div class="header-right-text"><div>{{ data_get($meta, 'header_right', '') }}</div></div>
      </div>

      @if($rightLogo)<img src="{{ $rightLogo }}" class="header-logo" alt="Right Logo">@endif
    </div>

    <div class="announcement">
      <div class="announcement-track">
        <span class="announcement-copy">
          <span class="fire-icon">&#128293;</span>
          <span class="fire-text">{{ data_get($meta, 'announcement', 'Results have been officially published. Please use the search facility below to locate your school or examination centre.') }}</span>
          <span class="fire-icon">&#128293;</span>
        </span>
      </div>
    </div>
  </header>

  <div class="filters-wrapper">
    @if(data_get($meta, 'back_url'))
      <div class="back-nav">
        <a href="{{ data_get($meta, 'back_url') }}" class="back-link-btn">
          <span class="back-arrow">&larr;</span>
          <span>{{ data_get($meta, 'back_label', 'Back') }}</span>
        </a>
      </div>
    @endif
    <div class="header-search">
      <input type="text" id="schoolSearch" placeholder="{{ data_get($meta, 'search_placeholder', 'Search from the list') }}">
      <button type="button" onclick="applyFilters()">Search</button>
    </div>

    <div class="alpha-filter-bar">
      <button class="alpha-link active" data-letter="ALL">{{ data_get($meta, 'alpha_all_label', 'ALL CENTRES') }}</button>
      <span class="alpha-label">{{ data_get($meta, 'alpha_label', 'CLICK ANY LETTER BELOW TO FILTER SCHOOLS BY ALPHABET') }}</span>
      <div class="alpha-letters" id="alphaLetters"></div>
    </div>

    <div id="noResults" class="no-results">No matching school or examination centre was found for the current search criteria. Please refine your keywords and try again.</div>
  </div>

  @php
    $entriesList = $entries->values();
    $actualColumns = max(1, min($columnsCount, max($entriesList->count(), 1)));
    $chunks = collect(range(0, $actualColumns - 1))->map(fn () => collect());

    foreach ($entriesList as $index => $entry) {
        $columnIndex = $index % $actualColumns;
        $chunks[$columnIndex]->push($entry);
    }

    $columnClasses = ['blue', 'pink', 'green', 'blue'];
  @endphp

  <div class="container" id="schoolsContainer" style="grid-template-columns: repeat({{ $actualColumns }}, minmax(0, 1fr));">
    @for($i = 0; $i < $actualColumns; $i++)
      @php
        $columnItems = $chunks[$i] ?? collect();
        $columnClass = $columnClasses[$i % count($columnClasses)];
      @endphp
      <div class="column {{ $columnClass }}">
        @foreach($columnItems as $it)
          <div class="item" data-label="{{ strtoupper($it['label']) }}">
            <a href="{{ $it['url'] }}">{{ $it['label'] }}</a>
          </div>
        @endforeach
      </div>
    @endfor
  </div>
@endif

<script>
  function extractSchoolName(label) {
    var raw = (label || '').trim();
    return raw.replace(/^[A-Z0-9\/-]+\s*-\s*/i, '').trim();
  }

  function applyFilters() {
    var input = document.getElementById('schoolSearch');
    var textFilter = input ? input.value.toLowerCase() : '';

    var activeAlpha = document.querySelector('.alpha-link.active');
    var letter = activeAlpha ? activeAlpha.getAttribute('data-letter') : 'ALL';

    var container = document.getElementById('schoolsContainer');
    if (!container) return;

    var items = container.getElementsByClassName('item');
    var anyVisible = false;

    for (var i = 0; i < items.length; i++) {
      var txtValue = (items[i].getAttribute('data-label') || '').trim();
      var txtLower = txtValue.toLowerCase();
      var schoolName = extractSchoolName(txtValue);
      var schoolNameLower = schoolName.toLowerCase();

      var matchesText = !textFilter || txtLower.indexOf(textFilter) > -1 || schoolNameLower.indexOf(textFilter) > -1;

      var matchesLetter = true;
      if (letter && letter !== 'ALL') {
        var firstChar = schoolName.charAt(0).toUpperCase();
        matchesLetter = firstChar === letter;
      }

      if (matchesText && matchesLetter) {
        items[i].style.display = '';
        anyVisible = true;
      } else {
        items[i].style.display = 'none';
      }
    }

    var noResults = document.getElementById('noResults');
    if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var alpha = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    var wrap = document.getElementById('alphaLetters');
    if (wrap) {
      alpha.forEach(function (ch) {
        var btn = document.createElement('button');
        btn.className = 'alpha-link';
        btn.setAttribute('data-letter', ch);
        btn.textContent = ch;
        wrap.appendChild(btn);
      });
    }

    var input = document.getElementById('schoolSearch');
    if (input) {
      input.addEventListener('keyup', function (event) {
        if (event.key === 'Enter') event.preventDefault();
        applyFilters();
      });
    }

    var alphaButtons = document.querySelectorAll('.alpha-link');
    alphaButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        alphaButtons.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        applyFilters();
      });
    });
  });
</script>
</body>
</html>
