<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_subject_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'subject_id', 'year']);
            $table->index(['exam_type_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_subject_selections');
    }
};
