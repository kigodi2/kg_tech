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
        Schema::create('mark_entry_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_mark_id')->nullable()->constrained('raw_marks')->onDelete('cascade');
            $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->onDelete('cascade');
            
            $table->string('error_type'); // e.g., Missing Mark, Invalid Mark, Mark Above Maximum, etc.
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->text('message');
            $table->string('status')->default('open'); // open, resolved, ignored, returned, corrected
            
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_comment')->nullable();
            
            $table->timestamps();

            // Indexes for performance
            $table->index(['exam_year_id', 'status']);
            $table->index(['region_id', 'status']);
            $table->index(['error_type', 'severity']);
            $table->index(['raw_mark_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_entry_validations');
    }
};
