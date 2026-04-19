<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('exam_types') || !Schema::hasTable('subjects')) {
            return;
        }

        $psleId = DB::table('exam_types')->where('code', 'PSLE')->value('id');
        if (!$psleId) {
            return;
        }

        DB::table('subjects')
            ->where('exam_type_id', $psleId)
            ->update([
                'max_marks' => 50,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('subject_marks')) {
            DB::table('subject_marks')
                ->where('exam_type_id', $psleId)
                ->update([
                    'max_marks' => 50,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('exam_types') || !Schema::hasTable('subjects')) {
            return;
        }

        $psleId = DB::table('exam_types')->where('code', 'PSLE')->value('id');
        if (!$psleId) {
            return;
        }

        DB::table('subjects')
            ->where('exam_type_id', $psleId)
            ->update([
                'max_marks' => 100,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('subject_marks')) {
            DB::table('subject_marks')
                ->where('exam_type_id', $psleId)
                ->update([
                    'max_marks' => 100,
                    'updated_at' => now(),
                ]);
        }
    }
};
