<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $practical50Codes = ['031', '032', '033', '036'];
    private array $practical100Codes = ['016', '017', '051', '052'];

    public function up(): void
    {
        if (!Schema::hasTable('subjects') || !Schema::hasTable('exam_types')) {
            return;
        }

        $examTypeId = DB::table('exam_types')->where('code', 'CSEE')->value('id');
        if (!$examTypeId) {
            return;
        }

        $allCodes = array_merge($this->practical50Codes, $this->practical100Codes);
        $subjectIdsByCode = DB::table('subjects')
            ->where('exam_type_id', $examTypeId)
            ->whereIn('code', $allCodes)
            ->pluck('id', 'code');

        if ($subjectIdsByCode->isEmpty()) {
            return;
        }

        DB::table('subjects')
            ->where('exam_type_id', $examTypeId)
            ->whereIn('code', $allCodes)
            ->update([
                'written_papers' => 1,
                'has_practical' => true,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('subject_paper_weights')) {
            foreach ($subjectIdsByCode as $code => $subjectId) {
                $practicalMax = in_array($code, $this->practical50Codes, true) ? 50.0 : 100.0;

                $this->upsertWeight((int) $subjectId, 'paper_1', 1.0, 100.0);
                $this->upsertWeight((int) $subjectId, 'paper_3', 1.0, $practicalMax);

                DB::table('subject_paper_weights')
                    ->where('subject_id', $subjectId)
                    ->where('paper_code', 'paper_2')
                    ->update([
                        'is_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        }

        $subjectIds = $subjectIdsByCode->values()->all();

        if (Schema::hasTable('subject_marks') && !empty($subjectIds)) {
            DB::table('subject_marks')
                ->whereIn('subject_id', $subjectIds)
                ->whereNull('paper_3')
                ->whereNotNull('paper_2')
                ->update([
                    'paper_3' => DB::raw('paper_2'),
                    'paper_2' => null,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('raw_marks') && !empty($subjectIds)) {
            DB::table('raw_marks')
                ->whereIn('subject_id', $subjectIds)
                ->whereNull('paper_3_marks')
                ->whereNotNull('paper_2_marks')
                ->update([
                    'paper_3_marks' => DB::raw('paper_2_marks'),
                    'practical_marks' => DB::raw('COALESCE(practical_marks, paper_2_marks)'),
                    'paper_2_marks' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subjects') || !Schema::hasTable('exam_types')) {
            return;
        }

        $examTypeId = DB::table('exam_types')->where('code', 'CSEE')->value('id');
        if (!$examTypeId) {
            return;
        }

        $allCodes = array_merge($this->practical50Codes, $this->practical100Codes);
        $subjectIds = DB::table('subjects')
            ->where('exam_type_id', $examTypeId)
            ->whereIn('code', $allCodes)
            ->pluck('id')
            ->all();

        DB::table('subjects')
            ->where('exam_type_id', $examTypeId)
            ->whereIn('code', $allCodes)
            ->update([
                'written_papers' => 2,
                'has_practical' => false,
                'updated_at' => now(),
            ]);

        if (Schema::hasTable('subject_paper_weights') && !empty($subjectIds)) {
            DB::table('subject_paper_weights')
                ->whereIn('subject_id', $subjectIds)
                ->where('paper_code', 'paper_2')
                ->update([
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    private function upsertWeight(int $subjectId, string $paperCode, float $weight, float $maxMark): void
    {
        $existingId = DB::table('subject_paper_weights')
            ->where('subject_id', $subjectId)
            ->where('paper_code', $paperCode)
            ->value('id');

        $payload = [
            'weight' => $weight,
            'max_mark' => $maxMark,
            'is_required' => true,
            'is_active' => true,
            'updated_at' => now(),
        ];

        if ($existingId) {
            DB::table('subject_paper_weights')
                ->where('id', $existingId)
                ->update($payload);

            return;
        }

        DB::table('subject_paper_weights')->insert(array_merge($payload, [
            'subject_id' => $subjectId,
            'paper_code' => $paperCode,
            'created_at' => now(),
        ]));
    }
};
