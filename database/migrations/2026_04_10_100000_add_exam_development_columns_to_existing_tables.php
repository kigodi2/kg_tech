<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_types', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_types', 'level')) {
                $table->string('level', 50)->nullable()->after('name');
            }
        });

        DB::table('exam_types')
            ->whereNull('level')
            ->update(['level' => DB::raw('education_level')]);

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'short_name')) {
                $table->string('short_name', 100)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'short_name')) {
                $table->dropColumn('short_name');
            }
        });

        Schema::table('exam_types', function (Blueprint $table) {
            if (Schema::hasColumn('exam_types', 'level')) {
                $table->dropColumn('level');
            }
        });
    }
};
