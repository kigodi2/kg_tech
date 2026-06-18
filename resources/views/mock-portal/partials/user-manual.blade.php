@php
    $manualId = $manualId ?? 'mockPortalManual';
    $manualTitle = $manualTitle ?? 'User Manual';
    $manualSummary = $manualSummary ?? '';
    $manualSteps = $manualSteps ?? [];
    $manualNotes = $manualNotes ?? [];
    $manualPdf = $manualPdf ?? null;
    $manualButtonLabel = $manualButtonLabel ?? 'User Manual';
    $manualButtonIcon = $manualButtonIcon ?? 'fa-book-open';
    $manualButtonClass = $manualButtonClass ?? 'manual-fab';
    $manualJsId = preg_replace('/[^A-Za-z0-9_]/', '_', $manualId);
@endphp

<style>
    .manual-fab {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 1400;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 18px;
        border-radius: 999px;
        border: 1px solid rgba(187,164,94,.24);
        background: linear-gradient(135deg, rgba(0,163,221,.96), rgba(0,111,163,.96));
        color: #fff;
        font-size: .85rem;
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 18px 36px rgba(0,0,0,.28), 0 10px 24px rgba(0,163,221,.22);
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }
    .manual-fab:hover {
        transform: translateY(-2px);
        box-shadow: 0 22px 40px rgba(0,0,0,.34), 0 12px 28px rgba(0,163,221,.28);
        opacity: .98;
    }
    .manual-fab i { font-size: .95rem; }
    .manual-overlay {
        position: fixed;
        inset: 0;
        z-index: 1450;
        background: rgba(0,0,0,.82);
        backdrop-filter: blur(5px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }
    .manual-shell {
        width: 100%;
        max-width: 780px;
        background: #101518;
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 30px 80px rgba(0,0,0,.5);
    }
    .manual-head {
        padding: 20px 24px;
        border-bottom: 1px solid rgba(255,255,255,.06);
        background: linear-gradient(135deg, rgba(187,164,94,.08), rgba(0,163,221,.04));
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .manual-head h3 {
        margin: 0;
        font-size: 1.08rem;
        color: #f0e6c8;
        font-weight: 800;
    }
    .manual-close {
        width: 38px;
        height: 38px;
        border: 0;
        border-radius: 10px;
        background: rgba(255,255,255,.04);
        color: #9ca3af;
        cursor: pointer;
        transition: background .18s ease, color .18s ease, transform .18s ease;
    }
    .manual-close:hover { background: rgba(255,255,255,.1); color: #fff; transform: rotate(90deg); }
    .manual-body {
        padding: 22px 24px;
        max-height: min(76vh, 720px);
        overflow-y: auto;
    }
    .manual-summary {
        margin: 0 0 18px;
        color: #d1d5db;
        line-height: 1.7;
        font-size: .92rem;
    }
    .manual-steps {
        display: grid;
        gap: 14px;
    }
    .manual-step {
        display: grid;
        grid-template-columns: 42px 1fr;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 14px;
        background: linear-gradient(160deg, rgba(255,255,255,.03), rgba(255,255,255,.015));
        border: 1px solid rgba(255,255,255,.07);
    }
    .manual-step-no {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #00A3DD, #006fa3);
        color: #fff;
        font-size: .95rem;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(0,163,221,.22);
    }
    .manual-step h4 {
        margin: 0 0 6px;
        color: #f0e6c8;
        font-size: .98rem;
        font-weight: 800;
    }
    .manual-step p {
        margin: 0;
        color: #d7dde5;
        line-height: 1.7;
        font-size: .9rem;
    }
    .manual-notes {
        margin-top: 18px;
        padding: 16px 18px;
        border-radius: 14px;
        background: rgba(252,209,22,.08);
        border: 1px solid rgba(252,209,22,.18);
        color: #f7e4a1;
        display: grid;
        gap: 8px;
        font-size: .88rem;
        line-height: 1.65;
    }
    .manual-notes strong { color: #FCD116; }
    .manual-actions {
        padding: 16px 24px 22px;
        border-top: 1px solid rgba(255,255,255,.06);
        background: rgba(255,255,255,.02);
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
    }
    .manual-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,.1);
        background: rgba(255,255,255,.04);
        color: #f0f4f7;
        text-decoration: none;
        font-size: .84rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
    }
    .manual-btn:hover { transform: translateY(-1px); background: rgba(255,255,255,.08); box-shadow: 0 10px 24px rgba(0,0,0,.2); }
    .manual-btn-primary { background: linear-gradient(135deg, #00A3DD, #006fa3); border-color: rgba(0,163,221,.32); color: #fff; }
    .manual-btn-primary:hover { background: linear-gradient(135deg, #14b2f1, #007bb5); }

    @media (max-width: 768px) {
        .manual-fab {
            right: 14px;
            bottom: 14px;
            padding: 11px 15px;
            font-size: .8rem;
        }
        .manual-step { grid-template-columns: 1fr; }
        .manual-step-no { width: 38px; height: 38px; }
        .manual-body { padding: 18px; max-height: 74vh; }
        .manual-actions { padding: 16px; }
    }
</style>

<button type="button" class="{{ $manualButtonClass }}" onclick="openManual_{{ $manualJsId }}()">
    <i class="fas {{ $manualButtonIcon }}"></i>
    <span>{{ $manualButtonLabel }}</span>
</button>

<div id="{{ $manualId }}" class="manual-overlay" aria-hidden="true">
    <div class="manual-shell">
        <div class="manual-head">
            <h3>{{ $manualTitle }}</h3>
            <button type="button" class="manual-close" onclick="closeManual_{{ $manualJsId }}()"><i class="fas fa-times"></i></button>
        </div>
        <div class="manual-body" id="{{ $manualId }}_print">
            @if($manualSummary)
                <p class="manual-summary">{{ $manualSummary }}</p>
            @endif

            <div class="manual-steps">
                @foreach($manualSteps as $index => $step)
                    <div class="manual-step">
                        <div class="manual-step-no">{{ $index + 1 }}</div>
                        <div>
                            <h4>{{ $step['title'] ?? 'Step' }}</h4>
                            <p>{{ $step['body'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(!empty($manualNotes))
                <div class="manual-notes">
                    @foreach($manualNotes as $note)
                        <div>{!! $note !!}</div>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="manual-actions">
            @if($manualPdf)
                <a href="{{ $manualPdf }}" class="manual-btn manual-btn-primary" download>
                    <i class="fas fa-file-pdf"></i>
                    <span>Download PDF Guide</span>
                </a>
            @endif
            <button type="button" class="manual-btn" onclick="printManual_{{ $manualJsId }}()">
                <i class="fas fa-print"></i>
                <span>Print / Save as PDF</span>
            </button>
            <button type="button" class="manual-btn" onclick="closeManual_{{ $manualJsId }}()">
                <i class="fas fa-check"></i>
                <span>Close</span>
            </button>
        </div>
    </div>
</div>

<script>
    function openManual_{{ $manualJsId }}() {
        const modal = document.getElementById(@json($manualId));
        if (!modal) return;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeManual_{{ $manualJsId }}() {
        const modal = document.getElementById(@json($manualId));
        if (!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    }

    function printManual_{{ $manualJsId }}() {
        const content = document.getElementById(@json($manualId . '_print'));
        if (!content) return;

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow) return;

        printWindow.document.write(`
            <html>
                <head>
                    <title>{{ addslashes($manualTitle) }}</title>
                    <style>
                        body { font-family: Arial, sans-serif; color: #111827; margin: 32px; line-height: 1.65; }
                        h1 { font-size: 24px; margin-bottom: 14px; }
                        h4 { font-size: 16px; margin: 0 0 6px; }
                        p { margin: 0; }
                        .manual-step { border: 1px solid #d1d5db; border-radius: 12px; padding: 14px 16px; margin-bottom: 12px; }
                        .manual-step-no { display:inline-flex; min-width:28px; height:28px; align-items:center; justify-content:center; border-radius:999px; background:#0ea5e9; color:#fff; font-weight:700; margin-bottom:10px; }
                        .manual-notes { margin-top: 18px; padding: 14px 16px; border-radius: 12px; background: #f8fafc; border: 1px solid #e5e7eb; }
                    </style>
                </head>
                <body>
                    <h1>{{ addslashes($manualTitle) }}</h1>
                    ${content.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    document.addEventListener('click', function (event) {
        const modal = document.getElementById(@json($manualId));
        if (modal && event.target === modal) {
            closeManual_{{ $manualJsId }}();
        }
    });
</script>
