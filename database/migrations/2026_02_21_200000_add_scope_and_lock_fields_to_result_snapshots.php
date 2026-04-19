<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_snapshots')) {
            return;
        }

        Schema::table('result_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('result_snapshots', 'scope_type')) {
                $table->string('scope_type', 20)->nullable()->after('process_id');
            }
            if (!Schema::hasColumn('result_snapshots', 'scope_id')) {
                $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
            }

            if (!Schema::hasColumn('result_snapshots', 'locked_by')) {
                $table->unsignedBigInteger('locked_by')->nullable()->after('publish_notes');
            }
            if (!Schema::hasColumn('result_snapshots', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('result_snapshots', 'lock_reason')) {
                $table->text('lock_reason')->nullable()->after('locked_at');
            }
        });

        Schema::table('result_snapshots', function (Blueprint $table) {
            try {
                $table->dropUnique('result_snapshots_exam_type_exam_year_id_version_unique');
            } catch (\Throwable $e) {
                // Keep migration safe across environments.
            }

            try {
                $table->dropIndex('result_snapshots_exam_type_exam_year_id_is_active_index');
            } catch (\Throwable $e) {
                // Keep migration safe across environments.
            }

            try {
                $table->index(['exam_type', 'exam_year_id', 'scope_type', 'scope_id', 'is_active'], 'result_snapshots_active_scope_idx');
            } catch (\Throwable $e) {
                // Index may already exist.
            }

            try {
                $table->unique(['exam_type', 'exam_year_id', 'scope_type', 'scope_id', 'version'], 'result_snapshots_scope_version_unique');
            } catch (\Throwable $e) {
                // Unique may already exist.
            }
        });
    }

    public function down(): void
    {
        // Non-destructive rollback policy for production safety.
    }
};
