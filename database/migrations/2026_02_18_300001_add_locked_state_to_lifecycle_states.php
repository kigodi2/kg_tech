<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration: expand CHECK constraints on current_state / lifecycle_state
 * to include 'locked' and 'unlocked' states required by the state machine.
 *
 * Safe: no data is deleted or modified. Tables are rebuilt with all existing rows preserved.
 */
return new class extends Migration
{
    private const ALLOWED_STATES = "'draft','validating','validated','validation_failed','awaiting_moderation','approved','rejected','processing','processed','submitted','archived','locked','unlocked'";

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildForSqlite();
        } else {
            $states = self::ALLOWED_STATES;
            DB::statement("ALTER TABLE mark_entry_lifecycle_states MODIFY current_state ENUM({$states}) NOT NULL DEFAULT 'draft'");
            DB::statement("ALTER TABLE mark_import_batches MODIFY lifecycle_state ENUM({$states}) NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        // No-op: removing states could break existing data
    }

    private function rebuildForSqlite(): void
    {
        $states = self::ALLOWED_STATES;
        DB::statement('PRAGMA foreign_keys = OFF');

        // 1) Rebuild mark_entry_lifecycle_states
        DB::statement('ALTER TABLE "mark_entry_lifecycle_states" RENAME TO "_mels_old"');
        DB::statement("
            CREATE TABLE \"mark_entry_lifecycle_states\" (
                \"id\" integer primary key autoincrement not null,
                \"mark_import_batch_id\" integer not null,
                \"current_state\" varchar check (\"current_state\" in ({$states})) not null default 'draft',
                \"previous_state\" varchar,
                \"transitioned_by\" integer,
                \"transitioned_at\" datetime,
                \"transition_reason\" text,
                \"history\" text,
                \"created_at\" datetime,
                \"updated_at\" datetime,
                foreign key(\"mark_import_batch_id\") references \"mark_import_batches\"(\"id\") on delete cascade,
                foreign key(\"transitioned_by\") references \"users\"(\"id\") on delete set null
            )
        ");
        DB::statement('INSERT INTO "mark_entry_lifecycle_states" SELECT * FROM "_mels_old"');
        DB::statement('DROP TABLE "_mels_old"');
        DB::statement('CREATE INDEX "mark_entry_lifecycle_states_mark_import_batch_id_index" ON "mark_entry_lifecycle_states" ("mark_import_batch_id")');
        DB::statement('CREATE INDEX "mark_entry_lifecycle_states_current_state_index" ON "mark_entry_lifecycle_states" ("current_state")');
        DB::statement('CREATE INDEX "mark_entry_lifecycle_states_transitioned_at_index" ON "mark_entry_lifecycle_states" ("transitioned_at")');

        // 2) Rebuild mark_import_batches — remove CHECK on lifecycle_state
        //    Get the current CREATE TABLE statement
        $createSql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='mark_import_batches'")->sql;

        // Replace the old CHECK constraint with the expanded one
        $oldCheck = "check (\"lifecycle_state\" in ('draft', 'validating', 'validated', 'validation_failed', 'awaiting_moderation', 'approved', 'rejected', 'processing', 'processed', 'submitted', 'archived'))";
        $newCheck = "check (\"lifecycle_state\" in ({$states}))";

        if (str_contains($createSql, $oldCheck)) {
            $newCreateSql = str_replace($oldCheck, $newCheck, $createSql);
            $newCreateSql = str_replace('"mark_import_batches"', '"mark_import_batches_new"', $newCreateSql);

            // Get column list for INSERT
            $columns = Schema::getColumnListing('mark_import_batches');
            $colList = '"' . implode('","', $columns) . '"';

            DB::statement($newCreateSql);
            DB::statement("INSERT INTO \"mark_import_batches_new\" ({$colList}) SELECT {$colList} FROM \"mark_import_batches\"");
            DB::statement('DROP TABLE "mark_import_batches"');
            DB::statement('ALTER TABLE "mark_import_batches_new" RENAME TO "mark_import_batches"');

            // Recreate indexes
            DB::statement('CREATE UNIQUE INDEX "mark_import_batches_batch_code_unique" ON "mark_import_batches" ("batch_code")');
            DB::statement('CREATE INDEX "mark_import_batches_batch_code_index" ON "mark_import_batches" ("batch_code")');
            DB::statement('CREATE INDEX "mark_import_batches_school_id_exam_year_index" ON "mark_import_batches" ("school_id", "exam_year")');
            DB::statement('CREATE INDEX "mark_import_batches_subject_id_combination_id_index" ON "mark_import_batches" ("subject_id", "combination_id")');
            DB::statement('CREATE INDEX "mark_import_batches_status_index" ON "mark_import_batches" ("status")');
        }

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
