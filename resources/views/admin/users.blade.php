<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRMS - Manage Users</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
</head>
<body>
<style>
* { box-sizing: border-box; }
input, select, textarea, button { font-family: inherit; }
body, html { margin: 0; padding: 0; width: 100%; max-width: 100vw; overflow-x: hidden; background: #0f1117; font-family: 'Maiandra GD', sans-serif; }
:root{--tz-green:#1EB53A;--tz-yellow:#FCD116;--tz-blue:#00A3DD;--tz-dark:#0b1014;--tz-card:#101518;--tz-text:#f0f4f7;--tz-muted:rgba(255,255,255,.45);}
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
/* Toolbar */
.um-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:22px;flex-wrap:wrap;}
.um-search{flex:1;min-width:220px;padding:10px 16px 10px 40px;background:#161c26;border:1px solid rgba(255,255,255,.1);border-radius:10px;color:#f0e6c8;font-size:.875rem;outline:none;}
.um-search:focus{border-color:rgba(187,164,94,.4);}
.um-search-wrap{position:relative;flex:1;}
.um-search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.3);font-size:.85rem;}
.um-filter{padding:10px 14px;background:#161c26;border:1px solid rgba(255,255,255,.1);border-radius:10px;color:#f0e6c8;font-size:.85rem;outline:none;}
.um-filter:focus{border-color:rgba(187,164,94,.4);}
.um-add-btn{display:flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#BBA45E,#8a7340);color:#0d1b2a;border:none;border-radius:10px;font-size:.875rem;font-weight:700;cursor:pointer;transition:opacity .15s;white-space:nowrap;}
.um-add-btn:hover{opacity:.9;}
/* Stats row */
.um-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.um-stat{background:#161c26;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;}
.um-stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.um-stat-val{font-size:1.6rem;font-weight:800;color:#f0e6c8;line-height:1;}
.um-stat-lbl{font-size:.75rem;color:rgba(255,255,255,.4);margin-top:3px;}
/* Table */
.um-table-wrap{background:#161c26;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow-x:auto;overflow-y:hidden;width:100%;}
.um-table{width:100%;border-collapse:collapse;min-width:800px;}
.um-table thead tr{background:rgba(187,164,94,.08);border-bottom:1px solid rgba(187,164,94,.15);}
.um-table th{padding:12px 16px;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(187,164,94,.8);text-align:left;}
.um-table tbody tr{border-bottom:1px solid rgba(255,255,255,.04);transition:background .15s;}
.um-table tbody tr:hover{background:rgba(187,164,94,.04);}
.um-table td{padding:12px 16px;font-size:.85rem;color:rgba(255,255,255,.75);}
.um-table td.name{color:#f0e6c8;font-weight:600;}
.role-chip{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;letter-spacing:.05em;}
.chip-admin{background:rgba(187,164,94,.15);color:#BBA45E;border:1px solid rgba(187,164,94,.3);}
.chip-user{background:rgba(99,102,241,.12);color:#a5b4fc;border:1px solid rgba(99,102,241,.2);}
.chip-role{background:rgba(52,211,153,.1);color:#6ee7b7;border:1px solid rgba(52,211,153,.2);}
.status-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;}
.status-active{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.2);}
.status-suspended{background:rgba(239,68,68,.1);color:#fca5a5;border:1px solid rgba(239,68,68,.2);}
.um-actions{display:flex;gap:6px;}
.um-btn-icon{width:30px;height:30px;border-radius:8px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.8rem;transition:all .15s;}
.btn-edit{background:rgba(59,130,246,.1);color:#60a5fa;}
.btn-edit:hover{background:rgba(59,130,246,.25);}
.btn-del{background:rgba(239,68,68,.1);color:#fca5a5;}
.btn-del:hover{background:rgba(239,68,68,.25);}
/* Modal */
.um-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;padding:20px;}
.um-modal{background:#161c26;border:1px solid rgba(187,164,94,.2);border-radius:20px;width:100%;max-width:520px;overflow:hidden;}
.um-modal-head{background:linear-gradient(135deg,#0d1b2a,#111827);padding:22px 24px;border-bottom:1px solid rgba(187,164,94,.15);}
.um-modal-title{font-size:1.05rem;font-weight:700;color:#f0e6c8;margin-bottom:2px;}
.um-modal-sub{font-size:.8rem;color:rgba(255,255,255,.4);}
.um-modal-body{padding:24px;display:grid;gap:16px;}
.um-field label{display:block;font-size:.75rem;font-weight:700;color:rgba(187,164,94,.8);margin-bottom:6px;letter-spacing:.05em;text-transform:uppercase;}
.um-field input,.um-field select{width:100%;padding:10px 14px;background:#0f1117;border:1px solid rgba(255,255,255,.1);border-radius:10px;color:#f0e6c8;font-size:.875rem;outline:none;transition:border-color .15s;}
.um-field input:focus,.um-field select:focus{border-color:rgba(187,164,94,.5);}
.um-field select option{background:#161c26;}
.um-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.um-modal-foot{padding:16px 24px;display:flex;gap:10px;justify-content:flex-end;background:rgba(0,0,0,.2);border-top:1px solid rgba(255,255,255,.05);}
.btn-cancel{padding:9px 20px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:rgba(255,255,255,.6);font-size:.85rem;font-weight:600;cursor:pointer;}
.btn-cancel:hover{background:rgba(255,255,255,.1);}
.btn-save{padding:9px 24px;background:linear-gradient(135deg,#BBA45E,#8a7340);border:none;border-radius:10px;color:#0d1b2a;font-size:.85rem;font-weight:700;cursor:pointer;}
.btn-save:hover{opacity:.9;}
.um-toast{position:fixed;bottom:24px;right:24px;z-index:99999;padding:12px 20px;border-radius:12px;font-size:.875rem;font-weight:600;display:none;}
.toast-ok{background:#065f46;color:#6ee7b7;border:1px solid rgba(52,211,153,.3);}
.toast-err{background:#7f1d1d;color:#fca5a5;border:1px solid rgba(239,68,68,.3);}
.um-empty{padding:40px;text-align:center;color:rgba(255,255,255,.3);font-size:.9rem;}

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
@media(max-width:900px) {
    .page-footer-row { flex-direction: column; justify-content: center; text-align: center; }
    .page-footer-copy { flex: auto; order: 2; margin-top: 4px; }
    .page-footer-meta { text-align: center; order: 1; }
}
@media(max-width:1024px) {
    .um-body-row { flex-direction: column; background: #0f1117; }
    .um-sidebar { width: 100%; height: auto; max-height: none; position: static; background: linear-gradient(180deg,#0d1b2a,#11202e); border-bottom: 1px solid rgba(187,164,94,0.18); }
    .um-main { border-left: none; }
    .um-stats { grid-template-columns: repeat(2, 1fr); }
}
@media(max-width:600px) {
    .um-stats { grid-template-columns: 1fr; }
    .um-topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
}
.um-pagination { margin-top: 24px; display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: rgba(22, 28, 38, 0.6); border: 1px solid rgba(187, 164, 94, 0.15); border-radius: 16px; backdrop-filter: blur(10px); width: 100%; box-sizing: border-box; }
.pg-info { display: flex; align-items: center; gap: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.5); }
.pg-badge { background: rgba(187, 164, 94, 0.1); padding: 4px 12px; border-radius: 20px; border: 1px solid rgba(187, 164, 94, 0.2); }
.pg-btns { display: flex; gap: 10px; margin-left: auto; }
.pg-btn { padding: 8px 18px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; color: rgba(255, 255, 255, 0.7); font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
.pg-btn:hover:not(:disabled) { background: rgba(187, 164, 94, 0.1); border-color: rgba(187, 164, 94, 0.3); color: #BBA45E; transform: translateY(-1px); }
.pg-btn.active { background: rgba(187, 164, 94, 0.15); border-color: rgba(187, 164, 94, 0.4); color: #BBA45E; }
.pg-btn:active:not(:disabled) { transform: translateY(0); }
</style>

<div class="um-shell" x-data="userManager()" x-init="init()">

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
        <a href="/admin/manage-users" class="active"><span class="nav-ico"><i class="fas fa-user-gear"></i></span> Users & Roles</a>
        <a href="/admin/audit-logs"><span class="nav-ico"><i class="fas fa-shield-halved"></i></span> Audit Logs</a>
        <a href="/admin/manage-backups"><span class="nav-ico"><i class="fas fa-server"></i></span> Backups</a>
    </nav>
    <div class="um-footer">
        <form method="POST" action="/logout">@csrf
            <button type="submit" class="um-logout"><i class="fas fa-right-from-bracket"></i> Sign Out</button>
        </form>
    </div>
</aside>

{{-- MAIN --}}
<div class="um-main">
    <header class="um-topbar">
        <div>
            <div class="um-topbar-title">Users & Role Management</div>
            <div class="um-topbar-sub">Add, edit and assign roles to system users</div>
        </div>
        <div style="display:flex; align-items:center; gap: 12px;">
            <div class="um-date-pill" id="um-date">—</div>
            <div class="um-clock" id="um-clock">--:--</div>
        </div>
    </header>

    <div class="um-content">

        {{-- Stats --}}
        <div class="um-stats">
            <div class="um-stat">
                <div class="um-stat-icon" style="background:rgba(59,130,246,.15);color:#60a5fa;"><i class="fas fa-users"></i></div>
                <div><div class="um-stat-val" x-text="stats.total">0</div><div class="um-stat-lbl">Total Users</div></div>
            </div>
            <div class="um-stat">
                <div class="um-stat-icon" style="background:rgba(187,164,94,.15);color:#BBA45E;"><i class="fas fa-user-shield"></i></div>
                <div><div class="um-stat-val" x-text="stats.admins">0</div><div class="um-stat-lbl">Administrators</div></div>
            </div>
            <div class="um-stat">
                <div class="um-stat-icon" style="background:rgba(52,211,153,.12);color:#34d399;"><i class="fas fa-circle-check"></i></div>
                <div><div class="um-stat-val" x-text="stats.active">0</div><div class="um-stat-lbl">Active</div></div>
            </div>
            <div class="um-stat">
                <div class="um-stat-icon" style="background:rgba(239,68,68,.12);color:#fca5a5;"><i class="fas fa-ban"></i></div>
                <div><div class="um-stat-val" x-text="stats.suspended">0</div><div class="um-stat-lbl">Suspended</div></div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="um-toolbar">
            <div class="um-search-wrap">
                <i class="fas fa-magnifying-glass"></i>
                <input class="um-search" type="text" placeholder="Search by name or email…" x-model="search" @input.debounce.500ms="loadUsers(1)">
            </div>
            <select class="um-filter" x-model="filterRole" @change="loadUsers(1)">
                <option value="">All Roles</option>
                <template x-for="r in roles" :key="r.id">
                    <option :value="r.id" x-text="r.name"></option>
                </template>
            </select>
            <select class="um-filter" x-model="filterStatus" @change="loadUsers(1)">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
            <button class="um-add-btn" @click="openModal()"><i class="fas fa-plus"></i> Add User</button>
        </div>

        {{-- Table --}}
        <div class="um-table-wrap">
            <div x-show="loading" class="um-empty"><i class="fas fa-spinner fa-spin" style="font-size:1.4rem;color:#BBA45E;"></i></div>
            <table class="um-table" x-show="!loading">
                <thead>
                    <tr>
                        <th>#</th><th>Name</th><th>Email</th><th>Portal Role</th><th>System Role</th><th>Status</th><th>Last Login</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(u, i) in filtered" :key="u.id">
                        <tr>
                            <td x-text="((currentPage - 1) * 20) + i + 1" style="color:rgba(255,255,255,.3);"></td>
                            <td class="name" x-text="u.name"></td>
                            <td x-text="u.email"></td>
                            <td>
                                <span :class="['admin', 'mock_dao', 'mock_headteacher', 'mock_rao', 'subject_panel_leader'].includes(u.portal_role) ? 'role-chip chip-admin' : 'role-chip chip-user'" 
                                      x-text="u.portal_role==='admin' ? 'Admin' : (u.portal_role==='mock_dao' ? 'Mock DAO' : (u.portal_role==='mock_headteacher' ? 'Mock Headteacher' : (u.portal_role==='mock_rao' ? 'Mock RAO' : (u.portal_role==='subject_panel_leader' ? 'Subject Panel Leader' : 'User'))))"></span>
                            </td>
                            <td>
                                <span class="role-chip chip-role" x-text="u.role_name || '—'"></span>
                            </td>
                            <td>
                                <span :class="u.status==='active'?'status-chip status-active':'status-chip status-suspended'">
                                    <i :class="u.status==='active'?'fas fa-circle':'fas fa-ban'" style="font-size:.55rem;"></i>
                                    <span x-text="u.status==='active'?'Active':'Suspended'"></span>
                                </span>
                            </td>
                            <td x-text="u.last_login_at" style="color:rgba(255,255,255,.4);font-size:.8rem;"></td>
                            <td>
                                <div class="um-actions">
                                    <button class="um-btn-icon btn-edit" @click="openEdit(u)" title="Edit"><i class="fas fa-pen"></i></button>
                                    <button class="um-btn-icon btn-del" @click="deleteUser(u)" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="!loading && filtered.length===0">
                        <td colspan="8" class="um-empty">No users found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="um-pagination" x-show="lastPage > 1">
            <div class="pg-info">
                <div class="pg-badge">
                    Page <span x-text="currentPage" style="color: #BBA45E; font-weight: 800;"></span> 
                    <span style="opacity: 0.5; margin: 0 4px;">/</span> 
                    <span x-text="lastPage" style="color: #f0e6c8; font-weight: 600;"></span>
                </div>
                <div style="color: rgba(255,255,255,0.35);">
                    <i class="fas fa-users" style="font-size: 0.75rem; margin-right: 4px;"></i>
                    <span x-text="totalUsers" style="font-weight: 600; color: rgba(255,255,255,0.6);"></span> users total
                </div>
            </div>

            <div class="pg-btns">
                <button @click="loadUsers(currentPage - 1)" :disabled="currentPage <= 1" class="pg-btn" :style="currentPage <= 1 ? 'opacity: 0.3; cursor: not-allowed; pointer-events: none;' : ''">
                    <i class="fas fa-arrow-left"></i> Previous
                </button>
                <button @click="loadUsers(currentPage + 1)" :disabled="currentPage >= lastPage" class="pg-btn active" :style="currentPage >= lastPage ? 'opacity: 0.3; cursor: not-allowed; pointer-events: none;' : ''">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

    </div>{{-- end content --}}
</div>{{-- end main --}}
</div>{{-- end body-row --}}

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

{{-- MODAL --}}
<div class="um-overlay" x-show="modalOpen" style="display:none" @click.self="closeModal()" x-transition>
    <div class="um-modal" x-transition>
        <div class="um-modal-head">
            <div class="um-modal-title" x-text="editId ? 'Edit User' : 'Add New User'"></div>
            <div class="um-modal-sub" x-text="editId ? 'Update user details and role assignment' : 'Create a new system user and assign their role'"></div>
        </div>
        <div class="um-modal-body">
            <div class="um-modal-grid">
                <div class="um-field">
                    <label>Full Name *</label>
                    <input type="text" x-model="form.name" placeholder="e.g. John Doe">
                </div>
                <div class="um-field">
                    <label>Email Address *</label>
                    <input type="email" x-model="form.email" placeholder="user@example.com">
                </div>
            </div>
            <div class="um-modal-grid">
                <div class="um-field">
                    <label x-text="editId ? 'New Password (leave blank to keep)' : 'Password *'"></label>
                    <input type="password" x-model="form.password" placeholder="Min 8 characters">
                </div>
                <div class="um-field">
                    <label>Portal Role *</label>
                    <select x-model="form.portal_role">
                        <option value="user">User</option>
                        <option value="admin">Administrator</option>
                        <option value="mock_headteacher">Mock Headteacher</option>
                        <option value="mock_dao">Mock DAO</option>
                        <option value="mock_rao">Mock RAO</option>
                        <option value="subject_panel_leader">Subject Panel Leader</option>
                    </select>
                </div>
            </div>
            <div class="um-modal-grid">
                <div class="um-field">
                    <label>System Role</label>
                    <select x-model="form.role_id">
                        <option value="">— No specific role —</option>
                        <template x-for="r in roles" :key="r.id">
                            <option :value="r.id" x-text="r.name"></option>
                        </template>
                    </select>
                </div>
                <div class="um-field">
                    <label>Account Status *</label>
                    <select x-model="form.status">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
            </div>
            <div x-show="formError" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:10px 14px;color:#fca5a5;font-size:.82rem;" x-text="formError"></div>
        </div>
        <div class="um-modal-foot">
            <button class="btn-cancel" @click="closeModal()">Cancel</button>
            <button class="btn-save" @click="saveUser()" :disabled="saving">
                <span x-show="!saving" x-text="editId ? 'Update User' : 'Create User'"></span>
                <span x-show="saving"><i class="fas fa-spinner fa-spin"></i> Saving…</span>
            </button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div class="um-toast" id="um-toast"></div>

</div>{{-- end shell --}}

<script>
    // Disable Developer Tools for non-admins
    (function() {
        @if(!(auth()->check() && auth()->user()->isAdmin()))
            document.addEventListener('contextmenu', event => event.preventDefault());
            document.onkeydown = function(e) {
                if (e.keyCode == 123) return false;
                if (e.ctrlKey && e.shiftKey && (e.keyCode == 'I'.charCodeAt(0) || e.keyCode == 'J'.charCodeAt(0) || e.keyCode == 'C'.charCodeAt(0))) return false;
                if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
            };
        @endif
    })();

    function tick(){
    const n=new Date();
    document.getElementById('um-clock').textContent=n.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
    document.getElementById('um-date').textContent=n.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
}
tick(); setInterval(tick,1000);

function userManager() {
    return {
        users: [], filtered: [], roles: [],
        search: '', filterRole: '', filterStatus: '',
        loading: true, saving: false,
        modalOpen: false, editId: null, formError: '',
        currentPage: 1, lastPage: 1, totalUsers: 0,
        stats: { total: 0, admins: 0, active: 0, suspended: 0 },
        form: { name: '', email: '', password: '', portal_role: 'user', role_id: '', status: 'active' },

        async init() {
            await Promise.all([this.loadUsers(), this.loadRoles()]);
        },

        async loadUsers(page = 1) {
            if (page < 1 || (this.lastPage && page > this.lastPage)) return;
            this.loading = true;
            this.currentPage = page;
            try {
                const params = new URLSearchParams({
                    page: page,
                    search: this.search,
                    role_id: this.filterRole,
                    status: this.filterStatus
                });
                const r = await fetch('/admin/api/users?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const d = await r.json();
                this.users = d.data || [];
                this.filtered = this.users;
                this.lastPage = d.last_page || 1;
                this.totalUsers = d.total || 0;
                this.calcStats();
            } finally { this.loading = false; }
        },

        async loadRoles() {
            const r = await fetch('/admin/api/roles', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const d = await r.json();
            this.roles = d.data || [];
        },

        filterUsers() {
            // Handled server-side now
        },

        calcStats() {
            // For total we use the server total
            this.stats.total = this.totalUsers;
            // For other stats, if we want them accurate for the WHOLE system, 
            // we'd need another API call or return them in the pagination response.
            // For now, let's just keep them updated based on the current page's data 
            // OR we can just show the total.
            this.stats.admins = this.users.filter(u => u.portal_role === 'admin').length;
            this.stats.active = this.users.filter(u => u.status === 'active').length;
            this.stats.suspended = this.users.filter(u => u.status === 'suspended').length;
        },

        openModal() {
            this.editId = null;
            this.form = { name: '', email: '', password: '', portal_role: 'user', role_id: '', status: 'active' };
            this.formError = '';
            this.modalOpen = true;
        },

        openEdit(u) {
            this.editId = u.id;
            this.form = { name: u.name, email: u.email, password: '', portal_role: u.portal_role, role_id: u.role_id || '', status: u.status };
            this.formError = '';
            this.modalOpen = true;
        },

        closeModal() { this.modalOpen = false; },

        async saveUser() {
            this.formError = '';
            if (!this.form.name || !this.form.email) { this.formError = 'Name and email are required.'; return; }
            if (!this.editId && !this.form.password) { this.formError = 'Password is required for new users.'; return; }
            this.saving = true;
            try {
                const url = this.editId ? `/admin/api/users/${this.editId}` : '/admin/api/users';
                const method = this.editId ? 'PUT' : 'POST';
                const r = await fetch(url, {
                    method,
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                    },
                    body: JSON.stringify(this.form)
                });
                const d = await r.json();
                if (!r.ok) { this.formError = d.message || Object.values(d.errors || {}).flat().join(' '); return; }
                this.closeModal();
                this.toast(d.message || 'Saved!', true);
                await this.loadUsers(this.editId ? this.currentPage : 1);
            } catch(e) { this.formError = 'Network error. Please try again.'; }
            finally { this.saving = false; }
        },

        async deleteUser(u) {
            if (!confirm(`Delete user "${u.name}"? This cannot be undone.`)) return;
            const r = await fetch(`/admin/api/users/${u.id}`, {
                method: 'DELETE',
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content 
                }
            });
            const d = await r.json();
            if (r.ok) { this.toast(d.message, true); await this.loadUsers(); }
            else this.toast(d.message || 'Failed to delete', false);
        },

        toast(msg, ok) {
            const t = document.getElementById('um-toast');
            t.textContent = msg;
            t.className = 'um-toast ' + (ok ? 'toast-ok' : 'toast-err');
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 3000);
        }
    };
}
</script>
</body>
</html>
