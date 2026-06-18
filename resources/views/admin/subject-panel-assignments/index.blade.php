@extends('layouts.admin')

@section('title', 'Subject Panel Assignments | IRMS Admin')

@push('styles')
<style>
    .content-header { margin-bottom: 28px; }
    .assignments-layout { display: grid; grid-template-columns: 1fr 360px; gap: 24px; align-items: start; }
    .panel-card {
        background: linear-gradient(135deg, #0d1b2a, #111e29);
        border: 1px solid rgba(0,163,221,0.18);
        border-radius: 14px;
        overflow: hidden;
    }
    .panel-card-gold { border-color: rgba(187,164,94,0.2); padding: 24px; }
    .panel-card-head {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .panel-card-title { font-size: .95rem; font-weight: 700; color: #f0e6c8; }
    .count-pill {
        font-size: .72rem;
        background: rgba(0,163,221,0.12);
        border: 1px solid rgba(0,163,221,0.25);
        color: #67d8ff;
        padding: 2px 9px;
        border-radius: 12px;
        font-weight: 700;
    }
    .panel-table { width: 100%; border-collapse: collapse; }
    .panel-table th {
        padding: 9px 14px;
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: rgba(255,255,255,.3);
        background: rgba(0,0,0,.2);
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,.06);
        white-space: nowrap;
    }
    .panel-table td {
        padding: 11px 14px;
        font-size: .82rem;
        border-bottom: 1px solid rgba(255,255,255,.04);
    }
    .muted-cell { color: rgba(255,255,255,.55); }
    .status-badge {
        padding: 2px 9px;
        border-radius: 10px;
        font-size: .68rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
    }
    .status-active { background: rgba(74,222,128,.12); border: 1px solid rgba(74,222,128,.25); color: #4ade80; }
    .status-inactive { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: rgba(255,255,255,.35); }
    .action-row { display: flex; gap: 6px; flex-wrap: wrap; }
    .small-action {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: .7rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
    }
    .action-toggle { background: rgba(0,163,221,.1); border: 1px solid rgba(0,163,221,.25); color: #67d8ff; }
    .action-remove { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.2); color: #fca5a5; }
    .form-label {
        font-size: .63rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255,255,255,.35);
        display: block;
        margin-bottom: 5px;
    }
    .form-control {
        width: 100%;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 8px;
        color: #f0f4f7;
        padding: 9px 12px;
        font-size: .83rem;
        font-family: inherit;
    }
    .form-control option { background: #111e29; color: #f0f4f7; }
    .form-field { margin-bottom: 14px; }
    .create-btn {
        width: 100%;
        padding: 11px;
        border-radius: 9px;
        font-size: .88rem;
        font-weight: 700;
        cursor: pointer;
        background: linear-gradient(135deg, #BBA45E, #8a7540);
        border: none;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .flash {
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .85rem;
        font-weight: 600;
    }
    .flash-success { background: rgba(74,222,128,.1); border: 1px solid rgba(74,222,128,.25); color: #4ade80; }
    .flash-error { background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25); color: #f87171; }
    .empty-box { padding: 48px; text-align: center; color: rgba(255,255,255,.3); }
    @media (max-width: 980px) {
        .assignments-layout { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="content-header">
    <h1 style="font-size:1.5rem;font-weight:800;color:#f0e6c8;margin:0 0 4px;">Subject Panel Assignments</h1>
    <p style="color:rgba(255,255,255,0.45);font-size:0.85rem;margin:0;">Assign Subject Panel Leaders to PSLE subjects for mark verification.</p>
</div>

@if(session('success'))
    <div class="flash flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="flash flash-error">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ $errors->first() }}</span>
    </div>
@endif

<div class="assignments-layout">
    <div class="panel-card">
        <div class="panel-card-head">
            <span class="panel-card-title">Current Assignments</span>
            <span class="count-pill">{{ $assignments->total() }} total</span>
        </div>

        @if($assignments->isEmpty())
            <div class="empty-box">
                <i class="fas fa-users-slash" style="font-size:2.5rem;margin-bottom:14px;display:block;"></i>
                <div style="font-size:.9rem;">No assignments yet. Create one using the form on the right.</div>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>Panel Leader</th>
                            <th>Subject</th>
                            <th>Exam Year</th>
                            <th>Region Scope</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                            <tr>
                                <td style="color:#f0e6c8;font-weight:600;">{{ $assignment->user?->name ?? '—' }}</td>
                                <td style="color:#67d8ff;">{{ $assignment->subject?->name ?? '—' }}</td>
                                <td class="muted-cell">{{ $assignment->examYear?->year_label ?? 'All' }}</td>
                                <td class="muted-cell">{{ $assignment->region?->name ?? 'All Regions' }}</td>
                                <td>
                                    @if($assignment->is_active)
                                        <span class="status-badge status-active">Active</span>
                                    @else
                                        <span class="status-badge status-inactive">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-row">
                                        <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.toggle', $assignment->id) }}" style="margin:0;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="small-action action-toggle">
                                                {{ $assignment->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.destroy', $assignment->id) }}" style="margin:0;" onsubmit="return confirm('Remove this assignment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="small-action action-remove">Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($assignments->hasPages())
                <div style="padding:16px;display:flex;gap:6px;justify-content:center;">
                    {{ $assignments->links() }}
                </div>
            @endif
        @endif
    </div>

    <div class="panel-card panel-card-gold">
        <div style="font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#BBA45E;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fas fa-plus-circle"></i> New Assignment
        </div>

        <form method="POST" action="{{ route('mark-entry.psle.subject-panel-assignments.store') }}">
            @csrf

            <div class="form-field">
                <label class="form-label">Panel Leader User <span style="color:#fb923c;">*</span></label>
                @if($panelLeaders->isEmpty())
                    <div style="font-size:.8rem;color:#fb923c;background:rgba(251,146,60,.08);border:1px solid rgba(251,146,60,.2);border-radius:8px;padding:9px 12px;">
                        No users with <strong>subject_panel_leader</strong> portal role found. Create a user with that role first.
                    </div>
                    <input type="hidden" name="user_id" value="">
                @else
                    <select name="user_id" required class="form-control">
                        <option value="">— Select Panel Leader —</option>
                        @foreach($panelLeaders as $panelLeader)
                            <option value="{{ $panelLeader->id }}" @selected(old('user_id') == $panelLeader->id)>
                                {{ $panelLeader->name }} ({{ $panelLeader->email }})
                            </option>
                        @endforeach
                    </select>
                @endif
                @error('user_id') <div style="font-size:.72rem;color:#f87171;margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">PSLE Subject <span style="color:#fb923c;">*</span></label>
                <select name="subject_id" required class="form-control">
                    <option value="">— Select Subject —</option>
                    @foreach($psleSubjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
                @error('subject_id') <div style="font-size:.72rem;color:#f87171;margin-top:4px;">{{ $message }}</div> @enderror
            </div>

            <div class="form-field">
                <label class="form-label">Exam Year (optional)</label>
                <select name="exam_year_id" class="form-control">
                    <option value="">All Years</option>
                    @foreach($examYears as $examYear)
                        <option value="{{ $examYear->id }}" @selected(old('exam_year_id') == $examYear->id)>{{ $examYear->year_label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field" style="margin-bottom:20px;">
                <label class="form-label">Region Scope (optional)</label>
                <select name="region_id" class="form-control">
                    <option value="">All Regions</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" @selected(old('region_id') == $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="create-btn">
                <i class="fas fa-user-plus"></i> Create Assignment
            </button>
        </form>

        <div style="margin-top:20px;padding:12px 14px;background:rgba(0,163,221,.06);border:1px solid rgba(0,163,221,.15);border-radius:8px;font-size:.75rem;color:rgba(255,255,255,.45);line-height:1.55;">
            <strong style="color:#67d8ff;">Note:</strong>
            Users must have <code style="color:#BBA45E;">portal_role = subject_panel_leader</code> on their account.
            You can set this via Admin → Users & Roles.
        </div>
    </div>
</div>
@endsection
