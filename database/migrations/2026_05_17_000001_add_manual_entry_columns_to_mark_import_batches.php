<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns required by the manual mark entry (entry-sheet) workflow.
     *
     * - batch_name   : human-readable label for the batch (e.g. "Officer Entry - Tabora PS (Math)")
     * - batch_type   : distinguishes 'manual' from 'single_csv', 'school_zip', 'district_zip'
     * - created_by   : FK to users — who created this batch (officer or admin)
     * - exam_year_id : FK to exam_years — complements the existing integer `exam_year` column
     */
    public function up(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_import_batches', 'batch_name')) {
                $table->string('batch_name')->nullable()->after('batch_code');
            }

            if (!Schema::hasColumn('mark_import_batches', 'batch_type')) {
                $table->string('batch_type')->nullable()->default('single_csv')->after('batch_name');
                // Values: 'manual', 'single_csv', 'school_zip', 'district_zip'
            }

            if (!Schema::hasColumn('mark_import_batches', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('imported_by')
                      ->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('mark_import_batches', 'exam_year_id')) {
                $table->foreignId('exam_year_id')->nullable()->after('exam_year')
                      ->constrained('exam_years')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            // Drop foreign keys before columns
            if (Schema::hasColumn('mark_import_batches', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('mark_import_batches', 'exam_year_id')) {
                $table->dropForeign(['exam_year_id']);
                $table->dropColumn('exam_year_id');
            }
            if (Schema::hasColumn('mark_import_batches', 'batch_type')) {
                $table->dropColumn('batch_type');
            }
            if (Schema::hasColumn('mark_import_batches', 'batch_name')) {
                $table->dropColumn('batch_name');
            }
        });
    }
};
