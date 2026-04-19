@extends('layouts.auth-rms')

@section('title', auth()->user()->isAdmin() ? 'Exam Submissions (Admin)' : 'My Exam Submissions')

@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
    $totalCount = $submissions->total();
    $pendingCount = $isAdmin
        ? \App\Models\ExamSubmission::where('status', 'pending')->count()
        : \App\Models\ExamSubmission::where('user_id', auth()->id())->where('status', 'pending')->count();
    $approvedCount = $isAdmin
        ? \App\Models\ExamSubmission::where('status', 'approved')->count()
        : \App\Models\ExamSubmission::where('user_id', auth()->id())->where('status', 'approved')->count();
    $rejectedCount = $isAdmin
        ? \App\Models\ExamSubmission::where('status', 'rejected')->count()
        : \App\Models\ExamSubmission::where('user_id', auth()->id())->where('status', 'rejected')->count();
@endphp

<style>
    .exam-submissions-page {
        width: min(1180px, calc(100% - 32px));
        margin: 32px auto 40px;
    }

    .exam-submissions-stack {
        display: grid;
        gap: 20px;
    }

    .exam-card {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(203, 213, 225, 0.9);
        border-radius: 20px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
        backdrop-filter: blur(6px);
    }

    .exam-hero {
        padding: 28px;
    }

    .exam-hero-top {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .exam-eyebrow {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .exam-title {
        margin: 10px 0 0;
        color: #0f172a;
        font-size: clamp(30px, 4vw, 42px);
        line-height: 1.1;
        font-weight: 800;
    }

    .exam-description {
        margin: 14px 0 0;
        max-width: 760px;
        color: #475569;
        font-size: 16px;
        line-height: 1.65;
    }

    .exam-button,
    .exam-button-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        border-radius: 12px;
        padding: 12px 18px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
    }

    .exam-button {
        background: #0f172a;
        color: #ffffff;
        border: 1px solid #0f172a;
    }

    .exam-button:hover {
        background: #1e293b;
        border-color: #1e293b;
        transform: translateY(-1px);
    }

    .exam-button-secondary {
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
    }

    .exam-button-secondary:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .exam-button svg,
    .exam-button-secondary svg,
    .exam-link-button svg {
        width: 16px;
        height: 16px;
        flex: 0 0 16px;
        display: block;
    }

    .exam-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        border-top: 1px solid rgba(226, 232, 240, 0.95);
        background: #e2e8f0;
        gap: 1px;
    }

    .exam-stat {
        padding: 18px 24px;
        background: #f8fafc;
    }

    .exam-stat--pending {
        background: #fffbeb;
    }

    .exam-stat--approved {
        background: #ecfdf5;
    }

    .exam-stat--rejected {
        background: #fff1f2;
    }

    .exam-stat-label {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
    }

    .exam-stat-value {
        margin: 10px 0 0;
        font-size: 28px;
        line-height: 1;
        font-weight: 800;
        color: #0f172a;
    }

    .exam-flash {
        padding: 14px 18px;
        border-radius: 16px;
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #166534;
        font-size: 14px;
        font-weight: 600;
    }

    .exam-flash--error {
        border-color: #fdba74;
        background: #fff7ed;
        color: #c2410c;
    }

    .exam-section {
        padding: 24px 28px;
    }

    .exam-section-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .exam-section-title {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        font-weight: 800;
    }

    .exam-section-text {
        margin: 6px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .exam-filter-form {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }

    .exam-field {
        min-width: 220px;
    }

    .exam-field--compact {
        min-width: 200px;
    }

    .exam-label {
        display: block;
        margin-bottom: 8px;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .exam-select {
        width: 100%;
        height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1;
        border-radius: 0;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
    }

    .exam-search-input {
        width: 100%;
        height: 46px;
        padding: 0 14px;
        border: 1px solid #cbd5e1;
        border-radius: 0;
        background: #ffffff;
        color: #0f172a;
        font-size: 14px;
    }

    .exam-search-input:focus,
    .exam-select:focus {
        outline: 2px solid rgba(59, 130, 246, 0.15);
        outline-offset: 0;
        border-color: #3b82f6;
    }

    .exam-table-wrap {
        overflow-x: auto;
        border-top: 1px solid #e2e8f0;
    }

    .exam-table {
        width: 100%;
        border-collapse: collapse;
    }

    .exam-table th,
    .exam-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }

    .exam-table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .exam-primary-text {
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.5;
    }

    .exam-secondary-text {
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .exam-status {
        display: inline-flex;
        align-items: center;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }

    .exam-status--pending {
        background: #fffbeb;
        border-color: #fcd34d;
        color: #92400e;
    }

    .exam-status--approved,
    .exam-status--validated {
        background: #ecfdf5;
        border-color: #86efac;
        color: #166534;
    }

    .exam-status--rejected {
        background: #fff1f2;
        border-color: #fda4af;
        color: #be123c;
    }

    .exam-reason {
        margin-top: 8px;
        color: #be123c;
        font-size: 12px;
        line-height: 1.5;
        max-width: 240px;
    }

    .exam-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .exam-link-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        gap: 8px;
        min-height: 38px;
        padding: 0 14px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .exam-link-button:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .exam-link-button--primary {
        background: #0f172a;
        border-color: #0f172a;
        color: #ffffff;
    }

    .exam-link-button--primary:hover {
        background: #1e293b;
        border-color: #1e293b;
    }

    .exam-mobile-list {
        display: none;
        border-top: 1px solid #e2e8f0;
    }

    .exam-mobile-item {
        padding: 20px 22px;
        border-bottom: 1px solid #e2e8f0;
    }

    .exam-mobile-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    .exam-mobile-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 16px;
    }

    .exam-mobile-meta dt {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .exam-mobile-meta dd {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        line-height: 1.5;
    }

    .exam-mobile-actions {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
    }

    .exam-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        padding: 18px 28px 24px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }

    .exam-pagination-text {
        color: #475569;
        font-size: 14px;
        margin: 0;
    }

    .exam-empty {
        padding: 56px 28px;
        text-align: center;
    }

    .exam-empty-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 18px;
        border-radius: 999px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .exam-empty-icon svg {
        width: 28px;
        height: 28px;
        display: block;
        color: #94a3b8;
    }

    .exam-empty-title {
        margin: 0;
        color: #0f172a;
        font-size: 22px;
        font-weight: 800;
    }

    .exam-empty-text {
        margin: 10px 0 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .exam-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .exam-table-wrap {
            display: none;
        }

        .exam-mobile-list {
            display: block;
        }
    }

    @media (max-width: 640px) {
        .exam-submissions-page {
            width: min(100% - 20px, 1180px);
            margin-top: 20px;
            margin-bottom: 28px;
        }

        .exam-hero,
        .exam-section,
        .exam-pagination,
        .exam-empty {
            padding-left: 18px;
            padding-right: 18px;
        }

        .exam-stats {
            grid-template-columns: 1fr;
        }

        .exam-mobile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="exam-submissions-page">
    <div class="exam-submissions-stack">
        @if(session('success'))
            <div class="exam-flash">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="exam-flash exam-flash--error">{{ session('error') }}</div>
        @endif

        <section class="exam-card">
            <div class="exam-hero">
                <div class="exam-hero-top">
                    <div>
                        <p class="exam-eyebrow">{{ $isAdmin ? 'Admin Workspace' : 'Submission Workspace' }}</p>
                        <h1 class="exam-title">{{ $isAdmin ? 'Exam submissions' : 'My exam submissions' }}</h1>
                        <p class="exam-description">
                            {{ $isAdmin
                                ? 'Review uploaded papers, focus on pending items, and open individual records for approval or rejection.'
                                : 'Track your uploaded papers, check their review status, and open any submission for full validation details.' }}
                        </p>
                    </div>

                    <a href="{{ route('exam-submissions.create') }}" class="exam-button">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6"></path>
                        </svg>
                        <span>Submit New Exam</span>
                    </a>
                </div>
            </div>

            <div class="exam-stats">
                <div class="exam-stat">
                    <p class="exam-stat-label">Total</p>
                    <p class="exam-stat-value">{{ $totalCount }}</p>
                </div>
                <div class="exam-stat exam-stat--pending">
                    <p class="exam-stat-label">Pending</p>
                    <p class="exam-stat-value">{{ $pendingCount }}</p>
                </div>
                <div class="exam-stat exam-stat--approved">
                    <p class="exam-stat-label">Approved</p>
                    <p class="exam-stat-value">{{ $approvedCount }}</p>
                </div>
                <div class="exam-stat exam-stat--rejected">
                    <p class="exam-stat-label">Rejected</p>
                    <p class="exam-stat-value">{{ $rejectedCount }}</p>
                </div>
            </div>
        </section>

        @if($isAdmin)
            <section class="exam-card exam-section">
                <div class="exam-section-header">
                    <div>
                        <h2 class="exam-section-title">Filter submissions</h2>
                        <p class="exam-section-text">Use examination filters to narrow the review queue and find submissions faster.</p>
                    </div>

                    <form method="GET" action="{{ route('exam-submissions.index') }}" class="exam-filter-form">
                        <div class="exam-field">
                            <label for="exam_type_filter" class="exam-label">Exam Type</label>
                            <input type="hidden" name="exam_type_id" id="exam_type_filter" value="{{ request('exam_type_id') }}">
                            <input
                                id="exam_type_filter_search"
                                list="exam_type_filter_options"
                                class="exam-search-input"
                                placeholder="Search exam type"
                                autocomplete="off"
                                value="{{ optional($filterExamTypes->firstWhere('id', (int) request('exam_type_id')))?->name ? optional($filterExamTypes->firstWhere('id', (int) request('exam_type_id')))->name . ' (' . optional($filterExamTypes->firstWhere('id', (int) request('exam_type_id')))->code . ')' : '' }}"
                            >
                            <datalist id="exam_type_filter_options">
                                @foreach($filterExamTypes as $filterExamType)
                                    <option value="{{ $filterExamType->name }} ({{ $filterExamType->code }})" data-id="{{ $filterExamType->id }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="exam-field">
                            <label for="subject_filter" class="exam-label">Subject</label>
                            <input type="hidden" name="subject_id" id="subject_filter" value="{{ request('subject_id') }}">
                            <input
                                id="subject_filter_search"
                                list="subject_filter_options"
                                class="exam-search-input"
                                placeholder="Search subject"
                                autocomplete="off"
                                value="{{ optional($filterSubjects->firstWhere('id', (int) request('subject_id')))?->code ? optional($filterSubjects->firstWhere('id', (int) request('subject_id')))->code . ' - ' . optional($filterSubjects->firstWhere('id', (int) request('subject_id')))->name : '' }}"
                            >
                            <datalist id="subject_filter_options">
                                @foreach($filterSubjects as $filterSubject)
                                    <option value="{{ $filterSubject->code }} - {{ $filterSubject->name }}" data-id="{{ $filterSubject->id }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="exam-field exam-field--compact">
                            <label for="year_filter" class="exam-label">Exam Year</label>
                            <input type="hidden" name="exam_year_id" id="year_filter" value="{{ request('exam_year_id') }}">
                            <input
                                id="year_filter_search"
                                list="year_filter_options"
                                class="exam-search-input"
                                placeholder="Search exam year"
                                autocomplete="off"
                                value="{{ optional($filterExamYears->firstWhere('id', (int) request('exam_year_id')))?->year_label ?? '' }}"
                            >
                            <datalist id="year_filter_options">
                                @foreach($filterExamYears as $filterExamYear)
                                    <option value="{{ $filterExamYear->year_label }}" data-id="{{ $filterExamYear->id }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="exam-field">
                            <label for="status_filter" class="exam-label">Status</label>
                            <select name="status" id="status_filter" class="exam-select">
                                <option value="">All statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <button type="submit" class="exam-button">Apply Filter</button>
                        @if(request()->filled('status') || request()->filled('exam_type_id') || request()->filled('subject_id') || request()->filled('exam_year_id'))
                            <a href="{{ route('exam-submissions.index') }}" class="exam-button-secondary">Clear</a>
                        @endif
                    </form>
                </div>
            </section>
        @endif

        <section class="exam-card exam-section">
            <div class="exam-section-header">
                <div>
                    <h2 class="exam-section-title">Final report</h2>
                    <p class="exam-section-text">
                        Generate a consolidated government-style report for the selected examination activity. The report summarizes all registered subjects for the exam type and shows which papers are ready, pending, missing, or returned for correction.
                    </p>
                </div>

                <form method="GET" action="{{ route('exam-submissions.final-report') }}" class="exam-filter-form">
                    <div class="exam-field exam-field--compact">
                        <label for="report_exam_type_id" class="exam-label">Exam Type</label>
                        <input type="hidden" name="exam_type_id" id="report_exam_type_id" required>
                        <input id="report_exam_type_search" list="report_exam_type_options" class="exam-search-input" placeholder="Search exam type" autocomplete="off">
                        <datalist id="report_exam_type_options">
                            @foreach($reportExamTypes as $examType)
                                <option value="{{ $examType->name }} ({{ $examType->code }})" data-id="{{ $examType->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="exam-field exam-field--compact">
                        <label for="report_exam_year_id" class="exam-label">Exam Year</label>
                        <input type="hidden" name="exam_year_id" id="report_exam_year_id" required>
                        <input id="report_exam_year_search" list="report_exam_year_options" class="exam-search-input" placeholder="Search exam year" autocomplete="off">
                        <datalist id="report_exam_year_options">
                            @foreach($reportExamYears as $examYear)
                                <option value="{{ $examYear->year_label }}" data-id="{{ $examYear->id }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    @if($isAdmin)
                        <div class="exam-field">
                            <label for="report_user_id" class="exam-label">Submitting Account</label>
                            <input type="hidden" name="user_id" id="report_user_id" required>
                            <input id="report_user_search" list="report_user_options" class="exam-search-input" placeholder="Search submitting account" autocomplete="off">
                            <datalist id="report_user_options">
                                @foreach($reportUsers as $reportUser)
                                    <option value="{{ $reportUser->name }}@if($reportUser->email) ({{ $reportUser->email }})@endif" data-id="{{ $reportUser->id }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    @endif
                    <button type="submit" class="exam-button">Generate Final Report</button>
                </form>
            </div>
        </section>

        <section class="exam-card">
            <div class="exam-section">
                <div class="exam-section-header" style="margin-bottom:0;">
                    <div>
                        <h2 class="exam-section-title">Submission list</h2>
                        <p class="exam-section-text">
                            {{ $isAdmin
                                ? 'Open a submission to review the uploaded file and complete admin action from the details page.'
                                : 'Open a submission to view validation notes, file details, and review outcome.' }}
                        </p>
                    </div>

                    @if($submissions->count() > 0)
                        <p class="exam-pagination-text">
                            Showing {{ $submissions->firstItem() }}-{{ $submissions->lastItem() }} of {{ $submissions->total() }}
                        </p>
                    @endif
                </div>
            </div>

            @if($submissions->count() > 0)
                <div class="exam-table-wrap">
                    <table class="exam-table">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Subject</th>
                                <th>Year</th>
                                @if($isAdmin)
                                    <th>Submitted By</th>
                                @endif
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissions as $submission)
                                <tr>
                                    <td>
                                        <div class="exam-primary-text">{{ $submission->examType->name }}</div>
                                        <div class="exam-secondary-text">{{ $submission->examType->code }}</div>
                                    </td>
                                    <td>
                                        <div class="exam-primary-text">{{ $submission->subject->name }}</div>
                                        <div class="exam-secondary-text">{{ $submission->subject->code }}</div>
                                    </td>
                                    <td>
                                        <div class="exam-primary-text">{{ $submission->examYear->year }}</div>
                                    </td>
                                    @if($isAdmin)
                                        <td>
                                            <div class="exam-primary-text">{{ $submission->user->name ?? 'N/A' }}</div>
                                            <div class="exam-secondary-text">{{ $submission->user->email ?? '' }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="exam-status exam-status--{{ $submission->status }}">
                                            {{ ucfirst($submission->status) }}
                                        </span>
                                        @if($submission->status === 'rejected' && $submission->rejection_reason)
                                            <div class="exam-reason">{{ \Illuminate\Support\Str::limit($submission->rejection_reason, 90) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="exam-primary-text">{{ $submission->submitted_at->format('M j, Y') }}</div>
                                        <div class="exam-secondary-text">{{ $submission->submitted_at->format('g:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="exam-actions">
                                            <a href="{{ route('exam-submissions.show', $submission) }}" class="exam-link-button exam-link-button--primary">
                                                View Details
                                            </a>
                                            <a href="{{ route('exam-submissions.download', $submission) }}" class="exam-link-button">
                                                Download PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="exam-mobile-list">
                    @foreach($submissions as $submission)
                        <article class="exam-mobile-item">
                            <div class="exam-mobile-top">
                                <div>
                                    <div class="exam-primary-text">{{ $submission->examType->code }} / {{ $submission->subject->code }}</div>
                                    <div class="exam-section-text" style="margin-top:4px;">
                                        {{ $submission->examType->name }} • {{ $submission->subject->name }}
                                    </div>
                                </div>
                                <span class="exam-status exam-status--{{ $submission->status }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                            </div>

                            <dl class="exam-mobile-grid">
                                <div class="exam-mobile-meta">
                                    <dt>Exam Year</dt>
                                    <dd>{{ $submission->examYear->year }}</dd>
                                </div>
                                <div class="exam-mobile-meta">
                                    <dt>Submitted</dt>
                                    <dd>{{ $submission->submitted_at->format('M j, Y g:i A') }}</dd>
                                </div>
                                @if($isAdmin)
                                    <div class="exam-mobile-meta" style="grid-column: 1 / -1;">
                                        <dt>Submitted By</dt>
                                        <dd>
                                            {{ $submission->user->name ?? 'N/A' }}
                                            @if($submission->user?->email)
                                                <span style="color:#64748b;">({{ $submission->user->email }})</span>
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if($submission->status === 'rejected' && $submission->rejection_reason)
                                    <div class="exam-mobile-meta" style="grid-column: 1 / -1;">
                                        <dt style="color:#be123c;">Rejection Reason</dt>
                                        <dd style="color:#be123c;">{{ $submission->rejection_reason }}</dd>
                                    </div>
                                @endif
                            </dl>

                            <div class="exam-mobile-actions">
                                <a href="{{ route('exam-submissions.show', $submission) }}" class="exam-link-button exam-link-button--primary">
                                    View Details
                                </a>
                                <a href="{{ route('exam-submissions.download', $submission) }}" class="exam-link-button">
                                    Download PDF
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="exam-pagination">
                    <p class="exam-pagination-text">
                        Showing <strong>{{ $submissions->firstItem() }}</strong> to <strong>{{ $submissions->lastItem() }}</strong>
                        of <strong>{{ $submissions->total() }}</strong> submissions
                    </p>
                    {{ $submissions->withQueryString()->links() }}
                </div>
            @else
                <div class="exam-empty">
                    <div class="exam-empty-icon" aria-hidden="true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="exam-empty-title">No exam submissions yet</h3>
                    <p class="exam-empty-text">
                        {{ $isAdmin ? 'No papers have been submitted yet.' : 'You have not submitted any exam papers yet.' }}
                    </p>
                    <div style="margin-top:24px;">
                        <a href="{{ route('exam-submissions.create') }}" class="exam-button">Submit Your First Exam</a>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    setupSearchField('exam_type_filter_search', 'exam_type_filter', 'exam_type_filter_options');
    setupSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options');
    setupSearchField('year_filter_search', 'year_filter', 'year_filter_options');
    setupSearchField('report_exam_type_search', 'report_exam_type_id', 'report_exam_type_options');
    setupSearchField('report_exam_year_search', 'report_exam_year_id', 'report_exam_year_options');
    setupSearchField('report_user_search', 'report_user_id', 'report_user_options');

    const submissionFilterForm = document.querySelector('form[action="{{ route('exam-submissions.index') }}"]');
    if (submissionFilterForm) {
        submissionFilterForm.addEventListener('submit', function (event) {
            if (!resolveSearchField('exam_type_filter_search', 'exam_type_filter', 'exam_type_filter_options', true)) {
                event.preventDefault();
                alert('Please select Exam Type from the searchable list.');
                return;
            }

            if (!resolveSearchField('subject_filter_search', 'subject_filter', 'subject_filter_options', true)) {
                event.preventDefault();
                alert('Please select Subject from the searchable list.');
                return;
            }

            if (!resolveSearchField('year_filter_search', 'year_filter', 'year_filter_options', true)) {
                event.preventDefault();
            }
        });
    }

    const finalReportForm = document.querySelector('form[action="{{ route('exam-submissions.final-report') }}"]');
    if (finalReportForm) {
        finalReportForm.addEventListener('submit', function (event) {
            const examTypeOk = resolveSearchField('report_exam_type_search', 'report_exam_type_id', 'report_exam_type_options');
            const examYearOk = resolveSearchField('report_exam_year_search', 'report_exam_year_id', 'report_exam_year_options');
            const userOk = {{ $isAdmin ? 'resolveSearchField(\'report_user_search\', \'report_user_id\', \'report_user_options\')' : 'true' }};

            if (!examTypeOk || !examYearOk || !userOk) {
                event.preventDefault();
                alert('Please choose the final report filters from the searchable lists.');
            }
        });
    }

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

        const matched = options.find(option => option.value === value);

        if (hidden) {
            hidden.value = matched ? (matched.dataset.id || '') : '';
        }

        return Boolean(matched);
    }
});
</script>
@endsection
