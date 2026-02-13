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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            
            // User who triggered the operation
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            
            // Operation type: backup_created, backup_failed, restore_completed, etc.
            $table->string('operation');
            
            // Operation data (JSON) - stores backup_id, sizes, hashes, errors, etc.
            $table->json('data')->nullable();
            
            // Operation status: success or failed
            $table->enum('status', ['success', 'failed'])->default('success');
            
            // Immutable - only created, never updated
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['operation', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
