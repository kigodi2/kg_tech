<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_exam_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->string('registration_number')->unique();
            $table->string('status', 30)->default('PENDING');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'year'], 'cer_candidate_exam_year_unique');
            $table->index(['exam_type_id', 'year'], 'cer_exam_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_exam_registrations');
    }
};
