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
        Schema::create('mark_entry_outliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->onDelete('cascade');
            $table->foreignId('raw_mark_id')->nullable()->constrained('raw_marks')->onDelete('cascade');
            $table->foreignId('assignment_id')->nullable()->constrained('mark_entry_assignments')->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('mark_import_batches')->onDelete('cascade');
            $table->foreignId('officer_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('outlier_type'); // e.g., Extreme High, Extreme Low, Pattern Anomaly, Fast Entry
            $table->string('severity')->default('low'); // low, medium, high, critical
            $table->decimal('observed_value', 10, 2)->nullable();
            $table->string('expected_range')->nullable();
            $table->text('message');
            $table->string('status')->default('pending'); // pending, verified, resolved, escalated, corrected
            
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_comment')->nullable();
            
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['exam_year_id', 'status']);
            $table->index(['region_id', 'status']);
            $table->index(['outlier_type', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_entry_outliers');
    }
};
