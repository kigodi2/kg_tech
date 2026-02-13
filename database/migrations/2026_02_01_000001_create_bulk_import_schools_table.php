<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create bulk_import_schools pivot table for district-level imports
     * Tracks which schools are included in each district import
     */
    public function up(): void
    {
        Schema::create('bulk_import_schools', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulk_import_id');
            $table->unsignedBigInteger('school_id');
            $table->string('school_code'); // For audit trail
            $table->string('school_name'); // For audit trail
            $table->enum('status', ['pending', 'processing', 'success', 'partial', 'failed'])->default('pending');
            $table->integer('total_subjects')->default(0);
            $table->integer('processed_subjects')->default(0);
            $table->integer('total_candidates')->default(0);
            $table->integer('successful_candidates')->default(0);
            $table->integer('failed_candidates')->default(0);
            $table->text('error_summary')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['bulk_import_id', 'school_id']);
            $table->index(['school_id', 'status']);
            $table->index('status');

            // Foreign keys
            $table->foreign('bulk_import_id')
                ->references('id')
                ->on('bulk_imports')
                ->onDelete('cascade');
            $table->foreign('school_id')
                ->references('id')
                ->on('schools')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_import_schools');
    }
};
