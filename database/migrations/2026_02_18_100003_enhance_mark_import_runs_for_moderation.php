<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mark_import_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('mark_import_runs', 'correlation_id')) {
                $table->string('correlation_id', 36)->nullable()->after('id');
                $table->index('correlation_id');
            }
            if (!Schema::hasColumn('mark_import_runs', 'scope_type')) {
                $table->string('scope_type', 30)->default('single_subject')->after('scope');
                // single_subject, school_zip, district_zip
            }
            if (!Schema::hasColumn('mark_import_runs', 'region_id')) {
                $table->unsignedBigInteger('region_id')->nullable()->after('exam_year_id');
            }
            if (!Schema::hasColumn('mark_import_runs', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable()->after('region_id');
            }
            if (!Schema::hasColumn('mark_import_runs', 'stored_path')) {
                $table->string('stored_path', 500)->nullable()->after('file_name');
            }
            if (!Schema::hasColumn('mark_import_runs', 'checksum')) {
                $table->string('checksum', 64)->nullable()->after('stored_path');
            }
            if (!Schema::hasColumn('mark_import_runs', 'original_file_name')) {
                $table->string('original_file_name', 500)->nullable()->after('file_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mark_import_runs', function (Blueprint $table) {
            $columns = ['correlation_id', 'scope_type', 'region_id', 'district_id', 'stored_path', 'checksum', 'original_file_name'];
            $drop = [];
            foreach ($columns as $col) {
                if (Schema::hasColumn('mark_import_runs', $col)) {
                    $drop[] = $col;
                }
            }
            if (!empty($drop)) {
                if (in_array('correlation_id', $drop)) {
                    $table->dropIndex(['correlation_id']);
                }
                $table->dropColumn($drop);
            }
        });
    }
};
