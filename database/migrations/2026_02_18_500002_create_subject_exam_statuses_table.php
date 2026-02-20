<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_exam_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('exam_year', 10); // year label like "2025"
            $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->string('status', 10); // X, ABS, INC
            $table->string('source', 30); // validation, moderation, import
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('run_error_id')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'subject_id', 'exam_year', 'exam_type_id'], 'ses_cand_subj_year_type_unique');
            $table->index(['status']);
            $table->index(['batch_id']);

            $table->foreign('batch_id')->references('id')->on('mark_import_batches')->nullOnDelete();
            $table->foreign('decided_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('run_error_id')->references('id')->on('mark_import_run_errors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_exam_statuses');
    }
};
