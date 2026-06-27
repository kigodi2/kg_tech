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
        Schema::table('subject_marks', function (Blueprint $table) {
            $table->index(['year', 'candidate_id', 'subject_id'], 'idx_subject_marks_year_candidate_subject');
            $table->index(['year', 'subject_id', 'marks_obtained'], 'idx_subject_marks_year_subject_marks');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->index(['exam_year_id', 'school_id'], 'idx_candidates_exam_year_school');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('idx_candidates_exam_year_school');
        });

        Schema::table('subject_marks', function (Blueprint $table) {
            $table->dropIndex('idx_subject_marks_year_candidate_subject');
            $table->dropIndex('idx_subject_marks_year_subject_marks');
        });
    }
};
