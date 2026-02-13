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
        Schema::create('bulk_import_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bulk_import_id');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_code');
            $table->string('filename');
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->integer('rows_total')->default(0);
            $table->integer('rows_success')->default(0);
            $table->integer('rows_failed')->default(0);
            $table->longText('error_log')->nullable();
            $table->string('file_hash')->nullable(); // SHA-256 of CSV file
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('bulk_import_id');
            $table->index(['subject_code', 'status']);
            $table->foreign('bulk_import_id')->references('id')->on('bulk_imports')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bulk_import_files');
    }
};
