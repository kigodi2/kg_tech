<!-- Scoped styles for Results Processing Dashboard -->
<style>
    .processing-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }
    
    .proc-summary-card {
        background: linear-gradient(135deg, #121924, #0e131b);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        transition: all 0.25s ease;
    }
    .proc-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.25);
        border-color: rgba(187,164,94,0.2);
    }
    .proc-summary-label {
        font-size: 0.68rem;
        color: var(--tz-text-muted);
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 1px;
        margin-bottom: 6px;
    }
    .proc-summary-value {
        font-size: 1.7rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: -0.5px;
    }
    .proc-summary-sub {
        font-size: 0.76rem;
        color: rgba(255,255,255,0.45);
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .proc-summary-icon {
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 1.15rem;
        opacity: 0.2;
    }
    
    .lifecycle-grid {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 28px;
        margin-bottom: 28px;
    }
    
    .action-card {
        background: rgba(255,255,255,0.015);
        border: 1px solid rgba(255,255,255,0.04);
        border-radius: 10px;
        padding: 16px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        transition: all 0.2s ease;
    }
    .action-card:hover {
        background: rgba(255,255,255,0.03);
        border-color: rgba(255,255,255,0.08);
    }
    
    .action-card-btn {
        width: 100%;
        justify-content: flex-start !important;
        text-align: left;
        padding: 14px 18px !important;
        height: auto !important;
        border-radius: 8px !important;
    }
    .action-card-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .action-card-btn-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .action-card-btn-title {
        font-size: 0.88rem;
        font-weight: 700;
    }
    .action-card-btn-desc {
        font-size: 0.72rem;
        font-weight: normal;
        opacity: 0.8;
    }
    
    .compliance-item {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }
    .compliance-item i {
        color: var(--tz-green);
        font-size: 0.95rem;
        margin-top: 2px;
    }
    .compliance-item-text {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.7);
        line-height: 1.45;
    }
    
    .empty-state-container {
        padding: 48px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }
    .empty-state-icon {
        font-size: 2.5rem;
        color: var(--tz-green);
        margin-bottom: 16px;
        text-shadow: 0 0 20px rgba(30,181,58,0.2);
    }
    .empty-state-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #ffffff;
        margin-bottom: 6px;
    }
    .empty-state-desc {
        font-size: 0.85rem;
        color: var(--tz-text-muted);
        max-width: 420px;
    }

    @media (max-width: 1200px) {
        .processing-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .lifecycle-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }
    @media (max-width: 640px) {
        .processing-summary-grid {
            grid-template-columns: 1fr;
        }
    }

    .correction-form-grid {
        display: grid;
        grid-template-columns: 1.2fr 2fr 180px;
        gap: 16px;
        align-items: flex-end;
    }

    @media (max-width: 992px) {
        .correction-form-grid {
            grid-template-columns: 1fr 1fr;
        }
        .correction-form-grid > div:last-child {
            grid-column: span 2;
        }
    }

    @media (max-width: 640px) {
        .correction-form-grid {
            grid-template-columns: 1fr;
        }
        .correction-form-grid > div:last-child {
            grid-column: span 1;
        }
    }

    /* Custom Admin Dark Theme overrides for Select2 */
    .select2-container--admin-dark {
        font-family: inherit;
    }
    .select2-container--admin-dark .select2-selection--single {
        background: #151d21 !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
        border-radius: 6px !important;
        height: 38px !important;
        display: flex !important;
        align-items: center !important;
        color: #ffffff !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .select2-container--admin-dark .select2-selection--single .select2-selection__rendered {
        color: #ffffff !important;
        padding-left: 12px !important;
        font-size: 0.85rem !important;
    }
    .select2-container--admin-dark .select2-selection--single .select2-selection__placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }
    .select2-container--admin-dark .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
        right: 8px !important;
    }
    .select2-container--admin-dark .select2-selection--single .select2-selection__arrow b {
        border-color: rgba(255, 255, 255, 0.6) transparent transparent transparent !important;
    }
    .select2-container--admin-dark.select2-container--open .select2-selection--single .select2-selection__arrow b {
        border-color: transparent transparent rgba(255, 255, 255, 0.6) transparent !important;
    }
    .select2-container--admin-dark.select2-container--focus .select2-selection--single,
    .select2-container--admin-dark.select2-container--open .select2-selection--single {
        border-color: var(--tz-blue) !important;
        box-shadow: 0 0 0 3px rgba(0, 163, 221, 0.15) !important;
    }
    
    .select2-container--admin-dark .select2-dropdown {
        background-color: #151d21 !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
        color: #ffffff !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3) !important;
    }
    .select2-container--admin-dark .select2-search--dropdown {
        background-color: #151d21 !important;
        padding: 8px !important;
    }
    .select2-container--admin-dark .select2-search--dropdown .select2-search__field {
        background-color: #101518 !important;
        border: 1px solid rgba(255,255,255,0.12) !important;
        color: #ffffff !important;
        border-radius: 6px !important;
        padding: 6px 10px !important;
        outline: none !important;
    }
    .select2-container--admin-dark .select2-results__option {
        padding: 8px 12px !important;
        font-size: 0.85rem !important;
        color: rgba(255, 255, 255, 0.8) !important;
    }
    .select2-container--admin-dark .select2-results__option--highlighted[aria-selected] {
        background-color: var(--tz-blue) !important;
        color: #ffffff !important;
    }
    .select2-container--admin-dark .select2-results__option[aria-selected=true] {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #ffffff !important;
    }
