<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_mark_id')->constrained('raw_marks')->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending'); // pending | verified | returned_for_correction | corrected_resubmitted
            $table->text('return_reason')->nullable();
            $table->foreignId('returned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedSmallInteger('correction_round')->default(0);
            $table->timestamps();

            $table->unique('raw_mark_id', 'unique_raw_mark_verification');
            $table->index(['subject_id', 'status']);
            $table->index(['exam_year_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_verifications');
    }
};
