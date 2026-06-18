<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'users' => [
            'users_role_id_idx' => ['role_id'],
            'users_region_id_idx' => ['region_id'],
            'users_district_id_idx' => ['district_id'],
            'users_council_id_idx' => ['council_id'],
            'users_school_id_idx' => ['school_id'],
        ],
        'schools' => [
            'schools_name_idx' => ['name'],
            'schools_region_district_idx' => ['region_id', 'district_id'],
            'schools_district_name_idx' => ['district_id', 'name'],
            'schools_region_name_idx' => ['region_id', 'name'],
        ],
        'candidates' => [
            'candidates_school_year_idx' => ['school_id', 'exam_year_id'],
            'candidates_school_candidate_idx' => ['school_id', 'candidate_id'],
            'candidates_school_combination_idx' => ['school_id', 'combination_id'],
            'candidates_index_number_idx' => ['index_number'],
            'candidates_candidate_number_idx2' => ['candidate_number'],
        ],
        'candidate_exam_registrations' => [
            'cer_candidate_idx' => ['candidate_id'],
            'cer_school_year_idx' => ['school_id', 'exam_year_id'],
            'cer_school_year_status_idx' => ['school_id', 'exam_year_id', 'status'],
            'cer_school_year_type_idx' => ['school_id', 'exam_year_id', 'exam_type_id'],
        ],
        'candidate_subject_selections' => [
            'css_candidate_subject_idx' => ['candidate_id', 'subject_id'],
            'css_subject_year_idx' => ['subject_id', 'exam_year_id'],
            'css_school_subject_year_idx' => ['school_id', 'subject_id', 'exam_year_id'],
        ],
        'raw_marks' => [
            'raw_marks_candidate_subject_year_idx' => ['candidate_id', 'subject_id', 'exam_year_id'],
            'raw_marks_school_subject_year_idx' => ['school_id', 'subject_id', 'exam_year_id'],
            'raw_marks_school_subject_year_paper_idx' => ['school_id', 'subject_id', 'exam_year_id', 'paper_id'],
            'raw_marks_entered_by_idx' => ['entered_by'],
            'raw_marks_user_id_idx' => ['user_id'],
        ],
        'mark_entry_assignments' => [
            'mea_user_idx' => ['user_id'],
            'mea_assigned_to_idx' => ['assigned_to'],
            'mea_school_subject_year_idx2' => ['school_id', 'subject_id', 'exam_year_id'],
        ],
        'mark_entry_outliers' => [
            'meo_school_subject_year_candidate_idx' => ['school_id', 'subject_id', 'exam_year_id', 'candidate_id'],
        ],
        'mark_entry_validations' => [
            'mev_school_subject_year_candidate_idx' => ['school_id', 'subject_id', 'exam_year_id', 'candidate_id'],
        ],
        'audit_logs' => [
            'audit_logs_created_idx' => ['created_at'],
            'audit_logs_user_created_idx2' => ['user_id', 'created_at'],
            'audit_logs_entity_created_idx' => ['entity_type', 'entity_id', 'created_at'],
            'audit_logs_table_created_idx' => ['table_name', 'created_at'],
        ],
        'governance_audit_logs' => [
            'governance_logs_created_idx' => ['created_at'],
            'governance_logs_entity_created_idx' => ['entity_type', 'entity_id', 'created_at'],
            'governance_logs_table_created_idx' => ['table_name', 'created_at'],
            'governance_logs_model_created_idx' => ['model_type', 'model_id', 'created_at'],
        ],
        'psle_activity_logs' => [
            'pal_created_idx2' => ['created_at'],
            'pal_event_created_idx' => ['event_type', 'created_at'],
        ],
        'sessions' => [
            'sessions_user_id_idx2' => ['user_id'],
            'sessions_last_activity_idx2' => ['last_activity'],
        ],
        'jobs' => [
            'jobs_queue_reserved_idx' => ['queue', 'reserved_at'],
            'jobs_queue_available_idx' => ['queue', 'available_at'],
            'jobs_created_idx' => ['created_at'],
        ],
        'failed_jobs' => [
            'failed_jobs_failed_at_idx' => ['failed_at'],
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
                    if (
                        $this->hasAllColumns($table, $columns)
                        && ! $this->indexExists($table, $name)
                        && ! $this->indexWithColumnsExists($table, $columns)
                    ) {
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
                    if ($this->indexMatchesColumns($table, $name, $indexes[$name])) {
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
        }

        return false;
    }

    private function indexMatchesColumns(string $table, string $index, array $columns): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $tableName = $connection->getTablePrefix() . $table;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $rows = DB::select(
                'SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?',
                [$index]
            );

            if (empty($rows)) {
                return false;
            }

            $indexColumns = [];
            foreach ($rows as $row) {
                $indexColumns[(int) $row->Seq_in_index] = $row->Column_name;
            }

            ksort($indexColumns);

            return array_values($indexColumns) === array_values($columns);
        }

        return $this->indexExists($table, $index);
    }

    private function indexWithColumnsExists(string $table, array $columns): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $tableName = $connection->getTablePrefix() . $table;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $rows = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '`');
            $indexes = [];

            foreach ($rows as $row) {
                $indexes[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
            }

            foreach ($indexes as $indexColumns) {
                ksort($indexColumns);

                if (array_values($indexColumns) === array_values($columns)) {
                    return true;
                }
            }
        }

        return false;
    }
};
