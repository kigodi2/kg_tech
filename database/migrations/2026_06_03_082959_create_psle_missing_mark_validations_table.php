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
        Schema::create('psle_missing_mark_validations', function (Blueprint $table) {
            $table->id();
            
            // Scope references
            $table->foreignId('exam_year_id')->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            // Candidate and Subject references
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            
            // Validation details
            $table->string('classification', 10)->comment('ABS, INC');
            $table->string('decision', 20)->default('pending')->comment('pending, approved_abs, rejected, committed');
            $table->string('reason')->nullable()->comment('Mandatory reason for ABS approval');
            $table->text('remarks')->nullable();
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('committed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            
            $table->timestamps();
            
            // Composite unique constraint for data integrity and idempotence
            $table->unique(
                ['exam_year_id', 'school_id', 'candidate_id', 'subject_id'],
                'psle_missing_mark_val_unique'
            );
            
            // Indexes
            $table->index('exam_year_id');
            $table->index('school_id');
            $table->index('candidate_id');
            $table->index('subject_id');
            $table->index('decision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psle_missing_mark_validations');
    }
};