</style>

@php
    $total = $viewData['readiness']->total ?? 0;
    $complete = $viewData['readiness']->complete ?? 0;
    $readyPercentRaw = $total > 0 ? ($complete / $total) * 100 : 0;
    $readyPercent = $complete === $total ? 100 : min(99.99, round($readyPercentRaw, 2));
    
    $lastRuns = collect($viewData['lastRuns'] ?? []);

    $schoolsList = DB::table('schools')
        ->whereIn('region_id', $tasidoRegions->pluck('id')->toArray())
        ->where('education_level', 'PRIMARY')
        ->select('id', 'name', 'code')
        ->orderBy('name')
        ->get();
    
    // Check if raw marks have been submitted & locked
    $submitLockRun = $lastRuns->firstWhere('type', 'submit_lock');
    $isRawMarksLocked = $submitLockRun && $submitLockRun->status === 'completed';

    // Check if final run is done
    $finalRun = $lastRuns->firstWhere('type', 'final');
    $isFinalRunDone = $finalRun && $finalRun->status === 'completed';

    // Check if draft run is done
    $draftRun = $lastRuns->firstWhere('type', 'draft');
    $isDraftRunDone = $draftRun && $draftRun->status === 'completed';

    // Determine Active Lifecycle State Label and badge
    if ($isFinalRunDone) {
        $statusLabel = 'Locked & Calculated';
        $statusClass = 'badge-green';
    } elseif ($isDraftRunDone) {
        $statusLabel = 'Draft Run Done';
        $statusClass = 'badge-blue';
    } elseif ($isRawMarksLocked) {
        $statusLabel = 'Raw Marks Locked';
        $statusClass = 'badge-yellow';
    } else {
        $statusLabel = 'Pending Submission';
        $statusClass = 'badge-red';
    }

    // Check if published snapshot exists
    $publishedSnapshot = DB::table('psle_result_publications')
        ->where('exam_year_id', $examYear->id)
        ->where('status', 'published')
        ->first();

    if ($publishedSnapshot) {
        $pubState = 'Published (v' . $publishedSnapshot->version_no . ')';
        $pubClass = 'badge-green';
    } elseif ($isFinalRunDone) {
        $pubState = 'Ready to Publish';
        $pubClass = 'badge-blue';
    } else {
        $pubState = 'Not Published';
        $pubClass = 'badge-red';
    }
@endphp

<!-- Active Scope Information Box -->
<div class="adm-card" style="margin-bottom: 24px; background: linear-gradient(135deg, rgba(187,164,94,0.08) 0%, rgba(17,24,35,0.6) 100%); border: 1px solid rgba(187,164,94,0.18);">
    <div class="adm-card-body" style="padding: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 10px; background: rgba(187,164,94,0.15); display: flex; align-items: center; justify-content: center; color: var(--tz-yellow); font-size: 1.5rem;">
                <i class="fa-solid fa-earth-africa"></i>
            </div>
            <div>
                <h4 style="margin: 0 0 4px 0; font-size: 1.1rem; font-weight: 800; color: #ffffff;">All TASIDO Regions</h4>
                <p style="margin: 0; font-size: 0.8rem; color: var(--tz-text-muted);">
                    Affected Regions ({{ count($tasidoRegions) }}): <strong>{{ $tasidoRegions->pluck('name')->map(fn($n) => ucfirst(strtolower($n)))->implode(', ') }}</strong>
                </p>
            </div>
        </div>
        <div style="display: flex; gap: 32px;">
            <div>
                <span style="display: block; font-size: 0.68rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Active Schools</span>
                <span style="font-size: 1.3rem; font-weight: 800; color: #ffffff;">{{ number_format($metrics['schools']) }}</span>
            </div>
            <div>
                <span style="display: block; font-size: 0.68rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Registered Candidates</span>
                <span style="font-size: 1.3rem; font-weight: 800; color: #ffffff;">{{ number_format($metrics['registered']) }}</span>
            </div>
        </div>
    </div>
</div>

