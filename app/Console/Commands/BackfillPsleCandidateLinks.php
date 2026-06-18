<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\School;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BackfillPsleCandidateLinks extends Command
{
    protected $signature = 'psle:backfill-candidate-links
        {--year= : Exam year label, for example 2026}
        {--dry-run : Report changes without writing}';

    protected $description = 'Safely backfill nullable PSLE candidate school_id and registration exam_year_id values.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $yearLabel = (string) ($this->option('year') ?: '');
        $psleType = ExamType::where('code', 'PSLE')->first();

        if (!$psleType) {
            $this->error('PSLE exam type was not found.');
            return self::FAILURE;
        }

        $examYear = null;
        if ($yearLabel !== '') {
            $examYear = ExamYear::where('year_label', $yearLabel)->first();
            if (!$examYear) {
                $this->error("Exam year {$yearLabel} was not found.");
                return self::FAILURE;
            }
        }

        $schoolBackfill = $this->backfillSchoolIds($dryRun);
        $yearBackfill = $examYear ? $this->backfillExamYearIds($psleType->id, $examYear, $dryRun) : [
            'matched' => 0,
            'updated' => 0,
            'skipped' => 'Pass --year=YYYY to backfill exam_year_id.',
        ];

        $summary = [
            'dry_run' => $dryRun,
            'school_id_backfill' => $schoolBackfill,
            'exam_year_id_backfill' => $yearBackfill,
        ];

        Log::info('PSLE candidate link backfill summary', $summary);
        $this->info(json_encode($summary, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    private function backfillSchoolIds(bool $dryRun): array
    {
        if (!Schema::hasColumn('candidates', 'school_code')) {
            return [
                'matched' => 0,
                'updated' => 0,
                'unmatched_school_codes' => [],
                'skipped' => 'candidates.school_code column does not exist.',
            ];
        }

        $rows = Candidate::query()
            ->whereNull('school_id')
            ->where('exam_type', 'PSLE')
            ->whereNotNull('school_code')
            ->get(['id', 'school_code']);

        $updated = 0;
        $unmatched = [];

        foreach ($rows as $candidate) {
            $schoolCode = strtoupper(trim((string) $candidate->school_code));
            $schoolId = School::whereRaw('UPPER(code) = ?', [$schoolCode])->value('id');

            if (!$schoolId) {
                $unmatched[$schoolCode] = true;
                continue;
            }

            if (!$dryRun) {
                Candidate::whereKey($candidate->id)
                    ->whereNull('school_id')
                    ->update(['school_id' => $schoolId]);
            }

            $updated++;
        }

        if (!empty($unmatched)) {
            Log::warning('PSLE school_id backfill unmatched school codes', [
                'school_codes' => array_keys($unmatched),
            ]);
        }

        return [
            'matched' => $rows->count(),
            'updated' => $updated,
            'unmatched_school_codes' => array_keys($unmatched),
        ];
    }

    private function backfillExamYearIds(int $psleTypeId, ExamYear $examYear, bool $dryRun): array
    {
        $query = CandidateExamRegistration::query()
            ->where('exam_type_id', $psleTypeId)
            ->whereNull('exam_year_id')
            ->where(function ($q) use ($examYear) {
                $q->where('year', (int) $examYear->year_label)
                    ->orWhereNull('year');
            });

        $matched = (clone $query)->count();

        if (!$dryRun && $matched > 0) {
            $query->update([
                'exam_year_id' => $examYear->id,
                'year' => (int) $examYear->year_label,
                'updated_at' => now(),
            ]);
        }

        return [
            'matched' => $matched,
            'updated' => $matched,
            'exam_year_id' => $examYear->id,
            'exam_year' => $examYear->year_label,
        ];
    }
}
