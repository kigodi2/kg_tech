<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_mark_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained('exam_types')->restrictOnDelete();
            $table->string('exam_type', 20);
            $table->foreignId('exam_year_id')->constrained('exam_years')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->string('candidate_no', 100);
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->decimal('total', 8, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['exam_type_id', 'exam_year_id', 'candidate_id', 'subject_id'],
                'question_mark_entries_unique_context'
            );
            $table->index(['exam_year_id', 'candidate_id'], 'question_mark_entries_year_candidate_idx');
            $table->index(['candidate_no', 'exam_type_id'], 'question_mark_entries_candidate_no_idx');
            $table->index(['subject_id', 'region_id'], 'question_mark_entries_subject_region_idx');
            $table->index(['exam_type', 'status'], 'question_mark_entries_type_status_idx');
        });

        Schema::create('question_mark_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('question_mark_entries')->cascadeOnDelete();
            $table->unsignedInteger('question_no');
            $table->decimal('max_mark', 8, 2)->default(0);
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'question_no'], 'question_mark_entry_items_unique_question');
            $table->index(['entry_id', 'question_no'], 'question_mark_entry_items_entry_question_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_mark_entry_items');
        Schema::dropIfExists('question_mark_entries');
    }
};
