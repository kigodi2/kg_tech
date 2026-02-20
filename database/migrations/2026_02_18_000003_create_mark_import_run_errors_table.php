<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_import_run_errors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('run_id')->constrained('mark_import_runs')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('index_number', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('paper', 30)->nullable();
            $table->string('column_name', 50)->nullable();
            $table->string('error_code', 50); // OUT_OF_RANGE, INVALID_FORMAT, NOT_REGISTERED, DUPLICATE, MISSING_REQUIRED, REFERENTIAL_MISMATCH
            $table->text('message');
            $table->string('raw_value', 255)->nullable();
            $table->string('severity', 20)->default('error'); // error, warning
            $table->timestamps();

            $table->index(['run_id']);
            $table->index(['error_code']);
            $table->index(['index_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_import_run_errors');
    }
};
