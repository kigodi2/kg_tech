<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psle_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('affected_candidates')->default(0);
            $table->unsignedInteger('affected_marks')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['exam_year_id', 'region_id']);
            $table->index(['exam_year_id', 'district_id']);
            $table->index(['exam_year_id', 'school_id']);
            $table->index('event_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psle_activity_logs');
    }
};
