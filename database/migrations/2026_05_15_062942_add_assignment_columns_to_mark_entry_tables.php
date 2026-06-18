<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->foreignId('marking_centre_id')->nullable()->after('school_id')->constrained('marking_centres')->nullOnDelete();
            $table->foreignId('assignment_id')->nullable()->after('marking_centre_id')->constrained('mark_entry_assignments')->nullOnDelete();
        });

        Schema::table('raw_marks', function (Blueprint $table) {
            $table->foreignId('assignment_id')->nullable()->after('candidate_id')->constrained('mark_entry_assignments')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->dropForeign(['marking_centre_id']);
            $table->dropColumn('marking_centre_id');
            $table->dropForeign(['assignment_id']);
            $table->dropColumn('assignment_id');
        });

        Schema::table('raw_marks', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
            $table->dropColumn('assignment_id');
        });
    }
};
