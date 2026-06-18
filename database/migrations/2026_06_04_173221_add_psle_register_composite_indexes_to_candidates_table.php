<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $driver = DB::getDriverName();
            if ($driver === 'mysql') {
                return count(DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$index])) > 0;
            } else if ($driver === 'sqlite') {
                return count(DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?", [$index])) > 0;
            }
        } catch (\Exception $e) {
            // Fallback to false
        }
        return false;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Resolve exam year column name dynamically
        $yearColumn = null;
        if (Schema::hasColumn('candidates', 'exam_year_id')) {
            $yearColumn = 'exam_year_id';
        } elseif (Schema::hasColumn('candidates', 'exam_year')) {
            $yearColumn = 'exam_year';
        }

        if (!$yearColumn) {
            return; // No year column found to create indexes on
        }

        Schema::table('candidates', function (Blueprint $table) use ($yearColumn) {
            // 1. school_id + exam_year
            if (Schema::hasColumn('candidates', 'school_id') && !$this->indexExists('candidates', 'idx_candidates_school_year')) {
                $table->index(['school_id', $yearColumn], 'idx_candidates_school_year');
            }

            // 2. region_id + exam_year
            if (Schema::hasColumn('candidates', 'region_id') && !$this->indexExists('candidates', 'idx_candidates_region_year')) {
                $table->index(['region_id', $yearColumn], 'idx_candidates_region_year');
            }

            // 3. district_id + exam_year OR council_id + exam_year
            if (Schema::hasColumn('candidates', 'council_id')) {
                if (!$this->indexExists('candidates', 'idx_candidates_council_year')) {
                    $table->index(['council_id', $yearColumn], 'idx_candidates_council_year');
                }
            } elseif (Schema::hasColumn('candidates', 'district_id')) {
                if (!$this->indexExists('candidates', 'idx_candidates_district_year')) {
                    $table->index(['district_id', $yearColumn], 'idx_candidates_district_year');
                }
            }

            // 4. exam_year
            if (!$this->indexExists('candidates', 'idx_candidates_exam_year')) {
                $table->index($yearColumn, 'idx_candidates_exam_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if ($this->indexExists('candidates', 'idx_candidates_school_year')) {
                $table->dropIndex('idx_candidates_school_year');
            }
            if ($this->indexExists('candidates', 'idx_candidates_region_year')) {
                $table->dropIndex('idx_candidates_region_year');
            }
            if ($this->indexExists('candidates', 'idx_candidates_council_year')) {
                $table->dropIndex('idx_candidates_council_year');
            }
            if ($this->indexExists('candidates', 'idx_candidates_district_year')) {
                $table->dropIndex('idx_candidates_district_year');
            }
            if ($this->indexExists('candidates', 'idx_candidates_exam_year')) {
                $table->dropIndex('idx_candidates_exam_year');
            }
        });
    }
};
