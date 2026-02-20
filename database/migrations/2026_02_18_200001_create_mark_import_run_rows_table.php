<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mark_import_run_rows')) {
            Schema::create('mark_import_run_rows', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('run_id')->constrained('mark_import_runs')->cascadeOnDelete();
                $table->unsignedInteger('row_number');
                $table->string('source_file', 500)->nullable();
                $table->string('index_number', 50);
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('school_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->decimal('paper_1', 6, 2)->nullable();
                $table->decimal('paper_2', 6, 2)->nullable();
                $table->decimal('paper_3', 6, 2)->nullable();
                $table->decimal('practical', 6, 2)->nullable();
                $table->decimal('project', 6, 2)->nullable();
                $table->decimal('total', 6, 2)->nullable();
                $table->boolean('is_valid')->default(true);
                $table->string('status', 20)->default('pending'); // pending, accepted, rejected
                $table->timestamp('created_at')->nullable();

                $table->index(['run_id', 'is_valid']);
                $table->index(['index_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_import_run_rows');
    }
};
