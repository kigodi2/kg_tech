<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_results', function (Blueprint $table) {
            if (!Schema::hasColumn('candidate_results', 'result_status')) {
                $table->string('result_status', 20)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidate_results', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_results', 'result_status')) {
                $table->dropColumn('result_status');
            }
        });
    }
};
