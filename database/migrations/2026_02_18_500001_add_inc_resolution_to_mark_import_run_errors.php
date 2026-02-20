<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mark_import_run_errors', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_import_run_errors', 'is_actionable')) {
                $table->boolean('is_actionable')->default(false)->after('severity');
                $table->index(['is_actionable']);
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'is_resolved')) {
                $table->boolean('is_resolved')->default(false)->after('is_actionable');
                $table->index(['is_resolved']);
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('is_resolved');
                $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'resolution_action')) {
                $table->string('resolution_action', 30)->nullable()->after('resolved_at'); // ACCEPT_INC, REJECT
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'resolution_note')) {
                $table->text('resolution_note')->nullable()->after('resolution_action');
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'resolution_correlation_id')) {
                $table->string('resolution_correlation_id', 36)->nullable()->after('resolution_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_import_run_errors', function (Blueprint $table) {
            if (Schema::hasColumn('mark_import_run_errors', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
            }

            $cols = [
                'is_actionable',
                'is_resolved',
                'resolved_by',
                'resolved_at',
                'resolution_action',
                'resolution_note',
                'resolution_correlation_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('mark_import_run_errors', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
