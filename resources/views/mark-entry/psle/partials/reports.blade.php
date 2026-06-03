<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Reports & Exports</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Reports & Exports</h1>
    <p class="adm-page-desc">Generate official PDF mark sheets, statistical progress reports, and bulk data exports for PSLE 2026.</p>
</div>

<!-- Summary Cards -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-label">Candidates</div>
        <div class="adm-stat-value" style="color: var(--tz-green);">{{ number_format($reportSummary['total_candidates'] ?? 0) }}</div>
        <i class="fas fa-users adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Marks Recorded</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);">{{ number_format($reportSummary['total_marks'] ?? 0) }}</div>
        <i class="fas fa-check-double adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Flagged Outliers</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ number_format($reportSummary['total_outliers'] ?? 0) }}</div>
        <i class="fas fa-radar adm-stat-icon"></i>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Missing Records</div>
        <div class="adm-stat-value" style="color: #ff7b7b;">{{ number_format($reportSummary['total_missing'] ?? 0) }}</div>
        <i class="fas fa-exclamation-triangle adm-stat-icon"></i>
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Generation Filters</div>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="adm-filters" onsubmit="submitCleanedForm(this); return false;">
        <input type="hidden" name="view" value="reports">
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
        </div>
        <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
            <button type="submit" class="btn btn-primary" style="width:100%; height:40px;"><i class="fas fa-sync"></i> Refresh Data</button>
        </div>
    </form>
</div>

<!-- Report Groups -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    
    <!-- Mark Sheets -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title"><i class="fas fa-file-pdf" style="color: var(--tz-red); margin-right: 8px;"></i> Mark Sheets (Official)</div>
        </div>
        <div class="adm-card-body">
            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Subject Mark Sheets</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">PDF candidate list with marks and signature fields.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/entered-marks-pdf/region-zip') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm"><i class="fas fa-file-zipper"></i> ZIP</a>
                </div>
            </div>

            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Blank Scoresheets</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">PDF sheets for manual marking/intake.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/scoresheet-pdf/region-zip') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm"><i class="fas fa-file-zipper"></i> ZIP</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Administrative Reports -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title"><i class="fas fa-chart-line" style="color: var(--tz-green); margin-right: 8px;"></i> Performance & Monitoring</div>
        </div>
        <div class="adm-card-body">
            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Regional Progress Report</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Completion rates by district and school.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/progress/excel') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm" style="color: var(--tz-green);"><i class="fas fa-file-excel"></i> Excel</a>
                </div>
            </div>

            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Officer Productivity</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Mark entry counts and speed by officer.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/productivity/excel') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm" style="color: var(--tz-green);"><i class="fas fa-file-excel"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quality Assurance -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title"><i class="fas fa-shield-check" style="color: var(--tz-yellow); margin-right: 8px;"></i> Quality Assurance</div>
        </div>
        <div class="adm-card-body">
            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Missing Marks Report</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">List of candidates with no recorded marks.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/missing-marks/excel') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm" style="color: var(--tz-green);"><i class="fas fa-file-excel"></i> Excel</a>
                </div>
            </div>

            <div class="report-item" style="padding: 15px; border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-weight: 600;">Outliers & Anomalies</div>
                    <div style="font-size: 0.8rem; color: var(--tz-text-muted);">Export flagged suspicious marks.</div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <a href="{{ url('/api/mark-entry/psle/reports/outliers/excel') }}?exam_year_id={{ $activeFilters['exam_year_id'] }}&region_id={{ $activeFilters['region_id'] }}" class="btn btn-outline btn-sm" style="color: var(--tz-green);"><i class="fas fa-file-excel"></i> Excel</a>
                </div>
            </div>
        </div>
    </div>

    @if($isAdmin || $isReo)
    <!-- Final Exports -->
    <div class="adm-card" style="border: 1px solid rgba(252,209,22,0.3); background: rgba(252,209,22,0.03);">
        <div class="adm-card-head">
            <div class="adm-card-title"><i class="fas fa-box-open" style="color: var(--tz-yellow); margin-right: 8px;"></i> Final Data Packages</div>
        </div>
        <div class="adm-card-body">
            <div class="report-item" style="padding: 20px; border: 1px dashed rgba(252,209,22,0.3); border-radius: 8px; display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <div style="font-weight: 700; color: var(--tz-yellow);">Full Regional Export</div>
                    <div style="font-size: 0.85rem; color: var(--tz-text-muted);">Consolidated ZIP package containing all locked marks for the region. Ready for national promotion.</div>
                </div>
                <div style="display: flex; gap: 10px; width: 100%;">
                    @php
                        $regionalPackageExamYearLabel = \App\Models\ExamYear::where('id', $activeFilters['exam_year_id'] ?? null)->value('year_label');

                        $regionalPackageFilters = [
                            'exam_year_id' => $activeFilters['exam_year_id'] ?? null,
                            'region_id' => $activeFilters['region_id'] ?? null,
                            'exam_year' => $regionalPackageExamYearLabel,
                        ];

                        $regionalPackageFilters = array_filter($regionalPackageFilters, function ($value) {
                            return $value !== null && $value !== '';
                        });

                        $regionalPackageUrl = url('/api/mark-entry/psle/reports/entered-marks-pdf/region-zip') . '?' . http_build_query($regionalPackageFilters);
                    @endphp

                    @if(!empty($activeFilters['exam_year_id']) && !empty($activeFilters['region_id']))
                        <a href="{{ $regionalPackageUrl }}" class="btn btn-primary" style="background: var(--tz-yellow); color: #000; width: 100%; text-decoration: none; text-align: center; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fas fa-download"></i> Download Regional Package
                        </a>
                    @else
                        <button class="btn btn-primary" style="background: var(--tz-yellow); color: #000; width: 100%;" disabled title="Please select Exam Year and Region first">
                            <i class="fas fa-lock"></i> Download Regional Package
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

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
