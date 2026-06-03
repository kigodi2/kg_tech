<style>
    .btn-locked {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        color: rgba(255, 255, 255, 0.22) !important;
        cursor: not-allowed !important;
        pointer-events: none;
        box-shadow: none !important;
    }
    .btn-download-active {
        background: rgba(0, 163, 221, 0.12) !important;
        border: 1px solid var(--tz-blue) !important;
        color: #fff !important;
        text-shadow: 0 0 10px rgba(0, 163, 221, 0.3);
        box-shadow: 0 0 15px rgba(0, 163, 221, 0.15) !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
    }
    .btn-download-active:hover {
        background: var(--tz-blue) !important;
        color: #000 !important;
        box-shadow: 0 0 25px rgba(0, 163, 221, 0.45) !important;
        transform: translateY(-1px);
    }
    .btn-zip-active {
        background: rgba(30, 181, 58, 0.12) !important;
        border: 1px solid var(--tz-green) !important;
        color: #fff !important;
        text-shadow: 0 0 10px rgba(30, 181, 58, 0.3);
        box-shadow: 0 0 15px rgba(30, 181, 58, 0.15) !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
    }
    .btn-zip-active:hover {
        background: var(--tz-green) !important;
        color: #000 !important;
        box-shadow: 0 0 25px rgba(30, 181, 58, 0.45) !important;
        transform: translateY(-1px);
    }
    .btn-raw-export {
        background: linear-gradient(135deg, var(--tz-blue), #0077b6) !important;
        border: none !important;
        color: #fff !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(0, 163, 221, 0.3) !important;
        transition: all 0.3s ease !important;
        cursor: pointer !important;
    }
    .btn-raw-export:hover {
        transform: translateY(-1.5px);
        box-shadow: 0 6px 20px rgba(0, 163, 221, 0.5) !important;
    }
</style>

                <div class="adm-breadcrumb">
                    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Reports & Exports</span>
                </div>

                <div class="adm-page-header">
                    <h1 class="adm-page-title">Reports & Exports</h1>
                    <p class="adm-page-desc">Generate PSLE mark-entry reports, PDFs, Excel exports, and ZIP packages for the selected scope.</p>
                </div>

                <!-- Summary Cards -->
                <div class="adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">Total Candidates</div>
                        <div class="adm-stat-value" style="color: #fff;">{{ number_format($reportSummary['total_candidates'] ?? 0) }}</div>
                        <i class="fas fa-users adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Marks Recorded</div>
                        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($reportSummary['total_marks'] ?? 0) }}</div>
                        <i class="fas fa-check-double adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Missing Marks</div>
                        <div class="adm-stat-value" style="color: #ff7b7b;">{{ number_format($reportSummary['total_missing'] ?? 0) }}</div>
                        <i class="fas fa-circle-exclamation adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Detected Outliers</div>
                        <div class="adm-stat-value" style="color: #ffb74d;">{{ number_format($reportSummary['total_outliers'] ?? 0) }}</div>
                        <i class="fas fa-chart-line adm-stat-icon"></i>
                    </div>
                </div>

                <!-- Scope Filters -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Reporting Scope</div>
                    </div>
                    <form method="GET" action="{{ url()->current() }}" class="adm-filters" onsubmit="submitCleanedForm(this); return false;">
                        <input type="hidden" name="view" value="reports-exports">
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Exam Year</label>
                            <select name="exam_year_id" class="adm-select" onchange="submitCleanedForm(this.form)">
                                @foreach($examYears ?? [] as $yr)
                                    <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Region</label>
                            <select name="region_id" class="adm-select" onchange="submitCleanedForm(this.form)" {{ !empty($allowedRegionId) ? 'disabled' : '' }}>
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
                            <label class="adm-filter-label">District</label>
                            <select name="district_id" class="adm-select" onchange="submitCleanedForm(this.form)">
                                <option value="">All Districts</option>
                                @foreach($districts ?? [] as $dist)
                                    <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">School</label>
                            <select name="school_id" class="adm-select" onchange="submitCleanedForm(this.form)">
                                <option value="">All Schools</option>
                                @foreach($schools ?? [] as $sch)
                                    <option value="{{ $sch->id }}" {{ ($activeFilters['school_id'] ?? '') == $sch->id ? 'selected' : '' }}>{{ $sch->code }} - {{ $sch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="adm-filter-group">
                            <label class="adm-filter-label">Subject</label>
                            <select name="subject_id" class="adm-select" onchange="submitCleanedForm(this.form)">
                                <option value="">All Subjects</option>
                                @foreach($psleSubjects as $subj)
                                    <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Available Reports -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Available Reports</div>
                        <div class="adm-card-head-actions">
                            <a href="{{ url('/api/mark-entry/psle/reports/export') }}?{{ http_build_query($activeFilters) }}" class="btn btn-raw-export btn-sm">
                                <i class="fas fa-file-csv"></i> Export Raw Data
                            </a>
                        </div>
                    </div>
                    <div class="adm-card-body" style="padding: 0;">
                        <table class="adm-table">
                            <thead>
                                <tr>
                                    <th>Report Name</th>
                                    <th>Description</th>
                                    <th>Format</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PDF Reports -->
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Subject Mark Sheet</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Blank scoresheet for manual mark entry.</div>
                                    </td>
                                    <td>
                                        @if($activeFilters['school_id'] && $activeFilters['subject_id'])
                                            <span class="badge badge-outline">Single Subject</span>
                                        @elseif($activeFilters['school_id'])
                                            <span class="badge badge-outline">School ZIP</span>
                                        @elseif($activeFilters['district_id'])
                                            <span class="badge badge-outline">District ZIP</span>
                                        @else
                                            <span class="badge badge-yellow">Select School/Subject</span>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-red">PDF</span></td>
                                    <td style="text-align: right;">
                                        @if($activeFilters['school_id'] && $activeFilters['subject_id'])
                                            <a href="{{ url('/api/mark-entry/psle/reports/scoresheet-pdf') }}?{{ http_build_query(array_merge($activeFilters, ['exam_year' => \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'])->value('year_label')])) }}" class="btn btn-download-active btn-sm">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        @elseif($activeFilters['school_id'])
                                            <a href="{{ url('/api/mark-entry/psle/reports/scoresheet-pdf/school-zip') }}?{{ http_build_query(array_merge($activeFilters, ['exam_year' => \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'])->value('year_label')])) }}" class="btn btn-zip-active btn-sm">
                                                <i class="fas fa-file-archive"></i> Download ZIP
                                            </a>
                                        @elseif($activeFilters['district_id'])
                                            <a href="{{ url('/api/mark-entry/psle/reports/scoresheet-pdf/district-zip') }}?{{ http_build_query(array_merge($activeFilters, ['exam_year' => \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'])->value('year_label')])) }}" class="btn btn-zip-active btn-sm">
                                                <i class="fas fa-file-archive"></i> Download ZIP
                                            </a>
                                        @else
                                            <button class="btn btn-locked btn-sm" disabled title="Please select a School or District">
                                                <i class="fas fa-lock"></i> Locked
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Entered Marks Verification</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Verification sheet showing marks already entered.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Entered Data</span></td>
                                    <td><span class="badge badge-red">PDF</span></td>
                                    <td style="text-align: right;">
                                        @if($activeFilters['school_id'] && $activeFilters['subject_id'])
                                            <a href="{{ url('/api/mark-entry/psle/reports/entered-marks-pdf') }}?{{ http_build_query(array_merge($activeFilters, ['exam_year' => \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'])->value('year_label')])) }}" class="btn btn-download-active btn-sm">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        @elseif($activeFilters['school_id'])
                                            <a href="{{ url('/api/mark-entry/psle/reports/entered-marks-pdf/school-zip') }}?{{ http_build_query(array_merge($activeFilters, ['exam_year' => \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'])->value('year_label')])) }}" class="btn btn-zip-active btn-sm">
                                                <i class="fas fa-file-archive"></i> Download ZIP
                                            </a>
                                        @else
                                            <button class="btn btn-locked btn-sm" disabled title="Please select a School">
                                                <i class="fas fa-lock"></i> Locked
                                            </button>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Excel/CSV Reports -->
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Missing Marks Report</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Detailed list of candidates with missing marks.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Data Audit</span></td>
                                    <td><span class="badge badge-green">CSV</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ url('/api/mark-entry/psle/reports/missing-marks/excel') }}?{{ http_build_query($activeFilters) }}" class="btn btn-zip-active btn-sm">
                                            <i class="fas fa-file-excel"></i> Export
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Validation Errors Report</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Full list of validation conflicts and errors.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Data Integrity</span></td>
                                    <td><span class="badge badge-green">CSV</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ url('/api/mark-entry/psle/reports/validation-errors/csv') }}?{{ http_build_query($activeFilters) }}" class="btn btn-zip-active btn-sm">
                                            <i class="fas fa-file-excel"></i> Export
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Outliers & Patterns</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Statistical outliers and extreme mark patterns.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Moderation</span></td>
                                    <td><span class="badge badge-green">CSV</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ url('/api/mark-entry/psle/reports/outliers/excel') }}?{{ http_build_query($activeFilters) }}" class="btn btn-zip-active btn-sm">
                                            <i class="fas fa-file-excel"></i> Export
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Regional Progress Report</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Summary of mark entry progress across the region.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Monitoring</span></td>
                                    <td><span class="badge badge-green">CSV</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ url('/api/mark-entry/psle/reports/progress/excel') }}?{{ http_build_query($activeFilters) }}" class="btn btn-zip-active btn-sm">
                                            <i class="fas fa-file-excel"></i> Export
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;">Officer Activity Report</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">Productivity and activity log for mark entry officers.</div>
                                    </td>
                                    <td><span class="badge badge-outline">Governance</span></td>
                                    <td><span class="badge badge-green">CSV</span></td>
                                    <td style="text-align: right;">
                                        <a href="{{ url('/api/mark-entry/psle/reports/productivity/excel') }}?{{ http_build_query($activeFilters) }}" class="btn btn-zip-active btn-sm">
                                            <i class="fas fa-file-excel"></i> Export
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="adm-card" style="margin-top: 20px; border-left: 4px solid var(--tz-blue);">
                    <div class="adm-card-body" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fas fa-info-circle" style="color: var(--tz-blue); font-size: 1.2rem;"></i>
                            <div>
                                <div style="font-weight: 600; color: #fff;">Reporting Compliance</div>
                                <div style="font-size: 0.85rem; color: #94a3b8;">
                                    All report generations and exports are logged in the Governance Audit Log with your User ID and IP address.
                                    Unauthorized attempts to export data outside your assigned region will be flagged.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

<script>
function submitCleanedForm(form) {
    const params = new URLSearchParams();
    const formData = new FormData(form);
    for (const [key, val] of formData.entries()) {
        if (val !== null && val !== undefined && val !== '' && val !== 'all' && val !== '0') {
            params.append(key, val);
        }
    }
    const viewInput = form.querySelector('input[name="view"]');
    if (viewInput && !params.has('view')) {
        params.append('view', viewInput.value);
    }
    window.location.href = form.action + '?' + params.toString();
}
</script>
