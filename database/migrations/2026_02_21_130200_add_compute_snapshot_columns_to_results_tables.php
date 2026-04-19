<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['subject_marks', 'final_grades', 'candidate_results'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $needsProcessId = !Schema::hasColumn($tableName, 'process_id');
            $needsSnapshotId = !Schema::hasColumn($tableName, 'snapshot_id');
            if (!$needsProcessId && !$needsSnapshotId) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($needsProcessId, $needsSnapshotId) {
                if ($needsProcessId) {
                    $table->unsignedBigInteger('process_id')->nullable()->index();
                }
                if ($needsSnapshotId) {
                    $table->unsignedBigInteger('snapshot_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('result_processes')) {
            $needsConfigFingerprint = !Schema::hasColumn('result_processes', 'config_fingerprint');
            $needsInputFingerprint = !Schema::hasColumn('result_processes', 'input_fingerprint');
            $needsScopeType = !Schema::hasColumn('result_processes', 'scope_type');
            $needsScopeId = !Schema::hasColumn('result_processes', 'scope_id');
            $needsStats = !Schema::hasColumn('result_processes', 'stats');
            $needsErrorMessage = !Schema::hasColumn('result_processes', 'error_message');
            $needsStartedAt = !Schema::hasColumn('result_processes', 'started_at');
            $needsFinishedAt = !Schema::hasColumn('result_processes', 'finished_at');

            if ($needsConfigFingerprint || $needsInputFingerprint || $needsScopeType || $needsScopeId || $needsStats || $needsErrorMessage || $needsStartedAt || $needsFinishedAt) {
                Schema::table('result_processes', function (Blueprint $table) use ($needsConfigFingerprint, $needsInputFingerprint, $needsScopeType, $needsScopeId, $needsStats, $needsErrorMessage, $needsStartedAt, $needsFinishedAt) {
                    if ($needsConfigFingerprint) {
                        $table->string('config_fingerprint', 64)->nullable()->after('status');
                    }
                    if ($needsInputFingerprint) {
                        $table->string('input_fingerprint', 64)->nullable()->after('config_fingerprint');
                    }
                    if ($needsScopeType) {
                        $table->string('scope_type', 20)->nullable()->after('input_fingerprint');
                    }
                    if ($needsScopeId) {
                        $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
                    }
                    if ($needsStats) {
                        $table->json('stats')->nullable()->after('metadata');
                    }
                    if ($needsErrorMessage) {
                        $table->text('error_message')->nullable()->after('error_count');
                    }
                    if ($needsStartedAt) {
                        $table->timestamp('started_at')->nullable()->after('processed_at');
                    }
                    if ($needsFinishedAt) {
                        $table->timestamp('finished_at')->nullable()->after('completed_at');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Keep rollback non-destructive for production safety.
    }
};

