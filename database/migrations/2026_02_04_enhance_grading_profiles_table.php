<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Enhances grading_profiles table with additional fields
     * for grade mapping, GPA, and version control
     */
    public function up(): void
    {
        if (!Schema::hasColumn('grading_profiles', 'version')) {
            Schema::table('grading_profiles', function (Blueprint $table) {
                $table->integer('version')->default(1)->after('name');
            });
        }
        
        if (!Schema::hasColumn('grading_profiles', 'grade_boundaries')) {
            Schema::table('grading_profiles', function (Blueprint $table) {
                $table->json('grade_boundaries')->nullable()->after('version')
                    ->comment('Array of {grade, min, max} objects');
            });
        }
        
        if (!Schema::hasColumn('grading_profiles', 'gpa_mapping')) {
            Schema::table('grading_profiles', function (Blueprint $table) {
                $table->json('gpa_mapping')->nullable()->after('grade_boundaries')
                    ->comment('Grade to GPA mapping {A: 4.0, B: 3.0, ...}');
            });
        }
        
        if (!Schema::hasColumn('grading_profiles', 'competence_levels')) {
            Schema::table('grading_profiles', function (Blueprint $table) {
                $table->json('competence_levels')->nullable()->after('gpa_mapping')
                    ->comment('Grade to competence level {A: "Excellent", ...}');
            });
        }
        
        if (!Schema::hasColumn('grading_profiles', 'is_locked')) {
            Schema::table('grading_profiles', function (Blueprint $table) {
                $table->boolean('is_locked')->default(false)->after('is_active');
                $table->timestamp('locked_at')->nullable()->after('is_locked');
                $table->foreignId('locked_by_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('locked_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grading_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('grading_profiles', 'version')) {
                $table->dropColumn('version');
            }
            if (Schema::hasColumn('grading_profiles', 'grade_boundaries')) {
                $table->dropColumn('grade_boundaries');
            }
            if (Schema::hasColumn('grading_profiles', 'gpa_mapping')) {
                $table->dropColumn('gpa_mapping');
            }
            if (Schema::hasColumn('grading_profiles', 'competence_levels')) {
                $table->dropColumn('competence_levels');
            }
            if (Schema::hasColumn('grading_profiles', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
            if (Schema::hasColumn('grading_profiles', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
            if (Schema::hasColumn('grading_profiles', 'locked_by_id')) {
                $table->dropColumn('locked_by_id');
            }
        });
    }
};
