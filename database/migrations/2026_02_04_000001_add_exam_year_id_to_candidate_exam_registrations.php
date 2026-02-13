<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add exam_year_id FK to candidate_exam_registrations table
     */
    public function up(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            // Add exam_year_id if it doesn't exist
            if (!Schema::hasColumn('candidate_exam_registrations', 'exam_year_id')) {
                $table->foreignId('exam_year_id')
                    ->nullable()
                    ->constrained('exam_years')
                    ->onDelete('cascade')
                    ->after('exam_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            // Drop the foreign key constraint if it exists
            if (Schema::hasColumn('candidate_exam_registrations', 'exam_year_id')) {
                $table->dropForeign(['exam_year_id']);
                $table->dropColumn('exam_year_id');
            }
        });
    }
};
