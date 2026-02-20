<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_marks', 'subject_status')) {
                $table->string('subject_status', 10)->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('raw_marks', 'status_reason')) {
                $table->string('status_reason')->nullable()->after('subject_status');
            }
            if (!Schema::hasColumn('raw_marks', 'has_warnings')) {
                $table->boolean('has_warnings')->default(false)->after('has_errors');
            }
            if (!Schema::hasColumn('raw_marks', 'warning_messages')) {
                $table->text('warning_messages')->nullable()->after('has_warnings');
            }
        });
    }

    public function down(): void
    {
        Schema::table('raw_marks', function (Blueprint $table) {
            $cols = ['subject_status', 'status_reason', 'has_warnings', 'warning_messages'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('raw_marks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
