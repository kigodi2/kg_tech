<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('mark_entry_lifecycle_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->enum('current_state', [
                'draft', 'validating', 'validated', 'validation_failed',
                'awaiting_moderation', 'approved', 'rejected', 'processing',
                'processed', 'submitted', 'archived'
            ])->default('draft');
            $table->string('previous_state')->nullable();
            $table->foreignId('transitioned_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('transitioned_at')->nullable();
            $table->text('transition_reason')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();
            $table->index('mark_import_batch_id');
            $table->index('current_state');
            $table->index('transitioned_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_entry_lifecycle_states');
    }
};
