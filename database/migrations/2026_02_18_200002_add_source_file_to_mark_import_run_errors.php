<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mark_import_run_errors', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_import_run_errors', 'source_file')) {
                $table->string('source_file', 500)->nullable()->after('row_number');
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'candidate_id')) {
                $table->unsignedBigInteger('candidate_id')->nullable()->after('index_number');
            }
            if (!Schema::hasColumn('mark_import_run_errors', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('candidate_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_import_run_errors', function (Blueprint $table) {
            $drop = [];
            foreach (['source_file', 'candidate_id', 'school_id'] as $col) {
                if (Schema::hasColumn('mark_import_run_errors', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
