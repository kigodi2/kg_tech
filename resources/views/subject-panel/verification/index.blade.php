<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PSLE Subject Panel Verification Portal — Review and verify marks entered by Mark Entry Officers for your assigned subject.">
    <title>Subject Panel Verification — IRMS TASIDO 2026</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<style>
* { box-sizing: border-box; }
body, html { margin: 0; padding: 0; min-height: 100vh; background: #0b1014; font-family: 'Maiandra GD', sans-serif; overflow-x: hidden; color: #f0f4f7; }
:root {
    --tz-green: #1EB53A; --tz-yellow: #FCD116; --tz-blue: #00A3DD;
    --tz-text: #f0f4f7; --tz-muted: rgba(255,255,255,.45);
    --accent: #BBA45E; --accent-glow: rgba(187,164,94,0.2);
    --card-bg: linear-gradient(135deg, #0d1b2a, #111e29);
    --card-border: rgba(0,163,221,0.18);
}

/* ===== TOPBAR ===== */
.topbar { background: rgba(11,16,20,0.97); border-bottom: 1px solid rgba(187,164,94,0.15); padding: 11px 28px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
.brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
.brand-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #BBA45E, #8a7540); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.95rem; }
.brand-name { font-size: 1rem; font-weight: 800; color: #f0e6c8; line-height: 1.1; }
.brand-sub { font-size: 0.58rem; color: var(--tz-yellow); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.user-chip { display: flex; align-items: center; gap: 8px; background: rgba(187,164,94,0.08); border: 1px solid rgba(187,164,94,0.2); padding: 4px 12px 4px 5px; border-radius: 22px; }
.user-av { width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg, var(--accent), #8a7540); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.72rem; font-weight: 800; }
.user-name { font-size: 0.77rem; font-weight: 700; color: var(--tz-text); }
.user-role { font-size: 0.6rem; color: var(--tz-yellow); }
.logout-btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); border-radius: 8px; color: #fca5a5; font-size: 0.78rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: all 0.2s; }
.logout-btn:hover { background: rgba(239,68,68,0.22); color: #fff; }

/* ===== FLAG ===== */
.flag-bar { display: flex; height: 4px; width: 100%; }
.flag-bar span { display: block; flex: 1; }

/* ===== HERO ===== */
.hero { background: linear-gradient(135deg, #050e15 0%, #0d1b2a 50%, #0a1520 100%); padding: 52px 28px 40px; text-align: center; border-bottom: 1px solid rgba(187,164,94,0.1); position: relative; overflow: hidden; }
.hero::before { content:''; position:absolute; top:-80px; left:50%; transform:translateX(-50%); width:560px; height:560px; border-radius:50%; background:radial-gradient(circle,rgba(187,164,94,0.07) 0%,transparent 70%); pointer-events:none; }
.hero-eyebrow { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--tz-yellow); margin-bottom: 10px; }
.hero-title { font-size: 2.2rem; font-weight: 900; color: #f0e6c8; margin: 0 0 10px; letter-spacing: -0.4px; }
.hero-subtitle { font-size: 0.95rem; color: var(--tz-muted); max-width: 560px; margin: 0 auto 22px; line-height: 1.65; }
.hero-chips { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
.chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 13px; border-radius: 18px; font-size: 0.73rem; font-weight: 700; }
.chip-gold { background: rgba(187,164,94,0.12); border: 1px solid rgba(187,164,94,0.28); color: #e8d68a; }
.chip-blue { background: rgba(0,163,221,0.1); border: 1px solid rgba(0,163,221,0.25); color: #67d8ff; }
.chip-green { background: rgba(30,181,58,0.1); border: 1px solid rgba(30,181,58,0.25); color: #6ae086; }

/* ===== INFO STRIP ===== */
.info-strip { display: grid; grid-template-columns: repeat(4, 1fr); border-top: 1px solid rgba(255,255,255,0.04); border-bottom: 1px solid rgba(255,255,255,0.04); background: #080f15; }
.ic { padding: 20px 22px; border-right: 1px solid rgba(255,255,255,0.04); transition: background 0.2s; }
.ic:hover { background: rgba(255,255,255,0.025); }
.ic:last-child { border-right: none; }
.ic-label { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 5px; }
.ic-val { font-size: 1rem; font-weight: 700; color: #f0e6c8; }
.ic-sub { font-size: 0.72rem; color: var(--tz-muted); margin-top: 2px; }

/* ===== MAIN CONTENT ===== */
.main { max-width: 1500px; margin: 0 auto; padding: 36px 28px; }

/* ===== SUMMARY CARDS ===== */
.stats-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 14px; margin-bottom: 32px; }
.stat-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; padding: 18px 16px; text-align: center; transition: all 0.2s; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,0,0,0.25); }
.stat-num { font-size: 1.7rem; font-weight: 900; line-height: 1; margin-bottom: 5px; }
.stat-lbl { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--tz-muted); }
.stat-total .stat-num { color: #67d8ff; }
.stat-verified .stat-num { color: #6ae086; }
.stat-returned .stat-num { color: #fb923c; }
.stat-pending .stat-num { color: #fbbf24; }
.stat-schools .stat-num { color: #a78bfa; }
.stat-candidates .stat-num { color: #38bdf8; }

/* ===== FILTERS ===== */
.filters-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; padding: 20px 22px; margin-bottom: 24px; }
.filters-title { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--tz-blue); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.filters-row { display: grid; grid-template-columns: 150px minmax(220px, 1.1fr) minmax(300px, 1.4fr) 190px minmax(260px, 1.2fr) auto; gap: 12px; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
.filter-label { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.35); }
.filter-select, .filter-input {
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
    color: #f0f4f7; padding: 8px 12px; font-size: 0.82rem; font-family: inherit;
    transition: border-color 0.2s;
}
.filter-select:focus, .filter-input:focus { outline: none; border-color: rgba(0,163,221,0.5); }
.filter-select option { background: #111e29; color: #f0f4f7; }
.filter-input::placeholder { color: rgba(255,255,255,0.25); }
.btn-filter { padding: 8px 18px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; font-family: inherit; cursor: pointer; border: none; transition: all 0.2s; }
.btn-apply { background: linear-gradient(135deg, #00A3DD, #006fa3); color: #fff; }
.btn-apply:hover { opacity: 0.88; }
.btn-reset { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--tz-muted); }
.btn-reset:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* ===== TABLE ===== */
.table-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; }
.table-header { padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.05); }
.table-title-block { display: flex; align-items: center; gap: 10px; }
.table-title { font-size: 0.95rem; font-weight: 700; color: #f0e6c8; }
.table-count { font-size: 0.72rem; background: rgba(0,163,221,0.12); border: 1px solid rgba(0,163,221,0.25); color: #67d8ff; padding: 2px 9px; border-radius: 12px; font-weight: 700; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead th { padding: 10px 14px; font-size: 0.63rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.35); background: rgba(0,0,0,0.2); text-align: left; white-space: nowrap; border-bottom: 1px solid rgba(255,255,255,0.06); }
.data-table tbody tr { border-bottom: 1px solid rgba(255,255,255,0.04); transition: background 0.15s; }
.data-table tbody tr:hover { background: rgba(255,255,255,0.025); }
.data-table tbody tr:last-child { border-bottom: none; }
.data-table tbody td { padding: 11px 14px; font-size: 0.82rem; color: #e0e8f0; vertical-align: middle; }
.td-index { font-family: monospace; font-size: 0.8rem; color: #67d8ff; font-weight: 700; }
.td-name { font-weight: 600; color: #f0e6c8; }
.td-gender { font-size: 0.75rem; font-weight: 700; padding: 2px 7px; border-radius: 6px; }
.td-m { background: rgba(56,189,248,0.12); color: #7dd3fc; border: 1px solid rgba(56,189,248,0.2); }
.td-f { background: rgba(244,114,182,0.12); color: #f9a8d4; border: 1px solid rgba(244,114,182,0.2); }
.td-marks { font-family: monospace; font-size: 0.83rem; font-weight: 700; color: #f0e6c8; }
.td-muted { color: rgba(255,255,255,0.3); font-size: 0.75rem; }

/* Status badges */
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 10px; font-size: 0.68rem; font-weight: 700; white-space: nowrap; }
.badge-pending { background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.25); color: #fbbf24; }
.badge-verified { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.25); color: #4ade80; }
.badge-returned { background: rgba(251,146,60,0.12); border: 1px solid rgba(251,146,60,0.25); color: #fb923c; }
.badge-corrected { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.25); color: #a78bfa; }

/* Action buttons */
.actions { display: flex; gap: 6px; align-items: center; }
.btn-act { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; font-family: inherit; cursor: pointer; border: none; transition: all 0.18s; text-decoration: none; }
.btn-view { background: rgba(0,163,221,0.1); border: 1px solid rgba(0,163,221,0.25); color: #67d8ff; }
.btn-view:hover { background: rgba(0,163,221,0.2); }
.btn-verify { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); color: #4ade80; }
.btn-verify:hover { background: rgba(74,222,128,0.2); }
.btn-return { background: rgba(251,146,60,0.1); border: 1px solid rgba(251,146,60,0.25); color: #fb923c; }
.btn-return:hover { background: rgba(251,146,60,0.22); }
.btn-disabled { opacity: 0.35; cursor: not-allowed; pointer-events: none; }

/* ===== EMPTY STATE ===== */
.empty-state { padding: 64px 28px; text-align: center; }
.empty-icon { font-size: 2.8rem; color: rgba(255,255,255,0.12); margin-bottom: 16px; }
.empty-title { font-size: 1rem; font-weight: 700; color: #f0e6c8; margin-bottom: 8px; }
.empty-body { font-size: 0.85rem; color: var(--tz-muted); }

/* ===== MODAL OVERLAY ===== */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.72); backdrop-filter: blur(4px); z-index: 999; display: none; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.active { display: flex; }
.modal-shell { background: #0d1b2a; border: 1px solid rgba(187,164,94,0.22); border-radius: 18px; padding: 30px 32px; width: 100%; max-width: 540px; position: relative; max-height: 90vh; overflow-y: auto; }
.modal-close { position: absolute; top: 14px; right: 16px; background: none; border: none; color: rgba(255,255,255,0.4); font-size: 1.1rem; cursor: pointer; transition: color 0.2s; }
.modal-close:hover { color: #fff; }
.modal-eyebrow { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--tz-yellow); margin-bottom: 6px; }
.modal-title { font-size: 1.15rem; font-weight: 800; color: #f0e6c8; margin-bottom: 20px; }
.modal-field { margin-bottom: 16px; }
.modal-label { font-size: 0.65rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 5px; display: block; }
.modal-val { font-size: 0.88rem; color: #e0e8f0; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 9px 12px; }
.modal-marks-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 16px; }
.modal-mark-box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 10px; text-align: center; }
.modal-mark-label { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 4px; }
.modal-mark-val { font-size: 1.2rem; font-weight: 900; color: #f0e6c8; font-family: monospace; }
.reason-select, .reason-textarea { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f4f7; padding: 9px 12px; font-size: 0.83rem; font-family: inherit; transition: border-color 0.2s; }
.reason-select:focus, .reason-textarea:focus { outline: none; border-color: rgba(251,146,60,0.5); }
.reason-select option { background: #0d1b2a; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 22px; }
.btn-modal-cancel { padding: 9px 18px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; font-family: inherit; cursor: pointer; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--tz-muted); transition: all 0.2s; }
.btn-modal-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }
.btn-modal-submit { padding: 9px 20px; border-radius: 8px; font-size: 0.82rem; font-weight: 700; font-family: inherit; cursor: pointer; background: linear-gradient(135deg, #fb923c, #c2410c); border: none; color: #fff; transition: opacity 0.2s; }
.btn-modal-submit:hover { opacity: 0.88; }

/* ===== FLASH MESSAGES ===== */
.flash { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.flash-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); color: #4ade80; }
.flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #f87171; }
.flash-info { background: rgba(0,163,221,0.1); border: 1px solid rgba(0,163,221,0.25); color: #67d8ff; }

/* ===== PAGINATION ===== */
.pagination-wrap { display: flex; justify-content: center; align-items: center; gap: 6px; padding: 20px; }
.page-btn { padding: 6px 12px; border-radius: 7px; font-size: 0.78rem; font-weight: 700; font-family: inherit; cursor: pointer; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.04); color: var(--tz-muted); text-decoration: none; transition: all 0.15s; }
.page-btn:hover, .page-btn.active { background: rgba(0,163,221,0.15); border-color: rgba(0,163,221,0.35); color: #67d8ff; }

/* ===== FOOTER ===== */
.footer { background: #080f15; border-top: 1px solid rgba(255,255,255,0.05); padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.72rem; color: rgba(255,255,255,0.35); margin-top: 60px; }
.footer-brand { background: linear-gradient(90deg, #f9d769, #e8b822); -webkit-background-clip: text; color: transparent; font-weight: 800; }

/* ===== NO ASSIGNMENT ===== */
.no-assignment { max-width: 520px; margin: 80px auto; text-align: center; padding: 48px 32px; background: var(--card-bg); border: 1px solid rgba(251,146,60,0.25); border-radius: 18px; }
.no-assignment-icon { font-size: 3rem; color: rgba(251,146,60,0.4); margin-bottom: 20px; }

@media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
    .info-strip { grid-template-columns: 1fr 1fr; }
    .topbar { flex-direction: column; gap: 12px; padding: 14px 16px; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .hero-title { font-size: 1.6rem; }
    .main { padding: 24px 16px; }
    .filters-row { display: flex; flex-direction: column; }
}
@media (max-width: 480px) {
    .info-strip { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
}
</style>

{{-- ===== TOPBAR ===== --}}
<div class="topbar">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
        <div>
            <div class="brand-name">IRMS — TASIDO 2026</div>
            <div class="brand-sub">Subject Panel Verification Portal</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="user-chip">
            <div class="user-av">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
            <div>
                <div class="user-name">{{ $user->name }}</div>
                <div class="user-role">Subject Panel Leader</div>
            </div>
        </div>
        <form method="POST" action="/logout" style="margin:0;">
            @csrf
            <button type="submit" class="logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</button>
        </form>
    </div>
</div>

{{-- Tanzania flag stripe --}}
<div class="flag-bar" aria-hidden="true">
    <span style="background:#1EB53A;"></span>
    <span style="background:#FCD116;"></span>
    <span style="background:#000000;"></span>
    <span style="background:#00A3DD;"></span>
</div>

{{-- ===== HERO BANNER ===== --}}
<div class="hero">
    <div class="hero-eyebrow">PSLE MARK VERIFICATION — SUBJECT PANEL</div>
    <h1 class="hero-title">Subject Panel Verification</h1>
    <p class="hero-subtitle">
        Review submitted marks for your assigned subject. Verify correct entries or return incorrect marks to the Mark Entry Officer for correction.
    </p>
    @if(!($noAssignment ?? false))
    <div class="hero-chips">
        <span class="chip chip-gold"><i class="fas fa-calendar-alt" style="font-size:0.6rem;"></i> {{ $examYears->firstWhere('id', $selectedYearId)?->year_label ?? 'Exam Year' }}</span>
        <span class="chip chip-blue"><i class="fas fa-book" style="font-size:0.6rem;"></i> {{ $assignedSubject->name ?? 'Subject' }}</span>
        <span class="chip chip-green"><i class="fas fa-user-shield" style="font-size:0.6rem;"></i> {{ $user->name }}</span>
        @if($assignment->region)
            <span class="chip chip-gold"><i class="fas fa-map-marker-alt" style="font-size:0.6rem;"></i> {{ $assignment->region->name }}</span>
        @else
            <span class="chip chip-blue"><i class="fas fa-globe" style="font-size:0.6rem;"></i> All Regions</span>
        @endif
    </div>
    @endif
</div>

{{-- ===== NO ASSIGNMENT STATE ===== --}}
@if($noAssignment ?? false)
<div class="main">
    <div class="no-assignment">
        <div class="no-assignment-icon"><i class="fas fa-user-clock"></i></div>
        <h2 style="font-size:1.15rem;font-weight:800;color:#f0e6c8;margin-bottom:10px;">No Subject Assignment Found</h2>
        <p style="color:var(--tz-muted);font-size:0.88rem;line-height:1.65;">
            You do not have an active subject assignment. Please contact the system administrator to be assigned to a subject before you can begin verification.
        </p>
    </div>
</div>
@else

{{-- ===== INFO STRIP ===== --}}
<div class="info-strip">
    <div class="ic">
        <div class="ic-label">Assigned Subject</div>
        <div class="ic-val">{{ $assignedSubject->name }}</div>
        <div class="ic-sub">Code: {{ $assignedSubject->code }}</div>
    </div>
    <div class="ic">
        <div class="ic-label">Exam Year</div>
        <div class="ic-val">{{ $examYears->firstWhere('id', $selectedYearId)?->year_label ?? '—' }}</div>
        <div class="ic-sub">Active verification period</div>
    </div>
    <div class="ic">
        <div class="ic-label">Scope</div>
        <div class="ic-val">{{ $assignment->region?->name ?? 'All Regions' }}</div>
        <div class="ic-sub">Geographic access boundary</div>
    </div>
    <div class="ic">
        <div class="ic-label">Panel Leader</div>
        <div class="ic-val">{{ $user->name }}</div>
        <div class="ic-sub">Subject Panel Verification</div>
    </div>
</div>

{{-- ===== MAIN CONTENT ===== --}}
<div class="main">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="flash flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="flash flash-info"><i class="fas fa-info-circle"></i> {{ session('info') }}</div>
    @endif

    {{-- ===== SUMMARY CARDS ===== --}}
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-num">{{ number_format($stats['total']) }}</div>
            <div class="stat-lbl">Total Submitted</div>
        </div>
        <div class="stat-card stat-verified">
            <div class="stat-num">{{ number_format($stats['verified']) }}</div>
            <div class="stat-lbl">Verified</div>
        </div>
        <div class="stat-card stat-returned">
            <div class="stat-num">{{ number_format($stats['returned']) }}</div>
            <div class="stat-lbl">Returned</div>
        </div>
        <div class="stat-card stat-pending">
            <div class="stat-num">{{ number_format($stats['pending']) }}</div>
            <div class="stat-lbl">Pending Review</div>
        </div>
        <div class="stat-card stat-schools">
            <div class="stat-num">{{ number_format($stats['schools']) }}</div>
            <div class="stat-lbl">Schools</div>
        </div>
        <div class="stat-card stat-candidates">
            <div class="stat-num">{{ number_format($stats['candidates']) }}</div>
            <div class="stat-lbl">Candidates</div>
        </div>
    </div>

    {{-- ===== FILTERS ===== --}}
    <div class="filters-card">
        <div class="filters-title"><i class="fas fa-filter"></i> Filter Marks</div>
        <form method="GET" action="{{ route('subject-panel.verification.index') }}">
            <div class="filters-row">
                {{-- Exam Year --}}
                <div class="filter-group">
                    <span class="filter-label">Exam Year</span>
                    <select name="exam_year_id" class="filter-select">
                        @foreach($examYears as $yr)
                            <option value="{{ $yr->id }}" @selected($yr->id == $selectedYearId)>{{ $yr->year_label }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Region --}}
                @if($regions->count() > 1)
                <div class="filter-group">
                    <span class="filter-label">Region</span>
                    <select name="region_id" class="filter-select">
                        <option value="">All Regions</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" @selected($r->id == $selectedRegionId)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="region_id" value="{{ $selectedRegionId }}">
                @endif
                {{-- Council --}}
                @if(($councils ?? collect())->count() > 0)
                <div class="filter-group">
                    <span class="filter-label">Council</span>
                    <select name="council_id" class="filter-select">
                        <option value="">All Councils</option>
                        @foreach($councils as $council)
                            <option value="{{ $council->id }}" @selected($council->id == ($selectedCouncilId ?? null))>{{ $council->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                {{-- School --}}
                <div class="filter-group">
                    <span class="filter-label">School</span>
                    <select name="school_id" class="filter-select">
                        <option value="">All Schools</option>
                        @foreach($schools as $sc)
                            <option value="{{ $sc->id }}" @selected($sc->id == $selectedSchoolId)>{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">Subject</span>
                    <input type="text" class="filter-input" value="{{ $assignedSubject->name }}" readonly>
                </div>
                {{-- Status --}}
                <div class="filter-group">
                    <span class="filter-label">Status</span>
                    <select name="status" class="filter-select">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($key === $selectedStatus)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- Search --}}
                <div class="filter-group">
                    <span class="filter-label">Search Candidate</span>
                    <input type="text" name="search" class="filter-input" placeholder="Index no. or name…" value="{{ $searchQuery }}">
                </div>
                <div style="display:flex;gap:8px;align-items:flex-end;">
                    <button type="submit" class="btn-filter btn-apply"><i class="fas fa-search"></i> Apply</button>
                    <a href="{{ route('subject-panel.verification.index') }}" class="btn-filter btn-reset" style="text-decoration:none;padding:8px 16px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-times"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- ===== MARKS TABLE ===== --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-block">
                <span class="table-title"><i class="fas fa-table" style="color:var(--tz-blue);margin-right:6px;"></i> Mark Records</span>
                <span class="table-count">{{ $marks->total() }} records</span>
            </div>
            <div style="font-size:0.72rem;color:var(--tz-muted);">
                Subject locked to: <strong style="color:#e8d68a;">{{ $assignedSubject->name }}</strong>
            </div>
        </div>

        @if($marks->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                <div class="empty-title">No submitted marks found</div>
                <div class="empty-body">No submitted marks are currently pending verification for your assigned subject{{ $searchQuery ? ' matching "'.e($searchQuery).'"' : '' }}.</div>
            </div>
        @else
        <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Index No.</th>
                    <th>Candidate Name</th>
                    <th>Sex</th>
                    <th>School</th>
                    <th>Paper 1</th>
                    <th>Paper 2</th>
                    <th>Paper 3</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Entered By</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($marks as $i => $mark)
                @php
                    $verif = $mark->verification;
                    $status = $verif?->status ?? 'pending';
                    $isVerified = $status === 'verified';
                    $isReturned = $status === 'returned_for_correction';
                    $total = (float)($mark->paper_1_marks ?? 0)
                           + (float)($mark->paper_2_marks ?? 0)
                           + (float)($mark->paper_3_marks ?? 0)
                           + (float)($mark->practical_marks ?? 0);
                    $enteredBy = $mark->batch?->createdByUser?->name ?? $mark->batch?->importedByUser?->name ?? '—';
                @endphp
                <tr>
                    <td class="td-muted">{{ $marks->firstItem() + $i }}</td>
                    <td class="td-index">{{ $mark->candidate_index_number }}</td>
                    <td class="td-name">{{ $mark->full_name }}</td>
                    <td>
                        @if($mark->candidate?->gender === 'M')
                            <span class="td-gender td-m">M</span>
                        @elseif($mark->candidate?->gender === 'F')
                            <span class="td-gender td-f">F</span>
                        @else
                            <span class="td-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:0.78rem;">{{ $mark->batch?->school?->name ?? '—' }}</td>
                    <td class="td-marks">{{ $mark->paper_1_marks !== null ? number_format($mark->paper_1_marks, 1) : '—' }}</td>
                    <td class="td-marks">{{ $mark->paper_2_marks !== null ? number_format($mark->paper_2_marks, 1) : '—' }}</td>
                    <td class="td-marks">{{ $mark->paper_3_marks !== null ? number_format($mark->paper_3_marks, 1) : '—' }}</td>
                    <td class="td-marks" style="color:#fbbf24;">{{ number_format($total, 1) }}</td>
                    <td>
                        @if($status === 'verified')
                            <span class="badge badge-verified"><i class="fas fa-circle-check" style="font-size:0.55rem;"></i> Verified</span>
                        @elseif($status === 'returned_for_correction')
                            <span class="badge badge-returned"><i class="fas fa-rotate-left" style="font-size:0.55rem;"></i> Returned</span>
                        @elseif($status === 'corrected_resubmitted')
                            <span class="badge badge-corrected"><i class="fas fa-check-double" style="font-size:0.55rem;"></i> Corrected</span>
                        @else
                            <span class="badge badge-pending"><i class="fas fa-clock" style="font-size:0.55rem;"></i> Pending</span>
                        @endif
                    </td>
                    <td style="font-size:0.75rem;color:var(--tz-muted);">{{ $enteredBy }}</td>
                    <td class="td-muted">{{ $mark->updated_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>
                        <div class="actions">
                            {{-- View --}}
                            <a href="{{ route('subject-panel.verification.show', $mark->id) }}" class="btn-act btn-view" title="View details">
                                <i class="fas fa-eye"></i> View
                            </a>
                            {{-- Verify --}}
                            @if(!$isVerified && !$isReturned)
                            <form method="POST" action="{{ route('subject-panel.verification.verify', $mark->id) }}" style="margin:0;" onsubmit="return confirm('Verify this mark record as correct?');">
                                @csrf
                                <button type="submit" class="btn-act btn-verify"><i class="fas fa-check"></i> Verify</button>
                            </form>
                            @else
                            <span class="btn-act btn-verify btn-disabled"><i class="fas fa-check"></i> Verify</span>
                            @endif
                            {{-- Return --}}
                            @if(!$isVerified)
                            <button type="button" class="btn-act btn-return"
                                onclick='openReturnModal(@json($mark->id), @json($mark->candidate_index_number), @json($mark->full_name), @json($mark->batch?->school?->name ?? ""), @json($mark->paper_1_marks), @json($mark->paper_2_marks), @json($mark->paper_3_marks))'>
                                <i class="fas fa-rotate-left"></i> Return
                            </button>
                            @else
                            <span class="btn-act btn-return btn-disabled"><i class="fas fa-rotate-left"></i> Return</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        {{-- Pagination --}}
        @if($marks->hasPages())
        <div class="pagination-wrap">
            @if($marks->onFirstPage())
                <span class="page-btn" style="opacity:0.3;">&laquo; Prev</span>
            @else
                <a href="{{ $marks->previousPageUrl() }}" class="page-btn">&laquo; Prev</a>
            @endif

            @foreach($marks->getUrlRange(max(1, $marks->currentPage()-2), min($marks->lastPage(), $marks->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="page-btn {{ $page == $marks->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach

            @if($marks->hasMorePages())
                <a href="{{ $marks->nextPageUrl() }}" class="page-btn">Next &raquo;</a>
            @else
                <span class="page-btn" style="opacity:0.3;">Next &raquo;</span>
            @endif
        </div>
        @endif
        @endif
    </div>

</div><!-- /main -->

@endif {{-- end no-assignment --}}

@if(!($noAssignment ?? false))
{{-- ===== RETURN FOR CORRECTION MODAL ===== --}}
<div class="modal-overlay" id="returnModal">
    <div class="modal-shell" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle">
        <button class="modal-close" onclick="closeReturnModal()" aria-label="Close">&times;</button>
        <div class="modal-eyebrow">PANEL REVIEW ACTION</div>
        <div class="modal-title" id="returnModalTitle">Return Mark for Correction</div>

        <div class="modal-field">
            <span class="modal-label">Candidate</span>
            <div class="modal-val" id="rm-candidate">—</div>
        </div>
        <div class="modal-field">
            <span class="modal-label">School</span>
            <div class="modal-val" id="rm-school">—</div>
        </div>
        <div class="modal-marks-grid">
            <div class="modal-mark-box">
                <div class="modal-mark-label">Paper 1</div>
                <div class="modal-mark-val" id="rm-p1">—</div>
            </div>
            <div class="modal-mark-box">
                <div class="modal-mark-label">Paper 2</div>
                <div class="modal-mark-val" id="rm-p2">—</div>
            </div>
            <div class="modal-mark-box">
                <div class="modal-mark-label">Paper 3</div>
                <div class="modal-mark-val" id="rm-p3">—</div>
            </div>
        </div>

        <form method="POST" id="returnForm" action="">
            @csrf
            <div class="modal-field">
                <label class="modal-label" for="reason_select">Correction Reason <span style="color:#fb923c;">*</span></label>
                <select class="reason-select" id="reason_select" name="_reason_preset" onchange="syncReason(this)">
                    <option value="">— Select a reason —</option>
                    @foreach($returnReasons ?? \App\Models\MarkVerification::RETURN_REASONS as $reason)
                        <option value="{{ $reason }}">{{ $reason }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-field">
                <label class="modal-label" for="return_reason">Correction Details <span style="color:#fb923c;">*</span></label>
                <textarea class="reason-textarea" id="return_reason" name="return_reason" rows="3"
                    placeholder="Describe the issue clearly so the Mark Entry Officer can correct it…" required maxlength="500"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeReturnModal()">Cancel</button>
                <button type="submit" class="btn-modal-submit"><i class="fas fa-paper-plane"></i> Submit Return</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== FOOTER ===== --}}
<footer class="footer">
    <div>Copyright &copy; {{ now()->year }} IRMS — TASIDO 2026 | Subject Panel Verification Portal</div>
    <div>Developed by <strong class="footer-brand">ProSmart Technologies</strong></div>
</footer>

<script>
function openReturnModal(markId, indexNo, name, school, p1, p2, p3) {
    document.getElementById('rm-candidate').textContent = indexNo + ' — ' + name;
    document.getElementById('rm-school').textContent = school || '—';
    document.getElementById('rm-p1').textContent = p1 !== null ? p1 : '—';
    document.getElementById('rm-p2').textContent = p2 !== null ? p2 : '—';
    document.getElementById('rm-p3').textContent = p3 !== null ? p3 : '—';
    document.getElementById('returnForm').action = '/subject-panel/verification/' + markId + '/return';
    document.getElementById('reason_select').value = '';
    document.getElementById('return_reason').value = '';
    document.getElementById('returnModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('active');
    document.body.style.overflow = '';
}

function syncReason(sel) {
    const textarea = document.getElementById('return_reason');
    if (sel.value && sel.value !== 'Other') {
        textarea.value = sel.value;
    } else {
        textarea.value = '';
        textarea.focus();
    }
}

// Close modal on overlay click
document.getElementById('returnModal').addEventListener('click', function(e) {
    if (e.target === this) closeReturnModal();
});

// Escape key close
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReturnModal();
});
</script>
@endif
</body>
</html>
