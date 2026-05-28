<div class="psle-marking-centres-shell psle-admin-dark-theme">
    <style>
        /* Primary Portal Font Application */
        .psle-marking-centres-shell,
        .psle-marking-centres-shell input,
        .psle-marking-centres-shell select,
        .psle-marking-centres-shell button,
        .psle-marking-centres-shell textarea,
        .psle-marking-centres-shell a {
            font-family: 'Maiandra GD', 'Segoe UI', sans-serif !important;
        }

        /* Scoped Premium PSLE Marking Centres Styles */
        .psle-marking-centres-shell .psle-form-input {
            width: 100%;
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
            /* Allow natural cursor navigation and typed text visibility */
            text-overflow: clip !important;
            overflow: visible !important;
            white-space: normal !important;
        }

        .psle-marking-centres-shell .psle-form-select {
            width: 100%;
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
        .psle-marking-centres-shell .psle-form-input:focus,
        .psle-marking-centres-shell .psle-form-select:focus {
            border-color: #00a3dd !important;
            box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
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
            width: min(80vw, calc(100vw - 32px)) !important;
            max-width: 80vw !important;
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
        .psle-modal-card label,
        .psle-modal-card button {
            white-space: nowrap !important;
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
        .psle-modal-panel label {
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

        /* Form Grid Layout */
        .psle-marking-centres-shell .psle-form-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
            gap: 14px !important;
            width: 100% !important;
        }

        .psle-marking-centres-shell .psle-centre-form-grid {
            display: grid !important;
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            gap: 14px !important;
            width: 100% !important;
        }
        .psle-marking-centres-shell .psle-span-1 {
            grid-column: span 1 !important;
        }
        .psle-marking-centres-shell .psle-span-2 {
            grid-column: span 2 !important;
        }
        .psle-marking-centres-shell .psle-span-3 {
            grid-column: span 3 !important;
        }

        @media (max-width: 900px) {
            .psle-marking-centres-shell .psle-centre-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
            .psle-marking-centres-shell .psle-span-1 {
                grid-column: span 1 !important;
            }
            .psle-marking-centres-shell .psle-span-2,
            .psle-marking-centres-shell .psle-span-3 {
                grid-column: span 2 !important;
            }
        }

        @media (max-width: 640px) {
            .psle-marking-centres-shell .psle-centre-form-grid {
                grid-template-columns: 1fr !important;
            }
            .psle-marking-centres-shell .psle-span-1,
            .psle-marking-centres-shell .psle-span-2,
            .psle-marking-centres-shell .psle-span-3 {
                grid-column: span 1 !important;
            }
            .psle-marking-centres-shell .psle-modal-actions {
                flex-wrap: wrap !important;
            }
            .psle-marking-centres-shell .psle-modal-actions button {
                width: 100% !important;
                flex: 1 1 100% !important;
            }
        }
        
        .psle-marking-centres-shell .psle-modal-actions {
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
        .psle-marking-centres-shell .psle-modal-actions button {
            min-width: max-content !important;
            flex: 0 0 auto !important;
            white-space: nowrap !important;
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

        .psle-action-btn-green {
            background: linear-gradient(135deg, #16a34a, #15803d) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25) !important;
            height: 46px;
            padding: 0 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .psle-action-btn-green:hover {
            background: linear-gradient(135deg, #22c55e, #16a34a) !important;
            box-shadow: 0 6px 16px rgba(22, 163, 74, 0.35) !important;
            transform: translateY(-1px);
        }
        .psle-action-btn-red {
            background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25) !important;
            height: 46px;
            padding: 0 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .psle-action-btn-red:hover {
            background: linear-gradient(135deg, #ef4444, #dc2626) !important;
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.35) !important;
            transform: translateY(-1px);
        }
    </style>

    <div class="adm-breadcrumb">
        EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Marking Centres</span>
    </div>

    <div class="adm-page-header">
        <h1 class="adm-page-title">Marking Centre Management</h1>
        <p class="adm-page-desc">Define and manage regional marking centres for TASIDO PSLE mark entry operations.</p>
    </div>

    <!-- Summary Cards -->
    <div class="adm-stats">
        <div class="adm-stat">
            <div class="adm-stat-label">Total Centres</div>
            <div class="adm-stat-value" style="color: #fff;">{{ count($markingCentres ?? []) }}</div>
            <i class="fas fa-building adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">Active Centres</div>
            <div class="adm-stat-value" style="color: var(--tz-green);">{{ collect($markingCentres ?? [])->where('status', 'active')->count() }}</div>
            <i class="fas fa-check-circle adm-stat-icon"></i>
        </div>
        <div class="adm-stat">
            <div class="adm-stat-label">TASIDO Regions</div>
            <div class="adm-stat-value" style="color: var(--tz-blue);">{{ collect($markingCentres ?? [])->pluck('region_id')->unique()->count() }}</div>
            <i class="fas fa-map-marker-alt adm-stat-icon"></i>
        </div>
    </div>

    @if($isAdmin)
    <!-- Geofencing Global Toggle Control Panel -->
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-head" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 16px 20px;">
            <div class="adm-card-title" style="margin: 0; font-size: 1.1rem; font-weight: 600;">
                <i class="fas fa-shield-alt" style="margin-right: 8px; color: #3b82f6;"></i> Location Restriction Control
            </div>
            <span class="badge {{ $isGeofenceEnabled ? 'badge-green' : 'badge-red' }}" id="geofence-status-badge">
                {{ $isGeofenceEnabled ? 'Enabled' : 'Disabled' }}
            </span>
        </div>
        <div class="adm-card-body" style="padding: 20px;">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
                <div style="flex: 1; min-width: 300px;">
                    <p id="geofence-helper-text" style="color: #9ca3af; font-size: 0.9rem; margin: 0; line-height: 1.5;">
                        @if($isGeofenceEnabled)
                            Mark Entry Officers must verify GPS location and remain within the approved marking centre radius before accessing mark entry.
                        @else
                            Location restriction is currently disabled. Mark Entry Officers can access mark entry without GPS verification. One-device account restriction still applies.
                        @endif
                    </p>
                </div>
                <div>
                    <button type="button" id="geofence-toggle-btn" class="{{ $isGeofenceEnabled ? 'psle-action-btn-red' : 'psle-action-btn-green' }}" onclick="confirmToggleGeofence({{ $isGeofenceEnabled ? 'false' : 'true' }})" style="border-radius: 8px; border: none; font-weight: 700; color: #fff; cursor: pointer;">
                        <i class="fas {{ $isGeofenceEnabled ? 'fa-lock-open' : 'fa-lock' }}"></i>
                        <span id="geofence-btn-text">{{ $isGeofenceEnabled ? 'Disable Location Restriction' : 'Enable Location Restriction' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Centre Form -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title">Add New Marking Centre</div>
        </div>
        <div class="adm-card-body">
            <form method="POST" action="{{ url('/mark-entry/psle/marking-centres/create') }}" class="psle-form-grid adm-filters" style="border-bottom: none; background: transparent; padding: 20px;">
                @csrf
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Centre Name</label>
                    <input type="text" name="name" class="psle-form-input" placeholder="e.g. Dodoma Central" required>
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Centre Code</label>
                    <input type="text" name="code" class="psle-form-input" placeholder="e.g. MC-DOM-01" required>
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Region</label>
                    <select name="region_id" class="psle-form-select" required>
                        <option value="">Select Region</option>
                        @foreach($regions ?? [] as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Location (Optional)</label>
                    <input type="text" name="location" class="psle-form-input" placeholder="District or specific school">
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Latitude</label>
                    <input type="text" name="latitude" id="create_latitude" class="psle-form-input" placeholder="e.g. -6.178945">
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Longitude</label>
                    <input type="text" name="longitude" id="create_longitude" class="psle-form-input" placeholder="e.g. 35.748234">
                </div>
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Radius (Metres)</label>
                    <input type="number" name="allowed_radius_meters" class="psle-form-input" value="50" min="5" placeholder="e.g. 50">
                </div>
                <div class="adm-filter-group" style="display:flex; align-items:flex-end; gap: 8px;">
                    <button type="button" class="btn btn-outline" onclick="useCurrentLocation('create_latitude', 'create_longitude')" style="height:46px; width:100%; border:1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #fff; cursor: pointer;"><i class="fas fa-map-marker-alt"></i> Use My GPS</button>
                </div>
                <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="psle-action-btn-green" style="width:100%;"><i class="fas fa-plus"></i> Add Centre</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Centre List -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title">Regional Centres</div>
        </div>
        <div class="adm-card-body table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Centre Name</th>
                        <th>Region</th>
                        <th>Location</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($markingCentres ?? [] as $mc)
                    <tr>
                        <td><code>{{ $mc->code }}</code></td>
                        <td style="font-weight: 600;">{{ $mc->name }}</td>
                        <td>{{ $mc->region->name ?? 'N/A' }}</td>
                        <td>{{ $mc->location ?? 'Not Specified' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $mc->status === 'active' ? 'badge-green' : 'badge-red' }}">{{ ucfirst($mc->status) }}</span>
                        </td>
                        <td class="text-right">
                            @if($isAdmin)
                            <button type="button" class="btn btn-action" title="Edit Centre" onclick="openEditCentreModal({{ json_encode($mc) }})" style="background: none; border: none; color: #00a3dd; cursor: pointer; margin-right: 8px;">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ url('/mark-entry/psle/marking-centres/' . $mc->id . '/toggle-status') }}" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-action" title="{{ $mc->status === 'active' ? 'Deactivate' : 'Activate' }}" style="background: none; border: none; color: var(--tz-text-muted); cursor: pointer; margin-right: 8px;">
                                    <i class="fas {{ $mc->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-action" title="Delete Centre" onclick="confirmDeleteCentre({{ $mc->id }}, '{{ addslashes($mc->name) }}')" style="background: none; border: none; color: #ef4444; cursor: pointer;">
                                <i class="fas fa-trash"></i>
                            </button>
                            @else
                                <span class="text-muted" style="font-size: 0.8rem;">Read-only</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr class="empty-state-row">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-building empty-icon"></i>
                                <div class="empty-title">No Centres Found</div>
                                <div class="empty-desc">No marking centres have been defined yet.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Centre Modal (Initially Hidden) -->
<div id="editCentreModal" class="psle-modal-overlay" style="display: none;" onclick="if(event.target === this) closeEditCentreModal()">
    <div class="psle-modal-card" onclick="event.stopPropagation()">
        <div class="psle-modal-header">
            <div class="psle-modal-header-content">
                <div>
                    <span class="psle-modal-kicker">
                        <i class="fas fa-edit text-amber-300"></i> PSLE Marking Centre
                    </span>
                    <h2 class="psle-modal-title">Edit Marking Centre</h2>
                    <p class="psle-modal-subtitle">Update center name, code, region, or physical location info.</p>
                </div>
                <button type="button" onclick="closeEditCentreModal()" class="psle-modal-close">&times;</button>
            </div>
        </div>
        <div class="psle-modal-body">
            <div class="psle-modal-panel">
                <form id="editCentreForm" method="POST" action="" class="psle-centre-form-grid adm-filters" style="border-bottom: none; background: transparent; padding: 0;">
                    @csrf
                    <div class="adm-filter-group psle-span-2">
                        <label class="adm-filter-label">Centre Name</label>
                        <input type="text" name="name" id="edit_name" class="psle-form-input" required>
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Centre Code</label>
                        <input type="text" name="code" id="edit_code" class="psle-form-input" required>
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Region</label>
                        <select name="region_id" id="edit_region_id" class="psle-form-select" required>
                            <option value="">Select Region</option>
                            @foreach($regions ?? [] as $reg)
                                <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="adm-filter-group psle-span-3">
                        <label class="adm-filter-label">Location (Optional)</label>
                        <input type="text" name="location" id="edit_location" class="psle-form-input">
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Status</label>
                        <select name="status" id="edit_status" class="psle-form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Latitude</label>
                        <input type="text" name="latitude" id="edit_latitude" class="psle-form-input">
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Longitude</label>
                        <input type="text" name="longitude" id="edit_longitude" class="psle-form-input">
                    </div>
                    <div class="adm-filter-group psle-span-1">
                        <label class="adm-filter-label">Radius (Metres)</label>
                        <input type="number" name="allowed_radius_meters" id="edit_allowed_radius_meters" class="psle-form-input" min="5">
                    </div>
                    <div class="adm-filter-group psle-span-1" style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn btn-outline" onclick="useCurrentLocation('edit_latitude', 'edit_longitude')" style="height:46px; width:100%; border:1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); color: #fff; cursor: pointer;"><i class="fas fa-map-marker-alt"></i> Use GPS</button>
                    </div>
                    <div class="psle-modal-actions">
                        <button type="button" class="psle-modal-button psle-modal-button-secondary" onclick="closeEditCentreModal()">Cancel</button>
                        <button type="submit" class="psle-modal-button psle-modal-button-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditCentreModal(centre) {
        const modal = document.getElementById('editCentreModal');
        const form = document.getElementById('editCentreForm');
        
        if (!modal || !form || !centre) return;
        
        document.getElementById('edit_name').value = centre.name || '';
        document.getElementById('edit_code').value = centre.code || '';
        document.getElementById('edit_region_id').value = centre.region_id || '';
        document.getElementById('edit_location').value = centre.location || '';
        document.getElementById('edit_status').value = centre.status || 'active';
        document.getElementById('edit_latitude').value = centre.latitude || '';
        document.getElementById('edit_longitude').value = centre.longitude || '';
        document.getElementById('edit_allowed_radius_meters').value = centre.allowed_radius_meters || '50';
        
        // Set action URL
        form.action = `/mark-entry/psle/marking-centres/${centre.id}/update`;
        
        // Display modal
        modal.style.display = 'flex';
    }

    function closeEditCentreModal() {
        const modal = document.getElementById('editCentreModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function confirmDeleteCentre(centreId, centreName) {
        if (confirm(`Are you sure you want to delete the marking centre "${centreName}"? This action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/mark-entry/psle/marking-centres/${centreId}/delete`;
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function useCurrentLocation(latInputId, lonInputId) {
        if (!navigator.geolocation) {
            alert("Your browser does not support Geolocation services.");
            return;
        }
        
        const btn = event.currentTarget;
        const originalBtnText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Fetching...';
        btn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById(latInputId).value = position.coords.latitude.toFixed(7);
                document.getElementById(lonInputId).value = position.coords.longitude.toFixed(7);
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            },
            function(error) {
                alert("Failed to retrieve location: " + error.message);
                btn.innerHTML = originalBtnText;
                btn.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }

    function confirmToggleGeofence(targetEnabled) {
        if (!targetEnabled) {
            Swal.fire({
                title: 'Disable GPS Restriction?',
                text: "You are about to disable marking-centre GPS restriction. Mark Entry Officers will be able to proceed with mark entry without location verification. One-device account restriction will remain active. Continue?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Disable Restriction',
                cancelButtonText: 'Cancel',
                confirmButtonColor: 'var(--tz-red)',
                cancelButtonColor: 'rgba(255,255,255,0.15)',
                background: '#101518',
                color: '#f0f4f7'
            }).then((result) => {
                if (result.isConfirmed) {
                    performGeofenceToggle(false);
                }
            });
        } else {
            performGeofenceToggle(true);
        }
    }

    function performGeofenceToggle(enabled) {
        Swal.fire({
            title: 'Updating status...',
            allowOutsideClick: false,
            background: '#101518',
            color: '#f0f4f7',
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('/mark-entry/psle/marking-centres/geofence-toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ enabled: enabled })
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: data.message,
                    timer: 2000,
                    showConfirmButton: false,
                    background: '#101518',
                    color: '#f0f4f7'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                throw new Error(data.message || 'Failed to toggle geofence.');
            }
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message,
                background: '#101518',
                color: '#f0f4f7'
            });
        });
    }
</script>
