<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds exam_year_id to all exam-related tables.
     * This enforces multi-year data isolation at the database level.
     *
     * Tables affected:
     * - candidates
     * - registrations
     * - subject_registrations
     * - marks
     * - results
     * - summaries
     * - uploads
     * - reports
     * - csv_templates
     */
    public function up(): void
    {
        // Tables to update (only if they exist)
        $tables = [
            'candidates' => ['exam_year_id', 'school_id'],
            'registrations' => ['exam_year_id', 'candidate_id'],
            'subject_registrations' => ['exam_year_id', 'subject_id'],
            'marks' => ['exam_year_id', 'candidate_id', 'subject_id'],
            'results' => ['exam_year_id', 'candidate_id'],
            'summaries' => ['exam_year_id'],
            'uploads' => ['exam_year_id', 'status'],
            'reports' => ['exam_year_id', 'school_id'],
            'csv_templates' => ['exam_year_id', 'school_id', 'subject_id'],
        ];

        foreach ($tables as $tableName => $indexColumns) {
            // Only alter if table exists and column doesn't
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'exam_year_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($indexColumns) {
                    $table->foreignId('exam_year_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('exam_years')
                        ->cascadeOnDelete()
                        ->comment('Reference to exam year');
                    
                    $table->index($indexColumns);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys and columns in reverse order
        $tables = [
            'csv_templates',
            'reports',
            'uploads',
            'summaries',
            'results',
            'marks',
            'subject_registrations',
            'registrations',
            'candidates',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'exam_year_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['exam_year_id']);
                    $table->dropColumn('exam_year_id');
                });
            }
        }
    }
};
