<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('psle_result_validation_errors')) {
            Schema::create('psle_result_validation_errors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
                $table->string('scope_type', 20)->default('national');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
                $table->foreignId('result_id')->nullable()->constrained('candidate_results')->nullOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->string('candidate_no')->nullable();
                $table->string('subject_code')->nullable();
                $table->string('error_type', 80);
                $table->text('error_message');
                $table->string('severity', 20)->default('info');
                $table->json('metadata')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['exam_year_id', 'scope_type', 'scope_id'], 'psle_validation_scope_idx');
                $table->index(['severity', 'error_type'], 'psle_validation_severity_idx');
            });
        }

        if (!Schema::hasTable('psle_result_approvals')) {
            Schema::create('psle_result_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
                $table->foreignId('result_snapshot_id')->nullable()->constrained('result_snapshots')->nullOnDelete();
                $table->foreignId('psle_result_id')->nullable()->constrained('candidate_results')->nullOnDelete();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('council_id')->nullable();
                $table->foreign('council_id')->references('id')->on('district_councils')->nullOnDelete();
                $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
                $table->string('stage', 50);
                $table->string('action', 50);
                $table->unsignedTinyInteger('readiness_score')->nullable();
                $table->text('comments')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();

                $table->index(['exam_year_id', 'stage', 'action'], 'psle_approval_stage_idx');
                $table->index(['school_id', 'acted_at'], 'psle_approval_school_idx');
            });
        }

        if (!Schema::hasTable('psle_result_publications')) {
            Schema::create('psle_result_publications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
                $table->foreignId('snapshot_id')->nullable()->constrained('result_snapshots')->nullOnDelete();
                $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
                $table->string('council_id')->nullable();
                $table->foreign('council_id')->references('id')->on('district_councils')->nullOnDelete();
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                $table->string('publication_scope', 20)->default('national');
                $table->string('status', 30)->default('draft');
                $table->string('version_no', 40)->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('published_at')->nullable();
                $table->timestamps();

                $table->index(['exam_year_id', 'publication_scope', 'status'], 'psle_publication_scope_idx');
                $table->index(['snapshot_id', 'published_at'], 'psle_publication_snapshot_idx');
            });
        }

        if (!Schema::hasTable('psle_result_amendments')) {
            Schema::create('psle_result_amendments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('psle_result_id')->constrained('candidate_results')->cascadeOnDelete();
                $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
                $table->string('amendment_type', 60);
                $table->json('old_value')->nullable();
                $table->json('new_value')->nullable();
                $table->text('reason');
                $table->string('status', 30)->default('pending');
                $table->json('metadata')->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('effective_at')->nullable();
                $table->timestamps();

                $table->index(['exam_year_id', 'status'], 'psle_amendment_status_idx');
                $table->index(['candidate_id', 'created_at'], 'psle_amendment_candidate_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('psle_result_amendments');
        Schema::dropIfExists('psle_result_publications');
        Schema::dropIfExists('psle_result_approvals');
        Schema::dropIfExists('psle_result_validation_errors');
    }
};
