<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRMS - Admin Control Centre</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <!-- Alpine core -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body>
<style>
/* PSLE Sidebar Submenu Styles */
.psle-submenu {
    padding: 6px 8px !important;
    margin-left: 14px !important;
    margin-bottom: 4px !important;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.psle-sub-link {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    border-radius: 12px !important;
    border: 1px solid transparent !important;
    padding: 6px 12px 6px 10px !important;
    font-size: 0.8rem !important;
    font-weight: 700 !important;
    color: rgba(255, 255, 255, 0.6) !important;
    text-decoration: none !important;
    transition: all 0.2s ease !important;
    width: 100% !important;
}
.psle-sub-link::before {
    display: none !important; /* Overrides standard list bullet dots if inherited */
}
.psle-sub-ico {
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important; /* Circular background */
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0.75rem !important;
    background: rgba(255, 255, 255, 0.05) !important;
    color: rgba(255, 255, 255, 0.5) !important;
    transition: all 0.2s ease !important;
    margin-right: 8px !important;
}
.psle-sub-link:hover {
    background: rgba(255, 255, 255, 0.04) !important;
    color: #ffffff !important;
    border-color: rgba(255, 255, 255, 0.05) !important;
}
.psle-sub-link:hover .psle-sub-ico {
    background: rgba(187, 164, 94, 0.1) !important;
    color: #fde047 !important;
}
.psle-sub-link.active {
    background: rgba(187, 164, 94, 0.12) !important;
    border-color: rgba(187, 164, 94, 0.3) !important;
    color: #fde047 !important;
}
.psle-sub-link.active .psle-sub-ico {
    background: rgba(187, 164, 94, 0.2) !important;
    color: #fcd116 !important;
    box-shadow: 0 0 0 1px rgba(187, 164, 94, 0.2) !important;
}
.psle-sub-dot {
    width: 6px !important;
    height: 6px !important;
    border-radius: 50% !important;
    background: #fcd116 !important;
    box-shadow: 0 0 0 3px rgba(253, 224, 71, 0.2) !important;
}

* { box-sizing: border-box; }
input, select, textarea, button { font-family: inherit; }
body, html { margin: 0; padding: 0; width: 100%; max-width: 100vw; overflow-x: hidden; background: #0f1117; font-family: 'Maiandra GD', sans-serif; }
:root{
    --tz-green:#1EB53A;
    --tz-yellow:#FCD116;
    --tz-blue:#00A3DD;
    --tz-dark:#0b1014;
    --tz-card:#101518;
    --tz-text:#f0f4f7;
    --tz-muted:rgba(255,255,255,.45);
}

/* Layout classes */
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

.um-nav{padding:14px 12px;flex:1;overflow-y:auto;}
.um-nav::-webkit-scrollbar { width: 2px; }
.um-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); }

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
.submenu a { display: flex; align-items: center; padding: 8px 12px 8px 30px; font-size: 0.82rem; color: rgba(255,255,255,0.5); gap: 8px; text-decoration: none; width: 100%; position: relative; }
.submenu a::before { content: ''; position: absolute; left: 16px; width: 4px; height: 4px; border-radius: 50%; background: rgba(255,255,255,0.2); }
.submenu a:hover, .submenu a.active { background: rgba(255,255,255,0.04); color: #f0e6c8; }
.submenu a:hover::before, .submenu a.active::before { background: #BBA45E; }
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
@media(max-width:768px){.adm-stats{grid-template-columns:1fr;}.adm-modules{grid-template-columns:1fr;}}

@media(max-width:1024px) {
    .um-body-row { flex-direction: column; background: #0f1117; }
    .um-sidebar { width: 100%; height: auto; max-height: none; position: static; box-shadow:none; border-right:none; border-bottom: 1px solid rgba(187,164,94,.18); }
    .um-main { margin-left:0; border-left: none; }
}
</style>

<div class="um-shell">
    <div class="um-body-row">
        <!-- Sidebar -->
        <aside class="um-sidebar">
            <a href="/admin/dashboard" class="um-profile">
                <div class="adm-brand-icon"><i class="fas fa-shield-halved"></i></div>
                <div>
                    <div class="adm-brand-text">IRMS</div>
                    <div class="adm-brand-sub">Admin Panel</div>
                </div>
            </a>
            
            <nav class="um-nav">
                <!-- HOME -->
                <a href="/admin/dashboard" class="active">
                    <span class="nav-ico"><i class="fas fa-house"></i></span> 
                    HOME
                </a>

                <!-- REGISTRATION -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-address-card"></i></span>
                        REGISTRATION
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="/admin/registration"><i class="fas fa-table-columns"></i> Dashboard</a></div>
                        <div class="submenu-item"><a href="/admin/registration/regions"><i class="fas fa-map"></i> Regions</a></div>
                        <div class="submenu-item"><a href="/admin/registration/districts"><i class="fas fa-map-location-dot"></i> Districts</a></div>
                        <div class="submenu-item"><a href="/admin/registration/schools"><i class="fas fa-school"></i> Schools</a></div>
                        <div class="submenu-item"><a href="/admin/registration/candidates"><i class="fas fa-user-graduate"></i> Candidates</a></div>
                        <div class="submenu-item"><a href="{{ route('admin.zonal-control-centre') }}"><i class="fas fa-sitemap"></i> Zonal Control Centre</a></div>
                        <hr class="submenu-hr">
                        <div class="submenu-item"><a href="/mock-portal" target="_blank"><i class="fas fa-globe"></i> Mock Portal</a></div>
                    </div>
                </div>

                <!-- EXAM TYPE -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-file-signature"></i></span>
                        EXAM TYPE
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        @php
                            $configuredExamTypes = \App\Models\ExamType::query()->whereIn('code', config('irms.active_exam_types', ['PSLE']))->orderBy('code')->get(['code', 'name']);
                        @endphp
                        @forelse ($configuredExamTypes as $configuredExamType)
                            <div class="submenu-item">
                                <a href="/admin/exam-types/{{ strtolower($configuredExamType->code) }}">
                                    <i class="fas fa-file-circle-check"></i> {{ $configuredExamType->code }}
                                </a>
                            </div>
                        @empty
                            <div class="submenu-item"><a href="#" style="cursor:default;opacity:0.5;"><i class="fas fa-circle-info"></i> No exam types</a></div>
                        @endforelse
                    </div>
                </div>


                <!-- MARK ENTRY -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-keyboard"></i></span>
                        MARK ENTRY
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="/mark-entry/psle"><i class="fas fa-pen-to-square"></i> PSLE</a></div>
                        <hr class="submenu-hr">
                        <div class="submenu-item"><a href="{{ route('mark-entry.psle.questions.show') }}"><i class="fas fa-hashtag"></i> PSLE Questions</a></div>
                    </div>
                </div>

                <!-- RESULTS -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-square-poll-vertical"></i></span>
                        RESULTS
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="/results/psle"><i class="fas fa-square-poll-vertical"></i> PSLE</a></div>
                        <hr class="submenu-hr">
                        <div class="submenu-item"><a href="/results/2026/psle"><i class="fas fa-globe"></i> Public Results (PSLE)</a></div>
                    </div>
                </div>

                <!-- EVALUATIONS -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-chart-pie"></i></span>
                        EVALUATIONS
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="/evaluations/psle"><i class="fas fa-magnifying-glass-chart"></i> PSLE</a></div>
                    </div>
                </div>

                <!-- EXAM DEVELOPMENT -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-diagram-project"></i></span>
                        EXAM DEVELOPMENT
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="{{ route('exam-development.dashboard') }}"><i class="fas fa-diagram-project"></i> Dashboard</a></div>
                        <div class="submenu-item"><a href="{{ route('exam-development.formats.index') }}"><i class="fas fa-layer-group"></i> Format Master</a></div>
                        <div class="submenu-item"><a href="{{ route('exam-development.projects.index') }}"><i class="fas fa-folder-tree"></i> Projects</a></div>
                        <div class="submenu-item"><a href="{{ route('exam-development.questions.index') }}"><i class="fas fa-book-open"></i> Question Bank</a></div>
                    </div>
                </div>

                <!-- GOVERNANCE -->
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button">
                        <span class="nav-ico"><i class="fas fa-user-gear"></i></span>
                        GOVERNANCE
                        <i class="fas fa-chevron-down nav-caret" :style="open ? 'transform: rotate(180deg)' : ''"></i>
                    </button>
                    <div class="submenu" x-show="open" x-collapse x-cloak>
                        <div class="submenu-item"><a href="/admin/manage-users"><i class="fas fa-user-gear"></i> Users & Roles</a></div>
                        <div class="submenu-item"><a href="/admin/audit-logs"><i class="fas fa-shield-halved"></i> Audit Logs</a></div>
                        <div class="submenu-item"><a href="/admin/manage-backups"><i class="fas fa-server"></i> Backups</a></div>
                        <div class="submenu-item"><a href="/admin/system-settings"><i class="fas fa-sliders-h"></i> System Settings</a></div>
                        <div class="submenu-item"><a href="/admin/dashboard-announcements"><i class="fas fa-bullhorn"></i> Announcements</a></div>
                        <div class="submenu-item"><a href="/admin/restore-audit-logs"><i class="fas fa-clock-rotate-left"></i> Restore History</a></div>
                    </div>
                </div>
            </nav>
            <div class="um-footer">
                <form method="POST" action="/logout" style="margin:0;">
                    @csrf
                    <button type="submit" class="um-logout"><i class="fas fa-right-from-bracket"></i> Logout</button>
                </form>
            </div>
        </aside>

        <!-- Main -->
        <div class="um-main">
            <header class="um-topbar">
                <div>
                    <div class="um-topbar-title">Admin Control Centre</div>
                    <div class="um-topbar-sub">Central management hub for all system modules</div>
                </div>
                <div style="display:flex; align-items:center; gap: 16px;">
                    <div class="um-date-pill" id="um-date">—</div>
                    <div class="um-clock" id="um-clock">--:--</div>
                    
                    <div class="adm-user">
                        <div class="adm-user-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                        <div>
                            <div class="adm-user-name">{{ auth()->user()->name }}</div>
                            <div class="adm-user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- ════════ CONTENT ════════ --}}
            <div class="um-content">

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
                            <a href="/admin/exam-types/psle" class="adm-link"><span class="adm-link-icon"><i class="fas fa-graduation-cap"></i></span> PSLE Configuration</a>
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
                            @php
                                $databaseDriver = DB::connection()->getDriverName();
                                $databaseLabel = match ($databaseDriver) {
                                    'mysql' => 'MySQL / MariaDB',
                                    'pgsql' => 'PostgreSQL',
                                    'sqlite' => 'SQLite',
                                    default => strtoupper($databaseDriver),
                                };
                            @endphp
                            <div class="adm-health-title"><i class="fas fa-rocket"></i> System Health</div>
                            <div class="adm-health-row"><span class="adm-health-key">Database</span><span class="adm-health-val val-blue">{{ $databaseLabel }}</span></div>
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
    </div>
</div>

<script>
    function tick(){
        const n=new Date();
        const clk = document.getElementById('um-clock');
        const dt = document.getElementById('um-date');
        if(clk) clk.textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
        if(dt) dt.textContent=n.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    }
    tick(); setInterval(tick,1000);
</script>
</body>
</html>
