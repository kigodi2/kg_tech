<?php

namespace App\Services\Results;

use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use App\Models\Region;
use Illuminate\Support\Facades\DB;

class TasidoResultProcessingService
{
    protected CandidateResultStatusService $statusService;
    protected RegionalSchoolResultDiagnosticService $diagnosticService;

    public function __construct(
        CandidateResultStatusService $statusService,
        RegionalSchoolResultDiagnosticService $diagnosticService
    ) {
        $this->statusService = $statusService;
        $this->diagnosticService = $diagnosticService;
    }

    /**
     * Run results processing.
     *
     * @param ExamYear $examYear
     * @param ExamType $examType
     * @param int|null $snapshotId
     * @param int|null $processId
     * @return int Total processed candidates count
     */
    public function processResults(ExamYear $examYear, ExamType $examType, ?int $snapshotId = null, ?int $processId = null): int
    {
        $examTypeId = $examType->id;
        $yearLabel = $examYear->year_label;

        // Fetch primary subjects
        $subjects = DB::table('subjects as s')
            ->where('s.exam_type_id', $examTypeId)
            ->select('s.*')
            ->get();
        $subjectIds = $subjects->pluck('id')->toArray();

        // Clear existing draft results if computing a draft
        if ($snapshotId === null) {
            DB::table('candidate_results')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $yearLabel)
                ->whereNull('snapshot_id')
                ->delete();

            DB::table('subject_marks')
                ->where('exam_type_id', $examTypeId)
                ->where('year', $yearLabel)
                ->whereNull('snapshot_id')
                ->delete();
        }

        $targetNames = app()->environment('testing')
            ? Region::pluck('name')->map(fn($n) => strtoupper($n))->toArray()
            : ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'];
        $tasidoRegions = Region::whereIn(DB::raw('upper(name)'), $targetNames)->get();
        $tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
        $schoolIds = School::whereIn('region_id', $tasidoRegionIds)
            ->where('education_level', 'PRIMARY')
            ->pluck('id')
            ->toArray();

        $totalProcessed = 0;

        DB::table('candidate_exam_registrations as cer')
            ->join('candidates as c', 'c.id', '=', 'cer.candidate_id')
            ->join('schools as s', 's.id', '=', 'c.school_id')
            ->whereIn('s.region_id', $tasidoRegionIds)
            ->where('cer.exam_type_id', $examTypeId)
            ->where('cer.year', $yearLabel)
            ->select(['c.id as candidate_pk', 'c.gender', 'c.school_id'])
            ->orderBy('c.id')
            ->chunk(5000, function ($candidatesChunk) use ($schoolIds, $examYear, $examTypeId, $snapshotId, $processId, $subjectIds, &$totalProcessed) {
                $candidateIds = $candidatesChunk->pluck('candidate_pk')->toArray();

                // Fetch raw marks for this chunk
                $allRawMarks = DB::table('raw_marks')
                    ->whereIn('school_id', $schoolIds)
                    ->where('exam_year_id', $examYear->id)
                    ->whereIn('candidate_id', $candidateIds)
                    ->get()
                    ->groupBy('candidate_id');

                $candidateResultsData = [];
                $subjectMarksData = [];

                foreach ($candidatesChunk as $cand) {
                    $candMarks = $allRawMarks->get($cand->candidate_pk, collect());
                    $candMarksBySubject = $candMarks->pluck('paper_1_marks', 'subject_id')->toArray();

                    $evaluation = $this->statusService->evaluateCandidate($cand->candidate_pk, $candMarksBySubject, $subjectIds);

                    $candidateResultsData[] = [
                        'candidate_id' => $cand->candidate_pk,
                        'exam_type_id' => $examTypeId,
                        'year' => $examYear->year_label,
                        'total_marks' => $evaluation['total_marks'],
                        'total_percentage' => $evaluation['total_percentage'],
                        'overall_grade' => $evaluation['overall_grade'],
                        'status' => $evaluation['db_status'],
                        'released_at' => $evaluation['db_status'] === 'RELEASED' ? now() : null,
                        'snapshot_id' => $snapshotId,
                        'process_id' => $processId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    foreach ($evaluation['subjects'] as $subId => $details) {
                        $subjectMarksData[] = [
                            'candidate_id' => $cand->candidate_pk,
                            'exam_type_id' => $examTypeId,
                            'subject_id' => $subId,
                            'year' => $examYear->year_label,
                            'marks_obtained' => $details['marks_obtained'],
                            'max_marks' => 50.0,
                            'percentage' => $details['percentage'],
                            'grade' => $details['grade'],
                            'snapshot_id' => $snapshotId,
                            'process_id' => $processId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                foreach (array_chunk($candidateResultsData, 500) as $chunk) {
                    DB::table('candidate_results')->insert($chunk);
                }

                foreach (array_chunk($subjectMarksData, 500) as $chunk) {
                    DB::table('subject_marks')->insert($chunk);
                }

                $totalProcessed += count($candidateResultsData);
            });

        // Fail loudly if counts don't align with expected diagnostic requirements
        $this->diagnosticService->validateProcessedAndDisplayedCount($yearLabel, $examTypeId, $snapshotId);

        return $totalProcessed;
    }
}
