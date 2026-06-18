<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Subject Panel Assignments</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Subject Panel Assignments</h1>
    <p class="adm-page-desc">Assign Subject Panel Leaders to PSLE subjects for mark verification and quality assurance.</p>
</div>

@if($isReo)
    <div style="padding:12px 16px; background:rgba(0,163,221,.1); border:1px solid rgba(0,163,221,.25); border-radius:10px; margin-bottom:20px; font-size:.85rem; color:#67d8ff; display:flex; align-items:center; gap:8px;">
        <i class="fas fa-info-circle"></i>
        <span>You have read-only access to Subject Panel Assignments. You are restricted to viewing assignments for your assigned region.</span>
    </div>
@endif

@if(session('success'))
    <div style="padding: 12px 16px; background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.25); color: #4ade80; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600;">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div style="padding: 12px 16px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600;">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div style="padding: 12px 16px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600;">
        <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
    </div>
@endif

<div style="display: grid; grid-template-columns: {{ $isAdmin ? '1fr 360px' : '1fr' }}; gap: 24px; align-items: start;">
    <!-- Left Column: Current Assignments Table -->
    <div class="adm-card" style="margin-bottom:0;">
        <div class="adm-card-head" style="display:flex; justify-content:space-between; align-items:center;">
            <div class="adm-card-title">Current Assignments</div>
            <span style="font-size: .75rem; background: rgba(0,163,221,0.12); border: 1px solid rgba(0,163,221,0.25); color: #67d8ff; padding: 2px 9px; border-radius: 12px; font-weight: 700;">
                {{ $assignments->total() }} total
            </span>
        </div>
        <div class="adm-card-body table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Panel Leader</th>
                        <th>Subject</th>
                        <th>Exam Year</th>
                        <th>Region Scope</th>
                        <th class="text-center">Status</th>
                        @if($isAdmin)
                            <th class="text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                    <tr>
                        <td>
                            <div style="font-weight: 600;">{{ $assignment->user?->name ?? '—' }}</div>
                            <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ $assignment->user?->email ?? '' }}</div>
                        </td>
                        <td style="color: var(--tz-blue); font-weight: 600;">{{ $assignment->subject?->name ?? '—' }}</td>
                        <td>{{ $assignment->examYear?->year_label ?? 'All Years' }}</td>
                        <td>{{ $assignment->region?->name ?? 'All Regions' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $assignment->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        @if($isAdmin)
                            <td class="text-right">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.toggle', $assignment->id) }}" style="margin:0;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-action" style="font-size: 0.75rem; padding: 4px 10px; background: rgba(0,163,221,.1); border: 1px solid rgba(0,163,221,.25); color: #67d8ff;" title="{{ $assignment->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="fas {{ $assignment->is_active ? 'fa-ban' : 'fa-check' }}"></i> {{ $assignment->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.destroy', $assignment->id) }}" style="margin:0;" onsubmit="return confirm('Remove this assignment?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-action" style="font-size: 0.75rem; padding: 4px 10px; background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); color: #fca5a5;" title="Remove Assignment">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 6 : 5 }}">
                            <div class="empty-state">
                                <i class="fas fa-users-slash empty-icon"></i>
                                <div class="empty-title">No Assignments Found</div>
                                <div class="empty-desc">No subject panel leader assignments have been registered.</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            
            @if($assignments->hasPages())
                <div style="padding:16px; display:flex; justify-content:center;">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Right Column: New Assignment Form Card (Admin Only) -->
    @if($isAdmin)
    <div class="adm-card" style="margin-bottom:0; border-color: rgba(187,164,94,0.2);">
        <div class="adm-card-head">
            <div class="adm-card-title" style="color: var(--tz-gold); font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-plus-circle"></i> New Assignment
            </div>
        </div>
        <div class="adm-card-body" style="padding: 20px;">
            <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.store') }}">
                @csrf

                <div style="margin-bottom: 16px;">
                    <label class="adm-filter-label" style="margin-bottom: 6px; display: block;">Panel Leader User <span style="color:#fb923c;">*</span></label>
                    @if($panelLeaders->isEmpty())
                        <div style="font-size: 0.8rem; color: #fb923c; background: rgba(251,146,60,0.08); border: 1px solid rgba(251,146,60,0.2); border-radius: 8px; padding: 9px 12px;">
                            No active users with <strong>subject_panel_leader</strong> portal role found.
                        </div>
                    @else
                        <select name="user_id" required class="adm-select">
                            <option value="">— Select Panel Leader —</option>
                            @foreach($panelLeaders as $panelLeader)
                                <option value="{{ $panelLeader->id }}" @selected(old('user_id') == $panelLeader->id)>
                                    {{ $panelLeader->name }} ({{ $panelLeader->email }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="adm-filter-label" style="margin-bottom: 6px; display: block;">PSLE Subject <span style="color:#fb923c;">*</span></label>
                    <select name="subject_id" required class="adm-select">
                        <option value="">— Select Subject —</option>
                        @foreach($psleSubjects as $subject)
                            <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 16px;">
                    <label class="adm-filter-label" style="margin-bottom: 6px; display: block;">Exam Year (optional)</label>
                    <select name="exam_year_id" class="adm-select">
                        <option value="">All Years</option>
                        @foreach($examYears as $examYear)
                            <option value="{{ $examYear->id }}" @selected(old('exam_year_id') == $examYear->id)>{{ $examYear->year_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 24px;">
                    <label class="adm-filter-label" style="margin-bottom: 6px; display: block;">Region Scope (optional)</label>
                    <select name="region_id" class="adm-select">
                        <option value="">All Regions</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; height: 42px; background: linear-gradient(135deg, #BBA45E, #8a7540); border: none; color: #fff; font-weight: 700; box-shadow: 0 4px 12px rgba(187,164,94,0.25);">
                    <i class="fas fa-user-plus"></i> Create Assignment
                </button>
            </form>

            <div style="margin-top: 20px; padding: 12px 14px; background: rgba(0,163,221,0.06); border: 1px solid rgba(0,163,221,0.15); border-radius: 8px; font-size: 0.75rem; color: rgba(255,255,255,0.45); line-height: 1.55;">
                <strong style="color: #67d8ff;">Note:</strong>
                Panel leaders must have their <code style="color: #BBA45E;">portal_role</code> set to <code style="color: #BBA45E;">subject_panel_leader</code> and their status active.
            </div>
        </div>
    </div>
    @endif
</div>
