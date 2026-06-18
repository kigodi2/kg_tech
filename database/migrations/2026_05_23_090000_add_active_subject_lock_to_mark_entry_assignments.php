<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mark_entry_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('mark_entry_assignments', 'active_lock')) {
                $table->unsignedTinyInteger('active_lock')->nullable()->after('status');
            }
        });

        DB::table('mark_entry_assignments')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->update(['active_lock' => 1]);

        DB::table('mark_entry_assignments')
            ->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhereNotNull('deleted_at');
            })
            ->update(['active_lock' => null]);

        Schema::table('mark_entry_assignments', function (Blueprint $table) {
            $table->unique(
                ['exam_year_id', 'exam_type_id', 'school_id', 'subject_id', 'assignment_type', 'active_lock'],
                'mea_active_school_subject_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mark_entry_assignments', function (Blueprint $table) {
            $table->dropUnique('mea_active_school_subject_unique');

            if (Schema::hasColumn('mark_entry_assignments', 'active_lock')) {
                $table->dropColumn('active_lock');
            }
        });
    }
};
