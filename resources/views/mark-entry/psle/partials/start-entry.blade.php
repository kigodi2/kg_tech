<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Regional Mark Entry</span>
</div>

<div class="adm-page-header">
    <h1 class="adm-page-title">Regional Mark Entry Workspace</h1>
    <p class="adm-page-desc">You can enter marks for any district, school, and PSLE subject within your assigned region.</p>
</div>

<!-- Selection Form -->
<div class="adm-card" style="border-left: 4px solid var(--tz-gold);">
    <div class="adm-card-head">
        <div class="adm-card-title"><i class="fas fa-filter" style="color: var(--tz-gold);"></i> Select Entry Scope</div>
    </div>
    <div class="adm-card-body">
        <form method="GET" action="{{ url()->current() }}" class="adm-filters" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            <input type="hidden" name="view" value="start-entry" id="view-input">
            
            <div class="adm-filter-group">
                <label class="adm-filter-label">Exam Year</label>
                <select name="exam_year_id" class="adm-select" required onchange="this.form.submit()">
                    @foreach($examYears ?? [] as $yr)
                        <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="adm-filter-group">
                <label class="adm-filter-label">District / Council</label>
                <select name="district_id" class="adm-select" onchange="this.form.submit()">
                    <option value="">Select District</option>
                    @foreach($districts ?? [] as $dist)
                        <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="adm-filter-group">
                <label class="adm-filter-label">Primary School</label>
                <input
                    type="search"
                    id="school-filter-fallback"
                    class="adm-input school-filter-fallback"
                    placeholder="Type school code or name..."
                    autocomplete="off"
                    aria-label="Search school"
                >
                <select
                    name="school_id"
                    id="start-entry-school-select"
                    class="adm-select searchable-school-select"
                    required
                    onchange="this.form.submit()"
                    data-placeholder="Search by centre number or school name"
                >
                    <option value="">Select School</option>
                    @foreach($schools ?? [] as $sch)
                        <option
                            value="{{ $sch->id }}"
                            data-centre="{{ $sch->code }}"
                            data-school-name="{{ $sch->name }}"
                            {{ ($activeFilters['school_id'] ?? '') == $sch->id ? 'selected' : '' }}
                        >{{ $sch->code }} - {{ $sch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="adm-filter-group">
                <label class="adm-filter-label">PSLE Subject</label>
                <select name="subject_id" class="adm-select" required>
                    <option value="">Select Subject</option>
                    @foreach($psleSubjects ?? [] as $subj)
                        <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                    @endforeach
                </select>
                @if(($hiddenTakenSubjectsCount ?? 0) > 0 && ($isMarkOfficer ?? false) && !($isTrulyAdmin ?? false))
                    <div class="subject-lock-note">Subjects already taken by other officers are hidden.</div>
                @endif
            </div>

            <div class="adm-filter-group" style="display:flex; align-items:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:100%; height:40px;" onclick="document.getElementById('view-input').value='entry-sheet';">
                    <i class="fas fa-keyboard"></i> Open Entry Sheet
                </button>
            </div>
        </form>
    </div>
</div>

@if($isTrulyAdmin || $isReo)
<!-- My Assignments Section (Kept for tracking but labeled as Assignments) -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">Active Assignments Log</div>
    </div>
    <div class="adm-card-body table-responsive">
        <table>
            <thead>
                <tr>
                    <th>District</th>
                    <th>School</th>
                    <th>Subject</th>
                    <th class="text-center">Candidates</th>
                    <th class="text-center">Status</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($userAssignments ?? [] as $asgn)
                <tr>
                    <td>{{ $asgn->district->name ?? 'N/A' }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $asgn->school->name ?? 'N/A' }}</div>
                        <div style="font-size: 0.75rem; color: var(--tz-text-muted);">{{ $asgn->school->code ?? '' }}</div>
                    </td>
                    <td>{{ $asgn->subject->name ?? 'N/A' }}</td>
                    <td class="text-center">--</td>
                    <td class="text-center">
                        <span class="badge {{ $asgn->status === 'active' ? 'badge-green' : 'badge-blue' }}">
                            {{ ucfirst($asgn->status) }}
                        </span>
                    </td>
                    <td class="text-right">
                        <a href="{{ url('/mark-entry/psle?view=entry-sheet&assignment_id=' . $asgn->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-keyboard"></i> Open
                        </a>
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="6">
                        <div class="empty-state" style="padding: 20px;">
                            <div class="empty-desc">No specific assignments found. Use the selector above to work across the region.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

<style>
    .school-filter-fallback {
        width: 100%;
        height: 40px;
        margin-bottom: 8px;
        padding: 0 12px;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px;
        color: #fff;
        font-family: 'Maiandra GD', sans-serif;
        font-size: 0.85rem;
        font-weight: 600;
        outline: none;
    }
    .school-filter-fallback::placeholder {
        color: rgba(255,255,255,0.48);
    }
    .school-filter-fallback:focus {
        border-color: var(--tz-blue);
        box-shadow: 0 0 0 1px rgba(0,163,221,0.25);
    }
    .school-filter-fallback.is-hidden {
        display: none;
    }
    .subject-lock-note {
        margin-top: 7px;
        color: var(--tz-text-muted);
        font-size: 0.72rem;
        line-height: 1.25;
    }
    .select2-container--psle-school .select2-selection--single {
        height: 40px !important;
        display: flex !important;
        align-items: center !important;
        background: rgba(255,255,255,0.05) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        border-radius: 8px !important;
        color: #fff !important;
        font-family: 'Maiandra GD', sans-serif !important;
    }
    .select2-container--psle-school.select2-container--focus .select2-selection--single,
    .select2-container--psle-school.select2-container--open .select2-selection--single {
        border-color: var(--tz-blue) !important;
        box-shadow: 0 0 0 1px rgba(0,163,221,0.25) !important;
    }
    .select2-container--psle-school .select2-selection__rendered {
        color: #fff !important;
        line-height: 40px !important;
        padding-left: 12px !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
    }
    .select2-container--psle-school .select2-selection__placeholder {
        color: rgba(255,255,255,0.65) !important;
    }
    .select2-container--psle-school .select2-selection__arrow {
        height: 38px !important;
    }
    .select2-container--psle-school .select2-selection__arrow b {
        border-color: rgba(255,255,255,0.65) transparent transparent transparent !important;
    }
    .select2-container--psle-school .select2-dropdown {
        background: #111820 !important;
        border: 1px solid rgba(0,163,221,0.35) !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        font-family: 'Maiandra GD', sans-serif !important;
    }
    .select2-container--psle-school .select2-search--dropdown {
        padding: 8px !important;
        background: #111820 !important;
    }
    .select2-container--psle-school .select2-search__field {
        background: rgba(255,255,255,0.06) !important;
        border: 1px solid rgba(255,255,255,0.14) !important;
        border-radius: 6px !important;
        color: #fff !important;
        outline: none !important;
        padding: 7px 10px !important;
        font-family: inherit !important;
    }
    .select2-container--psle-school .select2-results__option {
        color: #fff !important;
        padding: 8px 12px !important;
        font-size: 0.86rem !important;
    }
    .select2-container--psle-school .select2-results__option--highlighted {
        background: var(--tz-blue) !important;
        color: #fff !important;
    }
    .select2-container--psle-school .select2-results__option[aria-selected="true"] {
        background: rgba(0,163,221,0.28) !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const fallbackSearch = document.getElementById('school-filter-fallback');
    const schoolSelect = document.getElementById('start-entry-school-select');

    if (!schoolSelect) {
        return;
    }

    const enableFallbackSearch = function () {
        if (!fallbackSearch) {
            return;
        }

        fallbackSearch.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            Array.from(schoolSelect.options).forEach(function (option) {
                if (!option.value) {
                    option.hidden = false;
                    return;
                }

                const centre = (option.dataset.centre || '').toLowerCase();
                const schoolName = (option.dataset.schoolName || '').toLowerCase();
                const displayText = option.text.toLowerCase();
                const searchableText = [centre, schoolName, displayText].join(' ');

                option.hidden = query.length > 0 && !searchableText.includes(query);
            });
        });
    };

    const normalizeSchoolSearch = function (value) {
        return String(value || '')
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
    };

    const matchSchoolByCentreOrName = function (params, data) {
        if (!params.term || params.term.trim() === '') {
            return data;
        }

        if (!data.element) {
            return null;
        }

        const query = normalizeSchoolSearch(params.term);
        const centre = normalizeSchoolSearch(data.element.dataset.centre);
        const schoolName = normalizeSchoolSearch(data.element.dataset.schoolName);
        const displayText = normalizeSchoolSearch(data.text);

        if (centre.includes(query) || schoolName.includes(query) || displayText.includes(query)) {
            return data;
        }

        return null;
    };

    const initSelect2 = function () {
        if (!window.jQuery || !jQuery.fn.select2) {
            enableFallbackSearch();
            return;
        }

        const $schoolSelect = jQuery(schoolSelect);

        if ($schoolSelect.hasClass('select2-hidden-accessible')) {
            return;
        }

        $schoolSelect.select2({
            theme: 'psle-school',
            width: '100%',
            placeholder: schoolSelect.dataset.placeholder || 'Search by centre number or school name',
            allowClear: true,
            dropdownAutoWidth: true,
            matcher: matchSchoolByCentreOrName
        });

        if (fallbackSearch) {
            fallbackSearch.classList.add('is-hidden');
        }
    };

    try {
        initSelect2();
    } catch (error) {
        enableFallbackSearch();
    }
});
</script>
