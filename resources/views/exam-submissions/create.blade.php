@extends('layouts.auth-rms')

@section('title', 'Submit Exam Paper')

@section('content')
<style>
    .exam-form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0;
        padding: 0.55rem 0.75rem;
        background: #ffffff;
        color: #111827;
    }

    .exam-form-control:focus {
        outline: 2px solid rgba(59, 130, 246, 0.15);
        outline-offset: 0;
        border-color: #3b82f6;
    }

    .exam-upload-frame {
        border-radius: 0 !important;
    }
</style>
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Submit Exam Paper</h1>
            <p class="text-gray-600 mt-2">Upload your exam paper in PDF or DOCX format. The system will validate it against NECTA format requirements.</p>
            <p class="text-sm text-amber-700 mt-1">Current server upload limit: {{ $maxUploadMegabytes }} MB</p>
        </div>

        <form id="examSubmissionForm" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- Exam Type -->
            <div>
                <label for="exam_type_search" class="block text-sm font-medium text-gray-700">Exam Type</label>
                <input id="exam_type_id" name="exam_type_id" type="hidden" required>
                <input id="exam_type_search" list="exam_type_options" placeholder="Search or select Exam Type" class="exam-form-control mt-1" autocomplete="off">
                <datalist id="exam_type_options">
                    @foreach($examTypes as $examType)
                        <option value="{{ $examType->name }} ({{ $examType->code }})" data-id="{{ $examType->id }}" data-code="{{ $examType->code }}"></option>
                    @endforeach
                </datalist>
            </div>

            <!-- Exam Year -->
            <div>
                <label for="exam_year_search" class="block text-sm font-medium text-gray-700">Exam Year</label>
                <input id="exam_year_id" name="exam_year_id" type="hidden" required>
                <input id="exam_year_search" list="exam_year_options" placeholder="Search or select Exam Year" class="exam-form-control mt-1" autocomplete="off">
                <datalist id="exam_year_options">
                    @foreach($examYears as $examYear)
                        <option value="{{ $examYear->year_label }}" data-id="{{ $examYear->id }}"></option>
                    @endforeach
                </datalist>
            </div>

            <!-- Subject -->
            <div>
                <label for="subject_search" class="block text-sm font-medium text-gray-700">Official NECTA Subject</label>
                <input id="subject_id" name="subject_id" type="hidden" required>
                <input id="subject_search" list="subject_options" placeholder="Select Exam Type first" class="exam-form-control mt-1" autocomplete="off">
                <datalist id="subject_options"></datalist>
            </div>

            <!-- School (Optional) -->
            <div>
                <label for="school_search" class="block text-sm font-medium text-gray-700">School (Optional)</label>
                <input id="school_id" name="school_id" type="hidden">
                <input id="school_search" list="school_options" placeholder="Search or select School (if applicable)" class="exam-form-control mt-1" autocomplete="off">
                <datalist id="school_options"></datalist>
            </div>

            <!-- File Upload -->
            <div>
                <label for="exam_paper" class="block text-sm font-medium text-gray-700">Exam Paper (PDF or DOCX)</label>
                <div class="exam-upload-frame mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="exam_paper" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload a PDF or DOCX file</span>
                                <input id="exam_paper" name="exam_paper" type="file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required class="sr-only">
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">PDF or DOCX up to {{ $maxUploadMegabytes }}MB</p>
                    </div>
                </div>
                <div id="fileInfo" class="mt-2 text-sm text-gray-600 hidden">
                    <span id="fileName"></span> (<span id="fileSize"></span>)
                </div>
            </div>

            <!-- Format Guidelines -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Format Requirements</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Ensure your exam paper follows the official NECTA format:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>PDF or DOCX only (max {{ $maxUploadMegabytes }}MB on the current server)</li>
                                <li>Clear NECTA branding and exam information</li>
                                <li>Proper page numbering</li>
                                <li>Subject and paper details clearly indicated</li>
                            </ul>
                            <p class="mt-2">
                                <a href="https://www.necta.go.tz/publications/all" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 underline">Browse all official NECTA publications (formats, templates)</a>
                            </p>
                            <p class="mt-1 text-sm text-blue-600">If you already downloaded a learning-type template from NECTA, use it as the baseline before uploading.</p>
                            <p class="mt-1">
                                <a id="viewFormatLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-blue-600 hover:text-blue-800 underline">Download NECTA format for selected exam type (FTNA general)</a>
                            </p>
                            <p class="mt-1">
                                <a id="viewVocationalFormatLink" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-blue-600 hover:text-blue-800 underline">Download FTNA vocational stream format</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" id="submitBtn"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" id="submitSpinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Submit Exam
                </button>
            </div>
        </form>

        <!-- Validation Results -->
        <div id="validationResults" class="mt-6 hidden">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Preliminary Validation Report</h3>
            <div id="validationContent"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const maxUploadMegabytes = {{ (int) $maxUploadMegabytes }};
    const form = document.getElementById('examSubmissionForm');
    const examTypeIdInput = document.getElementById('exam_type_id');
    const examTypeInput = document.getElementById('exam_type_search');
    const examYearIdInput = document.getElementById('exam_year_id');
    const examYearInput = document.getElementById('exam_year_search');
    const subjectIdInput = document.getElementById('subject_id');
    const subjectInput = document.getElementById('subject_search');
    const subjectOptions = document.getElementById('subject_options');
    const schoolIdInput = document.getElementById('school_id');
    const schoolInput = document.getElementById('school_search');
    const fileInput = document.getElementById('exam_paper');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');
    const submitSpinner = document.getElementById('submitSpinner');
    const validationResults = document.getElementById('validationResults');
    const validationContent = document.getElementById('validationContent');
    const viewFormatLink = document.getElementById('viewFormatLink');
    const viewVocationalFormatLink = document.getElementById('viewVocationalFormatLink');

    // Handle exam type change
    examTypeInput.addEventListener('change', function() {
        const selectedOption = resolveDatalistSelection(examTypeInput, examTypeIdInput, 'exam_type_options');
        subjectInput.value = '';
        subjectIdInput.value = '';
        subjectOptions.innerHTML = '';

        if (selectedOption) {
            const examTypeId = selectedOption.dataset.id;
            loadSubjects(examTypeId);
            updateFormatLink(selectedOption.dataset.code || '');
        } else {
            subjectInput.placeholder = 'Select Exam Type first';
            updateFormatLink('');
        }
    });

    examYearInput.addEventListener('change', function() {
        resolveDatalistSelection(examYearInput, examYearIdInput, 'exam_year_options');
    });

    subjectInput.addEventListener('change', function() {
        resolveDatalistSelection(subjectInput, subjectIdInput, 'subject_options');
    });

    schoolInput.addEventListener('change', function() {
        resolveDatalistSelection(schoolInput, schoolIdInput, 'school_options');
    });

    // Handle file selection
    fileInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > maxUploadMegabytes * 1024 * 1024) {
                alert(`This server currently allows uploads up to ${maxUploadMegabytes} MB. Please choose a smaller PDF/DOCX file or increase the PHP upload limit.`);
                this.value = '';
                fileInfo.classList.add('hidden');
                return;
            }
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileInfo.classList.remove('hidden');
        } else {
            fileInfo.classList.add('hidden');
        }
    });

    // Handle form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        resolveDatalistSelection(examTypeInput, examTypeIdInput, 'exam_type_options');
        resolveDatalistSelection(examYearInput, examYearIdInput, 'exam_year_options');
        resolveDatalistSelection(subjectInput, subjectIdInput, 'subject_options');
        resolveDatalistSelection(schoolInput, schoolIdInput, 'school_options');

        if (!examTypeIdInput.value || !examYearIdInput.value || !subjectIdInput.value) {
            alert('Please select Exam Type, Exam Year, and Official NECTA Subject from the searchable lists.');
            return;
        }

        submitBtn.disabled = true;
        submitSpinner.classList.remove('hidden');

        const formData = new FormData(form);

        try {
            const response = await fetch('/exam-submissions', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                showValidationResults(result.validation_results);
                if (result.validation_results.is_valid) {
                    alert('Exam submitted successfully!');
                    form.reset();
                    examTypeIdInput.value = '';
                    examYearIdInput.value = '';
                    subjectIdInput.value = '';
                    schoolIdInput.value = '';
                    subjectOptions.innerHTML = '';
                    subjectInput.placeholder = 'Select Exam Type first';
                    updateFormatLink('');
                    fileInfo.classList.add('hidden');
                }
            } else {
                alert('Submission failed: ' + result.message);
            }
        } catch (error) {
            alert('An error occurred during submission.');
            console.error(error);
        } finally {
            submitBtn.disabled = false;
            submitSpinner.classList.add('hidden');
        }
    });

    function loadSubjects(examTypeId) {
        fetch(`/exam-submissions/subjects/${examTypeId}`)
            .then(response => response.json())
            .then(subjects => {
                subjectOptions.innerHTML = '';
                subjectInput.placeholder = 'Search or select Official NECTA Subject';
                subjects.forEach(subject => {
                    const option = document.createElement('option');
                    option.value = `${subject.code} - ${subject.name}`;
                    option.dataset.id = subject.id;
                    subjectOptions.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                subjectOptions.innerHTML = '';
                subjectInput.placeholder = 'Error loading official subjects';
            });
    }

    function updateFormatLink(code) {
        viewFormatLink.classList.add('hidden');
        viewVocationalFormatLink.classList.add('hidden');

        if (code) {
            let formatUrl = '';
            let vocationalUrl = '';

            switch (code) {
                case 'ACSEE':
                    formatUrl = '{{ route("exam-types.acsee.formats.pdf") }}';
                    break;
                case 'CSEE':
                    formatUrl = '{{ route("exam-types.csee.formats.pdf") }}';
                    break;
                case 'FTNA':
                    formatUrl = '{{ route("exam-types.ftna.formats.pdf") }}?stream=general';
                    vocationalUrl = '{{ route("exam-types.ftna.formats.pdf") }}?stream=vocational';
                    break;
            }

            if (formatUrl) {
                viewFormatLink.href = formatUrl;
                viewFormatLink.textContent = code === 'FTNA' ? 'Download FTNA general format' : 'Download NECTA format for selected exam type';
                viewFormatLink.classList.remove('hidden');
            }

            if (vocationalUrl) {
                viewVocationalFormatLink.href = vocationalUrl;
                viewVocationalFormatLink.classList.remove('hidden');
            }
        }
    }

    function resolveDatalistSelection(input, hiddenInput, datalistId) {
        const option = Array.from(document.querySelectorAll(`#${datalistId} option`))
            .find(item => item.value === input.value.trim());

        hiddenInput.value = option ? (option.dataset.id || '') : '';

        return option || null;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function showValidationResults(results) {
        validationResults.classList.remove('hidden');

        const rulebook = results.rulebook || {};
        const templateComparison = results.template_comparison || {};
        const recommendations = results.recommendations || [];
        let html = '<div class="space-y-4">';

        // Status
        const statusClass = results.is_valid ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100';
        const statusText = results.is_valid
            ? 'The submitted paper meets the current validation threshold.'
            : 'The submitted paper does not meet the current validation threshold.';
        html += `<div class="p-4 rounded-md ${statusClass}">`;
        html += `<h4 class="font-medium">${statusText}</h4>`;
        html += '</div>';

        // Errors
        if (results.errors && results.errors.length > 0) {
            html += '<div class="bg-red-50 border border-red-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-red-800 mb-2">Non-Compliance Findings:</h5>';
            html += '<ul class="list-disc list-inside text-red-700 space-y-1">';
            results.errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }

        // Warnings
        if (results.warnings && results.warnings.length > 0) {
            html += '<div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-yellow-800 mb-2">Observations Requiring Attention:</h5>';
            html += '<ul class="list-disc list-inside text-yellow-700 space-y-1">';
            results.warnings.forEach(warning => {
                html += `<li>${warning}</li>`;
            });
            html += '</ul>';
            html += '</div>';
        }

        if (Object.keys(templateComparison).length > 0) {
            html += '<div class="bg-white border border-gray-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-gray-800 mb-3">Official Template Comparison</h5>';
            html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
            html += `<div class="rounded-md border border-gray-200 bg-gray-50 p-3"><p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Template Reference Status</p><p class="mt-1 text-sm text-gray-900">${templateComparison.template_available ? 'Official reference template available' : 'Official reference template not available'}</p></div>`;
            html += `<div class="rounded-md border border-gray-200 bg-gray-50 p-3"><p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Indicative Compliance Score</p><p class="mt-1 text-sm text-gray-900">${typeof templateComparison.compliance_score !== 'undefined' ? templateComparison.compliance_score + '%' : 'N/A'}</p></div>`;
            html += '</div>';

            if (Array.isArray(templateComparison.matched_elements) && templateComparison.matched_elements.length > 0) {
                html += '<div class="mt-3">';
                html += '<h6 class="font-medium text-green-800 mb-1">Confirmed Areas of Alignment:</h6>';
                html += '<ul class="list-disc list-inside text-sm text-green-700 space-y-1">';
                templateComparison.matched_elements.forEach(item => {
                    html += `<li>${escapeHtml(item)}</li>`;
                });
                html += '</ul></div>';
            }

            if (Array.isArray(templateComparison.missing_elements) && templateComparison.missing_elements.length > 0) {
                html += '<div class="mt-3">';
                html += '<h6 class="font-medium text-red-800 mb-1">Identified Areas of Non-Alignment:</h6>';
                html += '<ul class="list-disc list-inside text-sm text-red-700 space-y-1">';
                templateComparison.missing_elements.forEach(item => {
                    html += `<li>${escapeHtml(item)}</li>`;
                });
                html += '</ul></div>';
            }

            html += '</div>';
        }

        if (Object.keys(rulebook).length > 0) {
            html += '<div class="bg-white border border-gray-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-gray-800 mb-3">NECTA Official Format Reference</h5>';
            html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
            html += `<div class="rounded-md border border-gray-200 bg-gray-50 p-3"><p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Reference Guide</p><p class="mt-1 text-sm text-gray-900">${escapeHtml(rulebook.guide_title || 'N/A')}</p>${rulebook.edition ? `<p class="text-xs text-gray-500 mt-1">${escapeHtml(rulebook.edition)}</p>` : ''}</div>`;
            html += `<div class="rounded-md border border-gray-200 bg-gray-50 p-3"><p class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Validation Profile</p><p class="mt-1 text-sm text-gray-900">${escapeHtml(formatLabel(rulebook.profile_status || 'unknown'))}</p>${rulebook.subject_name ? `<p class="text-xs text-gray-500 mt-1">${escapeHtml(rulebook.subject_name)}</p>` : ''}</div>`;
            html += '</div>';

            if (Array.isArray(rulebook.common_sections) && rulebook.common_sections.length > 0) {
                html += '<div class="mt-3">';
                html += '<h6 class="font-medium text-gray-800 mb-2">Expected Sections in the Official Format</h6>';
                html += '<div class="flex flex-wrap gap-2">';
                rulebook.common_sections.forEach(section => {
                    html += `<span class="inline-flex px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">${escapeHtml(formatLabel(section))}</span>`;
                });
                html += '</div></div>';
            }

            if (Array.isArray(rulebook.papers) && rulebook.papers.length > 0) {
                html += '<div class="mt-4 space-y-3">';
                html += '<h6 class="font-medium text-gray-800">Expected Paper Structure Under the NECTA Guide</h6>';
                rulebook.papers.forEach(paper => {
                    html += '<div class="rounded-md border border-gray-200 bg-gray-50 p-4">';
                    html += `<p class="text-sm font-semibold text-gray-900">${escapeHtml(paper.code || 'Paper')}${paper.type ? `<span class="ml-2 text-xs font-medium text-gray-500 uppercase tracking-wide">${escapeHtml(formatLabel(paper.type))}</span>` : ''}</p>`;
                    html += '<div class="mt-1 text-xs text-gray-500 space-y-1">';
                    if (paper.duration) html += `<p>Duration: ${escapeHtml(paper.duration)}</p>`;
                    if (paper.duration_special_needs) html += `<p>Special needs duration: ${escapeHtml(paper.duration_special_needs)}</p>`;
                    if (paper.total_marks) html += `<p>Total marks: ${escapeHtml(String(paper.total_marks))}</p>`;
                    html += '</div>';

                    if (Array.isArray(paper.sections) && paper.sections.length > 0) {
                        html += '<div class="mt-3 overflow-x-auto"><table class="min-w-full text-sm">';
                        html += '<thead><tr class="text-left text-gray-500 border-b border-gray-200"><th class="py-2 pr-4 font-medium">Section</th><th class="py-2 pr-4 font-medium">Question Types</th><th class="py-2 font-medium">Marks</th></tr></thead><tbody>';
                        paper.sections.forEach(section => {
                            const questionTypes = Array.isArray(section.question_types) ? section.question_types.map(formatLabel).join(', ') : '-';
                            html += `<tr class="border-b border-gray-100"><td class="py-2 pr-4 text-gray-900">${escapeHtml(section.name || '-')}</td><td class="py-2 pr-4 text-gray-600">${escapeHtml(questionTypes)}</td><td class="py-2 text-gray-900">${escapeHtml(section.marks != null ? String(section.marks) : '-')}</td></tr>`;
                        });
                        html += '</tbody></table></div>';
                    }

                    if (Array.isArray(paper.components) && paper.components.length > 0) {
                        html += '<div class="mt-3"><h6 class="font-medium text-gray-800 mb-1">Assessment Components</h6><ul class="list-disc list-inside text-sm text-gray-700 space-y-1">';
                        paper.components.forEach(component => {
                            const parts = [formatLabel(component.name || 'component')];
                            if (typeof component.marks !== 'undefined') parts.push(component.marks + ' marks');
                            if (component.duration) parts.push(component.duration);
                            html += `<li>${escapeHtml(parts.join(' - '))}</li>`;
                        });
                        html += '</ul></div>';
                    }

                    if (Array.isArray(paper.rules) && paper.rules.length > 0) {
                        html += '<div class="mt-3"><h6 class="font-medium text-gray-800 mb-1">Official Expectations</h6><ul class="list-disc list-inside text-sm text-gray-700 space-y-1">';
                        paper.rules.forEach(rule => {
                            html += `<li>${escapeHtml(rule)}</li>`;
                        });
                        html += '</ul></div>';
                    }

                    html += '</div>';
                });
                html += '</div>';
            }

            html += '</div>';
        }

        if (Array.isArray(recommendations) && recommendations.length > 0) {
            html += '<div class="bg-white border border-gray-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-gray-800 mb-2">Formal Assessment Remarks:</h5>';
            html += '<ul class="list-disc list-inside text-sm text-gray-700 space-y-1">';
            recommendations.forEach(item => {
                html += `<li>${escapeHtml(item)}</li>`;
            });
            html += '</ul></div>';
        }

        // Metadata
        if (results.metadata) {
            html += '<div class="bg-gray-50 border border-gray-200 rounded-md p-4">';
            html += '<h5 class="font-medium text-gray-800 mb-2">Document Information:</h5>';

            if (results.document_inspector && results.document_inspector.fields && Object.keys(results.document_inspector.fields).length > 0) {
                html += '<div class="mb-3 bg-white border border-gray-200 rounded-md p-4">';
                html += '<h6 class="font-medium text-gray-800 mb-2">Document Metadata Inspector</h6>';
                html += '<dl class="space-y-1">';
                Object.entries(results.document_inspector.fields).forEach(([key, value]) => {
                    html += `<div><dt class="inline font-medium">${escapeHtml(formatLabel(key))}:</dt> <dd class="inline ml-2">${escapeHtml(String(value))}</dd></div>`;
                });
                html += '</dl>';
                html += '</div>';
            }

            if (results.document_inspector && Array.isArray(results.document_inspector.environment_clues) && results.document_inspector.environment_clues.length > 0) {
                html += '<div class="mb-3 bg-white border border-gray-200 rounded-md p-4">';
                html += '<h6 class="font-medium text-gray-800 mb-2">Possible Device / Environment Clues</h6>';
                html += '<ul class="list-disc list-inside text-sm text-gray-700 space-y-1">';
                results.document_inspector.environment_clues.forEach(clue => {
                    html += `<li>${escapeHtml(clue)}</li>`;
                });
                html += '</ul>';
                html += '</div>';
            }

            html += '<dl class="space-y-1">';
            Object.entries(results.metadata).forEach(([key, value]) => {
                html += `<div><dt class="inline font-medium">${escapeHtml(formatLabel(key))}:</dt> <dd class="inline ml-2">${escapeHtml(String(value))}</dd></div>`;
            });
            html += '</dl>';
            html += '</div>';
        }

        html += '</div>';
        validationContent.innerHTML = html;
    }

    function formatLabel(value) {
        return String(value)
            .replace(/_/g, ' ')
            .replace(/\b\w/g, char => char.toUpperCase());
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection
