<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Validation Errors</span>
</div>

<div class="adm-page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;">
    <div>
        <h1 class="adm-page-title">Validation Errors</h1>
        <p class="adm-page-desc">Show invalid marks and validation issues. Correct errors before submission.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-outline" onclick="runValidation()">
            <i class="fas fa-sync-alt" id="runValIcon"></i> Run Validation
        </button>
        <a href="{{ url('/api/mark-entry/psle/reports/validation-errors/csv') }}?{{ http_build_query(request()->all()) }}" class="btn btn-primary">
            <i class="fas fa-file-csv"></i> Export CSV
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Total Errors</div>
        <div class="adm-stat-value" style="color: #ff7b7b;">{{ $validationStats['total'] ?? 0 }}</div>
        <i class="fas fa-exclamation-triangle adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Invalid Marks</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ $validationStats['invalid_marks'] ?? 0 }}</div>
        <i class="fas fa-times-circle adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Duplicate Entries</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ $validationStats['duplicates'] ?? 0 }}</div>
        <i class="fas fa-copy adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Resolved Errors</div>
        <div class="adm-stat-value" style="color: var(--tz-green);">{{ $validationStats['resolved'] ?? 0 }}</div>
        <i class="fas fa-check-circle adm-stat-icon"></i>
    </div>
</div>

