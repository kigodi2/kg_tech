<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Moderation & Review</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Moderation & Regional Review</h1>
    <p class="adm-page-desc">Regional Education Officers (REO) review submitted mark entry batches for quality assurance.</p>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Pending Review</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ $moderationStats['pending'] ?? 0 }}</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Approved</div>
        <div class="adm-stat-value" style="color: var(--tz-green);">{{ $moderationStats['approved'] ?? 0 }}</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Rejected</div>
        <div class="adm-stat-value" style="color: var(--tz-red);">{{ $moderationStats['rejected'] ?? 0 }}</div>
    </div>
</div>

<!-- Review Table -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Submitted Batches for Review</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>School & Subject</th>
                    <th>Submitted By</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">QA Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches ?? [] as $b)
                <tr>
                    <td><code>{{ $b->batch_code ?? 'MANUAL-' . $b->id }}</code></td>
                    <td>
                        <div style="font-weight: 600;">{{ $b->school->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-blue);">{{ $b->subject->name ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $b->user->name ?? ($b->assignment->assignedTo->name ?? 'Officer') }}</div>
                        <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $b->updated_at->format('d M Y, H:i') }}</div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $b->status === 'submitted' ? 'badge-yellow' : ($b->status === 'approved' ? 'badge-green' : 'badge-red') }}">
                            {{ ucfirst($b->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div style="display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                            <a href="{{ url('/mark-entry/psle?view=entry-sheet&school_id=' . $b->school_id . '&subject_id=' . $b->subject_id) }}" class="btn btn-outline btn-sm" style="color: var(--tz-blue); border-color: rgba(0, 163, 221, 0.4); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fas fa-eye"></i> View Sheet
                            </a>
                            @if($b->status === 'submitted' && ($isAdmin || $isReo))
                                <form method="POST" action="{{ url('/mark-entry/psle/batches/' . $b->id . '/approve') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" style="background: var(--tz-green); border-color: var(--tz-green); color: #fff;"><i class="fas fa-check"></i> Approve</button>
                                </form>
                                <button type="button" class="btn btn-outline btn-sm" style="color: var(--tz-red); border-color: rgba(255, 123, 123, 0.4);" onclick="showRejectModal({{ $b->id }})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            @else
                                <span class="badge {{ $b->status === 'approved' ? 'badge-green' : ($b->status === 'rejected' ? 'badge-red' : 'badge-yellow') }}" style="font-size: 0.75rem;">
                                    {{ ucfirst($b->status) }}
                                </span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check empty-icon"></i>
                            <div class="empty-title">No submitted batches awaiting review</div>
                            <div class="empty-desc">Once Mark Entry Officers submit school-subject batches, they will appear here for regional QA review.</div>
                            
                            @if(config('app.env') === 'local')
                                <div class="diagnostics-box" style="margin-top: 25px; padding: 15px; background: rgba(255,255,255,0.03); border: 1px dashed rgba(255,255,255,0.1); border-radius: 8px; font-family: monospace; text-align: left; max-width: 400px; margin-left: auto; margin-right: auto; font-size: 0.8rem;">
                                    <div style="color: var(--tz-yellow); font-weight: bold; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; margin-bottom: 8px;">
                                        <i class="fas fa-bug"></i> Local Environment Diagnostics
                                    </div>
                                    <div><strong>Environment:</strong> {{ config('app.env') }}</div>
                                    <div><strong>User Role:</strong> {{ $isAdmin ? 'Admin' : ($isReo ? 'REO' : 'Officer') }}</div>
                                    <div><strong>Region ID:</strong> {{ $selectedRegionId ?? 'None' }}</div>
                                    <div><strong>Pending count:</strong> {{ $moderationStats['pending'] ?? 0 }}</div>
                                    <div><strong>Approved count:</strong> {{ $moderationStats['approved'] ?? 0 }}</div>
                                    <div><strong>Rejected count:</strong> {{ $moderationStats['rejected'] ?? 0 }}</div>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; align-items:center; justify-content:center;">
    <div class="adm-card" style="width: 400px; max-width: 90%;">
        <div class="adm-card-head">
            <div class="adm-card-title">Reject Batch</div>
        </div>
        <div class="adm-card-body">
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="adm-filter-group">
                    <label class="adm-filter-label">Reason for Rejection</label>
                    <textarea name="reason" class="adm-select" style="height: 100px; padding: 10px; background: rgba(255,255,255,0.05); color: #fff;" required placeholder="Please explain why this batch is being rejected..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn btn-outline btn-sm" onclick="hideRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" style="background: var(--tz-red);">Confirm Rejection</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(batchId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `{{ url('/mark-entry/psle/batches') }}/${batchId}/reject`;
    modal.style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
