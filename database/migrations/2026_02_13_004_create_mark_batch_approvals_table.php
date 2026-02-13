<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('mark_batch_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->enum('approval_level', ['validation', 'moderation', 'submission']);
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('signature')->nullable();
            $table->timestamps();
            $table->unique(['mark_import_batch_id', 'approval_level']);
            $table->index('approved_by');
            $table->index('approved_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_batch_approvals');
    }
};
