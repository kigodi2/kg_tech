<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $uniqueName = 'raw_marks_exam_year_candidate_subject_unique_guard';
    private string $legacyUniqueName = 'raw_marks_unique_exam_candidate_subject';

    public function up(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'exam_year_id')) {
                $table->unsignedBigInteger('exam_year_id')->nullable();
            }
        });

        if (!Schema::hasTable('raw_marks_duplicates_backup')) {
            Schema::create('raw_marks_duplicates_backup', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('raw_mark_id');
                $table->unsignedBigInteger('mark_import_batch_id')->nullable();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('exam_year_id')->nullable();
                $table->decimal('paper_1_marks', 6, 2)->nullable();
                $table->string('candidate_index_number')->nullable();
                $table->string('full_name')->nullable();
                $table->string('batch_status')->nullable();
                $table->string('batch_type')->nullable();
                $table->timestamp('raw_mark_updated_at')->nullable();
                $table->timestamps();
            });
        }

        $duplicates = DB::table('raw_marks')
            ->leftJoin('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->selectRaw('COALESCE(raw_marks.exam_year_id, mark_import_batches.exam_year_id) as effective_exam_year_id, raw_marks.candidate_id, raw_marks.subject_id, COUNT(*) as row_count')
            ->whereNotNull('raw_marks.candidate_id')
            ->whereNotNull('raw_marks.subject_id')
            ->groupByRaw('COALESCE(raw_marks.exam_year_id, mark_import_batches.exam_year_id), raw_marks.candidate_id, raw_marks.subject_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            if (!$duplicate->effective_exam_year_id) {
                continue;
            }

            $records = DB::table('raw_marks')
                ->leftJoin('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
                ->where('raw_marks.candidate_id', $duplicate->candidate_id)
                ->where('raw_marks.subject_id', $duplicate->subject_id)
                ->where(function ($query) use ($duplicate) {
                    $query->where('raw_marks.exam_year_id', $duplicate->effective_exam_year_id)
                        ->orWhere('mark_import_batches.exam_year_id', $duplicate->effective_exam_year_id);
                })
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

            DB::table('raw_marks')
                ->where('id', $keep->id)
                ->update(['exam_year_id' => $duplicate->effective_exam_year_id]);

            foreach ($records->slice(1) as $record) {
                DB::table('raw_marks_duplicates_backup')->insert([
                    'raw_mark_id' => $record->id,
                    'mark_import_batch_id' => $record->mark_import_batch_id,
                    'candidate_id' => $record->candidate_id,
                    'subject_id' => $record->subject_id,
                    'exam_year_id' => $duplicate->effective_exam_year_id,
                    'paper_1_marks' => $record->paper_1_marks,
                    'candidate_index_number' => $record->candidate_index_number,
                    'full_name' => $record->full_name,
                    'batch_status' => $record->batch_status,
                    'batch_type' => $record->batch_type,
                    'raw_mark_updated_at' => $record->updated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->deleteRawMarkDependents((int) $record->id);
                DB::table('raw_marks')->where('id', $record->id)->delete();
            }
        }

        DB::table('raw_marks')
            ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
            ->whereNull('raw_marks.exam_year_id')
            ->whereNotNull('mark_import_batches.exam_year_id')
            ->select('raw_marks.id', 'mark_import_batches.exam_year_id')
            ->orderBy('raw_marks.id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('raw_marks')
                        ->where('id', $row->id)
                        ->update(['exam_year_id' => $row->exam_year_id]);
                }
            }, 'raw_marks.id', 'id');

        if (
            !$this->indexExists('raw_marks', $this->legacyUniqueName)
            && !$this->indexExists('raw_marks', $this->uniqueName)
        ) {
            Schema::table('raw_marks', function (Blueprint $table) {
                $table->unique(['exam_year_id', 'candidate_id', 'subject_id'], $this->uniqueName);
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
        $prefix = $connection->getTablePrefix();
        $tableName = $prefix . $table;

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
            return (bool) DB::table('sys.indexes')
                ->where('name', $index)
                ->exists();
        }

        return false;
    }
};
