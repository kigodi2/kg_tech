                <div class="adm-breadcrumb">
                    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Bulk Import</span>
                </div>

                <div class="adm-page-header">
                    <h1 class="adm-page-title">Bulk Import</h1>
                    <p class="adm-page-desc">Download the PSLE CSV template, fill the Mark column only, preview validation, and import valid rows.</p>
                </div>

                <!-- Summary Cards -->
                <div class="adm-stats">
                    <div class="adm-stat">
                        <div class="adm-stat-label">Templates Downloaded</div>
                        <div class="adm-stat-value" id="stats-templates" style="color: #fff;">0</div>
                        <i class="fas fa-download adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Files Uploaded</div>
                        <div class="adm-stat-value" id="stats-uploads" style="color: var(--tz-blue);">0</div>
                        <i class="fas fa-upload adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Valid Rows</div>
                        <div class="adm-stat-value" id="stats-valid" style="color: var(--tz-green);">0</div>
                        <i class="fas fa-check adm-stat-icon"></i>
                    </div>
                    <div class="adm-stat">
                        <div class="adm-stat-label">Invalid Rows</div>
                        <div class="adm-stat-value" id="stats-invalid" style="color: #ff7b7b;">0</div>
                        <i class="fas fa-times adm-stat-icon"></i>
                    </div>
                </div>

                <!-- Step 1: Selection & Template -->
                <div class="adm-card">
                    <div class="adm-card-head">
                        <div class="adm-card-title">1. Preparation & Upload</div>
                    </div>
                    <div class="adm-card-body">
                        <!-- Filters (GET Form) -->
                        <form method="GET" action="{{ url()->current() }}" class="adm-filters" id="bulk-filter-form">
                            <input type="hidden" name="view" value="bulk-import">
                            
                            <div class="adm-filter-group">
                                <label class="adm-filter-label">Exam Year</label>
                                <select name="exam_year_id" id="bulk-year-id" class="adm-select">
                                    @foreach($examYears ?? [] as $yr)
                                        <option value="{{ $yr->id }}" {{ ($activeFilters['exam_year_id'] ?? '') == $yr->id ? 'selected' : '' }}>{{ $yr->year_label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="adm-filter-group">
                                <label class="adm-filter-label">Region</label>
                                <select name="region_id" id="bulk-region-id" class="adm-select" {{ !empty($allowedRegionId) ? 'disabled' : '' }}>
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
                                <select name="district_id" id="bulk-district-id" class="adm-select">
                                    <option value="">All Districts</option>
                                    @foreach($districts ?? [] as $dist)
                                        <option value="{{ $dist->id }}" {{ ($activeFilters['district_id'] ?? '') == $dist->id ? 'selected' : '' }}>{{ $dist->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="adm-filter-group">
                                <label class="adm-filter-label">Primary School</label>
                                <select
                                    name="school_id"
                                    id="bulk-school-id"
                                    class="adm-select"
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
                                <label class="adm-filter-label">Subject</label>
                                <select name="subject_id" id="bulk-subject-id" class="adm-select">
                                    <option value="">Select Subject</option>
                                    @foreach($psleSubjects as $subj)
                                        <option value="{{ $subj->id }}" {{ ($activeFilters['subject_id'] ?? '') == $subj->id ? 'selected' : '' }}>{{ $subj->code }} - {{ $subj->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="adm-filter-group" style="display:flex; align-items:flex-end; min-width: 180px; gap: 8px;">
                                <button type="button" id="btn-download-template" class="btn btn-primary" style="flex:1; height:40px;">
                                    <i class="fas fa-download"></i> Download Template
                                </button>
                                <a href="{{ url('/mark-entry/psle?view=bulk-import') }}" class="btn btn-outline" style="height:40px;" title="Reset Filters"><i class="fas fa-undo"></i></a>
                            </div>
                        </form>

                        @if($isMarkOfficer && !$isAdmin)
                        <!-- Upload (POST Form) -->
                        <form id="bulk-import-form" enctype="multipart/form-data" style="padding: 20px;">
                            <div class="form-group">
                                <label class="adm-filter-label" style="font-size: 0.85rem; margin-bottom: 12px;">Upload CSV File</label>
                                <div class="custom-file-upload" id="drop-zone" style="background: rgba(255,255,255,0.01); border: 2px dashed rgba(255,255,255,0.08); padding: 50px 20px;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: var(--tz-blue); margin-bottom: 15px; opacity: 0.8;"></i>
                                    <p style="font-size: 1rem; color: var(--tz-text-muted); margin-bottom: 5px;">Drag & Drop CSV file here or <strong>Click to Browse</strong></p>
                                    <p style="font-size: 0.75rem; color: rgba(255,255,255,0.3);">Expected format: CNO, PReM, Name, Sex, Mark</p>
                                    <input type="file" name="file" id="bulk-file-input" accept=".csv,.txt" style="display: none;">
                                    <div id="selected-file-name" style="margin-top: 15px; font-weight: bold; color: var(--tz-green); display: none; background: rgba(30,181,58,0.1); padding: 8px 15px; border-radius: 20px;"></div>
                                    <button type="button" id="btn-remove-file" class="btn btn-outline btn-sm" style="margin-top: 12px; display:none;">
                                        <i class="fas fa-times"></i> Remove File
                                    </button>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end" style="margin-top: 20px;">
                                <button type="button" id="btn-preview-import" class="btn btn-primary" disabled>
                                    <i class="fas fa-eye"></i> Preview & Validate
                                </button>
                            </div>
                        </form>
                        @else
                        <div style="padding: 30px; text-align: center; color: var(--tz-text-muted);">
                            <div class="alert alert-info" style="display: inline-flex; align-items: center; gap: 10px; background: rgba(0,163,221,0.08); border: 1px solid rgba(0,163,221,0.18); border-radius: 8px; padding: 12px 20px; text-align: left; max-width: 600px; color: #fff;">
                                <i class="fas fa-info-circle" style="color: var(--tz-blue); font-size: 1.2rem; flex-shrink: 0;"></i>
                                <div>
                                    <strong style="color: var(--tz-yellow);">Bulk import is available only to Mark Entry Officers.</strong>
                                    <div style="font-size: 0.85rem; margin-top: 4px; color: var(--tz-text-muted);">Admin and REO accounts can view mark sheets and download templates for review only. Mark entry capabilities are restricted.</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Step 2: Preview Results (Hidden by default) -->
                <div id="preview-section" class="adm-card" style="display: none; margin-top: 20px;">
                    <div class="adm-card-head d-flex justify-content-between align-items-center">
                        <div class="adm-card-title">2. Preview & Validation Results</div>
                        <div id="preview-status-badge"></div>
                    </div>
                    <div class="adm-card-body">
                        <div class="alert alert-info" id="preview-summary-text">
                            Validating data...
                        </div>

                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="adm-table" id="preview-table">
                                <thead>
                                    <tr>
                                        <th>Row</th>
                                        <th>CNO</th>
                                        <th>PReM No.</th>
                                        <th>Full Name</th>
                                        <th>Sex</th>
                                        <th>Mark</th>
                                        <th>Validation Status</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center" style="margin-top: 20px;">
                            <button type="button" id="btn-cancel-import" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="button" id="btn-commit-import" class="btn btn-green" disabled>
                                <i class="fas fa-check-double"></i> Import Valid Rows
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Import History -->
                <div class="adm-card" style="margin-top: 20px;">
                    <div class="adm-card-head">
                        <div class="adm-card-title">Import History</div>
                    </div>
                    <div class="adm-card-body">
                        <div class="table-responsive">
                            <table class="adm-table" id="history-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>School</th>
                                        <th>Subject</th>
                                        <th>Batch Code</th>
                                        <th>Total</th>
                                        <th>Valid</th>
                                        <th>Errors</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="history-body">
                                    <tr>
                                        <td colspan="9" class="text-center">Loading history...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

<style>
.select2-container--bootstrap4 .select2-selection--single {
    height: 46px !important;
    display: flex !important;
    align-items: center !important;
    background-color: #fff !important;
    border: 1px solid #ced4da !important;
    font-family: 'Maiandra GD', sans-serif !important;
    border-radius: 6px !important;
}
.select2-container--bootstrap4 .select2-selection__rendered {
    line-height: 46px !important;
    padding-left: 15px !important;
    font-size: 0.85rem !important;
    font-family: inherit !important;
    color: #fff !important;
    font-weight: 600 !important;
}
.select2-results__option {
    font-family: 'Maiandra GD', sans-serif !important;
}
.select2-container--psle-bulk-school .select2-selection--single {
    height: 40px !important;
    display: flex !important;
    align-items: center !important;
    background: rgba(255,255,255,0.05) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    border-radius: 8px !important;
    color: #fff !important;
    font-family: 'Maiandra GD', sans-serif !important;
}
.select2-container--psle-bulk-school.select2-container--focus .select2-selection--single,
.select2-container--psle-bulk-school.select2-container--open .select2-selection--single {
    border-color: var(--tz-blue) !important;
    box-shadow: 0 0 0 1px rgba(0,163,221,0.25) !important;
}
.select2-container--psle-bulk-school .select2-selection__rendered {
    color: #fff !important;
    line-height: 40px !important;
    padding-left: 12px !important;
    font-size: 0.85rem !important;
    font-weight: 600 !important;
}
.select2-container--psle-bulk-school .select2-selection__placeholder {
    color: rgba(255,255,255,0.65) !important;
}
.select2-container--psle-bulk-school .select2-selection__arrow {
    height: 38px !important;
}
.select2-container--psle-bulk-school .select2-selection__arrow b {
    border-color: rgba(255,255,255,0.65) transparent transparent transparent !important;
}
.select2-container--psle-bulk-school .select2-dropdown {
    background: #111820 !important;
    border: 1px solid rgba(0,163,221,0.35) !important;
    border-radius: 8px !important;
    overflow: hidden !important;
    font-family: 'Maiandra GD', sans-serif !important;
}
.select2-container--psle-bulk-school .select2-search--dropdown {
    padding: 8px !important;
    background: #111820 !important;
}
.select2-container--psle-bulk-school .select2-search__field {
    background: rgba(255,255,255,0.06) !important;
    border: 1px solid rgba(255,255,255,0.14) !important;
    border-radius: 6px !important;
    color: #fff !important;
    outline: none !important;
    padding: 7px 10px !important;
    font-family: inherit !important;
}
.select2-container--psle-bulk-school .select2-results__option {
    color: #fff !important;
    padding: 8px 12px !important;
    font-size: 0.86rem !important;
}
.select2-container--psle-bulk-school .select2-results__option--highlighted {
    background: var(--tz-blue) !important;
    color: #fff !important;
}
.select2-container--psle-bulk-school .select2-results__option[aria-selected="true"] {
    background: rgba(0,163,221,0.28) !important;
}

.custom-file-upload {
    border: 2px dashed rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.custom-file-upload:hover, .custom-file-upload.dragging {
    border-color: var(--tz-blue);
    background: rgba(var(--tz-blue-rgb), 0.05);
}
.badge-error { background: #ff4d4d; color: white; }
.badge-success { background: #28a745; color: white; }
.row-error { background: rgba(255, 77, 77, 0.05); }
.row-error td { color: #ff7b7b !important; }
</style>

<script>
$(document).ready(function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('bulk-file-input');
    const fileNameDisplay = document.getElementById('selected-file-name');
    const btnRemoveFile = document.getElementById('btn-remove-file');
    const btnPreview = document.getElementById('btn-preview-import');
    const btnDownload = document.getElementById('btn-download-template');
    const btnCommit = document.getElementById('btn-commit-import');
    const btnCancel = document.getElementById('btn-cancel-import');
    const previewSection = document.getElementById('preview-section');
    const historyBody = document.getElementById('history-body');

    // Stats elements
    const statTemplates = document.getElementById('stats-templates');
    const statUploads = document.getElementById('stats-uploads');
    const statValid = document.getElementById('stats-valid');
    const statInvalid = document.getElementById('stats-invalid');

    let currentFile = null;
    const routes = {
        regions: '{{ route("mark-entry.psle.bulk.filters.regions") }}',
        districts: '{{ route("mark-entry.psle.bulk.filters.districts") }}',
        schools: '{{ route("mark-entry.psle.bulk.filters.schools") }}',
        subjects: '{{ route("mark-entry.psle.bulk.filters.subjects") }}'
    };

    const yearSelect = document.getElementById('bulk-year-id');
    const regionSelect = document.getElementById('bulk-region-id');
    const districtSelect = document.getElementById('bulk-district-id');
    const schoolSelect = document.getElementById('bulk-school-id');
    const subjectSelect = document.getElementById('bulk-subject-id');

    if ($.fn.select2) {
        $('#bulk-school-id').select2({
            theme: 'psle-bulk-school',
            width: '100%',
            placeholder: $('#bulk-school-id').data('placeholder') || 'Search by centre number or school name',
            allowClear: true,
            dropdownAutoWidth: true,
            minimumInputLength: 2,
            ajax: {
                url: routes.schools,
                dataType: 'json',
                delay: 350,
                cache: true,
                data: function(params) {
                    return {
                        q: params.term || '',
                        exam_year_id: yearSelect.value,
                        region_id: regionSelect ? regionSelect.value : '',
                        district_id: districtSelect.value
                    };
                },
                processResults: function(data) {
                    return {
                        results: (data.data || []).map(function(row) {
                            return {
                                id: row.id,
                                text: row.text || ((row.code ? row.code + ' - ' : '') + row.name),
                                code: row.code,
                                name: row.name
                            };
                        })
                    };
                }
            }
        });
    }

    function selectedContextReady() {
        return schoolSelect.value && subjectSelect.value && yearSelect.value;
    }

    function refreshActionState() {
        const ready = selectedContextReady();
        btnDownload.disabled = !ready;
        fileInput.disabled = !ready;
        btnPreview.disabled = !ready || !currentFile;
        if (!ready) {
            dropZone.style.opacity = '0.55';
            dropZone.style.pointerEvents = 'none';
        } else {
            dropZone.style.opacity = '1';
            dropZone.style.pointerEvents = 'auto';
        }
    }

    function optionHtml(value, text, selected = false, extra = {}) {
        const attrs = Object.entries(extra).map(([key, val]) => ` data-${key}="${String(val || '').replace(/"/g, '&quot;')}"`).join('');
        return `<option value="${value}"${selected ? ' selected' : ''}${attrs}>${text}</option>`;
    }

    function resetSelect(select, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
        if (window.jQuery && jQuery.fn.select2 && jQuery(select).hasClass('select2-hidden-accessible')) {
            jQuery(select).trigger('change.select2');
        }
    }

    function resetSchoolSearch(placeholder = 'Type centre number or school name') {
        schoolSelect.innerHTML = `<option value="">${placeholder}</option>`;
        schoolSelect.disabled = false;
        if (window.jQuery && jQuery.fn.select2 && jQuery(schoolSelect).hasClass('select2-hidden-accessible')) {
            jQuery(schoolSelect).val(null).trigger('change');
        }
    }

    function populateSelect(select, placeholder, rows, selectedValue = '') {
        select.innerHTML = `<option value="">${placeholder}</option>` + rows.map(row => {
            const text = row.text || row.name || `${row.code || ''} ${row.name || ''}`.trim();
            return optionHtml(row.id, text, String(row.id) === String(selectedValue), {
                centre: row.code || '',
                schoolName: row.name || ''
            });
        }).join('');
        select.disabled = false;
        if (window.jQuery && jQuery.fn.select2 && jQuery(select).hasClass('select2-hidden-accessible')) {
            jQuery(select).trigger('change.select2');
        }
    }

    function fetchJson(url, params = {}) {
        const query = new URLSearchParams(params);
        return fetch(url + (query.toString() ? '?' + query.toString() : ''), {
            headers: { 'Accept': 'application/json' }
        }).then(response => response.json());
    }

    async function loadDistricts() {
        resetSelect(districtSelect, 'Loading districts...');
        resetSchoolSearch();
        resetSelect(subjectSelect, 'Select Subject');
        const data = await fetchJson(routes.districts, {
            exam_year_id: yearSelect.value,
            region_id: regionSelect.value
        });
        populateSelect(districtSelect, 'All Districts', data.data || []);
        refreshActionState();
    }

    async function loadSchools() {
        resetSchoolSearch();
        resetSelect(subjectSelect, 'Select Subject');
        refreshActionState();
    }

    async function loadSubjects() {
        resetSelect(subjectSelect, 'Loading subjects...');
        if (!schoolSelect.value) {
            resetSelect(subjectSelect, 'Select Subject');
            refreshActionState();
            return;
        }
        const data = await fetchJson(routes.subjects, {
            exam_year_id: yearSelect.value,
            school_id: schoolSelect.value
        });
        if (!data.success) {
            resetSelect(subjectSelect, 'Select Subject');
            Swal.fire('Subject Filter', data.message || 'Could not load subjects for the selected school.', 'warning');
            refreshActionState();
            return;
        }
        populateSelect(subjectSelect, 'Select Subject', data.data || []);
        refreshActionState();
    }

    yearSelect.addEventListener('change', async function () {
        await loadDistricts();
    });
    if (regionSelect) {
        regionSelect.addEventListener('change', async function () {
            await loadDistricts();
        });
    }
    districtSelect.addEventListener('change', loadSchools);
    $('#bulk-school-id').on('change', function () {
        loadSubjects();
        loadHistory();
    });
    subjectSelect.addEventListener('change', function () {
        refreshActionState();
        loadHistory();
    });

    // Load history on start
    loadHistory();
    refreshActionState();

    // Re-load history when filters change
    $('#bulk-subject-id').on('change', loadHistory);

    // Template Download
    $(document).on('click', '#btn-download-template', function(e) {
        e.preventDefault();
        const schoolId = $('#bulk-school-id').val();
        const subjectId = $('#bulk-subject-id').val();
        const yearId = $('#bulk-year-id').val() || '{{ $activeFilters["exam_year_id"] }}';

        if (!selectedContextReady()) {
            Swal.fire({
                title: 'Required',
                text: 'Please select school and subject first.',
                icon: 'warning',
                confirmButtonColor: '#bba45e'
            });
            return;
        }

        const url = `{{ route('mark-entry.psle.bulk-import.template') }}?school_id=${schoolId}&subject_id=${subjectId}&exam_year_id=${yearId}`;
        window.location.href = url;
        
        // Update stats (simulated for UI feedback)
        const $stat = $('#stats-templates');
        $stat.text(parseInt($stat.text()) + 1);
    });

    // File selection
    dropZone.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // Drag and drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragging');
    });

    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragging');
        if (e.dataTransfer.files.length > 0) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!selectedContextReady()) {
            Swal.fire('Required', 'Please select exam year, primary school, and subject before uploading.', 'warning');
            return;
        }
        if (!/\.csv$/i.test(file.name) && !/\.txt$/i.test(file.name)) {
            Swal.fire('Invalid File', 'Please upload a CSV file generated from the template.', 'error');
            return;
        }
        currentFile = file;
        fileNameDisplay.innerText = "Selected: " + file.name;
        fileNameDisplay.style.display = 'block';
        btnRemoveFile.style.display = 'inline-flex';
        refreshActionState();
    }

    btnRemoveFile.addEventListener('click', function(e) {
        e.stopPropagation();
        currentFile = null;
        fileInput.value = '';
        fileNameDisplay.style.display = 'none';
        btnRemoveFile.style.display = 'none';
        previewSection.style.display = 'none';
        refreshActionState();
    });

    // Preview Import
    $('#btn-preview-import').on('click', function() {
        const schoolId = $('#bulk-school-id').val();
        const subjectId = $('#bulk-subject-id').val();
        const yearId = $('#bulk-year-id').val() || '{{ $activeFilters["exam_year_id"] }}';

        if (!currentFile || !selectedContextReady()) return;

        const formData = new FormData();
        formData.append('file', currentFile);
        formData.append('school_id', schoolId);
        formData.append('subject_id', subjectId);
        formData.append('exam_year_id', yearId);
        formData.append('_token', '{{ csrf_token() }}');

        const $btn = $(this);
        $btn.prop('disabled', true);
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Validating...');

        fetch('{{ route("mark-entry.psle.bulk-import.preview") }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            $btn.prop('disabled', false);
            $btn.html('<i class="fas fa-eye"></i> Preview & Validate');

            if (!data.success && data.message) {
                Swal.fire('Error', data.message, 'error');
                if (data.errors && Array.isArray(data.errors)) {
                    showPreview(data);
                }
                return;
            }

            showPreview(data);
        })
        .catch(error => {
            $btn.prop('disabled', false);
            $btn.html('<i class="fas fa-eye"></i> Preview & Validate');
            Swal.fire('Error', 'Failed to validate file.', 'error');
        });
    });

    function showPreview(data) {
        previewSection.style.display = 'block';
        const tbody = document.querySelector('#preview-table tbody');
        tbody.innerHTML = '';

        const totals = data.totals || {};
        statValid.innerText = data.valid_count || totals.valid_rows || 0;
        statInvalid.innerText = data.invalid_count || totals.invalid_rows || 0;
        statUploads.innerText = parseInt(statUploads.innerText) + 1;

        const summaryText = document.getElementById('preview-summary-text');
        summaryText.innerHTML = `<strong>Upload preview completed.</strong> ${totals.valid_rows || data.valid_count || 0} valid rows and ${totals.invalid_rows || data.invalid_count || 0} invalid rows found. Duplicate CNO rows: ${totals.duplicate_rows || 0}. Candidates not found: ${totals.not_found_rows || 0}. Locked/submitted rows: ${totals.locked_rows || 0}. Existing marks ready to update: ${totals.existing_rows || 0}.`;
        
        if ((totals.invalid_rows || data.invalid_count || 0) > 0) {
            summaryText.className = 'alert alert-warning';
        } else {
            summaryText.className = 'alert alert-success';
        }

        // Show first 100 rows for preview
        const previewRows = data.records || data.preview || [];
        previewRows.forEach((row, index) => {
            const tr = document.createElement('tr');
            if (!row.valid) tr.className = 'row-error';

            tr.innerHTML = `
                <td>${row.line || index + 1}</td>
                <td>${row.candidate_number || '-'}</td>
                <td>${row.prem_no || '-'}</td>
                <td>${row.full_name || row.name || '-'}</td>
                <td>${row.sex || '-'}</td>
                <td><strong style="color: var(--tz-yellow)">${row.mark || ''}</strong></td>
                <td>
                    ${row.valid 
                        ? '<span class="badge badge-success">Valid</span>' 
                        : `<span class="badge badge-error" style="background: rgba(220,53,69,0.1); color: #ff808b; border: 1px solid rgba(220,53,69,0.2);" title="${(row.errors || []).join('; ')}">Error</span>`}
                </td>
                <td><small style="color: #9ca3af;">${row.message || (row.errors && row.errors.length > 0 ? row.errors[0] : '-')}</small></td>
            `;
            tbody.appendChild(tr);
        });

        btnCommit.disabled = ((totals.valid_rows || data.valid_count || 0) === 0);
        
        // Scroll to preview
        previewSection.scrollIntoView({ behavior: 'smooth' });
    }

    // Commit Import
    $('#btn-commit-import').on('click', function() {
        const schoolId = $('#bulk-school-id').val();
        const subjectId = $('#bulk-subject-id').val();
        const yearId = $('#bulk-year-id').val() || '{{ $activeFilters["exam_year_id"] }}';

        Swal.fire({
            title: 'Confirm Import',
            text: "Are you sure you want to commit these marks to the database?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Commit'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('file', currentFile);
                formData.append('school_id', schoolId);
                formData.append('subject_id', subjectId);
                formData.append('exam_year_id', yearId);
                formData.append('_token', '{{ csrf_token() }}');

                const $btn = $(this);
                $btn.prop('disabled', true);
                $btn.html('<i class="fas fa-spinner fa-spin"></i> Committing...');

                fetch('{{ route("mark-entry.psle.bulk-import.confirm") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    $btn.html('<i class="fas fa-check-double"></i> Commit All Valid Rows');
                    
                    if (data.success) {
                        const summary = data.summary || {};
                        Swal.fire('Success', data.message || `Imported ${summary.total_processed || 0} records successfully.`, 'success');
                        previewSection.style.display = 'none';
                        loadHistory();
                        // Reset file
                        currentFile = null;
                        fileInput.value = '';
                        fileNameDisplay.style.display = 'none';
                        btnRemoveFile.style.display = 'none';
                        refreshActionState();
                    } else {
                        Swal.fire('Error', data.message || 'Commit failed.', 'error');
                        $btn.prop('disabled', false);
                    }
                });
            }
        });
    });

    btnCancel.addEventListener('click', () => {
        previewSection.style.display = 'none';
    });

    function loadHistory() {
        const schoolId = document.getElementById('bulk-school-id').value;
        const subjectId = document.getElementById('bulk-subject-id').value;
        let url = '{{ route("mark-entry.psle.bulk-import.history") }}';
        
        const params = new URLSearchParams();
        if (schoolId) params.append('school_id', schoolId);
        if (subjectId) params.append('subject_id', subjectId);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }

        fetch(url)
        .then(response => response.json())
        .then(data => {
            historyBody.innerHTML = '';
            const batches = data.data || [];

            if (batches.length === 0) {
                historyBody.innerHTML = '<tr><td colspan="9" class="text-center">No import history found.</td></tr>';
                return;
            }

            batches.forEach(batch => {
                const date = batch.time || '';
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${date}</td>
                    <td>${batch.school_name || '-'}</td>
                    <td>${batch.subject_name || batch.subject_code || '-'}</td>
                    <td><code style="font-size: 0.75rem;">${batch.batch_code}</code></td>
                    <td>${batch.total_records}</td>
                    <td>${batch.valid_records}</td>
                    <td>${batch.error_records}</td>
                    <td><span class="badge badge-${getStatusColor(batch.status)}">${batch.status.toUpperCase()}</span></td>
                    <td>
                        ${batch.error_records > 0 
                            ? `<a href="/mark-entry/psle/bulk-import/errors/${batch.id}" class="btn btn-xs btn-outline-danger" title="Download Errors"><i class="fas fa-exclamation-triangle"></i></a>` 
                            : '<i class="fas fa-check text-success"></i>'}
                    </td>
                `;
                historyBody.appendChild(tr);
            });
        });
    }

    function getStatusColor(status) {
        switch(status) {
            case 'completed': return 'success';
            case 'draft': return 'yellow';
            case 'rejected': return 'danger';
            case 'submitted': return 'blue';
            default: return 'secondary';
        }
    }
});
</script>
