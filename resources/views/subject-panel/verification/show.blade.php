<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Detail — Subject Panel Verification</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<style>
* { box-sizing: border-box; }
body, html { margin: 0; padding: 0; min-height: 100vh; background: #0b1014; font-family: 'Maiandra GD', sans-serif; color: #f0f4f7; }
:root { --accent:#BBA45E; --tz-yellow:#FCD116; --tz-blue:#00A3DD; --tz-muted:rgba(255,255,255,.45); --card-bg:linear-gradient(135deg,#0d1b2a,#111e29); --card-border:rgba(0,163,221,0.18); }

.topbar { background: rgba(11,16,20,0.97); border-bottom: 1px solid rgba(187,164,94,0.15); padding: 11px 28px; display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 12px; }
.brand-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #BBA45E, #8a7540); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.95rem; }
.brand-name { font-size: 1rem; font-weight: 800; color: #f0e6c8; }
.brand-sub { font-size: 0.58rem; color: var(--tz-yellow); font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; }
.flag-bar { display: flex; height: 4px; }
.flag-bar span { flex: 1; }
.back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--tz-blue); font-size: 0.82rem; font-weight: 600; text-decoration: none; margin-bottom: 24px; transition: opacity 0.2s; }
.back-link:hover { opacity: 0.8; }
.main { max-width: 820px; margin: 0 auto; padding: 36px 28px 64px; }
.section-eyebrow { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--tz-yellow); margin-bottom: 6px; }
.section-title { font-size: 1.5rem; font-weight: 900; color: #f0e6c8; margin-bottom: 6px; }
.section-sub { font-size: 0.88rem; color: var(--tz-muted); margin-bottom: 28px; }
.card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 14px; padding: 24px 28px; margin-bottom: 20px; }
.card-title { font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--tz-blue); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.field-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.field { display: flex; flex-direction: column; gap: 4px; }
.field-lbl { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.3); }
.field-val { font-size: 0.9rem; color: #f0e6c8; font-weight: 600; }
.field-val-mono { font-family: monospace; font-size: 1rem; color: #67d8ff; font-weight: 700; }
.marks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: 12px; }
.mark-box { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; text-align: center; }
.mark-box-lbl { font-size: 0.6rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(255,255,255,0.3); margin-bottom: 6px; }
.mark-box-val { font-size: 1.6rem; font-weight: 900; color: #f0e6c8; font-family: monospace; }
.mark-box-total .mark-box-val { color: #fbbf24; }
.mark-box-abs .mark-box-val { color: #f87171; font-size: 1rem; }
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 11px; border-radius: 12px; font-size: 0.72rem; font-weight: 700; }
.badge-pending { background: rgba(251,191,36,0.12); border: 1px solid rgba(251,191,36,0.25); color: #fbbf24; }
.badge-verified { background: rgba(74,222,128,0.12); border: 1px solid rgba(74,222,128,0.25); color: #4ade80; }
.badge-returned { background: rgba(251,146,60,0.12); border: 1px solid rgba(251,146,60,0.25); color: #fb923c; }
.badge-corrected { background: rgba(167,139,250,0.12); border: 1px solid rgba(167,139,250,0.25); color: #a78bfa; }
.readonly-notice { background: rgba(0,163,221,0.08); border: 1px solid rgba(0,163,221,0.2); border-radius: 10px; padding: 12px 16px; font-size: 0.82rem; color: #67d8ff; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
.return-reason-box { background: rgba(251,146,60,0.08); border: 1px solid rgba(251,146,60,0.2); border-radius: 10px; padding: 14px 16px; margin-top: 12px; }
.return-reason-label { font-size: 0.62rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #fb923c; margin-bottom: 6px; }
.return-reason-text { font-size: 0.88rem; color: #f0e6c8; line-height: 1.6; }
.actions-row { display: flex; gap: 10px; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 9px; font-size: 0.84rem; font-weight: 700; font-family: inherit; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
.btn-verify { background: linear-gradient(135deg, #4ade80, #16a34a); color: #fff; }
.btn-verify:hover { opacity: 0.88; }
.btn-return { background: rgba(251,146,60,0.15); border: 1px solid rgba(251,146,60,0.3); color: #fb923c; }
.btn-return:hover { background: rgba(251,146,60,0.25); }
.btn-back { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--tz-muted); }
.btn-back:hover { background: rgba(255,255,255,0.1); color: #fff; }
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.72); backdrop-filter: blur(4px); z-index: 999; display: none; align-items: center; justify-content: center; padding: 20px; }
.modal-overlay.active { display: flex; }
.modal-shell { background: #0d1b2a; border: 1px solid rgba(187,164,94,0.22); border-radius: 18px; padding: 28px 30px; width: 100%; max-width: 480px; position: relative; }
.modal-close { position: absolute; top: 14px; right: 16px; background: none; border: none; color: rgba(255,255,255,0.4); font-size: 1.1rem; cursor: pointer; }
.reason-select, .reason-textarea { width: 100%; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; color: #f0f4f7; padding: 9px 12px; font-size: 0.83rem; font-family: inherit; margin-bottom: 12px; }
.reason-select:focus, .reason-textarea:focus { outline: none; border-color: rgba(251,146,60,0.5); }
.reason-select option { background: #0d1b2a; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.btn-cancel { padding: 8px 16px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; font-family: inherit; cursor: pointer; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); color: var(--tz-muted); }
.btn-submit { padding: 8px 18px; border-radius: 8px; font-size: 0.8rem; font-weight: 700; font-family: inherit; cursor: pointer; background: linear-gradient(135deg, #fb923c, #c2410c); border: none; color: #fff; }
.flash { padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.flash-success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.25); color: #4ade80; }
.flash-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.25); color: #f87171; }
</style>

<div class="topbar">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
        <div>
            <div class="brand-name">IRMS — TASIDO 2026</div>
            <div class="brand-sub">Subject Panel Verification</div>
        </div>
    </div>
    <form method="POST" action="/logout" style="margin:0;">
        @csrf
        <button type="submit" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;color:#fca5a5;font-size:0.78rem;font-weight:600;cursor:pointer;font-family:inherit;">
            <i class="fas fa-right-from-bracket"></i> Logout
        </button>
    </form>
</div>
<div class="flag-bar">
    <span style="background:#1EB53A;"></span>
    <span style="background:#FCD116;"></span>
    <span style="background:#000;"></span>
    <span style="background:#00A3DD;"></span>
</div>

<div class="main">
    @if(session('success'))
        <div class="flash flash-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif

    <a href="{{ route('subject-panel.verification.index') }}" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Verification List
    </a>

    <div class="section-eyebrow">MARK RECORD DETAIL</div>
    <h1 class="section-title">{{ $rawMark->candidate_index_number }}</h1>
    <p class="section-sub">Read-only view. You may verify or return this record for correction.</p>

    <div class="readonly-notice">
        <i class="fas fa-lock"></i>
        Marks are displayed in <strong>read-only</strong> mode. You cannot edit mark values from this portal.
    </div>

    {{-- Candidate Info --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-user"></i> Candidate Information</div>
        <div class="field-grid">
            <div class="field">
                <span class="field-lbl">Index Number</span>
                <span class="field-val-mono">{{ $rawMark->candidate_index_number }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Full Name</span>
                <span class="field-val">{{ $rawMark->full_name }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Sex</span>
                <span class="field-val">{{ $rawMark->candidate?->gender === 'M' ? 'Male' : ($rawMark->candidate?->gender === 'F' ? 'Female' : '—') }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">School</span>
                <span class="field-val">{{ $rawMark->batch?->school?->name ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Region</span>
                <span class="field-val">{{ $rawMark->batch?->school?->region?->name ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Council</span>
                <span class="field-val">{{ $rawMark->batch?->school?->council?->name ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Subject</span>
                <span class="field-val">{{ $rawMark->subject?->name ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Mark Details --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-chart-bar"></i> Mark Details (Read-Only)</div>
        <div class="marks-grid">
            <div class="mark-box">
                <div class="mark-box-lbl">Paper 1</div>
                <div class="mark-box-val">{{ $rawMark->paper_1_marks !== null ? number_format($rawMark->paper_1_marks, 1) : '—' }}</div>
            </div>
            <div class="mark-box">
                <div class="mark-box-lbl">Paper 2</div>
                <div class="mark-box-val">{{ $rawMark->paper_2_marks !== null ? number_format($rawMark->paper_2_marks, 1) : '—' }}</div>
            </div>
            <div class="mark-box">
                <div class="mark-box-lbl">Paper 3</div>
                <div class="mark-box-val">{{ $rawMark->paper_3_marks !== null ? number_format($rawMark->paper_3_marks, 1) : '—' }}</div>
            </div>
            <div class="mark-box">
                <div class="mark-box-lbl">Practical</div>
                <div class="mark-box-val">{{ $rawMark->practical_marks !== null ? number_format($rawMark->practical_marks, 1) : '—' }}</div>
            </div>
            <div class="mark-box mark-box-total">
                <div class="mark-box-lbl">Total</div>
                <div class="mark-box-val">{{ number_format($rawMark->total_score, 1) }}</div>
            </div>
            @if($rawMark->subject_status)
            <div class="mark-box mark-box-abs">
                <div class="mark-box-lbl">Status</div>
                <div class="mark-box-val">{{ strtoupper($rawMark->subject_status) }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Verification Status --}}
    <div class="card">
        <div class="card-title"><i class="fas fa-clipboard-check"></i> Verification Status</div>
        @php
            $verif = $rawMark->verification;
            $status = $verif?->status ?? 'pending';
        @endphp
        <div class="field-grid">
            <div class="field">
                <span class="field-lbl">Current Status</span>
                <span class="field-val">
                    @if($status === 'verified') <span class="badge badge-verified"><i class="fas fa-circle-check"></i> Verified</span>
                    @elseif($status === 'returned_for_correction') <span class="badge badge-returned"><i class="fas fa-rotate-left"></i> Returned for Correction</span>
                    @elseif($status === 'corrected_resubmitted') <span class="badge badge-corrected"><i class="fas fa-check-double"></i> Corrected & Resubmitted</span>
                    @else <span class="badge badge-pending"><i class="fas fa-clock"></i> Pending Review</span>
                    @endif
                </span>
            </div>
            <div class="field">
                <span class="field-lbl">Entered By</span>
                <span class="field-val">{{ $rawMark->batch?->createdByUser?->name ?? $rawMark->batch?->importedByUser?->name ?? '—' }}</span>
            </div>
            <div class="field">
                <span class="field-lbl">Last Updated</span>
                <span class="field-val">{{ $rawMark->updated_at?->format('d M Y H:i') ?? '—' }}</span>
            </div>
            @if($verif?->verified_at)
            <div class="field">
                <span class="field-lbl">Verified At</span>
                <span class="field-val">{{ $verif->verified_at->format('d M Y H:i') }}</span>
            </div>
            @endif
            @if($verif?->correction_round > 0)
            <div class="field">
                <span class="field-lbl">Correction Round</span>
                <span class="field-val">{{ $verif->correction_round }}</span>
            </div>
            @endif
        </div>

        @if($status === 'returned_for_correction' && $verif?->return_reason)
        <div class="return-reason-box">
            <div class="return-reason-label"><i class="fas fa-exclamation-triangle"></i> Return Reason</div>
            <div class="return-reason-text">{{ $verif->return_reason }}</div>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="actions-row">
        <a href="{{ route('subject-panel.verification.index') }}" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to List</a>
        @if($status !== 'verified' && $status !== 'returned_for_correction')
        <form method="POST" action="{{ route('subject-panel.verification.verify', $rawMark->id) }}" style="margin:0;" onsubmit="return confirm('Verify this mark as correct?');">
            @csrf
            <button type="submit" class="btn btn-verify"><i class="fas fa-check-circle"></i> Verify as Correct</button>
        </form>
        <button type="button" class="btn btn-return" onclick="document.getElementById('returnModal').classList.add('active')">
            <i class="fas fa-rotate-left"></i> Return for Correction
        </button>
        @elseif($status === 'verified')
        <span style="font-size:0.82rem;color:#4ade80;display:flex;align-items:center;gap:6px;"><i class="fas fa-check-circle"></i> This record has been verified.</span>
        @elseif($status === 'returned_for_correction')
        <span style="font-size:0.82rem;color:#fb923c;display:flex;align-items:center;gap:6px;"><i class="fas fa-clock"></i> Waiting for MEO to correct this record.</span>
        @endif
    </div>
</div>

{{-- Return Modal --}}
<div class="modal-overlay" id="returnModal">
    <div class="modal-shell">
        <button class="modal-close" onclick="document.getElementById('returnModal').classList.remove('active')">&times;</button>
        <div style="font-size:0.62rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--tz-yellow);margin-bottom:6px;">PANEL ACTION</div>
        <div style="font-size:1.1rem;font-weight:800;color:#f0e6c8;margin-bottom:18px;">Return Mark for Correction</div>
        <form method="POST" action="{{ route('subject-panel.verification.return', $rawMark->id) }}">
            @csrf
            <select class="reason-select" name="_reason_preset" onchange="document.getElementById('return_reason').value = this.value !== 'Other' ? this.value : '';">
                <option value="">— Select reason —</option>
                @foreach($returnReasons as $reason)
                    <option value="{{ $reason }}">{{ $reason }}</option>
                @endforeach
            </select>
            <select class="reason-select" name="paper_code">
                <option value="">All / not paper-specific</option>
                <option value="paper_1">Paper 1</option>
                <option value="paper_2">Paper 2</option>
                <option value="paper_3">Paper 3</option>
                <option value="practical">Practical</option>
                <option value="project">Project</option>
            </select>
            <textarea class="reason-textarea" id="return_reason" name="return_reason" rows="3"
                placeholder="Describe the correction needed…" required maxlength="500"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('returnModal').classList.remove('active')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Return</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
