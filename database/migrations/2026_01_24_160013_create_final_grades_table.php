<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('grading_profile_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->string('grade', 2);
            $table->string('grade_name', 50);
            $table->decimal('final_percentage', 5, 2);
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'year'], 'final_grades_candidate_exam_year_unique');
            $table->index(['exam_type_id', 'year'], 'final_grades_exam_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};
