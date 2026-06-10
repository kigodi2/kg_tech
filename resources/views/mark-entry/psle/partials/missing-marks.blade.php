<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Missing Marks</span>
</div>

<style>
    .mark-input {
        height: 28px !important;
        font-size: 0.85rem !important;
        font-weight: 600;
        width: 80px;
        text-align: center;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #fff;
    }
    .mark-input-error {
        border-color: var(--tz-red) !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }
    .mark-input-save-error {
        border-color: var(--tz-yellow) !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.18) !important;
    }
</style>

<div class="adm-page-header">
    <h1 class="adm-page-title">Missing Marks Report</h1>
    <p class="adm-page-desc">Identify, validate, and manage ABS and INC candidates' missing marks.</p>
</div>

@if(isset($isAdmin) && $isAdmin && isset($dataQualityIssues) && $dataQualityIssues->isNotEmpty())
<div class="adm-card" style="border: 1px solid #f87171; background: rgba(239, 68, 68, 0.05); margin-bottom: 20px;">
    <div class="adm-card-head" style="border-bottom: 1px solid rgba(248, 113, 113, 0.2); padding: 12px 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-triangle-exclamation" style="color: #f87171; font-size: 1.2rem;"></i>
        <div class="adm-card-title" style="color: #f87171; font-size: 1rem; font-weight: 700;">Data Quality Alert: PSLE Candidates Registered at Non-Primary Schools</div>
    </div>
    <div class="adm-card-body" style="padding: 15px 20px;">
        <p style="color: var(--tz-text-muted); font-size: 0.9rem; margin-bottom: 12px; line-height: 1.5;">
            The following non-primary schools have candidates registered for PSLE exams in the selected year. Since PSLE is restricted to primary schools, these candidates will not appear in the standard primary missing marks count below, but their data should be corrected in the system registration registry.
        </p>
        <div class="table-responsive">
            <table class="missing-marks-table" style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <th style="color: #f87171; text-align: left; padding: 10px 12px;">School Code</th>
                        <th style="color: #f87171; text-align: left; padding: 10px 12px;">School Name</th>
                        <th style="color: #f87171; text-align: left; padding: 10px 12px;">School Level</th>
                        <th style="color: #f87171; text-align: left; padding: 10px 12px;">School Type</th>
                        <th style="color: #f87171; text-align: center; padding: 10px 12px;">PSLE Registered Candidates</th>
                        <th style="color: #f87171; text-align: right; padding: 10px 12px;">Recommended Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dataQualityIssues as $school)
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
                        <td style="padding: 8px 12px;"><code>{{ $school->code }}</code></td>
                        <td style="padding: 8px 12px;"><strong>{{ $school->name }}</strong></td>
                        <td style="padding: 8px 12px;"><span class="badge badge-blue" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd;">{{ $school->education_level }}</span></td>
                        <td style="padding: 8px 12px;"><span class="badge badge-outline" style="border-color: rgba(255,255,255,0.2); color: #d1d5db;">{{ $school->school_type }}</span></td>
                        <td class="text-center" style="padding: 8px 12px; font-weight: bold; color: #fca5a5;">{{ number_format($school->candidate_count) }}</td>
                        <td class="text-right" style="padding: 8px 12px; color: #fca5a5; font-size: 0.85rem;"><i class="fas fa-edit"></i> Update School Metadata to Primary</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Context Filters -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Filter Missing Marks</div>
    </div>
    <form method="GET" action="{{ url()->current() }}" class="adm-filters">
        <input type="hidden" name="view" value="missing-marks">
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
            <label class="adm-filter-label">Classification Filter</label>
            <select name="classification" class="adm-select" onchange="this.form.submit()">
                <option value="all" {{ ($classification ?? 'all') == 'all' ? 'selected' : '' }}>All Challenges</option>
                <option value="abs" {{ ($classification ?? 'all') == 'abs' ? 'selected' : '' }}>ABS only</option>
                <option value="inc" {{ ($classification ?? 'all') == 'inc' ? 'selected' : '' }}>INC only</option>
                <option value="pending" {{ ($classification ?? 'all') == 'pending' ? 'selected' : '' }}>Pending approval</option>
                <option value="approved" {{ ($classification ?? 'all') == 'approved' ? 'selected' : '' }}>Approved ABS</option>
                <option value="committed" {{ ($classification ?? 'all') == 'committed' ? 'selected' : '' }}>Committed ABS</option>
                <option value="rejected" {{ ($classification ?? 'all') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="adm-filter-group" style="display:flex; align-items:flex-end; gap: 8px;">
            <button type="submit" class="btn btn-primary" style="flex:1; height:40px;"><i class="fas fa-filter"></i> Apply</button>
            <a href="{{ url('/mark-entry/psle?view=missing-marks') }}" class="btn btn-outline" style="height:40px;" title="Reset Filters"><i class="fas fa-undo"></i></a>
        </div>
    </form>
</div>

<!-- Status Legend Card -->
<div class="adm-card" style="margin-bottom: 20px; background: rgba(255, 255, 255, 0.02);">
    <div class="adm-card-body" style="padding: 12px 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
        <span style="font-weight: 700; color: var(--tz-text-muted); font-size: 0.85rem;">Status Legend:</span>
        <div style="display: flex; align-items: center; gap: 6px;">
            <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 2px 6px; font-size: 0.75rem;">ABS</span>
            <span style="font-size: 0.8rem; color: var(--tz-text-muted);">No marks entered in any required subjects (ABS candidate)</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 6px; font-size: 0.75rem;">INC</span>
            <span style="font-size: 0.8rem; color: var(--tz-text-muted);">Partial marks entered (some entered, some missing)</span>
        </div>
        <div style="display: flex; align-items: center; gap: 6px;">
            <span class="badge" style="background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.4); padding: 2px 6px; font-size: 0.75rem;">X</span>
            <span style="font-size: 0.8rem; color: var(--tz-text-muted);">Official approved absent mark committed to raw marks</span>
        </div>
    </div>
</div>

@if(isset($schoolDetails) && $schoolDetails)
<!-- ================== SCHOOL DETAIL MODE ================== -->
<div style="margin-bottom: 15px;">
    <a href="{{ url('/mark-entry/psle?view=missing-marks&exam_year_id=' . $selectedYearId . '&region_id=' . $selectedRegionId . '&district_id=' . $selectedDistrictId . '&classification=' . $classification) }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="fas fa-arrow-left"></i> Back to School Summaries
    </a>
</div>

<!-- Warning / Commit Action Box -->
@if(($schoolDetails['pending_count'] ?? 0) > 0)
<div class="adm-card" style="border: 1px solid var(--tz-green); background: rgba(16, 185, 129, 0.05); margin-bottom: 20px;">
    <div class="adm-card-body" style="padding: 20px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
        <div>
            <h3 style="color: var(--tz-green); font-size: 1.1rem; margin: 0 0 5px 0; font-weight: 700;">
                <i class="fas fa-check-circle"></i> Approved ABS Records Pending Commit
            </h3>
            <p style="color: var(--tz-text-muted); font-size: 0.9rem; margin: 0;">
                There are approved ABS records for this school. You can now commit them to the official raw marks database.
            </p>
        </div>
        <div>
            @if($isAdmin || $isReo)
                <button type="button" class="btn btn-success" id="btn-commit-abs" style="background-color: var(--tz-green); border-color: var(--tz-green); color: white; padding: 10px 20px; font-weight: bold; border-radius: 4px; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-cloud-upload-alt"></i> Commit Approved ABS to Official X Marks
                </button>
            @else
                <button type="button" class="btn btn-success" disabled style="opacity: 0.5; cursor: not-allowed;">
                    <i class="fas fa-cloud-upload-alt"></i> Commit (MEO Read-only)
                </button>
            @endif
        </div>
    </div>
</div>
@endif

<!-- Batch Approval Actions Card -->
@if($isAdmin || $isReo)
<div class="adm-card" id="approve-actions-card" style="display: none; margin-bottom: 20px; border: 1px solid var(--tz-blue); background: rgba(59, 130, 246, 0.05);">
    <div class="adm-card-head">
        <div class="adm-card-title"><i class="fas fa-check-double"></i> Batch Actions for Selected ABS Challenges</div>
    </div>
    <div class="adm-card-body" style="padding: 20px;">
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div>
                <label class="adm-filter-label" style="font-weight: 700; margin-bottom: 5px;">Approval Remark / Reason (Mandatory for approvals) <span style="color: var(--tz-red);">*</span></label>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <select id="select-preset-remark" class="adm-select" style="max-width: 300px;">
                        <option value="">-- Choose preset remark --</option>
                        <option value="Confirmed from attendance sheet">Confirmed from attendance sheet</option>
                        <option value="Confirmed by headteacher">Confirmed by headteacher</option>
                        <option value="Confirmed by examination record">Confirmed by examination record</option>
                    </select>
                    <span style="color: var(--tz-text-muted);">or type custom:</span>
                    <input type="text" id="input-custom-remark" class="adm-select" style="flex: 1; min-width: 250px;" placeholder="Type custom remark here...">
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <label style="display: inline-flex; align-items: center; gap: 8px; margin-right: 15px; cursor: pointer; font-size: 0.9rem; color: var(--tz-text-muted);">
                    <input type="checkbox" id="select-all-abs" style="transform: scale(1.1);"> Select All ABS on Page
                </label>
                <button type="button" class="btn btn-primary" id="btn-approve-selected" style="background-color: var(--tz-blue); border-color: var(--tz-blue); display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check"></i> Approve Selected ABS
                </button>
                <button type="button" class="btn btn-outline" id="btn-reject-selected" style="display: inline-flex; align-items: center; gap: 6px; color: var(--tz-red); border-color: var(--tz-red);">
                    <i class="fas fa-times"></i> Reject Selected ABS
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Candidate Detail Table -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">School Candidates & Subjects Drill-down</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table class="missing-marks-table" style="white-space: nowrap; width: 100%;">
            <thead>
                <tr>
                    <th>Index Number</th>
                    <th>PREM No</th>
                    <th>Candidate Name</th>
                    <th class="text-center">Sex</th>
                    @foreach($schoolDetails['subjects'] as $subject)
                        <th class="text-center" style="font-family: monospace;">{{ \App\Services\MarkEntry\PsleMissingMarksService::getSubjectShortCode($subject->code) }}</th>
                    @endforeach
                    <th>Candidate Status</th>
                    <th>Remarks / Challenge</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schoolDetails['rows'] as $row)
                <tr>
                    <td><code>{{ $row['candidate']->candidate_id }}</code></td>
                    <td>{{ $row['candidate']->prem_no ?? 'N/A' }}</td>
                    <td><strong>{{ $row['candidate']->full_name }}</strong></td>
                    <td class="text-center">{{ $row['candidate']->gender }}</td>
                    
                    @foreach($schoolDetails['subjects'] as $subject)
                        @php
                            $cell = $row['subject_cells'][$subject->id] ?? null;
                        @endphp
                        <td class="text-center">
                            @if($cell)
                                @if($cell['status'] === 'numeric_mark')
                                    <span style="font-weight: bold; color: var(--tz-text-muted);">{{ $cell['display'] }}</span>
                                @elseif($cell['status'] === 'committed_abs')
                                    <span class="badge" style="background: rgba(100, 116, 139, 0.2); color: #94a3b8; border: 1px solid rgba(100, 116, 139, 0.4);" title="Committed ABS">X</span>
                                @elseif($cell['status'] === 'approved_abs')
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);" title="Approved ABS (Pending Commit)">Approved</span>
                                @elseif($cell['status'] === 'rejected')
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4);" title="Rejected ABS Request">Rejected</span>
                                @elseif($cell['status'] === 'missing')
                                    @if($row['classification'] === 'ABS')
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                            <span class="badge badge-red" style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 2px 6px;">ABS</span>
                                            @if($isAdmin || $isReo)
                                                <input type="checkbox" class="abs-checkbox" 
                                                       data-candidate-id="{{ $row['candidate']->id }}" 
                                                       data-subject-id="{{ $subject->id }}"
                                                       data-school-id="{{ $selectedSchoolId }}"
                                                       data-exam-year-id="{{ $selectedYearId }}">
                                            @endif
                                        </div>
                                    @else
                                        @php
                                            $hasIncPermission = false;
                                            if ($isAdmin) {
                                                $hasIncPermission = true;
                                            } elseif ($isReo) {
                                                $hasIncPermission = $user->region_id && ((int) ($row['candidate']->school->region_id ?? 0) === (int) $user->region_id);
                                            } elseif ($isMarkOfficer) {
                                                if ($user->region_id) {
                                                    $hasIncPermission = (int) ($row['candidate']->school->region_id ?? 0) === (int) $user->region_id;
                                                } else {
                                                    $hasIncPermission = $userAssignments->contains(function ($assignment) use ($row, $subject, $selectedYearId) {
                                                        return (int) $assignment->school_id === (int) $row['candidate']->school_id
                                                            && (int) $assignment->subject_id === (int) $subject->id
                                                            && (int) $assignment->exam_year_id === (int) $selectedYearId;
                                                    });
                                                }
                                            }
                                        @endphp
                                        @if($hasIncPermission)
                                            <span class="badge badge-yellow inc-clickable-badge" 
                                                  style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 6px; cursor: pointer;"
                                                  data-candidate-id="{{ $row['candidate']->id }}"
                                                  data-school-id="{{ $row['candidate']->school_id }}"
                                                  data-subject-id="{{ $subject->id }}"
                                                  data-subject-code="{{ \App\Services\MarkEntry\PsleMissingMarksService::getSubjectShortCode($subject->code) }}"
                                                  data-subject-max-marks="{{ $subject->max_marks }}"
                                                  data-candidate-name="{{ $row['candidate']->full_name }}"
                                                  data-index-number="{{ $row['candidate']->candidate_id }}"
                                                  data-prem-number="{{ $row['candidate']->prem_no ?? 'N/A' }}"
                                                  data-school-name="{{ $row['candidate']->school->name ?? 'N/A' }}"
                                                  title="Click to complete missing INC mark">INC</span>
                                        @else
                                            <span class="badge badge-yellow" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 6px; opacity: 0.65;" title="Read-only: Missing mark entry assignment">INC</span>
                                        @endif
                                    @endif
                                @elseif($cell['status'] === 'not_applicable')
                                    <span class="text-muted">-</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    @endforeach
                    
                    <td>
                        @if($row['classification'] === 'COMPLETE')
                            <span class="badge badge-success" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">Complete</span>
                        @elseif($row['classification'] === 'ABS')
                            <span class="badge badge-danger" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">ABS</span>
                        @else
                            <span class="badge badge-warning" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24;">INC</span>
                        @endif
                    </td>
                    <td style="font-size: 0.85rem; max-width: 200px; white-space: normal;" title="{{ $row['remarks'] }}">
                        {{ $row['remarks'] ?: '-' }}
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="{{ 6 + count($schoolDetails['subjects']) }}">
                        <div class="empty-state">
                            <i class="fas fa-check-circle empty-icon" style="color: var(--tz-green); font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                            <div class="empty-title" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 5px;">No Challenges Found!</div>
                            <div class="empty-desc" style="color: var(--tz-text-muted);">No candidates matching the filter criteria were found for this school.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
