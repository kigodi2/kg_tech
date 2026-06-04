<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRMS - PSLE Configuration</title>
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
@include('registration.partials.theme')
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
:root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-dark:#0b1014;--tz-card:#101518;--tz-text:#f0f4f7;--tz-muted:rgba(255,255,255,.45);}

/* Layout CSS from dashboard.blade.php */
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

/* Footer */
.page-footer { background: #0f1117; color: #ffffff; border-top: 1px solid rgba(187,164,94,.15); margin-top: auto; width: 100%; }
.page-footer-stripes { display: flex; width: 100%; height: 3px; }
.page-footer-stripes span { display: block; width: 25%; }
.page-footer-body { width: 100%; padding: 12px 24px; }
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

@media(max-width:1024px) {
    .um-body-row { flex-direction: column; background: #0f1117; }
    .um-sidebar { width: 100%; height: auto; max-height: none; position: static; box-shadow:none; border-right:none; border-bottom: 1px solid rgba(187,164,94,.18); }
    .um-main { margin-left:0; border-left: none; }
}
@media(max-width:600px) {
    .um-topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
}
@media(max-width:900px) {
    .page-footer-row { flex-direction: column; justify-content: center; text-align: center; }
    .page-footer-copy { flex: auto; order: 2; margin-top: 4px; }
    .page-footer-meta { text-align: center; order: 1; }
}
</style>
<div class="um-shell" x-data="psleManager()" x-init="init()">
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
                <a href="/admin/dashboard">
                    <span class="nav-ico"><i class="fas fa-house"></i></span> 
                    HOME
                </a>

                <!-- REGISTRATION -->
                <div x-data="{ open: false }">
                    <button @click="open = !open">
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
                <div x-data="{ open: true }">
                    <button @click="open = !open">
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
                                <a href="/admin/exam-types/{{ strtolower($configuredExamType->code) }}" class="{{ strtolower($configuredExamType->code) === 'psle' ? 'active' : '' }}">
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
                    <button @click="open = !open">
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
                    <button @click="open = !open">
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
                    <button @click="open = !open">
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
                    <button @click="open = !open">
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
                    <div class="um-topbar-title">PSLE Exam Configuration</div>
                    <div class="um-topbar-sub">Manage subjects, paper structure, timetable, schools and registered pupils</div>
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

            <div class="um-content psle-admin-dark-theme">
<style>
    /* Filter Panel & Spacers */
    .exam-filter-panel {
        background: #101518 !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 20px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4) !important;
    }

    .exam-section {
        padding: 24px 28px;
    }

    .exam-field {
        min-width: 220px;
    }

    .exam-field--compact {
        min-width: 200px;
    }

    .exam-label {
        display: block;
        margin-bottom: 8px;
        color: rgba(255, 255, 255, 0.5) !important;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .exam-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .exam-search-input,
    .exam-select,
    .exam-dropdown {
        width: 100%;
        height: 46px;
        padding: 0 14px;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 8px !important;
        background: rgba(255, 255, 255, 0.04) !important;
        color: #ffffff !important;
        font-size: 14px;
        box-shadow: none !important;
        outline: none !important;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .exam-dropdown {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        text-align: left;
        transition: border-color 0.2s ease, background-color 0.2s ease;
    }

    .exam-combobox-wrap {
        position: relative;
    }

    .exam-combobox-input {
        padding-right: 38px;
    }

    .exam-combobox-icon {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.45) !important;
        font-size: 13px;
        pointer-events: none;
    }

    .exam-search-input:focus,
    .exam-select:focus,
    .exam-dropdown:focus {
        border-color: #00a3dd !important;
        box-shadow: 0 0 0 3px rgba(0,163,221,0.15) !important;
    }

    .exam-dropdown-menu {
        position: absolute;
        top: calc(100% - 1px);
        left: 0;
        right: 0;
        background: #161b22 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        z-index: 30;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 18px 36px rgba(0, 0, 0, 0.4) !important;
        overflow: hidden;
    }

    .exam-dropdown-option {
        padding: 11px 14px;
        cursor: pointer;
        font-size: 14px;
        line-height: 1.4;
        color: #f0f4f7 !important;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .exam-dropdown-option:hover,
    .exam-dropdown-option.is-active {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
    }

    .exam-dropdown-menu .filter-search-input {
        background: rgba(255, 255, 255, 0.02) !important;
        color: #ffffff !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    .exam-dropdown-menu .filter-search-input::placeholder {
        color: rgba(255, 255, 255, 0.45);
    }

    .exam-actions-row {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        margin-left: auto;
        flex-wrap: wrap;
    }

    .exam-button,
    .exam-button-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 46px;
        padding: 0 18px;
        border-radius: 8px !important;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
    }

    .exam-button:hover,
    .exam-button-secondary:hover {
        transform: translateY(-1px);
    }

    .exam-button {
        background: linear-gradient(135deg, #00a3dd, #006fa3) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0, 163, 221, 0.25) !important;
    }

    .exam-button:hover {
        background: linear-gradient(135deg, #00b4f0, #008cc2) !important;
        box-shadow: 0 6px 16px rgba(0, 163, 221, 0.35) !important;
    }

    .exam-button-secondary {
        background: rgba(255, 255, 255, 0.04) !important;
        color: #f0f4f7 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    .exam-button-secondary:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
    }

    .exam-section-text {
        margin-top: 14px;
        color: rgba(255, 255, 255, 0.4) !important;
        font-size: 13px;
        line-height: 1.6;
    }

    @media (max-width: 720px) {
        .exam-section {
            padding: 18px 20px;
        }

        .exam-field,
        .exam-field--compact {
            min-width: 100%;
        }

        .exam-actions-row {
            margin-left: 0;
            width: 100%;
        }

        .exam-button,
        .exam-button-secondary {
            width: 100%;
        }
    }

    /* PSLE Configuration Page Dark Theme Overrides */
    .psle-admin-dark-theme {
        --tz-green: #1eb53a;
        --tz-yellow: #fcd116;
        --tz-blue: #00a3dd;
        --tz-black: #000000;
        --tz-bg: #0f1117;
        --tz-card: #101518;
        --tz-card-soft: #161b22;
        --tz-border: rgba(255,255,255,0.06);
        --tz-text: #f0f4f7;
        --tz-text-muted: rgba(255,255,255,0.55);
        --tz-gold: #bba45e;
        --tz-gold-soft: rgba(187,164,94,.16);

        background: var(--tz-bg) !important;
        color: var(--tz-text) !important;
        font-family: 'Maiandra GD', 'Segoe UI', sans-serif !important;
        min-height: calc(100vh - 140px);
    }

    /* Override Global Top level page stack */
    .psle-admin-dark-theme .registration-page-stack {
        gap: 24px !important;
    }

    /* Surface Card Panel */
    .psle-admin-dark-theme .registration-surface {
        background: var(--tz-card) !important;
        border: 1px solid var(--tz-border) !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.4) !important;
        color: var(--tz-text) !important;
        border-radius: 16px !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .psle-admin-dark-theme .registration-surface:hover {
        border-color: rgba(187, 164, 94, 0.15) !important;
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5) !important;
    }

    /* Headings & Text Color Overrides */
    .psle-admin-dark-theme h1,
    .psle-admin-dark-theme h2,
    .psle-admin-dark-theme h3,
    .psle-admin-dark-theme h4,
    .psle-admin-dark-theme h5,
    .psle-admin-dark-theme h6,
    .psle-admin-dark-theme strong {
        color: #ffffff !important;
    }
    
    .psle-admin-dark-theme .text-slate-900,
    .psle-admin-dark-theme .text-gray-900,
    .psle-admin-dark-theme .text-slate-800,
    .psle-admin-dark-theme .text-gray-800 {
        color: #ffffff !important;
    }

    .psle-admin-dark-theme p,
    .psle-admin-dark-theme span,
    .psle-admin-dark-theme label {
        color: var(--tz-text-muted) !important;
    }

    .psle-admin-dark-theme .text-slate-600,
    .psle-admin-dark-theme .text-gray-600,
    .psle-admin-dark-theme .text-slate-500,
    .psle-admin-dark-theme .text-gray-500 {
        color: var(--tz-text-muted) !important;
    }

    /* Breadcrumbs styling */
    .psle-admin-dark-theme .adm-breadcrumb {
        font-size: 0.72rem !important;
        font-weight: 700 !important;
        color: rgba(255, 255, 255, 0.45) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.12em !important;
        margin-bottom: 14px !important;
        display: flex;
        align-items: center;
    }
    .psle-admin-dark-theme .adm-breadcrumb span {
        color: var(--tz-yellow) !important;
    }

    /* Page Header */
    .psle-admin-dark-theme .adm-page-header {
        margin-bottom: 28px !important;
        border-left: 4px solid var(--tz-gold) !important;
        padding-left: 16px !important;
    }
    .psle-admin-dark-theme .adm-page-title {
        font-size: 1.85rem !important;
        font-weight: 800 !important;
        color: #f0e6c8 !important;
        margin-bottom: 6px !important;
        letter-spacing: -0.5px !important;
    }
    .psle-admin-dark-theme .adm-page-desc {
        font-size: 0.92rem !important;
        color: var(--tz-text-muted) !important;
        max-width: 900px !important;
        line-height: 1.6 !important;
    }

    /* Inputs, Textareas, Selects & Buttons */
    .psle-admin-dark-theme input[type="text"],
    .psle-admin-dark-theme input[type="search"],
    .psle-admin-dark-theme select,
    .psle-admin-dark-theme textarea,
    .psle-admin-dark-theme .exam-search-input,
    .psle-admin-dark-theme .exam-select {
        background: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        border-radius: 8px !important;
        outline: none !important;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .psle-admin-dark-theme input[type="text"]:focus,
    .psle-admin-dark-theme input[type="search"]:focus,
    .psle-admin-dark-theme select:focus,
    .psle-admin-dark-theme textarea:focus,
    .psle-admin-dark-theme .exam-search-input:focus,
    .psle-admin-dark-theme .exam-select:focus {
        border-color: var(--tz-blue) !important;
        box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
    }
    .psle-admin-dark-theme select option {
        background: var(--tz-card) !important;
        color: #ffffff !important;
    }

    /* Dropdowns / Custom Select Lists */
    .psle-admin-dark-theme .exam-dropdown {
        background: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
        border-radius: 8px !important;
    }
    .psle-admin-dark-theme .exam-dropdown-menu {
        background: #161b22 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    }
    .psle-admin-dark-theme .exam-dropdown-option {
        color: var(--tz-text) !important;
    }
    .psle-admin-dark-theme .exam-dropdown-option:hover,
    .psle-admin-dark-theme .exam-dropdown-option.is-active {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
    }
    .psle-admin-dark-theme .exam-dropdown-menu .filter-search-input {
        background: rgba(255, 255, 255, 0.02) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
    }

    /* Stats Cards from results/psle index.blade.php */
    .psle-admin-dark-theme .adm-stats {
        margin-bottom: 30px !important;
        width: 100% !important;
    }
    .psle-admin-dark-theme .adm-stat {
        border-radius: 14px !important;
        padding: 24px !important;
        position: relative !important;
        overflow: hidden !important;
        border: 1px solid rgba(255, 255, 255, .06) !important;
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease !important;
    }
    .psle-admin-dark-theme .adm-stat::after {
        content: '' !important;
        position: absolute !important;
        right: -16px !important;
        bottom: -16px !important;
        width: 66px !important;
        height: 66px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, .04) !important;
        z-index: 1 !important;
    }
    .psle-admin-dark-theme .adm-stat:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .5) !important;
    }
    .psle-admin-dark-theme .adm-stat-label {
        font-size: 0.68rem !important;
        text-transform: uppercase !important;
        font-weight: 700 !important;
        letter-spacing: .08em !important;
        margin-bottom: 6px !important;
        opacity: 0.7 !important;
        position: relative !important;
        z-index: 3 !important;
    }
    .psle-admin-dark-theme .adm-stat-value {
        font-size: 2.2rem !important;
        font-weight: 800 !important;
        margin-bottom: 6px !important;
        letter-spacing: -2px !important;
        line-height: 1.1 !important;
        position: relative !important;
        z-index: 3 !important;
    }
    .psle-admin-dark-theme .adm-stat-icon {
        position: absolute !important;
        top: 18px !important;
        right: 18px !important;
        font-size: 1.35rem !important;
        opacity: 0.18 !important;
        z-index: 2 !important;
    }
    .psle-admin-dark-theme .adm-stat p,
    .psle-admin-dark-theme .adm-stat-desc {
        opacity: 0.55 !important;
        font-size: 0.75rem !important;
        line-height: 1.4 !important;
        margin-top: 8px !important;
        margin-bottom: 0 !important;
        color: inherit !important;
        position: relative !important;
        z-index: 3 !important;
    }

    /* Gradients for stats cards matching results/psle index.blade.php */
    .psle-admin-dark-theme .adm-stat.stat-blue {
        background: linear-gradient(135deg, #003d52, #004f6b) !important;
        border-color: rgba(0, 163, 221, .2) !important;
        color: #67d8ff !important;
    }
    .psle-admin-dark-theme .adm-stat.stat-green {
        background: linear-gradient(135deg, #0a3012, #0e3d17) !important;
        border-color: rgba(30, 181, 58, .2) !important;
        color: #6ae086 !important;
    }
    .psle-admin-dark-theme .adm-stat.stat-yellow {
        background: linear-gradient(135deg, #3a2e00, #453600) !important;
        border-color: rgba(252, 209, 22, .2) !important;
        color: #FCD116 !important;
    }
    .psle-admin-dark-theme .adm-stat.stat-black {
        background: linear-gradient(135deg, #111416, #161b1f) !important;
        border-color: rgba(255, 255, 255, .08) !important;
        color: #c0ccd6 !important;
    }
    .psle-admin-dark-theme .adm-stat.stat-blue .adm-stat-icon { color: #67d8ff !important; }
    .psle-admin-dark-theme .adm-stat.stat-green .adm-stat-icon { color: #6ae086 !important; }
    .psle-admin-dark-theme .adm-stat.stat-yellow .adm-stat-icon { color: #FCD116 !important; }
    .psle-admin-dark-theme .adm-stat.stat-black .adm-stat-icon { color: var(--tz-gold) !important; }

    /* Navigation Tabs Container styling */
    .psle-admin-dark-theme .registration-surface.p-4 {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    .psle-admin-dark-theme .registration-surface.p-4 > div {
        background: var(--tz-card-soft) !important;
        border: 1px solid var(--tz-border) !important;
        border-radius: 14px !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2) !important;
        padding: 12px !important;
    }
    .psle-admin-dark-theme .registration-surface.p-4 > div > div {
        background: rgba(0,0,0,0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.04) !important;
        border-radius: 12px !important;
        padding: 8px !important;
    }

    /* Toolbar cards and filters background */
    .psle-admin-dark-theme .registration-toolbar-card {
        background: var(--tz-card-soft) !important;
        border: 1px solid var(--tz-border) !important;
        border-radius: 14px !important;
        padding: 18px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
    }
    .psle-admin-dark-theme .registration-toolbar-card input[type="text"] {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .psle-admin-dark-theme .registration-toolbar-grid {
        background: transparent !important;
    }

    /* Tables Override matching .adm-activity-table */
    .psle-admin-dark-theme .registration-table-card {
        background: var(--tz-card) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
        overflow: hidden !important;
    }
    .psle-admin-dark-theme table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .psle-admin-dark-theme table thead,
    .psle-admin-dark-theme .registration-table-card thead {
        background: #161b22 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .psle-admin-dark-theme table th,
    .psle-admin-dark-theme .registration-table-card thead th {
        padding: 12px 14px !important;
        background: rgba(255, 255, 255, 0.03) !important;
        text-align: left !important;
        font-size: .68rem !important;
        letter-spacing: .09em !important;
        text-transform: uppercase !important;
        color: rgba(255, 255, 255, 0.45) !important;
        border: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    }
    .psle-admin-dark-theme table tbody tr,
    .psle-admin-dark-theme .registration-table-card tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        background: transparent !important;
        transition: background-color 0.15s !important;
    }
    .psle-admin-dark-theme table tbody tr:hover,
    .psle-admin-dark-theme .registration-table-card tbody tr:hover {
        background: rgba(255, 255, 255, 0.02) !important;
    }
    .psle-admin-dark-theme table tbody tr.bg-blue-100:hover,
    .psle-admin-dark-theme .registration-table-card tbody tr.bg-blue-100:hover {
        background: rgba(0, 163, 221, 0.15) !important;
    }
    .psle-admin-dark-theme table tbody td,
    .psle-admin-dark-theme .registration-table-card tbody td {
        padding: 13px 14px !important;
        font-size: .82rem !important;
        color: rgba(255, 255, 255, 0.78) !important;
        border: none !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
        vertical-align: middle !important;
    }
    .psle-admin-dark-theme table tbody tr:last-child td,
    .psle-admin-dark-theme .registration-table-card tbody tr:last-child td {
        border-bottom: none !important;
    }

    /* Highlight codes/candidate numbers columns */
    .psle-admin-dark-theme td.bg-blue-50 {
        background: rgba(0, 163, 221, 0.12) !important;
        color: #67d8ff !important;
        border-radius: 6px !important;
        font-weight: 700 !important;
    }
    .psle-admin-dark-theme tr.bg-blue-50 {
        background: rgba(0, 163, 221, 0.06) !important;
    }
    .psle-admin-dark-theme tr.bg-blue-100 {
        background: rgba(0, 163, 221, 0.1) !important;
    }

    /* Banners & Warning Alerts */
    .psle-admin-dark-theme .bg-emerald-50,
    .psle-admin-dark-theme .border-emerald-200 {
        background: rgba(30, 181, 58, 0.08) !important;
        border-color: rgba(30, 181, 58, 0.15) !important;
    }
    .psle-admin-dark-theme .text-emerald-900,
    .psle-admin-dark-theme .text-emerald-800 {
        color: #6ae086 !important;
    }
    .psle-admin-dark-theme .bg-amber-50,
    .psle-admin-dark-theme .border-amber-200 {
        background: rgba(252, 209, 22, 0.08) !important;
        border-color: rgba(252, 209, 22, 0.15) !important;
    }
    .psle-admin-dark-theme .text-amber-900,
    .psle-admin-dark-theme .text-amber-800 {
        color: #fde047 !important;
    }
    .psle-admin-dark-theme .bg-blue-50,
    .psle-admin-dark-theme .border-blue-200 {
        background: rgba(0, 163, 221, 0.08) !important;
        border-color: rgba(0, 163, 221, 0.15) !important;
    }
    .psle-admin-dark-theme .text-blue-900,
    .psle-admin-dark-theme .text-blue-800 {
        color: #67d8ff !important;
    }

    /* Buttons visual overrides */
    .psle-admin-dark-theme button,
    .psle-admin-dark-theme .btn,
    .psle-admin-dark-theme .exam-button,
    .psle-admin-dark-theme .exam-button-secondary {
        border-radius: 8px !important;
        font-weight: 700 !important;
        transition: transform 0.2s, background-color 0.2s, border-color 0.2s, box-shadow 0.2s !important;
    }
    .psle-admin-dark-theme button:hover,
    .psle-admin-dark-theme .btn:hover,
    .psle-admin-dark-theme .exam-button:hover,
    .psle-admin-dark-theme .exam-button-secondary:hover {
        transform: translateY(-1px);
    }
    .psle-admin-dark-theme button:disabled,
    .psle-admin-dark-theme .btn:disabled {
        transform: none !important;
        opacity: 0.4 !important;
    }

    /* Secondary / outline buttons (usually bg-white) */
    .psle-admin-dark-theme button.bg-white,
    .psle-admin-dark-theme button.border-slate-200,
    .psle-admin-dark-theme .exam-button-secondary {
        background: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: var(--tz-text) !important;
    }
    .psle-admin-dark-theme button.bg-white:hover,
    .psle-admin-dark-theme button.border-slate-200:hover,
    .psle-admin-dark-theme .exam-button-secondary:hover {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
    }

    /* Success / Sync Action Buttons */
    .psle-admin-dark-theme button.bg-emerald-600,
    .psle-admin-dark-theme button.bg-green-600 {
        background: linear-gradient(135deg, var(--tz-green), #0f7a1e) !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(30, 181, 58, 0.3) !important;
    }
    .psle-admin-dark-theme button.bg-emerald-600:hover,
    .psle-admin-dark-theme button.bg-green-600:hover {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
        box-shadow: 0 6px 16px rgba(30, 181, 58, 0.4) !important;
    }

    /* Primary Blue Action Buttons (Add candidate, etc.) */
    .psle-admin-dark-theme button.exam-button,
    .psle-admin-dark-theme button.bg-blue-600 {
        background: linear-gradient(135deg, var(--tz-blue), #006fa3) !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 163, 221, 0.3) !important;
    }
    .psle-admin-dark-theme button.exam-button:hover,
    .psle-admin-dark-theme button.bg-blue-600:hover {
        background: linear-gradient(135deg, #00b4f0, #008cc2) !important;
        box-shadow: 0 6px 16px rgba(0, 163, 221, 0.4) !important;
    }

    /* Danger / Delete Action Buttons */
    .psle-admin-dark-theme button.bg-red-600 {
        background: linear-gradient(135deg, #ef4444, #b91c1c) !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
    }
    .psle-admin-dark-theme button.bg-red-600:hover {
        background: linear-gradient(135deg, #f87171, #dc2626) !important;
        box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4) !important;
    }

    /* Table Footer & Pagination Container Overrides */
    .psle-admin-dark-theme .border-t.border-slate-200 {
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        background: #101518 !important;
    }
    
    /* Pagination Page number buttons */
    .psle-admin-dark-theme .flex.items-center.gap-2.rounded-2xl.border.border-slate-200.bg-white {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
    }
    .psle-admin-dark-theme .flex.items-center.gap-2.rounded-2xl.border.border-slate-200.bg-white button {
        color: rgba(255, 255, 255, 0.6) !important;
        background: transparent !important;
        border: none !important;
    }
    .psle-admin-dark-theme .flex.items-center.gap-2.rounded-2xl.border.border-slate-200.bg-white button:hover {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #ffffff !important;
    }
    .psle-admin-dark-theme .flex.items-center.gap-2.rounded-2xl.border.border-slate-200.bg-white button.bg-blue-600 {
        background: linear-gradient(135deg, var(--tz-blue), #0077a3) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 163, 221, 0.25) !important;
    }

    /* Other paginator buttons like next/prev/first/last */
    .psle-admin-dark-theme button[title*="page"],
    .psle-admin-dark-theme button[class*="goTo"],
    .psle-admin-dark-theme .inline-flex.h-10.items-center.gap-2.rounded-xl.border.border-slate-200.bg-white {
        background: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: var(--tz-text-muted) !important;
    }
    .psle-admin-dark-theme button[title*="page"]:hover:not(:disabled),
    .psle-admin-dark-theme button[class*="goTo"]:hover:not(:disabled),
    .psle-admin-dark-theme .inline-flex.h-10.items-center.gap-2.rounded-xl.border.border-slate-200.bg-white:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
    }

    /* Unified Badge styles matching .adm-activity-badge */
    .psle-admin-dark-theme .rounded-full.text-xs.font-semibold {
        display: inline-flex !important;
        align-items: center !important;
        padding: 5px 10px !important;
        border-radius: 999px !important;
        font-size: .68rem !important;
        font-weight: 700 !important;
        border: 1px solid transparent !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
    }
    .psle-admin-dark-theme .bg-green-100.text-green-800,
    .psle-admin-dark-theme .bg-emerald-100.text-emerald-800 {
        background: rgba(30, 181, 58, .14) !important;
        color: #6ae086 !important;
        border-color: rgba(30, 181, 58, .24) !important;
    }
    .psle-admin-dark-theme .bg-yellow-100.text-yellow-800,
    .psle-admin-dark-theme .bg-amber-100.text-amber-800 {
        background: rgba(252, 209, 22, .12) !important;
        color: #FCD116 !important;
        border-color: rgba(252, 209, 22, .2) !important;
    }

    /* Modals & Dialogs Dark theme */
    .registration-modal-shell {
        background: var(--tz-card) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: var(--tz-text) !important;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6) !important;
        border-radius: 20px !important;
    }
    .registration-modal-body {
        background: var(--tz-bg) !important;
        color: var(--tz-text) !important;
    }
    .registration-modal-header {
        background: linear-gradient(135deg, #0d1b2a 0%, #11202e 100%) !important;
        border-bottom: 1px solid rgba(187, 164, 94, 0.15) !important;
    }
    .registration-modal-header-content h2,
    .registration-modal-title {
        color: #ffffff !important;
    }
    .registration-modal-header-content p,
    .registration-modal-subtitle {
        color: var(--tz-text-muted) !important;
    }
    .registration-modal-close {
        color: var(--tz-text-muted) !important;
    }
    .registration-modal-close:hover {
        color: #ffffff !important;
    }
    .registration-modal-panel {
        background: var(--tz-card-soft) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 12px !important;
    }
    .registration-modal-shell label {
        color: var(--tz-text-muted) !important;
    }
    .registration-modal-shell input[readonly],
    .registration-modal-shell textarea[readonly] {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        color: var(--tz-text) !important;
    }
    .registration-modal-actions {
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        background: transparent !important;
    }
    .registration-modal-button-secondary {
        background: rgba(255, 255, 255, 0.05) !important;
        color: var(--tz-text) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .registration-modal-button-secondary:hover {
        background: rgba(255, 255, 255, 0.08) !important;
    }
    .registration-modal-button-primary {
        background: linear-gradient(135deg, var(--tz-blue), #0077a3) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 12px rgba(0, 163, 221, 0.3) !important;
    }
    .registration-modal-button-primary:hover {
        background: linear-gradient(135deg, #00b4f0, #008cc2) !important;
        box-shadow: 0 6px 16px rgba(0, 163, 221, 0.4) !important;
    }
    .registration-modal-note {
        background: rgba(0, 163, 221, 0.08) !important;
        border: 1px solid rgba(0, 163, 221, 0.15) !important;
        color: #f0f4f7 !important;
        border-radius: 12px !important;
    }
    .registration-modal-note-icon {
        background: rgba(0, 163, 221, 0.12) !important;
        color: var(--tz-blue) !important;
    }
    .registration-modal-note p {
        color: var(--tz-text-muted) !important;
    }
    .registration-modal-stat {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 12px !important;
    }

    /* CSV Import Drag & Drop Zone */
    .registration-dropzone {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 2px dashed rgba(255, 255, 255, 0.1) !important;
        border-radius: 16px !important;
    }
    .registration-dropzone:hover {
        border-color: var(--tz-blue) !important;
        background: rgba(255, 255, 255, 0.04) !important;
    }
    .registration-dropzone-icon {
        background: rgba(0, 163, 221, 0.1) !important;
        color: var(--tz-blue) !important;
    }

    /* Tools Modal specific overrides */
    .psle-admin-dark-theme div[x-show="toolsModalOpen"] > div {
        background: var(--tz-card) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6) !important;
    }

    /* Tools Modal button cards */
    .psle-admin-dark-theme button[class*="rounded-3xl"] {
        background: var(--tz-card-soft) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    .psle-admin-dark-theme button[class*="rounded-3xl"]:hover {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: var(--tz-blue) !important;
    }

    /* Tools Modal footer card */
    .psle-admin-dark-theme .flex.flex-col.gap-3.border-t.border-slate-200.bg-white {
        background: var(--tz-card-soft) !important;
        border-top-color: rgba(255, 255, 255, 0.08) !important;
    }

    /* Timetable Card Overrides for standard sessions */
    .psle-admin-dark-theme .rounded-2xl.border.border-slate-200.bg-white {
        background: var(--tz-card-soft) !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    
    /* Timetable Card/Box Overrides for slate-50 blocks */
    .psle-admin-dark-theme .rounded-2xl.border.border-slate-200.bg-slate-50 {
        background: rgba(255, 255, 255, 0.02) !important;
        border-color: rgba(255, 255, 255, 0.06) !important;
    }

    /* Modal Grid Container background */
    .psle-admin-dark-theme .grid.bg-slate-50 {
        background: var(--tz-card-soft) !important;
    }

    /* Horizontal PSLE Feature Navigation Links */
    .psle-tab-link {
        display: inline-flex !important;
        align-items: center !important;
        border-radius: 12px !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        padding: 10px 20px !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        color: rgba(255, 255, 255, 0.6) !important;
        text-decoration: none !important;
        background: rgba(255, 255, 255, 0.02) !important;
        transition: all 0.2s ease !important;
        cursor: pointer !important;
        margin-right: 4px !important;
    }
    .psle-tab-link:hover {
        background: rgba(255, 255, 255, 0.06) !important;
        color: #ffffff !important;
        border-color: rgba(187, 164, 94, 0.25) !important;
        box-shadow: 0 0 12px rgba(187, 164, 94, 0.15) !important;
    }
    .psle-tab-link.active {
        background: rgba(187, 164, 94, 0.12) !important;
        border-color: #bba45e !important;
        color: #fde047 !important;
        box-shadow: 0 0 16px rgba(187, 164, 94, 0.2) !important;
    }
</style>
<div class="registration-shell psle-admin-dark-theme w-full max-w-none px-4 sm:px-6 lg:px-8 py-6">
    <div class="registration-page-stack">
        <!-- Breadcrumb -->
        <div class="adm-breadcrumb">
            EXAM TYPE <i class="fas fa-chevron-right text-[10px] mx-2 text-[#bba45e]"></i> PSLE <i class="fas fa-chevron-right text-[10px] mx-2 text-[#bba45e]"></i> <span>CONFIGURATION</span>
        </div>

        <!-- Page Header -->
        <div class="adm-page-header">
            <h1 class="adm-page-title">PSLE Configuration</h1>
            <p class="adm-page-desc">Manage PSLE subjects, paper structure, school centres, and pupil registration with a primary-school workflow aligned to region, council, and school administration.</p>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 w-full adm-stats">
                <div class="adm-stat stat-blue">
                    <div class="adm-stat-label">Subjects</div>
                    <div class="adm-stat-value" x-text="subjects.length">0</div>
                    <i class="fa-solid fa-book adm-stat-icon"></i>
                    <p>Configured PSLE subjects and paper definitions.</p>
                </div>
                <div class="adm-stat stat-green">
                    <div class="adm-stat-label">Synced Councils</div>
                    <div class="adm-stat-value" x-text="syncedCouncilCount">0</div>
                    <i class="fa-solid fa-map-signs adm-stat-icon"></i>
                    <p>Councils represented by the current NECTA-synced PSLE school scope.</p>
                </div>
                <div class="adm-stat stat-yellow">
                    <div class="adm-stat-label">Synced Primary Schools</div>
                    <div class="adm-stat-value" x-text="syncedSchoolCount">0</div>
                    <i class="fa-solid fa-school adm-stat-icon"></i>
                    <p>NECTA-synced PSLE schools visible in the current scope.</p>
                </div>
                <div class="adm-stat stat-black">
                    <div class="adm-stat-label">Registered Pupils</div>
                    <div class="adm-stat-value" x-text="Number(stats.registeredPupils || 0).toLocaleString()">0</div>
                    <i class="fa-solid fa-user-graduate adm-stat-icon"></i>
                    <p>PSLE pupil records currently visible within this workspace.</p>
                </div>
            </div>

            <!-- Main Grid/Flex Container for Sidebar Navigation & Tab Content (Inner page vertical navigation moved to left admin sidebar) -->
            <div class="w-full">
                
                <!-- Tab Content Area -->
                <div class="w-full space-y-6 min-w-0">
                    
                    <!-- PSLE Feature Navigation -->
                    <div class="registration-surface w-full max-w-none rounded-2xl p-3 mb-5">
                        <div class="flex flex-wrap gap-2">
                            <a href="/admin/exam-types/psle?tab=subjects"
                               @click.prevent="setActiveTab('subjects')"
                               class="psle-tab-link"
                               :class="activeTab === 'subjects' ? 'active' : ''">
                               <i class="fas fa-book mr-2"></i>Subjects
                            </a>

                            <a href="/admin/exam-types/psle?tab=paper-structure"
                               @click.prevent="setActiveTab('papers')"
                               class="psle-tab-link"
                               :class="activeTab === 'papers' ? 'active' : ''">
                               <i class="fas fa-layer-group mr-2"></i>Paper Structure
                            </a>

                            <a href="/admin/exam-types/psle?tab=timetable"
                               @click.prevent="setActiveTab('timetable')"
                               class="psle-tab-link"
                               :class="activeTab === 'timetable' ? 'active' : ''">
                               <i class="fas fa-calendar-alt mr-2"></i>Timetable
                            </a>

                            <a href="/admin/exam-types/psle?tab=schools"
                               @click.prevent="setActiveTab('schools')"
                               class="psle-tab-link"
                               :class="activeTab === 'schools' ? 'active' : ''">
                               <i class="fas fa-school mr-2"></i>Schools & Centres
                            </a>

                            <a href="/admin/exam-types/psle?tab=pupils"
                               @click.prevent="setActiveTab('pupils')"
                               class="psle-tab-link"
                               :class="activeTab === 'pupils' ? 'active' : ''">
                               <i class="fas fa-user-graduate mr-2"></i>Pupil Register
                            </a>
                        </div>
                    </div>

            <section x-show="activeTab === 'subjects'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900">PSLE Subjects</h2>
                            <p class="mt-2 text-sm text-slate-600">PSLE subjects come from the official NECTA catalog. Synchronize the catalog instead of creating local subject variants.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="downloadSubjectsTemplate()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-download mr-2"></i>Template
                            </button>
                            <button @click="syncOfficialSubjects()" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-rotate mr-2"></i>Sync Official Catalog
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[220px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="subjectSearch" @input="filterSubjects()" type="text" placeholder="Search PSLE subjects..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div x-show="loadingSubjects" class="p-8 text-center text-slate-500">
                        <i class="fas fa-spinner animate-spin text-2xl"></i>
                    </div>
                    <div x-show="!loadingSubjects" class="overflow-x-auto">
                        <table class="w-full min-w-[760px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-book mr-1 text-gray-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-layer-group mr-1 text-emerald-600"></i>Subject Group</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-note-sticky mr-1 text-amber-600"></i>Description</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in filteredSubjects" :key="subject.id">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-center text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded" x-text="formatPsleSubjectCode(subject.code)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="formatPsleCategory(subject.category)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="subject.description || '-'"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="openSubjectModal(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Subject">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openSubjectPapers(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800" title="Open Paper Governance">
                                                    <i class="fas fa-table-list"></i>
                                                </button>
                                                <button @click="openOfficialPaperSource(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800" title="Open Official Source">
                                                    <i class="fas fa-file-arrow-up-right"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!loadingSubjects && filteredSubjects.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE subjects found. Use `Sync Official Catalog` to load the NECTA subject list.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'papers'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <h2 class="text-2xl font-black text-slate-900">PSLE Paper Governance</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-600">Manage paper-structure readiness for official PSLE subjects from one controlled workspace. This area is intentionally governance-first: paper formats are not guessed locally and must be backed by official NECTA source material before activation.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openPaperGuidanceModal()" class="whitespace-nowrap rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700">
                                <i class="fas fa-circle-info mr-2"></i>Governance Notes
                            </button>
                            <button @click="showMessage('Official PSLE paper formats are pending verified NECTA source import.', 'error')" class="whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-shield-check mr-2"></i>Await Official Import
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface p-5">
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold uppercase tracking-[0.18em] text-[11px] text-emerald-800">Official Source On File</p>
                                <p class="mt-1 font-semibold">FORMAT FOR PRIMARY SCHOOL LEAVING EXAMINATIONS, Revised January 2024</p>
                                <p class="mt-1 text-emerald-800/90">NECTA handles PSLE subject formats through one official format booklet, with each subject covered in its own section rather than through a public paper-setup dashboard.</p>
                            </div>
                            <a href="https://necta.go.tz/webroot/uploads/news/FORMAT_PSLE_2024_ENGLISH.pdf" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-emerald-300 bg-white px-4 py-2.5 font-semibold text-emerald-800 transition hover:bg-emerald-100">
                                <i class="fas fa-file-pdf mr-2"></i>Open Official Booklet
                            </a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 w-full adm-stats">
                    <div class="adm-stat stat-blue">
                        <div class="adm-stat-label">Official Subjects</div>
                        <div class="adm-stat-value" x-text="subjects.length">0</div>
                        <i class="fa-solid fa-book-open adm-stat-icon"></i>
                        <p>Subjects currently controlled by the official PSLE catalog.</p>
                    </div>
                    <div class="adm-stat stat-green">
                        <div class="adm-stat-label">Verified Formats</div>
                        <div class="adm-stat-value" x-text="verifiedPaperCount">0</div>
                        <i class="fa-solid fa-check-double adm-stat-icon"></i>
                        <p>Subjects mapped to the official NECTA PSLE format booklet now on file.</p>
                    </div>
                    <div class="adm-stat stat-yellow">
                        <div class="adm-stat-label">Pending Source</div>
                        <div class="adm-stat-value" x-text="pendingPaperCount">0</div>
                        <i class="fa-solid fa-hourglass-half adm-stat-icon"></i>
                        <p>Subjects still waiting for internal extraction into structured IRMS paper fields.</p>
                    </div>
                    <div class="adm-stat stat-black">
                        <div class="adm-stat-label">Policy Mode</div>
                        <div class="adm-stat-value">Read Only</div>
                        <i class="fa-solid fa-lock adm-stat-icon"></i>
                        <p>Local paper edits stay blocked until official format definitions are loaded.</p>
                    </div>
                </div>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col flex-1 min-w-[240px]">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Search</label>
                            <input x-model="paperSearch" @input="filterPaperSubjects()" type="text" placeholder="Search PSLE subjects for paper governance..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="paperStatusFilter = 'all'; filterPaperSubjects()" :class="paperStatusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">All</button>
                            <button @click="paperStatusFilter = 'pending'; filterPaperSubjects()" :class="paperStatusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Pending Source</button>
                            <button @click="paperStatusFilter = 'verified'; filterPaperSubjects()" :class="paperStatusFilter === 'verified' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-200'" class="px-4 py-2 rounded-lg text-sm font-semibold transition">Verified</button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1220px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-book mr-1 text-gray-600"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-layer-group mr-1 text-emerald-600"></i>Subject Group</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-shield-halved mr-1 text-amber-600"></i>Format Status</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-file-lines mr-1 text-violet-600"></i>Official Source</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap"><i class="fas fa-note-sticky mr-1 text-slate-500"></i>Governance Note</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="subject in filteredPaperSubjects" :key="subject.id || subject.code">
                                    <tr class="hover:bg-blue-50 transition-colors">
                                        <td class="px-3 py-1.5 text-center text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded whitespace-nowrap" x-text="formatPsleSubjectCode(subject.code)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium whitespace-nowrap" x-text="subject.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="formatPsleCategory(subject.category)"></td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap" :class="paperStatusKey(subject) === 'verified' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'" x-text="paperStatusLabel(subject)"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="paperSourceLabel(subject)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="paperGovernanceNote(subject)"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="openPaperSubjectModal(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Governance">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openOfficialPaperSource(subject)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-800" title="Open Source">
                                                    <i class="fas fa-file-arrow-up-right"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="filteredPaperSubjects.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No PSLE paper-governance subjects match the current search or status filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <section x-show="activeTab === 'timetable'" class="space-y-6">
                <div class="registration-surface p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-4xl">
                            <h2 class="text-2xl font-black text-slate-100">PSLE Zonal Timetable</h2>
                            <p class="mt-2 text-sm leading-7 text-slate-400">This timetable workspace is aligned to the zonal PSLE LaTeX source used for the May 2026 mock schedule. It preserves the same official-style structure: date and day, session time, hidden code, subject title, and controlled break windows.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button @click="openTimetablePreview()" class="whitespace-nowrap rounded-xl border border-slate-700/50 bg-slate-800/30 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/60 hover:text-white">
                                <i class="fas fa-magnifying-glass mr-2"></i>Print Preview
                            </button>
                            <button @click="printTimetable()" class="whitespace-nowrap rounded-xl border border-slate-700/50 bg-slate-800/30 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:border-slate-600 hover:bg-slate-800/60 hover:text-white">
                                <i class="fas fa-print mr-2"></i>Print PDF
                            </button>
                            <button @click="openTimetableSourceModal()" class="whitespace-nowrap rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fas fa-file-lines mr-2"></i>Source Notes
                            </button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface p-5">
                    <div class="rounded-3xl border border-emerald-500/20 bg-emerald-500/5 px-5 py-4 text-sm text-emerald-300">
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="font-semibold uppercase tracking-[0.18em] text-[11px] text-emerald-400">Source Alignment</p>
                                <p class="mt-1 font-semibold text-white">RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA, MEI 2026</p>
                                <p class="mt-1 text-slate-300">Structured from the zonal LaTeX timetable used by the academic special zone for Tanga, Iringa, Singida, Morogoro, Dodoma, Lindi, Mtwara, and Tabora.</p>
                            </div>
                            <span class="inline-flex items-center justify-center whitespace-nowrap rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-2.5 font-semibold text-emerald-400">
                                <i class="fas fa-calendar-days mr-2"></i>Mock Timetable 2026
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 w-full adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">Exam Days</div>
                        <div class="adm-stat-value" x-text="timetableDays.length">0</div>
                        <i class="fa-solid fa-calendar-week adm-stat-icon" style="color: #67d8ff;"></i>
                        <p>Scheduled days in the current zonal mock timetable.</p>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Exam Slots</div>
                        <div class="adm-stat-value" x-text="timetableExamSlotCount">0</div>
                        <i class="fa-solid fa-clock adm-stat-icon" style="color: #6ae086;"></i>
                        <p>Timed examination sitting windows excluding designated breaks.</p>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Break Windows</div>
                        <div class="adm-stat-value" x-text="timetableBreakCount">0</div>
                        <i class="fa-solid fa-mug-hot adm-stat-icon" style="color: #fde047;"></i>
                        <p>Official break intervals preserved exactly from the zonal source timetable.</p>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Medium Tracks</div>
                        <div class="adm-stat-value">SWA + ENG</div>
                        <i class="fa-solid fa-language adm-stat-icon" style="color: #bba45e;"></i>
                        <p>The timetable preserves both Kiswahili and English-medium subject rows where the zonal format includes them.</p>
                    </div>
                </div>

                <div class="registration-surface registration-toolbar-card">
                    <div class="registration-toolbar-grid">
                        <div class="flex flex-col min-w-[220px]">
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Day</label>
                            <div class="relative" @click.outside="timetableDayOpen = false">
                                <button type="button" class="w-full px-3 py-2 border border-slate-700/50 text-left bg-slate-800/30 hover:bg-slate-800/50 transition-colors flex justify-between items-center rounded-lg text-slate-200" @click="timetableDayOpen = !timetableDayOpen">
                                    <span class="truncate" x-text="selectedTimetableDay ? (timetableDays.find(day => day.date === selectedTimetableDay)?.label || 'All Days') : 'All Days'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                                </button>
                                <div x-show="timetableDayOpen" class="absolute top-full left-0 right-0 bg-slate-900 border border-slate-700 z-30 rounded-lg flex flex-col mt-1 shadow-xl overflow-hidden" x-transition>
                                    <div class="max-h-56 overflow-y-auto">
                                        <div @click="setTimetableDay('')" class="px-3 py-2 hover:bg-blue-600 hover:text-white text-slate-300 cursor-pointer text-sm transition-colors">All Days</div>
                                        <template x-for="day in timetableDays" :key="day.date">
                                            <div @click="setTimetableDay(day.date)" :class="selectedTimetableDay === day.date ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-blue-600 hover:text-white'" class="px-3 py-2 cursor-pointer text-sm transition-colors" x-text="day.label"></div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col flex-1 min-w-[240px]">
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Search</label>
                            <input x-model="timetableSearch" @input="filterTimetableEntries()" type="text" placeholder="Search subject, code, or day..." class="w-full px-3 py-2 border border-slate-700/50 bg-slate-800/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-200 placeholder-slate-500">
                        </div>
                        <div class="ml-auto flex gap-2 items-end self-end">
                            <button @click="timetableTypeFilter = 'all'; filterTimetableEntries()" :class="timetableTypeFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-slate-800/30 text-slate-300 border border-slate-700/50 hover:bg-slate-800/60 hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">All Rows</button>
                            <button @click="timetableTypeFilter = 'exam'; filterTimetableEntries()" :class="timetableTypeFilter === 'exam' ? 'bg-emerald-600 text-white' : 'bg-slate-800/30 text-slate-300 border border-slate-700/50 hover:bg-slate-800/60 hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">Exam Only</button>
                            <button @click="timetableTypeFilter = 'break'; filterTimetableEntries()" :class="timetableTypeFilter === 'break' ? 'bg-amber-500 text-white' : 'bg-slate-800/30 text-slate-300 border border-slate-700/50 hover:bg-slate-800/60 hover:text-white'" class="px-4 py-2 rounded-lg text-sm font-semibold transition whitespace-nowrap">Breaks</button>
                        </div>
                    </div>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1120px]">
                            <thead class="bg-slate-900/80 border-b border-slate-700/80 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-calendar-day mr-1 text-blue-400"></i>Date & Day</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-clock mr-1 text-emerald-400"></i>Time</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-hashtag mr-1 text-amber-400"></i>Hidden Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-book mr-1 text-violet-400"></i>Subject</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-language mr-1 text-slate-400"></i>Track</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-layer-group mr-1 text-indigo-400"></i>Session Type</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-slate-300 whitespace-nowrap"><i class="fas fa-note-sticky mr-1 text-slate-400"></i>Operational Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/50">
                                <template x-for="entry in filteredTimetableEntries" :key="entry.key">
                                    <tr :class="entry.type === 'break' ? 'bg-amber-500/5 hover:bg-amber-500/10' : 'hover:bg-slate-800/30'" class="transition-colors">
                                        <td class="px-3 py-1.5 text-[13px] font-semibold text-slate-200 whitespace-nowrap" x-text="entry.dayLabel"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-300 whitespace-nowrap" x-text="entry.time"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold whitespace-nowrap" :class="entry.type === 'break' ? 'text-amber-400' : 'text-sky-400'" x-text="entry.code || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] font-medium whitespace-nowrap" :class="entry.type === 'break' ? 'text-amber-200' : 'text-slate-100'" x-text="entry.subject"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-400 whitespace-nowrap" x-text="entry.track"></td>
                                        <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-bold border" :class="entry.type === 'break' ? 'bg-amber-500/15 text-amber-400 border-amber-500/30' : 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'" x-text="entry.type === 'break' ? 'Break' : 'Exam Sitting'"></span>
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] text-slate-400 whitespace-nowrap" x-text="entry.note"></td>
                                    </tr>
                                </template>
                                <tr x-show="filteredTimetableEntries.length === 0">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-slate-500">No timetable rows match the current day or search filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <section class="grid gap-4 xl:grid-cols-2">
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Day One Layout</p>
                        <h3 class="mt-3 text-xl font-black text-white">20.05.2026 · JUMATANO</h3>
                        <div class="mt-4 space-y-3">
                            <template x-for="entry in timetableEntries.filter(item => item.date === '20.05.2026')" :key="entry.key + '-card'">
                                <div class="rounded-2xl border px-4 py-3 transition-all duration-200 shadow-sm" :class="entry.type === 'break' ? 'border-amber-500/20 bg-amber-500/5 hover:border-amber-500/30' : 'border-slate-700/50 bg-slate-800/30 hover:border-slate-600/50 hover:bg-slate-800/50'">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-100" x-text="entry.subject"></p>
                                            <p class="mt-1 text-xs text-slate-400" x-text="entry.track + ' · ' + entry.note"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-slate-200" x-text="entry.time"></p>
                                            <p class="mt-1 text-xs font-mono text-slate-400" x-text="entry.code || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </article>
                    <article class="registration-surface p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Day Two Layout</p>
                        <h3 class="mt-3 text-xl font-black text-white">21.05.2026 · ALHAMISI</h3>
                        <div class="mt-4 space-y-3">
                            <template x-for="entry in timetableEntries.filter(item => item.date === '21.05.2026')" :key="entry.key + '-card'">
                                <div class="rounded-2xl border px-4 py-3 transition-all duration-200 shadow-sm" :class="entry.type === 'break' ? 'border-amber-500/20 bg-amber-500/5 hover:border-amber-500/30' : 'border-slate-700/50 bg-slate-800/30 hover:border-slate-600/50 hover:bg-slate-800/50'">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-100" x-text="entry.subject"></p>
                                            <p class="mt-1 text-xs text-slate-400" x-text="entry.track + ' · ' + entry.note"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-slate-200" x-text="entry.time"></p>
                                            <p class="mt-1 text-xs font-mono text-slate-400" x-text="entry.code || '-'"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </article>
                </section>
            </section>


            <section x-show="activeTab === 'schools'" class="space-y-6">
                <div class="registration-surface exam-filter-panel exam-section">
                    <div class="exam-filter-form">
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Exam Year</label>
                            <input
                                x-model="examYearSearch"
                                @input="syncExamYearSelection()"
                                @change="syncExamYearSelection()"
                                list="psle_schools_exam_year_options"
                                class="exam-search-input"
                                placeholder="Search exam year"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_exam_year_options">
                                <option value=""></option>
                                <template x-for="year in examYears" :key="'schools-year-' + year.id">
                                    <option :value="year.year_label"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Region</label>
                            <input
                                x-model="regionSearch"
                                @input="syncRegionSelection()"
                                @change="syncRegionSelection()"
                                list="psle_schools_region_options"
                                class="exam-search-input"
                                placeholder="Search region"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_region_options">
                                <option value=""></option>
                                <template x-for="region in regions" :key="'schools-region-' + region.id">
                                    <option :value="region.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label class="exam-label">Council</label>
                            <input
                                x-model="districtSearch"
                                @input="syncDistrictSelection()"
                                @change="syncDistrictSelection()"
                                list="psle_schools_district_options"
                                class="exam-search-input"
                                placeholder="Search council"
                                autocomplete="off"
                            >
                            <datalist id="psle_schools_district_options">
                                <option value=""></option>
                                <template x-for="district in filteredDistricts" :key="'schools-district-' + district.id">
                                    <option :value="district.name"></option>
                                </template>
                            </datalist>
                        </div>
                        <div class="exam-field flex-1">
                            <label class="exam-label">Search</label>
                            <input x-model="schoolSearch" @input.debounce.300ms="loadSchools()" type="text" placeholder="Search primary schools..." class="exam-search-input">
                        </div>
                        <div class="exam-actions-row">
                            <button @click="syncNectaSchools()" :disabled="syncingSchools" class="exam-button">
                                <i class="fas" :class="syncingSchools ? 'fa-spinner animate-spin' : 'fa-rotate'"></i>
                                <span x-text="syncingSchools ? 'Syncing...' : (filterRegion ? 'Sync Selected Region' : 'Sync Registered Regions')"></span>
                            </button>
                            <button @click="openAddSchoolModal()" class="exam-button bg-green-600">
                                <i class="fas fa-plus"></i> Add School
                            </button>
                            <button @click="resetFilters()" class="exam-button-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </div>
                    <p class="exam-section-text">This sync pulls PSLE 2025 primary schools from the official NECTA site for the selected region, or for all registered regions when no region filter is active.</p>
                </div>

                <template x-if="filterDistrict && schools.length > 0 && schools.reduce((sum, s) => sum + (s.candidates_count || 0), 0) === 0">
                    <div class="mb-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-amber-400"></i>
                            <span>
                                <strong x-text="districtSearch"></strong> has registered schools but no PSLE candidates linked for the selected exam year.
                            </span>
                        </div>
                    </div>
                </template>

                <div x-show="selectedSchoolItems.size > 0" class="flex gap-2 items-center bg-blue-50 p-4 rounded-lg border border-blue-200 shadow-sm">
                    <span class="text-sm font-medium text-gray-700">
                        <span x-text="selectedSchoolItems.size"></span> school(s) selected
                    </span>
                    <button @click="bulkDeleteSchools()" class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>

                <div class="registration-surface registration-table-card overflow-x-auto">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[820px]">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAllSchools()"
                                            :checked="paginatedVisibleSchools.length > 0 && paginatedVisibleSchools.every(school => selectedSchoolItems.has(school.id))"
                                            class="w-4 h-4 cursor-pointer"
                                            title="Select all visible schools"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-barcode mr-1 text-blue-600"></i>Code</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-school mr-1 text-purple-600"></i>Primary School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-building-columns mr-1 text-emerald-600"></i>Ownership</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-map mr-1 text-amber-600"></i>Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-location-dot mr-1 text-slate-500"></i>Region</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider"><i class="fas fa-users mr-1 text-teal-600"></i>Candidates</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="school in paginatedVisibleSchools" :key="school.id">
                                    <tr class="hover:bg-blue-50 transition-colors" :class="selectedSchoolItems.has(school.id) ? 'bg-blue-100' : ''">
                                        <td class="px-3 py-1.5 text-left">
                                            <input
                                                type="checkbox"
                                                :checked="selectedSchoolItems.has(school.id)"
                                                @change="toggleSchoolSelection(school.id)"
                                                class="w-4 h-4 cursor-pointer"
                                            >
                                        </td>
                                        <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded" x-text="school.code || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium" x-text="school.name"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="school.ownership || '-'"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="resolveDistrictName(school)"></td>
                                        <td class="px-3 py-1.5 text-[13px] text-gray-600" x-text="resolveRegionName(school)"></td>
                                        <td class="px-3 py-1.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center justify-center min-w-[2.5rem] rounded-full border border-cyan-500/40 bg-cyan-500/10 px-2 py-0.5 text-xs font-bold text-cyan-200 shadow-sm">
                                                <span x-text="Number(school.candidates_count ?? school.registered_pupils ?? school.pupils_count ?? 0).toLocaleString()"></span>
                                            </span>
                                        </td>
                                        <td class="px-3 py-1.5 text-sm">
                                            <div class="flex items-center justify-center gap-1">
                                                <button @click="viewSchool(school)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View School">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button @click="openSchoolPupils(school)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800" title="Open Pupils">
                                                    <i class="fas fa-user-graduate"></i>
                                                </button>
                                                <button @click="openEditSchoolModal(school)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-amber-600 transition hover:bg-amber-50 hover:text-amber-800" title="Edit School">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button @click="deleteSchool(school.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 hover:text-red-800" title="Delete School">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="visibleSchools.length === 0">
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">No primary schools match the current filters.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5" x-show="visibleSchools.length > 0">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="schoolsCurrentPage"></span> of <span x-text="Math.max(schoolsTotalPages, 1)"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-table-list text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="paginatedVisibleSchools.length"></span> of <span class="font-semibold text-slate-800" x-text="visibleSchools.length"></span> schools</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <button @click="goToFirstSchoolsPage()" :disabled="schoolsCurrentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="First page">
                                    <i class="fas fa-angles-left text-xs"></i>
                                </button>
                                <button @click="goToPreviousSchoolsPage()" :disabled="schoolsCurrentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Previous page">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </button>
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                    <template x-for="page in visibleSchoolPages" :key="page">
                                        <button @click="goToSchoolsPage(page)" :class="schoolsCurrentPage === page ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors" x-text="page"></button>
                                    </template>
                                </div>
                                <button @click="goToNextSchoolsPage()" :disabled="schoolsCurrentPage >= schoolsTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Next page">
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                                <button @click="goToLastSchoolsPage()" :disabled="schoolsCurrentPage >= schoolsTotalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Last page">
                                    <i class="fas fa-angles-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section x-show="activeTab === 'pupils'" class="space-y-6">
                <div class="registration-surface exam-filter-panel exam-section w-full rounded-2xl">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 w-full">
                            <div class="exam-field flex flex-col">
                                <label class="exam-label">Region</label>
                                <input
                                    x-model="regionSearch"
                                    @input="syncRegionSelection()"
                                    @change="syncRegionSelection()"
                                    list="psle_pupils_region_options"
                                    class="exam-search-input"
                                    placeholder="Search region"
                                    autocomplete="off"
                                >
                                <datalist id="psle_pupils_region_options">
                                    <option value=""></option>
                                    <template x-for="region in regions" :key="'pupils-region-' + region.id">
                                        <option :value="region.name"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="exam-field flex flex-col">
                                <label class="exam-label">Council</label>
                                <input
                                    x-model="districtSearch"
                                    @input="syncDistrictSelection()"
                                    @change="syncDistrictSelection()"
                                    list="psle_pupils_district_options"
                                    class="exam-search-input"
                                    placeholder="Search council"
                                    autocomplete="off"
                                >
                                <datalist id="psle_pupils_district_options">
                                    <option value=""></option>
                                    <template x-for="district in filteredDistricts" :key="'pupils-district-' + district.id">
                                        <option :value="district.name"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="exam-field flex flex-col">
                                <label class="exam-label">School</label>
                                <input
                                    x-model="schoolOptionSearch"
                                    @input="syncSchoolSelection()"
                                    @change="syncSchoolSelection()"
                                    list="psle_pupils_school_options"
                                    class="exam-search-input disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-400"
                                    :disabled="!filterDistrict"
                                    :placeholder="filterDistrict ? 'Search school' : 'Select Council First'"
                                    autocomplete="off"
                                >
                                <datalist id="psle_pupils_school_options">
                                    <option value=""></option>
                                    <template x-for="school in filteredSchools" :key="'pupils-school-' + school.id">
                                        <option :value="formatSchoolOptionLabel(school)"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div class="exam-field flex flex-col">
                                <label class="exam-label">Search</label>
                                <input x-model="candidateSearch" @input.debounce.500ms="onSearchChange()" type="text" placeholder="Search pupils..." class="exam-search-input">
                            </div>
                        </div>
                        <div class="flex flex-wrap items-end justify-end gap-3 pt-2">
                            <button @click="openToolsModal()" class="exam-button">
                                <i class="fas fa-wrench"></i> Tools
                                <i class="fas fa-arrow-up-right-from-square text-xs opacity-80"></i>
                            </button>
                            <button @click="exportCandidatesCSV()" class="exam-button">
                                <i class="fas fa-file-csv"></i> Export
                            </button>
                            <button @click="openCandidateModal()" class="exam-button-secondary" style="background:#16a34a; border-color:#16a34a; color:#ffffff;">
                                <i class="fas fa-plus"></i> Add Pupil
                            </button>
                        </div>
                    </div>
                </div>

                <div x-show="selectedCandidateItems.size > 0" class="flex gap-2 items-center bg-blue-50 p-4 rounded-lg border border-blue-200 shadow-sm">
                    <span class="text-sm font-medium text-gray-700">
                        <span x-text="selectedCandidateItems.size"></span> pupil(s) selected
                    </span>
                    <button @click="bulkDeleteCandidates()" class="ml-auto bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors text-sm font-medium flex items-center gap-2">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                </div>

                <div class="registration-surface registration-table-card w-full max-w-none overflow-hidden rounded-2xl">
                    <div class="w-full overflow-x-auto">
                        <table class="min-w-full w-full table-auto">
                            <thead class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            @change="toggleSelectAllCandidates()"
                                            :checked="candidates.length > 0 && candidates.every(candidate => selectedCandidateItems.has(candidate.id))"
                                            class="w-4 h-4 cursor-pointer"
                                            title="Select all visible pupils"
                                        >
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap"><i class="fas fa-barcode mr-1 text-blue-600"></i>Candidate Number</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap"><i class="fas fa-id-card mr-1 text-indigo-600"></i>PReM No</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider min-w-[180px]"><i class="fas fa-user mr-1 text-gray-600"></i>Pupil Name</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap"><i class="fas fa-venus-mars mr-1"></i>Sex</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider min-w-[220px]"><i class="fas fa-school mr-1 text-purple-600"></i>Primary School</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap"><i class="fas fa-map mr-1"></i>Council</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap"><i class="fas fa-info-circle mr-1"></i>Status</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider whitespace-nowrap">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <!-- Real candidate rows -->
                                <template x-if="!loadingCandidates">
                                    <template x-for="candidate in candidates" :key="candidate.id">
                                        <tr class="hover:bg-blue-50 transition-colors" :class="selectedCandidateItems.has(candidate.id) ? 'bg-blue-100' : ''">
                                            <td class="px-3 py-1.5 text-left whitespace-nowrap">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedCandidateItems.has(candidate.id)"
                                                    @change="toggleCandidateSelection(candidate.id)"
                                                    class="w-4 h-4 cursor-pointer"
                                                >
                                            </td>
                                            <td class="px-3 py-1.5 text-[13px] font-mono font-semibold text-blue-700 bg-blue-50 rounded whitespace-nowrap" x-text="candidate.candidate_id || '-'"></td>
                                            <td class="px-3 py-1.5 text-[13px] font-mono text-gray-600 whitespace-nowrap" x-text="candidate.prem_no || '-'"></td>
                                            <td class="px-3 py-1.5 text-[13px] text-gray-800 font-medium min-w-[180px]" x-text="candidate.full_name"></td>
                                            <td class="px-3 py-1.5 text-[13px] text-gray-600 text-center font-medium whitespace-nowrap" x-text="candidate.gender === 'M' ? '♂ M' : (candidate.gender === 'F' ? '♀ F' : '-')"></td>
                                            <td class="px-3 py-1.5 text-[13px] text-gray-600 min-w-[220px]" x-text="candidate.school_name || '-'"></td>
                                            <td class="px-3 py-1.5 text-[13px] text-gray-600 whitespace-nowrap" x-text="candidate.district_name || resolveCandidateDistrict(candidate)"></td>
                                            <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="(candidate.status || 'registered') === 'registered' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'" x-text="candidate.status || 'registered'"></span>
                                            </td>
                                            <td class="px-3 py-1.5 text-sm whitespace-nowrap">
                                                <div class="flex gap-1">
                                                    <button @click="viewCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-600 transition hover:bg-slate-100 hover:text-slate-900" title="View Pupil"><i class="fas fa-eye"></i></button>
                                                    <button @click="editCandidate(candidate)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"><i class="fas fa-edit"></i></button>
                                                    <button @click="deleteCandidate(candidate.id)" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-red-600 transition hover:bg-red-50 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>

                                <!-- Skeleton rows when loading -->
                                <template x-if="loadingCandidates">
                                    <template x-for="i in [1,2,3,4,5,6]" :key="'skeleton-row-' + i">
                                        <tr class="animate-pulse bg-gray-50/10">
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-4 w-4 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-blue-200/30 h-5 w-28 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-5 w-24 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-5 w-40 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-5 w-8 rounded mx-auto"></div></td>
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-5 w-48 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-gray-300/40 h-5 w-24 rounded"></div></td>
                                            <td class="px-3 py-3"><div class="bg-green-200/30 h-5 w-16 rounded-full"></div></td>
                                            <td class="px-3 py-3"><div class="flex gap-2 justify-center"><div class="bg-gray-300/40 h-5 w-5 rounded"></div><div class="bg-gray-300/40 h-5 w-5 rounded"></div><div class="bg-gray-300/40 h-5 w-5 rounded"></div></div></td>
                                        </tr>
                                    </template>
                                </template>
                                <tr x-show="!loadingCandidates && candidates.length === 0">
                                    <td colspan="9" class="px-6 py-12 text-center text-sm text-slate-400">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <i class="fa-solid fa-user-slash text-slate-500 text-3xl mb-2"></i>
                                            <p class="font-semibold text-slate-200" x-text="
                                                (totalCandidates === 0 && !filterRegion && !filterDistrict && !filterSchool && !candidateSearch)
                                                    ? 'No pupils registered yet for the selected PSLE year.'
                                                    : (filterSchool && candidates.length === 0)
                                                        ? 'Pupils exist but are not linked to the selected school.'
                                                        : (filterRegion || filterDistrict) && candidates.length === 0
                                                            ? 'No pupils found for the selected region/council filters.'
                                                            : candidateSearch && candidates.length === 0
                                                                ? 'No pupil matches the current search keyword.'
                                                                : 'No pupils found for the current PSLE filters.'
                                            "></p>
                                            <p class="text-xs text-slate-500">Please refine your search query or check active filter selections.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @if(app()->environment('local'))
                    <div
                        x-show="!loadingCandidates && candidateDiagnostics"
                        x-cloak
                        class="mx-6 mt-4 rounded-lg border border-[#bba45e]/30 bg-[#bba45e]/5 px-4 py-3 text-xs text-[#fde047]"
                    >
                        <div class="font-semibold uppercase tracking-wider text-[10px] text-slate-400 mb-2 flex items-center gap-1">
                            <i class="fas fa-bug text-[#fcd116]"></i> Local PSLE Candidate Diagnostic
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-slate-300">
                            <span>Active exam year: <strong class="text-white" x-text="candidateDiagnostics?.active_exam_year ?? '-'"></strong></span>
                            <span>Selected region ID: <strong class="text-white" x-text="candidateDiagnostics?.selected_region_id ?? '-'"></strong></span>
                            <span>Selected council ID: <strong class="text-white" x-text="candidateDiagnostics?.selected_council_id ?? '-'"></strong></span>
                            <span>Selected school ID: <strong class="text-white" x-text="candidateDiagnostics?.selected_school_id ?? '-'"></strong></span>
                            <span>Total pupils before filters: <strong class="text-white" x-text="candidateDiagnostics?.total_pupils_before_filters ?? '-'"></strong></span>
                            <span>Total pupils after filters: <strong class="text-white" x-text="candidateDiagnostics?.total_pupils_after_filters ?? '-'"></strong></span>
                            <span>Authenticated user ID: <strong class="text-white" x-text="candidateDiagnostics?.authenticated_user_id ?? '-'"></strong></span>
                            <span>Authenticated user email: <strong class="text-white" x-text="candidateDiagnostics?.authenticated_user_email ?? '-'"></strong></span>
                            <span>Route source: <strong class="text-white" x-text="candidateDiagnostics?.route_source ?? '-'"></strong></span>
                            <span>Middleware: <strong class="text-white" x-text="candidateDiagnostics?.middleware ?? '-'"></strong></span>
                        </div>
                    </div>
                @endif
                    <div class="border-t border-slate-200 bg-gradient-to-r from-slate-50 via-white to-slate-50 px-6 py-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-600">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 font-semibold text-blue-700">
                                    <i class="fas fa-layer-group text-xs"></i>
                                    <span>Page <span x-text="currentPage"></span> of <span x-text="Math.max(totalPages, 1)"></span></span>
                                </div>
                                <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5">
                                    <i class="fas fa-table-list text-xs text-slate-400"></i>
                                    <span>Showing <span class="font-semibold text-slate-800" x-text="candidates.length"></span> of <span class="font-semibold text-slate-800" x-text="totalCandidates"></span> pupils</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                                <button @click="goToFirstCandidatesPage()" :disabled="currentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="First page">
                                    <i class="fas fa-angles-left text-xs"></i>
                                </button>
                                <button @click="goToPreviousCandidatesPage()" :disabled="currentPage <= 1" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Previous page">
                                    <i class="fas fa-chevron-left text-xs"></i>
                                    <span class="hidden sm:inline">Previous</span>
                                </button>
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-2 py-2 shadow-sm">
                                    <template x-for="page in visibleCandidatePages" :key="page">
                                        <button @click="goToCandidatesPage(page)" :class="currentPage === page ? 'bg-blue-600 text-white shadow-md shadow-blue-200/80' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" class="min-w-[2.5rem] rounded-xl px-3 py-2 text-sm font-semibold transition-colors" x-text="page"></button>
                                    </template>
                                </div>
                                <button @click="goToNextCandidatesPage()" :disabled="currentPage >= totalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Next page">
                                    <span class="hidden sm:inline">Next</span>
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                                <button @click="goToLastCandidatesPage()" :disabled="currentPage >= totalPages" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-40" title="Last page">
                                    <i class="fas fa-angles-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
            </section>
                </div> <!-- End Tab Content Area -->
            </div> <!-- End Main Flex Container -->

            <div
                x-show="subjectModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="subjectModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-book text-amber-300"></i>PSLE Subject</span>
                                <h2 class="registration-modal-title" x-text="viewingSubject.name || 'Subject Details'"></h2>
                                <p class="registration-modal-subtitle">Review the official PSLE subject record synchronized from the NECTA catalog.</p>
                            </div>
                            <button @click="subjectModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Code</label>
                                    <input type="text" readonly :value="viewingSubject.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Group</label>
                                    <input type="text" readonly :value="formatPsleCategory(viewingSubject.category)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Subject Name</label>
                                <input type="text" readonly :value="viewingSubject.name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Description</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="viewingSubject.description || '-'"></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Source</label>
                                <input type="text" readonly :value="paperSourceLabel(viewingSubject)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="subjectModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="subjectModalOpen = false; openSubjectPapers(viewingSubject)" class="registration-modal-button registration-modal-button-secondary">Open Paper Governance</button>
                                <button type="button" @click="openOfficialPaperSource(viewingSubject)" class="registration-modal-button registration-modal-button-primary">Open Official Source</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="paperGuidanceModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="paperGuidanceModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-scroll text-amber-300"></i>PSLE Paper Governance</span>
                                <h2 class="registration-modal-title">Official Paper Structure Policy</h2>
                                <p class="registration-modal-subtitle">This workspace governs readiness for PSLE paper definitions without publishing unverified structure data.</p>
                            </div>
                            <button @click="paperGuidanceModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4 text-sm leading-7 text-slate-600">
                            <p>PSLE paper structure must be driven by official NECTA examination format documents. Until those formats are loaded into IRMS, this section remains read-only and governance-focused.</p>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="font-semibold text-slate-900">Required official data before activation</p>
                                <ul class="mt-3 space-y-2">
                                    <li>Subject-specific paper title or format reference</li>
                                    <li>Paper count and sequencing where officially defined</li>
                                    <li>Duration and marks allocation</li>
                                    <li>Source document or official publication reference</li>
                                </ul>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="paperGuidanceModalOpen = false" class="registration-modal-button registration-modal-button-primary">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="paperSubjectModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="paperSubjectModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-book-open text-amber-300"></i>Paper Subject Review</span>
                                <h2 class="registration-modal-title" x-text="viewingPaperSubject.name || 'PSLE Subject'"></h2>
                                <p class="registration-modal-subtitle">Governance review for PSLE paper structure readiness.</p>
                            </div>
                            <button @click="paperSubjectModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Code</label>
                                    <input type="text" readonly :value="viewingPaperSubject.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Subject Group</label>
                                    <input type="text" readonly :value="formatPsleCategory(viewingPaperSubject.category)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Format Status</label>
                                <input type="text" readonly :value="paperStatusLabel(viewingPaperSubject)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Official Source</label>
                                <textarea readonly rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="paperSourceLabel(viewingPaperSubject)"></textarea>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Governance Position</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="paperGovernanceLongNote(viewingPaperSubject)"></textarea>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="paperSubjectModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="openOfficialPaperSource(viewingPaperSubject)" class="registration-modal-button registration-modal-button-secondary">Open Source</button>
                                <button type="button" @click="paperSubjectModalOpen = false; openPaperGuidanceModal()" class="registration-modal-button registration-modal-button-primary">View Governance Notes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="timetableSourceModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="timetableSourceModalOpen = false"
            >
                <div class="registration-modal-shell max-w-4xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-calendar-days text-amber-300"></i>PSLE Timetable Source</span>
                                <h2 class="registration-modal-title">Zonal Timetable Reference</h2>
                                <p class="registration-modal-subtitle">This timetable tab is derived from the zonal PSLE LaTeX source used for the May 2026 mock examination programme.</p>
                            </div>
                            <button @click="timetableSourceModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel space-y-5 p-6">
                            <div class="rounded-2xl border border-slate-700/50 bg-slate-800/30 p-5 text-sm leading-7 text-slate-300">
                                <p class="font-bold text-white">Source heading</p>
                                <p class="mt-2">OFISI YA WAZIRI MKUU, TAWALA ZA MIKOA NA SERIKALI ZA MITAA, KANDA MAALUMU YA KITAALUMA.</p>
                                <p class="mt-2">RATIBA YA MTIHANI WA UTAMILIFU DARASA LA SABA KANDA YA KITAALUMA, MEI 2026.</p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-slate-700/50 bg-slate-800/30 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Regions Covered</p>
                                    <p class="mt-3 text-sm font-semibold leading-7 text-white">TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, LINDI, MTWARA, and TABORA.</p>
                                </div>
                                <div class="rounded-2xl border border-slate-700/50 bg-slate-800/30 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Controlled Columns</p>
                                    <p class="mt-3 text-sm font-semibold leading-7 text-white">Date and day, time, hidden code, and subject are kept exactly in the structure used by the LaTeX timetable.</p>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5 text-sm leading-7 text-emerald-300">
                                <p class="font-bold text-emerald-200">Governance position</p>
                                <p class="mt-2">This workspace mirrors the zonal timetable as an administrative reference. It does not infer extra sessions, durations, or sequence changes beyond the source document.</p>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="timetableSourceModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="printTimetable()" class="registration-modal-button registration-modal-button-primary">Print Timetable</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="toolsModalOpen"
                class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="closeToolsModal()"
                @keydown.escape.window="closeToolsModal()"
                x-transition.opacity
            >
                <div class="w-full max-w-4xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl shadow-slate-900/20" x-transition>
                    <div class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-r from-slate-900 via-blue-900 to-emerald-800 px-6 py-6 text-white">
                        <div class="absolute inset-y-0 right-0 w-56 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.16),_transparent_68%)]"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-white/80">
                                    <i class="fas fa-screwdriver-wrench text-[0.7rem] text-amber-300"></i>
                                    Pupil Tools
                                </span>
                                <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">PSLE pupil import and export workspace</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">
                                    Launch the candidate registration workspace, download a PSLE-ready template, export the current filtered pupil list, or open the pupil form from one controlled panel.
                                </p>
                            </div>
                            <button @click="closeToolsModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-lg text-white/80 transition hover:bg-white/15 hover:text-white" type="button" aria-label="Close tools">&times;</button>
                        </div>
                    </div>
                    <div class="grid gap-4 bg-slate-50 p-6 md:grid-cols-2 xl:grid-cols-4">
                        <button type="button" @click="launchCandidateImportFlow()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-700"><i class="fas fa-file-import text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Import Pupils</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Open the main candidate import workspace to validate and upload PSLE pupil records in bulk.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-700">Open import workspace<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="downloadCandidateTemplate()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><i class="fas fa-download text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">PSLE Template</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Download the official PSLE pupil CSV template with candidate_number, PReM_No, pupil_name, sex, and school_code columns.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700">Download template<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="exportCandidateExcel()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700"><i class="fas fa-file-excel text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Export Current Data</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Export the currently filtered PSLE pupil list for offline review or downstream reporting.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-emerald-700">Export filtered pupils<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                        <button type="button" @click="openPupilRegistrationFromTools()" class="group flex h-full flex-col rounded-3xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-100/70">
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700"><i class="fas fa-user-plus text-lg"></i></span>
                            <h3 class="mt-5 text-base font-bold text-slate-900">Register Pupil</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Open the embedded PSLE pupil registration form directly from this tools panel.</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700">Open pupil form<i class="fas fa-arrow-right text-xs transition group-hover:translate-x-0.5"></i></span>
                        </button>
                    </div>
                    <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                        <p class="leading-6">This modal reuses the registration workspace pattern while keeping PSLE-specific actions inside the current page.</p>
                        <button type="button" @click="closeToolsModal()" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 font-semibold text-slate-700 transition hover:bg-slate-100">Close</button>
                    </div>
                </div>
            </div>

            <div
                x-show="pupilImportModalOpen"
                class="fixed inset-0 z-[9998] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="if (!importProcessing) { pupilImportModalOpen = false; resetPupilImportModal(); }"
            >
                <div class="registration-modal-shell max-w-5xl flex flex-col" x-transition @click.stop>
                    <div class="registration-modal-header flex-shrink-0">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker">
                                    <i class="fas fa-file-import text-amber-300"></i>
                                    PSLE Pupil Import
                                </span>
                                <h2 class="registration-modal-title" x-text="importPhase === 'report' ? 'PSLE Pupil Import Validation Results' : 'Import PSLE Pupils'">Import PSLE Pupils</h2>
                                <p class="registration-modal-subtitle">Validate PSLE pupil files, review duplicates and warnings, and commit only approved records without leaving this workspace.</p>
                            </div>
                            <button
                                @click="if (!importProcessing) { pupilImportModalOpen = false; resetPupilImportModal(); }"
                                class="registration-modal-close"
                                :disabled="importProcessing"
                                type="button"
                            >&times;</button>
                        </div>
                    </div>

                    <div class="registration-modal-body flex-1 space-y-6">
                        <div x-show="importPhase === 'upload'" class="space-y-4">
                            <div class="registration-modal-note max-w-3xl">
                                <div class="registration-modal-note-icon">
                                    <i class="fas fa-circle-info"></i>
                                </div>
                                <div>
                                    <strong>Step 1: Select File</strong>
                                    <p>Required CSV columns: candidate_number, PReM_No, pupil_name, sex, school_code. Region and council are detected automatically from school_code.</p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="downloadCandidateTemplate()"
                                    class="registration-modal-button registration-modal-button-success text-sm"
                                    :disabled="importProcessing"
                                >
                                    <i class="fas fa-download"></i> Download Official CSV Template
                                </button>
                            </div>

                            <div
                                @drop.prevent="handleImportDrop($event)"
                                @dragover.prevent="importDragActive = true"
                                @dragleave.prevent="importDragActive = false"
                                :class="importDragActive ? 'border-blue-500 bg-blue-50 shadow-[0_18px_30px_rgba(59,130,246,0.12)]' : ''"
                                class="registration-dropzone cursor-pointer"
                            >
                                <input
                                    type="file"
                                    id="psle-import-file-input"
                                    @change="handleImportFileSelect($event)"
                                    accept=".xlsx,.xls,.csv"
                                    class="hidden"
                                    :disabled="importProcessing"
                                >
                                <label for="psle-import-file-input" class="cursor-pointer block">
                                    <span class="registration-dropzone-icon">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                    </span>
                                    <p class="text-lg font-semibold text-slate-700">Drop PSLE pupil file here or click to select</p>
                                    <p class="text-sm text-slate-500 mt-2">Example: PS0404006-0001, 20201520092, ASHERI JOSHUA CHAULA, M, PS0404006</p>
                                </label>
                            </div>

                            <div x-show="importFile" class="registration-modal-panel border-blue-200 bg-blue-50/80 p-4">
                                <div class="grid gap-2 md:grid-cols-2 text-sm text-gray-700">
                                    <p><strong>File:</strong> <span x-text="importFile ? importFile.name : ''"></span></p>
                                    <p><strong>Size:</strong> <span x-text="formatFileSize(importFile ? importFile.size : 0)"></span></p>
                                    <p><strong>Format:</strong> <span x-text="importFileType"></span></p>
                                    <p><strong>Exam year:</strong> <span x-text="examYear || 'Active exam year'"></span></p>
                                    <p><strong>Mapping:</strong> school_code will detect school, council, and region</p>
                                </div>
                            </div>

                            <div class="registration-modal-panel p-4">
                                <p class="text-sm font-semibold text-gray-700 mb-3">If pupil already exists:</p>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="onExistsMode" value="skip" class="w-4 h-4 cursor-pointer">
                                        <span class="text-sm text-gray-700">
                                            <strong>Skip existing</strong>
                                            <span class="text-gray-500 block text-xs">Do not overwrite existing pupil records</span>
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="onExistsMode" value="replace" class="w-4 h-4 cursor-pointer">
                                        <span class="text-sm text-gray-700">
                                            <strong>Replace existing</strong>
                                            <span class="text-gray-500 block text-xs">Update existing pupil name, sex, PReM number, and school assignment</span>
                                        </span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="radio" x-model="onExistsMode" value="stop" class="w-4 h-4 cursor-pointer">
                                        <span class="text-sm text-gray-700">
                                            <strong>Stop import if duplicates found</strong>
                                            <span class="text-gray-500 block text-xs">Do not commit anything when existing candidate numbers are detected</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div class="registration-modal-panel p-4 text-sm text-gray-700">
                                <p class="font-semibold">Required CSV columns: candidate_number, PReM_No, pupil_name, sex, school_code</p>
                                <p class="mt-2">Example row: PS0404006-0001, 20201520092, ASHERI JOSHUA CHAULA, M, PS0404006</p>
                                <p class="mt-2">School code must already exist in the PSLE Schools & Centres list. Region and council will be detected automatically.</p>
                            </div>
                        </div>

                        <div x-show="importPhase === 'report'" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-700">Step 3: Preview Records</h3>

                            <div class="registration-modal-stats !grid-cols-2 xl:!grid-cols-3">
                                <div class="registration-modal-stat">
                                    <p class="registration-modal-stat-label">Total Rows</p>
                                    <p class="registration-modal-stat-value text-gray-800 mt-1" x-text="importSummaryValue('total_rows')"></p>
                                </div>
                                <div class="registration-modal-stat border-l-4 border-l-green-500">
                                    <p class="registration-modal-stat-label text-green-600">Valid Rows</p>
                                    <p class="registration-modal-stat-value text-green-800 mt-1" x-text="importSummaryValue('valid_rows')"></p>
                                </div>
                                <div class="registration-modal-stat border-l-4 border-l-red-500">
                                    <p class="registration-modal-stat-label text-red-600">Errors</p>
                                    <p class="registration-modal-stat-value text-red-800 mt-1" x-text="importSummaryValue('invalid_rows')"></p>
                                </div>
                                <div class="registration-modal-stat border-l-4 border-l-rose-500">
                                    <p class="registration-modal-stat-label text-rose-600">Duplicate Conflicts</p>
                                    <p class="registration-modal-stat-value text-rose-800 mt-1" x-text="importSummaryValue('duplicate_conflicts')"></p>
                                </div>
                                <div class="registration-modal-stat border-l-4 border-l-amber-500">
                                    <p class="registration-modal-stat-label text-amber-600">Missing PReM No</p>
                                    <p class="registration-modal-stat-value text-amber-800 mt-1" x-text="importSummaryValue('missing_prem_no')"></p>
                                </div>
                                <div class="registration-modal-stat border-l-4 border-l-blue-500">
                                    <p class="registration-modal-stat-label text-blue-600">Invalid Sex</p>
                                    <p class="registration-modal-stat-value text-blue-800 mt-1" x-text="importSummaryValue('invalid_sex')"></p>
                                </div>
                            </div>

                            <div x-show="importReport.warning_count > 0" class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                <p class="font-semibold">Potential duplicates or PSLE data-quality warnings were detected.</p>
                                <p class="mt-1">Review the flagged rows before commit. Warnings do not block import.</p>
                            </div>

                            <!-- Validation Status Message -->
                            <div class="rounded-2xl border p-4 text-sm" 
                                 :class="importSummaryValue('valid_rows') > 0 ? (importReport.error_count > 0 || importSummaryValue('duplicate_conflicts') > 0 ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-green-200 bg-green-50 text-green-900') : 'border-red-200 bg-red-50 text-red-900'">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5">
                                        <i class="fas text-lg" 
                                           :class="importSummaryValue('valid_rows') > 0 ? (importReport.error_count > 0 || importSummaryValue('duplicate_conflicts') > 0 ? 'fa-exclamation-triangle text-amber-500' : 'fa-circle-check text-green-500') : 'fa-circle-xmark text-red-500'"></i>
                                    </div>
                                    <div class="space-y-1">
                                        <p class="font-bold" x-text="`Validation completed. ${importSummaryValue('duplicate_conflicts')} conflict row${importSummaryValue('duplicate_conflicts') == 1 ? '' : 's'} found. ${importSummaryValue('valid_rows')} valid row${importSummaryValue('valid_rows') == 1 ? ' is' : 's are'} available for import.`"></p>
                                        
                                        <template x-if="importSummaryValue('valid_rows') > 0 && (importReport.error_count > 0 || importSummaryValue('duplicate_conflicts') > 0)">
                                            <p class="text-xs font-semibold">Only valid non-conflicting rows will be imported. Error/conflict rows will be skipped.</p>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="downloadImportReport('errors')" class="registration-modal-button registration-modal-button-secondary text-sm" :disabled="!(importReport.errors && importReport.errors.length)">Download Error Report</button>
                                <button type="button" @click="downloadImportReport('duplicates')" class="registration-modal-button registration-modal-button-secondary text-sm" :disabled="!hasDuplicateRows()">Download Duplicate Report</button>
                                <button type="button" @click="downloadImportReport('summary')" class="registration-modal-button registration-modal-button-secondary text-sm">Download Import Summary</button>
                            </div>

                            <div x-show="importReport.error_count > 0" class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-semibold text-gray-700">Errors Found</h4>
                                    <button
                                        @click="downloadImportErrors()"
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-red-100 text-red-700 rounded text-sm hover:bg-red-200 transition-colors"
                                        :disabled="importReport.error_count === 0"
                                    >
                                        <i class="fas fa-download text-xs"></i> Download Errors
                                    </button>
                                </div>
                            <div class="registration-modal-panel overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Row</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Candidate No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pupil Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Issue Type</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Details</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Recommended Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <template x-for="(error, idx) in importReport.errors.slice(0, 50)" :key="idx">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.row_number"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="error.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="error.full_name || '-'"></td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold"
                                                                  :class="error.is_conflict ? 'bg-rose-100 text-rose-800' : 'bg-red-100 text-red-800'"
                                                                  x-text="error.is_conflict ? 'Duplicate Conflict' : 'Validation Error'"></span>
                                                        </td>
                                                        <td class="px-3 py-2 text-xs">
                                                             <span x-show="!error.is_conflict" class="text-gray-600" x-text="error.primary_error"></span>
                                                             <div x-show="error.is_conflict" class="space-y-1.5 bg-rose-50/50 p-2.5 rounded-lg border border-rose-100/50 text-rose-800">
                                                                 <div class="font-bold flex items-center gap-1">
                                                                     <i class="fas fa-triangle-exclamation text-rose-500"></i>
                                                                     Registration Conflict: <span class="font-mono text-rose-600 bg-rose-100/70 px-1 py-0.5 rounded text-[11px]" x-text="error.conflict_details ? error.conflict_details.registration_number : ''"></span>
                                                                 </div>
                                                                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-[11px] text-rose-900/90 pt-1">
                                                                     <div><strong>Imported Candidate:</strong> <span x-text="error.conflict_details ? error.conflict_details.imported_candidate_id + ' - ' + error.conflict_details.imported_name : ''"></span></div>
                                                                     <div><strong>Existing Owner:</strong> <span x-text="error.conflict_details ? (error.conflict_details.existing_candidate_id || '-') + ' - ' + (error.conflict_details.existing_name || '-') : ''"></span></div>
                                                                     <div></div>
                                                                     <div><strong>Existing Owner School ID:</strong> <span x-text="error.conflict_details ? error.conflict_details.existing_school_id || '-' : ''"></span></div>
                                                                 </div>
                                                             </div>
                                                         </td>
                                                        <td class="px-3 py-2 text-xs text-slate-600 italic" 
                                                            x-text="error.is_conflict ? 'Resolve candidate assignment or skip row' : 'Check field requirements and correct'"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div x-show="importReport.rows && importReport.rows.length > 0" class="space-y-2">
                                <h4 class="font-semibold text-gray-700">Preview Records</h4>
                                <div class="registration-modal-panel overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-100 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Candidate No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">PReM No</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Pupil Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Sex</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">School Code</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">School Name</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Council</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Status</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                <template x-for="(row, idx) in importReport.rows.slice(0, 20)" :key="idx">
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="row.candidate_number || row.candidate_id || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="row.prem_no || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.pupil_name || row.full_name || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.sex || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 font-mono text-xs" x-text="row.school_code || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.school_name || row.school || '-'"></td>
                                                        <td class="px-3 py-2 text-gray-600 text-xs" x-text="row.council || '-'"></td>
                                                        <td class="px-3 py-2">
                                                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold" :class="importStatusClass(row.status, row.message)" x-text="importStatusLabel(row.status, row.message)"></span>
                                                        </td>
                                                        <td class="px-3 py-2 text-xs text-gray-600" x-text="row.message || (row.messages && row.messages.length ? row.messages[0] : '-')"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="importPhase === 'processing'" class="flex flex-col items-center justify-center py-12">
                            <div class="inline-flex h-20 w-20 items-center justify-center rounded-[28px] bg-blue-100 text-blue-700 shadow-inner shadow-blue-200/70 mb-4">
                                <i class="fas fa-spinner animate-spin text-4xl"></i>
                            </div>
                            <p class="text-lg font-semibold text-gray-700" x-text="importProcessingTitle"></p>
                            <p class="text-sm text-gray-500 mt-2" x-text="importProcessingMessage"></p>
                            <div class="registration-modal-panel mt-5 w-full max-w-md p-4 text-left">
                                <template x-for="(state, index) in importProgressStates" :key="state">
                                    <div class="flex items-center gap-3 py-1 text-sm">
                                        <i class="fas" :class="index <= importProgressIndex ? 'fa-check-circle text-green-500' : 'fa-circle text-gray-400'"></i>
                                        <span x-text="state"></span>
                                    </div>
                                </template>
                            </div>
                            <p x-show="importErrorMessage" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700" x-text="importErrorMessage"></p>
                        </div>
                    </div>

                    <div class="registration-modal-actions">
                        <button
                            type="button"
                            @click="pupilImportModalOpen = false; resetPupilImportModal();"
                            class="registration-modal-button registration-modal-button-secondary"
                            :disabled="importProcessing"
                        >Close</button>
                        <button
                            type="button"
                            x-show="importPhase === 'upload'"
                            @click="validateImportFile()"
                            class="registration-modal-button registration-modal-button-primary"
                            :disabled="!importFile || importProcessing"
                        >Validate File</button>
                        <button
                            type="button"
                            x-show="importPhase === 'report'"
                            @click="importPhase = 'upload'"
                            class="registration-modal-button registration-modal-button-secondary"
                            :disabled="importProcessing"
                        >Back</button>
                        <button
                            type="button"
                            x-show="importPhase === 'report'"
                            @click="commitImportFile()"
                            class="registration-modal-button registration-modal-button-primary"
                            :disabled="importSummaryValue('valid_rows') === 0 || importProcessing"
                        >Import Valid Records</button>
                    </div>
                </div>
            </div>

            <div
                x-show="schoolViewModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="schoolViewModalOpen = false"
            >
                <div class="registration-modal-shell max-w-2xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-school text-amber-300"></i>PSLE School</span>
                                <h2 class="registration-modal-title">School Details</h2>
                                <p class="registration-modal-subtitle">Review the synchronized NECTA school record and jump directly into the related pupil register.</p>
                            </div>
                            <button @click="schoolViewModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">School Code</label>
                                    <input type="text" readonly :value="viewingSchool.code || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Ownership</label>
                                    <input type="text" readonly :value="viewingSchool.ownership || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                <input type="text" readonly :value="viewingSchool.name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                    <input type="text" readonly :value="resolveDistrictName(viewingSchool)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                                    <input type="text" readonly :value="resolveRegionName(viewingSchool)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Source</label>
                                <input type="text" readonly :value="viewingSchool.source_system || 'NECTA_PSLE_2025'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="schoolViewModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="openSchoolPupils(viewingSchool); schoolViewModalOpen = false" class="registration-modal-button registration-modal-button-primary">Open Pupils</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateViewModalOpen"
                class="fixed inset-0 z-[9996] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="candidateViewModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-user-graduate text-amber-300"></i>PSLE Pupil</span>
                                <h2 class="registration-modal-title">Pupil Details</h2>
                                <p class="registration-modal-subtitle">Review the pupil record, school assignment, and currently allocated PSLE subject set.</p>
                            </div>
                            <button @click="candidateViewModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <div class="registration-modal-body">
                        <div class="registration-modal-panel p-6 space-y-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                                    <input type="text" readonly :value="viewingCandidate.candidate_id || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">PREM NO</label>
                                    <input type="text" readonly :value="viewingCandidate.prem_no || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-mono text-slate-700">
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sex</label>
                                    <input type="text" readonly :value="viewingCandidate.gender === 'M' ? 'Male' : (viewingCandidate.gender === 'F' ? 'Female' : '-')" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Status</label>
                                    <input type="text" readonly :value="viewingCandidate.status || 'registered'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Pupil Name</label>
                                <input type="text" readonly :value="viewingCandidate.full_name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                    <input type="text" readonly :value="viewingCandidate.school_name || '-'" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                    <input type="text" readonly :value="viewingCandidate.district_name || resolveCandidateDistrict(viewingCandidate)" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Allocated Subjects</label>
                                <textarea readonly rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" x-text="viewingCandidate.allocated_subjects && viewingCandidate.allocated_subjects.length ? viewingCandidate.allocated_subjects.map(subject => subject.code + ' - ' + subject.name).join(', ') : '-'"></textarea>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="candidateViewModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Close</button>
                                <button type="button" @click="candidateViewModalOpen = false; editCandidate(viewingCandidate)" class="registration-modal-button registration-modal-button-primary">Edit Pupil</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-show="candidateModalOpen"
                class="fixed inset-0 z-[9995] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="candidateModalOpen = false"
            >
                <div class="registration-modal-shell max-w-3xl">
                    <div class="registration-modal-header">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-user-graduate text-amber-300"></i>PSLE Pupil</span>
                                <h2 class="registration-modal-title" x-text="editingCandidateId ? 'Edit Pupil' : 'Register New Pupil'"></h2>
                                <p class="registration-modal-subtitle">Capture candidate number, pupil identity, and the primary school assignment used for PSLE administration.</p>
                            </div>
                            <button @click="candidateModalOpen = false" class="registration-modal-close">&times;</button>
                        </div>
                    </div>
                    <form @submit.prevent="saveCandidate()" novalidate class="registration-modal-body">
                        <div class="registration-modal-panel space-y-4 p-6">
                            <!-- General error message if any -->
                            <template x-if="candidateFormErrors.general">
                                <div class="p-3 bg-red-900/30 border border-red-500/30 rounded-xl text-red-200 text-sm font-medium" x-text="candidateFormErrors.general"></div>
                            </template>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                                <input x-model="candidateForm.candidate_id" @input="autoSelectSchool()" type="text" placeholder="e.g., PS0102001-0004" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <p x-show="candidateFormErrors.candidate_id" x-text="candidateFormErrors.candidate_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">PREM NO</label>
                                <input x-model="candidateForm.prem_no" type="text" placeholder="e.g., 20261234567" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-mono focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <p x-show="candidateFormErrors.prem_no" x-text="candidateFormErrors.prem_no" class="text-xs text-red-500 mt-1 font-semibold"></p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Pupil Name</label>
                                <input x-model="candidateForm.full_name" type="text" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                <p x-show="candidateFormErrors.full_name" x-text="candidateFormErrors.full_name" class="text-xs text-red-500 mt-1 font-semibold"></p>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Sex</label>
                                    <select x-model="candidateForm.gender" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <option value="">Select sex</option>
                                        <option value="M">Male</option>
                                        <option value="F">Female</option>
                                    </select>
                                    <p x-show="candidateFormErrors.gender" x-text="candidateFormErrors.gender" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Region</label>
                                    <select x-model="candidateForm.region_id" @change="candidateForm.district_id = ''; candidateForm.school_id = ''; candidateModalSchools = [];" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100">
                                        <option value="">Select region</option>
                                        <template x-for="region in regions" :key="region.id">
                                            <option :value="region.id" x-text="region.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="candidateFormErrors.region_id" x-text="candidateFormErrors.region_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Council</label>
                                    <select x-model="candidateForm.district_id" @change="candidateForm.school_id = ''; loadCandidateModalSchools(candidateForm.district_id);" :disabled="!candidateForm.region_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                        <option value="">Select council</option>
                                        <template x-for="district in modalDistricts" :key="district.id">
                                            <option :value="district.id" x-text="district.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="candidateFormErrors.district_id" x-text="candidateFormErrors.district_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Primary School</label>
                                    <select x-model="candidateForm.school_id" :disabled="!candidateForm.district_id" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                        <option value="">Select primary school</option>
                                        <template x-for="school in candidateModalSchools" :key="school.id">
                                            <option :value="school.id" x-text="school.code + ' - ' + school.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="candidateFormErrors.school_id" x-text="candidateFormErrors.school_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                            </div>
                            <div class="registration-modal-actions">
                                <button type="button" @click="candidateModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                                <button type="submit" class="registration-modal-button registration-modal-button-primary" x-text="editingCandidateId ? 'Update Pupil' : 'Register Pupil'"></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Manually Add Primary School Modal -->
            <div
                x-show="addSchoolModalOpen"
                class="fixed inset-0 z-[9995] flex items-center justify-center bg-slate-950/55 p-4"
                style="display: none;"
                @click.self="addSchoolModalOpen = false"
                @keydown.escape.window="addSchoolModalOpen = false"
                x-transition.opacity
            >
                <div class="registration-modal-shell max-w-2xl bg-[#111827]/95" x-transition @click.stop>
                    <div class="registration-modal-header bg-gradient-to-r from-slate-900 via-slate-850 to-slate-800">
                        <div class="registration-modal-header-content">
                            <div>
                                <span class="registration-modal-kicker"><i class="fas fa-school text-amber-300"></i> PSLE School</span>
                                <h2 class="registration-modal-title" x-text="schoolModalMode === 'edit' ? 'Edit Primary School' : 'Add Primary School'">Add Primary School</h2>
                                <p class="registration-modal-subtitle" x-text="schoolModalMode === 'edit' ? 'Modify the primary school details. Source system details will remain intact.' : 'Manually create a primary school record. The source system will be set to MANUAL to prevent future sync overwrites.'"></p>
                            </div>
                            <button @click="addSchoolModalOpen = false" class="registration-modal-close" type="button">&times;</button>
                        </div>
                    </div>
                    <form @submit.prevent="saveSchool()" class="registration-modal-body" novalidate>
                        <div class="registration-modal-panel space-y-4 p-6">
                            <!-- General error message if any -->
                            <template x-if="schoolFormErrors.general">
                                <div class="p-3 bg-red-900/30 border border-red-500/30 rounded-xl text-red-200 text-sm font-medium" x-text="schoolFormErrors.general"></div>
                            </template>

                            <!-- Safety check warning if school has candidates in edit mode -->
                            <template x-if="schoolModalMode === 'edit' && editingSchoolCandidatesCount > 0">
                                <div class="p-3 bg-amber-950/40 border border-amber-500/30 rounded-xl text-amber-200 text-xs font-medium space-y-1">
                                    <div class="flex items-center gap-1.5 font-semibold text-amber-300">
                                        <i class="fas fa-exclamation-triangle"></i> Restricted Modifications
                                    </div>
                                    <div>This school has <span class="font-bold text-amber-300" x-text="editingSchoolCandidatesCount"></span> active registered candidate(s). Modifying its region or council is restricted to protect academic tracking records.</div>
                                </div>
                            </template>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-400">Exam Year</label>
                                    <input x-model="schoolForm.exam_year" type="text" readonly class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-400 placeholder:text-slate-600 focus:outline-none cursor-not-allowed">
                                    <p x-show="schoolFormErrors.exam_year" x-text="schoolFormErrors.exam_year" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-400">School Code</label>
                                    <input x-model="schoolForm.code" type="text" placeholder="e.g. PS0101001" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-900/30">
                                    <p x-show="schoolFormErrors.code" x-text="schoolFormErrors.code" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-400">School Name</label>
                                <input x-model="schoolForm.name" type="text" placeholder="e.g. Bunge Primary School" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-900/30">
                                <p x-show="schoolFormErrors.name" x-text="schoolFormErrors.name" class="text-xs text-red-500 mt-1 font-semibold"></p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-400">Ownership</label>
                                    <select x-model="schoolForm.ownership" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-900/30">
                                        <option value="GOVERNMENT">Government</option>
                                        <option value="NON-GOVERNMENT">Non-Government</option>
                                    </select>
                                    <p x-show="schoolFormErrors.ownership" x-text="schoolFormErrors.ownership" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-400">Region</label>
                                    <select x-model="schoolForm.region_id" @change="onSchoolRegionChange()" :disabled="schoolModalMode === 'edit' && editingSchoolCandidatesCount > 0" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-900/30 disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed">
                                        <option value="">Select Region</option>
                                        <template x-for="region in regions" :key="'add-school-region-' + region.id">
                                            <option :value="region.id" x-text="region.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="schoolFormErrors.region_id" x-text="schoolFormErrors.region_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-400">Council</label>
                                    <select x-model="schoolForm.district_id" :disabled="!schoolForm.region_id || (schoolModalMode === 'edit' && editingSchoolCandidatesCount > 0)" class="w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-100 focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-900/30 disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed">
                                        <option value="">Select Council</option>
                                        <template x-for="district in filteredSchoolFormDistricts" :key="'add-school-district-' + district.id">
                                            <option :value="district.id" x-text="district.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="schoolFormErrors.district_id" x-text="schoolFormErrors.district_id" class="text-xs text-red-500 mt-1 font-semibold"></p>
                                </div>
                            </div>

                            <div class="registration-modal-actions flex justify-end gap-2 pt-4 border-t border-slate-750">
                                <button type="button" @click="addSchoolModalOpen = false" class="registration-modal-button registration-modal-button-secondary">Cancel</button>
                                <button type="submit" class="registration-modal-button registration-modal-button-primary bg-blue-600 hover:bg-blue-500 text-white font-semibold" x-text="schoolModalMode === 'edit' ? 'Update School' : 'Save School'">Save School</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- End um-content -->

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

</div> <!-- End um-main -->
</div> <!-- End um-body-row -->
</div> <!-- End um-shell -->

<script>
    function psleManager() {
        return {
            activeTab: 'subjects',
            activeTabClass: 'border-[rgba(187,164,94,0.4)] bg-[rgba(187,164,94,0.12)] text-[#fde047] shadow-[0_8px_20px_rgba(0,0,0,0.3)] -translate-y-0.5',
            inactiveTabClass: 'border-transparent bg-transparent text-gray-400 hover:border-gray-700 hover:bg-white/5 hover:text-white hover:shadow-[0_8px_20px_rgba(0,0,0,0.2)]',
            activeIconClass: 'bg-[#bba45e]/20 text-[#fcd116] ring-1 ring-[#bba45e]/30',
            inactiveIconClass: 'bg-white/5 text-gray-400 ring-1 ring-white/5 group-hover:bg-[#bba45e]/10 group-hover:text-[#fde047] group-hover:ring-[#bba45e]/20',
            examTypeCode: 'PSLE',
            examYears: [],
            examYear: '',
            examYearSearch: '',
            subjects: [],
            filteredSubjects: [],
            subjectSearch: '',
            paperSearch: '',
            paperStatusFilter: 'all',
            filteredPaperSubjects: [],
            loadingSubjects: false,
            subjectModalOpen: false,
            regions: [],
            districts: [],
            schools: [],
            filterRegion: '',
            filterDistrict: '',
            filterSchool: '',
            regionOpen: false,
            districtOpen: false,
            schoolOpen: false,
            regionSearch: '',
            districtSearch: '',
            schoolOptionSearch: '',
            schoolSearch: '',
            syncingSchools: false,
            addSchoolModalOpen: false,
            schoolModalMode: 'create',
            editingSchoolId: null,
            editingSchoolCandidatesCount: 0,
            schoolForm: { exam_year: '', code: '', name: '', ownership: 'GOVERNMENT', region_id: '', district_id: '' },
            schoolFormErrors: {},
            toolsModalOpen: false,
            schoolsCurrentPage: 1,
            schoolsPageSize: 100,
            selectedSchoolItems: new Set(),
            candidates: [],
            candidateDiagnostics: null,
            loadingCandidates: false,
            candidateSearch: '',
            currentPage: 1,
            candidatePageSize: 12,
            totalPages: 1,
            totalCandidates: 0,
            selectedCandidateItems: new Set(),
            paperGuidanceModalOpen: false,
            paperSubjectModalOpen: false,
            viewingPaperSubject: {},
            viewingSubject: {},
            timetableSearch: '',
            timetableTypeFilter: 'all',
            selectedTimetableDay: '',
            timetableDayOpen: false,
            timetableSourceModalOpen: false,
            schoolViewModalOpen: false,
            candidateViewModalOpen: false,
            viewingSchool: {},
            viewingCandidate: {},
            candidateModalOpen: false,
            editingCandidateId: null,
            candidateForm: { candidate_id: '', prem_no: '', full_name: '', gender: '', school_id: '', region_id: '', district_id: '' },
            candidateFormErrors: {},
            candidateModalSchools: [],
            pupilImportModalOpen: false,
            importFile: null,
            importPhase: 'upload',
            importProcessing: false,
            importProcessingMessage: '',
            importProcessingTitle: 'Preparing PSLE Import...',
            importProgressIndex: 0,
            importErrorMessage: '',
            importResultMessage: '',
            importDragActive: false,
            onExistsMode: 'skip',
            importProgressStates: [
                'Uploading file...',
                'Reading CSV...',
                'Validating rows...',
                'Checking school codes...',
                'Checking duplicates...',
                'Preparing preview...',
                'Importing approved records...',
                'Import complete.'
            ],
            importReport: {
                errors: [],
                warnings: [],
                total_rows: 0,
                create_count: 0,
                update_count: 0,
                skip_count: 0,
                error_count: 0,
                warning_count: 0,
                can_import: false,
                rows: [],
                summary: {},
                message: '',
                success: false
            },

            // Stats loading variables
            loadingStats: false,
            stats: { subjects: 0, syncedCouncils: 0, syncedSchools: 0, registeredPupils: 0 },
            candidateAbortController: null,

            get filteredDistricts() {
                if (!this.filterRegion) return this.districts;
                return this.districts.filter(district => String(district.region_id) === String(this.filterRegion));
            },

            get modalDistricts() {
                if (!this.candidateForm.region_id) return this.districts;
                return this.districts.filter(district => String(district.region_id) === String(this.candidateForm.region_id));
            },

            get importFileType() {
                if (!this.importFile || !this.importFile.name) return '-';
                const extension = this.importFile.name.split('.').pop().toLowerCase();
                if (extension === 'xlsx' || extension === 'xls') return 'Excel';
                if (extension === 'csv') return 'CSV';
                return extension.toUpperCase();
            },

            get filteredRegionOptions() {
                const query = (this.regionSearch || '').toLowerCase().trim();
                return this.regions.filter(region => !query || (region.name || '').toLowerCase().includes(query));
            },

            get filteredDistrictOptions() {
                const query = (this.districtSearch || '').toLowerCase().trim();
                return this.filteredDistricts.filter(district => !query || (district.name || '').toLowerCase().includes(query));
            },

            get filteredSchools() {
                return this.schools;
            },

            get filteredSchoolOptions() {
                const query = (this.schoolOptionSearch || '').toLowerCase().trim();
                return this.filteredSchools.filter(school => {
                    if (!query) return true;
                    return `${school.code || ''} ${school.name || ''}`.toLowerCase().includes(query);
                });
            },

            get visibleSchools() {
                const query = (this.schoolSearch || '').toLowerCase().trim();
                return this.filteredSchools.filter(school => {
                    if (!query) return true;
                    return `${school.code || ''} ${school.name || ''}`.toLowerCase().includes(query);
                });
            },

            get schoolsTotalPages() {
                return Math.max(Math.ceil(this.visibleSchools.length / this.schoolsPageSize), 1);
            },

            get paginatedVisibleSchools() {
                const start = (this.schoolsCurrentPage - 1) * this.schoolsPageSize;
                return this.visibleSchools.slice(start, start + this.schoolsPageSize);
            },

            get visibleSchoolPages() {
                return this.buildVisiblePages(this.schoolsCurrentPage, this.schoolsTotalPages);
            },

            get syncedCouncilCount() {
                return this.stats.syncedCouncils;
            },

            get syncedSchoolCount() {
                return this.stats.syncedSchools;
            },

            get visibleCandidatePages() {
                return this.buildVisiblePages(this.currentPage, this.totalPages);
            },

            async init() {
                this.syncTabFromUrl();
                window.addEventListener('popstate', () => this.syncTabFromUrl());
                await this.loadExamYears();
                await this.loadRegions();
                await this.loadDistricts();
                if (this.activeTab === 'schools') {
                    await this.loadSchools();
                } else {
                    this.schools = [];
                }
                await this.loadSubjects();
                this.loadDashboardStats();
                await this.loadCandidates();
            },

            syncTabFromUrl() {
                const params = new URLSearchParams(window.location.search);
                let tab = params.get('tab');
                if (tab === 'paper-structure') {
                    tab = 'papers';
                }
                const validTabs = ['subjects', 'papers', 'timetable', 'schools', 'pupils'];
                this.activeTab = validTabs.includes(tab) ? tab : 'subjects';
            },

            setActiveTab(tab) {
                const validTabs = ['subjects', 'papers', 'timetable', 'schools', 'pupils'];
                if (!validTabs.includes(tab)) return;
                this.activeTab = tab;
                const url = new URL(window.location.href);
                const urlTab = tab === 'papers' ? 'paper-structure' : tab;
                url.searchParams.set('tab', urlTab);
                window.history.replaceState({}, '', url.toString());

                if (tab === 'schools') {
                    this.loadSchools();
                } else if (tab === 'pupils') {
                    this.loadCandidates();
                }
            },

            async loadDashboardStats() {
                this.loadingStats = true;
                try {
                    const params = new URLSearchParams();
                    if (this.filterRegion) params.set('region_id', this.filterRegion);
                    if (this.filterDistrict) params.set('district_id', this.filterDistrict);

                    const response = await fetch(`/api/exam-types/psle/summary?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });
                    const data = await response.json();
                    if (response.ok) {
                        this.stats.subjects = data.subjects ?? 0;
                        this.stats.syncedCouncils = data.synced_councils ?? 0;
                        this.stats.syncedSchools = data.synced_primary_schools ?? 0;
                        this.stats.registeredPupils = data.registered_pupils ?? 0;
                    }
                } catch (error) {
                    console.error('Failed to load dashboard stats:', error);
                } finally {
                    this.loadingStats = false;
                }
            },

            async loadExamYears() {
                try {
                    const response = await fetch('/admin/api/exam-years');
                    const data = await response.json();
                    this.examYears = data.exam_years || [];
                    const active = this.examYears.find(year => year.is_active);
                    if (active && !this.examYear) {
                        this.examYear = String(active.year_label);
                    }
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load exam years', 'error');
                }
            },

            async loadRegions() {
                try {
                    const response = await fetch('/admin/api/regions');
                    const data = await response.json();
                    this.regions = data.data || [];
                    this.syncFilterSearchLabels();
                } catch (error) {
                    this.showMessage('Failed to load regions', 'error');
                }
            },

            async loadDistricts() {
                this.districtLoadSeq = (this.districtLoadSeq || 0) + 1;
                const currentSeq = this.districtLoadSeq;
                try {
                    const params = new URLSearchParams();
                    if (this.filterRegion) params.set('region_id', this.filterRegion);
                    const response = await fetch(`/api/exam-types/psle/councils?${params.toString()}`);
                    const data = await response.json();
                    if (currentSeq !== this.districtLoadSeq) return;
                    this.districts = data.data || [];
                    this.syncFilterSearchLabels();
                } catch (error) {
                    if (currentSeq !== this.districtLoadSeq) return;
                    this.showMessage('Failed to load councils', 'error');
                }
            },

            async loadSchools() {
                this.schoolSearchSeq = (this.schoolSearchSeq || 0) + 1;
                const currentSeq = this.schoolSearchSeq;
                try {
                    const params = new URLSearchParams();
                    if (this.filterRegion) params.set('region_id', this.filterRegion);
                    if (this.filterDistrict) params.set('district_id', this.filterDistrict);
                    if (this.schoolSearch) params.set('search', this.schoolSearch);

                    const response = await fetch(`/api/exam-types/psle/schools?${params.toString()}`);
                    const data = await response.json();
                    if (currentSeq !== this.schoolSearchSeq) return;
                    this.schools = data.data || [];
                    this.schoolsCurrentPage = 1;
                    this.selectedSchoolItems.clear();
                    this.syncFilterSearchLabels();
                } catch (error) {
                    if (currentSeq !== this.schoolSearchSeq) return;
                    this.showMessage('Failed to load primary schools', 'error');
                }
            },

            async loadCandidateModalSchools(districtId) {
                if (!districtId) {
                    this.candidateModalSchools = [];
                    return;
                }
                try {
                    const response = await fetch(`/api/exam-types/psle/schools?district_id=${districtId}`);
                    const data = await response.json();
                    this.candidateModalSchools = data.data || [];
                } catch (error) {
                    this.showMessage('Failed to load primary schools for selected council', 'error');
                }
            },

            async syncNectaSchools() {
                this.syncingSchools = true;
                try {
                    const response = await fetch('/api/exam-types/psle/schools/sync-necta-2025', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            region_id: this.filterRegion || null,
                        }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to sync NECTA PSLE schools');
                    }

                    await this.loadDistricts();
                    await this.loadSchools();

                    const summary = data.summary || {};
                    const summaryText = `${summary.regions_processed || 0} region(s), ${summary.districts_synced || 0} council(s), ${summary.schools_synced || 0} school(s) synced`;
                    this.showMessage(summaryText, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to sync NECTA PSLE schools', 'error');
                } finally {
                    this.syncingSchools = false;
                }
            },

            openAddSchoolModal() {
                this.schoolForm = {
                    exam_year: this.examYear || '2026',
                    code: '',
                    name: '',
                    ownership: 'GOVERNMENT',
                    region_id: this.filterRegion || '',
                    district_id: this.filterDistrict || ''
                };
                this.schoolFormErrors = {};
                this.schoolModalMode = 'create';
                this.editingSchoolId = null;
                this.editingSchoolCandidatesCount = 0;
                this.addSchoolModalOpen = true;
            },

            openEditSchoolModal(school) {
                this.schoolForm = {
                    exam_year: this.examYear || '2026',
                    code: school.code || '',
                    name: school.name || '',
                    ownership: school.ownership || 'GOVERNMENT',
                    region_id: school.region_id || '',
                    district_id: school.district_id || ''
                };
                this.schoolFormErrors = {};
                this.schoolModalMode = 'edit';
                this.editingSchoolId = school.id;
                this.editingSchoolCandidatesCount = school.candidates_count || 0;
                this.addSchoolModalOpen = true;
            },

            onSchoolRegionChange() {
                this.schoolForm.district_id = '';
            },

            get filteredSchoolFormDistricts() {
                if (!this.schoolForm.region_id) return [];
                return this.districts.filter(district => String(district.region_id) === String(this.schoolForm.region_id));
            },

            async saveSchool() {
                this.schoolFormErrors = {};

                // Clean and normalize input data
                const code = (this.schoolForm.code || '').trim().toUpperCase();
                const name = (this.schoolForm.name || '').trim().toUpperCase();

                // Front-end validation checks
                if (!this.schoolForm.exam_year) {
                    this.schoolFormErrors.exam_year = 'Exam year is required.';
                }
                if (!code) {
                    this.schoolFormErrors.code = 'School code is required.';
                }
                if (!name) {
                    this.schoolFormErrors.name = 'School name is required.';
                }
                if (!this.schoolForm.ownership) {
                    this.schoolFormErrors.ownership = 'Ownership is required.';
                }
                if (!this.schoolForm.region_id) {
                    this.schoolFormErrors.region_id = 'Region is required.';
                }
                if (!this.schoolForm.district_id) {
                    this.schoolFormErrors.district_id = 'Council is required.';
                }

                if (Object.keys(this.schoolFormErrors).length > 0) {
                    return;
                }

                try {
                    const isEdit = this.schoolModalMode === 'edit';
                    const url = isEdit ? `/api/schools/${this.editingSchoolId}` : '/api/exam-types/psle/schools';
                    const method = isEdit ? 'PUT' : 'POST';

                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            exam_year: this.schoolForm.exam_year,
                            code: code,
                            name: name,
                            ownership: this.schoolForm.ownership,
                            region_id: this.schoolForm.region_id,
                            district_id: this.schoolForm.district_id
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            // Map Laravel validation errors to schoolFormErrors
                            Object.keys(data.errors).forEach(key => {
                                this.schoolFormErrors[key] = data.errors[key][0];
                            });
                        } else {
                            this.schoolFormErrors.general = data.message || 'Failed to save school.';
                        }
                        return;
                    }

                    this.showMessage(isEdit ? 'School updated successfully.' : 'School added successfully.', 'success');
                    this.addSchoolModalOpen = false;
                    
                    // Reload school list and dashboard stats to ensure changes appear immediately
                    await this.loadSchools();
                    await this.loadDashboardStats();
                } catch (error) {
                    this.showMessage('An unexpected error occurred while saving the school.', 'error');
                }
            },

            async loadSubjects() {
                this.loadingSubjects = true;
                try {
                    const response = await fetch('/api/exam-types/PSLE/subjects');
                    const data = await response.json();
                    this.subjects = data.data || [];
                    this.filteredSubjects = this.subjects;
                    this.filteredPaperSubjects = this.subjects;
                } catch (error) {
                    this.showMessage('Failed to load PSLE subjects', 'error');
                } finally {
                    this.loadingSubjects = false;
                }
            },

            filterSubjects() {
                const query = (this.subjectSearch || '').toLowerCase().trim();
                this.filteredSubjects = this.subjects.filter(subject => {
                    if (!query) return true;
                    const code = (subject.code || '').toLowerCase();
                    const displayCode = this.formatPsleSubjectCode(subject.code).toLowerCase();
                    const name = (subject.name || '').toLowerCase();
                    return code.includes(query) || displayCode.includes(query) || name.includes(query);
                });
            },

            formatPsleSubjectCode(code) {
                if (!code) return '-';
                return String(code).replace(/^PSLE-/i, '');
            },

            openSubjectModal(subject) {
                this.viewingSubject = subject || {};
                this.subjectModalOpen = true;
            },

            openSubjectPapers(subject) {
                this.setActiveTab('papers');
                this.paperSearch = `${subject?.code || ''} ${subject?.name || ''}`.trim();
                this.filterPaperSubjects();
                this.openPaperSubjectModal(subject);
            },

            filterPaperSubjects() {
                const query = (this.paperSearch || '').toLowerCase().trim();
                this.filteredPaperSubjects = this.subjects.filter(subject => {
                    if (!query) return true;
                    const code = (subject.code || '').toLowerCase();
                    const displayCode = this.formatPsleSubjectCode(subject.code).toLowerCase();
                    const name = (subject.name || '').toLowerCase();
                    const matchesSearch = code.includes(query) || displayCode.includes(query) || name.includes(query);
                    const status = this.paperStatusKey(subject);
                    const matchesStatus = this.paperStatusFilter === 'all' || this.paperStatusFilter === status;
                    return matchesSearch && matchesStatus;
                });
            },

            formatPsleCategory(category) {
                if (category === 'ARTS') return 'Language and Literacy';
                if (category === 'SCIENCE') return 'Mathematics and Science';
                if (category === 'BUSINESS') return 'Social Studies and General Learning';
                return category || '-';
            },

            paperStatusKey(subject) {
                const code = subject?.code || '';
                return this.pslePaperSourceMap[code] ? 'verified' : 'pending';
            },

            paperStatusLabel(subject) {
                return this.paperStatusKey(subject) === 'verified'
                    ? 'Verified Official Format'
                    : 'Awaiting Official Source';
            },

            get pslePaperSourceMap() {
                return {
                    'PSLE-01': 'PSLE Format Booklet 2024 · Section 01 KISWAHILI',
                    'PSLE-02': 'PSLE Format Booklet 2024 · Section 02 ENGLISH LANGUAGE',
                    'PSLE-03': 'PSLE Format Booklet 2024 · Section 03 SOCIAL STUDIES AND VOCATIONAL SKILLS',
                    'PSLE-04': 'PSLE Format Booklet 2024 · Section 04 MATHEMATICS',
                    'PSLE-05': 'PSLE Format Booklet 2024 · Section 05 SCIENCE AND TECHNOLOGY',
                    'PSLE-06': 'PSLE Format Booklet 2024 · Section 06 CIVIC AND MORAL EDUCATION',
                };
            },

            paperSourceLabel(subject) {
                const code = subject?.code || '';
                return this.pslePaperSourceMap[code] || 'Official source not yet attached';
            },

            paperGovernanceNote(subject) {
                return this.paperStatusKey(subject) === 'verified'
                    ? 'Booklet-linked'
                    : 'Awaiting source mapping';
            },

            paperGovernanceLongNote(subject) {
                if (this.paperStatusKey(subject) === 'verified') {
                    return 'This subject is linked to the official NECTA PSLE 2024 format booklet. Internal extraction of paper count, duration, and marks into structured IRMS fields can proceed without relying on assumptions.';
                }

                return 'Paper count, duration, and weighting remain hidden until official source material is verified and loaded. This avoids introducing assumptions into PSLE administration.';
            },

            get verifiedPaperCount() {
                return this.subjects.filter(subject => this.paperStatusKey(subject) === 'verified').length;
            },

            get pendingPaperCount() {
                return this.subjects.filter(subject => this.paperStatusKey(subject) === 'pending').length;
            },

            get timetableDays() {
                return [
                    { date: '20.05.2026', day: 'JUMATANO', label: '20.05.2026 · JUMATANO' },
                    { date: '21.05.2026', day: 'ALHAMISI', label: '21.05.2026 · ALHAMISI' },
                ];
            },

            get timetableEntries() {
                return [
                    {
                        key: '2026-05-20-01',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '2:00 -- 3:40',
                        code: '01',
                        subject: 'KISWAHILI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Opening language paper in the zonal sequence.',
                    },
                    {
                        key: '2026-05-20-break-1',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '3:40 -- 4:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval from the source timetable.',
                    },
                    {
                        key: '2026-05-20-04',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '4:30 -- 6:30',
                        code: '04',
                        subject: 'HISABATI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Shared sitting window with the English-medium mathematics paper.',
                    },
                    {
                        key: '2026-05-20-04E',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '4:30 -- 6:30',
                        code: '04E',
                        subject: 'MATHEMATICS',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Parallel bilingual paper in the same zonal sitting window.',
                    },
                    {
                        key: '2026-05-20-break-2',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '6:30 -- 8:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval before the final evening sitting.',
                    },
                    {
                        key: '2026-05-20-06',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '8:30 -- 10:00',
                        code: '06',
                        subject: 'URAIA NA MAADILI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Final Kiswahili-medium sitting for day one.',
                    },
                    {
                        key: '2026-05-20-06E',
                        date: '20.05.2026',
                        day: 'JUMATANO',
                        dayLabel: '20.05.2026 · JUMATANO',
                        time: '8:30 -- 10:00',
                        code: '06E',
                        subject: 'CIVIC AND MORAL EDUCATION',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'English-medium parallel paper for the day-one civic session.',
                    },
                    {
                        key: '2026-05-21-02',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '2:00 -- 3:40',
                        code: '02',
                        subject: 'ENGLISH LANGUAGE',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Opening paper for day two.',
                    },
                    {
                        key: '2026-05-21-break-1',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '3:40 -- 4:30',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval from the zonal source timetable.',
                    },
                    {
                        key: '2026-05-21-05',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '4:30 -- 6:00',
                        code: '05',
                        subject: 'SAYANSI NA TEKNOLOJIA',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Shared sitting window with the English-medium science paper.',
                    },
                    {
                        key: '2026-05-21-05E',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '4:30 -- 6:00',
                        code: '05E',
                        subject: 'SCIENCE AND TECHNOLOGY',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Parallel bilingual paper in the same zonal sitting window.',
                    },
                    {
                        key: '2026-05-21-break-2',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '6:00 -- 8:00',
                        code: '',
                        subject: 'MAPUMZIKO',
                        track: 'Administrative Break',
                        type: 'break',
                        note: 'Controlled rest interval before the closing paper window.',
                    },
                    {
                        key: '2026-05-21-03',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '8:00 -- 9:30',
                        code: '03',
                        subject: 'MAARIFA YA JAMII NA STADI ZA KAZI',
                        track: 'Kiswahili Medium',
                        type: 'exam',
                        note: 'Closing Kiswahili-medium sitting in the zonal programme.',
                    },
                    {
                        key: '2026-05-21-03E',
                        date: '21.05.2026',
                        day: 'ALHAMISI',
                        dayLabel: '21.05.2026 · ALHAMISI',
                        time: '8:00 -- 9:30',
                        code: '03E',
                        subject: 'SOCIAL STUDIES AND VOCATIONAL SKILLS',
                        track: 'English Medium',
                        type: 'exam',
                        note: 'Closing English-medium parallel paper for day two.',
                    },
                ];
            },

            get filteredTimetableEntries() {
                const query = (this.timetableSearch || '').toLowerCase().trim();

                return this.timetableEntries.filter(entry => {
                    const matchesDay = !this.selectedTimetableDay || entry.date === this.selectedTimetableDay;
                    const matchesType = this.timetableTypeFilter === 'all' || entry.type === this.timetableTypeFilter;
                    const haystack = [
                        entry.date,
                        entry.day,
                        entry.dayLabel,
                        entry.time,
                        entry.code,
                        entry.subject,
                        entry.track,
                        entry.note,
                    ].join(' ').toLowerCase();
                    const matchesSearch = !query || haystack.includes(query);

                    return matchesDay && matchesType && matchesSearch;
                });
            },

            get timetableExamSlotCount() {
                return this.timetableEntries.filter(entry => entry.type === 'exam').length;
            },

            get timetableBreakCount() {
                return this.timetableEntries.filter(entry => entry.type === 'break').length;
            },

            openPaperGuidanceModal() {
                this.paperGuidanceModalOpen = true;
            },

            openPaperSubjectModal(subject) {
                this.viewingPaperSubject = subject || {};
                this.paperSubjectModalOpen = true;
            },

            openOfficialPaperSource(subject) {
                if (this.paperStatusKey(subject) !== 'verified') {
                    this.showMessage('Official source is not yet attached for this subject.', 'error');
                    return;
                }

                window.open('https://necta.go.tz/webroot/uploads/news/FORMAT_PSLE_2024_ENGLISH.pdf', '_blank', 'noopener');
            },

            filterTimetableEntries() {
                this.timetableDayOpen = false;
            },

            setTimetableDay(date) {
                this.selectedTimetableDay = date;
                this.timetableDayOpen = false;
            },

            openTimetableSourceModal() {
                this.timetableSourceModalOpen = true;
            },

            openTimetablePreview() {
                window.open('/exam-types/psle/timetable/pdf?disposition=inline', '_blank', 'noopener');
            },

            printTimetable() {
                const printWindow = window.open('/exam-types/psle/timetable/pdf?disposition=inline', '_blank');
                if (!printWindow) {
                    this.showMessage('Unable to open PDF print window.', 'error');
                    return;
                }

                const triggerPrint = () => {
                    try {
                        printWindow.focus();
                        printWindow.print();
                    } catch (error) {
                        // Browser PDF viewers vary; opening the inline PDF is the safe fallback.
                    }
                };

                printWindow.onload = triggerPrint;
                setTimeout(triggerPrint, 1800);
            },

            async syncOfficialSubjects() {
                try {
                    const response = await fetch('/api/exam-types/psle/subjects/sync-official', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to synchronize PSLE catalog');
                    }

                    await this.loadSubjects();
                    this.showMessage(data.message || 'Official PSLE subject catalog synchronized.', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to synchronize PSLE catalog', 'error');
                }
            },

            async loadCandidates() {
                this.loadingCandidates = true;
                if (this.candidateAbortController) {
                    this.candidateAbortController.abort();
                }
                this.candidateAbortController = new AbortController();

                try {
                    let url = `/api/exam-types/PSLE/candidates?page=${this.currentPage}&per_page=${this.candidatePageSize}&q=${encodeURIComponent(this.candidateSearch || '')}`;
                    if (this.examYear) url += `&exam_year=${encodeURIComponent(this.examYear)}`;
                    if (this.filterRegion) url += `&region_id=${this.filterRegion}`;
                    if (this.filterDistrict) url += `&district_id=${this.filterDistrict}`;
                    if (this.filterSchool) url += `&school_id=${this.filterSchool}`;

                    const response = await fetch(url, {
                        signal: this.candidateAbortController.signal
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Failed to load pupils');
                    }

                    const meta = data.meta || {};
                    this.candidates = (data.data || []).map(candidate => ({
                        ...candidate,
                        district_name: candidate.district_name || this.resolveCandidateDistrict(candidate),
                        region_name: candidate.region_name || this.resolveCandidateRegion(candidate),
                    }));
                    this.totalCandidates = meta.total || 0;
                    this.totalPages = meta.last_page || 1;
                    this.candidateDiagnostics = data.debug || null;
                    this.selectedCandidateItems.clear();
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    this.candidateDiagnostics = null;
                    this.showMessage(error.message || 'Failed to load pupils', 'error');
                } finally {
                    this.loadingCandidates = false;
                }
            },

            onSearchChange() {
                this.currentPage = 1;
                this.loadCandidates();
            },

            onRegionChange() {
                this.filterDistrict = '';
                this.filterSchool = '';
                this.currentPage = 1;
                this.loadDistricts();
                this.loadSchools();
                this.syncFilterSearchLabels();
                this.loadCandidates();
                this.loadDashboardStats();
            },

            onExamYearChange() {
                this.currentPage = 1;
                this.loadCandidates();
            },

            onDistrictChange() {
                this.filterSchool = '';
                this.currentPage = 1;
                if (this.filterDistrict) {
                    this.loadSchools();
                } else {
                    this.schools = [];
                }
                this.syncFilterSearchLabels();
                this.loadCandidates();
                this.loadDashboardStats();
            },

            formatSchoolOptionLabel(school) {
                if (!school) return '';
                return `${school.code ? school.code + ' - ' : ''}${school.name || ''}`.trim();
            },

            syncFilterSearchLabels() {
                this.examYearSearch = this.examYear || '';

                this.regionSearch = this.filterRegion
                    ? (this.regions.find(region => String(region.id) === String(this.filterRegion))?.name || '')
                    : '';

                this.districtSearch = this.filterDistrict
                    ? (this.filteredDistricts.find(district => String(district.id) === String(this.filterDistrict))?.name || '')
                    : '';

                this.schoolOptionSearch = this.filterSchool
                    ? this.formatSchoolOptionLabel(this.filteredSchools.find(school => String(school.id) === String(this.filterSchool)))
                    : '';
            },

            syncExamYearSelection() {
                const value = (this.examYearSearch || '').trim();

                if (!value) {
                    if (!this.examYear) return;
                    this.examYear = '';
                    this.onExamYearChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.examYears.find(year => String(year.year_label).toLowerCase() === value.toLowerCase());
                if (!match || String(this.examYear) === String(match.year_label)) return;

                this.examYear = String(match.year_label);
                this.onExamYearChange();
                this.syncFilterSearchLabels();
            },

            syncRegionSelection() {
                const value = (this.regionSearch || '').trim();

                if (!value) {
                    if (!this.filterRegion) return;
                    this.filterRegion = '';
                    this.onRegionChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.regions.find(region => (region.name || '').toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterRegion) === String(match.id)) return;

                this.filterRegion = match.id;
                this.onRegionChange();
                this.syncFilterSearchLabels();
            },

            syncDistrictSelection() {
                const value = (this.districtSearch || '').trim();

                if (!value) {
                    if (!this.filterDistrict) return;
                    this.filterDistrict = '';
                    this.onDistrictChange();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.filteredDistricts.find(district => (district.name || '').toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterDistrict) === String(match.id)) return;

                this.filterDistrict = match.id;
                this.onDistrictChange();
                this.syncFilterSearchLabels();
            },

            syncSchoolSelection() {
                const value = (this.schoolOptionSearch || '').trim();

                if (!value) {
                    if (!this.filterSchool) return;
                    this.filterSchool = '';
                    this.currentPage = 1;
                    this.loadCandidates();
                    this.syncFilterSearchLabels();
                    return;
                }

                const match = this.filteredSchools.find(school => this.formatSchoolOptionLabel(school).toLowerCase() === value.toLowerCase());
                if (!match || String(this.filterSchool) === String(match.id)) return;

                this.filterSchool = match.id;
                this.currentPage = 1;
                this.loadCandidates();
                this.syncFilterSearchLabels();
            },

            resetFilters() {
                const active = this.examYears.find(year => year.is_active);
                if (active) {
                    this.examYear = String(active.year_label);
                }
                this.filterRegion = '';
                this.filterDistrict = '';
                this.filterSchool = '';
                this.regionOpen = false;
                this.districtOpen = false;
                this.schoolOpen = false;
                this.regionSearch = '';
                this.districtSearch = '';
                this.schoolOptionSearch = '';
                this.schoolSearch = '';
                this.candidateSearch = '';
                this.schoolsCurrentPage = 1;
                this.currentPage = 1;
                
                if (this.activeTab === 'schools') {
                    this.loadSchools();
                } else {
                    this.schools = [];
                }
                this.syncFilterSearchLabels();
                this.loadCandidates();
                this.loadDashboardStats();
            },

            selectRegion(regionId) {
                this.filterRegion = regionId;
                this.regionOpen = false;
                this.syncFilterSearchLabels();
                this.onRegionChange();
            },

            selectDistrict(districtId) {
                this.filterDistrict = districtId;
                this.districtOpen = false;
                this.syncFilterSearchLabels();
                this.onDistrictChange();
            },

            selectSchool(schoolId) {
                this.filterSchool = schoolId;
                this.schoolOpen = false;
                this.syncFilterSearchLabels();
                this.currentPage = 1;
                this.loadCandidates();
            },

            buildVisiblePages(currentPage, totalPages) {
                const total = Math.max(totalPages || 1, 1);
                const start = Math.max(1, currentPage - 2);
                const end = Math.min(total, start + 4);
                const adjustedStart = Math.max(1, end - 4);
                return Array.from({ length: end - adjustedStart + 1 }, (_, index) => adjustedStart + index);
            },

            goToSchoolsPage(page) {
                this.schoolsCurrentPage = page;
            },

            goToFirstSchoolsPage() {
                this.schoolsCurrentPage = 1;
            },

            goToPreviousSchoolsPage() {
                if (this.schoolsCurrentPage > 1) this.schoolsCurrentPage--;
            },

            goToNextSchoolsPage() {
                if (this.schoolsCurrentPage < this.schoolsTotalPages) this.schoolsCurrentPage++;
            },

            goToLastSchoolsPage() {
                this.schoolsCurrentPage = this.schoolsTotalPages;
            },

            goToCandidatesPage(page) {
                this.currentPage = page;
                this.loadCandidates();
            },

            goToFirstCandidatesPage() {
                if (this.currentPage <= 1) return;
                this.currentPage = 1;
                this.loadCandidates();
            },

            goToPreviousCandidatesPage() {
                if (this.currentPage <= 1) return;
                this.currentPage--;
                this.loadCandidates();
            },

            goToNextCandidatesPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage++;
                this.loadCandidates();
            },

            goToLastCandidatesPage() {
                if (this.currentPage >= this.totalPages) return;
                this.currentPage = this.totalPages;
                this.loadCandidates();
            },

            async deleteSchool(id) {
                if (!confirm('Delete this school record?')) return;

                try {
                    const response = await fetch(`/api/schools/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });

                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete school');
                    }

                    this.selectedSchoolItems.delete(id);
                    await this.loadSchools();
                    await this.loadDashboardStats();
                    this.showMessage('School deleted successfully', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete school', 'error');
                }
            },

            async bulkDeleteSchools() {
                if (this.selectedSchoolItems.size === 0) return;
                const count = this.selectedSchoolItems.size;
                if (!confirm(`Delete ${count} selected school(s)?`)) return;

                try {
                    const response = await fetch('/api/schools/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids: Array.from(this.selectedSchoolItems) }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete selected schools');
                    }

                    this.selectedSchoolItems.clear();
                    await this.loadSchools();
                    await this.loadDashboardStats();
                    this.showMessage(`${data.deleted || count} school(s) deleted successfully`, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete selected schools', 'error');
                }
            },

            toggleSchoolSelection(id) {
                if (this.selectedSchoolItems.has(id)) {
                    this.selectedSchoolItems.delete(id);
                    return;
                }
                this.selectedSchoolItems.add(id);
            },

            toggleSelectAllSchools() {
                const visibleIds = this.paginatedVisibleSchools.map(school => school.id);
                const allSelected = visibleIds.length > 0 && visibleIds.every(id => this.selectedSchoolItems.has(id));

                if (allSelected) {
                    visibleIds.forEach(id => this.selectedSchoolItems.delete(id));
                    return;
                }

                visibleIds.forEach(id => this.selectedSchoolItems.add(id));
            },

            toggleCandidateSelection(id) {
                if (this.selectedCandidateItems.has(id)) {
                    this.selectedCandidateItems.delete(id);
                    return;
                }
                this.selectedCandidateItems.add(id);
            },

            toggleSelectAllCandidates() {
                const visibleIds = this.candidates.map(candidate => candidate.id);
                const allSelected = visibleIds.length > 0 && visibleIds.every(id => this.selectedCandidateItems.has(id));

                if (allSelected) {
                    visibleIds.forEach(id => this.selectedCandidateItems.delete(id));
                    return;
                }

                visibleIds.forEach(id => this.selectedCandidateItems.add(id));
            },

            async bulkDeleteCandidates() {
                if (this.selectedCandidateItems.size === 0) return;
                const count = this.selectedCandidateItems.size;
                if (!confirm(`Delete ${count} selected pupil record(s)?`)) return;

                try {
                    const response = await fetch('/api/candidates/bulk-delete', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ ids: Array.from(this.selectedCandidateItems) }),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to delete selected pupils');
                    }

                    this.selectedCandidateItems.clear();
                    await this.loadCandidates();
                    this.showMessage(`${data.deleted || count} pupil(s) deleted successfully`, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete selected pupils', 'error');
                }
            },

            openCandidateModal() {
                this.editingCandidateId = null;
                this.candidateFormErrors = {};
                this.candidateForm = {
                    candidate_id: '',
                    prem_no: '',
                    full_name: '',
                    gender: '',
                    region_id: this.filterRegion || '',
                    district_id: this.filterDistrict || '',
                    school_id: this.filterSchool || '',
                };
                
                this.candidateModalSchools = [];
                if (this.candidateForm.district_id) {
                    this.loadCandidateModalSchools(this.candidateForm.district_id);
                }
                
                this.candidateModalOpen = true;
            },

            openToolsModal() {
                this.toolsModalOpen = true;
            },

            closeToolsModal() {
                this.toolsModalOpen = false;
            },

            launchCandidateImportFlow() {
                this.closeToolsModal();
                this.resetPupilImportModal();
                this.pupilImportModalOpen = true;
            },

            downloadCandidateTemplate() {
                this.closeToolsModal();
                window.location.href = '/api/candidates/import/template?exam_type=PSLE';
            },

            exportCandidateExcel() {
                this.closeToolsModal();
                this.exportCandidatesCSV();
            },

            openPupilRegistrationFromTools() {
                this.closeToolsModal();
                this.openCandidateModal();
            },

            resetPupilImportModal() {
                this.importFile = null;
                this.importPhase = 'upload';
                this.importProcessing = false;
                this.importProcessingMessage = '';
                this.importProcessingTitle = 'Preparing PSLE Import...';
                this.importProgressIndex = 0;
                this.importErrorMessage = '';
                this.importResultMessage = '';
                this.importDragActive = false;
                this.onExistsMode = 'skip';
                this.importReport = {
                    errors: [],
                    warnings: [],
                    total_rows: 0,
                    create_count: 0,
                    update_count: 0,
                    skip_count: 0,
                    error_count: 0,
                    warning_count: 0,
                    can_import: false,
                    rows: [],
                    summary: {},
                    message: '',
                    success: false
                };
            },

            handleImportFileSelect(event) {
                const files = event.target.files || [];
                if (files.length > 0) {
                    this.importFile = files[0];
                }
            },

            handleImportDrop(event) {
                this.importDragActive = false;
                const files = event.dataTransfer.files || [];
                if (files.length > 0) {
                    this.importFile = files[0];
                }
            },

            formatFileSize(bytes) {
                if (!bytes) return '-';
                if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
                return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
            },

            selectedCouncilLabel() {
                const district = this.districts.find(item => String(item.id) === String(this.filterDistrict));
                return district ? district.name : 'All councils';
            },

            selectedSchoolLabel() {
                const school = this.schools.find(item => String(item.id) === String(this.filterSchool));
                return school ? `${school.code || school.registration_number || ''} ${school.name || ''}`.trim() : 'All schools/centres';
            },

            importSummaryValue(key) {
                return (this.importReport.summary && this.importReport.summary[key] !== undefined)
                    ? this.importReport.summary[key]
                    : 0;
            },

            hasDuplicateRows() {
                return (this.importReport.rows || []).some(row => ['SKIP', 'REPLACE', 'CONFLICT'].includes(row.status) || /duplicate|exists/i.test(row.message || ''));
            },

            importStatusLabel(status, message = '') {
                const lower = String(message || '').toLowerCase();
                if (lower.includes('duplicated within this file')) return 'Duplicate in File';
                if (lower.includes('unknown school code')) return 'Unknown School Code';
                if (lower.includes('candidate_id is required')) return 'Missing Candidate Number';
                if (lower.includes('prem_no is required')) return 'Missing PReM No';
                if (lower.includes('full_name is required')) return 'Missing Pupil Name';
                if (lower.includes('gender')) return 'Invalid Sex';
                return {
                    NEW: 'Ready',
                    WARNING: 'Ready',
                    SKIP: 'Already Exists',
                    REPLACE: 'Already Exists',
                    ERROR: 'Invalid',
                    CONFLICT: 'Duplicate Conflict',
                }[status] || status || 'Invalid';
            },

            importStatusClass(status, message = '') {
                const lower = String(message || '').toLowerCase();
                if (lower.includes('duplicated within this file') || lower.includes('unknown school code') || lower.includes('required') || lower.includes('invalid')) {
                    return 'bg-red-100 text-red-800';
                }
                return {
                    NEW: 'bg-blue-100 text-blue-800',
                    WARNING: 'bg-amber-100 text-amber-800',
                    SKIP: 'bg-slate-100 text-slate-800',
                    REPLACE: 'bg-purple-100 text-purple-800',
                    ERROR: 'bg-red-100 text-red-800',
                    CONFLICT: 'bg-rose-100 text-rose-800',
                }[status] || 'bg-red-100 text-red-800';
            },

            downloadImportReport(type) {
                const rows = type === 'summary'
                    ? Object.entries(this.importReport.summary || {}).map(([metric, value]) => ({ metric, value }))
                    : type === 'duplicates'
                        ? (this.importReport.rows || []).filter(row => ['SKIP', 'REPLACE'].includes(row.status) || /duplicate|exists/i.test(row.message || ''))
                        : (this.importReport.errors || []);

                if (!rows.length && type !== 'summary') return;

                const columns = Array.from(rows.reduce((set, row) => {
                    Object.keys(row || {}).forEach(key => set.add(key));
                    return set;
                }, new Set(['metric', 'value'])));
                const csvRows = [columns.join(',')].concat(rows.map(row => columns.map(column => `"${String(row[column] ?? '').replace(/"/g, '""')}"`).join(',')));
                const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `psle_pupil_import_${type}_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            async readJsonResponse(response) {
                const text = await response.text();
                try {
                    return text ? JSON.parse(text) : {};
                } catch (error) {
                    throw new Error(response.ok ? 'Server returned an unreadable response.' : 'Server returned HTML instead of JSON. Please check the Laravel log.');
                }
            },

            fetchWithTimeout(url, options = {}, timeoutMs = 120000) {
                const controller = new AbortController();
                const timeout = setTimeout(() => controller.abort(), timeoutMs);
                return fetch(url, { ...options, signal: controller.signal }).finally(() => clearTimeout(timeout));
            },

            normalizePupilImportReport(report = {}) {
                const rows = report.rows || [];
                const summary = report.summary || {};
                const validRows = summary.valid_rows ?? rows.filter(row => row.valid || ['NEW', 'WARNING', 'REPLACE'].includes(row.status)).length;
                const invalidRows = summary.invalid_rows ?? rows.filter(row => row.status === 'ERROR' || row.valid === false).length;
                const skipRows = summary.rows_to_skip ?? rows.filter(row => row.status === 'SKIP' || row.action === 'skip').length;
                const updateRows = summary.already_existing ?? rows.filter(row => row.status === 'REPLACE' || row.action === 'replace').length;
                const createRows = Math.max(validRows - skipRows - updateRows, 0);
                const errors = report.errors || rows
                    .filter(row => row.status === 'ERROR' || row.valid === false || row.status === 'CONFLICT')
                    .map(row => ({
                        row_number: row.row_number,
                        candidate_id: row.candidate_id || row.candidate_number || '',
                        prem_no: row.prem_no || '',
                        full_name: row.full_name || row.pupil_name || '',
                        gender: row.gender || row.sex || '',
                        school_code: row.school_code || '',
                        error_messages: row.messages || [row.message || 'Invalid row'],
                        primary_error: row.message || 'Invalid row',
                        is_conflict: row.is_conflict || false,
                        conflict_details: row.conflict_details || null,
                    }));

                return {
                    ...report,
                    rows,
                    summary,
                    errors,
                    total_rows: summary.total_rows ?? report.total_rows ?? rows.length,
                    create_count: report.create_count ?? createRows,
                    update_count: report.update_count ?? updateRows,
                    skip_count: report.skip_count ?? skipRows,
                    error_count: report.error_count ?? invalidRows,
                    warning_count: report.warning_count ?? rows.filter(row => row.status === 'WARNING').length,
                    can_import: report.can_import ?? validRows > skipRows,
                    success: report.success ?? invalidRows === 0,
                };
            },

            async validateImportFile() {
                if (!this.importFile) {
                    this.showMessage('Please select a PSLE pupil import file.', 'error');
                    return;
                }

                this.importProcessing = true;
                this.importPhase = 'processing';
                this.importProcessingTitle = 'Validating PSLE Import...';
                this.importProcessingMessage = 'Uploading file...';
                this.importProgressIndex = 0;
                this.importErrorMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_type', 'PSLE');
                    if (this.examYear) formData.append('exam_year', this.examYear);
                    if (this.filterSchool) formData.append('school_id', this.filterSchool);
                    formData.append('on_exists_mode', this.onExistsMode);

                    this.importProcessingMessage = 'Reading CSV and validating rows...';
                    this.importProgressIndex = 2;

                    const response = await this.fetchWithTimeout('/mark-entry/psle/candidates/import/validate', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    });

                    this.importProcessingMessage = 'Checking school codes, checking duplicates, and preparing preview...';
                    this.importProgressIndex = 4;
                    const responseData = await this.readJsonResponse(response);
                    if (!response.ok) {
                        throw new Error(responseData.message || 'Validation request failed');
                    }
                    const data = responseData.data || responseData;
                    this.importReport = this.normalizePupilImportReport(data);
                    this.importPhase = 'report';
                    this.importProcessing = false;

                    // Validation complete. Results are displayed inside the validation report modal phase. No toasts or browser alerts needed.
                } catch (error) {
                    this.importProcessing = false;
                    this.importErrorMessage = error.name === 'AbortError' ? 'Validation timed out. Please try again or use a smaller file.' : error.message;
                    this.importPhase = 'upload';
                    this.showMessage('Error validating PSLE pupil file: ' + this.importErrorMessage, 'error');
                }
            },

            async commitImportFile() {
                if (!this.importFile || !this.importReport.can_import) {
                    this.showMessage('Cannot proceed: invalid file or no valid PSLE pupil records.', 'error');
                    return;
                }

                this.importProcessing = true;
                this.importPhase = 'processing';
                this.importProcessingTitle = 'Committing PSLE Import...';
                this.importProcessingMessage = 'Importing approved records...';
                this.importProgressIndex = 5;
                this.importErrorMessage = '';

                try {
                    const formData = new FormData();
                    formData.append('file', this.importFile);
                    formData.append('exam_type', 'PSLE');
                    if (this.examYear) formData.append('exam_year', this.examYear);
                    if (this.filterSchool) formData.append('school_id', this.filterSchool);
                    formData.append('on_exists_mode', this.onExistsMode);

                    const response = await this.fetchWithTimeout('/mark-entry/psle/candidates/import/commit', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: formData,
                    }, 300000);

                    const data = await this.readJsonResponse(response);
                    this.importProcessing = false;

                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Failed to import PSLE pupil records');
                    }

                    await this.loadCandidates();
                    await this.loadDashboardStats();
                    this.activeTab = 'pupils';
                    this.pupilImportModalOpen = false;
                    const imported = (data.imported_count || 0) + (data.updated_count || 0) + (data.summary?.inserted || 0) + (data.summary?.updated || 0);
                    const skipped = this.importReport.error_count || 0;
                    this.importResultMessage = data.message || `${imported} valid records imported. ${skipped} invalid rows skipped.`;
                    this.showMessage(this.importResultMessage, 'success');
                    this.resetPupilImportModal();
                } catch (error) {
                    this.importProcessing = false;
                    this.importErrorMessage = error.name === 'AbortError' ? 'Import timed out. Please check whether records were imported before retrying.' : error.message;
                    this.importPhase = 'report';
                    this.showMessage(this.importErrorMessage || 'Failed to import PSLE pupil records', 'error');
                }
            },

            async downloadImportErrors() {
                if (!this.importReport.errors || this.importReport.errors.length === 0) {
                    return;
                }

                try {
                    const response = await fetch('/api/candidates/import/download-errors', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ errors: this.importReport.errors }),
                    });

                    if (!response.ok) {
                        throw new Error('Failed to download error report');
                    }

                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `psle_pupil_import_errors_${Date.now()}.csv`;
                    document.body.appendChild(link);
                    link.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(link);
                } catch (error) {
                    this.showMessage(error.message || 'Failed to download import errors', 'error');
                }
            },

            viewSchool(school) {
                this.viewingSchool = school || {};
                this.schoolViewModalOpen = true;
            },

            openSchoolPupils(school) {
                if (!school) return;
                this.activeTab = 'pupils';
                this.filterRegion = this.resolveRegionId(school) || '';
                this.filterDistrict = school.district_id || '';
                this.filterSchool = school.id || '';
                this.currentPage = 1;
                this.schoolViewModalOpen = false;
                this.loadCandidates();
            },

            viewCandidate(candidate) {
                this.viewingCandidate = candidate || {};
                this.candidateViewModalOpen = true;
            },

            editCandidate(candidate) {
                this.editingCandidateId = candidate.id;
                this.candidateFormErrors = {};
                this.candidateForm = {
                    candidate_id: candidate.candidate_id || '',
                    prem_no: candidate.prem_no || '',
                    full_name: candidate.full_name || '',
                    gender: candidate.gender || '',
                    region_id: candidate.region_id || '',
                    district_id: candidate.district_id || '',
                    school_id: candidate.school_id || '',
                };
                
                this.candidateModalSchools = [];
                if (this.candidateForm.district_id) {
                    this.loadCandidateModalSchools(this.candidateForm.district_id);
                }
                
                this.candidateViewModalOpen = false;
                this.candidateModalOpen = true;
            },

            async saveCandidate() {
                this.candidateFormErrors = {};
                if (!this.candidateForm.candidate_id) {
                    this.candidateFormErrors.candidate_id = 'Candidate Number is required.';
                }
                if (!this.candidateForm.full_name) {
                    this.candidateFormErrors.full_name = 'Pupil Name is required.';
                }
                if (!this.candidateForm.gender) {
                    this.candidateFormErrors.gender = 'Sex is required.';
                }
                if (!this.candidateForm.region_id) {
                    this.candidateFormErrors.region_id = 'Region is required.';
                }
                if (!this.candidateForm.district_id) {
                    this.candidateFormErrors.district_id = 'Council is required.';
                }
                if (!this.candidateForm.school_id) {
                    this.candidateFormErrors.school_id = 'Primary School is required.';
                }
                if (Object.keys(this.candidateFormErrors).length > 0) {
                    return;
                }

                try {
                    const payload = {
                        candidate_id: this.candidateForm.candidate_id,
                        prem_no: this.candidateForm.prem_no,
                        full_name: this.candidateForm.full_name,
                        gender: this.candidateForm.gender,
                        school_id: this.candidateForm.school_id,
                        exam_type: 'PSLE',
                        combination: null,
                    };

                    const url = this.editingCandidateId ? `/api/candidates/${this.editingCandidateId}` : '/api/candidates';
                    const method = this.editingCandidateId ? 'PUT' : 'POST';

                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            Object.keys(data.errors).forEach(key => {
                                this.candidateFormErrors[key] = data.errors[key][0];
                            });
                            if (data.message) {
                                this.candidateFormErrors.general = data.message;
                            }
                            return;
                        }
                        throw new Error(data.message || 'Failed to save pupil');
                    }

                    this.candidateModalOpen = false;
                    await this.loadDashboardStats();
                    await this.loadCandidates();
                    
                    const matchesFilter = !this.filterSchool || String(this.filterSchool) === String(payload.school_id);
                    const successMsg = matchesFilter ? (this.editingCandidateId ? 'Pupil updated successfully' : 'Pupil registered successfully') : 'Pupil registered successfully. Adjust filters to view the new record.';
                    this.showMessage(successMsg, 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to save pupil', 'error');
                }
            },

            async deleteCandidate(id) {
                if (!confirm('Delete this pupil record?')) return;
                try {
                    const response = await fetch(`/api/candidates/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    if (!response.ok) {
                        throw new Error('Failed to delete pupil');
                    }
                    await this.loadCandidates();
                    this.showMessage('Pupil deleted', 'success');
                } catch (error) {
                    this.showMessage(error.message || 'Failed to delete pupil', 'error');
                }
            },

            autoSelectSchool() {
                const value = (this.candidateForm.candidate_id || '').trim().toUpperCase();
                const dashIndex = value.indexOf('-');
                if (dashIndex <= 0) return;
                const schoolCode = value.slice(0, dashIndex);
                const school = this.schools.find(item => (item.code || '').toUpperCase() === schoolCode);
                if (school) {
                    this.candidateForm.school_id = school.id;
                }
            },

            resolveRegionId(school) {
                if (school.region_id) return school.region_id;
                const district = this.districts.find(item => String(item.id) === String(school.district_id));
                return district ? district.region_id : '';
            },

            resolveDistrictName(school) {
                if (school.district_name) return school.district_name;
                const district = this.districts.find(item => String(item.id) === String(school.district_id));
                return district ? district.name : '-';
            },

            resolveRegionName(school) {
                if (school.region_name) return school.region_name;
                const region = this.regions.find(item => String(item.id) === String(this.resolveRegionId(school)));
                return region ? region.name : '-';
            },

            resolveCandidateDistrict(candidate) {
                const school = this.schools.find(item => String(item.id) === String(candidate.school_id));
                return school ? this.resolveDistrictName(school) : '-';
            },

            resolveCandidateRegion(candidate) {
                const school = this.schools.find(item => String(item.id) === String(candidate.school_id));
                return school ? this.resolveRegionName(school) : '-';
            },

            downloadSubjectsTemplate() {
                const headers = ['Code', 'Name', 'Category', 'Written Papers'].join(',');
                const blob = new Blob([headers], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `PSLE_subjects_template_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            exportCandidatesCSV() {
                const headers = ['Candidate Number', 'PReM No', 'Pupil Name', 'Sex', 'Primary School', 'Council', 'Region', 'Status'].join(',');
                const rows = this.candidates.map(candidate => ([
                    candidate.candidate_id || '',
                    candidate.prem_no || '',
                    candidate.full_name || '',
                    candidate.gender || '',
                    candidate.school_name || '',
                    candidate.district_name || this.resolveCandidateDistrict(candidate),
                    candidate.region_name || this.resolveCandidateRegion(candidate),
                    candidate.status || 'registered',
                ]).map(value => `"${String(value).replace(/"/g, '""')}"`).join(','));
                const blob = new Blob([[headers, ...rows].join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `PSLE_pupils_${Date.now()}.csv`;
                document.body.appendChild(link);
                link.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            },

            showMessage(message, type) {
                const alertDiv = document.createElement('div');
                let bgClass = 'bg-slate-100 text-slate-700 border-slate-300';
                if (type === 'success') bgClass = 'bg-green-100 text-green-700 border-green-300';
                if (type === 'error') bgClass = 'bg-red-100 text-red-700 border-red-300';
                alertDiv.className = `fixed top-24 right-8 ${bgClass} max-w-sm rounded-xl border p-4 shadow-lg z-[10000]`;
                alertDiv.textContent = message;
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 4000);
            },
        };
    }
</script>

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
