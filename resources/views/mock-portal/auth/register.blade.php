@extends('layouts.auth-rms')

@section('title', 'Mock TASIDO 2026 - Register')

@php
    $mockPortalManual = [
        'manualId' => 'mockPortalRegisterManual',
        'manualTitle' => 'Mock Portal Registration Guide',
        'manualSummary' => 'Follow these steps to create a correct mock-portal account and avoid approval or access problems later.',
        'manualPdf' => '/system_overview.pdf',
        'manualSteps' => [
            ['title' => 'Select the correct role first', 'body' => 'Choose the exact responsibility you hold: Headteacher, DAO, RAO, or Secretariat. Your role controls which dashboard and records you can access.'],
            ['title' => 'Fill the school or area details carefully', 'body' => 'Headteachers must use the correct school centre number, while DAO and RAO users must select the right district and region assignments.'],
            ['title' => 'Use a valid email address', 'body' => 'Provide an email you can access because it may be needed for account recovery, communication, and approval follow-up.'],
            ['title' => 'Create a strong password', 'body' => 'Use the password visibility toggle to confirm what you typed, especially on mobile devices before submitting the form.'],
            ['title' => 'Review before submitting', 'body' => 'Check role, name, school or regional assignment, email, and password once more before clicking Register Account.'],
        ],
        'manualNotes' => [
            '<strong>Important:</strong> Do not register the same person multiple times under different emails unless the system administrator explicitly approved it.',
            '<strong>After registration:</strong> Log in using the same email and password from the mock portal login page.'
        ],
    ];

    $fieldErrorKeys = ['portal_role', 'region_id', 'district_id', 'code', 'ownership', 'name', 'email', 'password'];
    $generalErrors = collect($errors->messages())
        ->except($fieldErrorKeys)
        ->flatten();
@endphp

