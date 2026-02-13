<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_profile_id')->constrained()->onDelete('cascade');
            $table->decimal('min_percentage', 5, 2);
            $table->decimal('max_percentage', 5, 2);
            $table->string('grade', 2);
            $table->string('grade_name', 50);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['grading_profile_id', 'grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_rules');
    }
};
