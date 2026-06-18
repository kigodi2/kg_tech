<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_panel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exam_year_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One user should not be assigned twice to the same subject in the same exam year
            $table->unique(['user_id', 'subject_id', 'exam_year_id'], 'unique_panel_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_panel_assignments');
    }
};
