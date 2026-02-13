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
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->unique(['candidate_id', 'exam_type_id', 'year']);
            $table->index(['exam_type_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_exam_registrations');
    }
};
