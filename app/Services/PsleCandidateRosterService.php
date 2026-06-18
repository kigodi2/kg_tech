<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\ExamType;

class PsleCandidateRosterService
{
    /**
     * Get the canonical PSLE registered candidate roster query for the selected year and optional school.
     *
     * @param int $examYearId
     * @param int|null $schoolId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function rosterQuery(int $examYearId, ?int $schoolId = null)
    {
        $psleType = ExamType::where('code', 'PSLE')->first();
        $psleTypeId = $psleType ? $psleType->id : 4; // Default fallback to 4

        $query = Candidate::query()
            ->where('candidates.is_active', true)
            ->where(function ($q) use ($psleTypeId) {
                $q->where('candidates.exam_type', 'PSLE')
                  ->orWhereHas('examRegistrations', fn($r) => $r->where('exam_type_id', $psleTypeId));
            })
            ->whereHas('examRegistrations', function ($q) use ($psleTypeId, $examYearId) {
                $q->where('exam_type_id', $psleTypeId)
                  ->where('exam_year_id', $examYearId);
            });

        if ($schoolId) {
            $query->where('candidates.school_id', $schoolId);
        }

        return $query;
    }

    /**
     * Deduplicate a collection of candidates, prioritizing the correct school code prefix in candidate_id.
     *
     * @param \Illuminate\Support\Collection $candidates
     * @param string|null $schoolCode
     * @return \Illuminate\Support\Collection
     */
    public static function deduplicate($candidates, ?string $schoolCode = null)
    {
        if (empty($schoolCode) && $candidates->isNotEmpty()) {
            $first = $candidates->first();
            if ($first && $first->school_id) {
                $school = \App\Models\School::find($first->school_id);
                $schoolCode = $school ? $school->code : '';
            }
        }

        // Sort candidates so that those starting with the correct school code prefix are positioned first.
        // Within groups, keep the natural candidate_id alphabetical ascending order.
        $sortedCandidates = $candidates->sort(function ($a, $b) use ($schoolCode) {
            if (empty($schoolCode)) {
                return strcmp($a->candidate_id ?? '', $b->candidate_id ?? '');
            }
            $aCorrect = (strpos($a->candidate_id ?? '', $schoolCode . '-') === 0);
            $bCorrect = (strpos($b->candidate_id ?? '', $schoolCode . '-') === 0);

            if ($aCorrect && !$bCorrect) {
                return -1;
            }
            if (!$aCorrect && $bCorrect) {
                return 1;
            }

            return strcmp($a->candidate_id ?? '', $b->candidate_id ?? '');
        });

        $uniqueCandidates = collect();
        $seenKeys = [];

        foreach ($sortedCandidates as $cand) {
            // Uniquely identify a physical candidate by prem_no (if present), or full name + gender
            $key = !empty($cand->prem_no)
                ? 'prem_' . $cand->prem_no
                : 'name_' . strtolower(trim($cand->full_name ?? '')) . '_' . trim($cand->gender ?? '');

            if (!in_array($key, $seenKeys, true)) {
                $seenKeys[] = $key;
                $uniqueCandidates->push($cand);
            }
        }

        return $uniqueCandidates->sortBy('candidate_id')->values();
    }

    /**
     * Get the count of deduplicated registered candidates for the given exam year and school.
     *
     * @param int $examYearId
     * @param int|null $schoolId
     * @return int
     */
    public static function getDeduplicatedCount(int $examYearId, ?int $schoolId = null): int
    {
        $candidates = self::rosterQuery($examYearId, $schoolId)->get();
        return self::deduplicate($candidates)->count();
    }
}
