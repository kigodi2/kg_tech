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
        Schema::create('raw_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')->constrained('mark_import_batches')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
            
            // Row reference
            $table->unsignedInteger('row_number')->comment('CSV row number');
            
            // Raw candidate data (as uploaded)
            $table->string('candidate_index_number')->comment('Candidate index number from CSV');
            $table->string('full_name')->nullable()->comment('Candidate name from CSV');
            
            // Mark columns (dynamically filled based on paper structure)
            $table->decimal('paper_1_marks', 6, 2)->nullable();
            $table->decimal('paper_2_marks', 6, 2)->nullable();
            $table->decimal('paper_3_marks', 6, 2)->nullable();
            $table->decimal('practical_marks', 6, 2)->nullable();
            $table->decimal('project_marks', 6, 2)->nullable();
            
            // Validation
            $table->boolean('has_errors')->default(false);
            $table->json('error_messages')->nullable()->comment('Array of validation error messages');
            
            // Preserve raw row data
            $table->json('raw_data')->comment('Original CSV row data');
            
            // Processing
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('mark_import_batch_id');
            $table->index('candidate_id');
            $table->index('has_errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_marks');
    }
};
