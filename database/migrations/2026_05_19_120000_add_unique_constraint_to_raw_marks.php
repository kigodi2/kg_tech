<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add exam_year_id column to raw_marks if not exists
        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'exam_year_id')) {
                $table->foreignId('exam_year_id')->nullable()->constrained('exam_years')->nullOnDelete();
            }
        });

        // 2. Populate exam_year_id for all existing rows
        DB::statement('
            UPDATE raw_marks
            SET exam_year_id = (
                SELECT exam_year_id 
                FROM mark_import_batches 
                WHERE mark_import_batches.id = raw_marks.mark_import_batch_id
            )
            WHERE exam_year_id IS NULL
        ');

        // 3. Create the backup table for duplicates
        if (!Schema::hasTable('raw_marks_duplicates_backup')) {
            Schema::create('raw_marks_duplicates_backup', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('raw_mark_id');
                $table->unsignedBigInteger('mark_import_batch_id');
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('exam_year_id')->nullable();
                $table->decimal('paper_1_marks', 6, 2)->nullable();
                $table->string('candidate_index_number');
                $table->string('full_name')->nullable();
                $table->string('batch_status')->nullable();
                $table->string('batch_type')->nullable();
                $table->timestamp('raw_mark_updated_at')->nullable();
                $table->timestamps();
            });
        }

        // 4. Safely clean duplicate entries
        $duplicates = DB::table('raw_marks')
            ->select('exam_year_id', 'candidate_id', 'subject_id')
            ->groupBy('exam_year_id', 'candidate_id', 'subject_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $records = DB::table('raw_marks')
                ->join('mark_import_batches', 'raw_marks.mark_import_batch_id', '=', 'mark_import_batches.id')
                ->where('raw_marks.exam_year_id', $dup->exam_year_id)
                ->where('raw_marks.candidate_id', $dup->candidate_id)
                ->where('raw_marks.subject_id', $dup->subject_id)
                ->select('raw_marks.*', 'mark_import_batches.status as batch_status', 'mark_import_batches.batch_type')
                ->get();

            // Sort by status priority: locked > approved > submitted > validated > draft
            $sorted = $records->sortBy(function ($rec) {
                $priority = match ($rec->batch_status) {
                    'locked' => 1,
                    'approved' => 2,
                    'submitted' => 3,
                    'validated' => 4,
                    'draft' => 5,
                    default => 6,
                };
                return sprintf('%d_%s', $priority, 9999999999 - strtotime($rec->updated_at));
            })->values();

            $keep = $sorted[0];
            $superseded = $sorted->slice(1);

            foreach ($superseded as $row) {
                // Insert into backup table
                DB::table('raw_marks_duplicates_backup')->insert([
                    'raw_mark_id' => $row->id,
                    'mark_import_batch_id' => $row->mark_import_batch_id,
                    'candidate_id' => $row->candidate_id,
                    'subject_id' => $row->subject_id,
                    'exam_year_id' => $row->exam_year_id,
                    'paper_1_marks' => $row->paper_1_marks,
                    'candidate_index_number' => $row->candidate_index_number,
                    'full_name' => $row->full_name,
                    'batch_status' => $row->batch_status,
                    'batch_type' => $row->batch_type,
                    'raw_mark_updated_at' => $row->updated_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Delete related dependent tables to avoid constraint violations
                DB::table('mark_entry_validations')->where('raw_mark_id', $row->id)->delete();
                if (Schema::hasTable('mark_entry_outliers')) {
                    DB::table('mark_entry_outliers')->where('raw_mark_id', $row->id)->delete();
                }
                if (Schema::hasTable('mark_outlier_resolutions')) {
                    DB::table('mark_outlier_resolutions')->where('raw_mark_id', $row->id)->delete();
                }
                if (Schema::hasTable('mark_entry_changes')) {
                    DB::table('mark_entry_changes')->where('raw_mark_id', $row->id)->delete();
                }

                // Delete from raw_marks
                DB::table('raw_marks')->where('id', $row->id)->delete();
            }
        }

        // 5. Add unique constraint to raw_marks table
        Schema::table('raw_marks', function (Blueprint $table) {
            $table->unique(
                ['exam_year_id', 'candidate_id', 'subject_id'],
                'raw_marks_unique_exam_candidate_subject'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            $table->dropUnique('raw_marks_unique_exam_candidate_subject');
        });

        Schema::dropIfExists('raw_marks_duplicates_backup');

        Schema::table('raw_marks', function (Blueprint $table) {
            if (Schema::hasColumn('raw_marks', 'exam_year_id')) {
                $table->dropColumn('exam_year_id');
            }
        });
    }
};
