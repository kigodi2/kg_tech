@extends('layout')

@section('content')
@include('registration.partials.theme')

<style>
    /* Scoped premium style overrides for PSLE Questions Mark Entry */
    .mark-entry-shell {
        background:
            radial-gradient(circle at 50% 0%, rgba(14, 165, 233, 0.12), transparent 45%),
            radial-gradient(circle at 0% 100%, rgba(16, 185, 129, 0.06), transparent 35%),
            radial-gradient(circle at 100% 100%, rgba(245, 158, 11, 0.05), transparent 35%),
            linear-gradient(180deg, #f0f6fb 0%, #e8f0f8 50%, #f4f8fc 100%) !important;
        min-height: calc(100vh - 64px);
        position: relative;
    }

    .mark-entry-shell .registration-surface {
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(15, 95, 143, 0.16) !important;
        border-radius: 26px !important;
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.02),
            0 20px 40px -4px rgba(15, 23, 42, 0.08),
            inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
        position: relative;
        overflow: hidden;
    }

    /* National Flag Accent Top Trim */
    .mark-entry-shell .registration-surface::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #1EB53A 0%, #1EB53A 30%, #FCD116 30%, #FCD116 40%, #000000 40%, #000000 60%, #FCD116 60%, #FCD116 70%, #00A3DD 70%, #00A3DD 100%);
        z-index: 10;
    }

    .mark-entry-shell .registration-page-chip {
        background: rgba(14, 165, 233, 0.08) !important;
        border: 1px solid rgba(14, 165, 233, 0.2) !important;
        color: #0f766e !important;
        font-weight: 700 !important;
        padding: 6px 14px !important;
        border-radius: 99px !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 2px 4px rgba(14, 165, 233, 0.04);
        transition: all 0.2s ease;
    }

    .mark-entry-shell .registration-page-chip:hover {
        background: rgba(14, 165, 233, 0.12) !important;
        transform: translateY(-1px);
    }

    .mark-entry-shell .registration-page-chip i {
        color: #d97706 !important;
    }

    /* Premium Recessed Search Section */
    .mark-entry-shell .search-section-premium {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.95) 0%, rgba(246, 250, 254, 0.95) 100%) !important;
        border: 1px solid rgba(15, 95, 143, 0.12) !important;
        border-radius: 20px !important;
        box-shadow: 
            0 10px 25px -5px rgba(0, 0, 0, 0.02),
            inset 0 1px 1px rgba(255, 255, 255, 0.9),
            inset 0 -2px 6px rgba(15, 95, 143, 0.04) !important;
        padding: 24px !important;
        transition: all 0.3s ease;
    }

    .mark-entry-shell .search-section-premium:hover {
        border-color: rgba(15, 95, 143, 0.2) !important;
        box-shadow: 
            0 12px 30px -5px rgba(0, 0, 0, 0.04),
            inset 0 1px 1px rgba(255, 255, 255, 0.9),
            inset 0 -2px 6px rgba(15, 95, 143, 0.06) !important;
    }

    /* Premium Inputs and Focus States */
    .mark-entry-shell .premium-input {
        border-radius: 12px !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        padding: 12px 16px !important;
        font-size: 0.95rem !important;
        color: #1e293b !important;
        box-shadow: inset 0 2px 4px rgba(15, 23, 42, 0.03) !important;
        transition: all 0.2s ease !important;
        width: 100%;
    }

    .mark-entry-shell .premium-input:focus {
        outline: none !important;
        border-color: #0284c7 !important;
        box-shadow: 
            0 0 0 4px rgba(2, 132, 199, 0.15),
            inset 0 2px 4px rgba(15, 23, 42, 0.03) !important;
    }

    /* Tactile 3D Load Button */
    .mark-entry-shell .premium-btn-load {
        background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%) !important;
        border: 1px solid #1d4ed8 !important;
        border-radius: 12px !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        letter-spacing: 0.03em !important;
        padding: 12px 28px !important;
        text-transform: uppercase !important;
        font-size: 0.85rem !important;
        box-shadow: 
            0 4px 6px -1px rgba(29, 78, 216, 0.15),
            0 10px 15px -3px rgba(29, 78, 216, 0.1),
            0 -3px 0 rgba(0, 0, 0, 0.2) inset !important;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1) !important;
        cursor: pointer;
        position: relative;
    }

    .mark-entry-shell .premium-btn-load:hover {
        background: linear-gradient(180deg, #4f46e5 0%, #2563eb 100%) !important;
        transform: translateY(-1px);
        box-shadow: 
            0 6px 8px -1px rgba(29, 78, 216, 0.2),
            0 12px 20px -3px rgba(29, 78, 216, 0.15),
            0 -3px 0 rgba(0, 0, 0, 0.25) inset !important;
    }

    .mark-entry-shell .premium-btn-load:active {
        transform: translateY(2px) !important;
        box-shadow: 
            0 1px 2px rgba(29, 78, 216, 0.1),
            0 -1px 0 rgba(0, 0, 0, 0.15) inset !important;
    }

    /* Guidance Help Panels & Grid */
    .mark-entry-shell .question-info-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 20px;
        margin-top: 24px;
    }

    .mark-entry-shell .question-info-card {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
        border: 1px solid rgba(15, 95, 143, 0.08);
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.01), 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .mark-entry-shell .question-info-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #0284c7;
        opacity: 0.7;
        transition: height 0.3s ease;
    }

    .mark-entry-shell .question-info-card:hover {
        transform: translateY(-3px);
        box-shadow: 
            0 12px 20px -8px rgba(0, 0, 0, 0.05),
            0 4px 6px rgba(0, 0, 0, 0.01);
        border-color: rgba(2, 132, 199, 0.2);
    }

    .mark-entry-shell .question-info-card:hover::before {
        height: 100%;
        background: linear-gradient(180deg, #0284c7, #0d9488);
    }

    .mark-entry-shell .info-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    .mark-entry-shell .info-card-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(14, 165, 233, 0.1);
        color: #0284c7;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .mark-entry-shell .question-info-card:hover .info-card-icon {
        background: #0284c7;
        color: #ffffff;
        transform: scale(1.05);
    }

    .mark-entry-shell .question-info-card h4 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .mark-entry-shell .question-info-card p {
        margin: 0;
        font-size: 0.85rem;
        line-height: 1.6;
        color: #475569;
    }

    /* Premium Empty State */
    .mark-entry-shell .question-empty-state {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.7) 0%, rgba(248, 250, 252, 0.7) 100%);
        border: 2px dashed rgba(15, 95, 143, 0.16);
        border-radius: 20px;
        padding: 48px 24px;
        text-align: center;
        margin-top: 24px;
        transition: all 0.3s ease;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.01);
    }

    .mark-entry-shell .question-empty-state:hover {
        border-color: rgba(2, 132, 199, 0.4);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
    }

    .mark-entry-shell .empty-state-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(14, 165, 233, 0.08);
        color: #0284c7;
        font-size: 1.8rem;
        margin-bottom: 16px;
        animation: pulse-glow 3s infinite ease-in-out;
    }

    @keyframes pulse-glow {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(14, 165, 233, 0.2);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 0 20px 8px rgba(14, 165, 233, 0.1);
        }
    }

    .mark-entry-shell .question-empty-state h3 {
        margin: 0 0 8px;
        font-size: 1.15rem;
        font-weight: 700;
        color: #1e293b;
    }

    .mark-entry-shell .question-empty-state p {
        margin: 0;
        font-size: 0.9rem;
        color: #64748b;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Premium Loaded Details Section */
    .mark-entry-shell .details-section-premium {
        background: #ffffff !important;
        border: 1px solid rgba(15, 95, 143, 0.12) !important;
        border-radius: 20px !important;
        box-shadow: 
            0 4px 6px rgba(0, 0, 0, 0.01),
            0 10px 15px -3px rgba(0, 0, 0, 0.02) !important;
        transition: all 0.3s ease;
    }
    
    .mark-entry-shell .details-section-premium:hover {
        border-color: rgba(15, 95, 143, 0.2) !important;
        box-shadow: 
            0 10px 20px -5px rgba(15, 95, 143, 0.06),
            0 4px 6px rgba(0, 0, 0, 0.01) !important;
    }

    .mark-entry-shell .details-section-premium dl dt {
        font-size: 0.72rem !important;
        letter-spacing: 0.14em !important;
        color: #64748b !important;
        font-weight: 700 !important;
    }

    .mark-entry-shell .details-section-premium dl dd {
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        margin-top: 4px;
    }

    /* Premium Question Grid Cards */
    .mark-entry-shell .question-card-premium {
        background: #fbfdff !important;
        border: 1px solid rgba(15, 95, 143, 0.1) !important;
        border-radius: 16px !important;
        padding: 16px !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.01) !important;
    }

    .mark-entry-shell .question-card-premium:hover {
        border-color: rgba(2, 132, 199, 0.3) !important;
        background: #ffffff !important;
        box-shadow: 
            0 8px 16px -4px rgba(15, 95, 143, 0.08),
            0 2px 4px rgba(0, 0, 0, 0.01) !important;
        transform: translateY(-2px);
    }

    .mark-entry-shell .question-card-premium label {
        font-size: 0.9rem !important;
        font-weight: 700 !important;
        color: #1e293b !important;
    }

    .mark-entry-shell .question-card-premium input {
        border-radius: 10px !important;
        padding: 8px 12px !important;
        font-size: 0.95rem !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04) !important;
        transition: all 0.2s ease !important;
    }

    .mark-entry-shell .question-card-premium input:focus {
        outline: none !important;
        border-color: #0284c7 !important;
        box-shadow: 
            0 0 0 3px rgba(2, 132, 199, 0.15),
            inset 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }

    .mark-entry-shell .question-card-premium input:disabled {
        background: #f1f5f9 !important;
        color: #94a3b8 !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed;
    }

    /* 3D Action Buttons */
    .mark-entry-shell .premium-btn-draft {
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        color: #475569 !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        box-shadow: 
            0 2px 4px rgba(0, 0, 0, 0.02),
            0 -2px 0 rgba(0, 0, 0, 0.05) inset !important;
        transition: all 0.15s ease !important;
    }

    .mark-entry-shell .premium-btn-draft:hover {
        background: #f8fafc !important;
        color: #1e293b !important;
        border-color: #94a3b8 !important;
        box-shadow: 
            0 4px 6px rgba(0, 0, 0, 0.04),
            0 -2px 0 rgba(0, 0, 0, 0.08) inset !important;
        transform: translateY(-1px);
    }

    .mark-entry-shell .premium-btn-draft:active {
        transform: translateY(1px) !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
    }

    .mark-entry-shell .premium-btn-submit {
        background: linear-gradient(180deg, #10b981 0%, #047857 100%) !important;
        border: 1px solid #047857 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        box-shadow: 
            0 4px 6px -1px rgba(16, 185, 129, 0.15),
            0 -3px 0 rgba(0, 0, 0, 0.2) inset !important;
        transition: all 0.15s ease !important;
    }

    .mark-entry-shell .premium-btn-submit:hover {
        background: linear-gradient(180deg, #34d399 0%, #059669 100%) !important;
        box-shadow: 
            0 6px 8px -1px rgba(16, 185, 129, 0.2),
            0 -3px 0 rgba(0, 0, 0, 0.25) inset !important;
        transform: translateY(-1px);
    }

    .mark-entry-shell .premium-btn-submit:active {
        transform: translateY(2px) !important;
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.1) !important;
    }

    .mark-entry-shell .premium-btn-next {
        background: linear-gradient(180deg, #2563eb 0%, #1d4ed8 100%) !important;
        border: 1px solid #1d4ed8 !important;
        color: #ffffff !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        box-shadow: 
            0 4px 6px -1px rgba(37, 99, 235, 0.15),
            0 -3px 0 rgba(0, 0, 0, 0.2) inset !important;
        transition: all 0.15s ease !important;
    }

    .mark-entry-shell .premium-btn-next:hover {
        background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%) !important;
        box-shadow: 
            0 6px 8px -1px rgba(37, 99, 235, 0.2),
            0 -3px 0 rgba(0, 0, 0, 0.25) inset !important;
        transform: translateY(-1px);
    }

    .mark-entry-shell .premium-btn-next:active {
        transform: translateY(2px) !important;
        box-shadow: 0 1px 2px rgba(37, 99, 235, 0.1) !important;
    }

    /* Media Queries & Responsive Stacking */
    @media (max-width: 1024px) {
        .mark-entry-shell .question-info-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }

    @media (max-width: 640px) {
        .mark-entry-shell .search-section-premium {
            padding: 16px !important;
        }
        .mark-entry-shell .question-card-premium {
            padding: 12px !important;
        }
        .mark-entry-shell .premium-btn-load {
            width: 100% !important;
            justify-content: center;
        }
        .mark-entry-shell .registration-surface {
            border-radius: 18px !important;
        }
    }

    /* Prefers-reduced-motion Safeties */
    @media prefers-reduced-motion: reduce {
        .mark-entry-shell *,
        .mark-entry-shell .premium-btn-load,
        .mark-entry-shell .premium-btn-draft,
        .mark-entry-shell .premium-btn-submit,
        .mark-entry-shell .premium-btn-next,
        .mark-entry-shell .question-info-card,
        .mark-entry-shell .empty-state-icon {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }
    }
</style>

@php
    $statusLabel = $loadedEntry?->status ? strtoupper($loadedEntry->status) : 'NEW';
    $routeBase = 'mark-entry.' . strtolower($examCode) . '.questions';
@endphp

<div class="registration-shell mark-entry-shell">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="registration-surface p-6 sm:p-8 space-y-6">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $examType->code }} Question Entry</p>
                    <h1 class="text-2xl font-semibold text-slate-900">Mark Entry by Question</h1>
                    <p class="mt-2 text-sm text-slate-600">Active exam year: {{ $examYear->year_label }}. Region access is enforced from the logged-in account scope.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs text-slate-600">
                    <span class="registration-page-chip">
                        <i class="fas fa-calendar-alt"></i>
                        <span>{{ $examYear->year_label }}</span>
                    </span>
                    <span class="registration-page-chip">
                        <i class="fas fa-book-open"></i>
                        <span>{{ $subjects->count() }} subjects</span>
                    </span>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-none border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (!empty($pageErrors))
                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="space-y-1">
                        @foreach ($pageErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-none border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="search-section-premium">
                <form method="GET" action="{{ route($routeBase . '.load') }}" class="grid gap-4 lg:grid-cols-[1.2fr_1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Candidate Number</label>
                        <input
                            id="candidate_no_input"
                            type="text"
                            name="candidate_no"
                            value="{{ old('candidate_no', $candidateNo) }}"
                            class="premium-input w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Enter candidate number"
                            autocomplete="off"
                            required
                        >
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Subject</label>
                        <input type="hidden" name="subject_id" id="subject_filter" value="{{ old('subject_id', $selectedSubjectId) }}">
                        <input
                            id="subject_filter_search"
                            list="subject_filter_options"
                            class="premium-input w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Search subject"
                            autocomplete="off"
                            value="{{ optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))?->code ? optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))->code . ' - ' . optional($subjects->firstWhere('id', (int) old('subject_id', $selectedSubjectId)))->name : '' }}"
                            required
                        >
                        <datalist id="subject_filter_options">
                            @foreach ($subjects as $subject)
                                <option value="{{ $subject->code }} - {{ $subject->name }}" data-id="{{ $subject->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="premium-btn-load w-full rounded-none bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                            Load
                        </button>
                    </div>
                </form>
            </section>

            @if ($loaded && $candidate)
                <div class="grid gap-6 xl:grid-cols-[1.05fr_1.45fr]">
                    <section class="details-section-premium p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Candidate Details</h2>
                                <p class="text-sm text-slate-500">Loaded from the active exam registration context.</p>
                            </div>
                            <span class="rounded-none border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">{{ $statusLabel }}</span>
                        </div>

                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Candidate Name</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->full_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Candidate Number</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->candidate_id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Sex</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->gender }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">School/Centre</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->school?->code }} - {{ $candidate->school?->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Region</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $candidate->school?->region?->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Exam Type</dt>
                                <dd class="mt-1 text-sm font-medium text-slate-900">{{ $examType->code }}</dd>
                            </div>
                        </dl>

                        @if ($structure)
                            <div class="mt-5 rounded-none border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                {{ $structure['label'] }}
                            </div>
                        @endif
                    </section>

                    <section class="details-section-premium p-5 sm:p-6" x-data="questionMarkEntryPage(@js($scores), @js($structure ?? []))">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Question Marks</h2>
                                <p class="text-sm text-slate-500">Enter marks by question. Total updates automatically as you type.</p>
                                @if (!empty($structure['total_label']))
                                    <p class="mt-2 text-xs text-slate-500">{{ $structure['total_label'] }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Total</p>
                                <p class="text-2xl font-semibold text-slate-900" x-text="formattedTotal"></p>
                            </div>
                        </div>

                        @if (!empty($structure['papers']) && count($structure['papers']) > 1)
                            <div class="mt-5 grid gap-3 md:grid-cols-2">
                                @foreach ($structure['papers'] as $paper)
                                    <div class="border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $paper['paper_code'] }}</p>
                                        <p class="mt-1 text-sm font-medium text-slate-800">{{ $paper['paper_label'] }}</p>
                                        <div class="mt-2 flex items-center justify-between text-sm text-slate-600">
                                            <span>Paper total</span>
                                            <span x-text="paperTotal('{{ $paper['paper_code'] }}')"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($structure['choice_groups']))
                            <div class="mt-5 rounded-none border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                                <ul class="space-y-1">
                                    @foreach ($structure['choice_groups'] as $choiceGroup)
                                        <li>{{ $choiceGroup['label'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route($routeBase . '.store') }}" class="mt-5 space-y-5">
                            @csrf
                            <input type="hidden" name="candidate_no" value="{{ $candidate->candidate_id }}">
                            <input type="hidden" name="subject_id" value="{{ $selectedSubjectId }}">

                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach (($structure['questions'] ?? []) as $question)
                                    @php
                                        $questionNo = (int) $question['question_no'];
                                        $field = 'scores.' . $questionNo;
                                        $defaultScore = old("scores.$questionNo", $scores[$questionNo] ?? null);
                                    @endphp
                                    <div class="question-card-premium p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <label for="score_{{ $questionNo }}" class="text-sm font-semibold text-slate-800">{{ $question['display_label'] ?? ('Q' . $questionNo) }}</label>
                                                <p class="mt-1 text-xs text-slate-500">Maximum {{ number_format((float) $question['max_mark'], 2) }}</p>
                                                @if (!empty($question['paper_label']))
                                                    <p class="mt-1 text-xs text-slate-500">{{ $question['paper_label'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <input
                                            id="score_{{ $questionNo }}"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="{{ $question['max_mark'] }}"
                                            name="scores[{{ $questionNo }}]"
                                            value="{{ $defaultScore }}"
                                            x-model="scores['{{ $questionNo }}']"
                                            @if (!empty($question['choice_group']))
                                                :disabled="{{ $canEdit ? 'isChoiceLocked(\'' . $question['choice_group'] . '\', ' . $questionNo . ')' : 'true' }}"
                                            @endif
                                            @disabled(!$canEdit)
                                            class="mt-3 w-full rounded-none border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:bg-slate-100"
                                        >
                                        @error($field)
                                            <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-5">
                                @if ($canEdit)
                                    <button type="submit" name="entry_action" value="draft" class="premium-btn-draft rounded-none border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Save Draft
                                    </button>
                                    <button type="submit" name="entry_action" value="submit" class="premium-btn-submit rounded-none bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">
                                        Submit
                                    </button>
                                    <button type="submit" name="entry_action" value="next" class="premium-btn-next rounded-none bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                                        Save &amp; Next Candidate
                                    </button>
                                @else
                                    <p class="text-sm text-amber-700">This submitted entry is read-only for your account.</p>
                                @endif
                            </div>
                        </form>
                    </section>
                </div>
            @else
                {{-- Premium Guidance & Status Panel --}}
                <div class="question-info-grid">
                    <div class="question-info-card">
                        <div class="info-card-header">
                            <span class="info-card-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                            <h4>Candidate Verification</h4>
                        </div>
                        <p>Enter the candidate index number to load their profile. The index number will be verified against the active exam registration context.</p>
                    </div>

                    <div class="question-info-card">
                        <div class="info-card-header">
                            <span class="info-card-icon"><i class="fas fa-book-open" aria-hidden="true"></i></span>
                            <h4>Subject Question Structure</h4>
                        </div>
                        <p>Select the subject to fetch its specific paper structure, question count, and maximum mark allocations automatically.</p>
                    </div>

                    <div class="question-info-card">
                        <div class="info-card-header">
                            <span class="info-card-icon"><i class="fas fa-shield-halved" aria-hidden="true"></i></span>
                            <h4>Secure Regional Scope</h4>
                        </div>
                        <p>Officers are restricted to entering marks only for candidates registered within their authorized regional boundaries.</p>
                    </div>
                </div>

                {{-- Professional Empty State --}}
                <div class="question-empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-pen-ruler" aria-hidden="true"></i>
                    </div>
                    <h3>Load a candidate to begin question entry</h3>
                    <p>Enter a candidate number and subject above to open the secure question-level mark entry workspace.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function questionMarkEntryPage(initialScores, structure) {
        return {
            scores: initialScores || {},
            structure: structure || {},
            get questions() {
                return this.structure.questions || [];
            },
            get papers() {
                return this.structure.papers || [];
            },
            get total() {
                if ((this.structure.aggregation || 'sum') === 'normalize_to_100' && this.papers.length) {
                    const weightedSum = this.papers.reduce((sum, paper) => sum + this.numericPaperTotal(paper), 0);
                    const weightedMax = this.papers.reduce((sum, paper) => sum + Number(paper.max_mark_total || 0), 0);
                    return weightedMax > 0 ? (weightedSum / weightedMax) * 100 : 0;
                }

                if ((this.structure.aggregation || 'sum') === 'average_paper_totals' && this.papers.length) {
                    const paperTotals = this.papers.map((paper) => this.numericPaperTotal(paper));
                    return paperTotals.reduce((sum, value) => sum + value, 0) / this.papers.length;
                }

                return this.questions.reduce((sum, question) => sum + this.numericQuestionScore(question.question_no), 0);
            },
            get formattedTotal() {
                return this.total.toFixed(2);
            },
            numericQuestionScore(questionNo) {
                const value = parseFloat(this.scores[String(questionNo)] ?? this.scores[questionNo] ?? 0);
                return Number.isFinite(value) ? value : 0;
            },
            numericPaperTotal(paper) {
                return (paper.question_numbers || []).reduce((sum, questionNo) => sum + this.numericQuestionScore(questionNo), 0);
            },
            paperTotal(paperCode) {
                const paper = this.papers.find((item) => item.paper_code === paperCode);
                return paper ? this.numericPaperTotal(paper).toFixed(2) : '0.00';
            },
            isChoiceLocked(groupKey, questionNo) {
                const groups = this.structure.choice_groups || [];
                const group = groups.find((item) => item.group_key === groupKey);
                if (!group) {
                    return false;
                }

                const currentValue = this.scores[String(questionNo)] ?? this.scores[questionNo] ?? '';
                if (currentValue !== null && currentValue !== '') {
                    return false;
                }

                const filledCount = (group.question_numbers || []).filter((groupQuestionNo) => {
                    const value = this.scores[String(groupQuestionNo)] ?? this.scores[groupQuestionNo] ?? '';
                    return value !== null && value !== '';
                }).length;

                return filledCount >= Number(group.limit || 0);
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const shouldFocusCandidateInput = @js((bool) session('focus_candidate_no'));
        const loadForm = document.querySelector('form[action="{{ route($routeBase . '.load') }}"]');

        setupSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options');

        if (loadForm) {
            loadForm.addEventListener('submit', function (event) {
                if (!resolveSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options')) {
                    event.preventDefault();
                    alert('Please select Subject from the searchable list.');
                    return;
                }
            });
        }

        if (!shouldFocusCandidateInput) {
            return;
        }

        const candidateInput = document.getElementById('candidate_no_input');
        if (!candidateInput) {
            return;
        }

        window.requestAnimationFrame(() => {
            candidateInput.focus();
            candidateInput.select();
        });
    });

    function setupSearchField(inputId, hiddenId, datalistId) {
        const input = document.getElementById(inputId);
        if (!input) {
            return;
        }

        input.addEventListener('change', function () {
            resolveSearchField(inputId, hiddenId, datalistId, true);
        });
    }

    function resolveSearchField(inputId, hiddenId, datalistId, allowBlank = false) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const options = Array.from(document.querySelectorAll(`#${datalistId} option`));
        const value = (input?.value || '').trim();

        if (allowBlank && value === '') {
            if (hidden) {
                hidden.value = '';
            }
            return true;
        }

        const matched = options.find((option) => option.value === value);

        if (hidden) {
            hidden.value = matched ? (matched.dataset.id || '') : '';
        }

        return Boolean(matched);
    }
</script>
@endsection
