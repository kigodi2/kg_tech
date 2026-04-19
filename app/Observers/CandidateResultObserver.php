<?php

namespace App\Observers;

use App\Models\CandidateResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CandidateResultObserver
{
    public function saved(CandidateResult $result): void
    {
        $this->syncToRegistration($result);
    }

    public function deleted(CandidateResult $result): void
    {
        if (!Schema::hasTable('candidate_exam_registrations')) {
            return;
        }

        $query = DB::table('candidate_exam_registrations')
            ->where('candidate_id', (int) $result->candidate_id)
            ->where('exam_type_id', (int) $result->exam_type_id)
            ->where('year', (int) $result->year);

        $updates = [];
        if (Schema::hasColumn('candidate_exam_registrations', 'grade')) {
            $updates['grade'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'gpa')) {
            $updates['gpa'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'division')) {
            $updates['division'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'total_marks')) {
            $updates['total_marks'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'total_points')) {
            $updates['total_points'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'result_status')) {
            $updates['result_status'] = 'draft';
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'published_at')) {
            $updates['published_at'] = null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            $query->update($updates);
        }
    }

    private function syncToRegistration(CandidateResult $result): void
    {
        if (!Schema::hasTable('candidate_exam_registrations')) {
            return;
        }

        // Snapshot rows are immutable historical copies and should not back-write registration row.
        if (Schema::hasColumn('candidate_results', 'snapshot_id') && !is_null($result->snapshot_id)) {
            return;
        }

        $query = DB::table('candidate_exam_registrations')
            ->where('candidate_id', (int) $result->candidate_id)
            ->where('exam_type_id', (int) $result->exam_type_id)
            ->where('year', (int) $result->year);

        $updates = [];
        if (Schema::hasColumn('candidate_exam_registrations', 'grade')) {
            $updates['grade'] = $result->overall_grade !== null
                ? substr((string) $result->overall_grade, 0, 3)
                : null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'gpa') && isset($result->gpa)) {
            $updates['gpa'] = round((float) $result->gpa, 2);
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'division') && isset($result->division)) {
            $updates['division'] = is_numeric($result->division) ? (int) $result->division : null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'total_marks') && isset($result->total_marks)) {
            $updates['total_marks'] = (float) $result->total_marks;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'total_points') && isset($result->grade_points)) {
            $updates['total_points'] = is_numeric($result->grade_points) ? (int) $result->grade_points : null;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'result_status')) {
            $updates['result_status'] = $result->is_published ? 'published' : 'draft';
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'published_at')) {
            $updates['published_at'] = $result->published_at;
        }
        if (Schema::hasColumn('candidate_exam_registrations', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        if (!empty($updates)) {
            $query->update($updates);
        }
    }
}

