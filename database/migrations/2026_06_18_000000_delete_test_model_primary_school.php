<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $school = DB::table('schools')->where('code', 'PS000999')->first();
        if (!$school) {
            return;
        }

        $schoolId = $school->id;

        // Find candidate IDs
        $candidateIds = DB::table('candidates')->where('school_id', $schoolId)->pluck('id')->toArray();

        // Delete child records in proper dependency order
        if (!empty($candidateIds)) {
            DB::table('raw_marks')->whereIn('candidate_id', $candidateIds)->delete();
            DB::table('subject_marks')->whereIn('candidate_id', $candidateIds)->delete();
            DB::table('candidate_results')->whereIn('candidate_id', $candidateIds)->delete();
            DB::table('candidate_exam_registrations')->whereIn('candidate_id', $candidateIds)->delete();
            DB::table('candidate_subject_selections')->whereIn('candidate_id', $candidateIds)->delete();
            DB::table('candidates')->whereIn('id', $candidateIds)->delete();
        }

        DB::table('result_snapshots')->where('school_id', $schoolId)->delete();
        DB::table('result_processes')->where('school_id', $schoolId)->delete();
        
        // Delete the school itself
        DB::table('schools')->where('id', $schoolId)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback possible/needed for test data deletion
    }
};
