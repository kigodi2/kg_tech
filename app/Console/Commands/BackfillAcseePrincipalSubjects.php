<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\Combination;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\Subject;
use Illuminate\Console\Command;

class BackfillAcseePrincipalSubjects extends Command
{
    protected $signature = 'acsee:backfill-principal-subjects {--exam-year=2026}';

    protected $description = 'Backfill ACSEE candidate subject selections is_principal using combination subjects (all except GS code 111).';

    public function handle(): int
    {
        $examYearLabel = (string) $this->option('exam-year');

        $acsee = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $examYear = ExamYear::query()->where('year_label', $examYearLabel)->first();
        if (!$examYear) {
            $this->error("Exam year {$examYearLabel} not found.");
            return self::FAILURE;
        }

        $gsId = Subject::query()->where('code', '111')->value('id');
        $bamId = Subject::query()->where('code', '141')->value('id');
        if (!$gsId) {
            $this->warn('General Studies subject (code 111) not found; no GS exclusion will be applied.');
        }
        if (!$bamId) {
            $this->warn('Basic Applied Mathematics subject (code 141) not found; no BAM exclusion will be applied.');
        }

        $this->info("Backfilling ACSEE principal subjects for exam year {$examYearLabel}...");

        $updatedCandidates = 0;
        $updatedRows = 0;
        $skippedNoCombination = 0;

        Candidate::query()
            ->whereHas('examRegistrations', function ($q) use ($acsee, $examYear) {
                $q->where('exam_type_id', $acsee->id)
                    ->where('exam_year_id', $examYear->id);
            })
            ->orderBy('id')
            ->chunkById(500, function ($candidates) use (
                $acsee,
                $examYear,
                $gsId,
                $bamId,
                &$updatedCandidates,
                &$updatedRows,
                &$skippedNoCombination
            ) {
                foreach ($candidates as $candidate) {
                    $isPrivate = strtoupper((string) ($candidate->candidate_type ?? '')) === 'PRIVATE';
                    $combination = null;

                    if (!empty($candidate->combination_id)) {
                        $combination = Combination::query()
                            ->where('id', $candidate->combination_id)
                            ->where('exam_type_id', $acsee->id)
                            ->first();
                    }

                    if (!$combination && !empty($candidate->combination)) {
                        $combination = Combination::query()
                            ->where('code', $candidate->combination)
                            ->where('exam_type_id', $acsee->id)
                            ->first();
                    }

                    $subjectIdsForScope = [];
                    if ($isPrivate) {
                        $subjectIdsForScope = CandidateSubjectSelection::query()
                            ->where('candidate_id', $candidate->id)
                            ->where('exam_type_id', $acsee->id)
                            ->where(function ($q) use ($examYear) {
                                $q->where('exam_year_id', $examYear->id)
                                    ->orWhere('year', (string) $examYear->year_label)
                                    ->orWhere('year', (int) $examYear->year_label);
                            })
                            ->pluck('subject_id')
                            ->map(fn ($id) => (int) $id)
                            ->all();
                    } else {
                        if (!$combination) {
                            $skippedNoCombination++;
                            continue;
                        }

                        $subjectIdsForScope = $combination->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
                    }

                    if (empty($subjectIdsForScope)) {
                        continue;
                    }

                    $principalIds = array_values(array_filter($subjectIdsForScope, function ($id) use ($gsId, $bamId) {
                        if ($gsId && (int) $id === (int) $gsId) {
                            return false;
                        }
                        if ($bamId && (int) $id === (int) $bamId) {
                            return false;
                        }
                        return true;
                    }));

                    $scope = CandidateSubjectSelection::query()
                        ->where('candidate_id', $candidate->id)
                        ->where('exam_type_id', $acsee->id)
                        ->where(function ($q) use ($examYear) {
                            $q->where('exam_year_id', $examYear->id)
                                ->orWhere('year', (string) $examYear->year_label)
                                ->orWhere('year', (int) $examYear->year_label);
                        })
                        ->whereIn('subject_id', $subjectIdsForScope);

                    $resetCount = (clone $scope)->update(['is_principal' => false, 'is_active' => true]);
                    $setCount = 0;
                    if (!empty($principalIds)) {
                        $setCount = (clone $scope)->whereIn('subject_id', $principalIds)->update(['is_principal' => true, 'is_active' => true]);
                    }

                    if (($resetCount + $setCount) > 0) {
                        $updatedCandidates++;
                        $updatedRows += ($resetCount + $setCount);
                    }
                }
            });

        $this->info("Updated candidates: {$updatedCandidates}");
        $this->info("Updated rows (writes): {$updatedRows}");
        $this->info("Skipped (no combination): {$skippedNoCombination}");
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
