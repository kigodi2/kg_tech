<?php

namespace App\Services\ExamFormatValidation;

use App\Models\ExamSubmission;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\User;
use Illuminate\Support\Collection;

class ExamTypeFinalReportBuilder
{
    public function __construct(
        protected NectaFormatRulebook $rulebook
    ) {
    }

    public function build(ExamType $examType, ExamYear $examYear, User $submitter): array
    {
        $officialCatalog = collect($this->rulebook->getOfficialSubjects($examType->code))
            ->sortBy('code')
            ->values();

        $submissions = ExamSubmission::query()
            ->with(['subject', 'school', 'user'])
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->where('user_id', $submitter->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $latestBySubjectCode = $submissions
            ->filter(fn (ExamSubmission $submission) => $submission->subject !== null)
            ->unique(fn (ExamSubmission $submission) => strtoupper((string) $submission->subject->code))
            ->keyBy(fn (ExamSubmission $submission) => strtoupper((string) $submission->subject->code));

        $rows = $officialCatalog->map(function (array $catalogEntry) use ($latestBySubjectCode) {
            $subjectCode = strtoupper((string) ($catalogEntry['code'] ?? ''));
            /** @var ExamSubmission|null $submission */
            $submission = $latestBySubjectCode->get($subjectCode);
            $validationResults = $submission?->validation_results ?? [];
            $isValid = (bool) ($validationResults['is_valid'] ?? false);
            $complianceScore = $validationResults['template_comparison']['compliance_score'] ?? null;
            $reviewState = $this->normalizeReviewState($submission?->status);
            $remarks = $this->buildRemarks($submission, $validationResults);

            return [
                'subject_code' => $subjectCode,
                'subject_name' => $catalogEntry['name'] ?? $subjectCode,
                'subject_group_label' => $catalogEntry['subject_group_label'] ?? 'Official Format Subjects',
                'source_page' => $catalogEntry['source_page'] ?? null,
                'submission_reference' => $submission?->id,
                'submitted_at' => $submission?->submitted_at,
                'review_state' => $reviewState,
                'review_label' => $this->reviewStateLabel($reviewState),
                'format_state' => $submission === null ? 'not_submitted' : ($isValid ? 'compliant' : 'attention_required'),
                'format_label' => $submission === null ? 'Not Submitted' : ($isValid ? 'Compliant' : 'Attention Required'),
                'compliance_score' => $complianceScore,
                'determination' => $this->subjectDetermination($submission, $reviewState, $isValid),
                'remarks' => $remarks,
                'remarks_summary' => $remarks[0] ?? 'No formal remark recorded.',
            ];
        })->values();

        $summary = $this->summarizeRows($rows);
        $generatedAt = now();

        return [
            'reference_number' => sprintf(
                'IRMS/NFVR/%s/%s/%d/%s',
                strtoupper($examType->code),
                preg_replace('/\D+/', '', (string) $examYear->year_label),
                $submitter->id,
                $generatedAt->format('YmdHis')
            ),
            'generated_at' => $generatedAt,
            'generated_by' => auth()->user(),
            'exam_type' => $examType,
            'exam_year' => $examYear,
            'submitter' => $submitter,
            'scope_school_names' => $submissions->pluck('school.name')->filter()->unique()->values()->all(),
            'summary' => $summary,
            'subjects' => $rows,
            'outstanding_subjects' => $rows
                ->filter(fn (array $row) => ! ($row['review_state'] === 'approved' && $row['format_state'] === 'compliant'))
                ->values(),
            'overall_determination' => $this->overallDetermination($summary),
            'formal_remarks' => $this->formalRemarks($summary),
        ];
    }

    protected function summarizeRows(Collection $rows): array
    {
        $expectedCount = $rows->count();
        $submittedCount = $rows->whereNotNull('submission_reference')->count();
        $approvedCount = $rows->where('review_state', 'approved')->count();
        $readyCount = $rows->filter(fn (array $row) => $row['review_state'] === 'approved' && $row['format_state'] === 'compliant')->count();
        $pendingCount = $rows->where('review_state', 'pending_review')->count();
        $rejectedCount = $rows->where('review_state', 'rejected')->count();
        $missingCount = $rows->where('review_state', 'missing')->count();
        $attentionCount = $rows->where('format_state', 'attention_required')->count();

        return [
            'expected_subjects' => $expectedCount,
            'submitted_subjects' => $submittedCount,
            'ready_subjects' => $readyCount,
            'approved_subjects' => $approvedCount,
            'pending_subjects' => $pendingCount,
            'rejected_subjects' => $rejectedCount,
            'missing_subjects' => $missingCount,
            'attention_required_subjects' => $attentionCount,
            'coverage_percentage' => $expectedCount > 0 ? (int) round(($submittedCount / $expectedCount) * 100) : 0,
        ];
    }

    protected function normalizeReviewState(?string $status): string
    {
        return match ($status) {
            'approved' => 'approved',
            'pending', 'validated' => 'pending_review',
            'rejected' => 'rejected',
            null => 'missing',
            default => 'pending_review',
        };
    }

    protected function reviewStateLabel(string $state): string
    {
        return match ($state) {
            'approved' => 'Approved for Record',
            'pending_review' => 'Pending Administrative Action',
            'rejected' => 'Returned for Correction',
            default => 'Not Yet Submitted',
        };
    }

    protected function subjectDetermination(?ExamSubmission $submission, string $reviewState, bool $isValid): string
    {
        if ($submission === null) {
            return 'No subject paper has been lodged under the selected activity.';
        }

        if ($reviewState === 'rejected') {
            return 'The subject paper has been returned for correction prior to official compilation.';
        }

        if (! $isValid) {
            return 'The subject paper remains on record, but format deficiencies require corrective attention.';
        }

        if ($reviewState === 'approved') {
            return 'The subject paper is compliant and cleared for inclusion in the official submission file.';
        }

        return 'The subject paper is structurally compliant and awaits completion of administrative review.';
    }

    protected function buildRemarks(?ExamSubmission $submission, array $validationResults): array
    {
        if ($submission === null) {
            return ['Subject paper not yet submitted for validation under the selected examination activity.'];
        }

        $remarks = collect()
            ->when(!empty($validationResults['errors']), fn (Collection $collection) => $collection->merge($validationResults['errors']))
            ->when(!empty($validationResults['warnings']), fn (Collection $collection) => $collection->merge($validationResults['warnings']))
            ->when($submission->rejection_reason, fn (Collection $collection) => $collection->prepend('Administrative note: ' . $submission->rejection_reason));

        return $remarks->filter()->values()->all();
    }

    protected function overallDetermination(array $summary): array
    {
        if ($summary['expected_subjects'] === 0) {
            return [
                'state' => 'no_official_subject_catalog',
                'label' => 'No Official Subject Catalog',
                'statement' => 'No official subject catalog was found in the configured NECTA format guide for the selected examination type. A consolidated final report cannot be completed until the format catalog is available.',
            ];
        }

        if ($summary['submitted_subjects'] === 0) {
            return [
                'state' => 'no_submissions_received',
                'label' => 'No Subject Papers Submitted',
                'statement' => 'No subject papers have been lodged under the selected activity. Official submission is therefore not yet initiated.',
            ];
        }

        if (
            $summary['missing_subjects'] === 0 &&
            $summary['attention_required_subjects'] === 0 &&
            $summary['pending_subjects'] === 0 &&
            $summary['rejected_subjects'] === 0
        ) {
            return [
                'state' => 'ready_for_official_submission',
                'label' => 'Ready for Official Submission',
                'statement' => 'All official format subjects under the selected examination activity have valid papers on record and no outstanding administrative action remains.',
            ];
        }

        if (
            $summary['missing_subjects'] === 0 &&
            $summary['attention_required_subjects'] === 0 &&
            $summary['rejected_subjects'] === 0
        ) {
            return [
                'state' => 'awaiting_administrative_clearance',
                'label' => 'Awaiting Administrative Clearance',
                'statement' => 'All official format subjects have been uploaded and found structurally compliant, but one or more records still await administrative clearance before official submission.',
            ];
        }

        return [
            'state' => 'attention_required',
            'label' => 'Attention Required Before Official Submission',
            'statement' => 'The consolidated subject file is incomplete or contains matters requiring correction before formal submission may proceed.',
        ];
    }

    protected function formalRemarks(array $summary): array
    {
        $remarks = [];

        if ($summary['missing_subjects'] > 0) {
            $remarks[] = "{$summary['missing_subjects']} official format subject(s) do not yet have a paper on record under the selected activity.";
        }

        if ($summary['attention_required_subjects'] > 0) {
            $remarks[] = "{$summary['attention_required_subjects']} submitted subject(s) contain format findings that require correction or further explanation.";
        }

        if ($summary['pending_subjects'] > 0) {
            $remarks[] = "{$summary['pending_subjects']} subject record(s) remain pending administrative review or final confirmation.";
        }

        if ($summary['rejected_subjects'] > 0) {
            $remarks[] = "{$summary['rejected_subjects']} subject record(s) were returned for correction and must be resubmitted before compilation.";
        }

        if ($remarks === []) {
            $remarks[] = 'No outstanding matter was identified at the time of generating this consolidated validation report.';
        }

        return $remarks;
    }
}
