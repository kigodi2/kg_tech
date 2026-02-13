<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds total_marks and total_points columns to candidate_exam_registrations
     * so that grades, GPA, and division can be calculated and persisted.
     */
    public function up(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            $table->decimal('total_marks', 7, 2)->nullable()->after('gpa')->comment('Sum of all marks in all subjects');
            $table->integer('total_points')->nullable()->after('total_marks')->comment('Sum of grade points (excluding GENERAL STUDIES and BASIC APPLIED MATHEMATICS)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            $table->dropColumn(['total_marks', 'total_points']);
        });
    }
};
