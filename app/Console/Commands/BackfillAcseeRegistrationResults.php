<?php

namespace App\Console\Commands;

use App\Models\ExamType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAcseeRegistrationResults extends Command
{
    protected $signature = 'acsee:backfill-registration-results
        {--exam-year= : Optional numeric year label (e.g. 2026)}
        {--chunk=1000 : Chunk size for processing}
        {--dry-run : Preview updates without writing}';

    protected $description = 'Backfill candidate_exam_registrations result fields from latest candidate_results rows.';

    public function handle(): int
    {
        $acsee = ExamType::query()->where('code', 'ACSEE')->first();
        if (!$acsee) {
            $this->error('ACSEE exam type not found.');
            return self::FAILURE;
        }

        $yearFilter = $this->option('exam-year');
        $chunkSize = max(100, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $hasSnapshotId = Schema::hasColumn('candidate_results', 'snapshot_id');
        $hasPublishedAt = Schema::hasColumn('candidate_results', 'published_at');
        $hasGpa = Schema::hasColumn('candidate_results', 'gpa');
        $hasTotalMarks = Schema::hasColumn('candidate_results', 'total_marks');
        $hasGradePoints = Schema::hasColumn('candidate_results', 'grade_points');
        $hasOverallGrade = Schema::hasColumn('candidate_results', 'overall_grade');
        $hasDivision = Schema::hasColumn('candidate_results', 'division');
        $hasIsPublished = Schema::hasColumn('candidate_results', 'is_published');

        $latestIdsQuery = DB::table('candidate_results as cr')
            ->where('cr.exam_type_id', $acsee->id)
            ->when($hasSnapshotId, fn ($q) => $q->whereNull('cr.snapshot_id'))
            ->when($yearFilter, fn ($q) => $q->where('cr.year', (int) $yearFilter))
            ->groupBy('cr.candidate_id', 'cr.exam_type_id', 'cr.year')
            ->selectRaw('MAX(cr.id) as latest_id');

        $latestRowsQuery = DB::table('candidate_results as cr')
            ->joinSub($latestIdsQuery, 'latest', function ($join) {
                $join->on('cr.id', '=', 'latest.latest_id');
            })
            ->orderBy('cr.id');

        $totalRows = (clone $latestRowsQuery)->count();
        if ($totalRows === 0) {
            $this->warn('No candidate_results rows found for selected filter.');
            return self::SUCCESS;
        }

        $regHasGrade = Schema::hasColumn('candidate_exam_registrations', 'grade');
        $regHasGpa = Schema::hasColumn('candidate_exam_registrations', 'gpa');
        $regHasDivision = Schema::hasColumn('candidate_exam_registrations', 'division');
        $regHasTotalMarks = Schema::hasColumn('candidate_exam_registrations', 'total_marks');
        $regHasTotalPoints = Schema::hasColumn('candidate_exam_registrations', 'total_points');
        $regHasResultStatus = Schema::hasColumn('candidate_exam_registrations', 'result_status');
        $regHasPublishedAt = Schema::hasColumn('candidate_exam_registrations', 'published_at');
        $regHasUpdatedAt = Schema::hasColumn('candidate_exam_registrations', 'updated_at');

        $this->info("Processing {$totalRows} latest candidate_results rows (chunk={$chunkSize})...");

        $matched = 0;
        $updated = 0;
        $processed = 0;

        $latestRowsQuery->chunkById($chunkSize, function ($rows) use (
            &$processed,
            &$matched,
            &$updated,
            $dryRun,
            $regHasGrade,
            $regHasGpa,
            $regHasDivision,
            $regHasTotalMarks,
            $regHasTotalPoints,
            $regHasResultStatus,
            $regHasPublishedAt,
            $regHasUpdatedAt,
            $hasOverallGrade,
            $hasGpa,
            $hasDivision,
            $hasTotalMarks,
            $hasGradePoints,
            $hasIsPublished,
            $hasPublishedAt
        ) {
            foreach ($rows as $row) {
                $processed++;

                $query = DB::table('candidate_exam_registrations')
                    ->where('candidate_id', (int) $row->candidate_id)
                    ->where('exam_type_id', (int) $row->exam_type_id)
                    ->where('year', (int) $row->year);

                if (!(clone $query)->exists()) {
                    continue;
                }
                $matched++;

                $updates = [];
                if ($regHasGrade && $hasOverallGrade) {
                    $updates['grade'] = $row->overall_grade !== null ? substr((string) $row->overall_grade, 0, 3) : null;
                }
                if ($regHasGpa && $hasGpa) {
                    $updates['gpa'] = isset($row->gpa) ? round((float) $row->gpa, 2) : null;
                }
                if ($regHasDivision && $hasDivision) {
                    $updates['division'] = is_numeric($row->division) ? (int) $row->division : null;
                }
                if ($regHasTotalMarks && $hasTotalMarks) {
                    $updates['total_marks'] = isset($row->total_marks) ? (float) $row->total_marks : null;
                }
                if ($regHasTotalPoints && $hasGradePoints) {
                    $updates['total_points'] = is_numeric($row->grade_points) ? (int) $row->grade_points : null;
                }
                if ($regHasResultStatus && $hasIsPublished) {
                    $updates['result_status'] = ((int) ($row->is_published ?? 0) === 1) ? 'published' : 'draft';
                }
                if ($regHasPublishedAt && $hasPublishedAt) {
                    $updates['published_at'] = $row->published_at ?? null;
                }
                if ($regHasUpdatedAt) {
                    $updates['updated_at'] = now();
                }

                if (empty($updates)) {
                    continue;
                }

                if ($dryRun) {
                    $updated++;
                    continue;
                }

                $affected = $query->update($updates);
                if ($affected > 0) {
                    $updated += $affected;
                }
            }
        }, 'cr.id', 'id');

        $this->newLine();
        $this->info('Backfill complete.');
        $this->line("Processed latest results: {$processed}");
        $this->line("Matched registrations: {$matched}");
        $this->line(($dryRun ? 'Would update: ' : 'Updated: ') . $updated);

        return self::SUCCESS;
    }
}

