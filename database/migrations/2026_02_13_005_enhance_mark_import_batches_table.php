<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->enum('lifecycle_state', [
                'draft', 'validating', 'validated', 'validation_failed',
                'awaiting_moderation', 'approved', 'rejected', 'processing',
                'processed', 'submitted', 'archived'
            ])->default('draft')->after('status');
            $table->json('lifecycle_history')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->boolean('requires_resubmission')->default(false);
            $table->foreignId('resubmitted_from_batch_id')->nullable()
                ->constrained('mark_import_batches')->nullOnDelete();
            $table->foreignId('latest_review_id')->nullable()
                ->constrained('mark_moderation_reviews')->nullOnDelete();
            $table->string('batch_hash')->nullable()->unique();
        });
    }

    public function down(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_state', 'lifecycle_history', 'rejection_reason',
                'requires_resubmission', 'resubmitted_from_batch_id',
                'latest_review_id', 'batch_hash'
            ]);
        });
    }
};
