<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds a unique constraint on (exam_year_id, exam_type_id, candidate_id)
     * to enforce duplicate protection per exam context.
     * 
     * SAFETY NOTES:
     * 1. This migration first checks for existing duplicates
     * 2. If duplicates exist, it logs them and aborts with clear instructions
     * 3. It does NOT auto-delete duplicates (manual review required)
     * 4. The unique constraint is added to candidate_exam_registrations join table
     * 5. The candidate_id (index_number) itself remains unique at table level for backwards compat
     */
    public function up(): void
    {
        // Step 1: Detect duplicates before creating unique constraint
        $duplicates = $this->findDuplicates();

        if (!empty($duplicates)) {
            // Log duplicates for admin review
            \Illuminate\Support\Facades\Log::error(
                'Index number duplicates detected. Unique constraint migration aborted.',
                [
                    'duplicate_count' => count($duplicates),
                    'duplicates' => $duplicates,
                ]
            );

            throw new \Exception(
                "Cannot add unique constraint: " . count($duplicates) . " duplicate index numbers found.\n" .
                "Run: php artisan necta:scan-duplicate-index --output=json --export=/tmp/duplicates.json\n" .
                "Review and resolve duplicates manually, then re-run this migration.\n" .
                "Duplicates have been logged to application logs."
            );
        }

        // Step 2: Add unique index on the join table
        // This enforces: only one registration per candidate per exam context
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            // Create unique index: (candidate_id, exam_year_id, exam_type_id)
            $table->unique(
                ['candidate_id', 'exam_year_id', 'exam_type_id'],
                'unique_candidate_exam_context'
            );
        });

        // Step 3: Create a check constraint to ensure we're looking at the candidate's index number
        // This is informational - documents the intent
        \Illuminate\Support\Facades\Log::info(
            'Unique constraint added to candidate_exam_registrations table',
            [
                'constraint' => 'unique_candidate_exam_context',
                'columns' => ['candidate_id', 'exam_year_id', 'exam_type_id'],
                'purpose' => 'Enforce one candidate registration per exam context',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            $table->dropUnique('unique_candidate_exam_context');
        });
    }

    /**
     * Find duplicate index numbers in the same exam context
     * 
     * Returns array of duplicate groups:
     * [
     *     [
     *         'candidate_id' => 'S0445-0001',
     *         'exam_year_id' => 1,
     *         'exam_type_id' => 2,
     *         'count' => 3,
     *         'candidates' => [...]
     *     ]
     * ]
     */
    private function findDuplicates(): array
    {
        $duplicates = DB::table('candidate_exam_registrations')
            ->select(
                'candidate_exam_registrations.candidate_id',
                'candidate_exam_registrations.exam_year_id',
                'candidate_exam_registrations.exam_type_id',
                DB::raw('COUNT(*) as duplicate_count')
            )
            ->groupBy('candidate_id', 'exam_year_id', 'exam_type_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get()
            ->toArray();

        // Enrich with candidate details
        $result = [];
        foreach ($duplicates as $dup) {
            $candidates = DB::table('candidates')
                ->join('candidate_exam_registrations', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
                ->where('candidates.id', $dup->candidate_id)
                ->where('candidate_exam_registrations.exam_year_id', $dup->exam_year_id)
                ->where('candidate_exam_registrations.exam_type_id', $dup->exam_type_id)
                ->select('candidates.id', 'candidates.full_name', 'candidates.school_id')
                ->get()
                ->toArray();

            $result[] = [
                'candidate_id' => $dup->candidate_id,
                'exam_year_id' => $dup->exam_year_id,
                'exam_type_id' => $dup->exam_type_id,
                'count' => $dup->duplicate_count,
                'candidate_records' => $candidates,
            ];
        }

        return $result;
    }
};
