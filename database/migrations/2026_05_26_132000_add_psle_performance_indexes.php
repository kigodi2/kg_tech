<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPslePerformanceIndexes extends Migration
{
    private function indexExists($table, $index)
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                return count(DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])) > 0;
            } else if ($driver === 'sqlite') {
                return count(DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?", [$index])) > 0;
            }
        } catch (\Exception $e) {
            // Fallback to false if query fails
        }
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. candidates table indexes
        if (!$this->indexExists('candidates', 'idx_candidates_exam_type')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index('exam_type', 'idx_candidates_exam_type');
            });
        }
        if (!$this->indexExists('candidates', 'idx_candidates_prem_no')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index('prem_no', 'idx_candidates_prem_no');
            });
        }
        if (!$this->indexExists('candidates', 'idx_candidates_gender')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index('gender', 'idx_candidates_gender');
            });
        }
        if (!$this->indexExists('candidates', 'idx_candidates_exam_type_school')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index(['exam_type', 'school_id'], 'idx_candidates_exam_type_school');
            });
        }
        if (!$this->indexExists('candidates', 'idx_candidates_exam_type_candidate')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index(['exam_type', 'candidate_id'], 'idx_candidates_exam_type_candidate');
            });
        }
        if (!$this->indexExists('candidates', 'idx_candidates_exam_type_prem')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->index(['exam_type', 'prem_no'], 'idx_candidates_exam_type_prem');
            });
        }

        // 2. candidate_exam_registrations table indexes
        if (!$this->indexExists('candidate_exam_registrations', 'idx_cer_status')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->index('status', 'idx_cer_status');
            });
        }
        if (!$this->indexExists('candidate_exam_registrations', 'idx_cer_type_year')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->index(['exam_type_id', 'exam_year_id'], 'idx_cer_type_year');
            });
        }
        if (!$this->indexExists('candidate_exam_registrations', 'idx_cer_type_year_candidate')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->index(['exam_type_id', 'exam_year_id', 'candidate_id'], 'idx_cer_type_year_candidate');
            });
        }
        if (!$this->indexExists('candidate_exam_registrations', 'idx_cer_type_year_status')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->index(['exam_type_id', 'exam_year_id', 'status'], 'idx_cer_type_year_status');
            });
        }

        // 3. schools table indexes
        if (!$this->indexExists('schools', 'idx_schools_region_council')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->index(['region_id', 'council_id'], 'idx_schools_region_council');
            });
        }
        if (!$this->indexExists('schools', 'idx_schools_council_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->index(['council_id', 'id'], 'idx_schools_council_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // candidates
        if ($this->indexExists('candidates', 'idx_candidates_exam_type')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_exam_type');
            });
        }
        if ($this->indexExists('candidates', 'idx_candidates_prem_no')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_prem_no');
            });
        }
        if ($this->indexExists('candidates', 'idx_candidates_gender')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_gender');
            });
        }
        if ($this->indexExists('candidates', 'idx_candidates_exam_type_school')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_exam_type_school');
            });
        }
        if ($this->indexExists('candidates', 'idx_candidates_exam_type_candidate')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_exam_type_candidate');
            });
        }
        if ($this->indexExists('candidates', 'idx_candidates_exam_type_prem')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropIndex('idx_candidates_exam_type_prem');
            });
        }

        // registrations
        if ($this->indexExists('candidate_exam_registrations', 'idx_cer_status')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->dropIndex('idx_cer_status');
            });
        }
        if ($this->indexExists('candidate_exam_registrations', 'idx_cer_type_year')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->dropIndex('idx_cer_type_year');
            });
        }
        if ($this->indexExists('candidate_exam_registrations', 'idx_cer_type_year_candidate')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->dropIndex('idx_cer_type_year_candidate');
            });
        }
        if ($this->indexExists('candidate_exam_registrations', 'idx_cer_type_year_status')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->dropIndex('idx_cer_type_year_status');
            });
        }

        // schools
        if ($this->indexExists('schools', 'idx_schools_region_council')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropIndex('idx_schools_region_council');
            });
        }
        if ($this->indexExists('schools', 'idx_schools_council_id')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropIndex('idx_schools_council_id');
            });
        }
    }
}
