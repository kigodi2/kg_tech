                <div class="adm-breadcrumb">
                    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Entry & Validation</span>
                </div>

                <div class="adm-page-header">
                    <h1 class="adm-page-title">Entry & Validation</h1>
                    <p class="adm-page-desc">Monitor mark entry readiness, subject entry status, validation errors, and missing marks.</p>
                </div>

                <!-- Summary Cards -->
                <div class="adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">Registered Candidates</div>
                        <div class="adm-stat-value" style="color: #fff;">{{ number_format($validationSummary['registered'] ?? 0) }}</div>
                        <i class="fas fa-users adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Candidates With Marks</div>
                        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($validationSummary['with_marks'] ?? 0) }}</div>
                        <i class="fas fa-user-check adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Pending Marks</div>
                        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ number_format($validationSummary['pending'] ?? 0) }}</div>
                        <i class="fas fa-clock adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Missing Marks</div>
                        <div class="adm-stat-value" style="color: #ff7b7b;">{{ number_format($validationSummary['missing'] ?? 0) }}</div>
                        <i class="fas fa-circle-exclamation adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Invalid Marks</div>
                        <div class="adm-stat-value" style="color: #ef4444;">{{ number_format($validationSummary['invalid'] ?? 0) }}</div>
                        <i class="fas fa-triangle-exclamation adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">ABS Candidates</div>
                        <div class="adm-stat-value" style="color: #ffb74d;">{{ number_format($validationSummary['abs'] ?? 0) }}</div>
                        <i class="fas fa-user-minus adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Validation Warnings</div>
                        <div class="adm-stat-value" style="color: #60a5fa;">{{ number_format($validationSummary['warnings'] ?? 0) }}</div>
                        <i class="fas fa-circle-info adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Ready for Review</div>
                        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($validationSummary['ready_for_review'] ?? 0) }}</div>
                        <i class="fas fa-check-circle adm-stat-icon"></i>
                    </div>
                </div>

                <!-- Context Filters -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Mark Entry Scope</div>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" class="adm-filters">
                        <input type="hidden" name="view" value="entry-validation">
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
                        <div class="adm-filter-group" style="display:flex; align-items:flex-end; gap: 8px;">
                            <button type="submit" class="btn btn-primary" style="flex:1; height:40px;"><i class="fas fa-filter"></i> Apply</button>
                            <a href="{{ url()->current() }}?view=entry-validation" class="btn btn-outline" style="height:40px; display:flex; align-items:center; justify-content:center;" title="Reset Filters"><i class="fas fa-rotate-left"></i></a>
                        </div>
                    </form>
                </div>

                <!-- Subject Entry Status Table -->
                <div class="adm-card" style="margin-top: 24px;">
                    <div class="adm-card-head" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="adm-card-title">Subject Entry Status</div>
                        <div class="adm-card-actions">
                            <button class="btn btn-outline btn-sm" onclick="runValidationBulk()"><i class="fas fa-play"></i> Run Validation for All</button>
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <div class="adm-table-container">
                            <table class="adm-table">
                                <thead>
                                    <tr>
                                        <th style="min-width: 150px;">School</th>
                                        <th style="min-width: 120px;">Subject</th>
                                        <th class="text-center">Registered</th>
                                        <th class="text-center">Entered</th>
                                        <th class="text-center">Missing</th>
                                        <th class="text-center">Invalid</th>
                                        <th class="text-center">ABS</th>
                                        <th style="min-width: 120px;">Progress</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($subjectEntryStatus ?? [] as $item)
                                        <tr>
                                            <td>
                                                <div style="font-weight: 700;">{{ $item['school']->name }}</div>
                                                <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ $item['school']->code }} | {{ $item['school']->district->name ?? '' }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight: 600;">{{ $item['subject']->name }}</div>
                                                <div style="font-size: 0.7rem; color: var(--tz-text-muted);">{{ $item['subject']->code }}</div>
                                            </td>
                                            <td class="text-center">{{ number_format($item['registered']) }}</td>
                                            <td class="text-center" style="color: var(--tz-green); font-weight: 700;">{{ number_format($item['entered']) }}</td>
                                            <td class="text-center" style="color: {{ $item['missing'] > 0 ? '#ff7b7b' : 'inherit' }};">{{ number_format($item['missing']) }}</td>
                                            <td class="text-center" style="color: {{ $item['invalid'] > 0 ? '#ef4444' : 'inherit' }}; font-weight: 700;">{{ number_format($item['invalid']) }}</td>
                                            <td class="text-center">{{ number_format($item['abs']) }}</td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden;">
                                                        <div style="width: {{ $item['progress'] }}%; height: 100%; background: {{ $item['progress'] == 100 ? 'var(--tz-green)' : 'var(--tz-yellow)' }};"></div>
                                                    </div>
                                                    <span style="font-size: 0.75rem; font-weight: 700; min-width: 35px;">{{ $item['progress'] }}%</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $item['status_badge'] }}">{{ $item['status'] }}</span>
                                            </td>
                                            <td class="text-right">
                                                <div style="display: flex; gap: 4px; justify-content: flex-end;">
                                                    <a href="{{ url()->current() }}?view=entry-sheet&exam_year_id={{ $activeFilters['exam_year_id'] }}&school_id={{ $item['school']->id }}&subject_id={{ $item['subject']->id }}" 
                                                       class="btn btn-action" title="{{ $isAdmin || $isReo ? 'View Entry Sheet (Read-Only)' : 'Open Entry Sheet' }}">
                                                        <i class="fas {{ $isAdmin || $isReo ? 'fa-eye' : 'fa-edit' }}"></i>
                                                    </a>
                                                    <a href="{{ url()->current() }}?view=missing-marks&school_id={{ $item['school']->id }}&subject_id={{ $item['subject']->id }}" 
                                                       class="btn btn-action" title="View Missing Marks">
                                                        <i class="fas fa-search-minus"></i>
                                                    </a>
                                                    <a href="{{ url()->current() }}?view=validation-errors&school_id={{ $item['school']->id }}&subject_id={{ $item['subject']->id }}" 
                                                       class="btn btn-action" title="View Validation Errors">
                                                        <i class="fas fa-triangle-exclamation"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-action" onclick="runValidationSingle({{ $item['school']->id }}, {{ $item['subject']->id }})" title="Run Validation">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10">
                                                <div class="empty-state" style="padding: 40px;">
                                                    <i class="fas fa-folder-open empty-icon"></i>
                                                    <div class="empty-title">No Data Found</div>
                                                    <div class="empty-desc">Adjust your filters to see results.</div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @if(isset($paginatedSchools) && $paginatedSchools->hasPages())
                    <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
                        <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
                            Showing {{ $paginatedSchools->firstItem() }} to {{ $paginatedSchools->lastItem() }} of {{ $paginatedSchools->total() }} schools
                        </div>
                        <div class="pagination-links" style="display: flex; gap: 8px;">
                            @if ($paginatedSchools->onFirstPage())
                                <span class="btn btn-outline disabled"><i class="fas fa-chevron-left"></i> Previous</span>
                            @else
                                <a href="{{ $paginatedSchools->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
                            @endif

                            @if ($paginatedSchools->hasMorePages())
                                <a href="{{ $paginatedSchools->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
                            @else
                                <span class="btn btn-outline disabled">Next <i class="fas fa-chevron-right"></i></span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <script>
                    function runValidationSingle(schoolId, subjectId) {
                        const btn = event.currentTarget;
                        const originalHtml = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                        const params = new URLSearchParams({
                            exam_year_id: '{{ $activeFilters["exam_year_id"] }}',
                            region_id: '{{ $activeFilters["region_id"] }}',
                            school_id: schoolId,
                            subject_id: subjectId
                        });

                        fetch('{{ url("/api/mark-entry/psle/validation/run") }}?' + params, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Validation Complete',
                                    text: `Scanned ${data.processed_count} records. Found ${data.error_count ?? 0} issues.`,
                                    background: '#101518',
                                    color: '#fff',
                                    confirmButtonColor: 'var(--tz-green)'
                                }).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'Error running validation');
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Execution Failed',
                                text: error.message,
                                background: '#101518',
                                color: '#fff'
                            });
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                    }

                    function runValidationBulk() {
                        Swal.fire({
                            title: 'Run Validation',
                            text: 'This will scan all marks in the current filtered scope. Are you sure?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, Run Validation',
                            background: '#101518',
                            color: '#fff',
                            confirmButtonColor: 'var(--tz-blue)'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const btn = event.currentTarget;
                                const originalHtml = btn.innerHTML;
                                btn.disabled = true;
                                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';

                                const params = new URLSearchParams({
                                    exam_year_id: '{{ $activeFilters["exam_year_id"] }}',
                                    region_id: '{{ $activeFilters["region_id"] }}',
                                    district_id: '{{ $activeFilters["district_id"] }}',
                                    school_id: '{{ $activeFilters["school_id"] }}',
                                    subject_id: '{{ $activeFilters["subject_id"] }}'
                                });

                                fetch('{{ url("/api/mark-entry/psle/validation/run") }}?' + params, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Validation Complete',
                                            text: `Bulk validation finished. Scanned ${data.processed_count} records.`,
                                            background: '#101518',
                                            color: '#fff',
                                            confirmButtonColor: 'var(--tz-green)'
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    } else {
                                        throw new Error(data.message || 'Error running validation');
                                    }
                                })
                                .catch(error => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Execution Failed',
                                        text: error.message,
                                        background: '#101518',
                                        color: '#fff'
                                    });
                                })
                                .finally(() => {
                                    btn.disabled = false;
                                    btn.innerHTML = originalHtml;
                                });
                            }
                        });
                    }
                </script>
