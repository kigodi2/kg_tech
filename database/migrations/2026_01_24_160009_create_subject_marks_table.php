<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->decimal('marks_obtained', 5, 2)->nullable();
            $table->integer('max_marks')->default(100);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'subject_id', 'year']);
            $table->index(['exam_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_marks');
    }
};
