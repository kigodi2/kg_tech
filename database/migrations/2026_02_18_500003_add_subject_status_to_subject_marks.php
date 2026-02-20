<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('subject_marks', 'subject_status')) {
                $table->string('subject_status', 10)->nullable()->after('grade'); // X, ABS, INC
            }
        });
    }

    public function down(): void
    {
        Schema::table('subject_marks', function (Blueprint $table) {
            if (Schema::hasColumn('subject_marks', 'subject_status')) {
                $table->dropColumn('subject_status');
            }
        });
    }
};
