<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditPsleRawMarkDuplicates extends Command
{
    protected $signature = 'psle:raw-marks:audit-duplicates
        {--export= : Optional CSV path for the duplicate report}';

    protected $description = 'Report duplicate PSLE raw mark rows without deleting or changing data.';

    public function handle(): int
    {
        $duplicates = DB::table('raw_marks')
            ->select('exam_year_id', 'school_id', 'subject_id', 'candidate_id')
            ->selectRaw('COUNT(*) as row_count')
            ->whereNotNull('exam_year_id')
            ->whereNotNull('school_id')
            ->whereNotNull('subject_id')
            ->whereNotNull('candidate_id')
            ->groupBy('exam_year_id', 'school_id', 'subject_id', 'candidate_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('row_count')
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate PSLE raw mark rows found.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($duplicates as $duplicate) {
            $marks = DB::table('raw_marks')
                ->where('exam_year_id', $duplicate->exam_year_id)
                ->where('school_id', $duplicate->school_id)
                ->where('subject_id', $duplicate->subject_id)
                ->where('candidate_id', $duplicate->candidate_id)
                ->orderByDesc('updated_at')
                ->get(['id', 'mark_import_batch_id', 'paper_1_marks', 'updated_at', 'created_at']);

            foreach ($marks as $index => $mark) {
                $rows[] = [
                    'exam_year_id' => $duplicate->exam_year_id,
                    'school_id' => $duplicate->school_id,
                    'subject_id' => $duplicate->subject_id,
                    'candidate_id' => $duplicate->candidate_id,
                    'row_count' => $duplicate->row_count,
                    'raw_mark_id' => $mark->id,
                    'batch_id' => $mark->mark_import_batch_id,
                    'paper_1_marks' => $mark->paper_1_marks,
                    'updated_at' => $mark->updated_at,
                    'recommended_action' => $index === 0 ? 'keep_latest_updated' : 'review_before_cleanup',
                ];
            }
        }

        $this->warn('Duplicate PSLE raw mark groups found: ' . $duplicates->count());
        $this->table(
            ['Year', 'School', 'Subject', 'Candidate', 'Rows', 'Raw Mark', 'Action'],
            array_map(fn ($row) => [
                $row['exam_year_id'],
                $row['school_id'],
                $row['subject_id'],
                $row['candidate_id'],
                $row['row_count'],
                $row['raw_mark_id'],
                $row['recommended_action'],
            ], array_slice($rows, 0, 50))
        );

        if ($export = $this->option('export')) {
            $this->exportCsv($export, $rows);
            $this->info('Duplicate report exported to: ' . $export);
        }

        $this->line('No rows were deleted. Review the report before any cleanup.');

        return self::FAILURE;
    }

    private function exportCsv(string $path, array $rows): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
    }
}
