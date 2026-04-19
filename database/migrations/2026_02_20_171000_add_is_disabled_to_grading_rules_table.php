<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('grading_rules', 'is_disabled')) {
                $table->boolean('is_disabled')->default(false)->after('sort_order');
                $table->index(['grading_profile_id', 'is_disabled'], 'gr_profile_disabled_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('grading_rules', function (Blueprint $table) {
            if (Schema::hasColumn('grading_rules', 'is_disabled')) {
                $table->dropIndex('gr_profile_disabled_idx');
                $table->dropColumn('is_disabled');
            }
        });
    }
};
