<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('final_outlier_resolutions')) {
            return;
        }

        Schema::create('final_outlier_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_type_id');
            $table->unsignedInteger('year');
            $table->string('resolution_key', 120);
            $table->string('tab', 20)->nullable();
            $table->unsignedBigInteger('candidate_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['exam_type_id', 'year', 'resolution_key'], 'final_outlier_resolutions_unique');
            $table->index(['exam_type_id', 'year', 'tab'], 'final_outlier_resolutions_exam_year_tab');
            $table->index(['candidate_id', 'subject_id'], 'final_outlier_resolutions_candidate_subject');
            $table->index(['school_id'], 'final_outlier_resolutions_school');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_outlier_resolutions');
    }
};

