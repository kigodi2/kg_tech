<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'candidate_id')) {
                $table->string('candidate_id', 50)->unique()->after('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'candidate_id')) {
                $table->dropColumn('candidate_id');
            }
        });
    }
};
