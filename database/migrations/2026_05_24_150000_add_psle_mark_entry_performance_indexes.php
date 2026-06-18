<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'candidates' => [
            'candidates_school_examtype_active_idx' => ['school_id', 'exam_type', 'is_active'],
            'candidates_candidate_number_idx' => ['candidate_id'],
        ],
        'raw_marks' => [
            'raw_marks_year_school_subject_candidate_idx' => ['exam_year_id', 'school_id', 'subject_id', 'candidate_id'],
            'raw_marks_year_school_subject_updated_idx' => ['exam_year_id', 'school_id', 'subject_id', 'updated_at'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $name => $columns) {
                    if (! $this->hasAllColumns($table, $columns) || $this->indexExists($table, $name)) {
                        continue;
                    }

                    $blueprint->index($columns, $name);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach (array_keys($indexes) as $name) {
                    if ($this->indexExists($table, $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }

    private function hasAllColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $tableName = $connection->getTablePrefix() . $table;

        if ($driver === 'mysql') {
            return ! empty(DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?', [$index]));
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA index_list(' . $tableName . ')') as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        if ($driver === 'sqlsrv') {
            return DB::table('sys.indexes')->where('name', $index)->exists();
        }

        return false;
    }
};