@section('content')
<div class="login-shell">
    <div class="login-card register-card login-card--compact mock-register-card">
        <div class="login-card-header">
            <div class="login-emblem-wrap">
                <img src="{{ asset('images/vian.png') }}" alt="System login illustration" class="login-emblem">
                <div class="login-stripes" aria-hidden="true">
                    <span style="background:#1eb53a;"></span>
                    <span style="background:#fcd116;"></span>
                    <span style="background:#000000;"></span>
                    <span style="background:#00a3dd;"></span>
                </div>
            </div>
            <h1>STANDARD VII MOCK TASIDO 2026</h1>
            <p>Create your portal account</p>
        </div>

        <div class="login-card-body">
            <form action="{{ route('mock-portal.register.submit') }}" method="POST" novalidate>
                @csrf

                @if ($generalErrors->isNotEmpty())
                    <div class="login-error" role="alert" style="background:#fef2f2; color:#b91c1c; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.85rem;">
                        {{ $generalErrors->first() }}
                    </div>
                @endif

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="portal_role_search" class="form-label">Role</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/register.png') }}" alt="">
                        </span>
                        <input type="hidden" id="portal_role" name="portal_role" value="{{ old('portal_role', '') }}" required>
                        <input
                            id="portal_role_search"
                            class="form-input"
                            data-searchable-select="portal_role_options"
                            placeholder="Click to search and select role"
                            autocomplete="off"
                            value="{{ match(old('portal_role', '')) {
                                'mock_headteacher' => 'Headteacher (Primary School)',
                                'mock_dao' => 'District Academic Officer (DAO)',
                                'mock_rao' => 'Regional Administrative Officer (RAO)',
                                'mock_secretariat' => 'Zonal Secretariat',
                                default => '',
                            } }}"
                            required
                        >
                        <div class="searchable-select-dropdown" id="portal_role_search_dropdown" hidden></div>
                        <datalist id="portal_role_options">
                            <option value="Zonal Secretariat" data-id="mock_secretariat"></option>
                            <option value="Headteacher (Primary School)" data-id="mock_headteacher"></option>
                            <option value="District Academic Officer (DAO)" data-id="mock_dao"></option>
                            <option value="Regional Administrative Officer (RAO)" data-id="mock_rao"></option>
                        </datalist>
                    </div>
                    @error('portal_role')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                    <div
                        id="dao-limit-note"
                        style="display: {{ old('portal_role') === 'mock_dao' ? 'block' : 'none' }}; margin-top: 8px; padding: 10px 12px; border-radius: 10px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(245, 158, 11, 0.28); color: #92400e; font-size: 0.82rem; line-height: 1.45;"
                    >
                        DAO portal access is limited to 5 accounts per district.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;" id="region-field-group">
                    <label for="region_id_search" class="form-label" id="region-label">Region</label>
                    <div class="field-wrap">
                        <input type="hidden" id="region_id" name="region_id" value="{{ old('region_id') }}">
                        <input
                            id="region_id_search"
                            class="form-input"
                            data-searchable-select="region_options"
                            style="padding-left: 12px;"
                            placeholder="Search or select region"
                            autocomplete="off"
                            value="{{ old('region_id') ? optional($regions->firstWhere('id', (int) old('region_id')))->name : '' }}"
                        >
                        <div class="searchable-select-dropdown" id="region_id_search_dropdown" hidden></div>
                        <datalist id="region_options">
                            @foreach($regions as $reg)
                                <option value="{{ $reg->name }}" data-id="{{ $reg->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    @error('region_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 12px; display: none;" id="district-field-group">
                    <label for="district_id_search" class="form-label">District Council</label>
                    <div class="field-wrap">
                        <input type="hidden" id="district_id" name="district_id" value="{{ old('district_id') }}">
                        <input
                            id="district_id_search"
                            class="form-input"
                            data-searchable-select="district_options"
                            style="padding-left: 12px;"
                            placeholder="Search or select district"
                            autocomplete="off"
                            value=""
                        >
                        <div class="searchable-select-dropdown" id="district_id_search_dropdown" hidden></div>
                        <datalist id="district_options"></datalist>
                    </div>
                    @error('district_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 12px;" id="code-field-group">
                    <label for="code" class="form-label" id="code-label">School Centre Number</label>
                    <div class="field-wrap">
                        <input
                            type="text"
                            id="code"
                            name="code"
                            value="{{ old('code') }}"
                            class="form-input @error('code') is-invalid @enderror"
                            placeholder="e.g. PS0301001"
                            style="padding-left: 12px;"
                            oninput="checkSchoolLive(this.value)"
                        >
                    </div>
                    <div id="school-check-status" style="margin-top: 4px; font-size: 0.8rem; display: none;"></div>
                    @error('code')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 12px;" id="school-type-field-group">
                    <label class="form-label">School Type</label>
                    <div class="field-wrap">
                        <input type="text" class="form-input" value="PRIMARY" readonly style="background: #f3f4f6; cursor: not-allowed; padding-left: 12px;">
                        <input type="hidden" name="school_type" value="PRIMARY">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;" id="ownership-field-group">
                    <label for="ownership_search" class="form-label">School Ownership</label>
                    <div class="field-wrap">
                        <input type="hidden" id="ownership" name="ownership" value="{{ old('ownership', 'GOVERNMENT') }}">
                        <input
                            id="ownership_search"
                            class="form-input"
                            data-searchable-select="ownership_options"
                            style="padding-left: 12px;"
                            placeholder="Search or select ownership"
                            autocomplete="off"
                            value="{{ old('ownership', 'GOVERNMENT') }}"
                        >
                        <div class="searchable-select-dropdown" id="ownership_search_dropdown" hidden></div>
                        <datalist id="ownership_options">
                            <option value="GOVERNMENT" data-id="GOVERNMENT"></option>
                            <option value="NON-GOVERNMENT" data-id="NON-GOVERNMENT"></option>
                        </datalist>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/resume.png') }}" alt="">
                        </span>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-input @error('name') is-invalid @enderror"
                            placeholder="Enter your full name"
                            required
                        >
                    </div>
                    @error('name')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/mail.png') }}" alt="">
                        </span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="form-input @error('email') is-invalid @enderror"
                            placeholder="Enter your email"
                            required
                        >
                    </div>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label for="password" class="form-label">Password</label>
                    <div class="field-wrap">
                        <span class="field-icon" aria-hidden="true">
                            <img src="{{ asset('assets/rms-icons/keys.png') }}" alt="">
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input @error('password') is-invalid @enderror"
                            placeholder="Create a password"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePortalRegisterPassword('password')" aria-label="Show or hide password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m3 3 18 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M10.58 10.58a2 2 0 0 0 2.83 2.83" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M9.88 4.24A10.94 10.94 0 0 1 12 4c7 0 10 8 10 8a17.45 17.45 0 0 1-2.16 3.19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M6.71 6.72A17.34 17.34 0 0 0 2 12s3 8 10 8a9.77 9.77 0 0 0 5.29-1.53" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="login-button">Register Account</button>

                <div class="login-meta" style="margin-top: 15px; text-align: center;">
                    <span>Already registered? <a href="{{ route('mock-portal.login') }}" style="color:#00a3dd; font-weight:bold;">Login Here</a></span>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const searchableSelectRegistry = {};

    function togglePortalRegisterPassword(id) {
        const input = document.getElementById(id);
        const btn = input ? input.nextElementSibling : null;

        if (!input || !btn) {
            return;
        }

        if (input.type === 'password') {
            input.type = 'text';
            btn.classList.add('is-visible');
        } else {
            input.type = 'password';
            btn.classList.remove('is-visible');
        }
    }

    function resolveDatalistSelection(inputId, hiddenId, datalistId) {
        const input = document.getElementById(inputId);
        const hiddenInput = document.getElementById(hiddenId);
        const option = Array.from(document.querySelectorAll(`#${datalistId} option`))
            .find(item => item.value === input.value.trim());

        hiddenInput.value = option ? (option.dataset.id || '') : '';

        return option || null;
    }

    function escapeHtml(value) {
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function createSearchableSelect(inputId, hiddenId, datalistId) {
        const input = document.getElementById(inputId);
        const hiddenInput = document.getElementById(hiddenId);
        const datalist = document.getElementById(datalistId);
        const dropdown = document.getElementById(`${inputId}_dropdown`);

        if (!input || !hiddenInput || !datalist || !dropdown) {
            return { refresh() {}, syncSelection() {}, close() {} };
        }

        const getOptions = () => Array.from(datalist.querySelectorAll('option')).map(option => ({
            value: option.value,
            id: option.dataset.id || '',
        }));

        const closeDropdown = () => {
            dropdown.hidden = true;
            input.parentElement.classList.remove('searchable-select-open');
        };

        const openDropdown = () => {
            const options = getOptions();
            if (!options.length) {
                closeDropdown();
                return;
            }

            dropdown.hidden = false;
            input.parentElement.classList.add('searchable-select-open');
        };

        const renderOptions = (query = '') => {
            const normalizedQuery = query.trim().toLowerCase();
            const options = getOptions().filter(option => option.value.toLowerCase().includes(normalizedQuery));

            if (!options.length) {
                dropdown.innerHTML = '<div class="searchable-select-empty">No matches found</div>';
                openDropdown();
                return;
            }

            dropdown.innerHTML = options
                .map(option => `<button type="button" class="searchable-select-option" data-value="${escapeHtml(option.value)}" data-id="${escapeHtml(option.id)}">${escapeHtml(option.value)}</button>`)
                .join('');

            openDropdown();
        };

        dropdown.addEventListener('click', function (event) {
            const option = event.target.closest('.searchable-select-option');
            if (!option) {
                return;
            }

            input.value = option.dataset.value;
            hiddenInput.value = option.dataset.id;
            closeDropdown();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        input.addEventListener('focus', function () {
            renderOptions(input.value);
        });

        input.addEventListener('click', function () {
            renderOptions(input.value);
        });

        input.addEventListener('input', function () {
            resolveDatalistSelection(inputId, hiddenId, datalistId);
            renderOptions(input.value);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDropdown();
            }
        });

        document.addEventListener('click', function (event) {
            if (!input.parentElement.contains(event.target)) {
                closeDropdown();
            }
        });

        const syncSelection = () => {
            const selectedOption = getOptions().find(option => option.value === input.value.trim());
            hiddenInput.value = selectedOption ? selectedOption.id : '';
        };

        const api = {
            refresh(query = input.value) {
                renderOptions(query);
            },
            syncSelection,
            close: closeDropdown,
        };

        searchableSelectRegistry[inputId] = api;
        syncSelection();

        return api;
    }

    function toggleCodeField() {
        const role = document.getElementById('portal_role').value;
        const daoLimitNote = document.getElementById('dao-limit-note');
        const regionGroup = document.getElementById('region-field-group');
        const districtGroup = document.getElementById('district-field-group');
        const codeGroup = document.getElementById('code-field-group');
        const schoolTypeGroup = document.getElementById('school-type-field-group');
        const ownershipGroup = document.getElementById('ownership-field-group');
        const ownershipInput = document.getElementById('ownership');
        const regionInput = document.getElementById('region_id');
        const regionSearchInput = document.getElementById('region_id_search');
        const regionLabel = document.getElementById('region-label');
        const districtInput = document.getElementById('district_id');
        const districtSearchInput = document.getElementById('district_id_search');
        const codeInput = document.getElementById('code');
        const schoolStatus = document.getElementById('school-check-status');

        // Default: hide all contextual fields
        regionGroup.style.display = 'none';
        districtGroup.style.display = 'none';
        codeGroup.style.display = 'none';
        schoolTypeGroup.style.display = 'none';
        ownershipGroup.style.display = 'none';
        daoLimitNote.style.display = role === 'mock_dao' ? 'block' : 'none';
        ownershipInput.disabled = true;
        districtInput.required = false;
        regionInput.required = false;

        if (role === 'mock_secretariat') {
            regionInput.required = false;
        } else if (role === 'mock_rao') {
            // RAO: region only
            regionLabel.textContent = 'Assign Region';
            regionGroup.style.display = 'block';
            regionInput.required = true;
        } else if (role === 'mock_dao') {
            // DAO: region + district
            regionLabel.textContent = 'Region';
            regionGroup.style.display = 'block';
            districtGroup.style.display = 'block';
            districtInput.required = true;
            regionInput.required = true;
            const regId = regionInput.value;
            if (regId) loadDistricts(regId);
        } else {
            // Headteacher: school code + type + ownership
            regionInput.required = false;
            codeGroup.style.display = 'block';
            schoolTypeGroup.style.display = 'block';
            ownershipGroup.style.display = 'block';
            ownershipInput.disabled = false;
        }

        if (!role) {
            regionInput.value = '';
            regionSearchInput.value = '';
            districtInput.value = '';
            districtSearchInput.value = '';
            codeInput.value = '';
            schoolStatus.style.display = 'none';
        }
    }

    function loadDistricts(regionId) {
        const districtInput = document.getElementById('district_id');
        const districtSearchInput = document.getElementById('district_id_search');
        const districtOptions = document.getElementById('district_options');

        if (!regionId) {
            districtOptions.innerHTML = '';
            districtSearchInput.value = '';
            districtInput.value = '';
            searchableSelectRegistry.district_id_search?.close();
            return;
        }

        districtOptions.innerHTML = '';
        districtSearchInput.placeholder = 'Loading districts...';

        const url = "{{ route('mock-portal.districts', ['region' => ':ID']) }}".replace(':ID', regionId);
        fetch(url)
            .then(res => res.json())
            .then(data => {
                districtOptions.innerHTML = '';
                data.forEach(d => {
                    districtOptions.innerHTML += `<option value="${d.name}" data-id="${d.id}"></option>`;
                });

                districtSearchInput.placeholder = 'Search or select district';

                const oldDistrictId = districtInput.value;
                if (oldDistrictId) {
                    const selectedOption = Array.from(districtOptions.querySelectorAll('option'))
                        .find(option => option.dataset.id === String(oldDistrictId));

                    districtSearchInput.value = selectedOption ? selectedOption.value : '';
                }

                searchableSelectRegistry.district_id_search?.syncSelection();
            });
    }

    let checkTimeout;
    function checkSchoolLive(code) {
        const role = document.getElementById('portal_role').value;
        if (role !== 'mock_headteacher') return;
        if (code.length < 5) return;

        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(() => {
            const statusDiv = document.getElementById('school-check-status');
            const ownershipInput = document.getElementById('ownership');
            
            statusDiv.style.display = 'block';
            statusDiv.style.color = '#6b7280';
            statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking school...';

            fetch(`/mock-portal/check-school/${code}`)
                .then(response => response.json())
                .then(data => {
                    if (data.found) {
                        statusDiv.style.color = '#1eb53a';
                        statusDiv.innerHTML = `<i class="fas fa-check-circle"></i> Found: <strong>${data.name}</strong>`;
                        if (data.ownership) {
                            ownershipInput.value = data.ownership;
                            document.getElementById('ownership_search').value = data.ownership;
                            searchableSelectRegistry.ownership_search?.syncSelection();
                        }
                    } else {
                        statusDiv.style.color = '#b91c1c';
                        statusDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${data.message}`;
                    }
                })
                .catch(err => {
                    statusDiv.style.display = 'none';
                });
        }, 500);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleSearchInput = document.getElementById('portal_role_search');
        const regionSearchInput = document.getElementById('region_id_search');
        const districtSearchInput = document.getElementById('district_id_search');
        const ownershipSearchInput = document.getElementById('ownership_search');
        const form = roleSearchInput.closest('form');
        const roleSelect = createSearchableSelect('portal_role_search', 'portal_role', 'portal_role_options');
        const regionSelect = createSearchableSelect('region_id_search', 'region_id', 'region_options');
        const districtSelect = createSearchableSelect('district_id_search', 'district_id', 'district_options');
        const ownershipSelect = createSearchableSelect('ownership_search', 'ownership', 'ownership_options');

        roleSearchInput.addEventListener('input', function () {
            resolveDatalistSelection('portal_role_search', 'portal_role', 'portal_role_options');
            toggleCodeField();
        });

        roleSearchInput.addEventListener('change', function () {
            resolveDatalistSelection('portal_role_search', 'portal_role', 'portal_role_options');
            toggleCodeField();
        });

        regionSearchInput.addEventListener('change', function () {
            const selectedOption = resolveDatalistSelection('region_id_search', 'region_id', 'region_options');
            document.getElementById('district_id').value = '';
            districtSearchInput.value = '';
            loadDistricts(selectedOption ? selectedOption.dataset.id : '');
            toggleCodeField();
        });

        districtSearchInput.addEventListener('change', function () {
            resolveDatalistSelection('district_id_search', 'district_id', 'district_options');
        });

        ownershipSearchInput.addEventListener('change', function () {
            resolveDatalistSelection('ownership_search', 'ownership', 'ownership_options');
        });

        form.addEventListener('submit', function () {
            resolveDatalistSelection('portal_role_search', 'portal_role', 'portal_role_options');
            resolveDatalistSelection('region_id_search', 'region_id', 'region_options');
            resolveDatalistSelection('district_id_search', 'district_id', 'district_options');
            resolveDatalistSelection('ownership_search', 'ownership', 'ownership_options');
        });

        resolveDatalistSelection('portal_role_search', 'portal_role', 'portal_role_options');
        resolveDatalistSelection('region_id_search', 'region_id', 'region_options');
        resolveDatalistSelection('ownership_search', 'ownership', 'ownership_options');
        toggleCodeField();

        if (document.getElementById('region_id').value) {
            loadDistricts(document.getElementById('region_id').value);
        }
    });
</script>
<style>
    .mock-register-card {
        max-height: calc(100vh - 250px);
    }

    .mock-register-card .login-card-body {
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .searchable-select-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        z-index: 30;
        max-height: 220px;
        overflow-y: auto;
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 14px;
        background: rgba(20, 24, 31, 0.96);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.28);
        backdrop-filter: blur(10px);
        padding: 6px;
    }

    .searchable-select-open .form-input {
        border-color: var(--login-blue);
        box-shadow: 0 0 0 3px rgba(14, 76, 140, 0.12);
    }

    .searchable-select-option,
    .searchable-select-empty {
        display: block;
        width: 100%;
        border: 0;
        border-radius: 10px;
        padding: 11px 12px;
        text-align: left;
        font-size: 13px;
        line-height: 1.4;
    }

    .searchable-select-option {
        background: transparent;
        color: #f8fafc;
        cursor: pointer;
        transition: background 0.18s ease, color 0.18s ease;
    }

    .searchable-select-option:hover,
    .searchable-select-option:focus {
        background: rgba(59, 130, 246, 0.22);
        color: #ffffff;
        outline: none;
    }

    .searchable-select-empty {
        color: rgba(226, 232, 240, 0.82);
    }

    @media (max-width: 767px) {
        .mock-register-card {
            max-height: calc(100vh - 155px);
        }

        .mock-register-card .login-card-body {
            overflow-y: auto;
        }

        .searchable-select-dropdown {
            max-height: 180px;
            border-radius: 12px;
        }
    }
</style>
@endsection