@if($publishedSnapshot && !$isRawMarksLocked)
<div class="adm-card" style="margin-bottom: 24px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 10px;">
    <div class="adm-card-body" style="padding: 16px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(239, 68, 68, 0.15); display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <h5 style="margin: 0 0 4px 0; color: #ef4444; font-weight: 700; font-size: 0.92rem;">Warning: Results Published but Raw Marks Not Fully Locked</h5>
            <p style="margin: 0; font-size: 0.78rem; color: rgba(255, 255, 255, 0.85); line-height: 1.4;">
                The results for this year are currently published, but raw marks are not fully locked. Please submit and lock the raw marks immediately to secure data integrity.
            </p>
        </div>
    </div>
</div>
@endif

<!-- Status summary row -->
<div class="processing-summary-grid">
    <div class="proc-summary-card" style="border-left: 3px solid var(--tz-green);">
        <div class="proc-summary-label">Data Readiness</div>
        <div class="proc-summary-value">{{ $readyPercent }}%</div>
        <div class="proc-summary-sub">
            <i class="fa-solid fa-{{ $total - $complete > 0 ? 'triangle-exclamation' : 'circle-check' }}" style="color: {{ $total - $complete > 0 ? '#ef4444' : 'var(--tz-green)' }};"></i>
            <span>
                {{ number_format($complete) }} of {{ number_format($total) }} candidates complete
                @if($total - $complete > 0)
                    <strong style="color: #ef4444;">({{ number_format($total - $complete) }} incomplete)</strong>
                @else
                    and 0 incomplete
                @endif
            </span>
        </div>
        <i class="fa-solid fa-shield-check proc-summary-icon" style="color: var(--tz-green);"></i>
    </div>
    
    <div class="proc-summary-card" style="border-left: 3px solid var(--tz-blue);">
        <div class="proc-summary-label">Registered Candidates</div>
        <div class="proc-summary-value">{{ number_format($metrics['registered'] ?? $total) }}</div>
        <div class="proc-summary-sub">
            <i class="fa-solid fa-user-graduate" style="color: var(--tz-blue);"></i>
            <span>PSLE Candidates</span>
        </div>
        <i class="fa-solid fa-user-graduate proc-summary-icon" style="color: var(--tz-blue);"></i>
    </div>
    
    <div class="proc-summary-card" style="border-left: 3px solid var(--tz-yellow);">
        <div class="proc-summary-label">Processing Status</div>
        <div class="proc-summary-value" style="font-size: 1.1rem; margin-top: 6px; height: 34px; display: flex; align-items: center;">
            <span class="badge {{ $statusClass }}" style="font-size: 0.72rem; padding: 5px 12px;">{{ $statusLabel }}</span>
        </div>
        <div class="proc-summary-sub">
            <i class="fa-solid fa-gears" style="color: var(--tz-yellow);"></i>
            <span>Active Lifecycle State</span>
        </div>
        <i class="fa-solid fa-gears proc-summary-icon" style="color: var(--tz-yellow);"></i>
    </div>
    
    <div class="proc-summary-card" style="border-left: 3px solid var(--tz-gold);">
        <div class="proc-summary-label">Publication State</div>
        <div class="proc-summary-value" style="font-size: 1.1rem; margin-top: 6px; height: 34px; display: flex; align-items: center;">
            <span class="badge {{ $pubClass }}" style="font-size: 0.72rem; padding: 5px 12px;">{{ $pubState }}</span>
        </div>
        <div class="proc-summary-sub">
            <i class="fa-solid fa-globe" style="color: var(--tz-gold);"></i>
            <span>Portal Visibility State</span>
        </div>
        <i class="fa-solid fa-globe proc-summary-icon" style="color: var(--tz-gold);"></i>
    </div>
</div>

