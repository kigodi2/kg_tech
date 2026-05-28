<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidate_results')) {
            Schema::table('candidate_results', function (Blueprint $table) {
                if (! $this->indexExists('candidate_results', 'candidate_results_candidate_fk_idx')) {
                    $table->index('candidate_id', 'candidate_results_candidate_fk_idx');
                }

                foreach (['candidate_results_candidate_id_exam_type_id_year_unique', 'candidate_results_candidate_exam_year_unique'] as $index) {
                    if ($this->indexExists('candidate_results', $index)) {
                        $table->dropUnique($index);
                    }
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
                if (! $this->indexExists('final_grades', 'final_grades_candidate_fk_idx')) {
                    $table->index('candidate_id', 'final_grades_candidate_fk_idx');
                }

                foreach (['final_grades_candidate_id_exam_type_id_year_unique', 'final_grades_candidate_exam_year_unique'] as $index) {
                    if ($this->indexExists('final_grades', $index)) {
                        $table->dropUnique($index);
                    }
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
                if (! $this->indexExists('subject_marks', 'subject_marks_candidate_fk_idx')) {
                    $table->index('candidate_id', 'subject_marks_candidate_fk_idx');
                }

                foreach (['subject_marks_candidate_id_exam_type_id_subject_id_year_unique', 'subject_marks_candidate_exam_subject_year_unique'] as $index) {
                    if ($this->indexExists('subject_marks', $index)) {
                        $table->dropUnique($index);
                    }
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

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $connection->getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $index)
                ->exists();
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA index_list("' . str_replace('"', '""', $table) . '")') as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }
        }

        return false;
    }
};