<!-- ================== SCHOOL SUMMARIES MODE ================== -->
<div class="adm-stats">
    <div class="adm-stat" style="grid-column: span 4;">
        <div class="adm-stat-label">Total Missing Mark Records</div>
        <div class="adm-stat-value" style="color: var(--tz-yellow);">{{ number_format($missingMarksCount ?? 0) }}</div>
        <i class="fas fa-exclamation-triangle adm-stat-icon"></i>
    </div>
</div>

@if($isAdmin || $isReo)
<div class="adm-card" style="border: 1px solid var(--tz-blue); background: rgba(59, 130, 246, 0.05); margin-bottom: 20px;">
    <div class="adm-card-head" style="border-bottom: 1px solid rgba(59, 130, 246, 0.15); padding: 12px 20px;">
        <div class="adm-card-title" style="color: var(--tz-blue); display: flex; align-items: center; gap: 8px; font-weight: 700;">
            <i class="fas fa-server"></i> Regional/District Bulk ABS Validation Centre
        </div>
    </div>
    <div class="adm-card-body" style="padding: 20px;">
        <p style="color: var(--tz-text-muted); font-size: 0.9rem; margin-bottom: 15px; line-height: 1.5;">
            Select one or more schools from the table below to perform bulk actions, or apply filters to commit approved ABS marks for the entire district or region. MEO roles are prohibited from executing validations.
        </p>
        
        <div style="display: flex; flex-direction: column; gap: 15px;">

            <!-- Bulk actions row -->
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" id="btn-bulk-approve-schools" disabled style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;">
                    <i class="fas fa-check-double"></i> Approve ABS for Selected Schools (<span class="selected-count">0</span>)
                </button>
                <button type="button" class="btn btn-success" id="btn-bulk-commit-schools" disabled style="background-color: var(--tz-green); border-color: var(--tz-green); color: white; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;">
                    <i class="fas fa-cloud-upload-alt"></i> Commit Approved ABS for Selected Schools (<span class="selected-count">0</span>)
                </button>
                <button type="button" class="btn btn-success" id="btn-bulk-commit-filter" style="background-color: #0284c7; border-color: #0284c7; color: white; display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;" title="Commit all approved ABS candidates matching current filter criteria">
                    <i class="fas fa-filter"></i> Commit Approved ABS for Current Filter
                </button>
                <a href="{{ url('/api/mark-entry/psle/reports/missing-marks/excel') }}?{{ http_build_query($activeFilters) }}" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; color: var(--tz-yellow); border-color: var(--tz-yellow);" id="btn-download-review-sheet">
                    <i class="fas fa-file-csv"></i> Download ABS/INC Review Sheet
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">School-Level Completion & Missing Marks Summary</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table class="missing-marks-table" style="white-space: nowrap; width: 100%;">
            <thead>
                <tr>
                    @if($isAdmin || $isReo)
                        <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllActiveSchools" style="transform: scale(1.15); cursor: pointer;"></th>
                    @endif
                    <th>S/N</th>
                    <th>Region</th>
                    <th>District</th>
                    <th>School Code</th>
                    <th>School Name</th>
                    <th class="text-center">Registered Candidates</th>
                    <th class="text-center">Complete Candidates</th>
                    <th class="text-center">ABS Candidates</th>
                    <th class="text-center">INC Candidates</th>
                    <th class="text-center">Missing Subject Records</th>
                    <th class="text-center">Completion %</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schoolSummaries ?? [] as $index => $summary)
                <tr>
                    @if($isAdmin || $isReo)
                        <td style="text-align: center;">
                            <input type="checkbox" class="school-row-checkbox" value="{{ $summary->school_id }}" data-school-name="{{ $summary->school_name }}" style="transform: scale(1.15); cursor: pointer;" {{ !($summary->abs > 0 || $summary->has_approved) ? 'disabled' : '' }}>
                        </td>
                    @endif
                    <td>{{ $schoolSummaries->firstItem() ? ($schoolSummaries->firstItem() + $index) : ($index + 1) }}</td>
                    <td>{{ $summary->region_name }}</td>
                    <td>{{ $summary->district_name }}</td>
                    <td><code>{{ $summary->school_code }}</code></td>
                    <td><strong>{{ $summary->school_name }}</strong></td>
                    <td class="text-center">{{ number_format($summary->registered) }}</td>
                    <td class="text-center" style="color: var(--tz-green); font-weight: 600;">{{ number_format($summary->complete) }}</td>
                    <td class="text-center" style="color: var(--tz-red); font-weight: 600;">{{ number_format($summary->abs) }}</td>
                    <td class="text-center" style="color: var(--tz-yellow); font-weight: 600;">{{ number_format($summary->inc) }}</td>
                    <td class="text-center">{{ number_format($summary->missing_records) }}</td>
                    <td class="text-center">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <div style="width: 60px; background: rgba(255,255,255,0.05); height: 8px; border-radius: 4px; overflow: hidden; display: inline-block;">
                                <div style="width: {{ $summary->completion_pct }}%; background: var(--tz-green); height: 100%;"></div>
                            </div>
                            <span style="font-size: 0.85rem; font-weight: bold;">{{ $summary->completion_pct }}%</span>
                        </div>
                    </td>
                    <td class="text-right">
                        <a href="{{ url('/mark-entry/psle?view=missing-marks&school_id=' . $summary->school_id . '&exam_year_id=' . $selectedYearId . '&classification=' . $classification) }}" class="btn btn-outline btn-xs" style="font-size: 0.8rem; padding: 4px 8px;">
                            <i class="fas fa-eye"></i> View Challenges
                        </a>
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="{{ ($isAdmin || $isReo) ? 13 : 12 }}">
                        <div class="empty-state">
                            <i class="fas fa-check-circle empty-icon" style="color: var(--tz-green); font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                            <div class="empty-title" style="font-size: 1.2rem; font-weight: bold; margin-bottom: 5px;">All Schools Complete!</div>
                            <div class="empty-desc" style="color: var(--tz-text-muted);">No schools with missing marks matching the filters were found.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if(isset($schoolSummaries) && $schoolSummaries instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="adm-pagination" style="padding: 15px 20px; border-top: 1px solid rgba(255,255,255,.04); display: flex; justify-content: space-between; align-items: center;">
        <div class="pagination-info" style="color: var(--tz-text-muted); font-size: 0.85rem;">
            Showing {{ $schoolSummaries->firstItem() ?? 0 }} to {{ $schoolSummaries->lastItem() ?? 0 }} of {{ $schoolSummaries->total() ?? 0 }} results
        </div>
        <div class="pagination-links" style="display: flex; gap: 8px;">
            @if ($schoolSummaries->onFirstPage())
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;"><i class="fas fa-chevron-left"></i> Previous</span>
            @else
                <a href="{{ $schoolSummaries->previousPageUrl() }}" class="btn btn-outline"><i class="fas fa-chevron-left"></i> Previous</a>
            @endif

            @if ($schoolSummaries->hasMorePages())
                <a href="{{ $schoolSummaries->nextPageUrl() }}" class="btn btn-outline">Next <i class="fas fa-chevron-right"></i></a>
            @else
                <span class="btn btn-outline" style="cursor: not-allowed; opacity: 0.5;">Next <i class="fas fa-chevron-right"></i></span>
            @endif
        </div>
    </div>
    @endif
</div>
@endif

<!-- JavaScript Event Handlers -->

<!-- Bulk Approve Modal -->
<div id="bulk-approve-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="adm-card" style="width: 100%; max-width: 550px; background: #1e293b; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); margin: auto;">
        <div class="adm-card-head" style="border-bottom: 1px solid #334155; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div class="adm-card-title" style="color: white; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-double" style="color: var(--tz-blue);"></i> Approve Selected ABS Records
            </div>
            <button type="button" id="approve-modal-close-btn" style="background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-card-body" style="padding: 20px; color: #cbd5e1;">
            
            <div style="margin-bottom: 15px; font-size: 0.95rem;">
                Selected Schools: <strong style="color: white;" id="approve-selected-schools-count">0</strong>
            </div>

            <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; line-height: 1.4; color: #fef08a;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Only ABS records will be approved. INC records will remain report-only.
            </div>

            <div id="approve-modal-error-alert" style="display: none; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #fca5a5;">
                <i class="fas fa-times-circle"></i> <span id="approve-modal-error-text">Approval remark/reason is required.</span>
            </div>

            <div id="approve-modal-success-alert" style="display: none; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #a7f3d0;">
                <i class="fas fa-check-circle"></i> <span id="approve-modal-success-text">ABS records approved successfully.</span>
            </div>

            <div id="approve-modal-loading" style="display: none; align-items: center; justify-content: center; padding: 15px 0; gap: 10px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--tz-blue);"></i>
                <span style="color: #cbd5e1;">Approving ABS records, please wait...</span>
            </div>

            <div id="approve-modal-inputs">
                <div style="margin-bottom: 15px;">
                    <label class="adm-filter-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Preset Remark / Reason <span style="color: var(--tz-red);">*</span></label>
                    <select id="approve-modal-preset-remark" class="adm-select" style="width: 100%;">
                        <option value="">-- Choose preset remark --</option>
                        <option value="Confirmed from attendance sheet">Confirmed from attendance sheet</option>
                        <option value="Confirmed by headteacher">Confirmed by headteacher</option>
                        <option value="Confirmed by examination record">Confirmed by examination record</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="adm-filter-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Custom Remark / Reason</label>
                    <textarea id="approve-modal-custom-remark" class="adm-select" style="width: 100%; height: 80px; padding: 10px; resize: none;" placeholder="Type custom remark here..."></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="approve-modal-cancel-btn" class="btn btn-outline" style="padding: 8px 16px;">Cancel</button>
                <button type="button" id="approve-modal-confirm-btn" disabled class="btn btn-primary" style="padding: 8px 24px; font-weight: bold;">Confirm Approval</button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Commit Preview & Confirmation Modal -->
<div id="bulk-commit-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="adm-card" style="width: 100%; max-width: 550px; background: #1e293b; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); margin: auto;">
        <div class="adm-card-head" style="border-bottom: 1px solid #334155; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div class="adm-card-title" style="color: white; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-exclamation-triangle" style="color: var(--tz-yellow);"></i> Bulk ABS Commit Preview & Confirmation
            </div>
            <button type="button" id="modal-close-btn" style="background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-card-body" style="padding: 20px; color: #cbd5e1;">
            
            <!-- Loading indicator -->
            <div id="modal-loading" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px 0; gap: 15px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem; color: var(--tz-blue);"></i>
                <span style="color: #cbd5e1;">Fetching commit preview, please wait...</span>
            </div>

            <!-- Preview details -->
            <div id="modal-preview-content" style="display: none;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9rem;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Affected Region(s):</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="preview-regions">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Affected District(s):</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="preview-districts">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Schools Affected:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="preview-schools-count">0</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Candidates Affected:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="preview-candidates-count">0</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Subject Records to Commit:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: var(--tz-green);" id="preview-commit-count">0</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Records to Skip (Numeric marks exist):</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: var(--tz-red);" id="preview-skipped-count">0</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert alert-warning" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; line-height: 1.4; color: #fef08a;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> Committing will write ABS to the official raw marks database. Numeric marks will not be overwritten. This action is recorded in the audit logs.
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-weight: bold; margin-bottom: 8px; font-size: 0.9rem;">To confirm, please type exactly <code style="color: var(--tz-yellow); font-size: 1rem; background: #0f172a; padding: 2px 6px; border-radius: 3px;">COMMIT ABS</code> below:</label>
                    <input type="text" id="modal-confirm-input" class="adm-select" style="width: 100%; text-align: center; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; background: #0f172a; border-color: #334155; color: white;" autocomplete="off" placeholder="Type here...">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" id="modal-cancel-btn" class="btn btn-outline" style="padding: 8px 16px;">Cancel</button>
                    <button type="button" id="modal-confirm-btn" class="btn btn-success" disabled style="background-color: var(--tz-green); border-color: var(--tz-green); color: white; padding: 8px 16px; font-weight: bold;">Execute Commit</button>
                </div>
            </div>

            <!-- Execution Results summary -->
            <div id="modal-results-content" style="display: none; text-align: center; padding: 10px 0;">
                <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--tz-green); margin-bottom: 15px; display: block;"></i>
                <h4 style="font-size: 1.2rem; font-weight: 700; color: white; margin-bottom: 15px;">Commit Execution Completed</h4>
                
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 25px; background: #0f172a; padding: 15px; border-radius: 6px;">
                    <div>
                        <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Approved</span>
                        <strong style="font-size: 1.2rem; color: white;" id="result-approved">0</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Committed</span>
                        <strong style="font-size: 1.2rem; color: var(--tz-green);" id="result-committed">0</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Skipped</span>
                        <strong style="font-size: 1.2rem; color: var(--tz-yellow);" id="result-skipped">0</strong>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #94a3b8; display: block;">Failed</span>
                        <strong style="font-size: 1.2rem; color: var(--tz-red);" id="result-failed">0</strong>
                    </div>
                </div>

                <button type="button" id="modal-done-btn" class="btn btn-primary" style="padding: 8px 24px; font-weight: bold;">Done & Refresh</button>
            </div>

        </div>
    </div>
