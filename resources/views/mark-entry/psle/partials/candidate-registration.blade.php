@php
    $selectedExamYear = collect($examYears ?? [])->firstWhere('id', (int) ($selectedYearId ?? 0));
    $registrationOpen = ! ($selectedExamYear?->is_locked ?? false);
    $yearLabel = $selectedExamYear?->year_label ?? '2026';
@endphp

<div class="psle-candidate-registration" data-module="psle-candidate-registration">
    <style>
        .cr-page { background:#0b1014; border:1px solid rgba(187,164,94,.1); border-radius:8px; overflow:hidden; box-shadow:0 18px 48px rgba(0,0,0,.28); }
        .cr-flag-bar { display:flex; height:4px; width:100%; }
        .cr-flag-bar span { flex:1; display:block; }
        .cr-hero { background:linear-gradient(135deg,#050e15 0%,#0d1b2a 50%,#0a1520 100%); padding:26px 28px; border-bottom:1px solid rgba(187,164,94,.1); display:flex; align-items:center; justify-content:space-between; gap:24px; }
        .cr-eyebrow { font-size:.66rem; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:var(--tz-yellow); margin-bottom:8px; }
        .cr-title { margin:0 0 6px; font-size:1.7rem; font-weight:900; color:#f0e6c8; letter-spacing:0; line-height:1.2; }
        .cr-subtitle { color:rgba(255,255,255,.58); max-width:720px; margin:0; line-height:1.5; font-size:.9rem; }
        .cr-hero-badge { display:inline-flex; align-items:center; gap:8px; padding:8px 18px; border-radius:20px; font-size:.8rem; font-weight:800; border:1px solid rgba(30,181,58,.25); background:rgba(30,181,58,.12); color:#6ae086; }
        .cr-hero-badge.closed { border-color:rgba(239,68,68,.25); background:rgba(239,68,68,.12); color:#fca5a5; }
        .cr-info-strip { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); background:#080f15; border-bottom:1px solid rgba(255,255,255,.04); }
        .cr-info-card { padding:16px 20px; border-right:1px solid rgba(255,255,255,.04); min-height:82px; transition:.18s ease; }
        .cr-info-card:last-child { border-right:0; }
        .cr-info-card:hover { background:rgba(255,255,255,.03); transform:translateY(-1px); }
        .cr-info-label { font-size:.65rem; font-weight:800; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.35); margin-bottom:7px; }
        .cr-info-value { font-size:1.05rem; font-weight:800; color:#f0e6c8; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .cr-info-sub { margin-top:4px; font-size:.76rem; color:rgba(255,255,255,.42); }
        .cr-body { padding:24px; }
        .cr-window-panel { background:rgba(30,181,58,.08); border:1px solid rgba(30,181,58,.2); border-radius:8px; padding:16px 18px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
        .cr-window-panel.closed { background:rgba(239,68,68,.08); border-color:rgba(239,68,68,.2); }
        .cr-window-title { color:#6ae086; font-weight:800; font-size:.92rem; }
        .cr-window-panel.closed .cr-window-title { color:#fca5a5; }
        .cr-window-note { color:rgba(255,255,255,.48); font-size:.78rem; margin-top:4px; }
        .cr-actions { display:flex; flex-wrap:wrap; gap:10px; }
        .cr-btn { border:0; border-radius:8px; padding:10px 15px; min-height:40px; display:inline-flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; font-size:.84rem; font-weight:800; color:#fff; text-decoration:none; cursor:pointer; background:linear-gradient(135deg,#00a3dd,#006fa3); box-shadow:0 4px 12px rgba(0,163,221,.25); transition:.18s ease; }
        .cr-btn:hover { transform:translateY(-1px); box-shadow:0 8px 18px rgba(0,163,221,.32); }
        .cr-btn.secondary { background:rgba(255,255,255,.05); color:#dbeafe; border:1px solid rgba(255,255,255,.1); box-shadow:none; }
        .cr-btn.danger { background:rgba(239,68,68,.16); color:#fecaca; border:1px solid rgba(239,68,68,.28); box-shadow:none; }
        .cr-btn:disabled { opacity:.48; cursor:not-allowed; transform:none; box-shadow:none; }
        .cr-alert { display:none; margin:0 0 18px; border-radius:8px; padding:12px 14px; font-weight:700; font-size:.86rem; }
        .cr-alert.show { display:block; }
        .cr-alert.success { background:rgba(34,197,94,.12); color:#bbf7d0; border:1px solid rgba(34,197,94,.24); }
        .cr-alert.error { background:rgba(239,68,68,.12); color:#fecaca; border:1px solid rgba(239,68,68,.24); }
        .cr-section-label { color:var(--tz-blue); font-size:.68rem; font-weight:900; letter-spacing:.12em; text-transform:uppercase; display:flex; align-items:center; gap:8px; margin:0 0 14px; }
        .cr-filter-card, .cr-panel { background:linear-gradient(135deg,#0d1b2a,#111e29); border:1px solid rgba(0,163,221,.16); border-radius:8px; margin-bottom:18px; overflow:hidden; }
        .cr-panel-head { padding:16px 18px; border-bottom:1px solid rgba(255,255,255,.06); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
        .cr-panel-title { color:#f0e6c8; font-size:1rem; font-weight:900; }
        .cr-panel-body { padding:18px; }
        .cr-filter-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:12px; align-items:end; }
        .cr-field label { display:block; color:rgba(255,255,255,.48); font-size:.66rem; text-transform:uppercase; letter-spacing:.08em; font-weight:900; margin-bottom:7px; }
        .cr-input, .cr-select { width:100%; min-height:42px; border-radius:8px; border:1px solid rgba(148,163,184,.22); background:rgba(255,255,255,.06); color:#fff; padding:10px 12px; outline:none; font-family:inherit; }
        .cr-select option { color:#0f172a; }
        .cr-input:disabled, .cr-select:disabled { opacity:.48; cursor:not-allowed; }
        .cr-school-results { display:none; position:relative; margin-top:8px; border:1px solid rgba(148,163,184,.14); border-radius:8px; overflow:hidden; }
        .cr-school-results.show { display:block; }
        .cr-school-option { width:100%; text-align:left; padding:10px 12px; background:#0f172a; border:0; border-bottom:1px solid rgba(148,163,184,.1); color:#dbeafe; cursor:pointer; }
        .cr-school-option:hover { background:rgba(14,165,233,.16); }
        .cr-school-card { border:1px solid rgba(0,163,221,.22); border-radius:8px; padding:18px; display:flex; align-items:center; justify-content:space-between; gap:18px; background:linear-gradient(135deg,#0d1b2a,#111e29); margin-bottom:18px; }
        .cr-school-icon { width:52px; height:52px; border-radius:8px; background:linear-gradient(135deg,rgba(0,163,221,.2),rgba(0,163,221,.05)); border:1px solid rgba(0,163,221,.25); display:flex; align-items:center; justify-content:center; color:#67d8ff; font-size:1.35rem; flex:0 0 auto; }
        .cr-school-main { flex:1; min-width:0; }
        .cr-school-code { color:var(--tz-blue); font-size:.68rem; font-weight:900; letter-spacing:.1em; text-transform:uppercase; margin-bottom:4px; }
        .cr-school-name { color:#f0e6c8; font-size:1.12rem; font-weight:900; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .cr-school-meta { color:rgba(255,255,255,.48); font-size:.8rem; margin-top:5px; }
        .cr-school-arrow { color:rgba(255,255,255,.28); font-size:1.15rem; }
        .cr-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
        .cr-tab { border:1px solid rgba(148,163,184,.2); color:#cbd5e1; background:rgba(255,255,255,.05); border-radius:8px; padding:10px 14px; font-weight:900; cursor:pointer; font-family:inherit; }
        .cr-tab.active { background:rgba(0,163,221,.22); border-color:rgba(0,163,221,.5); color:#fff; }
        .cr-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
        .cr-table-wrap { overflow:auto; }
        .cr-table { width:100%; border-collapse:collapse; min-width:980px; }
        .cr-table th, .cr-table td { padding:12px 14px; border-bottom:1px solid rgba(255,255,255,.05); text-align:left; color:#dbeafe; font-size:.84rem; }
        .cr-table th { color:#facc15; font-size:.66rem; text-transform:uppercase; letter-spacing:.08em; background:rgba(2,6,23,.36); white-space:nowrap; }
        .cr-table tr:hover td { background:rgba(0,163,221,.05); }
        .cr-status { display:inline-flex; align-items:center; gap:6px; border-radius:20px; padding:4px 10px; font-size:.7rem; font-weight:900; color:#6ae086; background:rgba(30,181,58,.12); border:1px solid rgba(30,181,58,.24); }
        .cr-row-actions { display:flex; gap:8px; flex-wrap:wrap; }
        .cr-empty { text-align:center; padding:44px 20px; color:rgba(255,255,255,.5); }
        .cr-empty i { display:block; color:rgba(255,255,255,.12); font-size:2.6rem; margin-bottom:14px; }
        .cr-empty-title { color:#f0e6c8; font-weight:900; font-size:1.08rem; margin-bottom:8px; }
        .cr-empty-actions { display:flex; justify-content:center; gap:10px; flex-wrap:wrap; margin-top:18px; }
        .cr-upload { min-height:172px; border:2px dashed rgba(148,163,184,.28); border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:10px; background:rgba(255,255,255,.03); color:#94a3b8; cursor:pointer; padding:26px; }
        .cr-upload.dragging { border-color:#38bdf8; background:rgba(14,165,233,.08); }
        .registration-modal-overlay { position:fixed; inset:0; z-index:9998; display:none; align-items:center; justify-content:center; background:rgba(2,6,23,.72); padding:clamp(8px,2vw,16px); }
        .registration-modal-overlay.show { display:flex; }
        .registration-modal-shell { width:min(1040px,100%); max-height:calc(100vh - clamp(16px,4vw,32px)); overflow:hidden; background:#101923; border:1px solid rgba(255,255,255,.1); border-radius:8px; box-shadow:0 28px 70px rgba(0,0,0,.38); display:flex; flex-direction:column; }
        .registration-modal-header { background:linear-gradient(135deg,#0f172a,#0b3b61,#0f5f4a); padding:clamp(16px,2.4vw,22px) clamp(16px,2.6vw,24px); color:#fff; flex:0 0 auto; }
        .registration-modal-header-content { display:flex; justify-content:space-between; gap:18px; align-items:flex-start; }
        .registration-modal-kicker { font-size:.68rem; font-weight:900; letter-spacing:.14em; text-transform:uppercase; color:#facc15; }
        .registration-modal-title { margin:10px 0 0; color:#fff; font-size:clamp(1.12rem,2.5vw,1.4rem); font-weight:900; line-height:1.2; }
        .registration-modal-subtitle { margin:8px 0 0; color:rgba(255,255,255,.76); max-width:690px; line-height:1.55; font-size:.9rem; }
        .registration-modal-close { width:38px; height:38px; min-width:38px; border-radius:8px; border:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.1); color:#fff; font-size:1.35rem; cursor:pointer; }
        .registration-modal-body { padding:clamp(14px,2.4vw,22px); overflow:auto; flex:1 1 auto; min-height:0; background:#f8fafc; color:#334155; }
        .registration-modal-note { display:flex; gap:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 16px; color:#1e3a8a; margin-bottom:16px; }
        .registration-modal-panel { border:1px solid #e2e8f0; background:#fff; border-radius:8px; padding:16px; margin-top:16px; }
        .registration-modal-actions { display:flex; justify-content:flex-end; gap:10px; padding:clamp(12px,2vw,16px) clamp(14px,2.4vw,22px); border-top:1px solid #e2e8f0; background:#fff; flex:0 0 auto; flex-wrap:wrap; }
        .registration-modal-button { border:0; border-radius:8px; padding:11px 15px; font-weight:900; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .registration-modal-button:disabled { opacity:.55; cursor:not-allowed; }
        .registration-modal-button-secondary { background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; }
        .registration-modal-button-primary { background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff; }
        .registration-modal-button-success { background:linear-gradient(135deg,#059669,#047857); color:#fff; }
        .registration-modal-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
        .registration-modal-stat { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; }
        .registration-modal-stat-label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; font-weight:900; color:#64748b; margin:0 0 5px; }
        .registration-modal-stat-value { font-size:1.45rem; font-weight:900; margin:0; color:#0f172a; }
        .cr-candidate-modal { position:fixed; inset:0; z-index:9999; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.85); padding:clamp(12px,3vw,20px); }
        .cr-candidate-modal.show { display:flex; }
        .cr-candidate-modal-shell { background:#101518; border:1px solid rgba(255,255,255,.1); border-radius:16px; width:min(460px,100%); max-height:calc(100vh - clamp(24px,6vw,40px)); overflow:hidden; box-shadow:0 24px 60px rgba(0,0,0,.45); display:flex; flex-direction:column; }
        .cr-candidate-modal-header { padding:20px; border-bottom:1px solid rgba(255,255,255,.06); display:flex; justify-content:space-between; align-items:center; gap:12px; flex:0 0 auto; }
        .cr-candidate-modal-title { margin:0; font-size:1.1rem; color:#f0e6c8; font-weight:900; line-height:1.25; }
        .cr-candidate-modal-close { background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem; width:36px; height:36px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; flex:0 0 auto; }
        .cr-candidate-modal-close:hover { background:rgba(255,255,255,.06); color:#f8fafc; }
        .cr-candidate-modal-form { padding:24px; overflow:auto; flex:1 1 auto; min-height:0; }
        .cr-candidate-modal-actions { padding:16px 24px 20px; display:flex; justify-content:flex-end; gap:12px; background:rgba(255,255,255,.02); border-top:1px solid rgba(255,255,255,.06); flex:0 0 auto; }
        .cr-candidate-modal-cancel { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.1); color:#d1d5db; padding:10px 18px; border-radius:8px; font-weight:800; cursor:pointer; }
        .cr-candidate-modal-cancel:hover { background:rgba(255,255,255,.1); }
        .cr-candidate-modal .btn-loading { display:none; align-items:center; gap:8px; }
        @media (max-width:1100px) { .cr-info-strip, .cr-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
        @media (max-width:720px) { .cr-hero { align-items:flex-start; flex-direction:column; } .cr-body { padding:18px; } .cr-info-strip, .cr-filter-grid, .cr-form-grid, .registration-modal-stats { grid-template-columns:1fr; } .cr-title { font-size:1.45rem; } .cr-window-panel, .cr-panel-head { align-items:stretch; } .cr-actions, .cr-window-panel .cr-actions { width:100%; } .cr-actions .cr-btn { flex:1 1 180px; } .cr-school-card { align-items:flex-start; } .cr-school-name { white-space:normal; } .registration-modal-overlay { align-items:stretch; } .registration-modal-shell { max-height:calc(100vh - 16px); } .registration-modal-header-content { gap:12px; } .registration-modal-note { padding:12px; } .registration-modal-actions { justify-content:stretch; } .registration-modal-actions button { flex:1 1 140px; } }
        @media (max-width:520px) { .registration-modal-header-content { align-items:flex-start; } .registration-modal-subtitle { font-size:.82rem; } #crModalDownloadTemplate { width:100%; justify-content:center; } #crDropzone { min-height:150px; padding:18px 12px; } #crDropzone span { overflow-wrap:anywhere; } .registration-modal-panel label { align-items:flex-start; } .cr-candidate-modal { align-items:stretch; } .cr-candidate-modal-shell { width:100%; max-height:calc(100vh - 24px); } .cr-candidate-modal-actions { flex-direction:column-reverse; } .cr-candidate-modal-actions button { width:100%; justify-content:center; } }
    </style>

    <div class="cr-page">
        <div class="cr-flag-bar" aria-hidden="true">
            <span style="background:#1eb53a"></span><span style="background:#fcd116"></span><span style="background:#000"></span><span style="background:#00a3dd"></span>
        </div>

        <section class="cr-hero">
            <div>
                <div class="cr-eyebrow">STANDARD VII PSLE CANDIDATE REGISTRATION</div>
                <h1 class="cr-title">PSLE {{ $yearLabel }} Candidate Registration</h1>
                <p class="cr-subtitle">Register, validate, and manage PSLE pupils using the same registration path as the PSLE Pupil Register.</p>
            </div>
            <span class="cr-hero-badge {{ $registrationOpen ? '' : 'closed' }}">
                <i class="fas fa-circle" style="font-size:.48rem;"></i>
                {{ $registrationOpen ? 'Registration Window Open' : 'Registration Window Closed' }}
            </span>
        </section>

        <section class="cr-info-strip">
            <div class="cr-info-card"><div class="cr-info-label">Centre Number</div><div class="cr-info-value" id="crInfoCentre">Select School</div><div class="cr-info-sub">Selected PSLE centre</div></div>
            <div class="cr-info-card"><div class="cr-info-label">Region</div><div class="cr-info-value" id="crInfoRegion">-</div><div class="cr-info-sub">Administrative region</div></div>
            <div class="cr-info-card"><div class="cr-info-label">District</div><div class="cr-info-value" id="crInfoDistrict">-</div><div class="cr-info-sub">Council or district scope</div></div>
            <div class="cr-info-card"><div class="cr-info-label">Status</div><div class="cr-info-value" id="crInfoStatus" style="color:#6ae086;">{{ $registrationOpen ? 'Open' : 'Closed' }}</div><div class="cr-info-sub">Exam year registration state</div></div>
        </section>

        <div class="cr-body">
            <div id="crAlert" class="cr-alert"></div>

            <div class="cr-window-panel {{ $registrationOpen ? '' : 'closed' }}">
                <div>
                    <div class="cr-window-title"><i class="fas fa-clock"></i> Time Remaining / Deadline</div>
                    <div class="cr-window-note">{{ $registrationOpen ? 'Use the actions below to add or upload candidates for your assigned PSLE school scope.' : 'This exam year is locked. Registration actions may be rejected by the server.' }}</div>
                </div>
                <div class="cr-actions">
                    <button type="button" class="cr-btn" id="crOpenImport"><i class="fas fa-upload"></i> Upload Candidates</button>
                    <button type="button" class="cr-btn secondary" id="crOpenAddCandidate"><i class="fas fa-plus"></i> Add Single Candidate</button>
                    <button type="button" class="cr-btn secondary" id="crDownloadTemplate"><i class="fas fa-download"></i> Download Template</button>
                </div>
            </div>

            <div class="cr-section-label"><i class="fas fa-filter"></i> School Scope</div>
            <div class="cr-filter-card">
                <div class="cr-panel-body">
                    <div class="cr-filter-grid">
                        <div class="cr-field">
                            <label>Exam Year</label>
                            <select id="crExamYear" class="cr-select">
                                @foreach($examYears as $year)
                                    <option value="{{ $year->id }}" {{ (int) $selectedYearId === (int) $year->id ? 'selected' : '' }}>{{ $year->year_label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="cr-field"><label>Region</label><select id="crRegion" class="cr-select"><option value="">Select Region</option></select></div>
                        <div class="cr-field"><label>District/Council</label><select id="crCouncil" class="cr-select" disabled><option value="">Select District/Council</option></select></div>
                        <div class="cr-field">
                            <label>School Filter/Search</label>
                            <input id="crSchoolSearch" class="cr-input" type="text" placeholder="Centre number or school name" disabled>
                            <input id="crSchoolId" type="hidden">
                            <div id="crSchoolResults" class="cr-school-results"></div>
                        </div>
                        <div class="cr-field"><label>Search Candidate</label><input id="crCandidateSearch" class="cr-input" type="text" placeholder="Index number, PReM, name"></div>
                    </div>
                </div>
            </div>

            <div class="cr-section-label"><i class="fas fa-school"></i> Registered School / Selected School</div>
            <div class="cr-school-card" id="crSelectedSchoolCard">
                <div class="cr-school-icon"><i class="fas fa-school"></i></div>
                <div class="cr-school-main">
                    <div class="cr-school-code" id="crSchoolCode">No school selected</div>
                    <div class="cr-school-name" id="crSchoolName">Select an assigned school to manage candidates</div>
                    <div class="cr-school-meta" id="crSchoolMeta">Use the school search above. Officers only see schools inside their assigned scope.</div>
                </div>
                <div class="cr-school-arrow"><i class="fas fa-arrow-right"></i></div>
            </div>

            <div class="cr-tabs">
                <button type="button" class="cr-tab active" data-tab="list">Candidate List</button>
                <button type="button" class="cr-tab" data-tab="bulk">Bulk Upload</button>
            </div>

            <div class="cr-panel cr-panel-tab" data-panel="list">
                <div class="cr-panel-head">
                    <div class="cr-panel-title">Registered Candidates</div>
                    <div class="cr-actions">
                        <button type="button" class="cr-btn secondary" id="crExport"><i class="fas fa-file-export"></i> Export</button>
                        <button type="button" class="cr-btn secondary" id="crRefresh"><i class="fas fa-rotate"></i> Refresh</button>
                    </div>
                </div>
                <div class="cr-table-wrap">
                    <table class="cr-table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Index Number</th>
                                <th>PReM No</th>
                                <th>Candidate Full Name</th>
                                <th>Sex</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="crCandidateRows">
                            <tr><td colspan="7"><div class="cr-empty"><i class="fas fa-user-graduate"></i><div class="cr-empty-title">Select a school to view candidates.</div></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="cr-panel cr-panel-tab" data-panel="bulk" style="display:none;">
                <div class="cr-panel-head">
                    <div class="cr-panel-title">Bulk Candidate Upload</div>
                    <button type="button" class="cr-btn" id="crOpenImportPanel"><i class="fas fa-upload"></i> Upload File</button>
                </div>
                <div class="cr-panel-body">
                    <div class="cr-upload" id="crInlineUpload">
                        <i class="fas fa-cloud-arrow-up" style="font-size:2.1rem; color:#67d8ff;"></i>
                        <strong style="color:#f0e6c8;">Validate candidates before import</strong>
                        <span>The import template contains only candidate registration fields. It does not include Status or Remarks.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="crSingleCandidateModal" class="cr-candidate-modal" role="dialog" aria-modal="true" aria-labelledby="crFormTitle">
        <div class="cr-candidate-modal-shell">
            <div class="cr-candidate-modal-header">
                <h3 class="cr-candidate-modal-title" id="crFormTitle">Register New Candidate</h3>
                <button type="button" class="cr-candidate-modal-close" id="crCloseSingleCandidate" aria-label="Close candidate form"><i class="fas fa-times"></i></button>
            </div>
            <form id="crSingleForm">
                <div class="cr-candidate-modal-form">
                    <input type="hidden" id="crEditId">
                    <div class="cr-form-grid">
                        <div class="cr-field">
                            <label>Centre Number</label>
                            <input id="crCentreNumber" class="cr-input" readonly placeholder="Select school first">
                        </div>
                        <div class="cr-field">
                            <label>Index Number</label>
                            <input id="crCandidateId" class="cr-input" required placeholder="PS0404006-0001">
                        </div>
                        <div class="cr-field">
                            <label>Full Name</label>
                            <input id="crFullName" class="cr-input" required>
                        </div>
                        <div class="cr-field">
                            <label>Sex</label>
                            <select id="crGender" class="cr-select" required>
                                <option value="">Select</option>
                                <option value="M">M</option>
                                <option value="F">F</option>
                            </select>
                        </div>
                        <div class="cr-field" style="grid-column:1 / -1;">
                            <label>PReM Number</label>
                            <input id="crPremNo" class="cr-input" required maxlength="11" pattern="[0-9]{11}" title="Format: 11 digits (e.g. 20261234567)" placeholder="e.g. 20261234567">
                        </div>
                    </div>
                </div>
                <div class="cr-candidate-modal-actions">
                    <button type="button" class="cr-candidate-modal-cancel" id="crCancelSingleCandidate">Cancel</button>
                    <button type="submit" class="cr-btn" id="crSaveCandidate" disabled>
                        <span class="btn-text"><i class="fas fa-save"></i> Register Candidate</span>
                        <span class="btn-loading"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="crPupilImportModal" class="registration-modal-overlay">
        <div class="registration-modal-shell">
            <div class="registration-modal-header">
                <div class="registration-modal-header-content">
                    <div>
                        <div class="registration-modal-kicker"><i class="fas fa-file-import"></i> PSLE Candidate Upload</div>
                        <h2 class="registration-modal-title">Upload Candidates</h2>
                        <p class="registration-modal-subtitle">Download the PSLE pupil template, validate row-level errors and duplicates, then import only approved PSLE candidate records.</p>
                    </div>
                    <button class="registration-modal-close" id="crCloseImport" type="button">&times;</button>
                </div>
            </div>
            <div class="registration-modal-body">
                <div id="crImportUploadPhase" style="display:block;">
                    <div class="registration-modal-note">
                        <i class="fas fa-circle-info" style="margin-top:3px;"></i>
                        <div><strong>Required columns:</strong> candidate_number, PReM_No, pupil_name, sex, school_code. Status and Remarks are not part of this template.</div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
                        <button type="button" id="crModalDownloadTemplate" class="registration-modal-button registration-modal-button-success"><i class="fas fa-download"></i> Download Template</button>
                    </div>
                    <div id="crDropzone" class="cr-upload" style="background:#fff; color:#64748b;">
                        <input type="file" id="crFile" accept=".csv,.xlsx,.xls,text/csv" hidden>
                        <i class="fas fa-cloud-arrow-up" style="font-size:2.2rem; color:#2563eb;"></i>
                        <strong style="color:#334155;">Drop candidate CSV, XLSX, or XLS here or click to select</strong>
                        <span>Example: PS0404006-0001, 20201520092, ASHERI JOSHUA CHAULA, M, PS0404006</span>
                    </div>
                    <div id="crFileName" class="registration-modal-panel" style="display:none;"></div>
                    <div class="registration-modal-panel">
                        <p style="font-size:.9rem; font-weight:900; color:#334155; margin:0 0 10px;">If candidate already exists:</p>
                        <div style="display:grid; gap:10px;">
                            <label style="display:flex; gap:12px; cursor:pointer;"><input type="radio" name="crDuplicateMode" value="stop" checked><span><strong>Stop import when duplicates exist</strong><span style="display:block; color:#64748b; font-size:.78rem;">Shows clear row-level errors without changing existing candidates</span></span></label>
                            <label style="display:flex; gap:12px; cursor:pointer;"><input type="radio" name="crDuplicateMode" value="skip"><span><strong>Skip existing candidates</strong><span style="display:block; color:#64748b; font-size:.78rem;">Import new candidates only</span></span></label>
                        </div>
                    </div>
                </div>
                <div id="crImportReportPhase" style="display:none;">
                    <h3 style="margin:0 0 14px; color:#0f172a;">Preview Records</h3>
                    <div class="registration-modal-stats">
                        <div class="registration-modal-stat"><p class="registration-modal-stat-label">Total Rows</p><p class="registration-modal-stat-value" id="crTotalRows">0</p></div>
                        <div class="registration-modal-stat"><p class="registration-modal-stat-label">Valid</p><p class="registration-modal-stat-value" id="crModalValidRows">0</p></div>
                        <div class="registration-modal-stat"><p class="registration-modal-stat-label">Existing</p><p class="registration-modal-stat-value" id="crExistingRows">0</p></div>
                        <div class="registration-modal-stat"><p class="registration-modal-stat-label">Errors</p><p class="registration-modal-stat-value" id="crModalInvalidRows">0</p></div>
                    </div>
                    <div class="registration-modal-panel" style="overflow:auto;">
                        <table class="cr-table" style="min-width:1040px;">
                            <thead><tr><th>Row</th><th>Index Number</th><th>PReM No</th><th>Candidate Name</th><th>Sex</th><th>School Code</th><th>Status</th><th>Message</th></tr></thead>
                            <tbody id="crPreviewRows"></tbody>
                        </table>
                    </div>
                </div>
                <div id="crImportProcessingPhase" style="display:none; text-align:center; padding:48px 0;">
                    <i class="fas fa-spinner fa-spin" style="font-size:2.5rem; color:#2563eb;"></i>
                    <p id="crImportProcessingText" style="font-weight:900; margin-top:14px;">Processing...</p>
                </div>
            </div>
            <div class="registration-modal-actions">
                <button type="button" id="crImportCloseFooter" class="registration-modal-button registration-modal-button-secondary">Close</button>
                <button type="button" id="crPreview" class="registration-modal-button registration-modal-button-primary" disabled>Validate File</button>
                <button type="button" id="crImportBack" class="registration-modal-button registration-modal-button-secondary" style="display:none;">Back</button>
                <button type="button" id="crImport" class="registration-modal-button registration-modal-button-primary" style="display:none;" disabled>Import Valid Records</button>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const root = document.querySelector('[data-module="psle-candidate-registration"]');
    if (!root) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const endpoints = {
        regions: '{{ route('mark-entry.psle.candidates.filters.regions') }}',
        councils: '{{ route('mark-entry.psle.candidates.filters.councils') }}',
        schools: '{{ route('mark-entry.psle.candidates.filters.schools') }}',
        list: '{{ route('mark-entry.psle.candidates.list') }}',
        store: '{{ url('/api/candidates') }}',
        template: '{{ url('/api/candidates/import/template') }}',
        preview: '{{ url('/api/candidates/import/validate') }}',
        import: '{{ url('/api/candidates/import/commit') }}',
        update: id => `{{ url('/api/candidates') }}/${id}`,
        destroy: id => `{{ url('/api/candidates') }}/${id}`,
    };

    const el = id => document.getElementById(id);
    const state = { file: null, previewValid: 0, searchTimer: null, selectedSchool: null };

    function params(extra = {}) {
        const query = {
            exam_year_id: el('crExamYear').value,
            region_id: el('crRegion').value,
            council_id: el('crCouncil').value,
            school_id: el('crSchoolId').value,
            ...extra,
        };
        Object.keys(query).forEach(key => (query[key] === '' || query[key] == null) && delete query[key]);
        return new URLSearchParams(query);
    }

    function selectedExamYearLabel() {
        const option = el('crExamYear').selectedOptions[0];
        return option ? option.textContent.trim() : '{{ $yearLabel }}';
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    }

    function showAlert(message, type = 'success') {
        const box = el('crAlert');
        box.className = `cr-alert show ${type}`;
        box.textContent = message;
        setTimeout(() => box.classList.remove('show'), 6500);
    }

    async function getJson(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const json = await readJson(response);
        if (!response.ok || json.success === false) throw new Error(json.message || 'Request failed.');
        return json.data;
    }

    async function readJson(response) {
        const text = await response.text();
        try {
            return text ? JSON.parse(text) : {};
        } catch (error) {
            return { success: false, message: response.ok ? 'Unexpected server response.' : `Request failed with status ${response.status}.` };
        }
    }

    async function sendJson(url, method, payload) {
        const response = await fetch(url, {
            method,
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        const json = await readJson(response);
        if (!response.ok || json.success === false) throw new Error(json.message || 'Request failed.');
        return json;
    }

    function fillSelect(select, items, placeholder) {
        select.innerHTML = `<option value="">${placeholder}</option>` + items.map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
    }

    function selectedSchoolMeta(school) {
        const region = school?.region?.name || school?.council?.region?.name || '-';
        const district = school?.district?.name || school?.council?.name || '-';
        const type = school?.school_type || school?.education_level || 'Primary School';
        return { region, district, type };
    }

    function setSelectedSchool(school) {
        state.selectedSchool = school || null;
        el('crSchoolId').value = school?.id || '';
        el('crSchoolSearch').value = school ? `${school.code} - ${school.name}` : '';
        el('crCentreNumber').value = school?.code || '';
        const meta = selectedSchoolMeta(school);
        el('crInfoCentre').textContent = school?.code || 'Select School';
        el('crInfoRegion').textContent = school ? meta.region : '-';
        el('crInfoDistrict').textContent = school ? meta.district : '-';
        el('crSchoolCode').textContent = school ? `Centre No. ${school.code}` : 'No school selected';
        el('crSchoolName').textContent = school?.name || 'Select an assigned school to manage candidates';
        el('crSchoolMeta').textContent = school ? `${meta.region} - ${meta.district} - ${meta.type}` : 'Use the school search above. Officers only see schools inside their assigned scope.';
        setActionStates();
    }

    function resetSchool() {
        setSelectedSchool(null);
        el('crSchoolSearch').disabled = !el('crRegion').value && !el('crCouncil').value;
        el('crSchoolResults').classList.remove('show');
    }

    async function loadRegions() {
        const items = await getJson(`${endpoints.regions}?${params()}`);
        fillSelect(el('crRegion'), items, 'Select Region');
        if (items.length === 1) el('crRegion').value = items[0].id;
        await loadCouncils();
    }

    async function loadCouncils() {
        el('crCouncil').disabled = !el('crRegion').value;
        fillSelect(el('crCouncil'), [], 'Select District/Council');
        resetSchool();
        if (!el('crRegion').value) return loadList();
        const items = await getJson(`${endpoints.councils}?${params()}`);
        fillSelect(el('crCouncil'), items, 'Select District/Council');
        if (items.length === 1) el('crCouncil').value = items[0].id;
        el('crSchoolSearch').disabled = !el('crRegion').value && !el('crCouncil').value;
        await maybeAutoSelectSchool();
        loadList();
    }

    async function maybeAutoSelectSchool() {
        if (!el('crRegion').value && !el('crCouncil').value) return;
        const items = await getJson(`${endpoints.schools}?${params({ q: '' })}`);
        if (items.length === 1) {
            setSelectedSchool(items[0]);
        }
    }

    async function searchSchools() {
        const q = el('crSchoolSearch').value.trim();
        if (!el('crRegion').value && !el('crCouncil').value) return;
        if (q.length > 0 && q.length < 2) return;
        const items = await getJson(`${endpoints.schools}?${params({ q })}`);
        el('crSchoolResults').innerHTML = items.length
            ? items.map(school => `<button type="button" class="cr-school-option" data-school="${escapeHtml(JSON.stringify(school))}">${escapeHtml(`${school.code} - ${school.name}`)}</button>`).join('')
            : '<div class="cr-school-option">No matching assigned schools found.</div>';
        el('crSchoolResults').classList.add('show');
    }

    async function loadList() {
        const data = await getJson(`${endpoints.list}?${params({ q: el('crCandidateSearch').value, per_page: 100 })}`);
        if (!el('crSchoolId').value) {
            el('crCandidateRows').innerHTML = selectSchoolEmptyRow();
            return;
        }
        const rows = data.items || [];
        el('crCandidateRows').innerHTML = rows.length ? rows.map(candidateRow).join('') : emptyCandidateRow();
    }

    function candidateRow(candidate, index) {
        return `<tr>
            <td>${index + 1}</td>
            <td>${escapeHtml(candidate.candidate_id || '')}</td>
            <td>${escapeHtml(candidate.prem_no || '')}</td>
            <td>${escapeHtml(candidate.full_name || '')}</td>
            <td>${escapeHtml(candidate.gender || '')}</td>
            <td><span class="cr-status"><i class="fas fa-circle" style="font-size:.42rem;"></i>${escapeHtml(candidate.status || 'registered')}</span></td>
            <td><div class="cr-row-actions">
                <button type="button" class="cr-btn secondary" data-edit='${escapeHtml(JSON.stringify(candidate))}'>Edit</button>
                ${candidate.can_delete ? `<button type="button" class="cr-btn danger" data-delete="${candidate.id}">Delete</button>` : ''}
            </div></td>
        </tr>`;
    }

    function emptyCandidateRow() {
        return `<tr><td colspan="7"><div class="cr-empty">
            <i class="fas fa-user-graduate"></i>
            <div class="cr-empty-title">No candidates registered for this school yet.</div>
            <div>Start with a single candidate or upload a validated import file.</div>
            <div class="cr-empty-actions">
                <button type="button" class="cr-btn" data-empty-add><i class="fas fa-plus"></i> Add Single Candidate</button>
                <button type="button" class="cr-btn secondary" data-empty-upload><i class="fas fa-upload"></i> Upload Candidates</button>
            </div>
        </div></td></tr>`;
    }

    function selectSchoolEmptyRow() {
        return `<tr><td colspan="7"><div class="cr-empty">
            <i class="fas fa-school"></i>
            <div class="cr-empty-title">Select a school to manage candidate registration.</div>
            <div>Use the school filter/search above to choose an assigned PSLE centre.</div>
        </div></td></tr>`;
    }

    function setActionStates() {
        const hasSchool = !!el('crSchoolId').value;
        el('crSaveCandidate').disabled = !hasSchool;
        el('crPreview').disabled = !state.file;
        el('crImport').disabled = !state.file || state.previewValid < 1;
    }

    function formPayload() {
        return {
            school_id: el('crSchoolId').value,
            candidate_id: el('crCandidateId').value,
            prem_no: el('crPremNo').value,
            full_name: el('crFullName').value,
            gender: el('crGender').value,
            exam_type: 'PSLE',
            combination: null,
        };
    }

    function clearForm() {
        el('crEditId').value = '';
        el('crFormTitle').textContent = 'Register New Candidate';
        const text = el('crSaveCandidate').querySelector('.btn-text');
        if (text) text.innerHTML = '<i class="fas fa-save"></i> Register Candidate';
        ['crCandidateId', 'crPremNo', 'crFullName'].forEach(id => el(id).value = '');
        el('crGender').value = '';
    }

    async function saveCandidate(event) {
        event.preventDefault();
        if (!el('crSchoolId').value) return showAlert('Please select a school before registering a candidate.', 'error');
        setSaveLoading(true);
        const editId = el('crEditId').value;
        try {
            const json = await sendJson(editId ? endpoints.update(editId) : endpoints.store, editId ? 'PUT' : 'POST', formPayload());
            showAlert(json.message || 'Candidate saved successfully.');
            closeSingleModal();
            await loadList();
            showPanel('list');
        } finally {
            setSaveLoading(false);
        }
    }

    function showPanel(name) {
        document.querySelectorAll('.cr-panel-tab').forEach(panel => panel.style.display = panel.dataset.panel === name ? '' : 'none');
        document.querySelectorAll('.cr-tab').forEach(tab => tab.classList.toggle('active', tab.dataset.tab === name));
    }

    function setSaveLoading(isLoading) {
        const button = el('crSaveCandidate');
        const text = button.querySelector('.btn-text');
        const loading = button.querySelector('.btn-loading');
        if (text && loading) {
            text.style.display = isLoading ? 'none' : 'inline-flex';
            loading.style.display = isLoading ? 'inline-flex' : 'none';
        }
        button.disabled = isLoading || !el('crSchoolId').value;
        button.style.opacity = isLoading ? '.7' : '';
        button.style.cursor = isLoading ? 'not-allowed' : '';
    }

    function openSingleModal() {
        if (!el('crSchoolId').value) return showAlert('Please select a school first.', 'error');
        el('crSingleCandidateModal').classList.add('show');
        setActionStates();
        setTimeout(() => el('crCandidateId').focus(), 40);
    }

    function closeSingleModal() {
        el('crSingleCandidateModal').classList.remove('show');
        clearForm();
        setSaveLoading(false);
    }

    function openEditModal(candidate) {
        el('crEditId').value = candidate.id;
        el('crFormTitle').textContent = 'Edit Candidate Registration';
        const text = el('crSaveCandidate').querySelector('.btn-text');
        if (text) text.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        el('crCandidateId').value = candidate.candidate_id || '';
        el('crPremNo').value = candidate.prem_no || '';
        el('crFullName').value = candidate.full_name || '';
        el('crGender').value = candidate.gender || '';
        openSingleModal();
    }

    function openImportModal() {
        el('crPupilImportModal').classList.add('show');
        showImportPhase('upload');
        setActionStates();
    }

    function closeImportModal() {
        el('crPupilImportModal').classList.remove('show');
        state.file = null;
        state.previewValid = 0;
        el('crFile').value = '';
        el('crFileName').style.display = 'none';
        el('crPreviewRows').innerHTML = '';
        showImportPhase('upload');
        setActionStates();
    }

    function showImportPhase(phase) {
        el('crImportUploadPhase').style.display = phase === 'upload' ? 'block' : 'none';
        el('crImportReportPhase').style.display = phase === 'report' ? 'block' : 'none';
        el('crImportProcessingPhase').style.display = phase === 'processing' ? 'block' : 'none';
        el('crPreview').style.display = phase === 'upload' ? '' : 'none';
        el('crImportBack').style.display = phase === 'report' ? '' : 'none';
        el('crImport').style.display = phase === 'report' ? '' : 'none';
    }

    function setFile(file) {
        state.file = file;
        state.previewValid = 0;
        el('crFileName').style.display = file ? 'block' : 'none';
        el('crFileName').innerHTML = file ? `<strong>File:</strong> ${escapeHtml(file.name)}<br><strong>Size:</strong> ${formatFileSize(file.size)}` : '';
        showImportPhase('upload');
        setActionStates();
    }

    function formatFileSize(bytes) {
        if (!bytes) return '-';
        return bytes < 1024 * 1024 ? `${(bytes / 1024).toFixed(1)} KB` : `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    async function uploadAction(url) {
        if (!state.file) throw new Error('Please select a CSV, XLSX, or XLS file first.');
        const form = new FormData();
        form.append('exam_type', 'PSLE');
        form.append('exam_year', selectedExamYearLabel());
        if (el('crSchoolId').value) form.append('school_id', el('crSchoolId').value);
        form.append('on_exists_mode', document.querySelector('input[name="crDuplicateMode"]:checked')?.value || 'stop');
        form.append('file', state.file);
        const response = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: form });
        const json = await readJson(response);
        if (!response.ok || json.success === false) throw new Error(json.message || 'Upload failed.');
        return json;
    }

    function renderPreview(data) {
        const rows = data.rows || [];
        el('crPreviewRows').innerHTML = rows.map(row => `<tr>
            <td>${row.row_number}</td>
            <td>${escapeHtml(row.candidate_id)}</td>
            <td>${escapeHtml(row.prem_no)}</td>
            <td>${escapeHtml(row.pupil_name || row.full_name)}</td>
            <td>${escapeHtml(row.sex || row.gender)}</td>
            <td>${escapeHtml(row.school_code)}</td>
            <td>${escapeHtml(row.status)}</td>
            <td>${escapeHtml(row.message)}</td>
        </tr>`).join('');
        el('crModalValidRows').textContent = data.summary.valid_rows || 0;
        el('crModalInvalidRows').textContent = data.summary.invalid_rows || 0;
        el('crTotalRows').textContent = data.summary.total_rows || 0;
        el('crExistingRows').textContent = data.summary.already_existing || data.summary.existing_candidates || 0;
        state.previewValid = data.summary.valid_rows || 0;
        showImportPhase('report');
        setActionStates();
    }

    function downloadTemplate() {
        window.location.href = `${endpoints.template}?exam_type=PSLE`;
    }

    el('crExamYear').addEventListener('change', async () => { resetSchool(); await loadRegions(); });
    el('crRegion').addEventListener('change', loadCouncils);
    el('crCouncil').addEventListener('change', async () => { resetSchool(); await maybeAutoSelectSchool(); loadList(); });
    el('crSchoolSearch').addEventListener('input', () => { clearTimeout(state.searchTimer); state.searchTimer = setTimeout(() => searchSchools().catch(error => showAlert(error.message, 'error')), 300); });
    el('crSchoolResults').addEventListener('click', event => {
        const button = event.target.closest('[data-school]');
        if (!button) return;
        setSelectedSchool(JSON.parse(button.dataset.school));
        el('crSchoolResults').classList.remove('show');
        loadList();
    });
    el('crCandidateSearch').addEventListener('input', () => { clearTimeout(state.searchTimer); state.searchTimer = setTimeout(loadList, 350); });
    el('crRefresh').addEventListener('click', loadList);
    el('crDownloadTemplate').addEventListener('click', downloadTemplate);
    el('crModalDownloadTemplate').addEventListener('click', downloadTemplate);
    el('crOpenAddCandidate').addEventListener('click', () => { clearForm(); openSingleModal(); });
    el('crOpenImport').addEventListener('click', openImportModal);
    el('crOpenImportPanel').addEventListener('click', openImportModal);
    el('crInlineUpload').addEventListener('click', openImportModal);
    el('crSingleForm').addEventListener('submit', event => saveCandidate(event).catch(error => showAlert(error.message, 'error')));
    el('crCloseSingleCandidate').addEventListener('click', closeSingleModal);
    el('crCancelSingleCandidate').addEventListener('click', closeSingleModal);
    el('crSingleCandidateModal').addEventListener('click', event => {
        if (event.target.id === 'crSingleCandidateModal') closeSingleModal();
    });
    document.querySelectorAll('.cr-tab').forEach(tab => tab.addEventListener('click', () => showPanel(tab.dataset.tab)));
    el('crCandidateRows').addEventListener('click', event => {
        if (event.target.closest('[data-empty-add]')) { clearForm(); return openSingleModal(); }
        if (event.target.closest('[data-empty-upload]')) return openImportModal();
        const edit = event.target.closest('[data-edit]');
        const del = event.target.closest('[data-delete]');
        if (edit) {
            const candidate = JSON.parse(edit.dataset.edit);
            openEditModal(candidate);
        }
        if (del && confirm('Delete this candidate registration?')) {
            fetch(`${endpoints.destroy(del.dataset.delete)}?${params()}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                .then(response => readJson(response).then(json => ({ response, json })))
                .then(({ response, json }) => {
                    if (!response.ok || json.success === false) throw new Error(json.message || 'Delete failed.');
                    showAlert(json.message);
                    loadList();
                }).catch(error => showAlert(error.message, 'error'));
        }
    });

    const dropzone = el('crDropzone');
    dropzone.addEventListener('click', () => el('crFile').click());
    dropzone.addEventListener('dragover', event => { event.preventDefault(); dropzone.classList.add('dragging'); });
    dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragging'));
    dropzone.addEventListener('drop', event => { event.preventDefault(); dropzone.classList.remove('dragging'); setFile(event.dataTransfer.files[0]); });
    el('crFile').addEventListener('change', event => setFile(event.target.files[0]));
    el('crCloseImport').addEventListener('click', closeImportModal);
    el('crImportCloseFooter').addEventListener('click', closeImportModal);
    el('crImportBack').addEventListener('click', () => showImportPhase('upload'));
    el('crPreview').addEventListener('click', () => {
        showImportPhase('processing');
        el('crImportProcessingText').textContent = 'Checking school codes, duplicates, and officer scope...';
        uploadAction(endpoints.preview).then(json => { renderPreview(json.data); showAlert(json.message); }).catch(error => { showImportPhase('upload'); showAlert(error.message, 'error'); });
    });
    el('crImport').addEventListener('click', () => uploadAction(endpoints.import).then(json => {
        const imported = json.imported_count ?? json.summary?.inserted ?? 0;
        const updated = json.updated_count ?? json.summary?.updated ?? 0;
        const skipped = json.skipped_count ?? json.summary?.skipped ?? 0;
        showAlert(`${json.message || 'Import complete.'} ${imported} inserted, ${updated} updated, ${skipped} skipped.`);
        closeImportModal();
        loadList();
    }).catch(error => showAlert(error.message, 'error')));
    el('crExport').addEventListener('click', () => {
        const rows = Array.from(document.querySelectorAll('#crCandidateRows tr')).filter(tr => !tr.querySelector('.cr-empty')).map(tr => Array.from(tr.children).slice(0, 6).map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`).join(','));
        const csv = ['S/N,Index Number,PReM No,Candidate Full Name,Sex,Status'].concat(rows).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `psle_candidates_${Date.now()}.csv`; a.click(); URL.revokeObjectURL(url);
    });

    loadRegions().then(loadList).catch(error => showAlert(error.message, 'error'));
})();
</script>
