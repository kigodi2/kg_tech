<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_rejections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_id')->nullable();
            $table->unsignedBigInteger('mark_import_batch_id')->nullable();
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('reason_code', 50); // DATA_QUALITY, MISSING_MARKS, WRONG_SUBJECT, DUPLICATE_SUBMISSION, TEMPLATE_ERROR, OTHER
            $table->text('note')->nullable();
            $table->unsignedBigInteger('rejected_by');
            $table->string('scope', 30)->default('batch'); // candidate, subject_batch, run, batch
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();

            $table->index(['run_id']);
            $table->index(['mark_import_batch_id']);
            $table->index(['rejected_by']);
            $table->index(['reason_code']);
            $table->index(['created_at']);

            $table->foreign('run_id')->references('id')->on('mark_import_runs')->nullOnDelete();
            $table->foreign('mark_import_batch_id')->references('id')->on('mark_import_batches')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_rejections');
    }
};
