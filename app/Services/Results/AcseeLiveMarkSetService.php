<?php

namespace App\Services\Results;

use App\Models\SubjectMarks;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AcseeLiveMarkSetService
{
    public function readyBatchPairKeys(Builder $readyBatches): array
    {
        return (clone $readyBatches)
            ->get(['school_id', 'subject_id'])
            ->filter(fn ($row) => !empty($row->school_id) && !empty($row->subject_id))
            ->map(fn ($row) => (int) $row->school_id . ':' . (int) $row->subject_id)
            ->unique()
            ->values()
            ->all();
    }

    public function currentLiveSubjectMarksCollection(
        Request $request,
        int $examTypeId,
        int $year,
        callable $applyScopeFilters,
        bool $withSubject = false,
        ?array $candidateIds = null,
        ?array $readyPairKeys = null
    ): Collection {
        $query = SubjectMarks::query()
            ->join('candidates', 'subject_marks.candidate_id', '=', 'candidates.id')
            ->leftJoin('schools', 'candidates.school_id', '=', 'schools.id')
            ->where('subject_marks.exam_type_id', $examTypeId)
            ->where('subject_marks.year', $year)
            ->whereNull('subject_marks.snapshot_id')
            ->orderBy('subject_marks.id');

        if ($withSubject) {
            $query->with('subject:id,code,name,written_papers,has_practical');
        }

        if ($candidateIds !== null && !empty($candidateIds)) {
            $query->whereIn('subject_marks.candidate_id', $candidateIds);
        }

        $applyScopeFilters($query, $request, 'candidates', 'schools');

        $latestByKey = [];
        $query->select([
            'subject_marks.*',
            DB::raw('subject_marks.id as chunk_id'),
            DB::raw('candidates.school_id as candidate_school_id'),
        ])->chunkById(5000, function ($rows) use (&$latestByKey, $readyPairKeys) {
            foreach ($rows as $row) {
                $pairKey = (int) ($row->candidate_school_id ?? 0) . ':' . (int) ($row->subject_id ?? 0);
                if ($readyPairKeys !== null && !in_array($pairKey, $readyPairKeys, true)) {
                    continue;
                }

                $dedupeKey = (int) $row->candidate_id . ':' . (int) $row->subject_id;
                $latestByKey[$dedupeKey] = $row;
            }
        }, 'subject_marks.id', 'chunk_id');

        return collect(array_values($latestByKey));
    }
}
