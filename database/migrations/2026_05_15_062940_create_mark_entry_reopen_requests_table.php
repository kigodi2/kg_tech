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
        Schema::create('mark_entry_reopen_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_year_id')->constrained('exam_years')->onDelete('cascade');
            $table->foreignId('exam_type_id')->constrained('exam_types')->onDelete('cascade');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('cascade');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            $table->foreignId('candidate_id')->nullable()->constrained('candidates')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_entry_reopen_requests');
    }
};
