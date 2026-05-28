<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Submission & Locking</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Batch Submission & Locking</h1>
    <p class="adm-page-desc">Finalize mark entry batches, submit for regional review, and lock for final processing.</p>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Draft Batches</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ $lockingStats['draft'] ?? 0 }}</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Pending Approval</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ $lockingStats['pending'] ?? 0 }}</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Locked Batches</div>
        <div class="adm-stat-value" style="color: var(--tz-green);">{{ $lockingStats['locked'] ?? 0 }}</div>
    </div>
</div>

<!-- Filters Card -->
<div class="adm-card" style="margin-top: 24px; margin-bottom: 24px;">
    <div class="adm-card-head">
        <div class="adm-card-title"><i class="fas fa-filter"></i> Filter Batches</div>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="adm-filters" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); padding: 20px; background: rgba(0, 0, 0, 0.15); border-radius: 0 0 8px 8px; gap: 15px;">
        <input type="hidden" name="view" value="submission-locking">
        
        <div class="adm-filter-group">
            <label class="adm-filter-label">Exam Year</label>
            <select name="exam_year_id" class="adm-select" onchange="this.form.submit()">
                @foreach($examYears ?? [] as $yr)
                    <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Region</label>
            <select name="region_id" class="adm-select" onchange="this.form.submit()" {{ !empty($allowedRegionId) ? 'disabled' : '' }}>
                @if(empty($allowedRegionId))
                    <option value="">All Regions</option>
                @endif
                @foreach($regions ?? [] as $reg)
                    <option value="{{ $reg->id }}" {{ ($activeFilters['region_id'] ?? '') == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                @endforeach
            </select>
            @if(!empty($allowedRegionId))
                <input type="hidden" name="region_id" value="{{ $allowedRegionId }}">
            @endif
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">District / Council</label>
            <select name="district_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Districts</option>
                @foreach($districts ?? [] as $dist)
                    <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Primary School</label>
            <select name="school_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Schools</option>
                @foreach($schools ?? [] as $sch)
                    <option value="{{ $sch->id }}" {{ ($activeFilters['school_id'] ?? '') == $sch->id ? 'selected' : '' }}>{{ $sch->code }} - {{ $sch->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Subject</label>
            <select name="subject_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Subjects</option>
                @foreach($psleSubjects as $subj)
                    <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Entered By</label>
            <select name="created_by" class="adm-select" onchange="this.form.submit()">
                <option value="">All Officers</option>
                @foreach($officers ?? [] as $off)
                    <option value="{{ $off->id }}" {{ ($activeFilters['created_by'] ?? '') == $off->id ? 'selected' : '' }}>{{ $off->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Status</label>
            <select name="status" class="adm-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="draft" {{ ($activeFilters['status'] ?? '') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="submitted" {{ ($activeFilters['status'] ?? '') == 'submitted' ? 'selected' : '' }}>Submitted (Awaiting Review)</option>
                <option value="approved" {{ ($activeFilters['status'] ?? '') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ ($activeFilters['status'] ?? '') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="locked" {{ ($activeFilters['status'] ?? '') == 'locked' ? 'selected' : '' }}>Locked</option>
            </select>
        </div>

        <div class="adm-filter-group" style="display:flex; align-items:flex-end; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex:1; height:40px;"><i class="fas fa-filter"></i> Apply</button>
            <a href="{{ url('/mark-entry/psle?view=submission-locking') }}" class="btn btn-outline" style="height:40px; display:flex; align-items:center; justify-content:center;" title="Reset Filters"><i class="fas fa-rotate-left"></i></a>
        </div>
    </form>
</div>

<!-- Batches Table -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Mark Entry Batches</div>
    </div>
    <div class="adm-card-body table-responsive">
        
        <!-- Bulk Actions Action Bar -->
        <div class="adm-bulk-actions-bar" style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; padding: 10px 0;">
            <button type="button" id="btn-submit-selected" class="btn btn-primary btn-sm" disabled style="transition: all 0.2s ease;">
                <i class="fas fa-paper-plane"></i> Submit Selected (<span id="selected-count">0</span>)
            </button>
            @if(($lockingStats['draft'] ?? 0) > 0)
                <button type="button" id="btn-submit-all-eligible" class="btn btn-outline btn-sm" style="color: var(--tz-yellow); border-color: var(--tz-yellow); transition: all 0.2s ease;">
                    <i class="fas fa-mail-forward"></i> Submit All Eligible Drafts ({{ $lockingStats['draft'] ?? 0 }})
                </button>
            @endif
            <button type="button" id="btn-clear-selection" class="btn btn-outline btn-sm" style="display: none; transition: all 0.2s ease;">
                <i class="fas fa-times"></i> Clear Selection
            </button>
        </div>

        <!-- Gmail-Style Selection Banner -->
        <div id="selection-banner" class="adm-alert" style="display: none; margin-bottom: 15px; padding: 12px 20px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 6px; font-size: 0.9rem; align-items: center; justify-content: space-between; flex-direction: row; width: 100%; transition: all 0.3s ease;">
            <div style="display: flex; align-items: center;">
                <i class="fas fa-info-circle" style="color: var(--tz-yellow); margin-right: 8px; font-size: 1.1rem;"></i>
                <div>
                    <span id="banner-text">All visible draft batches are selected.</span>
                    <button type="button" id="btn-select-all-eligible-global" style="background: none; border: none; color: var(--tz-blue); font-weight: 600; text-decoration: underline; cursor: pointer; padding: 0; margin-left: 5px;">
                        Select all {{ $lockingStats['draft'] ?? 0 }} eligible draft batches matching filters
                    </button>
                </div>
            </div>
            <button type="button" id="btn-undo-global-select" class="btn btn-outline btn-sm" style="color: var(--tz-red); border-color: var(--tz-red); display: none; padding: 2px 8px; font-size: 0.75rem;">
                Undo Selection
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">
                        <input type="checkbox" id="select-all-visible" class="adm-checkbox" style="cursor: pointer;">
                    </th>
                    <th>Batch Code</th>
                    <th>School & Subject</th>
                    <th>Entered By</th>
                    <th class="text-center">Marks</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches ?? [] as $b)
                <tr id="batch-row-{{ $b->id }}" style="{{ !in_array($b->status, ['draft']) ? 'opacity: 0.75;' : '' }}">
                    <td class="text-center">
                        <input type="checkbox" class="batch-checkbox adm-checkbox" value="{{ $b->id }}" data-status="{{ $b->status }}" {{ !in_array($b->status, ['draft']) ? 'disabled' : '' }} style="cursor: {{ !in_array($b->status, ['draft']) ? 'not-allowed' : 'pointer' }};">
                    </td>
                    <td><code>{{ $b->batch_code ?? 'MANUAL-' . $b->id }}</code></td>
                    <td>
                        <div style="font-weight: 600;">{{ $b->school->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-blue);">{{ $b->subject->name ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $b->user->name ?? ($b->assignment->assignedTo->name ?? 'System') }}</div>
                        <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $b->created_at->format('d M Y, H:i') }}</div>
                    </td>
                    <td class="text-center">
                        <span style="font-weight: 600;">{{ $b->marks_count ?? 0 }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $b->status === 'draft' ? 'badge-yellow' : ($b->status === 'submitted' ? 'badge-blue' : ($b->status === 'approved' ? 'badge-green' : ($b->status === 'locked' ? 'badge-green' : 'badge-red'))) }}">
                            {{ $b->status === 'submitted' ? 'Awaiting Review' : ucfirst($b->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        @if($b->status === 'draft')
                            @if($isAdmin || ($isMarkOfficer && $b->created_by === $user->id))
                                @if(($b->marks_count ?? 0) > 0)
                                    <form method="POST" action="{{ url('/mark-entry/psle/batches/' . $b->id . '/submit') }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Submit</button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-primary btn-sm" disabled style="cursor: not-allowed; opacity: 0.5;" title="Cannot submit: this batch has no entered marks."><i class="fas fa-ban"></i> Submit</button>
                                @endif
                            @else
                                <span class="badge badge-outline" style="color: var(--tz-text-muted);">Awaiting Submission</span>
                            @endif
                        @elseif($b->status === 'submitted')
                            <span class="badge badge-blue">Awaiting Review</span>
                        @elseif($b->status === 'approved')
                            @if($isAdmin)
                                <form method="POST" action="{{ url('/mark-entry/psle/batches/' . $b->id . '/lock') }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-green btn-sm"><i class="fas fa-lock"></i> Lock</button>
                                </form>
                            @else
                                <span class="badge badge-green">Approved</span>
                            @endif
                        @elseif($b->status === 'rejected')
                            <span class="badge badge-red">Rejected</span>
                        @elseif($b->status === 'locked')
                            @if($isAdmin)
                                <form method="POST" action="{{ url('/mark-entry/psle/batches/' . $b->id . '/unlock') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="reason" value="Administrative unlock requested by Admin.">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--tz-red);"><i class="fas fa-unlock"></i> Unlock</button>
                                </form>
                            @else
                                <span class="badge badge-green"><i class="fas fa-lock"></i> Locked</span>
                            @endif
                        @else
                            <span class="text-muted" style="font-size: 0.8rem;">No Actions</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-layer-group empty-icon"></i>
                            <div class="empty-title">No Batches Found</div>
                            <div class="empty-desc">No mark entry batches match your current selection or status.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    @if($batches instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
        <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
            Showing {{ $batches->firstItem() ?? 0 }} to {{ $batches->lastItem() ?? 0 }} of {{ $batches->total() ?? 0 }} results
        </div>
        <div class="pagination-links" style="display: flex; gap: 8px;">
            @if ($batches->onFirstPage())
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;"><i class="fas fa-chevron-left"></i> Previous</span>
            @else
                <a href="{{ $batches->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
            @endif

            @if ($batches->hasMorePages())
                <a href="{{ $batches->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
            @else
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;">Next <i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- SweetAlert2 Interactive Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllVisible = document.getElementById('select-all-visible');
    const batchCheckboxes = document.querySelectorAll('.batch-checkbox');
    const btnSubmitSelected = document.getElementById('btn-submit-selected');
    const btnSubmitAllEligible = document.getElementById('btn-submit-all-eligible');
    const btnClearSelection = document.getElementById('btn-clear-selection');
    const selectedCountSpan = document.getElementById('selected-count');
    const selectionBanner = document.getElementById('selection-banner');
    const bannerText = document.getElementById('banner-text');
    const btnSelectAllEligibleGlobal = document.getElementById('btn-select-all-eligible-global');
    const btnUndoGlobalSelect = document.getElementById('btn-undo-global-select');

    let selectedIds = new Set();
    let isGlobalSelectActive = false;
    const totalEligibleDrafts = parseInt("{{ $lockingStats['draft'] ?? 0 }}");
    const visibleCheckboxes = Array.from(batchCheckboxes).filter(cb => !cb.disabled);

    // Update Action Bar Buttons State
    function updateActionBar() {
        const checkedCount = selectedIds.size;
        
        if (isGlobalSelectActive) {
            selectedCountSpan.textContent = totalEligibleDrafts;
            btnSubmitSelected.disabled = false;
            btnClearSelection.style.display = 'inline-flex';
        } else {
            selectedCountSpan.textContent = checkedCount;
            btnSubmitSelected.disabled = checkedCount === 0;
            btnClearSelection.style.display = checkedCount > 0 ? 'inline-flex' : 'none';
        }

        // Sync header checkbox
        if (visibleCheckboxes.length > 0) {
            const allVisibleChecked = visibleCheckboxes.every(cb => cb.checked);
            const someVisibleChecked = visibleCheckboxes.some(cb => cb.checked);
            
            selectAllVisible.checked = allVisibleChecked;
            selectAllVisible.indeterminate = someVisibleChecked && !allVisibleChecked;
        } else {
            selectAllVisible.checked = false;
            selectAllVisible.indeterminate = false;
        }
    }

    // Toggle header select
    selectAllVisible.addEventListener('change', function() {
        const isChecked = this.checked;
        
        visibleCheckboxes.forEach(cb => {
            cb.checked = isChecked;
            const rowId = parseInt(cb.value);
            if (isChecked) {
                selectedIds.add(rowId);
            } else {
                selectedIds.delete(rowId);
            }
        });

        // Gmail-style selection alert logic
        if (isChecked && totalEligibleDrafts > visibleCheckboxes.length) {
            selectionBanner.style.display = 'flex';
            bannerText.textContent = `All ${visibleCheckboxes.length} visible draft batches on this page are selected. `;
            btnSelectAllEligibleGlobal.style.display = 'inline-block';
            btnUndoGlobalSelect.style.display = 'none';
        } else {
            selectionBanner.style.display = 'none';
            isGlobalSelectActive = false;
        }

        updateActionBar();
    });

    // Individual checkbox change
    batchCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const rowId = parseInt(this.value);
            if (this.checked) {
                selectedIds.add(rowId);
            } else {
                selectedIds.delete(rowId);
                
                // If anything is unchecked, global selection is deactivated
                if (isGlobalSelectActive) {
                    isGlobalSelectActive = false;
                    selectionBanner.style.display = 'none';
                }
            }

            // Hide select-all banner if visible ones are unchecked
            if (selectionBanner.style.display === 'flex' && !isGlobalSelectActive) {
                selectionBanner.style.display = 'none';
            }

            updateActionBar();
        });
    });

    // Select All Eligible Drafts matching filters Globally (Gmail-style)
    btnSelectAllEligibleGlobal.addEventListener('click', function() {
        isGlobalSelectActive = true;
        
        // Visual check all
        visibleCheckboxes.forEach(cb => cb.checked = true);
        
        // Hide select all link, show undo link
        btnSelectAllEligibleGlobal.style.display = 'none';
        btnUndoGlobalSelect.style.display = 'inline-block';
        bannerText.textContent = `All ${totalEligibleDrafts} draft batches matching current filters have been selected globally. `;
        
        updateActionBar();
    });

    // Undo global select
    btnUndoGlobalSelect.addEventListener('click', clearAllSelection);
    btnClearSelection.addEventListener('click', clearAllSelection);

    function clearAllSelection() {
        selectedIds.clear();
        isGlobalSelectActive = false;
        selectAllVisible.checked = false;
        selectAllVisible.indeterminate = false;
        batchCheckboxes.forEach(cb => cb.checked = false);
        selectionBanner.style.display = 'none';
        updateActionBar();
    }

    // DRY-RUN ELIGIBILITY VALIDATION WORKFLOW
    async function triggerValidationWorkflow(allDrafts = false) {
        // Prevent double click double submits
        btnSubmitSelected.disabled = true;
        if (btnSubmitAllEligible) btnSubmitAllEligible.disabled = true;

        Swal.fire({
            title: 'Validating Batches',
            html: 'Running eligibility checks and scanning for outliers/validation errors just-in-time...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: '#101518',
            color: '#fff',
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Gather criteria
        const payload = {};
        if (allDrafts || isGlobalSelectActive) {
            payload.all_drafts = true;
            payload.exam_year_id = '{{ $activeFilters["exam_year_id"] ?? "" }}';
            payload.region_id = '{{ $activeFilters["region_id"] ?? "" }}';
            payload.district_id = '{{ $activeFilters["district_id"] ?? "" }}';
            payload.school_id = '{{ $activeFilters["school_id"] ?? "" }}';
            payload.subject_id = '{{ $activeFilters["subject_id"] ?? "" }}';
            payload.created_by = '{{ $activeFilters["created_by"] ?? "" }}';
        } else {
            payload.batch_ids = Array.from(selectedIds);
        }

        try {
            const response = await fetch('{{ url("/api/mark-entry/psle/batches/bulk-validate") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Validation request failed.');
            }

            const eligible = data.eligible || [];
            const skipped = data.skipped || [];

            // If absolutely nothing is selected/found
            if (eligible.length === 0 && skipped.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Batches Found',
                    text: 'There are no batches in scope matching the submission criteria.',
                    background: '#101518',
                    color: '#fff',
                    confirmButtonColor: 'var(--tz-blue)'
                });
                updateActionBar();
                if (btnSubmitAllEligible) btnSubmitAllEligible.disabled = false;
                return;
            }

            // Build beautifully customized skipped HTML block
            let skippedHtml = '';
            if (skipped.length > 0) {
                skippedHtml = `
                    <div style="margin-top: 15px; text-align: left;">
                        <div style="font-weight: 700; color: var(--tz-yellow); margin-bottom: 8px; font-size: 0.95rem;">
                            <i class="fas fa-exclamation-triangle"></i> Ineligible Drafts to Skip (${skipped.length})
                        </div>
                        <div style="max-height: 180px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.06); border-radius: 4px; padding: 10px; background: rgba(0,0,0,0.25);">
                            ${skipped.map(b => `
                                <div style="border-bottom: 1px solid rgba(255,255,255,0.04); padding: 6px 0; font-size: 0.85rem; line-height: 1.4;">
                                    <span style="font-weight:600; color: #fff;">${b.batch_code}</span>
                                    <span style="color: var(--tz-text-muted); font-size: 0.8rem;"> - ${b.school_name} (${b.subject_name})</span>
                                    <div style="color: #ff7b7b; font-size: 0.78rem; margin-top: 2px; font-weight: 500;">
                                        <i class="fas fa-ban" style="font-size:0.7rem; margin-right: 3px;"></i> ${b.reason}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // Build eligible block
            let eligibleHtml = '';
            if (eligible.length > 0) {
                eligibleHtml = `
                    <div style="margin-top: 15px; text-align: left;">
                        <div style="font-weight: 700; color: var(--tz-green); margin-bottom: 8px; font-size: 0.95rem;">
                            <i class="fas fa-check-circle"></i> Eligible for Bulk Submission (${eligible.length})
                        </div>
                        <div style="max-height: 180px; overflow-y: auto; border: 1px solid rgba(255,255,255,0.06); border-radius: 4px; padding: 10px; background: rgba(0,0,0,0.25);">
                            ${eligible.map(b => `
                                <div style="border-bottom: 1px solid rgba(255,255,255,0.04); padding: 5px 0; font-size: 0.85rem; color: #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <i class="fas fa-arrow-right" style="font-size: 0.7rem; color: var(--tz-green); margin-right: 5px;"></i>
                                        <strong style="color: #fff;">${b.batch_code}</strong>
                                        <span style="font-size: 0.8rem; color: var(--tz-text-muted);"> - ${b.school_name} (${b.subject_name})</span>
                                    </div>
                                    <span class="badge badge-outline" style="color: var(--tz-blue); border-color: rgba(59, 130, 246, 0.4);">${b.marks_count} marks</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            // If absolutely none are eligible
            if (eligible.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Eligible Batches Selected',
                    html: `
                        <p style="font-size: 0.95rem; line-height: 1.5; color: var(--tz-text-muted);">All selected batches have errors, outliers, or are empty and cannot be submitted.</p>
                        ${skippedHtml}
                    `,
                    background: '#101518',
                    color: '#fff',
                    confirmButtonColor: 'var(--tz-yellow)',
                    confirmButtonText: 'Review Batches'
                });
                updateActionBar();
                if (btnSubmitAllEligible) btnSubmitAllEligible.disabled = false;
                return;
            }

            // Final Confirmation Modal
            Swal.fire({
                title: 'Confirm Bulk Submission',
                html: `
                    <div style="font-size: 0.95rem; line-height: 1.5; text-align: left; color: #e2e8f0;">
                        You are about to submit <strong style="color: var(--tz-green);">${eligible.length} eligible batches</strong> for review. 
                        Once submitted, these batches will transition to the <strong>Awaiting Review</strong> status and cannot be modified without administrative rejection.
                    </div>
                    ${eligibleHtml}
                    ${skippedHtml}
                    <div style="margin-top: 20px; font-weight: 600; text-align: left; color: #f87171; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i> This action runs fresh validations, logs comprehensive audit trails, and triggers outlier reviews per batch.
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit Globally',
                cancelButtonText: 'Cancel',
                confirmButtonColor: 'var(--tz-green)',
                cancelButtonColor: 'rgba(255,255,255,0.1)',
                background: '#101518',
                color: '#fff',
                width: '650px'
            }).then((confirmRes) => {
                if (confirmRes.isConfirmed) {
                    executeBulkSubmit(eligible);
                } else {
                    updateActionBar();
                    if (btnSubmitAllEligible) btnSubmitAllEligible.disabled = false;
                }
            });

        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Dry-run Check Failed',
                text: err.message,
                background: '#101518',
                color: '#fff'
            });
            updateActionBar();
            if (btnSubmitAllEligible) btnSubmitAllEligible.disabled = false;
        }
    }

    // CHUNKED EXECUTION SUBMISSION FLOW
    async function executeBulkSubmit(eligibleBatches) {
        const eligibleIds = eligibleBatches.map(b => b.id);
        const totalBatches = eligibleIds.length;
        const chunkSize = 100;
        
        const chunks = [];
        for (let i = 0; i < totalBatches; i += chunkSize) {
            chunks.push(eligibleIds.slice(i, i + chunkSize));
        }
        
        const totalChunks = chunks.length;

        Swal.fire({
            title: 'Submitting Draft Batches...',
            html: `
                <div style="margin: 20px 0;">
                    <div style="font-size: 0.95rem; margin-bottom: 10px; text-align: left; font-weight: 600;">
                        Processing batch chunk <span id="chunk-current">1</span> of ${totalChunks}...
                    </div>
                    <div style="height: 12px; background: rgba(255,255,255,0.06); border-radius: 6px; overflow: hidden; width: 100%;">
                        <div id="progress-bar-fill" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--tz-blue), var(--tz-green)); transition: width 0.3s ease;"></div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--tz-text-muted); margin-top: 8px; text-align: right; font-weight: 500;">
                        <span id="submitted-count-fill">0</span> of ${totalBatches} batches processed
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            background: '#101518',
            color: '#fff',
            didOpen: async () => {
                let allSubmitted = [];
                let allFailed = [];
                let allSkipped = [];
                
                for (let idx = 0; idx < totalChunks; idx++) {
                    document.getElementById('chunk-current').textContent = idx + 1;
                    
                    try {
                        const response = await fetch('{{ url("/api/mark-entry/psle/batches/bulk-submit") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ batch_ids: chunks[idx] })
                        });
                        
                        const result = await response.json();
                        if (result.success) {
                            allSubmitted.push(...(result.submitted || []));
                            allFailed.push(...(result.failed || []));
                            allSkipped.push(...(result.skipped || []));
                        } else {
                            // Entire chunk failure
                            chunks[idx].forEach(id => {
                                const originalBatch = eligibleBatches.find(b => b.id === id);
                                allFailed.push({ 
                                    batch_code: originalBatch ? originalBatch.batch_code : `Batch ID: ${id}`, 
                                    reason: result.message || 'Chunk transition aborted.' 
                                });
                            });
                        }
                    } catch (err) {
                        chunks[idx].forEach(id => {
                            const originalBatch = eligibleBatches.find(b => b.id === id);
                            allFailed.push({ 
                                batch_code: originalBatch ? originalBatch.batch_code : `Batch ID: ${id}`, 
                                reason: err.message || 'Network/Server Error.' 
                            });
                        });
                    }
                    
                    const processedSoFar = allSubmitted.length + allFailed.length + allSkipped.length;
                    document.getElementById('submitted-count-fill').textContent = processedSoFar;
                    
                    const percent = Math.round(((idx + 1) / totalChunks) * 100);
                    document.getElementById('progress-bar-fill').style.width = percent + '%';
                }

                // Complete! Render elegant summary report
                let summaryHtml = `
                    <div style="text-align: left; font-size: 0.95rem; line-height: 1.6; color: #e2e8f0;">
                        <div style="margin-bottom: 12px; font-weight: 700; color: #fff;">Bulk Submission Processing Report:</div>
                        <ul style="list-style: none; padding-left: 0; margin-bottom: 15px; display: flex; flex-direction: column; gap: 6px;">
                            <li style="color: var(--tz-green); font-weight: 600;">
                                <i class="fas fa-check-circle" style="margin-right: 5px;"></i> Successfully Submitted: <strong>${allSubmitted.length}</strong>
                            </li>
                            <li style="color: var(--tz-yellow); font-weight: 600;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i> Skipped batches: <strong>${allSkipped.length}</strong>
                            </li>
                            <li style="color: var(--tz-red); font-weight: 600;">
                                <i class="fas fa-times-circle" style="margin-right: 5px;"></i> Failed transitions: <strong>${allFailed.length}</strong>
                            </li>
                        </ul>
                `;

                if (allFailed.length > 0) {
                    summaryHtml += `
                        <div style="font-weight: 700; color: var(--tz-red); margin-bottom: 6px; font-size: 0.9rem;">Failure Details (${allFailed.length})</div>
                        <div style="max-height: 160px; overflow-y: auto; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 4px; padding: 10px; background: rgba(239, 68, 68, 0.05); font-size: 0.8rem; line-height: 1.4;">
                            ${allFailed.map(f => `
                                <div style="margin-bottom: 6px; border-bottom: 1px solid rgba(255,255,255,0.04); padding-bottom: 4px;">
                                    <strong style="color: #fff;">${f.batch_code}</strong>: <span style="color: #f87171;">${f.reason}</span>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }

                summaryHtml += `
                    <div style="margin-top: 15px; font-size: 0.85rem; color: var(--tz-text-muted); border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px;">
                        System activity logs and governance audit trials have been fully locked for these events.
                    </div>
                </div>`;

                Swal.fire({
                    icon: allFailed.length === 0 ? 'success' : 'warning',
                    title: 'Bulk Submission Complete',
                    html: summaryHtml,
                    background: '#101518',
                    color: '#fff',
                    confirmButtonColor: 'var(--tz-blue)',
                    confirmButtonText: 'Reload Dashboard'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    // Button event listeners
    btnSubmitSelected.addEventListener('click', () => triggerValidationWorkflow(false));
    if (btnSubmitAllEligible) {
        btnSubmitAllEligible.addEventListener('click', () => triggerValidationWorkflow(true));
    }
});
</script>
