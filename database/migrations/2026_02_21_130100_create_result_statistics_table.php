<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('result_statistics')) {
            Schema::create('result_statistics', function (Blueprint $table) {
                $table->id();
                $table->string('exam_type', 20);
                $table->unsignedBigInteger('exam_year_id');
                $table->unsignedBigInteger('snapshot_id')->nullable();
                $table->unsignedBigInteger('process_id')->nullable();
                $table->string('scope_type', 20)->default('national');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->unsignedInteger('candidates_count')->default(0);
                $table->unsignedInteger('schools_count')->default(0);
                $table->decimal('mean_aggt', 6, 2)->nullable();
                $table->decimal('mean_gpa', 6, 2)->nullable();
                $table->json('division_counts')->nullable();
                $table->json('irregularity_counts')->nullable();
                $table->json('subject_grade_distributions')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();

                $table->index(['exam_type', 'exam_year_id']);
                $table->index(['snapshot_id']);
                $table->index(['process_id']);
                $table->index(['scope_type', 'scope_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('result_statistics');
    }
};

