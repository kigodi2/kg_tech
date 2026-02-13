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
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->enum('type', ['full_system', 'exam_year', 'metadata_only'])->default('exam_year');
            $table->unsignedBigInteger('exam_year_id')->nullable();
            $table->string('filename');
            $table->string('path');
            $table->text('manifest')->nullable(); // JSON
            $table->string('checksum_algo')->default('SHA256');
            $table->string('checksum');
            $table->string('signature')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->boolean('verified')->default(false);
            $table->dateTime('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('admin_id')->references('id')->on('users');
            $table->foreign('exam_year_id')->references('id')->on('exam_years');
            $table->foreign('verified_by')->references('id')->on('users');

            $table->index('type');
            $table->index('exam_year_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
