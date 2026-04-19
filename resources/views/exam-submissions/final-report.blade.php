@extends('layouts.auth-rms')

@section('title', 'NECTA Consolidated Final Validation Report')

@section('content')
@php
    $summary = $report['summary'];
    $determination = $report['overall_determination'];
    $generatedBy = $report['generated_by'];
    $scopeSchools = $report['scope_school_names'];
@endphp

<style>
    .final-report-page {
        width: min(1180px, calc(100% - 32px));
        margin: 32px auto 40px;
    }

    .final-report-stack {
        display: grid;
        gap: 20px;
    }

    .final-report-card {
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid rgba(203, 213, 225, 0.92);
        border-radius: 20px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .final-report-header {
        padding: 28px;
        border-bottom: 1px solid #e2e8f0;
    }

    .final-report-kicker {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .final-report-title {
        margin: 10px 0 0;
        color: #0f172a;
        font-size: clamp(28px, 4vw, 40px);
        line-height: 1.12;
        font-weight: 800;
    }

    .final-report-subtitle {
        margin: 12px 0 0;
        color: #475569;
        font-size: 15px;
        line-height: 1.7;
        max-width: 820px;
    }

    .final-report-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 20px;
    }

    .final-report-button,
    .final-report-button-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 42px;
        padding: 0 16px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 700;
        border: 1px solid transparent;
    }

    .final-report-button {
        color: #ffffff;
        background: #0f172a;
        border-color: #0f172a;
    }

    .final-report-button-secondary {
        color: #334155;
        background: #ffffff;
        border-color: #cbd5e1;
    }

    .final-report-body {
        padding: 24px 28px 28px;
    }

    .final-report-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .final-report-panel {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        padding: 18px;
    }

    .final-report-panel h2 {
        margin: 0 0 12px;
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
    }

    .final-report-meta {
        display: grid;
        gap: 12px;
    }

    .final-report-meta-row dt {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .final-report-meta-row dd {
        margin: 0;
        color: #0f172a;
        font-size: 14px;
        line-height: 1.6;
    }

    .final-report-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .final-report-stat {
        padding: 16px;
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }

    .final-report-stat-label {
        margin: 0;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .final-report-stat-value {
        margin: 10px 0 0;
        color: #0f172a;
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
    }

    .final-report-determination {
        border-radius: 18px;
        padding: 22px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
    }

    .final-report-determination--ready_for_official_submission {
        background: #ecfdf5;
        border-color: #86efac;
    }

    .final-report-determination--awaiting_administrative_clearance {
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .final-report-determination--attention_required,
    .final-report-determination--no_submissions_received,
    .final-report-determination--no_official_subject_catalog {
        background: #fff7ed;
        border-color: #fdba74;
    }

    .final-report-determination h2 {
        margin: 0;
        color: #0f172a;
        font-size: 20px;
        font-weight: 800;
    }

    .final-report-determination p {
        margin: 12px 0 0;
        color: #334155;
        font-size: 15px;
        line-height: 1.7;
    }

    .final-report-section {
        margin-top: 22px;
    }

    .final-report-section h2 {
        margin: 0 0 12px;
        color: #0f172a;
        font-size: 20px;
        font-weight: 800;
    }

    .final-report-section li,
    .final-report-section td {
        color: #475569;
        font-size: 14px;
        line-height: 1.7;
    }

    .final-report-list {
        margin: 0;
        padding-left: 20px;
    }

    .final-report-table-wrap {
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #ffffff;
    }

    .final-report-table {
        width: 100%;
        border-collapse: collapse;
    }

    .final-report-table th,
    .final-report-table td {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
        vertical-align: top;
    }

    .final-report-table th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .final-report-subject {
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.5;
    }

    .final-report-subject-code {
        color: #64748b;
        font-size: 12px;
        line-height: 1.5;
    }

    .final-report-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        border: 1px solid transparent;
    }

    .final-report-badge--ready,
    .final-report-badge--approved,
    .final-report-badge--compliant {
        color: #166534;
        background: #ecfdf5;
        border-color: #86efac;
    }

    .final-report-badge--pending_review {
        color: #1d4ed8;
        background: #eff6ff;
        border-color: #93c5fd;
    }

    .final-report-badge--rejected,
    .final-report-badge--attention_required,
    .final-report-badge--missing,
    .final-report-badge--not_submitted {
        color: #c2410c;
        background: #fff7ed;
        border-color: #fdba74;
    }

    .final-report-footer {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-top: 24px;
    }

    .final-report-signature {
        padding-top: 26px;
        border-top: 1px solid #94a3b8;
        color: #334155;
        font-size: 13px;
    }

    @media print {
        .final-report-actions,
        .main-nav,
        .mobile-nav {
            display: none !important;
        }

        .final-report-page {
            width: 100%;
            margin: 0;
        }

        .final-report-card {
            box-shadow: none;
            border-color: #cbd5e1;
        }
    }

    @media (max-width: 900px) {
        .final-report-grid,
        .final-report-footer,
        .final-report-stats {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="final-report-page">
    <div class="final-report-stack">
        <section class="final-report-card">
            <div class="final-report-header">
                <p class="final-report-kicker">Government Formal Submission Record</p>
                <h1 class="final-report-title">NECTA Consolidated Final Validation Report</h1>
                <p class="final-report-subtitle">
                    This document consolidates the latest subject paper records lodged under the selected examination activity against the official NECTA subject catalog extracted from the configured format guide. It summarizes completeness, validation standing, and matters requiring attention before forwarding the activity as a final subject bundle.
                </p>

                <div class="final-report-actions">
                    <a href="{{ route('exam-submissions.index') }}" class="final-report-button-secondary">Back to Submissions</a>
                    <button type="button" onclick="window.print()" class="final-report-button">Print Report</button>
                </div>
            </div>

            <div class="final-report-body">
                <div class="final-report-grid">
                    <section class="final-report-panel">
                        <h2>Document Particulars</h2>
                        <dl class="final-report-meta">
                            <div class="final-report-meta-row">
                                <dt>Reference Number</dt>
                                <dd>{{ $report['reference_number'] }}</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Examination Activity</dt>
                                <dd>{{ $report['exam_type']->name }} ({{ $report['exam_type']->code }})</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Examination Year</dt>
                                <dd>{{ $report['exam_year']->year_label }}</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Submitting Account</dt>
                                <dd>{{ $report['submitter']->name }}@if($report['submitter']->email) ({{ $report['submitter']->email }})@endif</dd>
                            </div>
                            @if(!empty($scopeSchools))
                                <div class="final-report-meta-row">
                                    <dt>School Coverage</dt>
                                    <dd>{{ implode(', ', $scopeSchools) }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>

                    <section class="final-report-panel">
                        <h2>Preparation Record</h2>
                        <dl class="final-report-meta">
                            <div class="final-report-meta-row">
                                <dt>Report Generated At</dt>
                                <dd>{{ $report['generated_at']->format('d M Y \a\t H:i') }}</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Prepared By</dt>
                                <dd>{{ $generatedBy?->name ?? 'System User' }}@if($generatedBy?->email) ({{ $generatedBy->email }})@endif</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Coverage Rate</dt>
                                <dd>{{ $summary['coverage_percentage'] }}% of official format subjects have a paper on record.</dd>
                            </div>
                            <div class="final-report-meta-row">
                                <dt>Readiness Position</dt>
                                <dd>{{ $determination['label'] }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <section class="final-report-section">
                    <div class="final-report-stats">
                        <div class="final-report-stat">
                            <p class="final-report-stat-label">Official Subjects</p>
                            <p class="final-report-stat-value">{{ $summary['expected_subjects'] }}</p>
                        </div>
                        <div class="final-report-stat">
                            <p class="final-report-stat-label">Submitted</p>
                            <p class="final-report-stat-value">{{ $summary['submitted_subjects'] }}</p>
                        </div>
                        <div class="final-report-stat">
                            <p class="final-report-stat-label">Ready</p>
                            <p class="final-report-stat-value">{{ $summary['ready_subjects'] }}</p>
                        </div>
                        <div class="final-report-stat">
                            <p class="final-report-stat-label">Pending</p>
                            <p class="final-report-stat-value">{{ $summary['pending_subjects'] }}</p>
                        </div>
                        <div class="final-report-stat">
                            <p class="final-report-stat-label">Outstanding</p>
                            <p class="final-report-stat-value">{{ $report['outstanding_subjects']->count() }}</p>
                        </div>
                    </div>
                </section>

                <section class="final-report-section">
                    <div class="final-report-determination final-report-determination--{{ $determination['state'] }}">
                        <h2>Overall Determination: {{ $determination['label'] }}</h2>
                        <p>{{ $determination['statement'] }}</p>
                    </div>
                </section>

                <section class="final-report-section">
                    <h2>Formal Assessment Remarks</h2>
                    <ul class="final-report-list">
                        @foreach($report['formal_remarks'] as $remark)
                            <li>{{ $remark }}</li>
                        @endforeach
                    </ul>
                </section>

                <section class="final-report-section">
                    <h2>Subject Schedule for Official Submission</h2>
                    <div class="final-report-table-wrap">
                        <table class="final-report-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Submission Reference</th>
                                    <th>Administrative Position</th>
                                    <th>Format Position</th>
                                    <th>Compliance Score</th>
                                    <th>Determination</th>
                                    <th>Primary Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['subjects'] as $subjectReport)
                                    <tr>
                                        <td>
                                            <div class="final-report-subject">{{ $subjectReport['subject_name'] }}</div>
                                            <div class="final-report-subject-code">{{ $subjectReport['subject_code'] }}</div>
                                        </td>
                                        <td>
                                            @if($subjectReport['submission_reference'])
                                                <div class="final-report-subject">Ref. {{ $subjectReport['submission_reference'] }}</div>
                                                <div class="final-report-subject-code">{{ optional($subjectReport['submitted_at'])->format('d M Y H:i') }}</div>
                                            @else
                                                <span class="final-report-badge final-report-badge--missing">Not on Record</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="final-report-badge final-report-badge--{{ $subjectReport['review_state'] }}">
                                                {{ $subjectReport['review_label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="final-report-badge final-report-badge--{{ $subjectReport['format_state'] }}">
                                                {{ $subjectReport['format_label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $subjectReport['compliance_score'] !== null ? $subjectReport['compliance_score'] . '%' : 'N/A' }}</td>
                                        <td>{{ $subjectReport['determination'] }}</td>
                                        <td>{{ $subjectReport['remarks_summary'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @if($report['outstanding_subjects']->isNotEmpty())
                    <section class="final-report-section">
                        <h2>Outstanding Subject Matters</h2>
                        <ul class="final-report-list">
                            @foreach($report['outstanding_subjects'] as $subjectReport)
                                <li>{{ $subjectReport['subject_code'] }} - {{ $subjectReport['subject_name'] }}: {{ $subjectReport['remarks_summary'] }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="final-report-footer">
                    <div class="final-report-signature">
                        Prepared by:<br>
                        {{ $generatedBy?->name ?? 'System User' }}
                    </div>
                    <div class="final-report-signature">
                        Official receiving officer:<br>
                        ______________________________
                    </div>
                </section>
            </div>
        </section>
    </div>
</div>
@endsection
