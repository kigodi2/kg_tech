<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Candidate-level cross-subject analysis
        Schema::create('candidate_extremity_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
            $table->foreignId('exam_year_id')->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            
            // Candidate profile
            $table->string('combination')->comment('Subject combination (e.g., PCM)');
            $table->integer('subject_count')->comment('Number of subjects');
            
            // Overall statistics
            $table->decimal('average_score', 8, 3)->comment('Average across all subjects');
            $table->decimal('median_score', 8, 3);
            $table->decimal('std_dev_across_subjects', 8, 3)->comment('Std dev of own scores');
            $table->decimal('min_score', 8, 3);
            $table->decimal('max_score', 8, 3);
            
            // Outlier detection
            $table->integer('outlier_subject_count')->default(0)->comment('Number of subject outliers');
            $table->json('outlier_subjects')->nullable()->comment('Array of outlier subject analyses');
            
            // Comparative analysis
            $table->decimal('expected_score', 8, 3)->nullable()->comment('Score based on other subjects');
            $table->json('subject_analysis')->nullable()->comment('Detailed per-subject analysis');
            
            // Flags
            $table->string('risk_level')->default('Low'); // Low, Moderate, High
            $table->json('flags')->nullable();
            
            // Metadata
            $table->text('analysis_notes')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->dateTime('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('review_notes')->nullable();
            
            // Audit
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['candidate_id', 'exam_year_id']);
            $table->index(['exam_year_id', 'exam_type_id']);
            $table->index('risk_level');
            $table->index('reviewed');
        });

        // Subject-level outlier details for candidates
        Schema::create('candidate_subject_outliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_extremity_id')->constrained('candidate_extremity_analysis')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('score', 8, 3);
            $table->decimal('candidate_average', 8, 3)->comment('Candidate\'s average excluding this subject');
            $table->decimal('deviation_from_average', 8, 3);
            $table->decimal('deviation_percentage', 8, 3);
            $table->decimal('zscore', 8, 3)->comment('Z-score relative to candidate\'s performance');
            $table->string('outlier_type')->comment('high, low');
            $table->timestamps();
            
            $table->index(['candidate_extremity_id', 'subject_id'], 'cso_extremity_subject_idx');
        });

        // Analysis log
        Schema::create('candidate_extremity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->integer('candidates_analyzed')->default(0);
            $table->integer('high_risk_count')->default(0);
            $table->integer('moderate_risk_count')->default(0);
            $table->integer('low_risk_count')->default(0);
            $table->integer('total_outliers_detected')->default(0);
            $table->dateTime('analysis_started_at');
            $table->dateTime('analysis_completed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['exam_year_id', 'exam_type_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_extremity_logs');
        Schema::dropIfExists('candidate_subject_outliers');
        Schema::dropIfExists('candidate_extremity_analysis');
    }
};
