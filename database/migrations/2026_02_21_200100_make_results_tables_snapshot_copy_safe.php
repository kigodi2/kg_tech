<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidate_results')) {
            Schema::table('candidate_results', function (Blueprint $table) {
                try {
                    $table->dropUnique('candidate_results_candidate_id_exam_type_id_year_unique');
                } catch (\Throwable $e) {
                    // Keep migration safe across environments.
                }

                try {
                    $table->unique(['candidate_id', 'exam_type_id', 'year', 'snapshot_id'], 'candidate_results_snapshot_unique');
                } catch (\Throwable $e) {
                    // Unique may already exist.
                }

                try {
                    $table->index(['exam_type_id', 'year', 'process_id', 'snapshot_id'], 'candidate_results_compute_scope_idx');
                } catch (\Throwable $e) {
                    // Index may already exist.
                }
            });
        }

        if (Schema::hasTable('final_grades')) {
            Schema::table('final_grades', function (Blueprint $table) {
                try {
                    $table->dropUnique('final_grades_candidate_id_exam_type_id_year_unique');
                } catch (\Throwable $e) {
                    // Keep migration safe across environments.
                }

                try {
                    $table->unique(['candidate_id', 'exam_type_id', 'year', 'snapshot_id'], 'final_grades_snapshot_unique');
                } catch (\Throwable $e) {
                    // Unique may already exist.
                }

                try {
                    $table->index(['exam_type_id', 'year', 'process_id', 'snapshot_id'], 'final_grades_compute_scope_idx');
                } catch (\Throwable $e) {
                    // Index may already exist.
                }
            });
        }

        if (Schema::hasTable('subject_marks')) {
            Schema::table('subject_marks', function (Blueprint $table) {
                try {
                    $table->dropUnique('subject_marks_candidate_id_exam_type_id_subject_id_year_unique');
                } catch (\Throwable $e) {
                    // Keep migration safe across environments.
                }

                try {
                    $table->unique(['candidate_id', 'exam_type_id', 'subject_id', 'year', 'snapshot_id'], 'subject_marks_snapshot_unique');
                } catch (\Throwable $e) {
                    // Unique may already exist.
                }

                try {
                    $table->index(['exam_type_id', 'year', 'process_id', 'snapshot_id'], 'subject_marks_compute_scope_idx');
                } catch (\Throwable $e) {
                    // Index may already exist.
                }
            });
        }
    }

    public function down(): void
    {
        // Non-destructive rollback policy for production safety.
    }
};
