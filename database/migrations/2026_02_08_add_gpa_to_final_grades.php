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
        Schema::table('final_grades', function (Blueprint $table) {
            // Add if columns don't exist
            if (!Schema::hasColumn('final_grades', 'gpa')) {
                $table->float('gpa')->nullable();
            }
            if (!Schema::hasColumn('final_grades', 'division')) {
                $table->string('division')->nullable();
            }
            if (!Schema::hasColumn('final_grades', 'grading_breakdown')) {
                $table->longText('grading_breakdown')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('final_grades', function (Blueprint $table) {
            $table->dropColumn(['gpa', 'division', 'grading_breakdown']);
        });
    }
};
