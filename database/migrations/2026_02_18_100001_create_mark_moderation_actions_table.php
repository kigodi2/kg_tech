<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_moderation_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('action', 30); // APPROVE, REJECT, OVERRIDE
            $table->string('scope', 50); // single_subject, school, district, candidate, run
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('mark_import_batch_id')->nullable();
            $table->unsignedBigInteger('run_id')->nullable();
            $table->unsignedBigInteger('exam_year_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedInteger('affected_rows')->default(0);
            $table->text('reason')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();

            $table->index(['action']);
            $table->index(['actor_id']);
            $table->index(['mark_import_batch_id']);
            $table->index(['created_at']);
            $table->index(['exam_year_id', 'action']);

            $table->foreign('actor_id')->references('id')->on('users');
            $table->foreign('mark_import_batch_id')->references('id')->on('mark_import_batches')->nullOnDelete();
            $table->foreign('run_id')->references('id')->on('mark_import_runs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_moderation_actions');
    }
};
