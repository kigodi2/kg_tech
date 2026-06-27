<style>
    .incomplete-audit-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin-top: 10px;
    }
    .incomplete-warning-alert {
        border-radius: 16px;
        border: 1px solid rgba(239, 68, 68, 0.3);
        background: rgba(127, 29, 29, 0.15);
        padding: 20px;
    }
    .incomplete-alert-flex {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    @media (min-width: 768px) {
        .incomplete-alert-flex {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }
    .incomplete-badge-counter {
        border-radius: 12px;
        border: 1px solid rgba(239, 68, 68, 0.3);
        background: rgba(239, 68, 68, 0.1);
        padding: 12px 16px;
        text-align: right;
        min-width: 180px;
    }
    .badge-entered {
        border-radius: 9999px;
        border: 1px solid rgba(52, 211, 153, 0.3);
        background: rgba(16, 185, 129, 0.1);
        padding: 4px 8px;
        font-size: 0.72rem;
        font-weight: 600;
        color: #a7f3d0;
        display: inline-block;
        margin: 2px;
    }
    .badge-missing {
        border-radius: 9999px;
        border: 1px solid rgba(248, 113, 113, 0.3);
        background: rgba(239, 68, 68, 0.1);
        padding: 4px 8px;
        font-size: 0.72rem;
        font-weight: 700;
        color: #fecaca;
        display: inline-block;
        margin: 2px;
    }
    .badge-status-incomplete {
        border-radius: 9999px;
        border: 1px solid rgba(250, 204, 21, 0.3);
        background: rgba(234, 179, 8, 0.1);
        padding: 4px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fef08a;
        display: inline-block;
    }
    .action-btn-open {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 8px;
        background: rgba(6, 182, 212, 0.15);
        padding: 8px 12px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #a5f3fc;
        border: 1px solid rgba(34, 211, 238, 0.3);
        text-decoration: none;
        transition: all 0.2s;
    }
    .action-btn-open:hover {
        background: rgba(6, 182, 212, 0.25);
        color: #ffffff;
    }
</style>

<div class="incomplete-audit-container">
    <div class="incomplete-warning-alert">
        <div class="incomplete-alert-flex">
            <div>
                <h2 style="font-size: 1.25rem; font-weight: 700; color: #ffffff; margin: 0 0 6px 0;">Incomplete Candidates Audit</h2>
                <p style="font-size: 0.88rem; color: #cbd5e1; margin: 0; line-height: 1.4;">
                    Candidates shown here have fewer than {{ $viewData['requiredSubjectCount'] ?? 6 }} required PSLE subject marks.
                    Resolve the missing marks before locking and republishing final results.
                </p>
            </div>

            <div class="incomplete-badge-counter">
                <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.5px; color: #fecaca; margin-bottom: 2px; font-weight: 700;">Incomplete Candidates</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #fca5a5;">{{ number_format($viewData['totalIncomplete'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <div class="adm-card" style="margin-bottom: 0;">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Candidate No.</th>
                        <th>Candidate Name</th>
                        <th>School</th>
                        <th>District</th>
                        <th>Subjects Entered</th>
                        <th>Missing Subjects</th>
                        <th>Status</th>
                        <th>Last Updated By</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse(($viewData['candidates'] ?? collect()) as $candidate)
                        <tr>
                            <td style="font-weight: 700; color: #fcd116;">
                                {{ $candidate->cno }}
                            </td>

                            <td style="color: #ffffff; font-weight: 600;">
                                {{ $candidate->full_name }}
                            </td>

                            <td style="color: #cbd5e1;">
                                {{ $candidate->school_name }}
                            </td>

                            <td style="color: #cbd5e1;">
                                {{ $candidate->district_name }}
                            </td>

                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @forelse($candidate->entered_marks as $mark)
                                        <span class="badge-entered">
                                            {{ $mark->subject_name }}: {{ $mark->marks_obtained }}
                                        </span>
                                    @empty
                                        <span style="font-size: 0.75rem; color: #9ca3af; font-style: italic;">
                                            No marks entered
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @foreach($candidate->missing_subjects as $subject)
                                        <span class="badge-missing">
                                            {{ $subject->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            <td>
                                <span class="badge-status-incomplete">
                                    {{ $candidate->completion_label }}
                                </span>
                            </td>

                            <td style="color: #cbd5e1; font-size: 0.82rem;">
                                {{ $candidate->last_updated_by }}
                            </td>

                            <td class="text-right">
                                @if($candidate->entry_url)
                                    <a href="{{ $candidate->entry_url }}" class="action-btn-open">
                                        <i class="fas fa-edit"></i>
                                        Open Entry Sheet
                                    </a>
                                @else
                                    <span style="font-size: 0.75rem; color: #64748b;">No action</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="padding: 40px; text-align: center; color: #9ca3af; font-style: italic;">
                                No incomplete candidates found. Data readiness is complete.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($viewData['candidates']) && method_exists($viewData['candidates'], 'links'))
            <div style="padding: 16px; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: center;">
                {{ $viewData['candidates']->links() }}
            </div>
        @endif
    </div>
</div>
