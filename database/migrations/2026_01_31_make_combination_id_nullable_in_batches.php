<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Make combination_id nullable in mark_import_batches.
     * Combination is now derived from candidate registration, not stored in batch.
     */
    public function up(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            // Make combination_id nullable
            $table->unsignedBigInteger('combination_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            // Revert to required
            $table->unsignedBigInteger('combination_id')->nullable(false)->change();
        });
    }
};
