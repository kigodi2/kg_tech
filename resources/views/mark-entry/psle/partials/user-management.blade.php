                <div class="psle-user-management-shell psle-admin-dark-theme">
    <style>
        /* Primary Portal Font Application */
        .psle-user-management-shell,
        .psle-user-management-shell input,
        .psle-user-management-shell select,
        .psle-user-management-shell button,
        .psle-user-management-shell textarea,
        .psle-user-management-shell a {
            font-family: 'Maiandra GD', 'Segoe UI', sans-serif !important;
        }

        /* Scoped Premium PSLE User Management Styles */
        .psle-user-management-shell .psle-search-input {
            width: 100%;
            height: 46px;
            padding: 0 14px 0 38px !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.04) !important;
            color: #ffffff !important;
            font-size: 14px;
            box-shadow: none !important;
            outline: none !important;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .psle-user-management-shell .psle-search-input:focus {
            border-color: #00a3dd !important;
            box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
        }
        
        .psle-user-management-shell .psle-action-btn {
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
            white-space: nowrap !important;
            cursor: pointer;
            border: none !important;
        }
        .psle-user-management-shell .psle-action-btn:hover {
            transform: translateY(-1px);
        }
        .psle-user-management-shell .psle-action-btn:active {
            transform: translateY(0px);
        }
        
        .psle-user-management-shell .psle-action-btn-blue {
            background: linear-gradient(135deg, #00a3dd, #006fa3) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(0, 163, 221, 0.25) !important;
        }
        .psle-user-management-shell .psle-action-btn-blue:hover {
            background: linear-gradient(135deg, #00b4f0, #008cc2) !important;
            box-shadow: 0 6px 16px rgba(0, 163, 221, 0.35) !important;
        }
        
        .psle-user-management-shell .psle-action-btn-dark {
            background: rgba(255, 255, 255, 0.04) !important;
            color: #f0f4f7 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .psle-user-management-shell .psle-action-btn-dark:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
        }
        
        .psle-user-management-shell .psle-action-btn-green {
            background: linear-gradient(135deg, #16a34a, #15803d) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25) !important;
        }
        .psle-user-management-shell .psle-action-btn-green:hover {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.35) !important;
        }

        /* Overlay Modals */
        .psle-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 17, 23, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 16px;
        }
        .psle-modal-card {
            width: min(860px, calc(100vw - 32px)) !important;
            max-width: 860px !important;
            overflow: hidden;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: #101518 !important;
            color: #f0f4f7 !important;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6) !important;
            display: flex;
            flex-direction: column;
            text-align: left;
        }
        .psle-modal-header {
            position: relative;
            overflow: hidden;
            padding: 24px 28px;
            border-bottom: 1px solid rgba(187, 164, 94, 0.15) !important;
            background: linear-gradient(135deg, #0d1b2a 0%, #11202e 100%) !important;
            color: #ffffff;
            flex-shrink: 0;
        }
        .psle-modal-header::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            right: -80px;
            top: -120px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(252, 209, 22, 0.1) 0%, rgba(252, 209, 22, 0) 70%);
            pointer-events: none;
        }
        .psle-modal-header-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }
        .psle-modal-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.73rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            white-space: nowrap !important;
        }
        .psle-modal-title {
            margin: 16px 0 0 !important;
            font-size: 1.85rem !important;
            font-weight: 800 !important;
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: #ffffff !important;
        }
        .psle-modal-subtitle {
            margin: 10px 0 0;
            max-width: 680px !important;
            line-height: 1.6 !important;
            white-space: normal !important;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.92rem;
        }
        .psle-modal-close {
            display: inline-flex;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.82);
            font-size: 1.4rem;
            line-height: 1;
            cursor: pointer;
            transition: background 160ms ease, color 160ms ease, transform 160ms ease;
        }
        .psle-modal-close:hover {
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            transform: translateY(-1px);
        }
        .psle-modal-body {
            background: #0f1117 !important;
            padding: 24px 28px 28px;
            overflow-y: auto;
            flex-grow: 1;
        }
        .psle-modal-panel {
            border: 1px solid rgba(255, 255, 255, 0.06) !important;
            border-radius: 22px;
            background: #161b22 !important;
            box-shadow: 0 18px 32px rgba(0,0,0,0.3);
            padding: 24px;
        }
        
        /* Prevent long text wrapping or breaking select boxes */
        .psle-modal-panel label,
        .psle-user-management-shell .adm-filter-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.5) !important;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap !important;
        }
        .psle-modal-panel input[type="text"],
        .psle-modal-panel input[type="email"],
        .psle-modal-panel input[type="password"],
        .psle-modal-panel select {
            width: 100% !important;
            min-width: 0 !important;
            height: 46px;
            padding: 0 14px !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 8px !important;
            background: rgba(255, 255, 255, 0.04) !important;
            color: #ffffff !important;
            font-size: 14px;
            box-shadow: none !important;
            outline: none !important;
            transition: border-color 0.2s, box-shadow 0.2s;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            white-space: nowrap !important;
        }
        .psle-modal-panel input:focus,
        .psle-modal-panel select:focus {
            border-color: #00a3dd !important;
            box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
        }
        .psle-modal-panel select option {
            background: #161b22 !important;
            color: #ffffff !important;
        }

        /* 3-Column Responsive Grid Form Layout */
        .psle-user-management-shell .psle-form-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 14px !important;
            width: 100% !important;
        }
        @media (max-width: 900px) {
            .psle-user-management-shell .psle-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
        @media (max-width: 640px) {
            .psle-user-management-shell .psle-form-grid {
                grid-template-columns: 1fr !important;
            }
        }

        /* Modal Footer Action Buttons formatting */
        .psle-user-management-shell .psle-modal-actions {
            display: flex !important;
            justify-content: flex-end !important;
            align-items: center !important;
            gap: 14px !important;
            flex-wrap: nowrap !important;
            width: 100% !important;
            grid-column: 1 / -1 !important;
            border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding-top: 18px !important;
            margin-top: 18px !important;
        }
        .psle-user-management-shell .psle-modal-actions button,
        .psle-user-management-shell .psle-modal-actions a {
            min-width: max-content !important;
            flex: 0 0 auto !important;
            white-space: nowrap !important;
        }
        @media (max-width: 640px) {
            .psle-user-management-shell .psle-modal-actions {
                flex-wrap: wrap !important;
            }
            .psle-user-management-shell .psle-modal-actions button,
            .psle-user-management-shell .psle-modal-actions a {
                width: 100% !important;
                flex: 1 1 100% !important;
            }
        }

        .psle-modal-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 50px;
            padding: 0 18px;
            border-radius: 18px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease, color 160ms ease;
            text-decoration: none;
            white-space: nowrap !important;
        }
        .psle-modal-button:hover {
            transform: translateY(-1px);
        }
        .psle-modal-button-primary {
            background: linear-gradient(135deg, #00a3dd, #006fa3) !important;
            color: #ffffff !important;
            box-shadow: 0 16px 28px rgba(0, 163, 221, 0.22);
        }
        .psle-modal-button-primary:hover {
            background: linear-gradient(135deg, #00b4f0, #008cc2) !important;
            box-shadow: 0 16px 28px rgba(0, 163, 221, 0.3);
        }
        .psle-modal-button-secondary {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #f0f4f7 !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
        }
        .psle-modal-button-secondary:hover {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
        }
    </style>

    <div class="adm-breadcrumb">
        EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>User Management</span>
    </div>

    <div class="adm-page-header">
        <h1 class="adm-page-title">User Management</h1>
        <p class="adm-page-desc">Create and manage REOs, Supervisors, Verifiers, and Mark Entry Officers for PSLE mark entry operations.</p>
    </div>

    @if(session('user_import_summary'))
        @php($summary = session('user_import_summary'))
        <div class="adm-card" style="border-color: rgba(252,209,22,.25); background: rgba(252,209,22,.06);">
            <div class="adm-card-body" style="padding: 16px 20px; display:flex; justify-content:space-between; gap:14px; align-items:center; flex-wrap:wrap;">
                <div style="color: var(--tz-text); font-size: .9rem;">
                    <strong>CSV Import Summary:</strong>
                    Users created: <strong style="color:var(--tz-green);">{{ $summary['created'] ?? 0 }}</strong> |
                    Skipped duplicates: <strong style="color:var(--tz-yellow);">{{ $summary['duplicates'] ?? 0 }}</strong> |
                    Failed rows: <strong style="color:#fca5a5;">{{ $summary['failed'] ?? 0 }}</strong>
                </div>
                @if(!empty($summary['error_report']))
                    <a class="btn btn-outline btn-sm" href="{{ url('/mark-entry/psle/users/import-errors/' . $summary['error_report']) }}">
                        <i class="fas fa-file-csv"></i> Download Error Report
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="adm-stats">
        <div class="adm-stat">
            <div class="adm-stat-label">Total Users</div>
            <div class="adm-stat-value" style="color: #fff;">{{ $userCounts['total'] ?? 0 }}</div>
            <i class="fas fa-users adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">REOs</div>
            <div class="adm-stat-value" style="color: var(--tz-green);">{{ $userCounts['reos'] ?? 0 }}</div>
            <i class="fas fa-user-tie adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">Supervisors</div>
            <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ $userCounts['supervisors'] ?? 0 }}</div>
            <i class="fas fa-user-shield adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">Mark Entry Officers</div>
            <div class="adm-stat-value" style="color: var(--tz-blue);">{{ $userCounts['officers'] ?? 0 }}</div>
            <i class="fas fa-keyboard adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">PSLE Subject Panel</div>
            <div class="adm-stat-value" style="color: #c084fc;">{{ $userCounts['panelLeaders'] ?? 0 }}</div>
            <i class="fas fa-book adm-stat-icon"></i>
        </div>
    </div>

    <!-- Create User Modal (Initially Hidden) -->
    <div id="createUserCard" class="psle-modal-overlay" style="display: none;" onclick="if(event.target === this) toggleCreateUser()">
        <div class="psle-modal-card" onclick="event.stopPropagation()">
            <div class="psle-modal-header">
                <div class="psle-modal-header-content">
                    <div>
                        <span class="psle-modal-kicker">
                            <i class="fas fa-user-plus text-amber-300"></i> PSLE User Management
                        </span>
                        <h2 class="psle-modal-title">Create New User</h2>
                        <p class="psle-modal-subtitle">Create REOs, Supervisors, Verifiers, and Mark Entry Officers with precise role and regional assignments.</p>
                    </div>
                    <button type="button" onclick="toggleCreateUser()" class="psle-modal-close">&times;</button>
                </div>
            </div>
            <div class="psle-modal-body">
                <div class="psle-modal-panel">
                    <form method="POST" action="{{ url('/mark-entry/psle/users/create') }}" class="psle-form-grid adm-filters" style="border-bottom: none; background: transparent; padding: 0;">
                        @csrf
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Full Name" required>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Phone (Optional)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Phone Number">
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Password Option</label>
                            <select name="password_mode" id="passwordMode" onchange="togglePasswordFields()">
                                <option value="auto" {{ old('password_mode', 'auto') === 'auto' ? 'selected' : '' }}>Auto-generate password</option>
                                <option value="manual" {{ old('password_mode') === 'manual' ? 'selected' : '' }}>Manually set password</option>
                            </select>
                        </div>
                        <div class="adm-filter-group password-field">
                            <label class="adm-filter-label">Password</label>
                            <input type="password" name="password" placeholder="Password">
                        </div>
                        <div class="adm-filter-group password-field">
                            <label class="adm-filter-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" placeholder="Confirm Password">
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Role</label>
                            <select name="role_id" required>
                                <option value="">Select Role</option>
                                @foreach($roles ?? [] as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Region</label>
                            <select name="region_id" id="createUserRegionSelect">
                                <option value="">Select Region (Optional)</option>
                                @foreach($regions ?? [] as $reg)
                                    <option value="{{ $reg->id }}" {{ old('region_id') == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Council/District</label>
                            <select name="district_council_id" id="createUserCouncilSelect">
                                <option value="">Select Council (Optional)</option>
                                @foreach($districtCouncils ?? [] as $council)
                                    <option value="{{ $council->id }}" data-region-id="{{ $council->region_id }}" data-fullname="{{ $council->name }} ({{ $council->region->name ?? 'N/A' }})" data-shortname="{{ $council->name }}" {{ old('district_council_id') == $council->id ? 'selected' : '' }}>{{ $council->name }} ({{ $council->region->name ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Marking Centre</label>
                            <select name="marking_centre_id" id="createUserMarkingCentreSelect">
                                <option value="">Select Centre (Optional)</option>
                                @foreach($markingCentres ?? [] as $mc)
                                    <option value="{{ $mc->id }}" data-region-id="{{ $mc->region_id }}" data-fullname="{{ $mc->name }} ({{ $mc->region->name ?? 'N/A' }})" data-shortname="{{ $mc->name }}" {{ old('marking_centre_id') == $mc->id ? 'selected' : '' }}>{{ $mc->name }} ({{ $mc->region->name ?? 'N/A' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Status</label>
                            <select name="status" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="adm-filter-group" style="display:flex; align-items:center; gap:8px; padding-top:22px;">
                            <input type="hidden" name="force_password_reset" value="0">
                            <input type="checkbox" id="forcePasswordReset" name="force_password_reset" value="1" checked style="width:16px;height:16px; margin: 0; cursor: pointer;">
                            <label for="forcePasswordReset" style="font-size:.78rem;color:var(--tz-text-muted);font-weight:700; margin: 0; cursor: pointer;">Force reset on first login</label>
                        </div>
                        <div class="psle-modal-actions">
                            <button type="button" class="psle-modal-button psle-modal-button-secondary" onclick="toggleCreateUser()">Cancel</button>
                            <button type="submit" class="psle-modal-button psle-modal-button-primary"><i class="fas fa-user-plus"></i> Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Import CSV Modal (Initially Hidden) -->
    <div id="importUserCard" class="psle-modal-overlay" style="display: none;" onclick="if(event.target === this) toggleImportUsers()">
        <div class="psle-modal-card" onclick="event.stopPropagation()">
            <div class="psle-modal-header">
                <div class="psle-modal-header-content">
                    <div>
                        <span class="psle-modal-kicker">
                            <i class="fas fa-file-import text-amber-300"></i> CSV Bulk Operations
                        </span>
                        <h2 class="psle-modal-title">Import PSLE Users</h2>
                        <p class="psle-modal-subtitle">Upload a comma-separated CSV scoresheet of REOs, Supervisors, Verifiers, and officers in bulk.</p>
                    </div>
                    <button type="button" onclick="toggleImportUsers()" class="psle-modal-close">&times;</button>
                </div>
            </div>
            <div class="psle-modal-body">
                <div class="psle-modal-panel">
                    <form method="POST" action="{{ url('/mark-entry/psle/users/import') }}" enctype="multipart/form-data" class="adm-filters" style="grid-template-columns: 1fr; border-bottom: none; background: transparent; padding: 0;">
                        @csrf
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">CSV File</label>
                            <input type="file" name="users_csv" accept=".csv,text/csv" required style="height:auto; padding:12px 14px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); color: #fff; border-radius: 8px; width: 100%;">
                        </div>
                        <div class="psle-modal-actions" style="display: flex; gap: 14px; width: 100%;">
                            <button type="button" class="psle-modal-button psle-modal-button-secondary" onclick="toggleImportUsers()" style="flex: 1;">Cancel</button>
                            <a class="psle-modal-button psle-modal-button-secondary" href="{{ url('/mark-entry/psle/users/template') }}" style="flex: 1; text-decoration: none;"><i class="fas fa-download"></i> Download CSV Template</a>
                            <button type="submit" class="psle-modal-button psle-modal-button-primary" style="flex: 1;"><i class="fas fa-upload"></i> Import CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="adm-card">
        <div class="adm-card-head" style="align-items: center; padding: 18px 22px;">
            <div class="adm-card-title">User List</div>
            <div class="psle-toolbar-grid flex flex-wrap items-center gap-3" style="margin-left: auto;">
                <form method="GET" action="{{ url('/mark-entry/psle') }}" style="min-width: 240px; display: inline-block; position: relative; margin: 0;">
                    <input type="hidden" name="view" value="user-management">
                    <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(255, 255, 255, 0.4); font-size: 14px; pointer-events: none;"></i>
                    <input type="text" name="user_search" id="userSearchInput" class="psle-search-input" value="{{ request('user_search') }}" placeholder="Search users..." style="padding-left: 38px !important;">
                </form>
                <a class="psle-action-btn psle-action-btn-dark" href="{{ url('/mark-entry/psle/users/template') }}">
                    <i class="fas fa-download"></i>
                    <span>CSV Template</span>
                </a>
                <button type="button" class="psle-action-btn psle-action-btn-blue" onclick="toggleImportUsers()">
                    <i class="fas fa-file-import"></i>
                    <span>Import CSV</span>
                </button>
                <button type="button" class="psle-action-btn psle-action-btn-green" onclick="toggleCreateUser()">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </button>
            </div>
        </div>
        <div class="adm-card-body table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Region</th>
                        <th>Council</th>
                        <th>Marking Centre</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($portalUsers ?? [] as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>
                            @if($u->portal_role === 'subject_panel_leader')
                                PSLE Subject Panel
                            @elseif($u->role)
                                {{ $u->role->name }}
                            @elseif($u->portal_role === 'mock_headteacher')
                                Headteacher
                            @elseif($u->portal_role)
                                {{ ucfirst(str_replace('_', ' ', $u->portal_role)) }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $u->region ? $u->region->name : ($u->portal_role === 'subject_panel_leader' ? 'All Regions' : 'N/A') }}</td>
                        <td>{{ $u->portal_role === 'subject_panel_leader' ? 'N/A' : ($u->council ? $u->council->name : 'N/A') }}</td>
                        <td>{{ $u->portal_role === 'subject_panel_leader' ? 'N/A' : ($u->markingCentre ? $u->markingCentre->name : 'N/A') }}</td>
                        <td class="text-center">
                            <span class="badge {{ $u->status === 'active' ? 'badge-green' : 'badge-red' }}">{{ ucfirst($u->status) }}</span>
                        </td>
                        <td class="text-right">
                            <form method="POST" action="{{ url('/mark-entry/psle/users/' . $u->id . '/toggle-status') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-action" title="{{ $u->status === 'active' ? 'Deactivate' : 'Activate' }}" style="background: none; border: none; color: var(--tz-text-muted); cursor: pointer;">
                                    <i class="fas {{ $u->status === 'active' ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-state-row">
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-cog empty-icon"></i>
                                <div class="empty-title">No Users Found</div>
                                <div class="empty-desc">No mark entry users have been created yet. Click "Add User" to create one.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
            <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
                Showing {{ $portalUsers->firstItem() ?? 0 }} to {{ $portalUsers->lastItem() ?? 0 }} of {{ $portalUsers->total() ?? 0 }} results
            </div>
            <div class="pagination-links" style="display: flex; gap: 8px;">
                @if ($portalUsers->onFirstPage())
                    <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;"><i class="fas fa-chevron-left"></i> Previous</span>
                @else
                    <a href="{{ $portalUsers->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
                @endif

                @if ($portalUsers->hasMorePages())
                    <a href="{{ $portalUsers->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
                @else
                    <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;">Next <i class="fas fa-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCreateUser() {
        const card = document.getElementById('createUserCard');
        if (card.style.display === 'none') {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    }

    function toggleImportUsers() {
        const card = document.getElementById('importUserCard');
        if (card.style.display === 'none') {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    }

    function togglePasswordFields() {
        const mode = document.getElementById('passwordMode')?.value || 'auto';
        document.querySelectorAll('.password-field').forEach(field => {
            field.style.display = mode === 'manual' ? 'block' : 'none';
        });
    }

    togglePasswordFields();

    document.getElementById('userSearchInput').addEventListener('keyup', function() {
        clearTimeout(this.dataset.searchTimer);
        this.dataset.searchTimer = setTimeout(() => this.form.submit(), 500);
    });

    // Region-based Council and Marking Centre filtering
    let allCouncilOptions = [];
    let allMarkingCentreOptions = [];

    document.addEventListener('DOMContentLoaded', function() {
        const councilSelect = document.getElementById('createUserCouncilSelect');
        const markingCentreSelect = document.getElementById('createUserMarkingCentreSelect');
        
        if (councilSelect) {
            allCouncilOptions = Array.from(councilSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                regionId: opt.getAttribute('data-region-id') || '',
                fullname: opt.getAttribute('data-fullname') || opt.textContent,
                shortname: opt.getAttribute('data-shortname') || opt.textContent
            }));
        }
        
        if (markingCentreSelect) {
            allMarkingCentreOptions = Array.from(markingCentreSelect.options).map(opt => ({
                value: opt.value,
                text: opt.textContent,
                regionId: opt.getAttribute('data-region-id') || '',
                fullname: opt.getAttribute('data-fullname') || opt.textContent,
                shortname: opt.getAttribute('data-shortname') || opt.textContent
            }));
        }
        
        const regionSelect = document.getElementById('createUserRegionSelect');
        if (regionSelect) {
            regionSelect.addEventListener('change', function() {
                // Clear selected values on change
                const cSelect = document.getElementById('createUserCouncilSelect');
                const mSelect = document.getElementById('createUserMarkingCentreSelect');
                if (cSelect) cSelect.value = '';
                if (mSelect) mSelect.value = '';
                filterCouncilsAndCentres(true);
            });
            // Initial filter run on page load
            filterCouncilsAndCentres(false);
        }
    });

    function filterCouncilsAndCentres(isUserChange = false) {
        const regionSelect = document.getElementById('createUserRegionSelect');
        const councilSelect = document.getElementById('createUserCouncilSelect');
        const markingCentreSelect = document.getElementById('createUserMarkingCentreSelect');
        
        if (!regionSelect || !councilSelect || !markingCentreSelect) return;
        
        const selectedRegionId = regionSelect.value;
        
        // Capture current selected values
        const currentCouncilVal = councilSelect.value;
        const currentMarkingCentreVal = markingCentreSelect.value;
        
        // Clear all options
        councilSelect.innerHTML = '';
        markingCentreSelect.innerHTML = '';
        
        // 1. Re-populate Councils
        const cDefaultOpt = document.createElement('option');
        cDefaultOpt.value = '';
        cDefaultOpt.textContent = 'Select Council (Optional)';
        councilSelect.appendChild(cDefaultOpt);
        
        let cFound = false;
        allCouncilOptions.forEach(opt => {
            if (opt.value !== '') {
                if (!selectedRegionId) {
                    // No region selected: show all councils with full name
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.fullname;
                    if (opt.value === currentCouncilVal) {
                        newOpt.selected = true;
                        cFound = true;
                    }
                    councilSelect.appendChild(newOpt);
                } else if (opt.regionId === selectedRegionId) {
                    // Region selected: show only matching councils with clean/short name
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.shortname;
                    if (opt.value === currentCouncilVal) {
                        newOpt.selected = true;
                        cFound = true;
                    }
                    councilSelect.appendChild(newOpt);
                }
            }
        });
        
        if (!cFound) {
            councilSelect.value = '';
        }
        
        // 2. Re-populate Marking Centres
        const mDefaultOpt = document.createElement('option');
        mDefaultOpt.value = '';
        mDefaultOpt.textContent = 'Select Centre (Optional)';
        markingCentreSelect.appendChild(mDefaultOpt);
        
        let mFound = false;
        allMarkingCentreOptions.forEach(opt => {
            if (opt.value !== '') {
                if (!selectedRegionId) {
                    // No region selected: show all marking centres with full name
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.fullname;
                    if (opt.value === currentMarkingCentreVal) {
                        newOpt.selected = true;
                        mFound = true;
                    }
                    markingCentreSelect.appendChild(newOpt);
                } else if (opt.regionId === selectedRegionId) {
                    // Region selected: show only matching marking centres with clean/short name
                    const newOpt = document.createElement('option');
                    newOpt.value = opt.value;
                    newOpt.textContent = opt.shortname;
                    if (opt.value === currentMarkingCentreVal) {
                        newOpt.selected = true;
                        mFound = true;
                    }
                    markingCentreSelect.appendChild(newOpt);
                }
            }
        });
        
        if (!mFound) {
            markingCentreSelect.value = '';
        }
    }
</script>