<!-- Two column layout for Run controls and Snapshot diagnostics -->
<div class="lifecycle-grid">
    <!-- Run Controls Column -->
    <div class="adm-card" style="margin-bottom: 0;">
        <div class="adm-card-head">
            <h3 class="adm-card-title"><i class="fa-solid fa-bolt"></i> Run Controls</h3>
        </div>
        <div class="adm-card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
            <p style="font-size: 0.88rem; color: var(--tz-text-muted); margin: 0; line-height: 1.5;">
                Execute validation sequences and results compilation loops. 
                Running dry draft snapshots allows administrators to inspect rankings and averages before official lockout publishing.
            </p>
            
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Step 1: Validation -->
                <button id="btnValidate" class="btn btn-outline action-card-btn">
                    <i class="fa-solid fa-clipboard-check" style="color: var(--tz-blue); font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                    <span class="action-card-btn-text" style="flex: 1;">
                        <span class="action-card-btn-title">1. Run Pre-Flight Validation Checks</span>
                        <span class="action-card-btn-desc">Inspect scoresheet records for out-of-boundary marks.</span>
                    </span>
                    @if(count($viewData['validationErrors'] ?? []) > 0)
                        <span class="badge badge-red" style="font-size: 0.65rem;"><i class="fa-solid fa-triangle-exclamation"></i> Failures</span>
                    @else
                        <span class="badge badge-green" style="font-size: 0.65rem;"><i class="fa-solid fa-check"></i> Clean</span>
                    @endif
                </button>
                
                <!-- Step 2: Submit & Lock -->
                <button id="btnSubmitLock" class="btn {{ $isRawMarksLocked ? 'btn-outline' : 'btn-primary' }} action-card-btn">
                    <i class="fa-solid fa-lock" style="color: {{ $isRawMarksLocked ? 'var(--tz-green)' : 'var(--tz-yellow)' }}; font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                    <span class="action-card-btn-text" style="flex: 1;">
                        <span class="action-card-btn-title">2. Submit & Lock Raw Marks</span>
                        <span class="action-card-btn-desc">Validate all regions, lock scoresheets, and mark ready.</span>
                    </span>
                    @if($isRawMarksLocked)
                        <span class="badge badge-green" style="font-size: 0.65rem;"><i class="fa-solid fa-lock"></i> Locked</span>
                    @else
                        <span class="badge badge-yellow" style="font-size: 0.65rem;">Pending</span>
                    @endif
                </button>
                
                <!-- Step 3: Draft Run -->
                <button id="btnDraftRun" class="btn {{ $isRawMarksLocked && !$isFinalRunDone ? 'btn-primary' : 'btn-outline' }} action-card-btn" {{ !$isRawMarksLocked ? 'disabled' : '' }}>
                    <i class="fa-solid fa-wand-magic-sparkles" style="color: {{ $isDraftRunDone ? 'var(--tz-green)' : 'inherit' }}; font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                    <span class="action-card-btn-text" style="flex: 1;">
                        <span class="action-card-btn-title">3. Execute Draft Processing Run</span>
                        <span class="action-card-btn-desc">Compile tentative GPAs, ranks, and performance averages.</span>
                    </span>
                    @if($isDraftRunDone)
                        <span class="badge badge-green" style="font-size: 0.65rem;"><i class="fa-solid fa-check"></i> Compiled</span>
                    @else
                        <span class="badge badge-yellow" style="font-size: 0.65rem;">Pending</span>
                    @endif
                </button>
                
                <!-- Step 4: Final Run -->
                <button id="btnFinalRun" class="btn {{ $isRawMarksLocked && !$isFinalRunDone ? 'btn-success' : 'btn-outline' }} action-card-btn" {{ !$isRawMarksLocked ? 'disabled' : '' }}>
                    <i class="fa-solid fa-shield-halved" style="color: {{ $isFinalRunDone ? 'var(--tz-green)' : 'inherit' }}; font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                    <span class="action-card-btn-text" style="flex: 1;">
                        <span class="action-card-btn-title">4. Execute Official Final Calculation & Lock</span>
                        <span class="action-card-btn-desc">Generate immutable snapshot tables and close scoresheet inputs.</span>
                    </span>
                    @if($isFinalRunDone)
                        <span class="badge badge-green" style="font-size: 0.65rem;"><i class="fa-solid fa-lock"></i> Snapshot Created</span>
                    @else
                        <span class="badge badge-yellow" style="font-size: 0.65rem;">Pending</span>
                    @endif
                </button>

                <!-- Step 5: Publish -->
                <button id="btnPublish" class="btn {{ $isFinalRunDone && !$publishedSnapshot ? 'btn-primary' : 'btn-outline' }} action-card-btn" {{ !$isFinalRunDone ? 'disabled' : '' }} style="{{ $isFinalRunDone && !$publishedSnapshot ? 'background: linear-gradient(135deg, var(--tz-gold), #8e732d); border-color: var(--tz-gold);' : '' }}">
                    <i class="fa-solid fa-globe" style="color: {{ $publishedSnapshot ? 'var(--tz-green)' : 'inherit' }}; font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                    <span class="action-card-btn-text" style="flex: 1;">
                        <span class="action-card-btn-title">5. Publish PSLE Snapshot</span>
                        <span class="action-card-btn-desc">Expose final results snapshot to public results and evaluation portals.</span>
                    </span>
                    @if($publishedSnapshot)
                        <span class="badge badge-green" style="font-size: 0.65rem;"><i class="fa-solid fa-globe"></i> Published</span>
                    @else
                        <span class="badge badge-yellow" style="font-size: 0.65rem;">Draft</span>
                    @endif
                </button>
                
                <!-- Rollback -->
                @if($isRawMarksLocked || $isFinalRunDone)
                    <button id="btnRollback" class="btn btn-danger action-card-btn" style="background: linear-gradient(135deg, #7c2d12, #ef4444); margin-top: 10px;">
                        <i class="fa-solid fa-rotate-left" style="font-size: 1.2rem; margin-right: 4px; flex-shrink: 0;"></i>
                        <span class="action-card-btn-text">
                            <span class="action-card-btn-title" style="color: #ffffff;">Rollback to Draft Sequence</span>
                            <span class="action-card-btn-desc" style="color: rgba(255,255,255,0.75);">Unlock candidate parameters and return context to draft state.</span>
                        </span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Snapshot Diagnostics Column -->
    <div style="display: flex; flex-direction: column; gap: 28px;">
        <!-- Diagnostics Card -->
        <div class="adm-card" style="margin-bottom: 0;">
            <div class="adm-card-head">
                <h3 class="adm-card-title"><i class="fa-solid fa-gauge-high"></i> Snapshot Diagnostics</h3>
            </div>
            <div class="adm-card-body" style="padding: 24px;">
                <div style="background: rgba(255,255,255,0.02); padding: 18px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06); margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem; margin-bottom: 8px;">
                        <span style="font-weight: 600; color: #ffffff;">Data Completeness Score</span>
                        <span style="font-weight: 800; font-size: 1.15rem; color: var(--tz-green);">{{ $readyPercent }}%</span>
                    </div>
                    
                    <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden; margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.03);">
                        <div style="width: {{ $readyPercent }}%; height: 100%; background: linear-gradient(90deg, #1eb53a, #6ae086); border-radius: 4px; transition: width 0.6s ease;"></div>
                    </div>
                    
                    <span style="font-size: 0.82rem; color: var(--tz-text-muted); display: block; line-height: 1.45;">
                        <i class="fa-solid fa-{{ $total - $complete > 0 ? 'triangle-exclamation' : 'circle-info' }}" style="color: {{ $total - $complete > 0 ? '#ef4444' : 'var(--tz-blue)' }}; margin-right: 4px;"></i>
                        <strong>{{ number_format($complete) }}</strong> of <strong>{{ number_format($total) }}</strong> candidates complete
                        @if($total - $complete > 0)
                            and <strong style="color: #ef4444;">{{ number_format($total - $complete) }} candidates incomplete</strong>.
                        @else
                            and 0 incomplete.
                        @endif
                    </span>
                    
                    <div style="display: flex; gap: 12px; margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 16px;">
                        <a href="{{ request()->fullUrl() }}" class="btn btn-outline" style="flex: 1; justify-content: center; font-size: 0.8rem;">
                            <i class="fa-solid fa-arrows-rotate"></i> Refresh Status
                        </a>
                        @if(\Illuminate\Support\Facades\Route::has('results.psle.reports.index'))
                            <a href="{{ route('results.psle.reports.index') }}" class="btn btn-warning" style="flex: 1; justify-content: center; font-size: 0.8rem;">
                                <i class="fa-solid fa-file-pdf"></i> Download Reports
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Compliance Checklist -->
        <div class="adm-card" style="margin-bottom: 0;">
            <div class="adm-card-head">
                <h3 class="adm-card-title"><i class="fa-solid fa-list-check" style="color: var(--tz-yellow);"></i> Compliance Checklist</h3>
            </div>
            <div class="adm-card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 14px;">
                <div class="compliance-item">
                    <i class="fa-solid fa-circle-check"></i>
                    <span class="compliance-item-text">
                        All raw marks must fall strictly inside the allowed <strong>0–50 scale</strong> range limit.
                    </span>
                </div>
                <div class="compliance-item">
                    <i class="fa-solid fa-circle-check"></i>
                    <span class="compliance-item-text">
                        Candidate calculations leverage <strong>GPA division</strong> mapping logic based on regional configurations.
                    </span>
                </div>
                <div class="compliance-item">
                    <i class="fa-solid fa-circle-check"></i>
                    <span class="compliance-item-text">
                        Rollback action removes publication snapshots and ranks but securely <strong>retains entered scoresheets</strong>.
                    </span>
                </div>
            </div>
        </div>

        <!-- Portals & Shareable Links Card -->
        <div class="adm-card" style="margin-bottom: 0;">
            <div class="adm-card-head">
                <h3 class="adm-card-title"><i class="fa-solid fa-share-nodes" style="color: var(--tz-blue);"></i> Portals & Public Links</h3>
            </div>
            <div class="adm-card-body" style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">
                <div>
                    <label style="display: block; font-size: 0.72rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px;">Public Results Portal Link</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" readonly value="{{ url('/results/' . $examYear->year_label . '/psle') }}" id="linkPublicPortal" class="adm-input" style="font-family: monospace; font-size: 0.8rem; background: rgba(255,255,255,0.03); flex: 1;">
                        <button onclick="copyToClipboard('linkPublicPortal')" class="btn btn-outline" style="padding: 0 14px;"><i class="fa-regular fa-copy"></i></button>
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 0.72rem; color: var(--tz-text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 6px; letter-spacing: 0.5px;">PSLE Evaluation Link</label>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" readonly value="{{ url('/evaluations/psle') }}" id="linkEvaluationPortal" class="adm-input" style="font-family: monospace; font-size: 0.8rem; background: rgba(255,255,255,0.03); flex: 1;">
                        <button onclick="copyToClipboard('linkEvaluationPortal')" class="btn btn-outline" style="padding: 0 14px;"><i class="fa-regular fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validation boundary errors section -->
<div class="adm-card" style="margin-bottom: 30px;">
    <div class="adm-card-head">
        <h3 class="adm-card-title">
            <i class="fa-solid fa-triangle-exclamation" style="color: var(--tz-yellow);"></i> 
            Active Boundary Validation Failures
        </h3>
        <span style="font-size: 0.78rem; color: var(--tz-text-muted);">Integrity Check logs within active scope</span>
    </div>
    <div class="adm-card-body" style="padding: 0;">
        @if(count($viewData['validationErrors'] ?? []) > 0)
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Candidate index</th>
                            <th>Primary School</th>
                            <th class="text-center">Subject Code</th>
                            <th class="text-center">Entered Mark</th>
                            <th>Failure Boundary Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viewData['validationErrors'] as $err)
                            <tr>
                                <td><strong style="color: var(--tz-yellow);">{{ $err->candidate_cno }}</strong></td>
                                <td>{{ strtoupper($err->school_name) }}</td>
                                <td class="text-center">{{ $err->subject_code }}</td>
                                <td class="text-center" style="color: #fca5a5; font-weight: bold;">{{ $err->mark }}</td>
                                <td>
                                    <span style="color: #fca5a5; font-size: 0.82rem; display: flex; align-items: center; gap: 6px;">
                                        <i class="fa-solid fa-triangle-exclamation"></i> 
                                        Mark falls outside the allowed 0-50 range limits!
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state-container">
                <i class="fa-solid fa-circle-check empty-state-icon"></i>
                <div class="empty-state-title">No Active Boundary Validation Failures Found</div>
                <div class="empty-state-desc">All raw scoresheets inside the active TASIDO scope contain valid marks and are clean.</div>
            </div>
        @endif
    </div>
