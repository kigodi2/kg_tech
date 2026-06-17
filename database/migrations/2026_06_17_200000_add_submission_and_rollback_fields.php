<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add fields to result_snapshots
        Schema::table('result_snapshots', function (Blueprint $table) {
            if (!Schema::hasColumn('result_snapshots', 'is_rolled_back')) {
                $table->boolean('is_rolled_back')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('result_snapshots', 'rolled_back_at')) {
                $table->timestamp('rolled_back_at')->nullable()->after('is_rolled_back');
            }
            if (!Schema::hasColumn('result_snapshots', 'rolled_back_by')) {
                $table->foreignId('rolled_back_by')->nullable()->after('rolled_back_at')->constrained('users')->nullOnDelete();
            }
        });

        // Add fields to result_processes
        Schema::table('result_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('result_processes', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('result_processes', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('result_processes', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('result_processes', 'locked_by')) {
                $table->foreignId('locked_by')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('result_processes', 'processing_ready_at')) {
                $table->timestamp('processing_ready_at')->nullable()->after('locked_by');
            }
            if (!Schema::hasColumn('result_processes', 'processing_ready_by')) {
                $table->foreignId('processing_ready_by')->nullable()->after('processing_ready_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('result_processes', 'processing_status')) {
                $table->string('processing_status', 30)->nullable()->after('processing_ready_by');
            }
        });

        // Add fields to raw_marks
        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('is_locked');
            }
            if (!Schema::hasColumn('raw_marks', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('raw_marks', 'processing_ready_at')) {
                $table->timestamp('processing_ready_at')->nullable()->after('submitted_by');
            }
            if (!Schema::hasColumn('raw_marks', 'processing_ready_by')) {
                $table->foreignId('processing_ready_by')->nullable()->after('processing_ready_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            $cols = ['submitted_at', 'submitted_by', 'processing_ready_at', 'processing_ready_by'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('raw_marks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('result_processes', function (Blueprint $table) {
            $cols = ['submitted_at', 'submitted_by', 'locked_at', 'locked_by', 'processing_ready_at', 'processing_ready_by', 'processing_status'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('result_processes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('result_snapshots', function (Blueprint $table) {
            $cols = ['is_rolled_back', 'rolled_back_at', 'rolled_back_by'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('result_snapshots', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
