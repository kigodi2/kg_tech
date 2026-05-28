<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->decimal('total_marks', 7, 2)->nullable();
            $table->decimal('total_percentage', 5, 2)->nullable();
            $table->string('overall_grade')->nullable();
            $table->enum('status', ['PENDING', 'RELEASED', 'WITHHELD'])->default('PENDING');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'year'], 'candidate_results_candidate_exam_year_unique');
            $table->index(['exam_type_id', 'year'], 'candidate_results_exam_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_results');
    }
};
