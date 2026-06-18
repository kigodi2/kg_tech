<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TASIDO 2026 - DAO Registration Control Centre</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <!-- Alpine plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* Tom Select Dark Mode Overrides */
        .ts-control { background: rgba(31,41,55,0.5) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; border-radius: 6px !important; }
        .ts-dropdown { background: #161b22 !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; }
        .ts-dropdown .active { background: var(--tz-green) !important; color: white !important; }
        .ts-dropdown .option { color: #d1d5db !important; }
        .ts-control input { color: white !important; }
    </style>
</head>
<body>
@php
    $mockPortalManual = [
        'manualId' => 'daoDashboardManual',
        'manualTitle' => 'DAO Portal Guide',
        'manualSummary' => 'This guide helps District Academic Officers manage schools, monitor registration issues, and coordinate corrections safely.',
        'manualPdf' => '/dao_guide.pdf',
        'manualSteps' => [
            ['title' => 'Use the district dashboard overview', 'body' => 'Start from the DAO dashboard totals to monitor the number of schools, accounts, and candidate activity under your district responsibility.'],
            ['title' => 'Manage schools carefully', 'body' => 'Use school create, view, edit, and delete actions only after confirming the centre number and ownership details for the correct district.'],
            ['title' => 'Review alerts and flagged issues', 'body' => 'Check the dashboard alerts and error areas regularly so district data problems are handled before the registration deadline.'],
            ['title' => 'Coordinate with headteachers', 'body' => 'If a school submission has errors, send it back with a clear correction note instead of allowing inconsistent records to remain in the system.'],
            ['title' => 'Search and filter before acting', 'body' => 'Use the search tools on schools, candidates, and statistics to verify the exact target record before editing or deleting anything.'],
        ],
        'manualNotes' => [
            '<strong>Important:</strong> DAO actions affect multiple schools, so always confirm the centre number before any update or deletion.',
            '<strong>Download option:</strong> Use the DAO PDF guide for district training and support.'
        ],
    ];
@endphp
<style>
* { box-sizing: border-box; }
input, select, textarea, button { font-family: inherit; }
body, html { margin: 0; padding: 0; width: 100%; max-width: 100vw; overflow-x: hidden; background: #0f1117; font-family: 'Maiandra GD', sans-serif; }
:root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-dark:#0b1014;--tz-card:#101518;--tz-text:#f0f4f7;--tz-muted:rgba(255,255,255,.45);}

/* Layout CSS from manage-users */
.um-shell{display:flex;flex-direction:column;min-height:100vh;background:#0f1117;font-family:'Maiandra GD',sans-serif;width:100%;max-width:100%;}
.um-body-row{display:flex;flex:1;width:100%;max-width:100%;min-height:100vh;background:linear-gradient(180deg,#0d1b2a,#11202e);}
.um-sidebar{
    width:260px;
    display:flex;
    flex-direction:column;
    position:fixed;
    top:0;
    left:0;
    height:100vh;
    background:linear-gradient(180deg,#0d1b2a,#11202e);
    border-right:1px solid rgba(187,164,94,.18);
    box-shadow:16px 0 40px rgba(0,0,0,.22);
    z-index:100;
    flex-shrink:0;
}
.um-sidebar::-webkit-scrollbar { width: 4px; }
.um-sidebar::-webkit-scrollbar-thumb { background: rgba(187,164,94,0.3); border-radius: 4px; }

/* Dashboard Brand in Sidebar */
.um-profile{padding:20px 20px 20px;border-bottom:1px solid rgba(187,164,94,.15);background:linear-gradient(135deg,rgba(187,164,94,.08),transparent); display:flex; align-items:center; gap: 12px; text-decoration: none;}
.adm-brand-icon{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--tz-green),#0f7a1e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;flex-shrink:0;}
.adm-brand-text{font-size:1.1rem;font-weight:800;color:var(--tz-text);line-height:1.1;}
.adm-brand-sub{font-size:.65rem;color:var(--tz-yellow);font-weight:700;letter-spacing:.07em;text-transform:uppercase;}

.um-nav{padding:14px 12px;flex:1;}
.um-nav-label{font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(187,164,94,.55);padding:6px 8px 4px;margin-top:10px;}
.um-nav a, .um-nav button{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;color:rgba(255,255,255,.65);font-size:.875rem;font-weight:500;text-decoration:none;transition:all .18s;margin-bottom:2px; width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit;}
.um-nav a:hover, .um-nav button:hover, .um-nav a.active{background:rgba(187,164,94,.12);color:#f0e6c8;}
.um-nav a.active{background:rgba(187,164,94,.18);}
.nav-ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;background:rgba(255,255,255,.06);flex-shrink:0;}
.um-nav a:hover .nav-ico, .um-nav button:hover .nav-ico, .um-nav a.active .nav-ico{background:rgba(187,164,94,.2);color:#BBA45E;}

/* Accordion specifics */
.nav-caret { margin-left: auto; font-size: 0.75rem; transition: transform 0.2s; color: rgba(255,255,255,0.3); }
.um-nav button:hover .nav-caret { color: #BBA45E; }
.submenu { padding-left: 14px; margin-bottom: 4px; overflow: hidden; }
.submenu a { padding: 8px 12px 8px 30px; font-size: 0.82rem; color: rgba(255,255,255,0.5); gap: 8px; }
.submenu a::before { content: ''; position: absolute; left: 24px; width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.2); }
.submenu a:hover { background: rgba(255,255,255,0.04); color: #f0e6c8; }
.submenu a:hover::before { background: #BBA45E; }
.submenu-item { position: relative; display: flex; align-items: center; }
.submenu-hr { margin: 4px 12px 4px 30px; border: none; border-top: 1px solid rgba(255,255,255,0.05); }

.um-footer{padding:16px;border-top:1px solid rgba(187,164,94,.15);}
.um-logout{display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;color:#fca5a5;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .18s;text-decoration:none;}
.um-logout:hover{background:rgba(239,68,68,.2);color:#fff;}

.um-main{flex:1;display:flex;flex-direction:column;min-width:0;max-width:100%;margin-left:260px;background:#0f1117;border-left:1px solid rgba(187,164,94,.18);}
.um-topbar{background:rgba(15,17,23,.95);border-bottom:1px solid rgba(187,164,94,.15);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;flex-wrap:wrap;gap:12px;max-width:100%;}
.um-topbar-title{font-size:1.25rem;font-weight:700;color:#f0e6c8;}
.um-topbar-sub{font-size:.8rem;color:rgba(255,255,255,.4);margin-top:1px;}
.um-date-pill{font-size:.85rem;color:rgba(255,255,255,.4);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);padding:8px 18px;border-radius:20px;}
.um-clock{font-size:.85rem;font-weight:700;color:#BBA45E;background:rgba(187,164,94,.08);border:1px solid rgba(187,164,94,.18);padding:7px 16px;border-radius:18px;}
.um-content{padding:28px;flex:1;}

/* User display in topbar */
.adm-user{display:flex;align-items:center;gap:8px;background:rgba(30,181,58,.07);border:1px solid rgba(30,181,58,.16);padding:4px 12px 4px 5px;border-radius:22px;}
.adm-user-av{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--tz-green),#0f7a1e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:800;}
.adm-user-name{font-size:.78rem;font-weight:700;color:var(--tz-text);}
.adm-user-role{font-size:.62rem;color:var(--tz-yellow);}

/* Dashboard Specific CSS */
.adm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-bottom:28px;}
.adm-stat{border-radius:14px;padding:24px;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.06);}
.adm-stat::after{content:'';position:absolute;right:-16px;bottom:-16px;width:66px;height:66px;border-radius:50%;background:rgba(255,255,255,.04);}
.adm-stat-label{font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.7;}
.adm-stat-value{font-size:2.2rem;font-weight:800;line-height:1.1;margin:5px 0 8px;letter-spacing:-2px;}
.adm-stat-desc{font-size:.75rem;opacity:.55;}
.adm-stat-icon{position:absolute;top:16px;right:16px;font-size:1.3rem;opacity:.18;}
.stat-blue  {background:linear-gradient(135deg,#003d52,#004f6b);color:#67d8ff;}
.stat-green {background:linear-gradient(135deg,#0a3012,#0e3d17);color:#6ae086;}
.stat-yellow{background:linear-gradient(135deg,#3a2e00,#453600);color:#FCD116;}
.stat-black {background:linear-gradient(135deg,#111416,#161b1f);color:#c0ccd6;border-color:rgba(0,163,221,.15)!important;}

.adm-modules{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;}
.adm-card{background:var(--tz-card);border:1px solid rgba(255,255,255,.06);border-radius:14px;overflow:hidden;transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column;}
.adm-card:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(0,0,0,.5);}
.adm-card-head{padding:16px 20px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.04);}
.adm-card-title{font-size:.9rem;font-weight:700;color:var(--tz-text);display:flex;align-items:center;gap:8px;}
.adm-card-badge{font-size:.58rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border-radius:18px;}
.badge-blue  {background:rgba(0,163,221,.15);color:#67d8ff;border:1px solid rgba(0,163,221,.25);}
.badge-green {background:rgba(30,181,58,.12);color:#6ae086;border:1px solid rgba(30,181,58,.22);}
.badge-yellow{background:rgba(252,209,22,.1);color:#FCD116;border:1px solid rgba(252,209,22,.2);}
.badge-indigo{background:rgba(0,163,221,.1);color:#67d8ff;border:1px solid rgba(0,163,221,.18);}
.badge-orange{background:rgba(252,209,22,.1);color:#FCD116;border:1px solid rgba(252,209,22,.2);}
.badge-purple{background:rgba(30,181,58,.1);color:#6ae086;border:1px solid rgba(30,181,58,.2);}
.adm-card-body{padding:10px;flex:1;}
.adm-link{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:9px;text-decoration:none;color:var(--tz-muted);font-size:.88rem;font-weight:500;transition:all .14s;}
.adm-link:hover{background:rgba(30,181,58,.08);color:#fff;}
.adm-link-icon{width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;}
.adm-link:hover .adm-link-icon{background:rgba(30,181,58,.16);color:var(--tz-green);}

.adm-health{background:linear-gradient(135deg,#050a0d,#080e12);border:1px solid rgba(0,163,221,.16);border-radius:12px;padding:20px;margin-top:16px;}
.adm-health-title{font-size:.9rem;font-weight:700;color:var(--tz-yellow);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.adm-health-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem;}
.adm-health-key{color:var(--tz-muted);}
.adm-health-val{font-weight:700;}
.val-blue{color:var(--tz-blue);}
.val-green{color:var(--tz-green);}
.val-orange{color:var(--tz-yellow);}
.adm-filament-btn{display:block;width:100%;margin-top:12px;padding:9px;background:linear-gradient(135deg,var(--tz-blue),#006fa3);color:#fff;border-radius:9px;font-size:.8rem;font-weight:700;text-align:center;text-decoration:none;transition:opacity .15s;}
.adm-filament-btn:hover{opacity:.85;}

/* Footer */
.page-footer { background: #0f1117; color: #ffffff; border-top: 1px solid rgba(187,164,94,.15); margin-top: auto; width: 100%; }
.page-footer-stripes { display: flex; width: 100%; height: 3px; }
.page-footer-stripes span { display: block; width: 25%; }
..page-footer-body { width: 100%; padding: 12px 24px; }
.page-footer-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; width: 100%; }
.page-footer-copy, .page-footer-meta { font-size: 0.75rem; line-height: 1.45; }
.page-footer-copy { text-align: center; color: rgba(255,255,255,.6); flex: 1; display: flex; justify-content: center; }
.page-footer-meta { text-align: right; color: rgba(255, 255, 255, 0.7); flex-shrink: 0; }
.page-footer-meta strong { color: #BBA45E; font-weight: 700; }
.footer-brand {
    background: linear-gradient(90deg, #f9d769 0%, #ffd35f 40%, #e8b822 100%);
    -webkit-background-clip: text;
    color: transparent;
    font-weight: 800;
    font-size: 0.85rem;
    text-shadow: 0 0 6px rgba(255, 210, 80, 0.9), 0 0 16px rgba(255, 210, 80, 0.35);
    transition: transform 0.24s ease, text-shadow 0.24s ease;
}
.footer-brand:hover {
    transform: translateY(-1px);
    text-shadow: 0 0 8px rgba(255, 210, 80, 0.95), 0 0 18px rgba(255, 210, 80, 0.5);
}
.page-footer-copy p, .page-footer-meta p { margin: 0; }

@media(max-width:1100px){.adm-stats{grid-template-columns:repeat(2,1fr);}.adm-modules{grid-template-columns:repeat(2,1fr);}}
@media(max-width:1024px) {
    .um-body-row { flex-direction: column; background: #0f1117; }
    .um-sidebar { width: 100%; height: auto; max-height: none; position: static; box-shadow:none; border-right:none; border-bottom: 1px solid rgba(187,164,94,.18); }
    .um-main { margin-left:0; border-left: none; }
}
@media(max-width:768px){.adm-stats{grid-template-columns:1fr 1fr;}.adm-modules{grid-template-columns:1fr;}}
@media(max-width:600px) {
    .adm-stats { grid-template-columns: 1fr; }
    .um-topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
}
@media(max-width:900px) {
    .page-footer-row { flex-direction: column; justify-content: center; text-align: center; }
    .page-footer-copy { flex: auto; order: 2; margin-top: 4px; }
    .page-footer-meta { text-align: center; order: 1; }
}

/* Modal Vanilla CSS */
.modal-overlay { position: fixed; top: 0; right: 0; bottom: 0; left: 0; z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal-backdrop { position: fixed; top: 0; right: 0; bottom: 0; left: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); }
.modal-container { position: relative; width: 100%; max-width: 512px; background: #111827; border: 1px solid #1f2937; border-radius: 1rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); z-index: 1001; overflow: hidden; }
.z-1000 { z-index: 1000; }
.overflow-y-auto { overflow-y: auto; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.min-h-screen { min-height: 100vh; }
.bg-black { background-color: #000; }
.bg-opacity-70 { --tw-bg-opacity: 0.7; background-color: rgb(0 0 0 / var(--tw-bg-opacity)); }
.backdrop-blur-sm { backdrop-filter: blur(4px); }
.bg-gray-900 { background-color: #111827; }
.bg-gray-800 { background-color: #1f2937; }
.border-gray-800 { border-color: #1f2937; }
.border-gray-700 { border-color: #374151; }
.rounded-2xl { border-radius: 1rem; }
.rounded-lg { border-radius: 0.5rem; }
.shadow-2xl { box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25); }
.w-full { width: 100%; }
.max-w-lg { max-width: 32rem; }
.p-6 { padding: 1.5rem; }
.px-6 { padding-left: 1.5rem; padding-right: 1.5rem; }
.py-4 { padding-top: 1rem; padding-bottom: 1rem; }
.mb-5 { margin-bottom: 1.25rem; }
.text-lg { font-size: 1.125rem; line-height: 1.75rem; }
.font-bold { font-weight: 700; }
.text-gray-500 { color: #6b7280; }
.text-gray-300 { color: #d1d5db; }
.text-xs { font-size: 0.75rem; line-height: 1rem; }
.uppercase { text-transform: uppercase; }
.tracking-wider { letter-spacing: 0.05em; }
.grid { display: grid; }
.grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.gap-4 { gap: 1rem; }
.col-span-2 { grid-column: span 2 / span 2; }
.outline-none { outline: 2-bit transparent; }
.transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
</style>

<div class="um-shell">

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

<div class="um-body-row">

{{-- ════════ SIDEBAR ════════ --}}
<aside class="um-sidebar">
    <a href="{{ route('mock-portal.dao.dashboard') }}" class="um-profile">
        <div class="adm-brand-icon" style="background: linear-gradient(135deg, #00A3DD, #006fa3);"><i class="fas fa-graduation-cap"></i></div>
        <div>
            <div class="adm-brand-text">TASIDO 2026</div>
            <div class="adm-brand-sub">DAO Portal</div>
        </div>
    </a>
    
    <nav class="um-nav">
        <!-- USERS -->
        <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'users']) }}" class="{{ $tab === 'users' ? 'active' : '' }}">
            <span class="nav-ico"><i class="fas fa-users"></i></span> 
            Users
        </a>

        <!-- SCHOOLS -->
        <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'schools']) }}" class="{{ $tab === 'schools' ? 'active' : '' }}">
            <span class="nav-ico"><i class="fas fa-school"></i></span> 
            Schools
        </a>

        <!-- CANDIDATES -->
        <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'candidates']) }}" class="{{ $tab === 'candidates' ? 'active' : '' }}">
            <span class="nav-ico"><i class="fas fa-user-graduate"></i></span> 
            Candidates
        </a>

        <!-- ERRORS -->
        <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'errors']) }}" class="{{ $tab === 'errors' ? 'active' : '' }}">
            <span class="nav-ico"><i class="fas fa-triangle-exclamation"></i></span> 
            Data Integrity
        </a>
    </nav>
    <div class="um-footer">
        <form method="POST" action="/logout" style="margin:0;">
            @csrf
            <button type="submit" class="um-logout"><i class="fas fa-right-from-bracket"></i> Logout</button>
        </form>
    </div>
</aside>

{{-- ════════ MAIN CONTENT ════════ --}}
<div class="um-main">
    <header class="um-topbar">
        <div>
            <div class="um-topbar-title">REGISTRATION CONTROL CENTRE</div>
            <div class="um-topbar-sub">Standard VII Mock Examination 2026 — District Registration Portal</div>
        </div>
        <div style="display:flex; align-items:center; gap: 16px;">
            <div class="um-date-pill" id="um-date">—</div>
            <div class="um-clock" id="um-clock">--:--</div>
            
            <div class="adm-user">
                <div class="adm-user-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                <div>
                    <div class="adm-user-name">{{ auth()->user()->name }}</div>
                    <div class="adm-user-role">District Academic Officer</div>
                </div>
            </div>
        </div>
    </header>

    <div class="um-content">
        @if(session('success'))
            <div style="background:rgba(30,181,58,0.15); border:1px solid rgba(30,181,58,0.3); color:#6ae086; padding:12px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px;">
                <i class="fas fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(!in_array($tab, ['users', 'candidates', 'errors']))
        <div class="adm-stats">
            <div class="adm-stat stat-blue">
                <i class="fas fa-users adm-stat-icon"></i>
                <div class="adm-stat-label">Total Users</div>
                <div class="adm-stat-value">{{ number_format($stats['users_count'] ?? 0) }}</div>
                <div class="adm-stat-desc">Registered Headteachers</div>
            </div>
            <div class="adm-stat stat-green">
                <i class="fas fa-user-graduate adm-stat-icon"></i>
                <div class="adm-stat-label">Candidates</div>
                <div class="adm-stat-value">{{ number_format($stats['candidates_count'] ?? 0) }}</div>
                <div class="adm-stat-desc">Candidates in your district</div>
            </div>
            <div class="adm-stat stat-yellow">
                <i class="fas fa-school adm-stat-icon"></i>
                <div class="adm-stat-label">Schools</div>
                <div class="adm-stat-value">{{ number_format($stats['schools_count'] ?? 0) }}</div>
                <div class="adm-stat-desc">Schools in your district</div>
            </div>
            <div class="adm-stat stat-black">
                <i class="fas fa-file-signature adm-stat-icon"></i>
                <div class="adm-stat-label">Data Integrity</div>
                <div class="adm-stat-value">{{ number_format($stats['errors_count'] ?? 0) }}</div>
                <div class="adm-stat-desc">Active district integrity flags</div>
            </div>
        </div>
        @endif

        {{-- Module Cards --}}
        
        @if($tab === 'schools')
        <div class="adm-modules" style="grid-template-columns: 1fr;" id="schools-section">
            <div class="adm-card">
                <div class="adm-card-head">
                    <div class="adm-card-title"><i class="fas fa-school" style="color:#67d8ff"></i> Schools Management</div>
                    <span class="adm-card-badge badge-blue">Manage</span>
                </div>
                <div class="adm-card-body" style="padding: 20px;">
                    <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
                        <form action="{{ route('mock-portal.dao.dashboard') }}" method="GET" style="display:flex; gap: 8px; flex: 1; max-width: 400px;">
                            <input type="hidden" name="tab" value="schools">
                            <div style="position: relative; flex: 1;">
                                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.8rem;"></i>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search schools..." 
                                    style="width: 100%; padding: 8px 12px 8px 35px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem;">
                            </div>
                            <button type="submit" style="background:var(--tz-blue); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">Search</button>
                            @if(request('search'))
                                <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'schools']) }}" style="background:rgba(255,255,255,0.1); color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:0.85rem;">Clear</a>
                            @endif
                        </form>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <a href="{{ route('mock-portal.dao.download-cal-zip') }}" style="background:#0f9f6e; color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; text-decoration:none; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                                <i class="fas fa-file-archive"></i> Download CAL Zip
                            </a>
                            <a href="{{ route('mock-portal.dao.schools.report.pdf', ['tab' => 'schools', 'search' => request('search')]) }}" style="background:#b35c00; color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; text-decoration:none; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                                <i class="fas fa-file-pdf"></i> PDF Report
                            </a>
                            <button onclick="openModal()" style="background:var(--tz-yellow); color:#000; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; text-decoration:none; font-size:0.85rem; font-weight:700; display:inline-flex; align-items:center; gap:8px;"><i class="fas fa-plus"></i> Add New School</button>
                        </div>
                    </div>
                    <table style="width:100%; border-collapse: collapse; color: var(--tz-text); font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align:left;">
                                <th style="padding: 10px; width: 100px;">Centre No.</th>
                                <th style="padding: 10px;">School Name</th>
                                <th style="padding: 10px; text-align: center;">Ownership</th>
                                <th style="padding: 10px; text-align: center;">Candidates</th>
                                <th style="padding: 10px; text-align: center;">Status</th>
                                <th style="padding: 10px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($schools as $school)
                                <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                    <td style="padding: 12px 10px; font-family:monospace; color:var(--tz-blue);">{{ $school->code }}</td>
                                    <td style="padding: 12px 10px;">{{ $school->name }}</td>
                                    <td style="padding: 12px 10px; text-align: center;">
                                        <span style="font-size:0.7rem; border:1px solid rgba(255,255,255,0.1); padding:2px 8px; border-radius:10px; color:#aaa;">{{ $school->ownership ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 12px 10px; text-align: center;">
                                        <span style="font-size:0.85rem; font-weight:700; color:#fff; background:rgba(0,163,221,0.1); padding:4px 10px; border-radius:6px; border:1px solid rgba(0,163,221,0.2);">
                                            {{ number_format($school->candidates_count) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 10px; text-align: center;">
                                        <span style="font-size:0.7rem; background:rgba(30,181,58,0.1); color:#6ae086; padding:2px 8px; border-radius:10px; border:1px solid rgba(30,181,58,0.2);">Active</span>
                                    </td>
                                    <td style="padding: 12px 10px; display: flex; gap: 12px; align-items: center;">
                                        <button onclick="openViewModal({{ json_encode($school->only(['id', 'name', 'code', 'school_type', 'ownership'])) }})" style="background:none; border:none; color:var(--tz-blue); cursor:pointer; font-size:0.8rem; padding:0; display:flex; align-items:center; gap:4px;" title="View Details"><i class="fas fa-eye"></i> View</button>
                                        
                                        <button onclick="openEditModal({{ json_encode($school->only(['id', 'name', 'code', 'school_type', 'ownership'])) }})" style="background:none; border:none; color:var(--tz-yellow); cursor:pointer; font-size:0.8rem; padding:0; display:flex; align-items:center; gap:4px;" title="Edit School"><i class="fas fa-pen"></i> Edit</button>
                                        
                                        <button type="button" onclick='openDeleteModal({{ json_encode($school->only(["id", "name", "code"])) }})' style="background:none; border:none; color:#ff4d4d; cursor:pointer; font-size:0.8rem; padding:0; display:flex; align-items:center; gap:4px;" title="Delete School">
                                            <i class="fas fa-trash-can"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 20px; text-align: center; color: var(--tz-muted);">No schools found in this district.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($schools->lastPage() > 1)
                    @php($schoolPages = $buildVisiblePages($schools))
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04), rgba(255,255,255,0.02)); padding: 18px 20px; border-radius: 0 0 14px 14px;">
                        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:0.85rem; color:rgba(255,255,255,0.72);">
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(0,163,221,0.10); border:1px solid rgba(0,163,221,0.22); color:#67d8ff; font-weight:700;">
                                    <i class="fas fa-layer-group" style="font-size:0.72rem;"></i>
                                    <span>Page {{ $schools->currentPage() }} of {{ max($schools->lastPage(), 1) }}</span>
                                </div>
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
                                    <i class="fas fa-table-list" style="font-size:0.72rem; color:rgba(255,255,255,0.45);"></i>
                                    <span>Showing <strong style="color:#fff;">{{ $schools->count() }}</strong> of <strong style="color:#fff;">{{ $schools->total() }}</strong> schools</span>
                                </div>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; justify-content:flex-end;">
                                <a href="{{ $schools->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $schools->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $schools->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-angles-left" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $schools->previousPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $schools->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $schools->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
                                    <span style="font-size:0.85rem; font-weight:700;">Previous</span>
                                </a>
                                <div style="display:flex; align-items:center; gap:8px; padding:8px; border-radius:16px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                                    @foreach($schoolPages as $page)
                                        <a href="{{ $schools->url($page) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:36px; padding:0 12px; border-radius:12px; text-decoration:none; font-size:0.85rem; font-weight:700; {{ $schools->currentPage() === $page ? 'background:#00A3DD; color:#fff; box-shadow:0 10px 24px rgba(0,163,221,0.28);' : 'color:#d1d5db; background:transparent;' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ $schools->nextPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $schools->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $schools->hasMorePages() ? 'auto' : 'none' }};">
                                    <span style="font-size:0.85rem; font-weight:700;">Next</span>
                                    <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $schools->url($schools->lastPage()) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $schools->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $schools->hasMorePages() ? 'auto' : 'none' }};">
                                    <i class="fas fa-angles-right" style="font-size:0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @elseif($tab === 'users')
        <div class="adm-stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
            <div class="adm-stat stat-blue">
                <i class="fas fa-users adm-stat-icon"></i>
                <div class="adm-stat-label">Total Headteachers</div>
                <div class="adm-stat-value">{{ number_format($userStats['total'] ?? 0) }}</div>
                <div class="adm-stat-desc">Registered in your district</div>
            </div>
            <div class="adm-stat stat-green">
                <i class="fas fa-person adm-stat-icon"></i>
                <div class="adm-stat-label">Male</div>
                <div class="adm-stat-value">{{ number_format($userStats['male'] ?? 0) }}</div>
                <div class="adm-stat-desc">Active male headteachers</div>
            </div>
            <div class="adm-stat stat-yellow">
                <i class="fas fa-person-dress adm-stat-icon"></i>
                <div class="adm-stat-label">Female</div>
                <div class="adm-stat-value">{{ number_format($userStats['female'] ?? 0) }}</div>
                <div class="adm-stat-desc">Active female headteachers</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-users" style="color:var(--tz-blue)"></i> Headteachers User Management</div>
                <span class="adm-card-badge badge-blue">View Only</span>
            </div>
            <div class="adm-card-body" style="padding: 20px;">
                <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px; margin-bottom: 20px;">
                    <form action="{{ route('mock-portal.dao.dashboard') }}" method="GET" style="display:flex; gap: 8px; flex: 1; max-width: 400px;">
                        <input type="hidden" name="tab" value="users">
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.8rem;"></i>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search headteachers..." 
                                style="width: 100%; padding: 8px 12px 8px 35px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem;">
                        </div>
                        <button type="submit" style="background:var(--tz-blue); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">Search</button>
                        @if(request('search'))
                            <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'users']) }}" style="background:rgba(255,255,255,0.1); color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:0.85rem;">Clear</a>
                        @endif
                    </form>
                    <div style="color: #9ca3af; font-size: 0.8rem; font-style: italic;">
                        <i class="fas fa-info-circle"></i> DAO is only allowed to view users.
                    </div>
                </div>
                
                <table style="width:100%; border-collapse: collapse; color: var(--tz-text); font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align:left;">
                            <th style="padding: 10px;">#</th>
                            <th style="padding: 10px;">Full Name</th>
                            <th style="padding: 10px; text-align: center;">Gender</th>
                            <th style="padding: 10px;">Phone Number</th>
                            <th style="padding: 10px;">Centre No.</th>
                            <th style="padding: 10px;">School Name</th>
                            <th style="padding: 10px; text-align: center;">Ownership</th>
                            <th style="padding: 10px; text-align: center;">Status</th>
                            <th style="padding: 10px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($headteachers as $index => $ht)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 12px 10px; color: #9ca3af;">{{ ($headteachers->currentPage() - 1) * $headteachers->perPage() + $index + 1 }}</td>
                                <td style="padding: 12px 10px; font-weight: 600;">{{ $ht->name }}</td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-size:0.75rem; color:{{ $ht->gender === 'M' ? '#67d8ff' : '#ff85c0' }}; font-weight: bold;">
                                        {{ $ht->gender }}
                                    </span>
                                </td>
                                <td style="padding: 12px 10px;">{{ $ht->phone ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px; font-family:monospace; color:var(--tz-blue);">{{ $ht->school?->code ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px;">{{ $ht->school?->name ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-size:0.7rem; border:1px solid rgba(255,255,255,0.1); padding:2px 8px; border-radius:10px; color:#aaa;">{{ $ht->school?->ownership ?? 'N/A' }}</span>
                                </td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-size:0.7rem; background:rgba(30,181,58,0.1); color:#6ae086; padding:2px 8px; border-radius:10px; border:1px solid rgba(30,181,58,0.2);">{{ $ht->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <button onclick='alert("Full Profile for {{ $ht->name }} viewing is being integrated.")' style="background:none; border:none; color:var(--tz-blue); cursor:pointer; font-size:0.8rem; padding:0; display:inline-flex; align-items:center; gap:4px;" title="View User"><i class="fas fa-eye"></i> View</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding: 20px; text-align: center; color: var(--tz-muted);">No headteachers found in this district.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if($headteachers->lastPage() > 1)
                    @php($headteacherPages = $buildVisiblePages($headteachers))
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04), rgba(255,255,255,0.02)); padding: 18px 20px; border-radius: 0 0 14px 14px;">
                        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:0.85rem; color:rgba(255,255,255,0.72);">
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(0,163,221,0.10); border:1px solid rgba(0,163,221,0.22); color:#67d8ff; font-weight:700;">
                                    <i class="fas fa-layer-group" style="font-size:0.72rem;"></i>
                                    <span>Page {{ $headteachers->currentPage() }} of {{ max($headteachers->lastPage(), 1) }}</span>
                                </div>
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
                                    <i class="fas fa-table-list" style="font-size:0.72rem; color:rgba(255,255,255,0.45);"></i>
                                    <span>Showing <strong style="color:#fff;">{{ $headteachers->count() }}</strong> of <strong style="color:#fff;">{{ $headteachers->total() }}</strong> users</span>
                                </div>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; justify-content:flex-end;">
                                <a href="{{ $headteachers->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $headteachers->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $headteachers->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-angles-left" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $headteachers->previousPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $headteachers->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $headteachers->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
                                    <span style="font-size:0.85rem; font-weight:700;">Previous</span>
                                </a>
                                <div style="display:flex; align-items:center; gap:8px; padding:8px; border-radius:16px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                                    @foreach($headteacherPages as $page)
                                        <a href="{{ $headteachers->url($page) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:36px; padding:0 12px; border-radius:12px; text-decoration:none; font-size:0.85rem; font-weight:700; {{ $headteachers->currentPage() === $page ? 'background:#00A3DD; color:#fff; box-shadow:0 10px 24px rgba(0,163,221,0.28);' : 'color:#d1d5db; background:transparent;' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ $headteachers->nextPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $headteachers->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $headteachers->hasMorePages() ? 'auto' : 'none' }};">
                                    <span style="font-size:0.85rem; font-weight:700;">Next</span>
                                    <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $headteachers->url($headteachers->lastPage()) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $headteachers->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $headteachers->hasMorePages() ? 'auto' : 'none' }};">
                                    <i class="fas fa-angles-right" style="font-size:0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @elseif($tab === 'candidates')
        <div class="adm-stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
            <div class="adm-stat stat-blue">
                <i class="fas fa-user-graduate adm-stat-icon"></i>
                <div class="adm-stat-label">Total Candidates</div>
                <div class="adm-stat-value">{{ number_format($candidateStats['total'] ?? 0) }}</div>
                <div class="adm-stat-desc">Registered in your district</div>
            </div>
            <div class="adm-stat stat-green">
                <i class="fas fa-child-rearing adm-stat-icon"></i>
                <div class="adm-stat-label">Boys</div>
                <div class="adm-stat-value">{{ number_format($candidateStats['male'] ?? 0) }}</div>
                <div class="adm-stat-desc">Registered male students</div>
            </div>
            <div class="adm-stat stat-yellow">
                <i class="fas fa-child-dress adm-stat-icon"></i>
                <div class="adm-stat-label">Girls</div>
                <div class="adm-stat-value">{{ number_format($candidateStats['female'] ?? 0) }}</div>
                <div class="adm-stat-desc">Registered female students</div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-user-graduate" style="color:var(--tz-green)"></i> Candidates Management</div>
                <span class="adm-card-badge badge-green">View Only</span>
            </div>
            <div class="adm-card-body" style="padding: 20px;">
                <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px; margin-bottom: 20px;">
                    <form id="candidate-filters-form" action="{{ route('mock-portal.dao.dashboard') }}" method="GET" style="display:flex; gap: 8px; flex: 1; max-width: 1000px;">
                        <input type="hidden" name="tab" value="candidates">
                        <select name="school_id" id="school-filter" style="padding: 8px 12px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem; width: 450px; min-width: 400px;">
                            <option value="">All Schools in District</option>
                            @foreach($districtSchools as $ds)
                                <option value="{{ $ds->id }}" {{ request('school_id') == $ds->id ? 'selected' : '' }}>{{ $ds->code }} - {{ $ds->name }}</option>
                            @endforeach
                        </select>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const filterForm = document.getElementById('candidate-filters-form');
                                const schoolFilter = document.getElementById('school-filter');
                                const candidateSearch = document.getElementById('candidate-search');

                                if (schoolFilter) {
                                    const schoolSelect = new TomSelect("#school-filter", {
                                        create: false,
                                        sortField: {
                                            field: "text",
                                            direction: "asc"
                                        },
                                        placeholder: "Select or search for a school...",
                                        allowEmptyOption: true
                                    });

                                    schoolSelect.on('change', function () {
                                        filterForm?.requestSubmit();
                                    });
                                }

                                if (filterForm && candidateSearch) {
                                    let searchDebounce;

                                    candidateSearch.addEventListener('input', function () {
                                        clearTimeout(searchDebounce);
                                        searchDebounce = setTimeout(() => filterForm.requestSubmit(), 300);
                                    });
                                }
                            });
                        </script>
                        <div style="position: relative; flex: 1; min-width: 300px;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.8rem;"></i>
                            <input id="candidate-search" type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, index or school..." 
                                style="width: 100%; padding: 8px 12px 8px 35px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem;">
                        </div>
                        <button type="submit" style="background:var(--tz-green); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">Search</button>
                        @if(request('search') || request('school_id'))
                            <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'candidates']) }}" style="background:rgba(255,255,255,0.1); color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:0.85rem;">Clear</a>
                        @endif
                    </form>
                </div>
                
                <table style="width:100%; border-collapse: collapse; color: var(--tz-text); font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align:left;">
                            <th style="padding: 10px; width: 40px;">#</th>
                            <th style="padding: 10px; width: 130px;">Index Number</th>
                            <th style="padding: 10px; width: 130px;">PREM No.</th>
                                <th style="padding: 10px;">Full Name</th>
                                <th style="padding: 10px; text-align: center;">Sex</th>
                            <th style="padding: 10px; text-align: center;">Centre No.</th>
                            <th style="padding: 10px;">School Name</th>
                            <th style="padding: 10px; text-align: center;">Status</th>
                            <th style="padding: 10px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($candidatesList as $index => $can)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 12px 10px; color: #9ca3af;">{{ ($candidatesList->currentPage() - 1) * $candidatesList->perPage() + $index + 1 }}</td>
                                <td style="padding: 12px 10px; font-family:monospace; color:var(--tz-yellow); font-weight: 600;">{{ $can->candidate_id ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px; font-family:monospace; color:var(--tz-green);">{{ $can->prem_no ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px;">{{ $can->full_name }}</td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-weight: bold; color:{{ $can->gender === 'M' ? '#67d8ff' : '#ff85c0' }};">{{ $can->gender }}</span>
                                </td>
                                <td style="padding: 12px 10px; text-align: center; font-family:monospace; color:var(--tz-blue);">{{ $can->school?->code ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px;">{{ $can->school?->name ?? 'N/A' }}</td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-size:0.7rem; background:rgba(30,181,58,0.1); color:#6ae086; padding:2px 8px; border-radius:10px; border:1px solid rgba(30,181,58,0.2);">Registered</span>
                                </td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <button onclick='alert("Candidate File for {{ $can->full_name }} is being loaded...")' style="background:none; border:none; color:var(--tz-green); cursor:pointer; font-size:0.8rem; padding:0; display:inline-flex; align-items:center; gap:4px;"><i class="fas fa-file-invoice"></i> File</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="padding: 20px; text-align: center; color: var(--tz-muted);">No candidates registered in this district yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if($candidatesList->lastPage() > 1)
                    @php($candidatePages = $buildVisiblePages($candidatesList))
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04), rgba(255,255,255,0.02)); padding: 18px 20px; border-radius: 0 0 14px 14px; overflow-x:auto;">
                        <div style="display:flex; flex-wrap:nowrap; gap:14px; align-items:center; justify-content:space-between; min-width:max-content;">
                            <div style="display:flex; flex-wrap:nowrap; align-items:center; gap:10px; font-size:0.85rem; color:rgba(255,255,255,0.72); white-space:nowrap; flex-shrink:0;">
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(0,163,221,0.10); border:1px solid rgba(0,163,221,0.22); color:#67d8ff; font-weight:700;">
                                    <i class="fas fa-layer-group" style="font-size:0.72rem;"></i>
                                    <span>Page {{ $candidatesList->currentPage() }} of {{ max($candidatesList->lastPage(), 1) }}</span>
                                </div>
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
                                    <i class="fas fa-table-list" style="font-size:0.72rem; color:rgba(255,255,255,0.45);"></i>
                                    <span>Showing <strong style="color:#fff;">{{ $candidatesList->count() }}</strong> of <strong style="color:#fff;">{{ $candidatesList->total() }}</strong> candidates</span>
                                </div>
                            </div>
                            <div style="display:flex; flex-wrap:nowrap; align-items:center; gap:8px; justify-content:flex-end; white-space:nowrap;">
                                <a href="{{ $candidatesList->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidatesList->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $candidatesList->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-angles-left" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $candidatesList->previousPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidatesList->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $candidatesList->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
                                    <span style="font-size:0.85rem; font-weight:700;">Previous</span>
                                </a>
                                <div style="display:flex; align-items:center; gap:8px; padding:8px; border-radius:16px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                                    @foreach($candidatePages as $page)
                                        <a href="{{ $candidatesList->url($page) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:36px; padding:0 12px; border-radius:12px; text-decoration:none; font-size:0.85rem; font-weight:700; {{ $candidatesList->currentPage() === $page ? 'background:#00A3DD; color:#fff; box-shadow:0 10px 24px rgba(0,163,221,0.28);' : 'color:#d1d5db; background:transparent;' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ $candidatesList->nextPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidatesList->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $candidatesList->hasMorePages() ? 'auto' : 'none' }};">
                                    <span style="font-size:0.85rem; font-weight:700;">Next</span>
                                    <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $candidatesList->url($candidatesList->lastPage()) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidatesList->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $candidatesList->hasMorePages() ? 'auto' : 'none' }};">
                                    <i class="fas fa-angles-right" style="font-size:0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @elseif($tab === 'errors')
        <div class="adm-stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 2rem;">
            <div class="adm-stat stat-black">
                <i class="fas fa-list-check adm-stat-icon"></i>
                <div class="adm-stat-label">Total Detected Issues</div>
                <div class="adm-stat-value">{{ $errorStats['total'] }}</div>
                <div class="adm-stat-desc">District-wide integrity flags</div>
            </div>
            <div class="adm-stat" style="background:linear-gradient(135deg,#3d1a1a,#5c2a2a); color:#fca5a5; border-color:rgba(239,68,68,0.2);">
                <i class="fas fa-circle-exclamation adm-stat-icon" style="opacity:0.3;"></i>
                <div class="adm-stat-label">Critical Errors</div>
                <div class="adm-stat-value">{{ $errorStats['critical'] }}</div>
                <div class="adm-stat-desc">Immediate action required</div>
            </div>
            <div class="adm-stat stat-yellow">
                <i class="fas fa-triangle-exclamation adm-stat-icon"></i>
                <div class="adm-stat-label">Warnings</div>
                <div class="adm-stat-value">{{ $errorStats['warning'] }}</div>
                <div class="adm-stat-desc">Records needing review</div>
            </div>
        </div>

        <div class="adm-card" style="margin-bottom: 2rem; border-color: rgba(0,163,221,.14);">
            <div class="adm-card-body" style="padding: 18px 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:1rem; font-weight:700; color:#f0e6c8; margin-bottom:6px; display:flex; align-items:center; gap:10px;">
                            <i class="fas fa-shield-halved" style="color:var(--tz-blue);"></i>
                            District Data Integrity Workspace
                        </div>
                        <div style="font-size:0.84rem; color:rgba(255,255,255,.58);">
                            Review missing candidate identifiers, empty schools, and registration gaps before final submission.
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="adm-card-badge badge-blue" style="padding:6px 10px;">Candidate Issues: {{ number_format($errorStats['candidate'] ?? 0) }}</span>
                        <span class="adm-card-badge badge-yellow" style="padding:6px 10px;">School Issues: {{ number_format($errorStats['school'] ?? 0) }}</span>
                        <span class="adm-card-badge" style="padding:6px 10px; background:rgba(239,68,68,0.1); color:#fca5a5; border:1px solid rgba(239,68,68,0.2);">District Review Required</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-triangle-exclamation" style="color:var(--tz-yellow)"></i> Data Integrity Review Queue</div>
                <span class="adm-card-badge" style="background:rgba(239,68,68,0.1); color:#fca5a5; border:1px solid rgba(239,68,68,0.2);">Action Window Open</span>
            </div>
            <div class="adm-card-body" style="padding: 20px;">
                <div style="display:flex; justify-content: space-between; align-items:center; gap: 10px; margin-bottom: 25px;">
                    <form action="{{ route('mock-portal.dao.dashboard') }}" method="GET" style="display:flex; gap: 8px; flex: 1; max-width: 900px;">
                        <input type="hidden" name="tab" value="errors">
                        <select name="error_category" onchange="this.form.submit()" style="padding: 8px 12px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem; width: 220px;">
                            <option value="">Filter by Category</option>
                            <option value="Candidate" {{ request('error_category') == 'Candidate' ? 'selected' : '' }}>Candidate Errors</option>
                            <option value="School" {{ request('error_category') == 'School' ? 'selected' : '' }}>School Errors</option>
                        </select>
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.8rem;"></i>
                            <input type="text" name="error_search" value="{{ request('error_search') }}" placeholder="Search by student or school name..." 
                                style="width: 100%; padding: 8px 12px 8px 35px; background: rgba(31,41,55,0.5); border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; color: white; font-size: 0.85rem;">
                        </div>
                        <button type="submit" style="background:var(--tz-green); color:#fff; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:700;">Filter</button>
                        @if(request('error_search') || request('error_category'))
                            <a href="{{ route('mock-portal.dao.dashboard', ['tab' => 'errors']) }}" style="background:rgba(255,255,255,0.1); color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:0.85rem;">Clear</a>
                        @endif
                    </form>
                    <div style="color: #9ca3af; font-size: 0.8rem; font-style: italic;">
                        Showing {{ $errorsList->count() }} integrity issues
                    </div>
                </div>
                
                <table style="width:100%; border-collapse: collapse; color: var(--tz-text); font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.1); text-align:left;">
                            <th style="padding: 10px; width: 40px;">#</th>
                            <th style="padding: 10px; width: 100px; text-align: center;">Severity</th>
                            <th style="padding: 10px; width: 120px;">Category</th>
                            <th style="padding: 10px;">Issue Description</th>
                            <th style="padding: 10px;">School Affected</th>
                            <th style="padding: 10px; text-align: center;">Suggested Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($errorsList as $idx => $err)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td style="padding: 12px 10px; color: #9ca3af;">{{ ($errorsList->currentPage() - 1) * $errorsList->perPage() + $idx + 1 }}</td>
                                <td style="padding: 12px 10px; text-align: center;">
                                    <span style="font-size:0.65rem; padding:2px 8px; border-radius:10px; font-weight:bold; background:{{ $err['type'] === 'Critical' ? 'rgba(239,68,68,0.1)' : 'rgba(252,209,22,0.1)' }}; color:{{ $err['type'] === 'Critical' ? '#fca5a5' : '#FCD116' }};">
                                        {{ $err['type'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 10px; font-weight: 600;">{{ $err['category'] }}</td>
                                <td style="padding: 12px 10px; color: #d1d5db;">{{ $err['description'] }}</td>
                                <td style="padding: 12px 10px;">{{ $err['school'] }}</td>
                                <td style="padding: 12px 10px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <span style="font-size:0.75rem; color:var(--tz-blue); border: 1px solid rgba(0,163,221,0.3); padding: 2px 10px; border-radius: 4px;">{{ $err['action'] }}</span>
                                    <button type="button" onclick="openRejectModal('{{ $err['id'] }}', '{{ $err['category'] }}', '{{ addslashes($err['description']) }}')" style="background:rgba(239,68,68,0.1); color:#ff4d4d; border:1px solid rgba(239,68,68,0.3); padding:2px 8px; border-radius:4px; font-size:0.75rem; cursor:pointer; font-weight:700;">
                                        <i class="fas fa-times-circle"></i> Reject
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 40px; text-align: center;">
                                    <div style="color: #6ae086; font-size: 1.1rem; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> No Data Errors Found</div>
                                    <p style="color: #9ca3af; font-size: 0.85rem;">All registration data in your district is currently consistent and complete.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @if($errorsList->lastPage() > 1)
                    @php($errorPages = $buildVisiblePages($errorsList))
                    <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04), rgba(255,255,255,0.02)); padding: 18px 20px; border-radius: 0 0 14px 14px;">
                        <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:0.85rem; color:rgba(255,255,255,0.72);">
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(0,163,221,0.10); border:1px solid rgba(0,163,221,0.22); color:#67d8ff; font-weight:700;">
                                    <i class="fas fa-layer-group" style="font-size:0.72rem;"></i>
                                    <span>Page {{ $errorsList->currentPage() }} of {{ max($errorsList->lastPage(), 1) }}</span>
                                </div>
                                <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
                                    <i class="fas fa-table-list" style="font-size:0.72rem; color:rgba(255,255,255,0.45);"></i>
                                    <span>Showing <strong style="color:#fff;">{{ $errorsList->count() }}</strong> of <strong style="color:#fff;">{{ $errorsList->total() }}</strong> errors</span>
                                </div>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; justify-content:flex-end;">
                                <a href="{{ $errorsList->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $errorsList->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $errorsList->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-angles-left" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $errorsList->previousPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $errorsList->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $errorsList->onFirstPage() ? 'none' : 'auto' }};">
                                    <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
                                    <span style="font-size:0.85rem; font-weight:700;">Previous</span>
                                </a>
                                <div style="display:flex; align-items:center; gap:8px; padding:8px; border-radius:16px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                                    @foreach($errorPages as $page)
                                        <a href="{{ $errorsList->url($page) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:36px; padding:0 12px; border-radius:12px; text-decoration:none; font-size:0.85rem; font-weight:700; {{ $errorsList->currentPage() === $page ? 'background:#00A3DD; color:#fff; box-shadow:0 10px 24px rgba(0,163,221,0.28);' : 'color:#d1d5db; background:transparent;' }}">
                                            {{ $page }}
                                        </a>
                                    @endforeach
                                </div>
                                <a href="{{ $errorsList->nextPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $errorsList->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $errorsList->hasMorePages() ? 'auto' : 'none' }};">
                                    <span style="font-size:0.85rem; font-weight:700;">Next</span>
                                    <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                                </a>
                                <a href="{{ $errorsList->url($errorsList->lastPage()) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $errorsList->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $errorsList->hasMorePages() ? 'auto' : 'none' }};">
                                    <i class="fas fa-angles-right" style="font-size:0.75rem;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>{{-- end content --}}
</div>{{-- end main --}}

</div>{{-- end body-row --}}

{{-- ════════ FOOTER ════════ --}}
<footer class="page-footer">
    <div class="page-footer-stripes" aria-hidden="true">
        <span style="background:var(--tz-green);"></span>
        <span style="background:var(--tz-yellow);"></span>
        <span style="background:#000000;"></span>
        <span style="background:var(--tz-blue);"></span>
    </div>
    <div class="page-footer-body">
        <div class="page-footer-row">
            <div class="page-footer-copy">
                <p>Copyright &copy; {{ now()->year }} Integrated Results Management System | All Rights Reserved</p>
            </div>
            <div class="page-footer-meta">
                Developed By <strong class="footer-brand">ProSmart Technologies</strong>
            </div>
        </div>
    </div>
</footer>

</div>{{-- end shell --}}

{{-- ════════ ADD SCHOOL MODAL ════════ --}}
<div id="schoolModal" class="modal-overlay" style="display: none;">
    <div class="modal-backdrop" onclick="closeModal()"></div>

    <div class="modal-container">
        <div style="padding: 1.5rem; border-bottom: 1px solid #1f2937; background: rgba(17,24,39,0.5); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #f0e6c8; margin: 0;">Add New School</h3>
            <button onclick="closeModal()" style="color: #6b7280; background: none; border: none; cursor: pointer; font-size: 1.25rem;"><i class="fas fa-times"></i></button>
        </div>
            <form action="{{ route('mock-portal.dao.schools.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="council_id" value="{{ $district->id ?? '' }}">
                <input type="hidden" name="region_id" value="{{ $district->region_id ?? '' }}">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Region</label>
                        <div style="padding: 0.75rem; background: rgba(31,41,55,0.5); border: 1px solid #374151; border-radius: 0.5rem; font-size: 0.875rem; color: #d1d5db;">
                            <i class="fas fa-location-dot" style="color: #BBA45E; margin-right: 0.5rem;"></i> 
                            {{ $district?->region?->name ?? 'Default Region' }}
                        </div>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">District</label>
                        <div style="padding: 0.75rem; background: rgba(31,41,55,0.5); border: 1px solid #374151; border-radius: 0.5rem; font-size: 0.875rem; color: #d1d5db;">
                            <i class="fas fa-building-columns" style="color: #BBA45E; margin-right: 0.5rem;"></i> 
                            {{ $district?->name ?? 'Default District' }}
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div style="grid-column: span 2;">
                        <label for="name" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">School Name</label>
                        <input type="text" name="name" id="name" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;" placeholder="e.g. MAJI YA CHAI PRIMARY SCHOOL">
                    </div>
                    <div>
                        <label for="code" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Centre Number</label>
                        <input type="text" name="code" id="code" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;" placeholder="e.g. PS0101001">
                    </div>
                    <div>
                        <label for="school_type" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">School Type</label>
                        <select name="school_type" id="school_type" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                            <option value="PRIMARY">PRIMARY</option>
                            <option value="SECONDARY">SECONDARY</option>
                            <option value="BOTH">BOTH</option>
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label for="ownership" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">Ownership</label>
                        <select name="ownership" id="ownership" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                            <option value="GOVERNMENT">GOVERNMENT</option>
                            <option value="NON-GOVERNMENT">NON-GOVERNMENT</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #1f2937;">
                    <button type="button" onclick="closeModal()" style="background: none; border: none; color: #9ca3af; font-size: 0.875rem; font-weight: 600; cursor: pointer; padding: 0.625rem 1rem; transition: color 0.2s;">Cancel</button>
                    <button type="submit" style="background: #00A3DD; border: none; color: #fff; font-size: 0.875rem; font-weight: 700; border-radius: 0.5rem; padding: 0.625rem 1.5rem; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 163, 221, 0.3); transition: transform 0.1s, opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'" onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                        Create School
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ════════ VIEW SCHOOL MODAL ════════ --}}
<div id="viewModal" class="modal-overlay" style="display: none;">
    <div class="modal-backdrop" onclick="closeViewModal()"></div>
    <div class="modal-container">
        <div style="padding: 1.5rem; border-bottom: 1px solid #1f2937; background: rgba(17,24,39,0.5); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #f0e6c8; margin: 0;">School Details</h3>
            <button onclick="closeViewModal()" style="color: #6b7280; background: none; border: none; cursor: pointer; font-size: 1.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">School Name</label>
                    <div id="view_name" style="color: #fff; font-weight: 600;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">Centre Number</label>
                    <div id="view_code" style="color: #00A3DD; font-family: monospace; font-size: 1rem;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">School Type</label>
                    <div id="view_type" style="color: #fff;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">Ownership</label>
                    <div id="view_ownership" style="color: #fff;"></div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">Region</label>
                    <div style="color: #fff;">{{ $district?->region?->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.25rem;">District</label>
                    <div style="color: #fff;">{{ $district?->name ?? 'N/A' }}</div>
                </div>
            </div>
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #1f2937; text-align: right;">
                <button onclick="closeViewModal()" style="background: #1f2937; border: 1px solid #374151; color: #fff; font-size: 0.875rem; font-weight: 600; border-radius: 0.5rem; padding: 0.625rem 1.5rem; cursor: pointer;">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ════════ EDIT SCHOOL MODAL ════════ --}}
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-backdrop" onclick="closeEditModal()"></div>
    <div class="modal-container">
        <div style="padding: 1.5rem; border-bottom: 1px solid #1f2937; background: rgba(17,24,39,0.5); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #f0e6c8; margin: 0;">Edit School</h3>
            <button onclick="closeEditModal()" style="color: #6b7280; background: none; border: none; cursor: pointer; font-size: 1.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <form id="editForm" method="POST" style="padding: 1.5rem;">
            @csrf
            @method('PUT')
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div style="grid-column: span 2;">
                    <label for="edit_name_input" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.5rem;">School Name</label>
                    <input type="text" name="name" id="edit_name_input" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                </div>
                <div>
                    <label for="edit_code_input" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.5rem;">Centre Number</label>
                    <input type="text" name="code" id="edit_code_input" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                </div>
                <div>
                    <label for="edit_type_input" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.5rem;">School Type</label>
                    <select name="school_type" id="edit_type_input" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                        <option value="PRIMARY">PRIMARY</option>
                        <option value="SECONDARY">SECONDARY</option>
                        <option value="BOTH">BOTH</option>
                    </select>
                </div>
                <div style="grid-column: span 2;">
                    <label for="edit_ownership_input" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.5rem;">Ownership</label>
                    <select name="ownership" id="edit_ownership_input" required style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none;">
                        <option value="GOVERNMENT">GOVERNMENT</option>
                        <option value="NON-GOVERNMENT">NON-GOVERNMENT</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #1f2937;">
                <button type="button" onclick="closeEditModal()" style="background: none; border: none; color: #9ca3af; font-size: 0.875rem; font-weight: 600; cursor: pointer; padding: 0.625rem 1rem;">Cancel</button>
                <button type="submit" style="background: #00A3DD; border: none; color: #fff; font-size: 0.875rem; font-weight: 700; border-radius: 0.5rem; padding: 0.625rem 1.5rem; cursor: pointer; box-shadow: 0 4px 12px rgba(0, 163, 221, 0.3);">
                    Update School
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ════════ REJECT ERROR MODAL ════════ --}}
<div id="rejectModal" class="modal-overlay" style="display: none;">
    <div class="modal-backdrop" onclick="closeRejectModal()"></div>
    <div class="modal-container" style="max-width: 450px;">
        <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,77,77,0.2); background: rgba(255,77,77,0.05); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #ff4d4d; margin: 0;"><i class="fas fa-triangle-exclamation"></i> Reject Data Submission</h3>
            <button onclick="closeRejectModal()" style="color: #6b7280; background: none; border: none; cursor: pointer; font-size: 1.25rem;"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('mock-portal.dao.reject') }}" method="POST" style="padding: 1.5rem;">
            @csrf
            <input type="hidden" name="id" id="reject_id">
            <input type="hidden" name="category" id="reject_category">
            
            <div style="margin-bottom: 1.25rem; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid #ff4d4d;">
                <div style="font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Issue Identified</div>
                <div id="reject_description" style="color: #fff; font-size: 0.9rem; font-weight: 500;"></div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="reject_reason" style="display: block; font-size: 0.75rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; margin-bottom: 0.5rem;">Reason for Rejection</label>
                <textarea name="reason" id="reject_reason" required rows="3" 
                    style="width: 100%; background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.625rem 1rem; color: #fff; outline: none; font-size: 0.9rem; resize: none;"
                    placeholder="e.g. Please provide valid PREM number for this student."></textarea>
                <p style="font-size: 0.75rem; color: #6b7280; mt-2;">This note will be sent back to the headteacher.</p>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
                <button type="button" onclick="closeRejectModal()" style="background: none; border: none; color: #9ca3af; font-size: 0.875rem; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="background: #ff4d4d; border: none; color: #fff; font-size: 0.875rem; font-weight: 700; border-radius: 0.5rem; padding: 0.625rem 1.5rem; cursor: pointer; box-shadow: 0 4px 12px rgba(255, 77, 77, 0.3);">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>
<div id="deleteModal" class="modal-overlay" style="display: none;">
    <div class="modal-backdrop" onclick="closeDeleteModal()"></div>
    <div class="modal-container" style="max-width: 400px; margin-top: 10vh;">
        <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,77,77,0.2); background: rgba(255,77,77,0.05); text-align: center;">
            <div style="width: 60px; height: 60px; background: rgba(255,77,77,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.5rem; color: #ff4d4d;"></i>
            </div>
            <h3 style="font-size: 1.125rem; font-weight: 700; color: #fff; margin: 0;">Confirm Deletion</h3>
        </div>
        
        <div style="padding: 1.5rem; text-align: center; color: #d1d5db;">
            <p style="margin: 0 0 0.5rem; font-size: 0.95rem;">Are you sure you want to delete:</p>
            <p id="deleteSchoolName" style="font-weight: 700; color: #ff4d4d; font-size: 1rem; margin-bottom: 1.5rem;"></p>
            <p style="font-size: 0.8rem; color: #9ca3af;">This action cannot be undone and will remove all associated data.</p>
        </div>

        <div style="padding: 1.25rem; background: rgba(17,24,39,0.5); border-top: 1px solid #1f2937; display: flex; gap: 10px;">
            <button onclick="closeDeleteModal()" style="flex: 1; padding: 0.75rem; background: #374151; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Cancel</button>
            <form id="deleteForm" method="POST" style="flex: 1;">
                @csrf
                @method('DELETE')
                <button type="submit" style="width: 100%; padding: 0.75rem; background: #ff4d4d; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Delete Now</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('schoolModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('schoolModal').style.display = 'none';
}

