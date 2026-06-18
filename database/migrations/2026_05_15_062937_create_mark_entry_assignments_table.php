<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mark_entry_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('marking_centre_id')->constrained('marking_centres')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('assignment_type')->default('entry');
            $table->string('batch_code')->nullable();
            $table->integer('candidate_range_start')->nullable();
            $table->integer('candidate_range_end')->nullable();
            $table->enum('status', ['active', 'completed', 'revoked'])->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_entry_assignments');
    }
};
