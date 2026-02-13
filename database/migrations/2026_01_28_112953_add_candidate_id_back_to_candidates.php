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
        // Only add the column if it doesn't already exist
        // In some database states, it may have been removed
        try {
            if (!Schema::hasColumn('candidates', 'candidate_id')) {
                Schema::table('candidates', function (Blueprint $table) {
                    $table->string('candidate_id', 50)->unique()->nullable()->after('school_id');
                });
            }
        } catch (\Exception $e) {
            // Silently fail if column already exists (SQLite limitation with hasColumn in table modifications)
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('candidate_id');
        });
    }
};
