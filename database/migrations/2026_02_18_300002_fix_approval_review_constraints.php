<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive migration: 
 * 1) Add missing columns (approval_type, status, approval_notes) to mark_batch_approvals
 * 2) Expand review_type CHECK on mark_moderation_reviews to include 'approval' and 'rejection'
 *
 * Safe: no data deleted, all existing rows preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Add missing columns to mark_batch_approvals
        Schema::table('mark_batch_approvals', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_batch_approvals', 'approval_type')) {
                $table->string('approval_type')->nullable()->after('approval_level');
            }
            if (!Schema::hasColumn('mark_batch_approvals', 'status')) {
                $table->string('status')->nullable()->after('approval_type');
            }
            if (!Schema::hasColumn('mark_batch_approvals', 'approval_notes')) {
                $table->text('approval_notes')->nullable()->after('approved_at');
            }
        });

        // 2) Expand review_type CHECK on mark_moderation_reviews
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildModerationReviewsForSqlite();
        } else {
            DB::statement("ALTER TABLE mark_moderation_reviews MODIFY review_type ENUM(
                'school_hod', 'district_supervisor', 'admin', 'approval', 'rejection'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('mark_batch_approvals', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('mark_batch_approvals', 'approval_type')) $cols[] = 'approval_type';
            if (Schema::hasColumn('mark_batch_approvals', 'status')) $cols[] = 'status';
            if (Schema::hasColumn('mark_batch_approvals', 'approval_notes')) $cols[] = 'approval_notes';
            if ($cols) $table->dropColumn($cols);
        });
    }

    private function rebuildModerationReviewsForSqlite(): void
    {
        $oldSql = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='mark_moderation_reviews'")->sql ?? null;
        if (!$oldSql) return;

        $oldCheck = "check (\"review_type\" in ('school_hod', 'district_supervisor', 'admin'))";
        $newCheck = "check (\"review_type\" in ('school_hod', 'district_supervisor', 'admin', 'approval', 'rejection'))";

        if (!str_contains($oldSql, $oldCheck)) return;

        DB::statement('PRAGMA foreign_keys = OFF');

        $newCreateSql = str_replace($oldCheck, $newCheck, $oldSql);
        $newCreateSql = str_replace('"mark_moderation_reviews"', '"_mmr_new"', $newCreateSql);

        $columns = Schema::getColumnListing('mark_moderation_reviews');
        $colList = '"' . implode('","', $columns) . '"';

        DB::statement($newCreateSql);
        DB::statement("INSERT INTO \"_mmr_new\" ({$colList}) SELECT {$colList} FROM \"mark_moderation_reviews\"");
        DB::statement('DROP TABLE "mark_moderation_reviews"');
        DB::statement('ALTER TABLE "_mmr_new" RENAME TO "mark_moderation_reviews"');

        // Recreate indexes
        DB::statement('CREATE INDEX "mmr_batch_id_index" ON "mark_moderation_reviews" ("mark_import_batch_id")');
        DB::statement('CREATE INDEX "mmr_reviewer_id_index" ON "mark_moderation_reviews" ("reviewer_id")');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