</div>

<!-- Complete Missing INC Mark Modal -->
<div id="inc-complete-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
    <div class="adm-card" style="width: 100%; max-width: 550px; background: #1e293b; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); margin: auto;">
        <div class="adm-card-head" style="border-bottom: 1px solid #334155; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between;">
            <div class="adm-card-title" style="color: white; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-edit" style="color: var(--tz-yellow);"></i> Complete Missing INC Mark
            </div>
            <button type="button" id="inc-modal-close-btn" style="background: none; border: none; color: #94a3b8; font-size: 1.2rem; cursor: pointer;"><i class="fas fa-times"></i></button>
        </div>
        <div class="adm-card-body" style="padding: 20px; color: #cbd5e1;">
            
            <div id="inc-modal-error-alert" style="display: none; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #fca5a5;">
                <i class="fas fa-times-circle"></i> <span id="inc-modal-error-text"></span>
            </div>

            <div id="inc-modal-success-alert" style="display: none; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; color: #a7f3d0;">
                <i class="fas fa-check-circle"></i> <span id="inc-modal-success-text"></span>
            </div>

            <div id="inc-modal-loading" style="display: none; align-items: center; justify-content: center; padding: 15px 0; gap: 10px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 1.5rem; color: var(--tz-blue);"></i>
                <span style="color: #cbd5e1;">Saving missing mark, please wait...</span>
            </div>

            <div id="inc-modal-inputs">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 0.9rem;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Candidate Name:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="inc-modal-cand-name">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Index Number:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="inc-modal-index-number">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">PREM Number:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="inc-modal-prem-number">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">School Name:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="inc-modal-school-name">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="padding: 8px 0; color: #94a3b8;">Subject Code:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: white;" id="inc-modal-subject-code">-</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #334155; display: none;" id="inc-modal-existing-marks-row">
                            <td style="padding: 8px 0; color: #f87171;">Existing Marks:</td>
                            <td style="padding: 8px 0; text-align: right; font-weight: bold; color: #fca5a5;" id="inc-modal-existing-marks">-</td>
                        </tr>
                    </tbody>
                </table>

                <div class="alert alert-warning" id="inc-modal-overwrite-warning" style="display: none; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 0.85rem; line-height: 1.4; color: #fca5a5;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> A numeric mark or ABS status already exists in the system for this subject. Overwriting is blocked.
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="adm-filter-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Enter Missing Mark (Max: <span id="inc-modal-max-label">50</span>) <span style="color: var(--tz-red);">*</span></label>
                    <input type="number" id="inc-modal-score" class="adm-select" style="width: 100%; color: white; background: #0f172a; border-color: #334155;" min="0" step="0.5" placeholder="Enter numeric score here...">
                </div>

                <div style="margin-bottom: 20px;">
                    <label class="adm-filter-label" style="font-weight: 700; margin-bottom: 8px; display: block;">Remark / Reason <span style="color: var(--tz-red);">*</span></label>
                    <textarea id="inc-modal-remark" class="adm-select" style="width: 100%; height: 80px; padding: 10px; resize: none; color: white; background: #0f172a; border-color: #334155;" placeholder="Enter reason for completing the missing mark (mandatory)..."></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" id="inc-modal-cancel-btn" class="btn btn-outline" style="padding: 8px 16px;">Cancel</button>
                <button type="button" id="inc-modal-save-btn" class="btn btn-primary" style="padding: 8px 24px; font-weight: bold;">Save Mark</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Event Handlers -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // School Detail Mode single-school elements
    const presetSelect = document.getElementById('select-preset-remark');
    const customInput = document.getElementById('input-custom-remark');
    const checkboxes = document.querySelectorAll('.abs-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-abs');
    const actionsCard = document.getElementById('approve-actions-card');

    function updateActionsCardVisibility() {
        const checkedCount = document.querySelectorAll('.abs-checkbox:checked').length;
        if (actionsCard) {
            actionsCard.style.display = checkedCount > 0 ? 'block' : 'none';
        }
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateActionsCardVisibility);
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateActionsCardVisibility();
        });
    }

    document.getElementById('btn-approve-selected')?.addEventListener('click', function() {
        submitAction('{{ route('mark-entry.psle.missing-marks.approve') }}');
    });

    document.getElementById('btn-reject-selected')?.addEventListener('click', function() {
        submitAction('{{ route('mark-entry.psle.missing-marks.reject') }}');
    });

    function submitAction(url) {
        const remark = (customInput ? customInput.value.trim() : '') || (presetSelect ? presetSelect.value : '');
        if (!remark && url.includes('approve')) {
            alert('Please enter or select a remark before approving.');
            return;
        }

        const selectedItems = [];
        document.querySelectorAll('.abs-checkbox:checked').forEach(cb => {
            selectedItems.push({
                candidate_id: parseInt(cb.getAttribute('data-candidate-id')),
                subject_id: parseInt(cb.getAttribute('data-subject-id')),
                school_id: parseInt(cb.getAttribute('data-school-id')),
                exam_year_id: parseInt(cb.getAttribute('data-exam-year-id'))
            });
        });

        if (selectedItems.length === 0) {
            alert('No items selected.');
            return;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                selected_items: selectedItems,
                reason: remark
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred.');
        });
    }

    document.getElementById('btn-commit-abs')?.addEventListener('click', function() {
        if (confirm('You are about to commit approved ABS records as official X marks. This action will affect entered data reports. Continue?')) {
            fetch('{{ route('mark-entry.psle.missing-marks.commit') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    school_id: {{ $selectedSchoolId ?? 'null' }},
                    exam_year_id: {{ $selectedYearId ?? 'null' }}
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('An unexpected error occurred.');
            });
        }
    });

    // ============================================
    // BULK VALIDATION CENTRE LOGIC
    // ============================================
    const selectAllSchools = document.getElementById('selectAllActiveSchools');
    const schoolCheckboxes = document.querySelectorAll('.school-row-checkbox');
    const btnBulkApprove = document.getElementById('btn-bulk-approve-schools');
    const btnBulkCommitSelected = document.getElementById('btn-bulk-commit-schools');
    const btnBulkCommitFilter = document.getElementById('btn-bulk-commit-filter');
    
    const bulkApproveModal = document.getElementById('bulk-approve-modal');
    const approveModalCloseBtn = document.getElementById('approve-modal-close-btn');
    const approveModalCancelBtn = document.getElementById('approve-modal-cancel-btn');
    const approveModalConfirmBtn = document.getElementById('approve-modal-confirm-btn');
    
    const approvePresetSelect = document.getElementById('approve-modal-preset-remark');
    const approveCustomRemark = document.getElementById('approve-modal-custom-remark');
    const approveErrorAlert = document.getElementById('approve-modal-error-alert');
    const approveErrorText = document.getElementById('approve-modal-error-text');
    const approveSuccessAlert = document.getElementById('approve-modal-success-alert');
    const approveLoading = document.getElementById('approve-modal-loading');
    const approveInputsContainer = document.getElementById('approve-modal-inputs');
    
    const bulkCommitModal = document.getElementById('bulk-commit-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const modalConfirmBtn = document.getElementById('modal-confirm-btn');
    const modalDoneBtn = document.getElementById('modal-done-btn');
    const modalConfirmInput = document.getElementById('modal-confirm-input');
    
    const modalLoading = document.getElementById('modal-loading');
    const modalPreviewContent = document.getElementById('modal-preview-content');
    const modalResultsContent = document.getElementById('modal-results-content');
    
    let isFilterCommit = false;

    function getApproveRemarkValue() {
        const preset = approvePresetSelect.value;
        const custom = approveCustomRemark.value.trim();
        if (preset === 'Other') {
            return custom;
        }
        return preset || custom;
    }

    function validateApproveModal() {
        const remark = getApproveRemarkValue();
        if (remark) {
            approveModalConfirmBtn.disabled = false;
            approveErrorAlert.style.display = 'none';
        } else {
            approveModalConfirmBtn.disabled = true;
            approveErrorAlert.style.display = 'block';
            approveErrorText.textContent = 'Approval remark/reason is required.';
        }
    }

    approvePresetSelect?.addEventListener('change', validateApproveModal);
    approveCustomRemark?.addEventListener('input', validateApproveModal);

    function closeApproveModal() {
        if (bulkApproveModal) {
            bulkApproveModal.style.display = 'none';
        }
    }

    approveModalCloseBtn?.addEventListener('click', closeApproveModal);
    approveModalCancelBtn?.addEventListener('click', closeApproveModal);

    function getActiveVisibleCheckboxes() {
        return Array.from(document.querySelectorAll('.school-row-checkbox:not(:disabled)')).filter(cb => {
            return cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null;
        });
    }

    function syncHeaderCheckbox() {
        if (!selectAllSchools) return;
        
        const activeVisible = getActiveVisibleCheckboxes();
        if (activeVisible.length === 0) {
            selectAllSchools.checked = false;
            selectAllSchools.disabled = true;
            return;
        }
        
        selectAllSchools.disabled = false;
        const allChecked = activeVisible.every(cb => cb.checked);
        selectAllSchools.checked = allChecked;
    }

    function updateBulkButtonsState() {
        const checkedActiveBoxes = Array.from(document.querySelectorAll('.school-row-checkbox:not(:disabled):checked')).filter(cb => {
            return cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null;
        });
        const count = checkedActiveBoxes.length;
        document.querySelectorAll('.selected-count').forEach(el => el.textContent = count);
        
        if (btnBulkApprove) btnBulkApprove.disabled = count === 0;
        if (btnBulkCommitSelected) btnBulkCommitSelected.disabled = count === 0;
    }

    if (selectAllSchools) {
        selectAllSchools.addEventListener('change', function() {
            const isChecked = this.checked;
            schoolCheckboxes.forEach(cb => {
                if (isChecked) {
                    if (!cb.disabled && (cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null)) {
                        cb.checked = true;
                    }
                } else {
                    cb.checked = false;
                }
            });
            updateBulkButtonsState();
        });
    }

    schoolCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkButtonsState();
            syncHeaderCheckbox();
        });
    });

    // Bulk Approve
    btnBulkApprove?.addEventListener('click', function() {
        const checkedActiveBoxes = Array.from(document.querySelectorAll('.school-row-checkbox:not(:disabled):checked')).filter(cb => {
            return cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null;
        });
        const count = checkedActiveBoxes.length;
        if (count === 0) {
            return;
        }

        document.getElementById('approve-selected-schools-count').textContent = count;
        
        // Reset modal fields/alerts
        approvePresetSelect.value = '';
        approveCustomRemark.value = '';
        approveErrorAlert.style.display = 'block';
        approveErrorText.textContent = 'Approval remark/reason is required.';
        approveSuccessAlert.style.display = 'none';
        approveLoading.style.display = 'none';
        approveInputsContainer.style.display = 'block';
        approveModalConfirmBtn.disabled = true;
        approveModalConfirmBtn.style.display = 'inline-block';
        approveModalCancelBtn.style.display = 'inline-block';

        if (bulkApproveModal) {
            bulkApproveModal.style.display = 'flex';
        }
    });

    // Confirm click (submit via AJAX/fetch)
    approveModalConfirmBtn?.addEventListener('click', function() {
        const remark = getApproveRemarkValue();
        if (!remark) {
            approveErrorAlert.style.display = 'block';
            approveErrorText.textContent = 'Approval remark/reason is required.';
            return;
        }

        const schoolIds = [];
        document.querySelectorAll('.school-row-checkbox:not(:disabled):checked').forEach(cb => {
            if (cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null) {
                schoolIds.push(parseInt(cb.value));
            }
        });

        if (schoolIds.length === 0) {
            approveErrorAlert.style.display = 'block';
            approveErrorText.textContent = 'No schools selected.';
            return;
        }

        // Hide inputs and show loading
        approveInputsContainer.style.display = 'none';
        approveErrorAlert.style.display = 'none';
        approveLoading.style.display = 'flex';
        approveModalConfirmBtn.style.display = 'none';
        approveModalCancelBtn.style.display = 'none';

        fetch('{{ route('mark-entry.psle.missing-marks.bulk-approve') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                school_ids: schoolIds,
                reason: remark,
                remarks: remark,
                exam_year_id: {{ $selectedYearId ?? 'null' }},
                subject_id: {{ $selectedSubjectId ?? 'null' }},
                region_id: {{ $selectedRegionId ?? 'null' }},
                district_id: {{ $selectedDistrictId ?? 'null' }}
            })
        })
        .then(res => res.json())
        .then(data => {
            approveLoading.style.display = 'none';
            if (data.success) {
                approveSuccessAlert.style.display = 'block';
                document.getElementById('approve-modal-success-text').textContent = data.message;
                
                // Reload page after a short delay
                setTimeout(() => {
                    closeApproveModal();
                    window.location.reload();
                }, 1500);
            } else {
                approveInputsContainer.style.display = 'block';
                approveErrorAlert.style.display = 'block';
                approveErrorText.textContent = 'Error: ' + data.message;
                approveModalConfirmBtn.style.display = 'inline-block';
                approveModalCancelBtn.style.display = 'inline-block';
                validateApproveModal();
            }
        })
        .catch(err => {
            console.error(err);
            approveLoading.style.display = 'none';
            approveInputsContainer.style.display = 'block';
            approveErrorAlert.style.display = 'block';
            approveErrorText.textContent = 'An unexpected error occurred.';
            approveModalConfirmBtn.style.display = 'inline-block';
            approveModalCancelBtn.style.display = 'inline-block';
            validateApproveModal();
        });
    });

    // Helper to open modal and load preview
    function openCommitModal(useFilter) {
        isFilterCommit = useFilter;
        bulkCommitModal.style.display = 'flex';
        modalLoading.style.display = 'flex';
        modalLoading.querySelector('span').textContent = 'Fetching commit preview, please wait...';
        modalPreviewContent.style.display = 'none';
        modalResultsContent.style.display = 'none';
        modalConfirmInput.value = '';
        modalConfirmBtn.disabled = true;

        const schoolIds = [];
        if (!useFilter) {
            document.querySelectorAll('.school-row-checkbox:not(:disabled):checked').forEach(cb => {
                if (cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null) {
                    schoolIds.push(parseInt(cb.value));
                }
            });
        }

        fetch('{{ route('mark-entry.psle.missing-marks.bulk-commit-preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                exam_year_id: {{ $selectedYearId ?? 'null' }},
                region_id: {{ $selectedRegionId ?? 'null' }},
                district_id: {{ $selectedDistrictId ?? 'null' }},
                school_id: {{ $selectedSchoolId ?? 'null' }},
                subject_id: {{ $selectedSubjectId ?? 'null' }},
                school_ids: useFilter ? null : schoolIds
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                modalLoading.style.display = 'none';
                modalPreviewContent.style.display = 'block';
                
                document.getElementById('preview-regions').textContent = data.preview.regions.join(', ') || 'None';
                document.getElementById('preview-districts').textContent = data.preview.districts.join(', ') || 'None';
                document.getElementById('preview-schools-count').textContent = data.preview.schools_count;
                document.getElementById('preview-candidates-count').textContent = data.preview.candidates_count;
                document.getElementById('preview-commit-count').textContent = data.preview.to_commit_count;
                document.getElementById('preview-skipped-count').textContent = data.preview.skipped_count;
            } else {
                alert('Error fetching preview: ' + data.message);
                closeModal();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load commit preview.');
            closeModal();
        });
    }

    function closeModal() {
        bulkCommitModal.style.display = 'none';
    }

    modalCloseBtn?.addEventListener('click', closeModal);
    modalCancelBtn?.addEventListener('click', closeModal);

    // Watch confirmation input
    modalConfirmInput?.addEventListener('input', function() {
        modalConfirmBtn.disabled = this.value.trim() !== 'COMMIT ABS';
    });

    btnBulkCommitSelected?.addEventListener('click', function() {
        openCommitModal(false);
    });

    btnBulkCommitFilter?.addEventListener('click', function() {
        openCommitModal(true);
    });

    // Execute Commit
    modalConfirmBtn?.addEventListener('click', function() {
        if (modalConfirmInput.value.trim() !== 'COMMIT ABS') {
            alert('Please type exactly COMMIT ABS to confirm.');
            return;
        }

        modalPreviewContent.style.display = 'none';
        modalLoading.style.display = 'flex';
        modalLoading.querySelector('span').textContent = 'Executing bulk commit, please wait...';

        const schoolIds = [];
        if (!isFilterCommit) {
            document.querySelectorAll('.school-row-checkbox:not(:disabled):checked').forEach(cb => {
                if (cb.offsetWidth > 0 || cb.offsetHeight > 0 || cb.offsetParent !== null) {
                    schoolIds.push(parseInt(cb.value));
                }
            });
        }

        fetch('{{ route('mark-entry.psle.missing-marks.bulk-commit') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                exam_year_id: {{ $selectedYearId ?? 'null' }},
                region_id: {{ $selectedRegionId ?? 'null' }},
                district_id: {{ $selectedDistrictId ?? 'null' }},
                school_id: {{ $selectedSchoolId ?? 'null' }},
                subject_id: {{ $selectedSubjectId ?? 'null' }},
                school_ids: isFilterCommit ? null : schoolIds,
                confirmation_text: 'COMMIT ABS'
            })
        })
        .then(res => res.json())
        .then(data => {
            modalLoading.style.display = 'none';
            if (data.success) {
                modalResultsContent.style.display = 'block';
                document.getElementById('result-approved').textContent = data.results.total_approved;
                document.getElementById('result-committed').textContent = data.results.committed;
                document.getElementById('result-skipped').textContent = data.results.skipped;
                document.getElementById('result-failed').textContent = data.results.failed;
            } else {
                alert('Commit failed: ' + data.message);
                modalPreviewContent.style.display = 'block';
            }
        })
        .catch(err => {
            console.error(err);
            alert('An unexpected error occurred during commit.');
            modalLoading.style.display = 'none';
            modalPreviewContent.style.display = 'block';
        });
    });

    modalDoneBtn?.addEventListener('click', function() {
        closeModal();
        window.location.reload();
    });

    // ============================================
    // COMPLETE MISSING INC MARK MODAL LOGIC
    // ============================================
    const incCompleteModal = document.getElementById('inc-complete-modal');
    const incModalCloseBtn = document.getElementById('inc-modal-close-btn');
    const incModalCancelBtn = document.getElementById('inc-modal-cancel-btn');
    const incModalSaveBtn = document.getElementById('inc-modal-save-btn');
    
    const incModalCandName = document.getElementById('inc-modal-cand-name');
    const incModalIndexNumber = document.getElementById('inc-modal-index-number');
    const incModalPremNumber = document.getElementById('inc-modal-prem-number');
    const incModalSchoolName = document.getElementById('inc-modal-school-name');
    const incModalSubjectCode = document.getElementById('inc-modal-subject-code');
    const incModalMaxLabel = document.getElementById('inc-modal-max-label');
    const incModalScoreInput = document.getElementById('inc-modal-score');
    const incModalRemarkTextarea = document.getElementById('inc-modal-remark');
    const incModalExistingMarksRow = document.getElementById('inc-modal-existing-marks-row');
    const incModalExistingMarks = document.getElementById('inc-modal-existing-marks');
    
    const incModalErrorAlert = document.getElementById('inc-modal-error-alert');
    const incModalErrorText = document.getElementById('inc-modal-error-text');
    const incModalSuccessAlert = document.getElementById('inc-modal-success-alert');
    const incModalSuccessText = document.getElementById('inc-modal-success-text');
    const incModalLoading = document.getElementById('inc-modal-loading');
    const incModalInputs = document.getElementById('inc-modal-inputs');

    let currentIncCandidateId = null;
    let currentIncSchoolId = null;
    let currentIncSubjectId = null;
    let currentIncMaxMarks = 50;

    function closeIncModal() {
        if (incCompleteModal) {
            incCompleteModal.style.display = 'none';
        }
    }

    incModalCloseBtn?.addEventListener('click', closeIncModal);
    incModalCancelBtn?.addEventListener('click', closeIncModal);

    incCompleteModal?.addEventListener('click', function(e) {
        if (e.target === incCompleteModal) {
            closeIncModal();
        }
    });

    function validateIncForm() {
        const scoreVal = incModalScoreInput.value.trim();
        const remarkVal = incModalRemarkTextarea.value.trim();
        
        if (scoreVal === '') {
            incModalSaveBtn.disabled = true;
            return false;
        }

        const score = parseFloat(scoreVal);
        if (isNaN(score) || score < 0 || score > currentIncMaxMarks) {
            incModalSaveBtn.disabled = true;
            incModalErrorAlert.style.display = 'block';
            incModalErrorText.textContent = `Score must be a number between 0 and ${currentIncMaxMarks}.`;
            return false;
        }

        if (remarkVal.length < 3) {
            incModalSaveBtn.disabled = true;
            incModalErrorAlert.style.display = 'block';
            incModalErrorText.textContent = 'Remark/reason is required and must be at least 3 characters.';
            return false;
        }

        incModalErrorAlert.style.display = 'none';
        incModalSaveBtn.disabled = false;
        return true;
    }

    incModalScoreInput?.addEventListener('input', validateIncForm);
    incModalRemarkTextarea?.addEventListener('input', validateIncForm);

    const psleSaveTimers = new Map();
    const psleSaveStates = new Map();
    const psleLastSavedValues = new Map();
    const PSLE_SAVE_DEBOUNCE_MS = 150;
    const PSLE_SAVE_RETRY_DELAYS = [1000, 3000, 7000];
    const PSLE_SAVE_TIMEOUT_MS = 20000;

    function saveStateKey(input, candidateId) {
        const examYearId = input.dataset.examYearId || '{{ $selectedYearId ?? "" }}';
        const schoolId = input.dataset.schoolId || '{{ $selectedSchoolId ?? "" }}';
        const subjectId = input.dataset.subjectId || '';
        return [examYearId, schoolId, subjectId, candidateId].join(':');
    }

    function queueMarkSave(input, candidateId) {
        const key = saveStateKey(input, candidateId);
        let score = input.value.trim();
        
        let scoreUpper = score.toUpperCase();
        if (scoreUpper === 'ABS' || score === '') {
            input.style.color = 'var(--tz-red)';
        } else if (scoreUpper === 'INC') {
            input.style.color = 'var(--tz-yellow)';
        } else {
            input.style.color = '#fff';
        }

        const state = psleSaveStates.get(key) || {};
        if (!state.inFlight && !input.classList.contains('mark-input-save-error') && psleLastSavedValues.get(key) === score) {
            return;
        }

        input.dataset.saveStatus = 'dirty';
        clearTimeout(psleSaveTimers.get(key));
        clearMarkInputError(input);
        clearMarkSaveError(input);
        
        psleSaveTimers.set(key, setTimeout(() => saveMark(input, candidateId), PSLE_SAVE_DEBOUNCE_MS));
    }

    function flushMarkSave(input, candidateId) {
        const key = saveStateKey(input, candidateId);
        if (psleSaveTimers.has(key)) {
            clearTimeout(psleSaveTimers.get(key));
            psleSaveTimers.delete(key);
            saveMark(input, candidateId);
            return;
        }

        if (
            input.classList.contains('mark-input-save-error')
            || input.dataset.saveStatus === 'dirty'
            || input.dataset.saveStatus === 'failed'
            || (input.value.trim() !== '' && psleLastSavedValues.get(key) !== input.value.trim())
        ) {
            saveMark(input, candidateId);
        }
    }

    function saveMark(input, candidateId, attempt = 0) {
        const key = saveStateKey(input, candidateId);
        let score = input.value.trim();
        const maxScore = parseFloat(input.dataset.maxScore || '50');
        const minScore = 0;
        
        let scoreUpper = score.toUpperCase();
        if (scoreUpper === 'ABS' || score === '') {
            score = 'ABS';
            input.value = 'ABS';
            input.style.color = 'var(--tz-red)';
        } else if (scoreUpper === 'INC') {
            score = 'INC';
            input.value = 'INC';
            input.style.color = 'var(--tz-yellow)';
        } else {
            const numScore = parseFloat(score);
            if (isNaN(numScore) || numScore < minScore || numScore > maxScore) {
                setMarkInputError(input, `Score must be a number between ${minScore} and ${maxScore}, or ABS, or INC.`);
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Score',
                    text: `Score must be a number between ${minScore} and ${maxScore}, or ABS, or INC.`,
                    confirmButtonColor: 'var(--tz-blue)',
                    background: '#161b22',
                    color: '#f0f4f7'
                });
                input.focus();
                return;
            }
            input.style.color = '#fff';
        }
        
        const state = psleSaveStates.get(key) || {};

        if (state.inFlight && attempt === 0) {
            state.pending = true;
            state.pendingValue = score;
            state.input = input;
            psleSaveStates.set(key, state);
            return;
        }

        if (attempt === 0 && !input.classList.contains('mark-input-save-error') && psleLastSavedValues.get(key) === score) {
            input.dataset.saveStatus = 'saved';
            return;
        }
        
        clearMarkInputError(input);
        clearMarkSaveError(input);
        input.style.borderColor = 'var(--tz-blue)';
        input.dataset.saveStatus = 'saving';
        
        let scoreToSend = score;
        if (score !== 'ABS' && score !== 'INC') {
            scoreToSend = Number(score);
        }
        
        const payload = {
            candidate_id: candidateId,
            score: scoreToSend,
            school_id: input.dataset.schoolId || '{{ $selectedSchoolId ?? "" }}',
            subject_id: input.dataset.subjectId,
            exam_year_id: input.dataset.examYearId || '{{ $selectedYearId ?? "" }}'
        };
        
        psleSaveStates.set(key, {
            ...state,
            inFlight: true,
            pending: false,
            input,
            savingValue: score,
            retryTimer: null
        });
        const controller = new AbortController();
        let timedOut = false;
        let retryScheduled = false;
        const timeout = setTimeout(() => {
            timedOut = true;
            controller.abort();
        }, PSLE_SAVE_TIMEOUT_MS);
        
        fetch('/api/mark-entry/psle/marks/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload),
            signal: controller.signal
        })
        .then(res => {
            return res.text().then(text => {
                let parsed = null;
                try {
                    parsed = JSON.parse(text);
                } catch (e) {
                    const titleMatch = text.match(/<title>([^<]+)<\/title>/i);
                    const errorTitle = titleMatch ? titleMatch[1] : 'Invalid server response';
                    const err = new Error(`${errorTitle} (Status: ${res.status})`);
                    err.status = res.status;
                    throw err;
                }
                
                if (!res.ok) {
                    const err = new Error(parsed.message || responseMessageForStatus(res.status));
                    err.status = res.status;
                    err.code = parsed.code || null;
                    err.type = parsed.type || null;
                    err.errors = parsed.errors || null;
                    throw err;
                }
                return parsed;
            });
        })
        .then(data => {
            if (data.success || data.ok) {
                clearMarkInputError(input);
                clearMarkSaveError(input);
                psleLastSavedValues.set(key, score);
                input.dataset.lastSavedValue = score;
                input.style.borderColor = 'rgba(255,255,255,0.1)';
                input.dataset.saveStatus = 'saved';
            } else {
                setMarkSaveError(input, data.message || 'An error occurred while saving the mark.');
            }
        })
        .catch(err => {
            if (err.name === 'AbortError') {
                if (!timedOut) {
                    console.debug('PSLE mark save aborted intentionally.', err);
                    return;
                }
                err.status = 0;
                err.type = 'timeout';
                err.message = responseMessageForStatus(0);
            }

            if (shouldRetrySave(err) && attempt < PSLE_SAVE_RETRY_DELAYS.length) {
                setMarkSaveError(input, responseMessageForStatus(err.status || 0), true);
                const retryDelay = PSLE_SAVE_RETRY_DELAYS[attempt];
                const retryTimer = setTimeout(() => saveMark(input, candidateId, attempt + 1), retryDelay);
                const retryState = psleSaveStates.get(key) || {};
                psleSaveStates.set(key, {
                    ...retryState,
                    inFlight: true,
                    input,
                    savingValue: score,
                    retryTimer
                });
                retryScheduled = true;
                return;
            }

            if (err.status === 422) {
                let msg = 'Invalid mark.';
                if (err.errors && err.errors.score) {
                    msg = err.errors.score[0];
                } else if (err.message) {
                    msg = err.message;
                }
                setMarkInputError(input, msg);
            } else {
                setMarkSaveError(input, responseMessageForStatus(err.status || 0));
            }
            console.error('Save failed:', err);
        })
        .finally(() => {
            clearTimeout(timeout);
            if (retryScheduled) {
                return;
            }

            const latestState = psleSaveStates.get(key);
            if (latestState && latestState.pending && latestState.pendingValue !== latestState.savingValue) {
                psleSaveStates.set(key, { inFlight: false, pending: false, input });
                saveMark(latestState.input || input, candidateId);
                return;
            }
            psleSaveStates.set(key, { inFlight: false, pending: false, input });
        });
    }

    function setMarkInputError(input, message) {
        input.classList.add('mark-input-error');
        input.dataset.saveStatus = 'invalid';
        input.dataset.validationError = message || 'Validation error';
        input.style.borderColor = 'var(--tz-red)';
    }

    function clearMarkInputError(input) {
        input.classList.remove('mark-input-error');
        delete input.dataset.validationError;
    }

    function setMarkSaveError(input, message, retrying = false) {
        input.dataset.saveError = message || 'The mark was not saved.';
        input.style.borderColor = 'var(--tz-yellow)';
        if (retrying) {
            input.dataset.saveStatus = 'saving';
            input.classList.remove('mark-input-save-error');
        } else {
            input.dataset.saveStatus = 'failed';
            input.classList.add('mark-input-save-error');
        }
    }

    function clearMarkSaveError(input) {
        input.classList.remove('mark-input-save-error');
        delete input.dataset.saveError;
    }

    function shouldRetrySave(err) {
        return err.name === 'AbortError' || [502, 503, 504].includes(Number(err.status));
    }

    function responseMessageForStatus(status) {
        if (status === 422) return 'Invalid mark.';
        if (status === 419) return 'Session expired, refresh page.';
        if (status === 403) return 'Not allowed.';
        if (status === 500) return 'Server error.';
        if ([502, 503, 504].includes(Number(status))) return 'Server is temporarily busy. The system will retry automatically.';
        if (!status) return 'Network/server timeout. The system will retry automatically.';
        return `The mark could not be saved. Server status: ${status}.`;
    }

    document.querySelectorAll('.inc-clickable-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const candidateId = this.getAttribute('data-candidate-id');
            const schoolId = this.getAttribute('data-school-id');
            const subjectId = this.getAttribute('data-subject-id');
            const maxScore = this.getAttribute('data-subject-max-marks') || 50;
            const examYearId = '{{ $selectedYearId ?? "" }}';

            const parentCell = this.parentElement;
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'mark-input adm-select';
            input.setAttribute('data-candidate-id', candidateId);
            input.setAttribute('data-school-id', schoolId);
            input.setAttribute('data-subject-id', subjectId);
            input.setAttribute('data-exam-year-id', examYearId);
            input.setAttribute('data-max-score', maxScore);
            input.value = 'INC';
            input.style.width = '80px';
            input.style.textAlign = 'center';
            input.style.background = 'rgba(255,255,255,0.05)';
            input.style.border = '1px solid rgba(255,255,255,0.1)';
            input.style.color = 'var(--tz-yellow)';
            
            input.dataset.lastSavedValue = 'INC';
            input.dataset.saveStatus = 'saved';
            psleLastSavedValues.set(saveStateKey(input, candidateId), 'INC');

            parentCell.innerHTML = '';
            parentCell.appendChild(input);

            input.addEventListener('input', function() {
                queueMarkSave(this, candidateId);
            });
            input.addEventListener('blur', function() {
                flushMarkSave(this, candidateId);
            });

            input.focus();
            input.select();
        });
    });

    incModalSaveBtn?.addEventListener('click', function() {
        if (!validateIncForm()) {
            return;
        }

        const score = parseFloat(incModalScoreInput.value.trim());
        const remark = incModalRemarkTextarea.value.trim();

        if (incModalLoading) incModalLoading.style.display = 'flex';
        if (incModalInputs) incModalInputs.style.display = 'none';
        if (incModalErrorAlert) incModalErrorAlert.style.display = 'none';
        if (incModalSaveBtn) incModalSaveBtn.disabled = true;

        fetch('/mark-entry/psle/missing-marks/inc/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                candidate_id: parseInt(currentIncCandidateId),
                school_id: parseInt(currentIncSchoolId),
                subject_id: parseInt(currentIncSubjectId),
                exam_year_id: {{ $selectedYearId ?? 'null' }},
                score: score,
                remark: remark
            })
        })
        .then(res => res.json())
        .then(data => {
            if (incModalLoading) incModalLoading.style.display = 'none';
            if (data.success) {
                if (incModalSuccessAlert) {
                    incModalSuccessAlert.style.display = 'block';
                    incModalSuccessText.textContent = data.message;
                }
                setTimeout(() => {
                    closeIncModal();
                    window.location.reload();
                }, 1500);
            } else {
                if (incModalInputs) incModalInputs.style.display = 'block';
                if (incModalErrorAlert) {
                    incModalErrorAlert.style.display = 'block';
                    incModalErrorText.textContent = data.message || 'Error occurred while saving.';
                }
                if (incModalSaveBtn) incModalSaveBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            if (incModalLoading) incModalLoading.style.display = 'none';
            if (incModalInputs) incModalInputs.style.display = 'block';
            if (incModalErrorAlert) {
                incModalErrorAlert.style.display = 'block';
                incModalErrorText.textContent = 'An unexpected error occurred while saving.';
            }
            if (incModalSaveBtn) incModalSaveBtn.disabled = false;
        });
    });

    // Initialize state
    syncHeaderCheckbox();
    updateBulkButtonsState();
});
</script>
