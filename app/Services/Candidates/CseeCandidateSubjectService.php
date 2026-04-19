<?php

namespace App\Services\Candidates;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\CandidateSubjectSelection;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use App\Models\SubjectMarks;
use Illuminate\Support\Facades\Auth;

class CseeCandidateSubjectService
{
    public const CORE_SUBJECT_CODES = ['011', '012', '013', '021', '022', '033', '041'];
    public const MAX_TOTAL_SUBJECTS = 10;

    public function ensureCoreSubjects(Candidate $candidate, ?ExamYear $examYear = null): array
    {
        [$examType, $examYear] = $this->resolveContext($examYear);
        $this->ensureExamRegistration($candidate, $examType, $examYear);

        $coreSubjects = $this->resolveCoreSubjects($examType);
        $created = 0;

        foreach ($coreSubjects as $subject) {
            $selection = CandidateSubjectSelection::firstOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'year' => (int) $examYear->year_label,
                    'is_active' => true,
                    'is_principal' => false,
                    'source' => 'auto-core',
                    'created_by' => Auth::id(),
                ]
            );

            if ($selection->wasRecentlyCreated) {
                $created++;
            }
        }

        return [
            'created' => $created,
            'core_subject_ids' => $coreSubjects->pluck('id')->all(),
        ];
    }

    public function syncSubjects(Candidate $candidate, array $subjectIds, ?ExamYear $examYear = null): array
    {
        [$examType, $examYear] = $this->resolveContext($examYear);
        $this->ensureExamRegistration($candidate, $examType, $examYear);

        $coreSubjects = $this->resolveCoreSubjects($examType);
        $coreIds = $coreSubjects->pluck('id')->map(fn ($id) => (int) $id)->all();
        $requestedIds = collect($subjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $allSelectedIds = $requestedIds->merge($coreIds)->unique()->values();

        if ($allSelectedIds->count() > self::MAX_TOTAL_SUBJECTS) {
            throw new \InvalidArgumentException('CSEE candidates cannot be assigned more than 10 subjects.');
        }

        $validSubjects = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->whereIn('id', $allSelectedIds->all())
            ->get();

        if ($validSubjects->count() !== $allSelectedIds->count()) {
            throw new \InvalidArgumentException('One or more selected subjects are not valid CSEE subjects.');
        }

        $existingSelections = CandidateSubjectSelection::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->get();

        $selectionIdsToRemove = $existingSelections
            ->whereNotIn('subject_id', $allSelectedIds->all())
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectionIdsToRemove->isNotEmpty()) {
            $marksExist = SubjectMarks::query()
                ->where('candidate_id', $candidate->id)
                ->where('exam_type_id', $examType->id)
                ->whereIn('subject_id', $selectionIdsToRemove->all())
                ->exists();

            if ($marksExist) {
                throw new \InvalidArgumentException('Cannot remove subjects that already have marks recorded.');
            }
        }

        CandidateSubjectSelection::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->whereNotIn('subject_id', $allSelectedIds->all())
            ->delete();

        foreach ($validSubjects as $subject) {
            CandidateSubjectSelection::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'year' => (int) $examYear->year_label,
                    'is_active' => true,
                    'is_principal' => false,
                    'source' => in_array((int) $subject->id, $coreIds, true) ? 'auto-core' : 'manual-csee',
                    'created_by' => Auth::id(),
                ]
            );
        }

        return [
            'subject_ids' => $allSelectedIds->all(),
            'core_subject_ids' => $coreIds,
            'total_subjects' => $allSelectedIds->count(),
        ];
    }

    public function syncRegisteredSubjects(Candidate $candidate, array $subjectIds, ?ExamYear $examYear = null): array
    {
        [$examType, $examYear] = $this->resolveContext($examYear);
        $this->ensureExamRegistration($candidate, $examType, $examYear);

        $requestedIds = collect($subjectIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($requestedIds->count() > self::MAX_TOTAL_SUBJECTS) {
            throw new \InvalidArgumentException('CSEE candidates cannot be assigned more than 10 subjects.');
        }

        $validSubjects = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->whereIn('id', $requestedIds->all())
            ->get();

        if ($validSubjects->count() !== $requestedIds->count()) {
            throw new \InvalidArgumentException('One or more selected subjects are not valid CSEE subjects.');
        }

        $existingSelections = CandidateSubjectSelection::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->get();

        $selectionIdsToRemove = $existingSelections
            ->whereNotIn('subject_id', $requestedIds->all())
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($selectionIdsToRemove->isNotEmpty()) {
            $marksExist = SubjectMarks::query()
                ->where('candidate_id', $candidate->id)
                ->where('exam_type_id', $examType->id)
                ->whereIn('subject_id', $selectionIdsToRemove->all())
                ->exists();

            if ($marksExist) {
                throw new \InvalidArgumentException('Cannot remove subjects that already have marks recorded.');
            }
        }

        CandidateSubjectSelection::query()
            ->where('candidate_id', $candidate->id)
            ->where('exam_type_id', $examType->id)
            ->where('exam_year_id', $examYear->id)
            ->whereNotIn('subject_id', $requestedIds->all())
            ->delete();

        $coreIds = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->whereIn('code', self::CORE_SUBJECT_CODES)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($validSubjects as $subject) {
            CandidateSubjectSelection::updateOrCreate(
                [
                    'candidate_id' => $candidate->id,
                    'exam_type_id' => $examType->id,
                    'exam_year_id' => $examYear->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'year' => (int) $examYear->year_label,
                    'is_active' => true,
                    'is_principal' => false,
                    'source' => 'pdf-registration',
                    'created_by' => Auth::id(),
                ]
            );
        }

        return [
            'subject_ids' => $requestedIds->all(),
            'core_subject_ids' => array_values(array_intersect($requestedIds->all(), $coreIds)),
            'total_subjects' => $requestedIds->count(),
        ];
    }

    private function resolveContext(?ExamYear $examYear = null): array
    {
        $examType = ExamType::where('code', 'CSEE')->first();
        if (!$examType) {
            throw new \RuntimeException('CSEE exam type is not configured.');
        }

        $resolvedExamYear = $examYear ?: ExamYear::where('is_active', true)->first();
        if (!$resolvedExamYear) {
            throw new \RuntimeException('No active exam year found for CSEE subject assignment.');
        }

        return [$examType, $resolvedExamYear];
    }

    private function resolveCoreSubjects(ExamType $examType)
    {
        $subjects = Subject::query()
            ->where('exam_type_id', $examType->id)
            ->whereIn('code', self::CORE_SUBJECT_CODES)
            ->get();

        if ($subjects->count() !== count(self::CORE_SUBJECT_CODES)) {
            throw new \RuntimeException('The configured CSEE core subjects are incomplete in the subject catalog.');
        }

        return $subjects;
    }

    private function ensureExamRegistration(Candidate $candidate, ExamType $examType, ExamYear $examYear): void
    {
        CandidateExamRegistration::updateOrCreate(
            [
                'candidate_id' => $candidate->id,
                'exam_type_id' => $examType->id,
                'exam_year_id' => $examYear->id,
            ],
            [
                'year' => (int) $examYear->year_label,
                'registration_number' => 'REG-' . uniqid(),
                'is_active' => true,
                'is_verified' => false,
            ]
        );
    }
}
