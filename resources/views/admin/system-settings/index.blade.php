<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRMS - System Settings</title>
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
.um-shell{display:flex;flex-direction:column;min-height:100vh;background:#0f1117;font-family:'Maiandra GD',sans-serif;width:100%;max-width:100%;}
.um-body-row{display:flex;flex:1;width:100%;max-width:100%;background:linear-gradient(180deg,#0d1b2a,#11202e);}
.um-sidebar{width:260px;display:flex;flex-direction:column;position:sticky;top:0;max-height:100vh;overflow-y:auto;flex-shrink:0;}
.um-profile{padding:28px 20px 22px;border-bottom:1px solid rgba(187,164,94,.15);background:linear-gradient(135deg,rgba(187,164,94,.08),transparent);}
.um-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#BBA45E,#8a7340);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#0d1b2a;margin-bottom:12px;box-shadow:0 0 0 3px rgba(187,164,94,.25);}
.um-name{font-size:.97rem;font-weight:700;color:#f0e6c8;}
.um-role-badge{display:inline-flex;align-items:center;gap:5px;margin-top:6px;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;background:rgba(187,164,94,.15);color:#BBA45E;padding:3px 10px;border-radius:20px;border:1px solid rgba(187,164,94,.3);}
.um-nav{padding:14px 12px;flex:1;}
.um-nav-label{font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(187,164,94,.55);padding:6px 8px 4px;margin-top:10px;}
.um-nav a, .um-nav button{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;color:rgba(255,255,255,.65);font-size:.875rem;font-weight:500;text-decoration:none;transition:all .18s;margin-bottom:2px; width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; text-align: left;}
.um-nav a:hover, .um-nav button:hover, .um-nav a.active, .um-nav button.active{background:rgba(187,164,94,.12);color:#f0e6c8;}
.um-nav a.active, .um-nav button.active{background:rgba(187,164,94,.18);}
.nav-ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;background:rgba(255,255,255,.06);flex-shrink:0;}
.um-nav a:hover .nav-ico, .um-nav button:hover .nav-ico, .um-nav a.active .nav-ico, .um-nav button.active .nav-ico{background:rgba(187,164,94,.2);color:#BBA45E;}
.nav-caret { margin-left: auto; font-size: 0.75rem; transition: transform 0.2s; color: rgba(255,255,255,0.3); }
.um-nav button:hover .nav-caret { color: #BBA45E; }

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
.um-footer{padding:16px;border-top:1px solid rgba(187,164,94,.15);}
.um-logout{display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;color:#fca5a5;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .18s;text-decoration:none;}
.um-logout:hover{background:rgba(239,68,68,.2);color:#fff;}
.um-main{flex:1;display:flex;flex-direction:column;min-width:0;max-width:100%;background:#0f1117;border-left:1px solid rgba(187,164,94,.18);}
.um-topbar{background:rgba(15,17,23,.95);border-bottom:1px solid rgba(187,164,94,.15);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;flex-wrap:wrap;gap:12px;max-width:100%;}
.um-topbar-title{font-size:1.25rem;font-weight:700;color:#f0e6c8;}
.um-topbar-sub{font-size:.8rem;color:rgba(255,255,255,.4);margin-top:1px;}
.um-date-pill{font-size:.85rem;color:rgba(255,255,255,.4);background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);padding:8px 18px;border-radius:20px;}
.um-clock{font-size:.85rem;font-weight:700;color:#BBA45E;background:rgba(187,164,94,.08);border:1px solid rgba(187,164,94,.18);padding:7px 16px;border-radius:18px;}
.um-content{padding:28px;flex:1;}

/* Custom form grid elements */
.settings-tab-btn {
    padding: 12px 18px;
    font-size: 0.85rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.6);
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
    text-align: left;
}
.settings-tab-btn:hover {
    color: #f0e6c8;
    background: rgba(255, 255, 255, 0.02);
}
.settings-tab-btn.active {
    color: #BBA45E;
    border-bottom-color: #BBA45E;
    background: rgba(187, 164, 94, 0.05);
}

.setting-card {
    background: #161c26;
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
}

.setting-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #f0e6c8;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.setting-card-desc {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.4);
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    padding-bottom: 12px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

@media(max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.field-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.field-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: rgba(187, 164, 94, 0.8);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.field-desc {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.35);
    margin-top: 2px;
}

.field-input, .field-select, .field-textarea {
    width: 100%;
    padding: 10px 14px;
    background: #0f1117;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px;
    color: #f0e6c8;
    font-size: 0.875rem;
    outline: none;
    transition: border-color .15s;
}

.field-input:focus, .field-select:focus, .field-textarea:focus {
    border-color: rgba(187, 164, 94, 0.5);
}

.field-select option {
    background: #161c26;
}

/* Custom Toggle Switch */
.toggle-label {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.toggle-switch {
    position: relative;
    width: 44px;
    height: 22px;
    background: #27272a;
    border-radius: 99px;
    transition: background-color 0.2s;
    border: 1px solid rgba(255,255,255,0.08);
}
.toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: #e4e4e7;
    border-radius: 50%;
    transition: transform 0.2s;
}
input:checked + .toggle-switch {
    background: #1EB53A;
}
input:checked + .toggle-switch::after {
    transform: translateX(22px);
    background: #ffffff;
}

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
@media(max-width:900px) {
    .page-footer-row { flex-direction: column; justify-content: center; text-align: center; }
    .page-footer-copy { flex: auto; order: 2; margin-top: 4px; }
    .page-footer-meta { text-align: center; order: 1; }
}
@media(max-width:1024px) {
    .um-body-row { flex-direction: column; background: #0f1117; }
    .um-sidebar { width: 100%; height: auto; max-height: none; position: static; background: linear-gradient(180deg,#0d1b2a,#11202e); border-bottom: 1px solid rgba(187,164,94,0.18); }
    .um-main { border-left: none; }
}
</style>

<div class="um-shell">
    <div class="um-body-row">
        {{-- SIDEBAR --}}
        <aside class="um-sidebar">
            <div class="um-profile">
                <div class="um-avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                <div class="um-name">{{ auth()->user()->name }}</div>
                <div class="um-role-badge"><i class="fas fa-shield-halved"></i> System Administrator</div>
            </div>
            <nav class="um-nav">
                <div class="um-nav-label">Overview</div>
                <a href="/admin/dashboard"><span class="nav-ico"><i class="fas fa-gauge-high"></i></span> Dashboard</a>
                
                <div class="um-nav-label">Registration</div>
                <a href="/admin/registration/regions"><span class="nav-ico"><i class="fas fa-map"></i></span> Regions</a>
                <a href="/admin/registration/districts"><span class="nav-ico"><i class="fas fa-map-location-dot"></i></span> Districts</a>
                <a href="/admin/registration/schools"><span class="nav-ico"><i class="fas fa-school-flag"></i></span> Schools</a>
                <a href="/admin/registration/candidates"><span class="nav-ico"><i class="fas fa-user-graduate"></i></span> Candidates</a>
                
                <div class="um-nav-label">Examinations</div>
                <a href="/admin/exam-types/psle"><span class="nav-ico"><i class="fas fa-graduation-cap"></i></span> PSLE</a>
                <a href="/admin/exam-years"><span class="nav-ico"><i class="fas fa-calendar-check"></i></span> Academic Years</a>

                <div class="um-nav-label">Governance</div>
                <a href="/admin/manage-users"><span class="nav-ico"><i class="fas fa-user-gear"></i></span> Users & Roles</a>
                <a href="/admin/audit-logs"><span class="nav-ico"><i class="fas fa-shield-halved"></i></span> Audit Logs</a>
                <a href="/admin/manage-backups"><span class="nav-ico"><i class="fas fa-server"></i></span> Backups</a>
                <a href="{{ route('admin.system-settings') }}" class="active"><span class="nav-ico"><i class="fas fa-sliders-h"></i></span> System Settings</a>
            </nav>
            <div class="um-footer">
                <form method="POST" action="/logout">@csrf
                    <button type="submit" class="um-logout"><i class="fas fa-right-from-bracket"></i> Sign Out</button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="um-main">
            <header class="um-topbar">
                <div>
                    <div class="um-topbar-title">System Settings Configuration</div>
                    <div class="um-topbar-sub">Configure global school configurations, examination settings, and security controls</div>
                </div>
                <div style="display:flex; align-items:center; gap: 12px;">
                    <div class="um-date-pill" id="um-date">—</div>
                    <div class="um-clock" id="um-clock">--:--</div>
                </div>
            </header>

            <div class="um-content" x-data="{ activeTab: 'general' }">
                
                {{-- Success / Error Alerts --}}
                @if(session('success'))
                    <div class="mb-6 p-4 rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 flex items-center gap-3">
                        <i class="fas fa-circle-check text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400 flex items-center gap-3">
                        <i class="fas fa-circle-exclamation text-lg"></i>
                        <span class="text-sm font-semibold">{{ session('error') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-6 p-4 rounded-xl border border-rose-500/20 bg-rose-500/10 text-rose-400">
                        <div class="flex items-center gap-3 mb-2">
                            <i class="fas fa-circle-exclamation text-lg"></i>
                            <span class="text-sm font-bold">Please correct the following errors:</span>
                        </div>
                        <ul class="list-disc pl-5 text-xs space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Custom Tabs Menu --}}
                <div class="flex border-b border-zinc-800 mb-6 overflow-x-auto w-full gap-2">
                    <button type="button" @click="activeTab = 'general'" :class="activeTab === 'general' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-globe mr-2"></i>General</button>
                    <button type="button" @click="activeTab = 'examination'" :class="activeTab === 'examination' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-file-signature mr-2"></i>Examination</button>
                    <button type="button" @click="activeTab = 'results'" :class="activeTab === 'results' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-square-poll-vertical mr-2"></i>Results Flow</button>
                    <button type="button" @click="activeTab = 'grading'" :class="activeTab === 'grading' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-award mr-2"></i>Grade &amp; Division</button>
                    <button type="button" @click="activeTab = 'registration'" :class="activeTab === 'registration' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-id-card mr-2"></i>Registration</button>
                    <button type="button" @click="activeTab = 'mark_entry'" :class="activeTab === 'mark_entry' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-keyboard mr-2"></i>Mark Entry</button>
                    <button type="button" @click="activeTab = 'reports'" :class="activeTab === 'reports' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-print mr-2"></i>Reports</button>
                    <button type="button" @click="activeTab = 'notifications'" :class="activeTab === 'notifications' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-bell mr-2"></i>Alerts</button>
                    <button type="button" @click="activeTab = 'security'" :class="activeTab === 'security' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-shield-halved"></i> Security</button>
                    <button type="button" @click="activeTab = 'maintenance'" :class="activeTab === 'maintenance' ? 'active' : ''" class="settings-tab-btn whitespace-nowrap"><i class="fas fa-gears mr-2"></i>Maintenance</button>
                </div>

                {{-- Main Settings Form --}}
                <form action="{{ route('admin.system-settings.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- 1. GENERAL SYSTEM SETTINGS --}}
                    <div x-show="activeTab === 'general'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-globe text-amber-500"></i> General Settings</div>
                            <div class="setting-card-desc">Configure system names, active academic cycles, and support contacts</div>
                            
                            <div class="form-grid">
                                <div class="field-group">
                                    <label class="field-label">System Name *</label>
                                    <input type="text" name="system_name" value="{{ old('system_name', setting('system_name')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">System Acronym *</label>
                                    <input type="text" name="system_acronym" value="{{ old('system_acronym', setting('system_acronym')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Institution Name *</label>
                                    <input type="text" name="institution_name" value="{{ old('institution_name', setting('institution_name')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Region Name *</label>
                                    <input type="text" name="region_name" value="{{ old('region_name', setting('region_name')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Council Name *</label>
                                    <input type="text" name="council_name" value="{{ old('council_name', setting('council_name')) }}" class="field-input">
                                </div>
                                <div class="field-group font-bold">
                                    <label class="field-label text-amber-500">Active Academic Year *</label>
                                    <input type="text" name="active_academic_year" value="{{ old('active_academic_year', setting('active_academic_year')) }}" class="field-input">
                                    <div class="field-desc">Currently running academic year</div>
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Default Exam Year *</label>
                                    <input type="text" name="default_exam_year" value="{{ old('default_exam_year', setting('default_exam_year')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Timezone *</label>
                                    <input type="text" name="timezone" value="{{ old('timezone', setting('timezone')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Default Language *</label>
                                    <input type="text" name="default_language" value="{{ old('default_language', setting('default_language')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Support Email Address</label>
                                    <input type="email" name="support_email" value="{{ old('support_email', setting('support_email')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Support Phone Number</label>
                                    <input type="text" name="support_phone" value="{{ old('support_phone', setting('support_phone')) }}" class="field-input">
                                </div>
                                <div class="field-group">
                                    <label class="field-label">System Logo</label>
                                    <div class="flex items-center gap-4">
                                        @php
                                            $logoUrl = null;
                                            $sysLogo = setting('system_logo');
                                            if ($sysLogo) {
                                                if (str_starts_with($sysLogo, 'http://') || str_starts_with($sysLogo, 'https://')) {
                                                    $logoUrl = $sysLogo;
                                                } elseif (str_starts_with($sysLogo, 'storage/')) {
                                                    $logoUrl = asset($sysLogo);
                                                } else {
                                                    $logoUrl = asset('storage/' . ltrim((string)$sysLogo, '/'));
                                                }
                                            }
                                        @endphp
                                        @if($logoUrl)
                                            <img src="{{ $logoUrl }}" alt="Logo" class="h-16 w-auto object-contain bg-zinc-900 border border-zinc-800 rounded p-1">
                                        @else
                                            <div class="h-16 w-16 bg-zinc-900 border border-zinc-800 rounded flex items-center justify-center text-xs text-zinc-600">No Logo</div>
                                        @endif
                                        <input type="file" name="system_logo" class="field-input text-zinc-400 bg-transparent border-none py-0">
                                    </div>
                                    <div class="field-desc">Accepted: png, jpg. Max size: 2MB</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 2. EXAMINATION SETTINGS --}}
                    <div x-show="activeTab === 'examination'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-file-signature text-amber-500"></i> Examination Settings</div>
                            <div class="setting-card-desc">Configure grades rules, exam level scopes, and default types</div>

                            <div class="form-grid">
                                <div class="field-group col-span-2">
                                    <label class="field-label">Enabled Exam Levels</label>
                                    <div class="grid grid-cols-3 gap-4 mt-2">
                                        @php
                                            $levels = ["Class II", "Standard IV", "Standard VII", "Form II", "Form IV", "Form VI"];
                                            $currentLevels = setting('enabled_exam_levels', []);
                                        @endphp
                                        @foreach($levels as $lvl)
                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" name="enabled_exam_levels[]" value="{{ $lvl }}" {{ in_array($lvl, $currentLevels) ? 'checked' : '' }} class="rounded border-zinc-700 bg-zinc-950 text-amber-500 focus:ring-amber-500 w-4 h-4">
                                                <span class="text-sm text-zinc-300">{{ $lvl }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="field-desc mt-2">Check levels available for assessment registration</div>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Default Exam Type Code *</label>
                                    <input type="text" name="default_exam_type" value="{{ old('default_exam_type', setting('default_exam_type')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Maximum Subject Mark *</label>
                                    <input type="number" name="maximum_subject_score" value="{{ old('maximum_subject_score', setting('maximum_subject_score')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Minimum Subject Mark *</label>
                                    <input type="number" name="minimum_subject_score" value="{{ old('minimum_subject_score', setting('minimum_subject_score')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Decimal Places for Marks *</label>
                                    <input type="number" name="marks_decimal_places" value="{{ old('marks_decimal_places', setting('marks_decimal_places')) }}" class="field-input">
                                </div>

                                <div class="field-group flex flex-row items-center gap-4 justify-between p-3 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Decimal Marks</label>
                                        <div class="field-desc">Permit entering marks with decimals</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_decimal_marks" value="0">
                                        <input type="checkbox" name="allow_decimal_marks" value="1" {{ setting('allow_decimal_marks') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Absent Code *</label>
                                    <input type="text" name="absent_code" value="{{ old('absent_code', setting('absent_code')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Incomplete Mark Code *</label>
                                    <input type="text" name="incomplete_code" value="{{ old('incomplete_code', setting('incomplete_code')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Withheld Status Code *</label>
                                    <input type="text" name="withheld_code" value="{{ old('withheld_code', setting('withheld_code')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Disqualified Code *</label>
                                    <input type="text" name="disqualified_code" value="{{ old('disqualified_code', setting('disqualified_code')) }}" class="field-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. RESULTS PROCESSING --}}
                    <div x-show="activeTab === 'results'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-square-poll-vertical text-amber-500"></i> Results Processing Settings</div>
                            <div class="setting-card-desc">Control auto-calculations, ranking ranges, and approval workflows</div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Auto Calculate Totals</label>
                                        <div class="field-desc">Calculate total marks automatically upon entry</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="auto_calculate_totals" value="0">
                                        <input type="checkbox" name="auto_calculate_totals" value="1" {{ setting('auto_calculate_totals') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Auto Calculate Average</label>
                                        <div class="field-desc">Update candidate average marks instantly</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="auto_calculate_average" value="0">
                                        <input type="checkbox" name="auto_calculate_average" value="1" {{ setting('auto_calculate_average') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Auto Calculate Grade</label>
                                        <div class="field-desc">Automatically map average marks to letter grades</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="auto_calculate_grade" value="0">
                                        <input type="checkbox" name="auto_calculate_grade" value="1" {{ setting('auto_calculate_grade') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Auto Calculate Division</label>
                                        <div class="field-desc">Calculate overall candidate divisions based on profiles</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="auto_calculate_division" value="0">
                                        <input type="checkbox" name="auto_calculate_division" value="1" {{ setting('auto_calculate_division') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Enable Rankings</label>
                                        <div class="field-desc">Calculate and store rankings for students and schools</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="ranking_enabled" value="0">
                                        <input type="checkbox" name="ranking_enabled" value="1" {{ setting('ranking_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Ranking Scope *</label>
                                    <select name="ranking_scope" class="field-select">
                                        <option value="national" {{ setting('ranking_scope') === 'national' ? 'selected' : '' }}>National (Whole System)</option>
                                        <option value="region" {{ setting('ranking_scope') === 'region' ? 'selected' : '' }}>Region Only</option>
                                        <option value="council" {{ setting('ranking_scope') === 'council' ? 'selected' : '' }}>Council District Only</option>
                                        <option value="school" {{ setting('ranking_scope') === 'school' ? 'selected' : '' }}>School Level Only</option>
                                    </select>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Show Positions on Reports</label>
                                        <div class="field-desc">Display ranking positions in PDF templates</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="show_position_on_reports" value="0">
                                        <input type="checkbox" name="show_position_on_reports" value="1" {{ setting('show_position_on_reports') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Require Result Approval</label>
                                        <div class="field-desc">Requires final panel leader approval before release</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="result_approval_required" value="0">
                                        <input type="checkbox" name="result_approval_required" value="1" {{ setting('result_approval_required') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Reprocessing After Approval</label>
                                        <div class="field-desc">Permits recalculations after result has been signed off</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_reprocess_after_approval" value="0">
                                        <input type="checkbox" name="allow_reprocess_after_approval" value="1" {{ setting('allow_reprocess_after_approval') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Lock Results After Approval</label>
                                        <div class="field-desc">Locks grade spreadsheets to prevent accidental edits</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="lock_results_after_approval" value="0">
                                        <input type="checkbox" name="lock_results_after_approval" value="1" {{ setting('lock_results_after_approval') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4. GRADE & DIVISION --}}
                    <div x-show="activeTab === 'grading'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-award text-amber-500"></i> Grade &amp; Division Settings</div>
                            <div class="setting-card-desc">Configure target profiles, pass mark boundaries, and subject count rules</div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label class="field-label">Grading Scale Template *</label>
                                    <select name="grading_scale_id" class="field-select">
                                        @foreach($gradingScales as $id => $name)
                                            <option value="{{ $id }}" {{ setting('grading_scale_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Division Rule Template *</label>
                                    <select name="division_rule_id" class="field-select">
                                        @foreach($divisionRules as $id => $name)
                                            <option value="{{ $id }}" {{ setting('division_rule_id') === $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Pass Mark Threshold *</label>
                                    <input type="number" name="pass_mark" value="{{ old('pass_mark', setting('pass_mark')) }}" class="field-input">
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Use Division Points System</label>
                                        <div class="field-desc">Calculate using sum of points (lower is better)</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="use_division_points" value="0">
                                        <input type="checkbox" name="use_division_points" value="1" {{ setting('use_division_points') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Best Subjects Count *</label>
                                    <input type="number" name="best_subjects_count" value="{{ old('best_subjects_count', setting('best_subjects_count')) }}" class="field-input">
                                    <div class="field-desc">Count of best-performing subjects included in points calculation</div>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Compulsory Rules Enabled</label>
                                        <div class="field-desc">Apply compulsory requirements (e.g. Mathematics, English)</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="compulsory_subject_rules_enabled" value="0">
                                        <input type="checkbox" name="compulsory_subject_rules_enabled" value="1" {{ setting('compulsory_subject_rules_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 5. REGISTRATION --}}
                    <div x-show="activeTab === 'registration'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-id-card text-amber-500"></i> Candidate Registration Settings</div>
                            <div class="setting-card-desc">Control registration rules, candidate formats, and mandatory details</div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label class="field-label">Candidate Index Number Format *</label>
                                    <input type="text" name="candidate_number_format" value="{{ old('candidate_number_format', setting('candidate_number_format')) }}" class="field-input">
                                    <div class="field-desc">Format placeholder rules (e.g., PS{YEAR}/{SCHOOL}/{NUMBER})</div>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Duplicate Names</label>
                                        <div class="field-desc">Allow identical names within the same school centre</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_duplicate_candidate_names" value="0">
                                        <input type="checkbox" name="allow_duplicate_candidate_names" value="1" {{ setting('allow_duplicate_candidate_names') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Inter-school Transfers</label>
                                        <div class="field-desc">Allow students to transfer between centers after registration</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_candidate_transfer" value="0">
                                        <input type="checkbox" name="allow_candidate_transfer" value="1" {{ setting('allow_candidate_transfer') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Require Photo Upload</label>
                                        <div class="field-desc">Mandate photo uploads for candidates</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="require_candidate_photo" value="0">
                                        <input type="checkbox" name="require_candidate_photo" value="1" {{ setting('require_candidate_photo') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Require Gender</label>
                                        <div class="field-desc">Require gender field during input</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="require_gender" value="0">
                                        <input type="checkbox" name="require_gender" value="1" {{ setting('require_gender') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Require Date of Birth</label>
                                        <div class="field-desc">Mandate birth dates to prevent duplicate index registrations</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="require_date_of_birth" value="0">
                                        <input type="checkbox" name="require_date_of_birth" value="1" {{ setting('require_date_of_birth') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Enable Bulk CSV Import</label>
                                        <div class="field-desc">Allow uploading registration files via CSV/Excel</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="candidate_import_enabled" value="0">
                                        <input type="checkbox" name="candidate_import_enabled" value="1" {{ setting('candidate_import_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 6. MARK ENTRY --}}
                    <div x-show="activeTab === 'mark_entry'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-keyboard text-amber-500"></i> Mark Entry Configuration</div>
                            <div class="setting-card-desc">Open or close portals, schedule schedules, and restrict permissions</div>

                            <div class="form-grid">
                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg col-span-2">
                                    <div>
                                        <label class="field-label text-amber-500">Mark Entry Portal Status</label>
                                        <div class="field-desc">Open or lock the general operator mark input portals globally</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="mark_entry_open" value="0">
                                        <input type="checkbox" name="mark_entry_open" value="1" {{ setting('mark_entry_open') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Portal Open Date</label>
                                    <input type="date" name="mark_entry_start_date" value="{{ old('mark_entry_start_date', setting('mark_entry_start_date')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Portal Lock Date</label>
                                    <input type="date" name="mark_entry_end_date" value="{{ old('mark_entry_end_date', setting('mark_entry_end_date')) }}" class="field-input">
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Direct School Entry</label>
                                        <div class="field-desc">Allow schools to access forms directly</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_school_mark_entry" value="0">
                                        <input type="checkbox" name="allow_school_mark_entry" value="1" {{ setting('allow_school_mark_entry') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Allow Offline Bulk Import</label>
                                        <div class="field-desc">Allow importing marks via offline templates</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="allow_bulk_mark_import" value="0">
                                        <input type="checkbox" name="allow_bulk_mark_import" value="1" {{ setting('allow_bulk_mark_import') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Strict 0-100 Boundaries</label>
                                        <div class="field-desc">Require values to lie within standard 0-100 scale</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="restrict_marks_between_0_and_100" value="0">
                                        <input type="checkbox" name="restrict_marks_between_0_and_100" value="1" {{ setting('restrict_marks_between_0_and_100') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Require Mark Approval</label>
                                        <div class="field-desc">Require panel review before marking batch is completed</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="require_mark_entry_approval" value="0">
                                        <input type="checkbox" name="require_mark_entry_approval" value="1" {{ setting('require_mark_entry_approval') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg col-span-2">
                                    <div>
                                        <label class="field-label">Lock Marks After Submission</label>
                                        <div class="field-desc">Lock fields instantly upon user pressing submit</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="lock_marks_after_submission" value="0">
                                        <input type="checkbox" name="lock_marks_after_submission" value="1" {{ setting('lock_marks_after_submission') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 7. REPORTS --}}
                    <div x-show="activeTab === 'reports'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-print text-amber-500"></i> Report Slips &amp; Signatures</div>
                            <div class="setting-card-desc">Configure report print elements, watermark stamps, and footer texts</div>

                            <div class="form-grid">
                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Show School Logo</label>
                                        <div class="field-desc">Render school emblem on individual slips</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="show_school_logo" value="0">
                                        <input type="checkbox" name="show_school_logo" value="1" {{ setting('show_school_logo') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Show Region Logo</label>
                                        <div class="field-desc">Render region emblem in summary layouts</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="show_region_logo" value="0">
                                        <input type="checkbox" name="show_region_logo" value="1" {{ setting('show_region_logo') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Show Signature Images</label>
                                        <div class="field-desc">Render head official signature stamps in PDF results</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="show_signatures" value="0">
                                        <input type="checkbox" name="show_signatures" value="1" {{ setting('show_signatures') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Report Watermark Text</label>
                                    <input type="text" name="report_watermark" value="{{ old('report_watermark', setting('report_watermark')) }}" class="field-input">
                                </div>

                                <div class="field-group col-span-2">
                                    <label class="field-label">Official Report Footer Text</label>
                                    <textarea name="report_footer_text" rows="3" class="field-textarea">{{ old('report_footer_text', setting('report_footer_text')) }}</textarea>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Print Candidate Slips</label>
                                        <div class="field-desc">Permit operators to print individual slips</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="print_candidate_result_slips" value="0">
                                        <input type="checkbox" name="print_candidate_result_slips" value="1" {{ setting('print_candidate_result_slips') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Print School Summary</label>
                                        <div class="field-desc">Permit printing general school ranking sheet summaries</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="print_school_summary" value="0">
                                        <input type="checkbox" name="print_school_summary" value="1" {{ setting('print_school_summary') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Print Council Summary</label>
                                        <div class="field-desc">Allow downloading summaries of council scores</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="print_council_summary" value="0">
                                        <input type="checkbox" name="print_council_summary" value="1" {{ setting('print_council_summary') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Print Regional Summary</label>
                                        <div class="field-desc">Allow generating regional performance summaries</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="print_regional_summary" value="0">
                                        <input type="checkbox" name="print_regional_summary" value="1" {{ setting('print_regional_summary') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 8. NOTIFICATIONS --}}
                    <div x-show="activeTab === 'notifications'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-bell text-amber-500"></i> Alerts &amp; Notification Triggers</div>
                            <div class="setting-card-desc">Control automated triggers, SMS channels, and background failure notices</div>

                            <div class="form-grid">
                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Email Notifications</label>
                                        <div class="field-desc">Send automated emails for key events</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="email_notifications_enabled" value="0">
                                        <input type="checkbox" name="email_notifications_enabled" value="1" {{ setting('email_notifications_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">SMS Notifications</label>
                                        <div class="field-desc">Send mobile SMS via Twilio/Nexmo integrations</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="sms_notifications_enabled" value="0">
                                        <input type="checkbox" name="sms_notifications_enabled" value="1" {{ setting('sms_notifications_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Notify on Result Approval</label>
                                        <div class="field-desc">Alert users when final results approval is granted</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="notify_on_result_approval" value="0">
                                        <input type="checkbox" name="notify_on_result_approval" value="1" {{ setting('notify_on_result_approval') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Notify on Mark Submission</label>
                                        <div class="field-desc">Alert panel leaders when schools submit raw marks</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="notify_on_mark_submission" value="0">
                                        <input type="checkbox" name="notify_on_mark_submission" value="1" {{ setting('notify_on_mark_submission') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg col-span-2">
                                    <div>
                                        <label class="field-label">Notify on Background Import Failure</label>
                                        <div class="field-desc">Alert operators if background CSV imports crash</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="notify_on_import_failure" value="0">
                                        <input type="checkbox" name="notify_on_import_failure" value="1" {{ setting('notify_on_import_failure') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 9. SECURITY --}}
                    <div x-show="activeTab === 'security'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-shield-halved text-amber-500"></i> Security &amp; Access Whitelist</div>
                            <div class="setting-card-desc">Define session timeout thresholds, IP restrictions, and audit cycle policies</div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label class="field-label">Inactivity Session Timeout (Minutes) *</label>
                                    <input type="number" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', setting('session_timeout_minutes')) }}" class="field-input">
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Password Expiry Cycle (Days)</label>
                                    <input type="number" name="password_expiry_days" value="{{ old('password_expiry_days', setting('password_expiry_days')) }}" class="field-input">
                                    <div class="field-desc">Set to 0 to disable password rotation checks</div>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Lockout Max Login Attempts *</label>
                                    <input type="number" name="max_login_attempts" value="{{ old('max_login_attempts', setting('max_login_attempts')) }}" class="field-input">
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Enforce Two-Factor Authentication</label>
                                        <div class="field-desc">Require all admins to configure 2FA</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="two_factor_enabled" value="0">
                                        <input type="checkbox" name="two_factor_enabled" value="1" {{ setting('two_factor_enabled') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group">
                                    <label class="field-label">Audit Log Retention (Days) *</label>
                                    <input type="number" name="audit_log_retention_days" value="{{ old('audit_log_retention_days', setting('audit_log_retention_days')) }}" class="field-input">
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label">Restrict Admin Panel access by IP</label>
                                        <div class="field-desc">Restrict admin routes to whitelist below</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="restrict_admin_by_ip" value="0">
                                        <input type="checkbox" name="restrict_admin_by_ip" value="1" {{ setting('restrict_admin_by_ip') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group col-span-2">
                                    <label class="field-label">Whitelisted Admin IP Addresses</label>
                                    <textarea name="allowed_admin_ips" rows="3" placeholder="e.g. 192.168.1.1, 10.0.0.1 (comma or newline separated)" class="field-textarea">@php
                                        $ips = setting('allowed_admin_ips', []);
                                        echo is_array($ips) ? implode(",\n", $ips) : '';
                                    @endphp</textarea>
                                    <div class="field-desc">Leave blank to disable IP whitelisting when restrict is enabled</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 10. MAINTENANCE --}}
                    <div x-show="activeTab === 'maintenance'" x-cloak>
                        <div class="setting-card">
                            <div class="setting-card-title"><i class="fas fa-gears text-amber-500"></i> Maintenance &amp; Public Message</div>
                            <div class="setting-card-desc">Lockout public portals, update alerts messages, and configure caching parameters</div>

                            <div class="form-grid">
                                <div class="field-group">
                                    <label class="field-label">System Settings Cache TTL (Seconds) *</label>
                                    <input type="number" name="cache_ttl_seconds" value="{{ old('cache_ttl_seconds', setting('cache_ttl_seconds')) }}" class="field-input">
                                </div>

                                <div class="flex items-center justify-between p-4 bg-zinc-950/40 rounded-lg">
                                    <div>
                                        <label class="field-label text-rose-500 font-bold">Lockout Maintenance Mode</label>
                                        <div class="field-desc">Lock out all non-admins from public portals</div>
                                    </div>
                                    <label class="toggle-label">
                                        <input type="hidden" name="maintenance_mode" value="0">
                                        <input type="checkbox" name="maintenance_mode" value="1" {{ setting('maintenance_mode') ? 'checked' : '' }} class="sr-only">
                                        <span class="toggle-switch"></span>
                                    </label>
                                </div>

                                <div class="field-group col-span-2">
                                    <label class="field-label">Maintenance Mode Alert Message *</label>
                                    <textarea name="maintenance_message" rows="3" class="field-textarea">{{ old('maintenance_message', setting('maintenance_message')) }}</textarea>
                                </div>

                                <div class="field-group col-span-2">
                                    <label class="field-label">Internal Administrator System Notes</label>
                                    <textarea name="system_notes" rows="4" placeholder="Log key server events, configuration shifts, etc." class="field-textarea">{{ old('system_notes', setting('system_notes')) }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Danger Zone Card --}}
                        <div class="border border-red-500/20 bg-red-950/20 rounded-16 p-6 rounded-xl mb-6">
                            <div class="text-red-400 font-bold text-base mb-2 flex items-center gap-2">
                                <i class="fas fa-triangle-exclamation"></i> Danger Zone
                            </div>
                            <p class="text-xs text-red-300/75 mb-4 leading-relaxed">
                                Purging compiled maps, caches, or static configurations can temporarily degrade server loading performance during high traffic but forces instant updates of cache keys.
                            </p>
                            
                            <button type="submit" form="clear-cache-form" onclick="return confirm('Are you sure you want to clear system caches, config maps, and compiled view templates?');" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-bold transition-all shadow-md">
                                <i class="fas fa-trash-can mr-2"></i> Clear System Caches
                            </button>
                        </div>
                    </div>

                    {{-- Form Footer Actions --}}
                    <div class="flex items-center gap-4 justify-end mt-4">
                        <a href="/admin/dashboard" class="px-6 py-2.5 rounded-lg text-sm font-bold bg-zinc-800 text-zinc-300 hover:bg-zinc-700 transition-all border border-zinc-700">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-bold bg-amber-500 hover:bg-amber-600 text-zinc-950 transition-all shadow-md">
                            <i class="fas fa-save mr-2"></i> Save Settings
                        </button>
                    </div>
                </form>

                {{-- Hidden Form for cache clearing --}}
                <form id="clear-cache-form" action="{{ route('admin.system-settings.clear-cache') }}" method="POST" class="hidden">
                    @csrf
                </form>

            </div>
        </div>
    </div>

    {{-- FOOTER --}}
    <footer class="page-footer">
        <div class="page-footer-stripes" aria-hidden="true">
            <span style="background:#1EB53A;"></span>
            <span style="background:#FCD116;"></span>
            <span style="background:#000000;"></span>
            <span style="background:#00A3DD;"></span>
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
</div>

<script>
    function tick(){
        const n=new Date();
        document.getElementById('um-clock').textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
        document.getElementById('um-date').textContent=n.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
    }
    tick(); setInterval(tick,1000);
</script>
</body>
</html>
