<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('grading_profiles', 'exam_year_id')) {
                $table->unsignedBigInteger('exam_year_id')
                    ->nullable()
                    ->after('exam_type_id');
                $table->index(['exam_type_id', 'exam_year_id', 'is_active'], 'gp_exam_type_year_active_idx');
                $table->index('exam_year_id', 'gp_exam_year_idx');
            }
        });

        Schema::table('grading_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('grading_rules', 'points')) {
                $table->unsignedTinyInteger('points')->nullable()->after('grade_name');
            }
            if (!Schema::hasColumn('grading_rules', 'is_principal')) {
                $table->boolean('is_principal')->default(false)->after('points');
            }
            if (!Schema::hasColumn('grading_rules', 'is_subsidiary')) {
                $table->boolean('is_subsidiary')->default(false)->after('is_principal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grading_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('grading_profiles', 'exam_year_id')) {
                $table->dropIndex('gp_exam_type_year_active_idx');
                $table->dropIndex('gp_exam_year_idx');
                $table->dropColumn('exam_year_id');
            }
        });

        Schema::table('grading_rules', function (Blueprint $table) {
            if (Schema::hasColumn('grading_rules', 'is_subsidiary')) {
                $table->dropColumn('is_subsidiary');
            }
            if (Schema::hasColumn('grading_rules', 'is_principal')) {
                $table->dropColumn('is_principal');
            }
            if (Schema::hasColumn('grading_rules', 'points')) {
                $table->dropColumn('points');
            }
        });
    }
};
