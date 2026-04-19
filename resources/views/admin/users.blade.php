@extends('layout')
@section('content')
<style>
.um-shell{display:flex;min-height:100vh;background:#0f1117;font-family:'Maiandra GD',sans-serif;}
.um-sidebar{width:260px;min-height:100vh;background:linear-gradient(180deg,#0d1b2a,#11202e);border-right:1px solid rgba(187,164,94,.18);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;flex-shrink:0;}
.um-profile{padding:28px 20px 22px;border-bottom:1px solid rgba(187,164,94,.15);background:linear-gradient(135deg,rgba(187,164,94,.08),transparent);}
.um-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#BBA45E,#8a7340);display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#0d1b2a;margin-bottom:12px;box-shadow:0 0 0 3px rgba(187,164,94,.25);}
.um-name{font-size:.97rem;font-weight:700;color:#f0e6c8;}
.um-role-badge{display:inline-flex;align-items:center;gap:5px;margin-top:6px;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;background:rgba(187,164,94,.15);color:#BBA45E;padding:3px 10px;border-radius:20px;border:1px solid rgba(187,164,94,.3);}
.um-nav{padding:14px 12px;flex:1;}
.um-nav-label{font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(187,164,94,.55);padding:6px 8px 4px;margin-top:10px;}
.um-nav a{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:10px;color:rgba(255,255,255,.65);font-size:.875rem;font-weight:500;text-decoration:none;transition:all .18s;margin-bottom:2px;}
.um-nav a:hover,.um-nav a.active{background:rgba(187,164,94,.12);color:#f0e6c8;}
.um-nav a.active{background:rgba(187,164,94,.18);}
.nav-ico{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.8rem;background:rgba(255,255,255,.06);flex-shrink:0;}
.um-nav a:hover .nav-ico,.um-nav a.active .nav-ico{background:rgba(187,164,94,.2);color:#BBA45E;}
.um-footer{padding:16px;border-top:1px solid rgba(187,164,94,.15);}
.um-logout{display:flex;align-items:center;gap:8px;width:100%;padding:9px 14px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;color:#fca5a5;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .18s;text-decoration:none;}
.um-logout:hover{background:rgba(239,68,68,.2);color:#fff;}
.um-main{flex:1;display:flex;flex-direction:column;min-width:0;}
.um-topbar{background:rgba(15,17,23,.95);border-bottom:1px solid rgba(187,164,94,.15);padding:16px 28px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:10;}
.um-topbar-title{font-size:1.25rem;font-weight:700;color:#f0e6c8;}
.um-topbar-sub{font-size:.8rem;color:rgba(255,255,255,.4);margin-top:1px;}
.um-badge{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#BBA45E,#8a7340);display:flex;align-items:center;justify-content:center;color:#0d1b2a;font-weight:700;font-size:.9rem;}
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
.um-table-wrap{background:#161c26;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;}
.um-table{width:100%;border-collapse:collapse;}
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
</style>

<div class="um-shell" x-data="userManager()" x-init="init()">

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
        <a href="/admin/exam-types"><span class="nav-ico"><i class="fas fa-tags"></i></span> Exam Types</a>
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
        <div class="um-badge">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
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
                <input class="um-search" type="text" placeholder="Search by name or email…" x-model="search" @input="filterUsers()">
            </div>
            <select class="um-filter" x-model="filterRole" @change="filterUsers()">
                <option value="">All Roles</option>
                <template x-for="r in roles" :key="r.id">
                    <option :value="r.id" x-text="r.name"></option>
                </template>
            </select>
            <select class="um-filter" x-model="filterStatus" @change="filterUsers()">
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
                            <td x-text="i+1" style="color:rgba(255,255,255,.3);"></td>
                            <td class="name" x-text="u.name"></td>
                            <td x-text="u.email"></td>
                            <td>
                                <span :class="u.portal_role==='admin'?'role-chip chip-admin':'role-chip chip-user'" x-text="u.portal_role==='admin'?'Admin':'User'"></span>
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

    </div>{{-- end content --}}
</div>{{-- end main --}}

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
function userManager() {
    return {
        users: [], filtered: [], roles: [],
        search: '', filterRole: '', filterStatus: '',
        loading: true, saving: false,
        modalOpen: false, editId: null, formError: '',
        stats: { total: 0, admins: 0, active: 0, suspended: 0 },
        form: { name: '', email: '', password: '', portal_role: 'user', role_id: '', status: 'active' },

        async init() {
            await Promise.all([this.loadUsers(), this.loadRoles()]);
        },

        async loadUsers() {
            this.loading = true;
            try {
                const r = await fetch('/admin/api/users');
                const d = await r.json();
                this.users = d.data || [];
                this.filterUsers();
                this.calcStats();
            } finally { this.loading = false; }
        },

        async loadRoles() {
            const r = await fetch('/admin/api/roles');
            const d = await r.json();
            this.roles = d.data || [];
        },

        filterUsers() {
            let list = this.users;
            if (this.search) list = list.filter(u => u.name.toLowerCase().includes(this.search.toLowerCase()) || u.email.toLowerCase().includes(this.search.toLowerCase()));
            if (this.filterRole) list = list.filter(u => String(u.role_id) === String(this.filterRole));
            if (this.filterStatus) list = list.filter(u => u.status === this.filterStatus);
            this.filtered = list;
        },

        calcStats() {
            this.stats.total = this.users.length;
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
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.form)
                });
                const d = await r.json();
                if (!r.ok) { this.formError = d.message || Object.values(d.errors || {}).flat().join(' '); return; }
                this.closeModal();
                this.toast(d.message || 'Saved!', true);
                await this.loadUsers();
            } catch(e) { this.formError = 'Network error. Please try again.'; }
            finally { this.saving = false; }
        },

        async deleteUser(u) {
            if (!confirm(`Delete user "${u.name}"? This cannot be undone.`)) return;
            const r = await fetch(`/admin/api/users/${u.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
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
@endsection
