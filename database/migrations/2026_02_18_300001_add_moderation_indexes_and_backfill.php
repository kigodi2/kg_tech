<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            // mark_import_batches
            ['mark_import_batches', 'mark_import_batches_lifecycle_state_index', 'lifecycle_state'],
            ['mark_import_batches', 'mark_import_batches_exam_year_index', 'exam_year'],
            ['mark_import_batches', 'mib_lifecycle_exam_year_idx', 'lifecycle_state, exam_year'],
            ['mark_import_batches', 'mib_status_exam_year_idx', 'status, exam_year'],
            // mark_import_runs
            ['mark_import_runs', 'mir_batch_id_idx', 'mark_import_batch_id'],
            ['mark_import_runs', 'mir_exam_year_id_idx', 'exam_year_id'],
            // mark_moderation_actions
            ['mark_moderation_actions', 'mma_exam_year_id_idx', 'exam_year_id'],
            ['mark_moderation_actions', 'mma_action_created_idx', 'action, created_at'],
        ];

        foreach ($indexes as [$table, $name, $columns]) {
            try {
                DB::statement("CREATE INDEX IF NOT EXISTS \"{$name}\" ON \"{$table}\" ({$columns})");
            } catch (\Exception $e) {
                // Index already exists — skip silently
            }
        }
    }

    public function down(): void
    {
        $indexNames = [
            'mark_import_batches_lifecycle_state_index',
            'mark_import_batches_exam_year_index',
            'mib_lifecycle_exam_year_idx',
            'mib_status_exam_year_idx',
            'mir_batch_id_idx',
            'mir_exam_year_id_idx',
            'mma_exam_year_id_idx',
            'mma_action_created_idx',
        ];

        foreach ($indexNames as $name) {
            try {
                DB::statement("DROP INDEX IF EXISTS \"{$name}\"");
            } catch (\Exception $e) {
                // Already gone — skip
            }
        }
    }
};
