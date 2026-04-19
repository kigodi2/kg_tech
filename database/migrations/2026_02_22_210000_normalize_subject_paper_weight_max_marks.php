<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subject_paper_weights')) {
            return;
        }

        DB::table('subject_paper_weights')
            ->where('paper_code', 'paper_3')
            ->update(['max_mark' => 50.00]);

        DB::table('subject_paper_weights')
            ->whereIn('paper_code', ['paper_1', 'paper_2'])
            ->update(['max_mark' => 100.00]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('subject_paper_weights')) {
            return;
        }

        // Historical behavior before normalization used 100 for all papers.
        DB::table('subject_paper_weights')
            ->update(['max_mark' => 100.00]);
    }
};

