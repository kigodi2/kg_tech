<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add nullable exam_year_id and exam_type_id to candidates table
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'exam_year_id')) {
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
            }
            if (!Schema::hasColumn('candidates', 'exam_type_id')) {
                $table->foreignId('exam_type_id')->nullable()->constrained('exam_types')->nullOnDelete();
            }
        });

        // 2. Normalize empty strings for prem_no to NULL to satisfy unique constraints
        DB::table('candidates')->where('prem_no', '')->update(['prem_no' => null]);

        // 3. Populate existing rows from candidate_exam_registrations
        try {
            DB::statement("
                UPDATE candidates c
                INNER JOIN candidate_exam_registrations cer ON cer.candidate_id = c.id
                SET c.exam_year_id = cer.exam_year_id,
                    c.exam_type_id = cer.exam_type_id
                WHERE c.exam_year_id IS NULL OR c.exam_type_id IS NULL
            ");
        } catch (\Throwable $e) {
            Log::warning("Failed to populate candidates exam columns from registrations: " . $e->getMessage());
        }

        // Fallback for candidates missing registrations
        try {
            DB::statement("
                UPDATE candidates c
                INNER JOIN exam_types et ON et.code = c.exam_type
                SET c.exam_type_id = et.id
                WHERE c.exam_type_id IS NULL
            ");
        } catch (\Throwable $e) {
            Log::warning("Failed to populate candidates exam_type_id from code: " . $e->getMessage());
        }

        $activeYearId = DB::table('exam_years')->where('is_active', true)->value('id');
        if ($activeYearId) {
            DB::table('candidates')->whereNull('exam_year_id')->update(['exam_year_id' => $activeYearId]);
        }

        // 4. Safely create unique constraints (wrapped in try-catch to ignore existing local duplicates)
        try {
            Schema::table('candidates', function (Blueprint $table) {
                $table->unique(['prem_no', 'exam_year_id', 'exam_type_id'], 'candidates_prem_year_type_unique');
            });
        } catch (\Throwable $e) {
            Log::warning("Could not create unique index candidates_prem_year_type_unique due to legacy duplicates: " . $e->getMessage());
        }

        try {
            Schema::table('candidates', function (Blueprint $table) {
                $table->unique(['candidate_id', 'exam_year_id', 'exam_type_id'], 'candidates_number_year_type_unique');
            });
        } catch (\Throwable $e) {
            Log::warning("Could not create unique index candidates_number_year_type_unique: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            try {
                $table->dropUnique('candidates_prem_year_type_unique');
            } catch (\Throwable $e) {}

            try {
                $table->dropUnique('candidates_number_year_type_unique');
            } catch (\Throwable $e) {}

            $table->dropColumn(['exam_year_id', 'exam_type_id']);
        });
    }
};
