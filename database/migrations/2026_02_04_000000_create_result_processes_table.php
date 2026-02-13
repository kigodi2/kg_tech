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
        if (!Schema::hasTable('result_processes')) {
            Schema::create('result_processes', function (Blueprint $table) {
                $table->id();
                
                // Foreign keys
                $table->foreignId('exam_type_id')
                    ->constrained('exam_types')
                    ->onDelete('cascade');
                
                $table->foreignId('exam_year_id')
                    ->constrained('exam_years')
                    ->onDelete('cascade');
                
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                
                // Processing type & status
                $table->enum('type', ['draft', 'final'])->comment('Draft: safe testing, Final: locked');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'rolled_back'])
                    ->default('pending')
                    ->index();
                
                // Processing metrics
                $table->integer('total_candidates')->default(0);
                $table->integer('processed_count')->default(0);
                $table->integer('error_count')->default(0);
                $table->text('error_log')->nullable()->comment('Comma-separated list of candidate errors');
                
                // Timestamps
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                
                // Metadata (for storing processing details, validations, etc.)
                $table->json('metadata')->nullable();
                
                // Timestamps
                $table->timestamps();
                
                // Indexes
                $table->index(['exam_type_id', 'exam_year_id']);
                $table->index('status');
                $table->index('type');
                $table->index('processed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('result_processes');
    }
};