function openViewModal(school) {
    document.getElementById('view_name').textContent = school.name;
    document.getElementById('view_code').textContent = school.code;
    document.getElementById('view_type').textContent = school.school_type;
    document.getElementById('view_ownership').textContent = school.ownership;
    document.getElementById('viewModal').style.display = 'flex';
}
function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function openEditModal(school) {
    const form = document.getElementById('editForm');
    form.action = `/mock-portal/dao/schools/${school.id}`;
    document.getElementById('edit_name_input').value = school.name;
    document.getElementById('edit_code_input').value = school.code;
    document.getElementById('edit_type_input').value = school.school_type;
    document.getElementById('edit_ownership_input').value = school.ownership;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openDeleteModal(school) {
    const form = document.getElementById('deleteForm');
    form.action = `/mock-portal/dao/schools/${school.id}`;
    document.getElementById('deleteSchoolName').innerText = `${school.name} (${school.code})`;
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

function openRejectModal(id, category, description) {
    document.getElementById('reject_id').value = id;
    document.getElementById('reject_category').value = category;
    document.getElementById('reject_description').textContent = description;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal-overlay') {
        closeModal();
        closeViewModal();
        closeEditModal();
        closeDeleteModal();
        closeRejectModal();
    }
}

function tick(){
    const n=new Date();
    const clk = document.getElementById('um-clock');
    const dt = document.getElementById('um-date');
    if(clk) clk.textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
    if(dt) dt.textContent=n.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
tick(); setInterval(tick,1000);
</script>
@include('mock-portal.partials.user-manual', $mockPortalManual)
</body>
</html>
