@extends('layout')
@section('content')
<style>
:root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-dark:#0b1014;--tz-card:#101518;--tz-text:#f0f4f7;--tz-muted:rgba(255,255,255,.45);}
.adm-shell{min-height:100vh;background:var(--tz-dark);font-family:'Maiandra GD',sans-serif;}

/* TOP NAVBAR */
.adm-navbar{position:sticky;top:0;z-index:100;background:#050a0d;box-shadow:0 4px 24px rgba(0,0,0,.5);}
.adm-navbar-flag{height:4px;background:linear-gradient(90deg,var(--tz-green) 0%,var(--tz-green) 25%,var(--tz-yellow) 25%,var(--tz-yellow) 50%,#111 50%,#111 75%,var(--tz-blue) 75%);}
.adm-navbar-inner{max-width:1400px;margin:0 auto;display:flex;align-items:center;height:62px;padding:0 24px;gap:0;}
.adm-brand{display:flex;align-items:center;gap:10px;padding-right:20px;border-right:1px solid rgba(255,255,255,.08);margin-right:16px;flex-shrink:0;text-decoration:none;}
.adm-brand-icon{width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,var(--tz-green),#0f7a1e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;}
.adm-brand-text{font-size:.95rem;font-weight:800;color:var(--tz-text);line-height:1.1;}
.adm-brand-sub{font-size:.6rem;color:var(--tz-yellow);font-weight:700;letter-spacing:.07em;text-transform:uppercase;}

/* Nav links */
.adm-navlinks{display:flex;align-items:center;gap:1px;flex:1;overflow-x:auto;}
.adm-navlinks::-webkit-scrollbar{height:0;}
.adm-navlinks a{display:flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;white-space:nowrap;color:rgba(255,255,255,.5);font-size:.8rem;font-weight:600;text-decoration:none;transition:all .16s;}
.adm-navlinks a:hover{background:rgba(30,181,58,.1);color:#fff;}
.adm-navlinks a i{font-size:.75rem;}
.nav-sep{width:1px;height:20px;background:rgba(255,255,255,.07);margin:0 4px;flex-shrink:0;}

/* Right side */
.adm-nav-right{display:flex;align-items:center;gap:8px;margin-left:auto;padding-left:16px;border-left:1px solid rgba(255,255,255,.08);flex-shrink:0;}
.adm-clock{font-size:.77rem;font-weight:700;color:var(--tz-yellow);background:rgba(252,209,22,.08);border:1px solid rgba(252,209,22,.18);padding:5px 12px;border-radius:18px;}
.adm-user{display:flex;align-items:center;gap:8px;background:rgba(30,181,58,.07);border:1px solid rgba(30,181,58,.16);padding:4px 12px 4px 5px;border-radius:22px;}
.adm-user-av{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--tz-green),#0f7a1e);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.75rem;font-weight:800;}
.adm-user-name{font-size:.78rem;font-weight:700;color:var(--tz-text);}
.adm-user-role{font-size:.62rem;color:var(--tz-yellow);}
.adm-logout-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.16);color:#fca5a5;font-size:.77rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .16s;}
.adm-logout-btn:hover{background:rgba(239,68,68,.2);color:#fff;}

/* Page header */
.adm-page-head{max-width:1400px;margin:0 auto;padding:22px 24px 0;display:flex;align-items:center;justify-content:space-between;}
.adm-page-title{font-size:1.4rem;font-weight:800;color:var(--tz-text);}
.adm-page-sub{font-size:.8rem;color:var(--tz-muted);margin-top:2px;}
.adm-date-pill{font-size:.78rem;color:var(--tz-muted);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);padding:6px 14px;border-radius:20px;}

/* Content wrapper */
.adm-content{max-width:1400px;margin:0 auto;padding:20px 24px 40px;}

/* Stats */
.adm-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px;}
.adm-stat{border-radius:14px;padding:20px;position:relative;overflow:hidden;border:1px solid rgba(255,255,255,.06);}
.adm-stat::after{content:'';position:absolute;right:-16px;bottom:-16px;width:66px;height:66px;border-radius:50%;background:rgba(255,255,255,.04);}
.adm-stat-label{font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;opacity:.7;}
.adm-stat-value{font-size:2.2rem;font-weight:800;line-height:1.1;margin:5px 0 8px;letter-spacing:-2px;}
.adm-stat-desc{font-size:.75rem;opacity:.55;}
.adm-stat-icon{position:absolute;top:16px;right:16px;font-size:1.3rem;opacity:.18;}
.stat-blue  {background:linear-gradient(135deg,#003d52,#004f6b);color:#67d8ff;}
.stat-green {background:linear-gradient(135deg,#0a3012,#0e3d17);color:#6ae086;}
.stat-yellow{background:linear-gradient(135deg,#3a2e00,#453600);color:#FCD116;}
.stat-black {background:linear-gradient(135deg,#111416,#161b1f);color:#c0ccd6;border-color:rgba(0,163,221,.15)!important;}

/* Module cards */
.adm-modules{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;}
.adm-card{background:var(--tz-card);border:1px solid rgba(255,255,255,.06);border-radius:14px;overflow:hidden;transition:transform .2s,box-shadow .2s;}
.adm-card:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(0,0,0,.5);}
.adm-card-head{padding:13px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,.04);}
.adm-card-title{font-size:.85rem;font-weight:700;color:var(--tz-text);display:flex;align-items:center;gap:7px;}
.adm-card-badge{font-size:.58rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 8px;border-radius:18px;}
.badge-blue  {background:rgba(0,163,221,.15);color:#67d8ff;border:1px solid rgba(0,163,221,.25);}
.badge-green {background:rgba(30,181,58,.12);color:#6ae086;border:1px solid rgba(30,181,58,.22);}
.badge-yellow{background:rgba(252,209,22,.1);color:#FCD116;border:1px solid rgba(252,209,22,.2);}
.badge-indigo{background:rgba(0,163,221,.1);color:#67d8ff;border:1px solid rgba(0,163,221,.18);}
.badge-orange{background:rgba(252,209,22,.1);color:#FCD116;border:1px solid rgba(252,209,22,.2);}
.badge-purple{background:rgba(30,181,58,.1);color:#6ae086;border:1px solid rgba(30,181,58,.2);}
.adm-card-body{padding:5px;}
.adm-link{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;text-decoration:none;color:var(--tz-muted);font-size:.83rem;font-weight:500;transition:all .14s;}
.adm-link:hover{background:rgba(30,181,58,.08);color:#fff;}
.adm-link-icon{width:28px;height:28px;border-radius:7px;background:rgba(255,255,255,.04);display:flex;align-items:center;justify-content:center;font-size:.75rem;flex-shrink:0;}
.adm-link:hover .adm-link-icon{background:rgba(30,181,58,.16);color:var(--tz-green);}

/* Health */
.adm-health{background:linear-gradient(135deg,#050a0d,#080e12);border:1px solid rgba(0,163,221,.16);border-radius:12px;padding:16px;margin-top:14px;}
.adm-health-title{font-size:.87rem;font-weight:700;color:var(--tz-yellow);margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.adm-health-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.78rem;}
.adm-health-key{color:var(--tz-muted);}
.adm-health-val{font-weight:700;}
.val-blue{color:var(--tz-blue);}
.val-green{color:var(--tz-green);}
.val-orange{color:var(--tz-yellow);}
.adm-filament-btn{display:block;width:100%;margin-top:12px;padding:9px;background:linear-gradient(135deg,var(--tz-blue),#006fa3);color:#fff;border-radius:9px;font-size:.8rem;font-weight:700;text-align:center;text-decoration:none;transition:opacity .15s;}
.adm-filament-btn:hover{opacity:.85;}

@media(max-width:1100px){.adm-stats{grid-template-columns:repeat(2,1fr);}.adm-modules{grid-template-columns:repeat(2,1fr);}}
@media(max-width:768px){.adm-stats{grid-template-columns:1fr 1fr;}.adm-modules{grid-template-columns:1fr;}.adm-navlinks a span{display:none;}}
</style>

<div class="adm-shell">

{{-- ════════ TOP NAVBAR ════════ --}}
<nav class="adm-navbar">
    <div class="adm-navbar-flag"></div>
    <div class="adm-navbar-inner">

        {{-- Brand --}}
        <a href="/admin/dashboard" class="adm-brand">
            <div class="adm-brand-icon"><i class="fas fa-shield-halved"></i></div>
            <div>
                <div class="adm-brand-text">IRMS</div>
                <div class="adm-brand-sub">Admin Panel</div>
            </div>
        </a>

        {{-- Nav Links --}}
        <div class="adm-navlinks">
            <a href="/admin/registration/regions"><i class="fas fa-map"></i><span>Regions</span></a>
            <a href="/admin/registration/districts"><i class="fas fa-map-location-dot"></i><span>Districts</span></a>
            <a href="/admin/registration/schools"><i class="fas fa-school-flag"></i><span>Schools</span></a>
            <a href="/admin/registration/candidates"><i class="fas fa-user-graduate"></i><span>Candidates</span></a>
            <div class="nav-sep"></div>
            <a href="/admin/exam-types"><i class="fas fa-tags"></i><span>Exam Types</span></a>
            <a href="/admin/exam-years"><i class="fas fa-calendar-check"></i><span>Academic Years</span></a>
            <a href="/mark-entry"><i class="fas fa-file-pen"></i><span>Mark Entry</span></a>
            <div class="nav-sep"></div>
            <a href="/admin/final-grades"><i class="fas fa-award"></i><span>Grades</span></a>
            <a href="/admin/candidate-results"><i class="fas fa-square-poll-vertical"></i><span>Results</span></a>
            <div class="nav-sep"></div>
            <a href="/admin/manage-users"><i class="fas fa-user-gear"></i><span>Users</span></a>
            <a href="/admin/audit-logs"><i class="fas fa-shield-halved"></i><span>Audit</span></a>
            <a href="/admin/manage-backups"><i class="fas fa-server"></i><span>Backups</span></a>
        </div>

        {{-- Right --}}
        <div class="adm-nav-right">
            <div class="adm-clock" id="adm-clock">--:--</div>
            <div class="adm-user">
                <div class="adm-user-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                <div>
                    <div class="adm-user-name">{{ auth()->user()->name }}</div>
                    <div class="adm-user-role">Administrator</div>
                </div>
            </div>
            <form method="POST" action="/logout" style="margin:0">
                @csrf
                <button type="submit" class="adm-logout-btn"><i class="fas fa-right-from-bracket"></i> Logout</button>
            </form>
        </div>

    </div>
</nav>

{{-- ════════ PAGE HEADER ════════ --}}
<div class="adm-page-head">
    <div>
        <div class="adm-page-title">Admin Control Centre</div>
        <div class="adm-page-sub">Central management hub for all system modules</div>
    </div>
    <div class="adm-date-pill" id="adm-date">—</div>
</div>

{{-- ════════ CONTENT ════════ --}}
<div class="adm-content">

    {{-- Stats --}}
    <div class="adm-stats">
        <div class="adm-stat stat-blue">
            <i class="fas fa-users adm-stat-icon"></i>
            <div class="adm-stat-label">Total Users</div>
            <div class="adm-stat-value">{{ number_format($stats['users_count']) }}</div>
            <div class="adm-stat-desc">Registered system accounts</div>
        </div>
        <div class="adm-stat stat-green">
            <i class="fas fa-user-graduate adm-stat-icon"></i>
            <div class="adm-stat-label">Candidates</div>
            <div class="adm-stat-value">{{ number_format($stats['candidates_count']) }}</div>
            <div class="adm-stat-desc">Registered exam candidates</div>
        </div>
        <div class="adm-stat stat-yellow">
            <i class="fas fa-school adm-stat-icon"></i>
            <div class="adm-stat-label">Schools</div>
            <div class="adm-stat-value">{{ number_format($stats['schools_count']) }}</div>
            <div class="adm-stat-desc">Active exam centres</div>
        </div>
        <div class="adm-stat stat-black">
            <i class="fas fa-file-signature adm-stat-icon"></i>
            <div class="adm-stat-label">Mark Entries</div>
            <div class="adm-stat-value">{{ number_format($stats['total_marks_count']) }}</div>
            <div class="adm-stat-desc">Total marks recorded</div>
        </div>
    </div>

    {{-- Module Cards --}}
    <div class="adm-modules">

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-id-card-clip" style="color:#67d8ff"></i> Registration &amp; Schools</div>
                <span class="adm-card-badge badge-blue">Core</span>
            </div>
            <div class="adm-card-body">
                <a href="/admin/registration/regions" class="adm-link"><span class="adm-link-icon"><i class="fas fa-map"></i></span> Regional Management</a>
                <a href="/admin/registration/districts" class="adm-link"><span class="adm-link-icon"><i class="fas fa-map-location-dot"></i></span> District Councils</a>
                <a href="/admin/registration/schools" class="adm-link"><span class="adm-link-icon"><i class="fas fa-school-flag"></i></span> School Directory</a>
                <a href="/admin/registration/candidates" class="adm-link"><span class="adm-link-icon"><i class="fas fa-user-plus"></i></span> Candidate Management</a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-file-pen" style="color:#6ae086"></i> Exam &amp; Mark Flow</div>
                <span class="adm-card-badge badge-green">Academics</span>
            </div>
            <div class="adm-card-body">
                <a href="/admin/exam-types" class="adm-link"><span class="adm-link-icon"><i class="fas fa-tags"></i></span> Exam Type Configuration</a>
                <a href="/admin/exam-years" class="adm-link"><span class="adm-link-icon"><i class="fas fa-calendar-check"></i></span> Academic Year Setup</a>
                <a href="/admin/raw-marks" class="adm-link"><span class="adm-link-icon"><i class="fas fa-input-numeric"></i></span> Raw Mark Repository</a>
                <a href="/admin/grading-profiles" class="adm-link"><span class="adm-link-icon"><i class="fas fa-graduation-cap"></i></span> Grading System</a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-chart-line" style="color:#67d8ff"></i> Final Output</div>
                <span class="adm-card-badge badge-indigo">Reports</span>
            </div>
            <div class="adm-card-body">
                <a href="/admin/final-grades" class="adm-link"><span class="adm-link-icon"><i class="fas fa-award"></i></span> Final Grade Processing</a>
                <a href="/admin/candidate-results" class="adm-link"><span class="adm-link-icon"><i class="fas fa-square-poll-vertical"></i></span> Result Portal Management</a>
            </div>
            <div class="adm-health">
                <div class="adm-health-title"><i class="fas fa-rocket"></i> System Health</div>
                <div class="adm-health-row"><span class="adm-health-key">Database</span><span class="adm-health-val val-blue">SQLite</span></div>
                <div class="adm-health-row"><span class="adm-health-key">Environment</span><span class="adm-health-val val-orange">Local</span></div>
                <div class="adm-health-row" style="border:none"><span class="adm-health-key">Status</span><span class="adm-health-val val-green">● Online</span></div>
                <a href="/admin" class="adm-filament-btn">Open Filament Panel</a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-database" style="color:#6ae086"></i> Maintenance</div>
                <span class="adm-card-badge badge-green">Critical</span>
            </div>
            <div class="adm-card-body">
                <a href="/admin/manage-backups" class="adm-link"><span class="adm-link-icon"><i class="fas fa-server"></i></span> System Backups</a>
                <a href="/admin/manage-users" class="adm-link"><span class="adm-link-icon"><i class="fas fa-user-gear"></i></span> User Management</a>
                <a href="/admin/system-settings" class="adm-link"><span class="adm-link-icon"><i class="fas fa-sliders-h"></i></span> System Settings</a>
            </div>
        </div>

        <div class="adm-card">
            <div class="adm-card-head">
                <div class="adm-card-title"><i class="fas fa-gears" style="color:#FCD116"></i> Governance</div>
                <span class="adm-card-badge badge-yellow">Admin</span>
            </div>
            <div class="adm-card-body">
                <a href="/admin/manage-users" class="adm-link"><span class="adm-link-icon"><i class="fas fa-user-gear"></i></span> Users &amp; Access Control</a>
                <a href="/admin/system-settings" class="adm-link"><span class="adm-link-icon"><i class="fas fa-sliders-h"></i></span> Global System Config</a>
                <a href="/admin/audit-logs" class="adm-link"><span class="adm-link-icon"><i class="fas fa-shield-halved"></i></span> Security Audit Logs</a>
                <a href="/admin/restore-audit-logs" class="adm-link"><span class="adm-link-icon"><i class="fas fa-clock-rotate-left"></i></span> Restore History</a>
                <a href="/admin/dashboard-announcements" class="adm-link"><span class="adm-link-icon"><i class="fas fa-bullhorn"></i></span> Announcements &amp; Notices</a>
            </div>
        </div>

    </div>
</div>
</div>

<script>
function tick(){
    const n=new Date();
    document.getElementById('adm-clock').textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
    document.getElementById('adm-date').textContent=n.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
tick(); setInterval(tick,1000);
</script>
@endsection
