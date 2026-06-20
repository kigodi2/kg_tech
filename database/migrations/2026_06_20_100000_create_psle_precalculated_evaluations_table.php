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
        Schema::create('psle_precalculated_evaluations', function (Blueprint $table) {
            $table->id();
            $table->integer('exam_year');
            $table->string('exam_type', 10)->default('PSLE');
            $table->string('scope_type', 20); // e.g. zonal, regional
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('evaluation_key', 100);
            $table->unsignedBigInteger('snapshot_id');
            $table->string('status', 20)->default('pending'); // pending, building, ready, failed, stale
            $table->longText('data')->nullable(); // json stored payload
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for speed
            $table->index(['exam_year', 'exam_type', 'scope_type', 'scope_id'], 'psle_precalc_scope_idx');
            $table->index(['evaluation_key', 'status'], 'psle_precalc_eval_status_idx');
            $table->index(['snapshot_id'], 'psle_precalc_snap_idx');

            // Unique key for snapshot caching
            $table->unique(['exam_year', 'exam_type', 'scope_type', 'scope_id', 'evaluation_key', 'snapshot_id'], 'psle_precalc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psle_precalculated_evaluations');
    }
};
