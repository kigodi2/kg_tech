<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Outliers & Extreme Marks</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Outliers & Extreme Marks</h1>
    <p class="adm-page-desc">Identify and verify suspicious scores, repeated patterns, and behavioral anomalies in mark entry.</p>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Total Outliers</div>
        <div class="adm-stat-value" style="color: #fff;">{{ $outlierStats['total'] ?? 0 }}</div>
        <i class="fas fa-radar adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Extreme High/Low</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ ($outlierStats['high'] ?? 0) + ($outlierStats['low'] ?? 0) }}</div>
        <i class="fas fa-arrows-up-down adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Pattern Issues</div>
        <div class="adm-stat-value" style="color: #ff7b7b;">{{ $outlierStats['patterns'] ?? 0 }}</div>
        <i class="fas fa-fingerprint adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Unresolved</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ $outlierStats['pending'] ?? 0 }}</div>
        <i class="fas fa-hourglass-half adm-stat-icon"></i>
    </div>
</div>

<!-- Filters -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Filter Outliers</div>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="adm-filters" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
        <input type="hidden" name="view" value="outliers">
        
        <div class="adm-filter-group">
            <label class="adm-filter-label">Severity</label>
            <select name="severity" class="adm-select" onchange="this.form.submit()">
                <option value="">All Severities</option>
                <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Low</option>
                <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Medium</option>
                <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>High</option>
                <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Critical</option>
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Status</label>
            <select name="status" class="adm-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="escalated" {{ request('status') == 'escalated' ? 'selected' : '' }}>Escalated</option>
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Subject</label>
            <select name="subject_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Subjects</option>
                @foreach($psleSubjects as $subj)
                    <option value="{{ $subj->id }}" {{ request('subject_id') == $subj->id ? 'selected' : '' }}>{{ $subj->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Officer</label>
            <select name="officer_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Officers</option>
                @foreach($officers ?? [] as $off)
                    <option value="{{ $off->id }}" {{ request('officer_id') == $off->id ? 'selected' : '' }}>{{ $off->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
            <a href="{{ url('/mark-entry/psle?view=outliers') }}" class="btn btn-outline" style="width:100%; height:40px; text-decoration:none; display:flex; align-items:center; justify-content:center;">Reset</a>
        </div>
    </form>
</div>

<!-- Outliers Table -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Detection Results</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Target</th>
                    <th>Outlier Type</th>
                    <th class="text-center">Severity</th>
                    <th class="text-center">Value</th>
                    <th>Message</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($outliers ?? [] as $o)
                <tr>
                    <td>
                        @if($o->candidate)
                            <div style="font-weight: 600;">{{ $o->candidate->full_name }}</div>
                            <div style="font-size: 0.75rem; color: var(--tz-blue);">{{ $o->candidate->exam_number }}</div>
                            <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $o->subject->name ?? '' }} | {{ $o->school->name ?? '' }}</div>
                        @elseif($o->batch)
                            <div style="font-weight: 600;">Batch #{{ $o->batch_id }}</div>
                            <div style="font-size: 0.75rem; color: var(--tz-text-muted);">Officer: {{ $o->officer->name ?? 'System' }}</div>
                        @else
                            <div style="font-weight: 600;">System Event</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-outline">{{ $o->outlier_type }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $o->severity === 'critical' ? 'badge-red' : ($o->severity === 'high' ? 'badge-yellow' : 'badge-blue') }}">
                            {{ ucfirst($o->severity) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <div style="font-weight: 700; font-size: 1.1rem;">{{ $o->observed_value }}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem; max-width: 250px;">{{ $o->message }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $o->status === 'pending' ? 'badge-yellow' : ($o->status === 'verified' ? 'badge-green' : ($o->status === 'resolved' ? 'badge-blue' : 'badge-red')) }}">
                            {{ ucfirst($o->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if($o->status === 'pending')
                            @if($isAdmin || $isReo)
                                <div style="display: flex; gap: 4px; justify-content: flex-end;">
                                    <form method="POST" action="{{ url('/mark-entry/psle/outliers/' . $o->id . '/verify') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-action" title="Verify as Correct" style="color: var(--tz-green);"><i class="fas fa-check-circle"></i></button>
                                    </form>
                                    <button type="button" class="btn btn-action" title="Escalate" onclick="showOutlierModal('escalate', {{ $o->id }})" style="color: var(--tz-yellow);"><i class="fas fa-arrow-up"></i></button>
                                    <button type="button" class="btn btn-action" title="Resolve" onclick="showOutlierModal('resolve', {{ $o->id }})" style="color: var(--tz-blue);"><i class="fas fa-check-double"></i></button>
                                </div>
                            @else
                                <span class="badge badge-blue">Pending Review</span>
                            @endif
                        @else
                            <span class="text-muted" style="font-size: 0.75rem;">{{ ucfirst($o->status) }} by {{ $o->resolver->name ?? ($o->verifier->name ?? 'System') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-shield-check empty-icon" style="color: var(--tz-green);"></i>
                            <div class="empty-title">All Clear</div>
                            <div class="empty-desc">No suspicious marks or patterns detected for the current filters.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($outliers instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
        <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
            Showing {{ $outliers->firstItem() ?? 0 }} to {{ $outliers->lastItem() ?? 0 }} of {{ $outliers->total() ?? 0 }} results
        </div>
        <div class="pagination-links" style="display: flex; gap: 8px;">
            @if ($outliers->onFirstPage())
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;"><i class="fas fa-chevron-left"></i> Previous</span>
            @else
                <a href="{{ $outliers->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
            @endif

            @if ($outliers->hasMorePages())
                <a href="{{ $outliers->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
            @else
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;">Next <i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Outlier Action Modal -->
<div id="outlierModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div class="adm-card" style="width: 450px; max-width: 90%;">
        <div class="adm-card-head">
            <div class="adm-card-title" id="outlierModalTitle">Action Outlier</div>
        </div>
        <div class="adm-card-body">
            <form id="outlierForm" method="POST" action="">
                @csrf
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Comment / Justification</label>
                    <textarea name="comment" class="adm-select" style="height: 120px; padding: 12px; background: rgba(255,255,255,0.05); color: #fff;" required placeholder="Enter details for this action..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideOutlierModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="outlierSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showOutlierModal(action, id) {
    const modal = document.getElementById('outlierModal');
    const title = document.getElementById('outlierModalTitle');
    const form = document.getElementById('outlierForm');
    const btn = document.getElementById('outlierSubmitBtn');
    
    form.action = `{{ url('/mark-entry/psle/outliers') }}/${id}/${action}`;
    
    if (action === 'escalate') {
        title.textContent = 'Escalate Outlier to High-Level Review';
        btn.style.background = 'var(--tz-yellow)';
        btn.textContent = 'Confirm Escalation';
    } else if (action === 'resolve') {
        title.textContent = 'Resolve Outlier (Mark as Fixed/Closed)';
        btn.style.background = 'var(--tz-blue)';
        btn.textContent = 'Confirm Resolution';
    }
    
    modal.style.display = 'flex';
}

function hideOutlierModal() {
    document.getElementById('outlierModal').style.display = 'none';
}
</script>
