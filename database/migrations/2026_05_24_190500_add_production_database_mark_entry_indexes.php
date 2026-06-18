<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'candidate_exam_registrations' => [
            'cer_year_type_candidate_idx' => ['exam_year_id', 'exam_type_id', 'candidate_id'],
        ],
        'candidate_subject_selections' => [
            'css_year_subject_candidate_idx' => ['exam_year_id', 'subject_id', 'candidate_id'],
        ],
        'mark_entry_assignments' => [
            'mea_user_school_subject_year_idx' => ['assigned_to', 'school_id', 'subject_id', 'exam_year_id'],
            'mea_school_subject_year_status_idx' => ['school_id', 'subject_id', 'exam_year_id', 'status'],
        ],
        'psle_activity_logs' => [
            'pal_user_created_idx' => ['user_id', 'created_at'],
            'pal_school_subject_year_created_idx' => ['school_id', 'subject_id', 'exam_year_id', 'created_at'],
        ],
        'audit_logs' => [
            'audit_logs_user_created_idx' => ['user_id', 'created_at'],
        ],
        'governance_audit_logs' => [
            'governance_logs_user_created_idx' => ['user_id', 'created_at'],
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
                    if ($this->hasAllColumns($table, $columns) && ! $this->indexExists($table, $name)) {
                        $blueprint->index($columns, $name);
                    }
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

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return ! empty(DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?', [$index]));
        }

        if ($driver === 'sqlite') {
            foreach (DB::select('PRAGMA index_list("' . str_replace('"', '""', $tableName) . '")') as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }
};
