@php
    $isReadOnly = $isAdmin || $isReo;
    $subjectId = $activeFilters['subject_id'] ?? request('subject_id');
    $subject = $subjectId ? \App\Models\Subject::find($subjectId) : null;
    $maxScore = $subject ? $subject->max_marks : 50;
@endphp
<div class="adm-breadcrumb">
    EXAMINATIONS <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> Mark Entry <i class="fas fa-chevron-right" style="font-size:0.6rem; margin:0 4px;"></i> <span>Entry Sheet</span>
</div>

<link href="https://fonts.cdnfonts.com/css/maiandra-gd" rel="stylesheet">

<style>
    #entry-sheet-table td, #entry-sheet-table th {
        padding: 4px 8px !important;
        font-size: 0.8rem;
        height: auto !important;
    }
    #entry-sheet-table tr {
        height: 36px !important;
    }
    .mark-input {
        height: 28px !important;
        font-size: 0.85rem !important;
        font-weight: 600;
    }
    .mark-input-error {
        border-color: var(--tz-red) !important;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
    }
    .mark-input-save-error {
        border-color: var(--tz-yellow) !important;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.18) !important;
    }
    .candidate-name {
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    code {
        font-size: 0.9rem !important;
        color: var(--tz-blue);
    }
</style>

<div class="adm-page-header">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1 class="adm-page-title">Entry Sheet: {{ $assignment->subject->name ?? \App\Models\Subject::find(request('subject_id'))->name ?? 'N/A' }}</h1>
            <p class="adm-page-desc">
                <strong>School:</strong> {{ $assignment->school->name ?? \App\Models\School::find(request('school_id'))->name ?? 'N/A' }} 
                ({{ $assignment->school->code ?? \App\Models\School::find(request('school_id'))->code ?? 'N/A' }})
                @if($assignment && $assignment->markingCentre)
                    | <strong>Centre:</strong> {{ $assignment->markingCentre->name }}
                @endif
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            @if(!$isReadOnly)
            <button type="button" id="retry-failed-saves-btn" class="btn btn-outline btn-sm" onclick="retryFailedSaves()" style="display:none;">
                <i class="fas fa-rotate-right"></i> Retry Failed Saves <span id="failed-save-count">0</span>
            </button>
            @endif
            <a href="{{ url('/mark-entry/psle' . ($isReadOnly ? '?view=entry-validation' : '?view=start-entry')) }}" class="btn btn-outline btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Workspace
            </a>
            @if(!$isReadOnly)
            <button type="button" class="btn btn-primary btn-sm" onclick="saveAllMarks()">
                <i class="fas fa-save"></i> Save All Changes
            </button>
            @endif
        </div>
    </div>
</div>

<div class="adm-stats" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
    <div class="adm-stat">
        <div class="adm-stat-label">Total Candidates</div>
        <div class="adm-stat-value" id="stats-total">{{ count($candidates ?? []) }}</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Marks Entered</div>
        <div class="adm-stat-value" style="color: var(--tz-green);" id="stats-entered">0</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-label">Completion</div>
        <div class="adm-stat-value" style="color: var(--tz-blue);" id="stats-percent">0%</div>
    </div>
</div>

@if($isReadOnly)
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-eye text-red-400" style="font-size: 1.2rem; color: #f87171;"></i>
        <span style="color: #f87171; font-weight: 600; font-size: 0.9rem;">
            Read-only review mode. Admin and REO users cannot enter or modify marks.
        </span>
    </div>
@endif

<!-- Entry Table -->
<div class="adm-card">
    <div class="adm-card-head" style="display: flex; justify-content: space-between; align-items: center;">
        <div class="adm-card-title">Candidate Mark List</div>
        @if($isReadOnly)
        <div style="font-size: 0.85rem; color: #f87171;">
            <i class="fas fa-eye"></i> Read-only Mode
        </div>
        @else
        <div style="font-size: 0.85rem; color: var(--tz-yellow);">
            <i class="fas fa-info-circle"></i> Marks are saved automatically as you type.
        </div>
        @endif
    </div>
    <div class="adm-card-body table-responsive" style="max-height: 600px; overflow-y: auto;">
        <table id="entry-sheet-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="position: sticky; top: 0; background: #1a1a1a; z-index: 10;">
                    <th width="40" style="white-space: nowrap;">SN</th>
                    <th width="180" class="text-center" style="white-space: nowrap;">INDEX NUMBER</th>
                    <th width="140" class="text-center" style="white-space: nowrap;">PReM NUMBER</th>
                    <th style="white-space: nowrap;">CANDIDATE NAME</th>
                    <th width="60" class="text-center" style="white-space: nowrap;">SEX</th>
                    <th width="140" class="text-center" style="white-space: nowrap;">SCORE (MAX {{ $maxScore }})</th>
                    <th width="120" class="text-center" style="white-space: nowrap;">STATUS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($candidates ?? [] as $index => $c)
                @php
                    $existingMark = $c->rawMarks
                        ->where('subject_id', $activeFilters['subject_id'])
                        ->where('exam_year_id', $activeFilters['exam_year_id'])
                        ->first();
                    $score = '';
                    $status = 'Pending';
                    $badgeClass = 'badge-yellow';
                    
                    if ($existingMark) {
                        if ($existingMark->subject_status === 'ABS') {
                            $score = 'ABS';
                            $status = 'ABS';
                            $badgeClass = 'badge-red';
                        } elseif ($existingMark->paper_1_marks !== null && $existingMark->paper_1_marks !== '') {
                            $score = $existingMark->paper_1_marks;
                            $status = 'Entered';
                            $badgeClass = 'badge-green';
                        } elseif ($existingMark->subject_status === 'INC') {
                            $score = 'INC';
                            $status = 'INC';
                            $badgeClass = 'badge-yellow';
                        }
                    }
                    $reg = $c->examRegistrations->where('exam_year_id', $activeFilters['exam_year_id'])->first();
                    $displayIndexNumber = $c->candidate_id ?? null;
                @endphp
                <tr data-candidate-id="{{ $c->id }}" data-row-index="{{ $index }}">
                    <td style="white-space: nowrap;">{{ $index + 1 }}</td>
                    <td class="text-center" style="white-space: nowrap;">
                        @if($displayIndexNumber)
                            <code>{{ str_replace('PSLE-', '', $displayIndexNumber) }}</code>
                        @else
                            <span class="text-warning font-semibold" title="Official candidate number missing. Verify PSLE Pupil Register.">
                                <i class="fas fa-exclamation-triangle"></i> Official candidate number missing. Verify PSLE Pupil Register.
                            </span>
                        @endif
                    </td>
                    <td class="text-center" style="white-space: nowrap;"><code>{{ $c->prem_no ?? 'N/A' }}</code></td>
                    <td class="candidate-name" style="white-space: nowrap; font-weight: 500;">{{ $c->full_name }}</td>
                    <td class="text-center" style="white-space: nowrap;">{{ $c->gender }}</td>
                    <td class="text-center" style="white-space: nowrap;">
                        <input type="text" 
                               {{ $isReadOnly ? 'disabled' : '' }}
                               class="mark-input adm-select" 
                               data-candidate-id="{{ $c->id }}"
                               data-row-index="{{ $index }}"
                               data-last-saved-value="{{ $score }}"
                               value="{{ $score }}" 
                               min="0" max="{{ $maxScore }}" step="0.5"
                               placeholder="0-{{ $maxScore }}"
                               style="width: 80px; text-align: center; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: {{ ($score === 'ABS') ? 'var(--tz-red)' : (($score === 'INC') ? 'var(--tz-yellow)' : '#fff') }};"
                               oninput="queueMarkSave(this, {{ $c->id }})"
                               onblur="flushMarkSave(this, {{ $c->id }})"
                               onkeydown="handleNavigation(event, this)">
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <span class="mark-status badge {{ $badgeClass }}" style="font-size: 0.7rem; {{ $status === 'INC' || $status === 'ABS' ? 'cursor: pointer;' : '' }}">
                            {{ $status }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr class="empty-state-row">
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fas fa-user-slash empty-icon"></i>
                            <div class="empty-title">No Candidates Found</div>
                            <div class="empty-desc">No candidates are registered for this school and subject selection.</div>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
const readOnlyMode = {{ $isReadOnly ? 'true' : 'false' }};
const psleSaveTimers = new Map();
const psleSaveStates = new Map();
const psleLastSavedValues = new Map();
const PSLE_SAVE_DEBOUNCE_MS = 150;
const PSLE_SAVE_RETRY_DELAYS = [1000, 3000, 7000];
const PSLE_SAVE_TIMEOUT_MS = 20000;
let psleCompletionNotified = false;

document.addEventListener('DOMContentLoaded', function() {
    updateStats();
    document.querySelectorAll('.mark-input').forEach(input => {
        const candidateId = input.closest('tr')?.dataset.candidateId;
        if (candidateId) {
            psleLastSavedValues.set(saveStateKey(candidateId), input.value.trim());
            input.dataset.lastSavedValue = input.value.trim();
            input.dataset.saveStatus = input.value.trim() !== '' ? 'saved' : 'idle';
        }
    });
    document.querySelectorAll('.mark-status').forEach(badge => {
        badge.addEventListener('click', function() {
            if (readOnlyMode) return;
            const row = badge.closest('tr');
            const input = row?.querySelector('.mark-input');
            if (input) {
                input.focus();
                input.select();
            }
        });
    });
});

function updateStats() {
    const inputs = document.querySelectorAll('.mark-input');
    const total = inputs.length;
    let entered = 0;
    
    inputs.forEach(input => {
        if (input.value !== '' && input.value !== null) {
            entered++;
        }
    });
    
    const percent = total > 0 ? Math.round((entered / total) * 100) : 0;
    
    document.getElementById('stats-total').innerText = total;
    document.getElementById('stats-entered').innerText = entered;
    document.getElementById('stats-percent').innerText = percent + '%';
}

function saveStateKey(candidateId) {
    return [
        '{{ request("exam_year_id") }}',
        '{{ request("school_id") }}',
        '{{ request("subject_id") }}',
        candidateId
    ].join(':');
}

function queueMarkSave(input, candidateId) {
    if (readOnlyMode) return;
    const key = saveStateKey(candidateId);
    let score = input.value.trim();
    
    // Apply immediate visual styling when typing
    let scoreUpper = score.toUpperCase();
    if (scoreUpper === 'ABS' || score === '') {
        input.style.color = 'var(--tz-red)';
        setMarkStatus(input, 'ABS', 'badge-red');
    } else if (scoreUpper === 'INC') {
        input.style.color = 'var(--tz-yellow)';
        setMarkStatus(input, 'INC', 'badge-yellow');
    } else {
        const numScore = parseFloat(score);
        if (!isNaN(numScore) && numScore >= 0 && numScore <= 50) {
            input.style.color = '#fff';
            setMarkStatus(input, 'Saving...', 'badge-yellow');
        } else {
            input.style.color = '#fff';
        }
    }

    const state = psleSaveStates.get(key) || {};
    if (!state.inFlight && !input.classList.contains('mark-input-save-error') && psleLastSavedValues.get(key) === score) {
        return;
    }

    psleCompletionNotified = false;
    input.dataset.saveStatus = 'dirty';
    clearTimeout(psleSaveTimers.get(key));
    clearMarkInputError(input);
    clearMarkSaveError(input);
    
    if (score !== '' && scoreUpper !== 'ABS' && scoreUpper !== 'INC') {
        setMarkStatus(input, 'Saving...', 'badge-yellow');
    }
    
    psleSaveTimers.set(key, setTimeout(() => saveMark(input, candidateId), PSLE_SAVE_DEBOUNCE_MS));
    updateStats();
}

function flushMarkSave(input, candidateId) {
    if (readOnlyMode) return;
    const key = saveStateKey(candidateId);
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

function saveRow(input) {
    if (readOnlyMode) return;
    const candidateId = input.dataset.candidateId || input.closest('tr')?.dataset.candidateId;
    if (!candidateId) return;

    const key = saveStateKey(candidateId);
    if (psleSaveTimers.has(key)) {
        clearTimeout(psleSaveTimers.get(key));
        psleSaveTimers.delete(key);
    }

    saveMark(input, candidateId);
}

function saveMark(input, candidateId, attempt = 0) {
    if (readOnlyMode) return;
    const key = saveStateKey(candidateId);
    let score = input.value.trim();
    const maxScore = parseFloat(input.max || '50');
    const minScore = parseFloat(input.min || '0');
    
    let scoreUpper = score.toUpperCase();
    if (scoreUpper === 'ABS' || score === '') {
        score = 'ABS';
        input.value = 'ABS';
        input.style.color = 'var(--tz-red)';
        setMarkStatus(input, 'ABS', 'badge-red');
    } else if (scoreUpper === 'INC') {
        score = 'INC';
        input.value = 'INC';
        input.style.color = 'var(--tz-yellow)';
        setMarkStatus(input, 'INC', 'badge-yellow');
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
    
    if (score === '0-50' || score === `0-${maxScore}`) {
        score = 'ABS';
        input.value = 'ABS';
        input.style.color = 'var(--tz-red)';
        setMarkStatus(input, 'ABS', 'badge-red');
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
        if (score === 'ABS') {
            setMarkStatus(input, 'ABS', 'badge-red');
        } else if (score === 'INC') {
            setMarkStatus(input, 'INC', 'badge-yellow');
        } else {
            setMarkStatus(input, 'Entered', 'badge-green');
        }
        checkCompletion();
        return;
    }
    
    // UI Feedback: Loading state
    clearMarkInputError(input);
    clearMarkSaveError(input);
    input.style.borderColor = 'var(--tz-blue)';
    input.dataset.saveStatus = 'saving';
    setMarkStatus(input, attempt > 0 ? `Retrying ${attempt}/3...` : 'Saving...', 'badge-yellow');
    
    // Dynamic payload structure
    let scoreToSend = score;
    if (score !== 'ABS' && score !== 'INC') {
        scoreToSend = Number(score);
    }
    
    const payload = {
        candidate_id: candidateId,
        score: scoreToSend
    };
    
    const assignmentId = '{{ $assignment->id ?? "" }}';
    if (assignmentId && assignmentId !== '') {
        payload.assignment_id = assignmentId;
    } else {
        payload.school_id = '{{ $activeFilters["school_id"] ?? "" }}';
        payload.subject_id = '{{ $activeFilters["subject_id"] ?? "" }}';
        payload.exam_year_id = '{{ $activeFilters["exam_year_id"] ?? "" }}';
    }

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
                // Response is not JSON (likely HTML error page)
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
            if (score === 'ABS') {
                setMarkStatus(input, 'ABS', 'badge-red');
            } else if (score === 'INC') {
                setMarkStatus(input, 'INC', 'badge-yellow');
            } else {
                setMarkStatus(input, 'Entered', 'badge-green');
            }
            updateStats();
            updateFailedSaveSummary();
            checkCompletion(data.completion || null);
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

        if (err.status === 423 && err.code === 'MARK_ENTRY_LOCATION_REQUIRED') {
            setMarkSaveError(input, err.message || 'Location verification required.');
            handleLocationExpired(err.message);
        } else if (err.status === 422) {
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
        updateFailedSaveSummary();
    });
}

function setMarkStatus(input, text, badgeClass) {
    const statusSpan = input.closest('tr')?.querySelector('.mark-status');
    if (!statusSpan) return;
    statusSpan.textContent = text;
    statusSpan.className = `mark-status badge ${badgeClass}`;
    if (text === 'INC' || text === 'ABS') {
        statusSpan.style.cursor = 'pointer';
    } else {
        statusSpan.style.cursor = '';
    }
}

function setMarkInputError(input, message) {
    input.classList.add('mark-input-error');
    input.dataset.saveStatus = 'invalid';
    psleCompletionNotified = false;
    input.dataset.validationError = message || 'Validation error';
    input.style.borderColor = 'var(--tz-red)';
    setMarkStatus(input, 'Invalid', 'badge-red');
}

function clearMarkInputError(input) {
    input.classList.remove('mark-input-error');
    delete input.dataset.validationError;
}

function setMarkSaveError(input, message, retrying = false) {
    psleCompletionNotified = false;
    input.dataset.saveError = message || 'The mark was not saved.';
    input.style.borderColor = 'var(--tz-yellow)';
    setMarkStatus(input, retrying ? 'Retrying...' : 'Failed - retry', 'badge-yellow');
    if (retrying) {
        input.dataset.saveStatus = 'saving';
        input.classList.remove('mark-input-save-error');
    } else {
        input.dataset.saveStatus = 'failed';
        input.classList.add('mark-input-save-error');
    }
    updateFailedSaveSummary();
}

function clearMarkSaveError(input) {
    input.classList.remove('mark-input-save-error');
    delete input.dataset.saveError;
    updateFailedSaveSummary();
}

function shouldRetrySave(err) {
    return err.name === 'AbortError' || [502, 503, 504].includes(Number(err.status));
}

function errorTitleForStatus(status) {
    if (status === 422) return 'Validation Error';
    if (status === 403) return 'Not Allowed';
    if (status === 419) return 'Session Expired';
    if ([502, 503, 504].includes(Number(status))) return 'Server Busy';
    if (!status) return 'Network Timeout';
    return 'Saving Failed';
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

function updateFailedSaveSummary() {
    const failed = document.querySelectorAll('.mark-input-save-error').length;
    const button = document.getElementById('retry-failed-saves-btn');
    const count = document.getElementById('failed-save-count');
    if (!button || !count) return;
    count.textContent = failed;
    button.style.display = failed > 0 ? 'inline-flex' : 'none';
}

function retryFailedSaves() {
    if (readOnlyMode) return;
    const failedInputs = Array.from(document.querySelectorAll('.mark-input-save-error'));
    failedInputs.forEach(input => {
        const candidateId = input.closest('tr')?.dataset.candidateId;
        if (!candidateId) return;
        saveMark(input, candidateId);
    });
}

function moveToNextInput(currentInput) {
    const inputs = Array.from(document.querySelectorAll('.mark-input'));
    const index = inputs.indexOf(currentInput);
    if (index >= 0 && index < inputs.length - 1) {
        inputs[index + 1].focus();
        inputs[index + 1].select();
    }
}

function moveToPreviousInput(currentInput) {
    const inputs = Array.from(document.querySelectorAll('.mark-input'));
    const index = inputs.indexOf(currentInput);
    if (index > 0) {
        inputs[index - 1].focus();
        inputs[index - 1].select();
    }
}

function handleNavigation(event, currentInput) {
    const row = currentInput.closest('tr');
    const candidateId = row?.dataset.candidateId;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        event.stopPropagation();
        if (candidateId) saveRow(currentInput);
        moveToNextInput(currentInput);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        event.stopPropagation();
        if (candidateId) saveRow(currentInput);
        moveToPreviousInput(currentInput);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        event.stopPropagation();
        if (candidateId) saveRow(currentInput);
        moveToNextInput(currentInput);
    } else if (event.key === 'Tab') {
        if (candidateId) saveRow(currentInput);
    }
}

function checkCompletion(serverCompletion = null) {
    const inputs = Array.from(document.querySelectorAll('.mark-input'));
    if (inputs.length === 0 || psleCompletionNotified) return;

    const complete = inputs.every(input => {
        const candidateId = input.closest('tr')?.dataset.candidateId;
        const key = candidateId ? saveStateKey(candidateId) : null;
        const value = input.value.trim().toUpperCase();
        if (value === 'ABS' || value === 'INC') {
            return input.dataset.saveStatus === 'saved'
                && key
                && psleLastSavedValues.get(key) === value
                && !input.classList.contains('mark-input-error')
                && !input.classList.contains('mark-input-save-error');
        }
        const maxScore = parseFloat(input.max || '50');
        const minScore = parseFloat(input.min || '0');
        const numericValue = parseFloat(value);

        return value !== ''
            && !Number.isNaN(numericValue)
            && numericValue >= minScore
            && numericValue <= maxScore
            && input.dataset.saveStatus === 'saved'
            && key
            && psleLastSavedValues.get(key) === input.value.trim()
            && !input.classList.contains('mark-input-error')
            && !input.classList.contains('mark-input-save-error');
    });

    if (!complete) return;

    if (serverCompletion && serverCompletion.is_complete === false) {
        return;
    }

    psleCompletionNotified = true;
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'All marks for this score sheet have been entered successfully.',
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
        background: '#161b22',
        color: '#f0f4f7'
    });
}

function saveAllMarks() {
    if (readOnlyMode) return;
    document.querySelectorAll('.mark-input').forEach(input => {
        const candidateId = input.closest('tr')?.dataset.candidateId;
        if (candidateId) flushMarkSave(input, candidateId);
    });

    const inputs = document.querySelectorAll('.mark-input');
    const total = inputs.length;
    let entered = 0;
    const errorInputs = [];
    const unsavedInputs = [];
    
    inputs.forEach(input => {
        if (input.value !== '' && input.value !== null) {
            entered++;
        }
        if (input.classList.contains('mark-input-error')) {
            errorInputs.push(input);
        }
        if (input.classList.contains('mark-input-save-error')) {
            unsavedInputs.push(input);
        }
    });
    
    const percent = total > 0 ? Math.round((entered / total) * 100) : 0;
    
    if (unsavedInputs.length > 0) {
        const firstError = unsavedInputs[0];
        const row = firstError.closest('tr');
        const indexNumber = row?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'Unknown candidate';
        const candidateName = row?.querySelector('.candidate-name')?.textContent?.trim() || '';
        const reason = firstError.dataset.saveError || 'The mark was not saved.';

        Swal.fire({
            icon: 'warning',
            title: 'Unsaved Marks',
            html: `You have <strong>${unsavedInputs.length}</strong> unsaved field(s).<br><br><strong>First issue:</strong> ${indexNumber} ${candidateName}<br>${reason}`,
            confirmButtonColor: 'var(--tz-yellow)',
            background: '#161b22',
            color: '#f0f4f7'
        }).then(() => {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    } else if (errorInputs.length > 0) {
        const firstError = errorInputs[0];
        const row = firstError.closest('tr');
        const indexNumber = row?.querySelector('td:nth-child(2)')?.textContent?.trim() || 'Unknown candidate';
        const candidateName = row?.querySelector('.candidate-name')?.textContent?.trim() || '';
        const reason = firstError.dataset.validationError || 'Validation error';

        Swal.fire({
            icon: 'warning',
            title: 'Unresolved Errors',
            html: `You have <strong>${errorInputs.length}</strong> field(s) with validation errors.<br><br><strong>First issue:</strong> ${indexNumber} ${candidateName}<br>${reason}`,
            confirmButtonColor: 'var(--tz-yellow)',
            background: '#161b22',
            color: '#f0f4f7'
        }).then(() => {
            firstError.focus();
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    } else {
        Swal.fire({
            icon: 'success',
            title: 'Synchronization Check',
            text: `All ${entered} entered marks are successfully synchronized with the server (${percent}% complete).`,
            confirmButtonColor: 'var(--tz-green)',
            background: '#161b22',
            color: '#f0f4f7'
        });
    }
}

let locationRecheckInFlight = false;

function handleLocationExpired(message) {
    if (locationRecheckInFlight) return;
    locationRecheckInFlight = true;

    Swal.fire({
        title: 'Location Verification Required',
        text: message || 'Your location verification has expired. We need to quickly re-verify your device is still at the marking centre to continue.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Verify Location Now',
        cancelButtonText: 'Cancel & Logout',
        confirmButtonColor: 'var(--tz-blue)',
        cancelButtonColor: 'var(--tz-red)',
        background: '#161b22',
        color: '#f0f4f7',
        allowOutsideClick: false,
        allowEscapeKey: false
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Acquiring GPS...',
                text: 'Please allow location permissions if prompted.',
                allowOutsideClick: false,
                background: '#161b22',
                color: '#f0f4f7',
                didOpen: () => {
                    Swal.showLoading();
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            fetch("{{ route('mark-entry.location.verify.submit') }}", {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    latitude: position.coords.latitude,
                                    longitude: position.coords.longitude,
                                    accuracy: position.coords.accuracy
                                })
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.ok) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Verified!',
                                        text: 'Your location is verified successfully. Autosave resuming.',
                                        timer: 2000,
                                        showConfirmButton: false,
                                        background: '#161b22',
                                        color: '#f0f4f7'
                                    }).then(() => {
                                        locationRecheckInFlight = false;
                                        // Retry all failed/stuck saves!
                                        retryFailedSaves();
                                    });
                                } else {
                                    throw new Error(data.message || 'Verification failed');
                                }
                            })
                            .catch(err => {
                                locationRecheckInFlight = false;
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Verification Failed',
                                    text: err.message,
                                    confirmButtonText: 'Retry',
                                    background: '#161b22',
                                    color: '#f0f4f7'
                                }).then(() => {
                                    handleLocationExpired(err.message);
                                });
                            });
                        },
                        function(geoErr) {
                            locationRecheckInFlight = false;
                            Swal.fire({
                                icon: 'error',
                                title: 'GPS Error',
                                text: 'Failed to acquire location. Please enable location services.',
                                confirmButtonText: 'Retry',
                                background: '#161b22',
                                color: '#f0f4f7'
                            }).then(() => {
                                handleLocationExpired();
                            });
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                }
            });
        } else {
            // Logout
            const logoutForm = document.createElement('form');
            logoutForm.method = 'POST';
            logoutForm.action = '{{ route("logout") }}';
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            logoutForm.appendChild(csrfInput);
            document.body.appendChild(logoutForm);
            logoutForm.submit();
        }
    });
}
</script>
