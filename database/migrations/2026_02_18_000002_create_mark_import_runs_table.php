<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_import_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('exam_year_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('mark_import_batch_id')->nullable();
            $table->string('scope', 50)->default('school'); // school, district
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 30)->default('pending'); // pending, processing, completed, failed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('success_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->text('summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['exam_year_id', 'school_id', 'subject_id']);
            $table->index(['user_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_import_runs');
    }
};
