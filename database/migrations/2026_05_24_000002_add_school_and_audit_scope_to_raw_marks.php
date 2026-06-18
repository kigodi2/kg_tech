<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $uniqueName = 'raw_marks_year_school_subject_candidate_unique';

    public function up(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable();
            }
            if (!Schema::hasColumn('raw_marks', 'entered_by')) {
                $table->unsignedBigInteger('entered_by')->nullable();
            }
            if (!Schema::hasColumn('raw_marks', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }
        });

        DB::table('raw_marks')
            ->leftJoin('candidates', 'raw_marks.candidate_id', '=', 'candidates.id')
            ->leftJoin('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->whereNull('raw_marks.school_id')
            ->select(
                'raw_marks.id',
                'candidates.school_id as candidate_school_id',
                'mark_import_batches.school_id as batch_school_id'
            )
            ->orderBy('raw_marks.id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $schoolId = $row->candidate_school_id ?: $row->batch_school_id;
                    if ($schoolId) {
                        DB::table('raw_marks')
                            ->where('id', $row->id)
                            ->update(['school_id' => $schoolId]);
                    }
                }
            }, 'raw_marks.id', 'id');

        DB::table('raw_marks')
            ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->whereNull('raw_marks.entered_by')
            ->whereNotNull('mark_import_batches.created_by')
            ->select('raw_marks.id', 'mark_import_batches.created_by')
            ->orderBy('raw_marks.id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('raw_marks')
                        ->where('id', $row->id)
                        ->update([
                            'entered_by' => $row->created_by,
                            'updated_by' => $row->created_by,
                        ]);
                }
            }, 'raw_marks.id', 'id');

        $duplicates = DB::table('raw_marks')
            ->select('exam_year_id', 'school_id', 'subject_id', 'candidate_id')
            ->whereNotNull('exam_year_id')
            ->whereNotNull('school_id')
            ->whereNotNull('subject_id')
            ->whereNotNull('candidate_id')
            ->groupBy('exam_year_id', 'school_id', 'subject_id', 'candidate_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $records = DB::table('raw_marks')
                ->leftJoin('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
                ->where('raw_marks.exam_year_id', $duplicate->exam_year_id)
                ->where('raw_marks.school_id', $duplicate->school_id)
                ->where('raw_marks.subject_id', $duplicate->subject_id)
                ->where('raw_marks.candidate_id', $duplicate->candidate_id)
                ->select('raw_marks.*', 'mark_import_batches.status as batch_status', 'mark_import_batches.batch_type')
                ->get()
                ->sortBy(function ($record) {
                    $priority = match ($record->batch_status) {
                        'locked' => 1,
                        'approved' => 2,
                        'submitted' => 3,
                        'validated' => 4,
                        'draft' => 5,
                        default => 6,
                    };

                    return sprintf('%d_%s', $priority, 9999999999 - strtotime((string) $record->updated_at));
                })
                ->values();

            $keep = $records->first();
            if (!$keep) {
                continue;
            }

            foreach ($records->slice(1) as $record) {
                if (Schema::hasTable('raw_marks_duplicates_backup')) {
                    DB::table('raw_marks_duplicates_backup')->insert([
                        'raw_mark_id' => $record->id,
                        'mark_import_batch_id' => $record->mark_import_batch_id,
                        'candidate_id' => $record->candidate_id,
                        'subject_id' => $record->subject_id,
                        'exam_year_id' => $record->exam_year_id,
                        'paper_1_marks' => $record->paper_1_marks,
                        'candidate_index_number' => $record->candidate_index_number,
                        'full_name' => $record->full_name,
                        'batch_status' => $record->batch_status,
                        'batch_type' => $record->batch_type,
                        'raw_mark_updated_at' => $record->updated_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->deleteRawMarkDependents((int) $record->id);
                DB::table('raw_marks')->where('id', $record->id)->delete();
            }
        }

        if (!$this->indexExists('raw_marks', $this->uniqueName)) {
            Schema::table('raw_marks', function (Blueprint $table) {
                $table->unique(
                    ['exam_year_id', 'school_id', 'subject_id', 'candidate_id'],
                    $this->uniqueName
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('raw_marks', $this->uniqueName)) {
            Schema::table('raw_marks', function (Blueprint $table) {
                $table->dropUnique($this->uniqueName);
            });
        }
    }

    private function deleteRawMarkDependents(int $rawMarkId): void
    {
        foreach (['mark_entry_validations', 'mark_entry_outliers', 'mark_outlier_resolutions', 'mark_entry_changes', 'mark_verifications'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'raw_mark_id')) {
                DB::table($table)->where('raw_mark_id', $rawMarkId)->delete();
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $tableName = $connection->getTablePrefix() . $table;

        if ($driver === 'mysql') {
            return !empty(DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?', [$index]));
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
            return (bool) DB::table('pg_indexes')
                ->where('tablename', $table)
                ->where('indexname', $index)
                ->exists();
        }

        if ($driver === 'sqlsrv') {
            return (bool) DB::table('sys.indexes')->where('name', $index)->exists();
        }

        return false;
    }
};
