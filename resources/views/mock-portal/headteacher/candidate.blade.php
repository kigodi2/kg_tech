<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Registration - {{ $school->name }}</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" href="/favicon.ico">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
</head>
<body>
@php
    $buildVisiblePages = function ($paginator, int $radius = 2) {
        $current = max(1, (int) $paginator->currentPage());
        $last = max(1, (int) $paginator->lastPage());
        $start = max(1, $current - $radius);
        $end = min($last, $current + $radius);

        if (($end - $start) < ($radius * 2)) {
            $start = max(1, min($start, $last - ($radius * 2)));
            $end = min($last, max($end, 1 + ($radius * 2)));
        }

        return range($start, $end);
    };

    $uploadErrorReasons = session('upload_error_reasons', []);
    $uploadErrorTotal = (int) session('upload_error_total', count($uploadErrorReasons));
@endphp

<style>
    body, html { margin: 0; padding: 0; width: 100%; min-height: 100vh; background: #0f1117; font-family: 'Maiandra GD', sans-serif; }
    .page-shell { padding: 40px; max-width: 1200px; margin: 0 auto; color: #f0f4f7; }
    .header-card { background: linear-gradient(135deg, #0d1b2a, #11202e); border: 1px solid rgba(187,164,94,0.15); border-radius: 16px; padding: 24px 32px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .school-title { font-size: 1.8rem; font-weight: 800; color: #f0e6c8; margin-bottom: 4px; }
    .school-subtitle { font-size: 0.9rem; color: #BBA45E; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
    .school-meta { font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-top: 8px; }
    
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
    .stat-card { background: #101518; border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; padding: 20px; text-align: center; }
    .stat-val { font-size: 2.2rem; font-weight: 800; margin-bottom: 5px; }
    .stat-lbl { font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    
    .panel-card { background: #101518; border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; }
    .panel-head { padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; }
    .panel-title { font-size: 1.1rem; font-weight: 700; color: #f0f4f7; }
    .panel-actions { display: flex; gap: 12px; }
    
    .btn { padding: 10px 18px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-family: inherit; }
    .btn-outline { background: rgba(255,255,255,0.05); color: #f0f4f7; border: 1px solid rgba(255,255,255,0.1); }
    .btn-outline:hover { background: rgba(255,255,255,0.1); }
    .btn-primary { background: linear-gradient(135deg, #00A3DD, #006fa3); color: #fff; }
    .btn-primary:hover { opacity: 0.9; }
    .btn-warning { background: linear-gradient(135deg, #FCD116, #bba45e); color: #0b1014; }
    .btn-disabled { opacity: 0.45; cursor: not-allowed; pointer-events: none; }
    
    .panel-body { padding: 24px; }
    .table-container { width: 100%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    th { padding: 12px 16px; font-size: 0.75rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid rgba(255,255,255,0.06); }
    td { padding: 16px; font-size: 0.9rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: #f0f4f7; }
    tr:hover td { background: rgba(255,255,255,0.02); }
    
    .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; }
    .badge-m { background: rgba(0,163,221,0.15); color: #67d8ff; border: 1px solid rgba(0,163,221,0.25); }
    .badge-f { background: rgba(252,209,22,0.15); color: #FCD116; border: 1px solid rgba(252,209,22,0.25); }

    .alert { padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; display: flex; align-items: center; gap: 12px; }
    .alert-success { background: rgba(30,181,58,0.15); color: #6ae086; border: 1px solid rgba(30,181,58,0.3); }
    .alert-error { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
    .modal-shell { background:#101518; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:460px; overflow:hidden; box-shadow: 0 24px 60px rgba(0,0,0,0.45); }
    .modal-header { padding:20px 24px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center; gap:16px; }
    .modal-title { margin:0; font-size:1.1rem; color:#f0e6c8; }
    .modal-close { background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem; }
    .modal-form { padding:24px; }
    .modal-grid { display:grid; gap:16px; }
    .modal-field { display:grid; gap:8px; }
    .modal-label { display:block; font-size:0.75rem; color:#9ca3af; text-transform:uppercase; font-weight:700; }
    .modal-input {
        width:100%;
        min-height:42px;
        padding:9px 12px;
        background:#1f2937;
        border:1px solid #374151;
        border-radius:10px;
        color:#fff;
        outline:none;
        font-family: inherit;
        box-sizing:border-box;
    }
    .modal-input::placeholder { color: rgba(255,255,255,0.34); }
    .modal-actions { display:flex; justify-content:flex-end; align-items:center; gap:12px; margin-top:24px; }
    .modal-cancel { background:none; border:none; color:#9ca3af; font-weight:700; cursor:pointer; font-family: inherit; padding:10px 6px; }
    .btn-danger { background: linear-gradient(135deg, #ef4444, #b91c1c); color: #fff; }
    .btn-danger:hover { opacity: 0.92; }
    .upload-error-panel {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(239,68,68,0.12);
        border: 1px solid rgba(239,68,68,0.26);
        color: #fecaca;
        display: grid;
        gap: 10px;
    }
    .upload-error-title { font-size: 0.92rem; font-weight: 800; color: #fca5a5; }
    .upload-error-list {
        margin: 0;
        padding-left: 18px;
        display: grid;
        gap: 6px;
        font-size: 0.84rem;
        line-height: 1.55;
    }
    .upload-help-panel {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        color: #d1d5db;
        font-size: 0.82rem;
        line-height: 1.6;
    }
    .upload-toggle {
        margin-top: 16px;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(252,209,22,0.08);
        border: 1px solid rgba(252,209,22,0.18);
        display: flex;
        gap: 12px;
        align-items: flex-start;
        color: #f8e7a7;
    }
    .upload-toggle input {
        margin-top: 3px;
        accent-color: #FCD116;
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
    }
    .upload-toggle strong { color: #FCD116; display: block; margin-bottom: 4px; }
    .upload-toggle p { margin: 0; font-size: 0.82rem; line-height: 1.55; }
    .manual-modal-shell { max-width: 760px; }
    .manual-modal-body { padding: 24px; max-height: min(76vh, 720px); overflow-y: auto; }
    .manual-summary { margin: 0 0 18px; color: #cbd5e1; line-height: 1.7; font-size: 0.92rem; }
    .manual-grid { display: grid; gap: 14px; }
    .manual-step {
        display: grid;
        grid-template-columns: 42px 1fr;
        gap: 14px;
        align-items: start;
        padding: 14px 16px;
        border-radius: 14px;
        background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02));
        border: 1px solid rgba(255,255,255,0.07);
    }
    .manual-step-number {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #00A3DD, #006fa3);
        color: #fff;
        font-weight: 800;
        font-size: 0.95rem;
        box-shadow: 0 10px 24px rgba(0,163,221,0.24);
    }
    .manual-step h4 { margin: 0 0 6px; color: #f0e6c8; font-size: 0.98rem; }
    .manual-step p { margin: 0; color: #d7dde5; line-height: 1.7; font-size: 0.9rem; }
    .manual-notes {
        margin-top: 18px;
        padding: 16px 18px;
        border-radius: 14px;
        background: rgba(252,209,22,0.08);
        border: 1px solid rgba(252,209,22,0.18);
        color: #f7e4a1;
        display: grid;
        gap: 8px;
        font-size: 0.88rem;
        line-height: 1.6;
    }
    .manual-notes strong { color: #FCD116; }
    .manual-actions-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        padding: 16px 24px 22px;
        background: rgba(255,255,255,0.02);
        border-top: 1px solid rgba(255,255,255,0.06);
    }

    /* Responsiveness */
    @media (max-width: 768px) {
        .page-shell { padding: 20px; }
        .header-card { flex-direction: column; text-align: center; gap: 20px; padding: 24px; }
        .stats-row { grid-template-columns: 1fr; }
        .panel-head { flex-direction: column; gap: 16px; text-align: center; }
        .panel-actions { flex-direction: column; width: 100%; }
        .panel-actions .btn { width: 100%; justify-content: center; }
        .school-title { font-size: 1.4rem; }
        .stat-val { font-size: 1.8rem; }
        .manual-step { grid-template-columns: 1fr; }
        .manual-step-number { width: 38px; height: 38px; }
        .manual-actions-row { padding: 16px; }
    }
</style>

<div class="page-shell">
    <a href="{{ route('mock-portal.school.dashboard') }}" style="color: #00A3DD; text-decoration: none; font-size: 0.85rem; margin-bottom: 20px; display: inline-block;">
        <i class="fas fa-arrow-left"></i> Back to Control Centre
    </a>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> {!! session('warning') !!}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="header-card">
        <div>
            <div class="school-subtitle">TASIDO 2026 MOCK REGISTRATION</div>
            <div class="school-title">{{ $school->name }}</div>
            <div class="school-meta">Centre Number: <strong>{{ $school->code }}</strong> &bull; Region: {{ $school->region->name ?? 'N/A' }}</div>
        </div>
        <div>
            <span style="display:inline-block; padding: 6px 14px; background: {{ $windowOpen ? 'rgba(30,181,58,0.2)' : 'rgba(239,68,68,0.15)' }}; color: {{ $windowOpen ? '#6ae086' : '#fca5a5' }}; border-radius: 20px; font-size: 0.8rem; font-weight: 700; border: 1px solid {{ $windowOpen ? 'rgba(30,181,58,0.4)' : 'rgba(239,68,68,0.35)' }};">
                <i class="fas fa-circle" style="font-size:0.5rem; vertical-align:middle; margin-right:4px;"></i> {{ $windowOpen ? 'Registration Open' : 'Registration Closed' }}
            </span>
        </div>
    </div>

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-val" style="color: #67d8ff;">{{ $stats['total'] }}</div>
            <div class="stat-lbl">Total Candidates</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color: #6ae086;">{{ $stats['boys'] }}</div>
            <div class="stat-lbl">Boys Registered</div>
        </div>
        <div class="stat-card">
            <div class="stat-val" style="color: #FCD116;">{{ $stats['girls'] }}</div>
            <div class="stat-lbl">Girls Registered</div>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-head">
            <div class="panel-title">Candidate Management</div>
            <div class="panel-actions">
                <button onclick="openAddModal()" class="btn btn-warning {{ $windowOpen ? '' : 'btn-disabled' }}" {{ $windowOpen ? '' : 'disabled' }}>
                    <i class="fas fa-plus"></i> Add Candidate
                </button>
                <a href="{{ route('mock-portal.school.candidate.template') }}" class="btn btn-outline">
                    <i class="fas fa-download"></i> CSV Template
                </a>
                <button type="button" onclick="openOwnershipModal()" class="btn btn-outline" style="color: #6ae086; border-color: rgba(30,181,58,0.3);">
                    <i class="fas fa-file-pdf"></i> Download CAL Zip
                </button>
                <button type="button" onclick="openManualModal()" class="btn btn-outline">
                    <i class="fas fa-book-open"></i> User Manual
                </button>
                <button onclick="openUploadModal()" class="btn btn-primary {{ $windowOpen ? '' : 'btn-disabled' }}" {{ $windowOpen ? '' : 'disabled' }}>
                    <i class="fas fa-upload"></i> Upload CSV, XLSX or XLS
                </button>
            </div>
        </div>
        <div class="panel-body">
            <div style="margin-bottom: 16px; color: #9ca3af; font-size: 0.82rem;">
                Accepted upload formats: <strong style="color:#f0e6c8;">CSV, XLSX, XLS</strong>. Required columns: <strong style="color:#f0e6c8;">Index Number, PReM No., Full Name, Sex</strong>.
            </div>
            <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 1%; white-space: nowrap;">#</th>
                                <th style="width: 1%; white-space: nowrap;">Index Number</th>
                                <th style="width: 1%; white-space: nowrap;">PReM No.</th>
                                <th>Full Name</th>
                                <th style="width: 1%; white-space: nowrap;">Sex</th>
                                <th>School</th>
                                <th style="width: 1%; white-space: nowrap;">Status</th>
                                <th style="width: 1%; white-space: nowrap;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($candidates as $idx => $candidate)
                            <tr style="{{ $candidate->status === 'rejected' ? 'background: rgba(239,68,68,0.05);' : '' }}">
                                <td>{{ ($candidates->currentPage() - 1) * $candidates->perPage() + $idx + 1 }}</td>
                                <td style="font-family: monospace; font-weight: 700; white-space: nowrap;">{{ $candidate->candidate_id }}</td>
                                <td style="font-family: monospace; color: #BBA45E; white-space: nowrap;">{{ $candidate->prem_no ?: '---' }}</td>
                                <td><strong>{{ $candidate->full_name }}</strong></td>
                                <td>
                                    @if($candidate->gender == 'M')
                                        <span class="badge badge-m">M</span>
                                    @else
                                        <span class="badge badge-f">F</span>
                                    @endif
                                </td>
                                <td style="font-size: 0.85rem;">{{ $school->name }}</td>
                                <td style="white-space: nowrap;">
                                    @if($candidate->status === 'rejected')
                                        <div title="Reason: {{ $candidate->rejection_reason }}">
                                            <span style="color:#fca5a5; font-size:0.8rem; font-weight:700;"><i class="fas fa-times-circle"></i> Rejected</span>
                                            <div style="font-size: 0.65rem; color: #9ca3af; margin-top: 2px; max-width: 150px; white-space: normal;">{{ $candidate->rejection_reason }}</div>
                                        </div>
                                    @else
                                        <span style="color:#6ae086; font-size:0.8rem;"><i class="fas fa-check"></i> Registered</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="viewCandidate({{ json_encode($candidate) }})" class="btn" style="padding: 4px 8px; background: rgba(0,163,221,0.1); color: #67d8ff; font-size: 0.75rem;" title="View">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="openEditModal({{ json_encode($candidate) }})" class="btn" style="padding: 4px 8px; background: rgba(252,209,22,0.1); color: #FCD116; font-size: 0.75rem;" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('mock-portal.school.candidate.destroy', $candidate->id) }}" method="POST" style="display: inline;" class="delete-candidate-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="openDeleteModal(this.form, {{ json_encode($candidate->full_name) }})" class="btn" style="padding: 4px 8px; background: rgba(239,68,68,0.1); color: #fca5a5; font-size: 0.75rem;" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.4);">
                                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
                                    <p style="font-size: 0.95rem; font-weight: 600;">No candidates registered yet.</p>
                                    <p style="font-size: 0.75rem;">Download template or add manually to see candidates here.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
            
            @if($candidates->lastPage() > 1)
                @php($canPages = $buildVisiblePages($candidates))
                <div style="margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.06); background: linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.04), rgba(255,255,255,0.02)); padding: 18px 20px; border-radius: 0 0 14px 14px;">
                    <div style="display:flex; flex-wrap:wrap; gap:14px; align-items:center; justify-content:space-between;">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; font-size:0.85rem; color:rgba(255,255,255,0.72);">
                            <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(0,163,221,0.10); border:1px solid rgba(0,163,221,0.22); color:#67d8ff; font-weight:700;">
                                <i class="fas fa-layer-group" style="font-size:0.72rem;"></i>
                                <span>Page {{ $candidates->currentPage() }} of {{ max($candidates->lastPage(), 1) }}</span>
                            </div>
                            <div style="display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border-radius:999px; background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.08);">
                                <i class="fas fa-table-list" style="font-size:0.72rem; color:rgba(255,255,255,0.45);"></i>
                                <span>Showing <strong style="color:#fff;">{{ $candidates->count() }}</strong> of <strong style="color:#fff;">{{ $candidates->total() }}</strong> candidates</span>
                            </div>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px; justify-content:flex-end;">
                            <a href="{{ $candidates->url(1) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidates->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $candidates->onFirstPage() ? 'none' : 'auto' }};">
                                <i class="fas fa-angles-left" style="font-size:0.75rem;"></i>
                            </a>
                            <a href="{{ $candidates->previousPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidates->onFirstPage() ? 'rgba(255,255,255,0.35)' : '#d1d5db' }}; text-decoration:none; pointer-events:{{ $candidates->onFirstPage() ? 'none' : 'auto' }};">
                                <i class="fas fa-chevron-left" style="font-size:0.75rem;"></i>
                                <span style="font-size:0.85rem; font-weight:700;">Previous</span>
                            </a>
                            <div style="display:flex; align-items:center; gap:8px; padding:8px; border-radius:16px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04);">
                                @foreach($canPages as $page)
                                    <a href="{{ $candidates->url($page) }}" style="display:inline-flex; align-items:center; justify-content:center; min-width:40px; height:36px; padding:0 12px; border-radius:12px; text-decoration:none; font-size:0.85rem; font-weight:700; {{ $candidates->currentPage() === $page ? 'background:#00A3DD; color:#fff; box-shadow:0 10px 24px rgba(0,163,221,0.28);' : 'color:#d1d5db; background:transparent;' }}">
                                        {{ $page }}
                                    </a>
                                @endforeach
                            </div>
                            <a href="{{ $candidates->nextPageUrl() ?: '#' }}" style="display:inline-flex; align-items:center; gap:8px; height:40px; padding:0 16px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidates->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $candidates->hasMorePages() ? 'auto' : 'none' }};">
                                <span style="font-size:0.85rem; font-weight:700;">Next</span>
                                <i class="fas fa-chevron-right" style="font-size:0.75rem;"></i>
                            </a>
                            <a href="{{ $candidates->url($candidates->lastPage()) }}" style="display:inline-flex; align-items:center; justify-content:center; height:40px; min-width:42px; padding:0 12px; border-radius:12px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.04); color:{{ $candidates->hasMorePages() ? '#d1d5db' : 'rgba(255,255,255,0.35)' }}; text-decoration:none; pointer-events:{{ $candidates->hasMorePages() ? 'auto' : 'none' }};">
                                <i class="fas fa-angles-right" style="font-size:0.75rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Candidate Modal --}}
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div class="modal-shell">
            <div class="modal-header">
                <h3 class="modal-title">Edit Candidate</h3>
                <button onclick="closeEditModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <form id="editForm" method="POST" class="modal-form">
                @csrf
                @method('PUT')
                <div class="modal-grid">
                    @if(session('modal_type') === 'edit' && session('error'))
                        <div class="alert alert-error" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                        </div>
                    @endif
                    @if(session('modal_type') === 'edit' && $errors->any())
                        <div class="alert alert-error" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <i class="fas fa-exclamation-triangle"></i> {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="modal-field">
                        <label class="modal-label">Index Number (Examination Number)</label>
                        <input type="text" name="candidate_id" id="edit_candidate_id" class="modal-input" required pattern="{{ $school->code }}-[0-9]{4}" title="Format: {{ $school->code }}-0001">
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="modal-input" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Gender</label>
                        <select name="gender" id="edit_gender" class="modal-input" required>
                            <option value="M">M</option>
                            <option value="F">F</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">PReM Number</label>
                        <input type="text" name="prem_no" id="edit_prem_no" class="modal-input" required pattern="[0-9]{11}" title="Format: 11 digits (e.g. 20261234567)" placeholder="e.g. 20261234567">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeEditModal()" class="modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <span class="btn-text">Save Changes</span>
                        <span class="btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- View Candidate Modal --}}
    <div id="viewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center;">
        <div style="background:#101518; border:1px solid rgba(255,255,255,0.1); border-radius:16px; width:100%; max-width:400px; overflow:hidden;">
            <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:1.1rem; color:#f0e6c8;">Candidate Details</h3>
                <button onclick="closeViewModal()" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <div id="viewContent" style="padding:24px; color: #f0f4f7;">
                <!-- Content via JS -->
            </div>
            <div style="padding:16px; background:rgba(255,255,255,0.02); display:flex; justify-content:center;">
                <button onclick="closeViewModal()" class="btn btn-outline">Close</button>
            </div>
        </div>
    </div>

    {{-- Add Candidate Modal --}}
    <div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1100; align-items:center; justify-content:center;">
        <div class="modal-shell">
            <div class="modal-header">
                <h3 class="modal-title">Register New Candidate</h3>
                <button onclick="closeAddModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('mock-portal.school.candidate.store') }}" method="POST" class="modal-form">
                @csrf
                <div class="modal-grid">
                    @if(session('modal_type') === 'add' && session('error'))
                        <div class="alert alert-error" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                        </div>
                    @endif
                    @if(session('modal_type') === 'add' && $errors->any())
                        <div class="alert alert-error" style="grid-column: 1 / -1; margin-bottom: 0;">
                            <i class="fas fa-exclamation-triangle"></i> {{ $errors->first() }}
                        </div>
                    @endif
                    <div class="modal-field">
                        <label class="modal-label">Index Number (Examination Number)</label>
                        <input type="text" name="candidate_id" value="{{ old('candidate_id') }}" class="modal-input" required pattern="{{ $school->code }}-[0-9]{4}" title="Format: {{ $school->code }}-0001" placeholder="e.g. {{ $school->code }}-0001">
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" class="modal-input" required>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">Gender</label>
                        <select name="gender" class="modal-input" required>
                            <option value="M" {{ old('gender') === 'M' ? 'selected' : '' }}>M</option>
                            <option value="F" {{ old('gender') === 'F' ? 'selected' : '' }}>F</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label class="modal-label">PReM Number</label>
                        <input type="text" name="prem_no" value="{{ old('prem_no') }}" class="modal-input" required pattern="[0-9]{11}" title="Format: 11 digits (e.g. 20261234567)" placeholder="e.g. 20261234567">
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeAddModal()" class="modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="addSubmitBtn">
                        <span class="btn-text">Register Candidate</span>
                        <span class="btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Registering...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Upload Candidates Modal --}}
    <div id="uploadModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1150; align-items:center; justify-content:center; padding:20px;">
        <div class="modal-shell" style="max-width: 560px;">
            <div class="modal-header">
                <h3 class="modal-title">Upload Candidate File</h3>
                <button type="button" onclick="closeUploadModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('mock-portal.school.candidate.upload') }}" method="POST" enctype="multipart/form-data" class="modal-form" id="uploadForm">
                @csrf
                <div class="modal-grid">
                    <div class="modal-field">
                        <label class="modal-label">Candidate file</label>
                        <input type="file" name="csv_file" id="csv_file" class="modal-input" accept=".csv,.xlsx,.xls" required>
                    </div>
                </div>

                <div class="upload-help-panel">
                    Use the official template headings: <strong>Index Number</strong>, <strong>PReM No.</strong>, <strong>Full Name</strong>, and <strong>Sex</strong>.
                    The system stops the whole upload when it finds an error so that no partial candidate list is saved.
                </div>

                <label class="upload-toggle">
                    <input type="checkbox" name="replace_existing" value="1" {{ old('replace_existing') ? 'checked' : '' }}>
                    <span>
                        <strong>Replace existing candidates from this school</strong>
                        <p>When this is checked, rows with an existing Index Number under this same school will update that candidate's name, sex, and PReM number. Cross-school conflicts and duplicate PReM numbers are still blocked.</p>
                    </span>
                </label>

                @if(session('upload_error_title') || !empty($uploadErrorReasons))
                    <div class="upload-error-panel">
                        <div class="upload-error-title">
                            <i class="fas fa-circle-exclamation"></i>
                            {{ session('upload_error_title', 'The upload could not be completed.') }}
                        </div>
                        @if(!empty($uploadErrorReasons))
                            <ul class="upload-error-list">
                                @foreach($uploadErrorReasons as $reason)
                                    <li>{{ $reason }}</li>
                                @endforeach
                                @if($uploadErrorTotal > count($uploadErrorReasons))
                                    <li>Plus {{ $uploadErrorTotal - count($uploadErrorReasons) }} more issue(s) not shown here.</li>
                                @endif
                            </ul>
                        @endif
                    </div>
                @endif

                <div class="modal-actions">
                    <button type="button" onclick="closeUploadModal()" class="modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Upload</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div id="deleteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:1200; align-items:center; justify-content:center;">
        <div class="modal-shell" style="max-width:430px;">
            <div class="modal-header">
                <h3 class="modal-title">Delete Candidate</h3>
                <button type="button" onclick="closeDeleteModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-form">
                <div style="display:grid; gap:12px;">
                    <p style="margin:0; color:#f0f4f7; line-height:1.7;">Are you sure you want to delete this candidate record?</p>
                    <div id="deleteCandidateName" style="padding:12px 14px; border-radius:10px; background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.2); color:#fca5a5; font-weight:700;"></div>
                    <p style="margin:0; color:#9ca3af; font-size:0.85rem;">This action cannot be undone.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeDeleteModal()" class="modal-cancel">Cancel</button>
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">Delete Candidate</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm School Ownership Modal --}}
    <div id="ownershipModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1250; align-items:center; justify-content:center; padding:20px;">
        <div class="modal-shell" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title">Confirm School Ownership</h3>
                <button type="button" onclick="closeOwnershipModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <form action="{{ route('mock-portal.school.update-ownership') }}" method="POST" class="modal-form" id="ownershipForm">
                @csrf
                <div class="modal-grid">
                    <div class="modal-field">
                        <label class="modal-label">Please review and confirm your school's ownership to proceed with the download. This will update your school's status across the system.</label>
                        <select name="ownership" class="modal-input" required id="ownershipSelect">
                            <option value="" selected disabled>SELECT OWNERSHIP</option>
                            <option value="GOVERNMENT" {{ ($school->ownership ?? '') === 'GOVERNMENT' ? 'selected' : '' }}>GOVERNMENT</option>
                            <option value="NON-GOVERNMENT" {{ ($school->ownership ?? '') === 'NON-GOVERNMENT' ? 'selected' : '' }}>NON-GOVERNMENT</option>
                        </select>
                    </div>
                </div>
                <div id="ownershipExpiredMessage" style="@if(!$windowOpen) display:block; @else display:none; @endif background: rgba(30,181,58,0.10); border: 1px solid rgba(30,181,58,0.28); border-radius: 12px; padding: 12px; margin-top: 12px; color: #86efac; font-size: 0.85rem; line-height: 1.5;">
                    <i class="fas fa-file-arrow-down" style="margin-right: 6px;"></i>
                    Candidate upload is closed, but CAL ZIP download remains available.
                </div>
                <div id="ownershipErrorMessage" style="display:none; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.35); border-radius: 12px; padding: 12px; margin-top: 12px; color: #fca5a5; font-size: 0.85rem; line-height: 1.5;">
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeOwnershipModal()" class="modal-cancel" id="ownershipCancelBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="ownershipSubmitBtn">
                        <span class="btn-text">Confirm & Download</span>
                        <span class="btn-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Processing...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- User Manual Modal --}}
    <div id="manualModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:1250; align-items:center; justify-content:center; padding:20px;">
        <div class="modal-shell manual-modal-shell">
            <div class="modal-header">
                <h3 class="modal-title">Headteacher User Manual</h3>
                <button type="button" onclick="closeManualModal()" class="modal-close"><i class="fas fa-times"></i></button>
            </div>
            <div class="manual-modal-body" id="manualPrintArea">
                <p class="manual-summary">
                    Follow these steps to register all Standard VII candidates safely and avoid missing or overwritten records.
                </p>

                <div class="manual-grid">
                    <div class="manual-step">
                        <div class="manual-step-number">1</div>
                        <div>
                            <h4>Open Candidate Management</h4>
                            <p>Log in to the mock portal, open your school dashboard, then enter <strong>Candidate Management</strong> to view totals, the registration window, and the current candidate list.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">2</div>
                        <div>
                            <h4>Download the Official Template</h4>
                            <p>Click <strong>CSV Template</strong> and use only that file structure. The required columns are <strong>Index Number</strong>, <strong>PReM No.</strong>, <strong>Full Name</strong>, and <strong>Sex</strong>.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">3</div>
                        <div>
                            <h4>Prepare the File Carefully</h4>
                            <p>Ensure each pupil has the correct school code in the index number, an 11-digit PReM number, a full name, and sex marked as <strong>M</strong> or <strong>F</strong>. Avoid duplicate index numbers or duplicate PReM numbers in the same file.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">4</div>
                        <div>
                            <h4>Use Add Candidate for Single Entries</h4>
                            <p>If only one or two pupils were omitted, click <strong>Add Candidate</strong> and register them manually. This is safer than re-uploading a full file just to fix one missing pupil.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">5</div>
                        <div>
                            <h4>Use Upload for New Candidates Only</h4>
                            <p>When uploading a file, include only new pupils or rows that exactly match already-registered pupils. If an existing index number has different details, the upload will stop and ask you to use <strong>Edit</strong> instead.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">6</div>
                        <div>
                            <h4>Review the Result Message</h4>
                            <p>After upload, read the success or error message carefully. The system now stops the whole upload if there is a conflict, which helps prevent incomplete registration records.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">7</div>
                        <div>
                            <h4>Check All Pages of the Candidate List</h4>
                            <p>The candidate table is paginated. If the total is more than 20, move to the next page numbers at the bottom before concluding that pupils are missing.</p>
                        </div>
                    </div>

                    <div class="manual-step">
                        <div class="manual-step-number">8</div>
                        <div>
                            <h4>Edit Existing Candidate Details</h4>
                            <p>Use the <strong>Edit</strong> button to correct an already-registered pupil. Do not rely on upload to change an existing pupil’s details, because upload is now designed to protect against accidental overwrites.</p>
                        </div>
                    </div>
                </div>

                <div class="manual-notes">
                    <div><strong>Important:</strong> If one row in the upload has an error, the whole upload is stopped so that no partial or inconsistent registration is saved.</div>
                    <div><strong>Best practice:</strong> Keep a verified copy of your final candidate file after successful registration.</div>
                    <div><strong>Need help?</strong> If the system reports duplicate index numbers or duplicate PReM numbers, review the existing candidate list first before trying another upload.</div>
                </div>
            </div>
            <div class="manual-actions-row">
                <a href="/headteacher_guide.pdf" class="btn btn-primary" download>
                    <i class="fas fa-file-pdf"></i> Download PDF Guide
                </a>
                <button type="button" onclick="printManual()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print / Save as PDF
                </button>
                <button type="button" onclick="closeManualModal()" class="btn btn-outline">
                    <i class="fas fa-check"></i> Close
                </button>
            </div>
        </div>
    </div>

    <script>
        let pendingDeleteForm = null;

        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'flex';
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
        }

        function viewCandidate(candidate) {
            const content = `
                <div style="display:grid; gap:16px;">
                    <div><span style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; font-weight:700;">Index Number</span><div style="font-size:1.1rem; font-weight:700; color:#67d8ff;">${candidate.candidate_id}</div></div>
                    <div><span style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; font-weight:700;">Full Name</span><div style="font-size:1rem; font-weight:700;">${candidate.full_name}</div></div>
                    <div><span style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; font-weight:700;">Gender</span><div style="font-size:1rem;">${candidate.gender === 'M' ? 'Male' : 'Female'}</div></div>
                    <div><span style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; font-weight:700;">PREM Number</span><div style="font-size:1rem; color:#BBA45E; font-family:monospace;">${candidate.prem_no || '---'}</div></div>
                    <div><span style="color:rgba(255,255,255,0.4); font-size:0.7rem; text-transform:uppercase; font-weight:700;">Status</span><div style="font-size:0.9rem;"><span style="color:${candidate.status === 'rejected' ? '#fca5a5' : '#6ae086'}; font-weight:700;">${candidate.status.toUpperCase()}</span></div></div>
                </div>
            `;
            document.getElementById('viewContent').innerHTML = content;
            document.getElementById('viewModal').style.display = 'flex';
        }
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function openEditModal(candidate) {
            document.getElementById('edit_candidate_id').value = candidate.candidate_id;
            document.getElementById('edit_full_name').value = candidate.full_name;
            document.getElementById('edit_gender').value = candidate.gender;
            document.getElementById('edit_prem_no').value = candidate.prem_no || '';
            document.getElementById('editForm').action = `/mock-portal/school/candidate/${candidate.id}`;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openDeleteModal(form, candidateName) {
            pendingDeleteForm = form;
            document.getElementById('deleteCandidateName').textContent = candidateName || 'Selected candidate';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            pendingDeleteForm = null;
            document.getElementById('deleteModal').style.display = 'none';
        }

        function confirmDelete() {
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
        }

        function openManualModal() {
            document.getElementById('manualModal').style.display = 'flex';
        }

        function closeManualModal() {
            document.getElementById('manualModal').style.display = 'none';
        }

        function openOwnershipModal() {
            document.getElementById('ownershipModal').style.display = 'flex';
        }

        function closeOwnershipModal() {
            document.getElementById('ownershipModal').style.display = 'none';
        }

        function printManual() {
            const content = document.getElementById('manualPrintArea');

            if (!content) {
                return;
            }

            const printWindow = window.open('', '_blank', 'width=900,height=700');
            if (!printWindow) {
                return;
            }

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Headteacher User Manual</title>
                        <style>
                            body { font-family: Arial, sans-serif; color: #111827; margin: 32px; line-height: 1.65; }
                            h1 { font-size: 24px; margin-bottom: 12px; }
                            h4 { font-size: 16px; margin: 0 0 6px; }
                            p { margin: 0; }
                            .manual-step { border: 1px solid #d1d5db; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
                            .manual-step-number { display:inline-block; min-width:28px; height:28px; line-height:28px; text-align:center; border-radius:999px; background:#0ea5e9; color:#fff; font-weight:700; margin-bottom:10px; }
                            .manual-notes { margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: #f8fafc; border: 1px solid #e5e7eb; }
                        </style>
                    </head>
                    <body>
                        <h1>Headteacher User Manual</h1>
                        ${content.innerHTML}
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const modalType = @json(session('modal_type'));
            const hasOldAddFormInput = @json((bool) (old('candidate_id') || old('full_name') || old('gender') || old('prem_no')));
            const shouldOpenUploadModal = @json((bool) session('upload_modal_open'));
            const editingId = @json(session('editing_candidate_id'));

            if (modalType === 'add' || (hasOldAddFormInput && !modalType)) {
                openAddModal();
            } else if (modalType === 'edit' && editingId) {
                // We need the candidate data to re-populate the edit modal
                // Since we only have the ID, we can try to find it in the table or just show the modal
                // Ideally the controller should pass the whole candidate or we find it in the list
                const candidateRow = document.querySelector(`button[onclick*="openEditModal"][onclick*="${editingId}"]`);
                if (candidateRow) {
                    // Extract candidate object from the onclick attribute if possible, 
                    // but simpler is to just trigger the click if it exists on this page
                    candidateRow.click();
                }
            }

            if (shouldOpenUploadModal) {
                openUploadModal();
            }

            // Handle ownership form submission asynchronously
            const ownershipForm = document.getElementById('ownershipForm');
            if (ownershipForm) {
                ownershipForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = document.getElementById('ownershipSubmitBtn');
                    const cancelBtn = document.getElementById('ownershipCancelBtn');
                    const errorEl = document.getElementById('ownershipErrorMessage');
                    const expiredEl = document.getElementById('ownershipExpiredMessage');
                    
                    errorEl.style.display = 'none';
                    errorEl.textContent = '';
                    
                    const isExpired = !@json($windowOpen);
                    if (isExpired && expiredEl) {
                        expiredEl.style.display = 'block';
                    }
                    
                    const textSpan = submitBtn.querySelector('.btn-text');
                    const loadingSpan = submitBtn.querySelector('.btn-loading');
                    
                    if (textSpan && loadingSpan) {
                        textSpan.style.display = 'none';
                        loadingSpan.style.display = 'inline-flex';
                    }
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.cursor = 'not-allowed';
                    cancelBtn.disabled = true;
                    cancelBtn.style.opacity = '0.7';
                    cancelBtn.style.cursor = 'not-allowed';
                    
                    try {
                        const formData = new FormData(this);
                        const response = await fetch(this.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                        
                        if (response.ok) {
                            const contentType = response.headers.get('content-type') || '';
                            if (contentType.includes('application/json')) {
                                const data = await response.json();
                                if (data.success) {
                                    if (data.redirect) {
                                        window.location.href = data.redirect;
                                    } else {
                                        location.reload();
                                    }
                                } else {
                                    throw new Error(data.message || 'An unexpected response was returned by the server.');
                                }
                            } else {
                                const blob = await response.blob();
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.href = url;
                                
                                const disposition = response.headers.get('content-disposition');
                                let filename = 'CAL_Report.zip';
                                if (disposition && disposition.indexOf('attachment') !== -1) {
                                    const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                                    const matches = filenameRegex.exec(disposition);
                                    if (matches != null && matches[1]) { 
                                        filename = matches[1].replace(/['"]/g, '');
                                    }
                                }
                                
                                a.download = filename;
                                document.body.appendChild(a);
                                a.click();
                                a.remove();
                                window.URL.revokeObjectURL(url);
                                
                                closeOwnershipModal();
                                location.reload();
                            }
                        } else {
                            let errorMessage = 'An error occurred during submission.';
                            const contentType = response.headers.get('content-type') || '';
                            if (contentType.includes('application/json')) {
                                try {
                                    const errorData = await response.json();
                                    errorMessage = errorData.message || errorMessage;
                                    if (errorData.errors) {
                                        const firstKey = Object.keys(errorData.errors)[0];
                                        if (firstKey) {
                                            errorMessage = errorData.errors[firstKey][0] || errorMessage;
                                        }
                                    }
                                } catch (e) {}
                            } else {
                                try {
                                    const text = await response.text();
                                    if (text.includes('<title>')) {
                                        const match = text.match(/<title>(.*?)<\/title>/i);
                                        if (match && match[1]) {
                                            errorMessage = `Server Error: ${match[1]}`;
                                        }
                                    } else if (text.trim().length > 0) {
                                        errorMessage = text.substring(0, 150);
                                    }
                                } catch (e) {}
                            }
                            throw new Error(errorMessage);
                        }
                    } catch (error) {
                        errorEl.textContent = error.message || 'An unexpected error occurred. Please try again.';
                        errorEl.style.display = 'block';
                    } finally {
                        if (textSpan && loadingSpan) {
                            textSpan.style.display = 'inline';
                            loadingSpan.style.display = 'none';
                        }
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                        submitBtn.style.cursor = '';
                        cancelBtn.disabled = false;
                        cancelBtn.style.opacity = '';
                        cancelBtn.style.cursor = '';
                    }
                });
            }

            // Handle loading states for forms
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn && (submitBtn.id === 'addSubmitBtn' || submitBtn.id === 'editSubmitBtn')) {
                        const text = submitBtn.querySelector('.btn-text');
                        const loading = submitBtn.querySelector('.btn-loading');
                        if (text && loading) {
                            text.style.display = 'none';
                            loading.style.display = 'inline-flex';
                            submitBtn.disabled = true;
                            submitBtn.style.opacity = '0.7';
                            submitBtn.style.cursor = 'not-allowed';
                        }
                    }
                });
            });
        });
    </script>
    {{-- Registration window warning --}}
    @if(!$windowOpen)
    <div style="background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.3); border-radius: 12px; padding: 16px 24px; margin-top: 20px; color: #fca5a5; font-weight: 600; font-size: 0.9rem;">
        <i class="fas fa-lock"></i> The 31-day registration window closed on {{ $registrationDeadline }}. No more uploads are accepted.
    </div>
    @else
    <div style="background: rgba(30,181,58,0.08); border: 1px solid rgba(30,181,58,0.2); border-radius: 12px; padding: 14px 24px; margin-top: 20px; color: #6ae086; font-size: 0.85rem; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-clock" style="font-size: 1.1rem;"></i> 
        <span>
            <strong>Time Remaining:</strong> <span id="countdown-timer" style="font-family: monospace; font-size: 1rem; font-weight: bold; margin: 0 5px;">{{ $daysRemaining }}d 00h 00m 00s</span> 
            to upload candidates. Deadline: <strong>{{ $registrationDeadline }}</strong>
        </span>
    </div>

    <script>
        (function() {
            const deadline = {{ $deadlineTimestamp }};
            const timerEl = document.getElementById('countdown-timer');

            function updateTimer() {
                const now = new Date().getTime();
                const diff = deadline - now;

                if (diff <= 0) {
                    timerEl.innerHTML = "EXPIRED";
                    location.reload(); // Reload to show locked state
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                timerEl.innerHTML = `${days}d ${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        })();
    </script>
    @endif
</div>
</body>
</html>