<!-- Filters -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Filter Validation Errors</div>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="adm-filters" id="filterForm">
        <input type="hidden" name="view" value="validation-errors">
        
        <div class="adm-filter-group">
            <label class="adm-filter-label">Exam Year</label>
            <select name="exam_year_id" class="adm-select" onchange="this.form.submit()">
                @foreach($examYears as $year)
                    <option value="{{ $year->id }}" {{ $selectedYearId == $year->id ? 'selected' : '' }}>{{ $year->year_label }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Region</label>
            <select name="region_id" class="adm-select" {{ !$isAdmin ? 'disabled' : '' }} onchange="this.form.submit()">
                <option value="">All Regions</option>
                @foreach($regions as $reg)
                    <option value="{{ $reg->id }}" {{ $selectedRegionId == $reg->id ? 'selected' : '' }}>{{ $reg->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">District/Council</label>
            <select name="district_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Districts</option>
                @foreach($districts as $dist)
                    <option value="{{ $dist->id }}" {{ $selectedDistrictId == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Subject</label>
            <select name="subject_id" class="adm-select" onchange="this.form.submit()">
                <option value="">All Subjects</option>
                @foreach($psleSubjects as $subj)
                    <option value="{{ $subj->id }}" {{ $selectedSubjectId == $subj->id ? 'selected' : '' }}>{{ $subj->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Error Type</label>
            <select name="error_type" class="adm-select" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="Missing Mark" {{ request('error_type') == 'Missing Mark' ? 'selected' : '' }}>Missing Mark</option>
                <option value="Invalid Mark" {{ request('error_type') == 'Invalid Mark' ? 'selected' : '' }}>Invalid Mark</option>
                <option value="Mark Above Maximum" {{ request('error_type') == 'Mark Above Maximum' ? 'selected' : '' }}>Mark Above Maximum</option>
                <option value="Negative Mark" {{ request('error_type') == 'Negative Mark' ? 'selected' : '' }}>Negative Mark</option>
                <option value="Duplicate Entry" {{ request('error_type') == 'Duplicate Entry' ? 'selected' : '' }}>Duplicate Entry</option>
                <option value="ABS Conflict" {{ request('error_type') == 'ABS Conflict' ? 'selected' : '' }}>ABS Conflict</option>
            </select>
        </div>

        <div class="adm-filter-group">
            <label class="adm-filter-label">Status</label>
            <select name="status" class="adm-select" onchange="this.form.submit()">
                <option value="open" {{ request('status', 'open') == 'open' ? 'selected' : '' }}>Open</option>
                <option value="corrected" {{ request('status') == 'corrected' ? 'selected' : '' }}>Corrected</option>
                <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="ignored" {{ request('status') == 'ignored' ? 'selected' : '' }}>Ignored</option>
            </select>
        </div>
        
        <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
            <a href="{{ url('/mark-entry/psle?view=validation-errors') }}" class="btn btn-outline" style="width:100%; height:40px; text-decoration:none; display:flex; align-items:center; justify-content:center;">Reset</a>
        </div>
    </form>
</div>

<!-- Error Table -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Error List</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>School</th>
                    <th>Subject</th>
                    <th class="text-center">Mark</th>
                    <th>Error Type</th>
                    <th class="text-center">Severity</th>
                    <th>Message</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($validationErrors ?? [] as $error)
                <tr>
                    <td>
                        <div style="font-weight: 700;">{{ $error->candidate->full_name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-yellow);">{{ $error->candidate->candidate_id ?? ($error->rawMark->candidate_index_number ?? 'N/A') }}</div>
                        <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $error->candidate->gender ?? '' }}</div>
                    </td>
                    <td>
                        <div style="font-size: 0.85rem;">{{ $error->school->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $error->district->name ?? '' }}</div>
                    </td>
                    <td>{{ $error->subject->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        <div style="font-weight: 800;">
                            {{ $error->rawMark->paper_1_marks ?? '-' }}
                            @if($error->rawMark->subject_status)
                                <span class="badge badge-yellow" style="font-size: 0.6rem;">{{ $error->rawMark->subject_status }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-outline">{{ $error->error_type }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $error->severity === 'critical' ? 'badge-red' : ($error->severity === 'high' ? 'badge-yellow' : 'badge-blue') }}">
                            {{ ucfirst($error->severity) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size: 0.8rem; color: #ff7b7b; max-width: 200px; white-space: normal;">{{ $error->message }}</div>
                    </td>
                    <td class="text-right">
                        @if($error->status === 'open')
                        <div style="display: flex; gap: 4px; justify-content: flex-end;">
                            <button type="button" class="btn btn-action" onclick="showCorrectionModal({{ json_encode($error) }})" title="Correct Mark">
                                <i class="fas fa-edit"></i> Correct
                            </button>
                            <button type="button" class="btn btn-action" onclick="resolveError({{ $error->id }})" title="Mark Resolved" style="color: var(--tz-green);">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                        @else
                        <span class="badge badge-green">{{ ucfirst($error->status) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-check-circle empty-icon" style="color: var(--tz-green);"></i>
                            <div class="empty-title">No Errors Found</div>
                            <div class="empty-desc">Great! No validation errors detected for the current filters.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($validationErrors instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
        <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
            Showing {{ $validationErrors->firstItem() ?? 0 }} to {{ $validationErrors->lastItem() ?? 0 }} of {{ $validationErrors->total() ?? 0 }} errors
        </div>
        <div class="pagination-links" style="display: flex; gap: 8px;">
            @if ($validationErrors->onFirstPage())
                <span class="btn btn-outline disabled"><i class="fas fa-chevron-left"></i> Previous</span>
            @else
                <a href="{{ $validationErrors->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
            @endif

            @if ($validationErrors->hasMorePages())
                <a href="{{ $validationErrors->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
            @else
                <span class="btn btn-outline disabled">Next <i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>

<!-- Correction Modal -->
<div id="correctionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div class="adm-card" style="width: 500px; max-width: 95%;">
        <div class="adm-card-head">
            <div class="adm-card-title">Correct Validation Error</div>
            <button onclick="hideCorrectionModal()" style="background:none; border:none; color:#fff; cursor:pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-card-body" style="padding: 24px;">
            <div id="errorInfo" style="margin-bottom: 20px; padding: 12px; background: rgba(239,68,68,0.1); border-left: 3px solid #ef4444; border-radius: 4px;">
                <div style="font-weight: 700; color: #ff7b7b;" id="modalErrorType"></div>
                <div style="font-size: 0.85rem;" id="modalErrorMessage"></div>
            </div>

            <form id="correctionForm">
                @csrf
                <input type="hidden" name="validation_id" id="modalValidationId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div class="adm-filter-group">
                        <label class="adm-filter-label">New Mark (0-50)</label>
                        <input type="number" name="new_mark" id="modalNewMark" class="adm-select" min="0" max="50" step="0.5" placeholder="Enter score">
                    </div>
                    <div class="adm-filter-group">
                        <label class="adm-filter-label">Subject Status</label>
                        <select name="subject_status" id="modalStatus" class="adm-select">
                            <option value="P">Present (P)</option>
                            <option value="ABS">Absent (ABS)</option>
                            <option value="INC">Incomplete (INC)</option>
                        </select>
                    </div>
                </div>

                <div class="adm-filter-group">
                    <label class="adm-filter-label">Correction Comment / Reason</label>
                    <textarea name="comment" class="adm-select" style="height: 100px; padding: 12px; background: rgba(255,255,255,0.05); color: #fff;" placeholder="Why is this change being made?"></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px;">
                    <button type="button" class="btn btn-outline" onclick="hideCorrectionModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Apply Correction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function runValidation() {
    const icon = document.getElementById('runValIcon');
    icon.classList.add('fa-spin');
    
    const params = new URLSearchParams(new FormData(document.getElementById('filterForm'))).toString();
    
    fetch('{{ url("/api/mark-entry/psle/validation/run") }}?' + params, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        icon.classList.remove('fa-spin');
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Validation Complete',
                text: data.message,
                background: '#101518',
                color: '#fff',
                confirmButtonColor: 'var(--tz-blue)'
            }).then(() => window.location.reload());
        }
    })
    .catch(err => {
        icon.classList.remove('fa-spin');
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'Execution Failed',
            text: 'An error occurred while running validation.',
            background: '#101518',
            color: '#fff'
        });
    });
}

function showCorrectionModal(error) {
    document.getElementById('modalValidationId').value = error.id;
    document.getElementById('modalErrorType').textContent = error.error_type;
    document.getElementById('modalErrorMessage').textContent = error.message;
    document.getElementById('modalNewMark').value = error.raw_mark?.paper_1_marks || '';
    document.getElementById('modalStatus').value = error.raw_mark?.subject_status || 'P';
    
    document.getElementById('correctionModal').style.display = 'flex';
}

function hideCorrectionModal() {
    document.getElementById('correctionModal').style.display = 'none';
}

document.getElementById('correctionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('{{ url("/api/mark-entry/psle/validation/correct") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            hideCorrectionModal();
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message,
                timer: 2000,
                showConfirmButton: false,
                background: '#101518',
                color: '#fff'
            }).then(() => window.location.reload());
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                background: '#101518',
                color: '#fff'
            });
        }
    });
});

function resolveError(id) {
    Swal.fire({
        title: 'Resolve Error',
        text: 'Are you sure you want to mark this error as resolved without changing the mark?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Resolve',
        background: '#101518',
        color: '#fff',
        confirmButtonColor: 'var(--tz-green)'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ url("/api/mark-entry/psle/validation/resolve") }}', {
                method: 'POST',
                body: JSON.stringify({ validation_id: id, resolution: 'resolved', comment: 'Manually resolved by officer' }),
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        }
    });
}
</script>
