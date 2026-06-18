<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Manage Assignments</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Officer Assignments</h1>
    <p class="adm-page-desc">Assign Mark Entry Officers to specific schools and subjects at regional marking centres.</p>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Total Assignments</div>
        <div class="adm-stat-value" style="color: #fff;">{{ count($assignments ?? []) }}</div>
        <i class="fas fa-tasks adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Active Officers</div>
        <div class="adm-stat-value" style="color: var(--tz-green);">{{ collect($assignments ?? [])->pluck('assigned_to')->unique()->count() }}</div>
        <i class="fas fa-user-tie adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Schools Covered</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ collect($assignments ?? [])->pluck('school_id')->unique()->count() }}</div>
        <i class="fas fa-school adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Subjects Assigned</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ collect($assignments ?? [])->pluck('subject_id')->unique()->count() }}</div>
        <i class="fas fa-book adm-stat-icon"></i>
    </div>
</div>

<!-- Create Assignment Form -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">New Assignment</div>
    </div>
    <div class="adm-card-body">
        <form method="POST" action="{{ url('/mark-entry/psle/assignments/create') }}" class="adm-filters" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            @csrf
            <div class="adm-filter-group">
                <label class="adm-filter-label">Mark Entry Officer</label>
                <select name="assigned_to" class="adm-select" required>
                    <option value="">Select Officer</option>
                    @foreach($officers ?? [] as $off)
                        <option value="{{ $off->id }}">{{ $off->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">Marking Centre</label>
                <select name="marking_centre_id" class="adm-select" required>
                    <option value="">Select Centre</option>
                    @foreach($markingCentres ?? [] as $mc)
                        <option value="{{ $mc->id }}">{{ $mc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">Region</label>
                <select name="region_id" id="assign_region_id" class="adm-select" required onchange="updateDistrictsForAssignment(this.value)">
                    @if(empty($allowedRegionId))
                        <option value="">Select Region</option>
                    @endif
                    @foreach($regions ?? [] as $reg)
                        <option value="{{ $reg->id }}" {{ (int)($selectedRegionId ?? 0) === (int)$reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">District</label>
                <select name="district_id" id="assign_district_id" class="adm-select" onchange="updateSchoolsForAssignment(this.value)">
                    <option value="">Select District</option>
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">School</label>
                <select name="school_id" id="assign_school_id" class="adm-select" required>
                    <option value="">Select School</option>
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">Subject</label>
                <select name="subject_id" class="adm-select" required>
                    <option value="">Select Subject</option>
                    @foreach($psleSubjects ?? [] as $sub)
                        <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="adm-filter-group">
                <label class="adm-filter-label">Assignment Type</label>
                <select name="assignment_type" class="adm-select">
                    <option value="entry">Mark Entry (First)</option>
                    <option value="verification">Verification (Second)</option>
                </select>
            </div>
            <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%; height:40px;"><i class="fas fa-check-circle"></i> Assign</button>
            </div>
        </form>
    </div>
</div>

<!-- Assignment List -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Current Assignments</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Officer</th>
                    <th>Centre</th>
                    <th>School</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments ?? [] as $asgn)
                <tr>
                    <td>
                        <div style="font-weight: 600;">{{ $asgn->assignedTo->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ $asgn->assignedTo->email ?? '' }}</div>
                    </td>
                    <td>{{ $asgn->markingCentre->name ?? 'N/A' }}</td>
                    <td>
                        <div>{{ $asgn->school->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ $asgn->school->code ?? '' }}</div>
                    </td>
                    <td>{{ $asgn->subject->name ?? 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $asgn->assignment_type === 'entry' ? 'badge-blue' : 'badge-yellow' }}">
                            {{ ucfirst($asgn->assignment_type) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $asgn->status === 'active' ? 'badge-green' : ($asgn->status === 'completed' ? 'badge-blue' : 'badge-red') }}">
                            {{ ucfirst($asgn->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <form method="POST" action="{{ url('/mark-entry/psle/assignments/' . $asgn->id . '/revoke') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-action" title="Revoke Assignment" onclick="return confirm('Are you sure you want to revoke this assignment?')" style="background: none; border: none; color: var(--tz-red); cursor: pointer;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-tasks empty-icon"></i>
                            <div class="empty-title">No Assignments</div>
                            <div class="empty-desc">Start assigning officers to schools to begin the mark entry process.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
const assignmentDistricts = @json($assignmentDistrictOptions ?? []);
const assignmentSchools = @json($assignmentSchoolOptions ?? []);

function updateDistrictsForAssignment(regionId) {
    const districtSelect = document.getElementById('assign_district_id');
    const schoolSelect = document.getElementById('assign_school_id');
    districtSelect.innerHTML = '<option value="">Select District</option>';
    schoolSelect.innerHTML = '<option value="">Select School</option>';

    if (!regionId) return;

    assignmentDistricts
        .filter(d => Number(d.region_id) === Number(regionId))
        .forEach(d => {
            const opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.name;
            districtSelect.appendChild(opt);
        });
}

function updateSchoolsForAssignment(districtId) {
    const schoolSelect = document.getElementById('assign_school_id');
    schoolSelect.innerHTML = '<option value="">Select School</option>';

    if (!districtId) return;

    assignmentSchools
        .filter(s => Number(s.district_id) === Number(districtId))
        .forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.code ? `${s.name} (${s.code})` : s.name;
            schoolSelect.appendChild(opt);
        });
}

document.addEventListener('DOMContentLoaded', () => {
    const regionSelect = document.getElementById('assign_region_id');
    if (regionSelect && regionSelect.value) {
        updateDistrictsForAssignment(regionSelect.value);
    }
});
</script>