</div>

<!-- School-Level Rollback & Correction Center -->
<div class="adm-card" style="margin-top: 30px; margin-bottom: 30px;">
    <div class="adm-card-head" style="background: linear-gradient(135deg, rgba(14, 116, 144, 0.15) 0%, rgba(17, 24, 35, 0.6) 100%); border-bottom: 1px solid rgba(14, 116, 144, 0.25);">
        <h3 class="adm-card-title">
            <i class="fa-solid fa-school-flag" style="color: var(--tz-blue);"></i> 
            School-Level Rollback & Correction Center
        </h3>
        <span style="font-size: 0.78rem; color: var(--tz-text-muted);">Manage single school rollback and results correction workflows</span>
    </div>
    <div class="adm-card-body" style="padding: 24px;">
        <div style="display: grid; grid-template-columns: 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Initiate Card Form -->
            <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); padding: 20px; border-radius: 12px;">
                <h4 style="margin: 0 0 12px 0; font-size: 1rem; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-plus-circle" style="color: var(--tz-blue);"></i> Initiate Single School Rollback
                </h4>
                <p style="font-size: 0.82rem; color: var(--tz-text-muted); margin-bottom: 16px;">
                    Rollback a selected school's raw marks to draft sequence. All other schools will remain locked.
                </p>
                <form id="formInitiateCorrection" class="correction-form-grid">
                    @csrf
                    <input type="hidden" name="exam_year_id" value="{{ $examYear->id }}">
                    <div>
                        <label class="adm-label" style="font-size: 0.75rem; margin-bottom: 6px;">Select Primary School</label>
                        <select name="school_id" id="school_id_select" class="adm-input select2" required style="width: 100%; background: #151d21; border-color: rgba(255,255,255,0.12); color: #ffffff;">
                            <option value="">-- Choose School --</option>
                            @foreach($schoolsList as $s)
                                <option value="{{ $s->id }}">{{ $s->code ? $s->code . ' - ' : '' }}{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="adm-label" style="font-size: 0.75rem; margin-bottom: 6px;">Rollback Reason</label>
                        <input type="text" name="reason" class="adm-input" placeholder="e.g. Correct typo in Mathematics marks for index..." required style="width: 100%; background: #151d21; border-color: rgba(255,255,255,0.12); color: #ffffff;">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; height: 38px;">
                            <i class="fa-solid fa-play"></i> Start Correction
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sessions List -->
        <h4 style="margin: 32px 0 16px 0; font-size: 1rem; color: #ffffff; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-list" style="color: var(--tz-yellow);"></i> Active & Historic Correction Batches
        </h4>
        @if(count($viewData['correctionBatches'] ?? []) > 0)
            <div class="table-responsive" style="border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; overflow: hidden;">
                <table style="width: 100%; margin-bottom: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02);">
                            <th>School</th>
                            <th class="text-center">Status</th>
                            <th>Reason & Details</th>
                            <th>Opened By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viewData['correctionBatches'] as $batch)
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                                <td>
                                    <strong style="color: #ffffff;">{{ $batch->school_name_snapshot }}</strong><br>
                                    <span style="font-size: 0.75rem; color: var(--tz-text-muted);">Code: {{ $batch->school_code_snapshot }} | Year: {{ $batch->exam_year }}</span>
                                </td>
                                <td class="text-center">
                                    @if($batch->status === 'open')
                                        <span class="badge badge-yellow" style="font-size: 0.65rem; padding: 4px 8px;">Correction Open</span>
                                    @elseif($batch->status === 'corrected')
                                        <span class="badge badge-blue" style="font-size: 0.65rem; padding: 4px 8px;">Marks Corrected</span>
                                    @elseif($batch->status === 'recalculated')
                                        <span class="badge badge-purple" style="font-size: 0.65rem; padding: 4px 8px; background: #8b5cf6;">Recalculated</span>
                                    @elseif($batch->status === 'republished')
                                        <span class="badge badge-green" style="font-size: 0.65rem; padding: 4px 8px;">Republished</span>
                                    @elseif($batch->status === 'cancelled')
                                        <span class="badge badge-red" style="font-size: 0.65rem; padding: 4px 8px;">Cancelled</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size: 0.82rem; max-width: 300px; word-wrap: break-word; color: #ffffff;">
                                        {{ $batch->reason }}
                                    </div>
                                    @if($batch->status === 'cancelled')
                                        <div style="font-size: 0.75rem; color: #fca5a5; margin-top: 4px;">
                                            Cancellation Reason: {{ $batch->cancellation_reason }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 0.82rem; color: #ffffff;">{{ $batch->openedByUser?->name ?? 'System' }}</span><br>
                                    <span style="font-size: 0.72rem; color: var(--tz-text-muted);">{{ $batch->created_at->format('Y-m-d H:i') }}</span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        @if($batch->status === 'open')
                                            <button class="btn btn-blue btn-sm btnCompleteCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fa-solid fa-check-double"></i> Complete Correction
                                            </button>
                                            <button class="btn btn-danger btn-sm btnCancelCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        @elseif($batch->status === 'corrected')
                                            <button class="btn btn-warning btn-sm btnRecalculateCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fa-solid fa-calculator"></i> Recalculate
                                            </button>
                                            <button class="btn btn-danger btn-sm btnCancelCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        @elseif($batch->status === 'recalculated')
                                            <button class="btn btn-success btn-sm btnRepublishCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px; background: #10b981;">
                                                <i class="fa-solid fa-globe"></i> Republish Results
                                            </button>
                                            <button class="btn btn-danger btn-sm btnCancelCorrection" data-id="{{ $batch->id }}" style="font-size: 0.75rem; padding: 4px 8px;">
                                                <i class="fa-solid fa-ban"></i> Cancel
                                            </button>
                                        @elseif($batch->status === 'republished')
                                            <span style="font-size: 0.75rem; color: var(--tz-green);"><i class="fa-solid fa-circle-check"></i> Republished at {{ $batch->republished_at ? \Carbon\Carbon::parse($batch->republished_at)->format('Y-m-d H:i') : '' }}</span>
                                        @elseif($batch->status === 'cancelled')
                                            <span style="font-size: 0.75rem; color: var(--tz-text-muted);"><i class="fa-solid fa-trash"></i> Cancelled</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="background: rgba(255, 255, 255, 0.01); border: 1px dashed rgba(255, 255, 255, 0.1); padding: 30px; text-align: center; border-radius: 8px;">
                <i class="fa-solid fa-school-circle-check" style="font-size: 2rem; color: var(--tz-text-muted); margin-bottom: 12px; display: block;"></i>
                <span style="font-size: 0.88rem; color: var(--tz-text-muted);">No correction batches registered for this academic year.</span>
            </div>
        @endif
    </div>
</div>

<!-- Scripts for ajax processing triggers -->
<script>
    function copyToClipboard(id) {
        var copyText = document.getElementById(id);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);
        
        Swal.fire({
            title: 'Copied!',
            text: 'Link copied to clipboard successfully.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            background: '#101518',
            color: '#f0f4f7'
        });
    }

    $(document).ready(function() {
        const swalConfig = {
            background: '#101518',
            color: '#f0f4f7',
            confirmButtonColor: '#00a3dd',
            cancelButtonColor: '#ef4444'
        };

        $('#btnValidate').click(function() {
            Swal.fire({
                title: 'Data Validation',
                text: 'Checking all raw scoresheets in TASIDO scope for boundary violations...',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Run Check',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Running...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.validate") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Validation Failed', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('#btnSubmitLock').click(function() {
            Swal.fire({
                title: 'Submit & Lock Raw Marks',
                text: 'Warning: This will validate and lock all raw marks across all TASIDO regions. Nobody will be able to edit scoresheets. Proceed?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Submit & Lock',
                confirmButtonColor: '#ffd875',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Submitting and Locking...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.submit-lock") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('#btnDraftRun').click(function() {
            Swal.fire({
                title: 'Draft Computation',
                text: 'This will run draft GPA standing tables and rankings for all primary candidates. Continue?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Run Draft',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.draft-run") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Completed', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('#btnFinalRun').click(function() {
            Swal.fire({
                title: 'Official Lock & Calculate',
                text: 'Executing this will lock the scoresheets and build permanent snapshot databases. This is a secure operational step!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Lock & Calc',
                confirmButtonColor: '#1eb53a',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Finalizing Calculation...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.final-run") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Official Locked', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('#btnPublish').click(function() {
            Swal.fire({
                title: 'Publish Results Snapshot',
                text: 'Are you sure you want to publish the active PSLE results snapshot? This will make them visible on public search portals.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Publish Now',
                confirmButtonColor: '#bba45e',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Publishing snapshot...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.publish") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('#btnRollback').click(function() {
            Swal.fire({
                title: 'Execute Snapshot Rollback',
                text: 'Warning! This rolls back the active calculations. It does not delete entered marks.',
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: 'Rollback',
                confirmButtonColor: '#ef4444',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Rolling back snapshot...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.processing.rollback") }}', { 
                        _token: '{{ csrf_token() }}',
                        exam_year_id: '{{ $examYear->id }}'
                    }, function(res) {
                        Swal.fire({ title: 'Restored', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        // School-level correction AJAX handlers
        $('#formInitiateCorrection').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            Swal.fire({
                title: 'Initiate School Correction',
                text: 'Are you sure you want to rollback this school? This will unlock raw marks only for the selected school, mark it as "under correction", and temporarily suspend public/evaluation portals for the year.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Start Correction',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Initiating rollback...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.correction.initiate") }}', form.serialize(), function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('.btnCompleteCorrection').click(function() {
            var batchId = $(this).data('id');
            Swal.fire({
                title: 'Complete Correction Phase',
                text: 'This will lock the scoresheet for the school and mark the correction phase as completed. Proceed?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Complete',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Locking scoresheet...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.correction.complete") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: batchId
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('.btnRecalculateCorrection').click(function() {
            var batchId = $(this).data('id');
            Swal.fire({
                title: 'Recalculate Year-level Results',
                text: 'This will run year-level calculations to update all rankings, averages, and subject summaries. Proceed?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Recalculate Now',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Recalculating results...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.correction.recalculate") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: batchId
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('.btnRepublishCorrection').click(function() {
            var batchId = $(this).data('id');
            Swal.fire({
                title: 'Republish Results',
                text: 'This will restore public and evaluation portal access with the newly corrected results. Proceed?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Republish',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'Republishing results...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.correction.republish") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: batchId
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        $('.btnCancelCorrection').click(function() {
            var batchId = $(this).data('id');
            Swal.fire({
                title: 'Cancel Correction Batch',
                text: 'Please specify the cancellation reason (minimum 5 characters):',
                input: 'text',
                inputPlaceholder: 'Reason for cancellation...',
                inputAttributes: {
                    required: 'true',
                    minlength: '5'
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cancel Batch',
                confirmButtonColor: '#ef4444',
                ...swalConfig
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({ title: 'Cancelling batch...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    $.post('{{ route("results.psle.correction.cancel") }}', {
                        _token: '{{ csrf_token() }}',
                        batch_id: batchId,
                        reason: result.value
                    }, function(res) {
                        Swal.fire({ title: 'Success', text: res.message, icon: 'success', ...swalConfig }).then(() => {
                            window.location.reload();
                        });
                    }).fail(function(xhr) {
                        Swal.fire({ title: 'Error', text: xhr.responseJSON?.message || 'Action failed.', icon: 'error', ...swalConfig });
                    });
                }
            });
        });

        // Initialize Select2 on the school dropdown
        if ($.fn.select2) {
            $('#school_id_select').select2({
                placeholder: '-- Choose School --',
                allowClear: false,
                width: '100%',
                theme: 'admin-dark'
            });
        }
    });
</script>
