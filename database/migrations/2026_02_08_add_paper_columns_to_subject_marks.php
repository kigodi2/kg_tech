<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds paper columns to subject_marks table to store individual paper marks.
     * The marks_obtained field will contain the average for multi-paper subjects.
     */
    public function up(): void
    {
        Schema::table('subject_marks', function (Blueprint $table) {
            $table->decimal('paper_1', 5, 2)->nullable()->after('year')->comment('First paper or main written exam');
            $table->decimal('paper_2', 5, 2)->nullable()->after('paper_1')->comment('Second paper or practical exam');
            $table->decimal('paper_3', 5, 2)->nullable()->after('paper_2')->comment('Third paper or project');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_marks', function (Blueprint $table) {
            $table->dropColumn(['paper_1', 'paper_2', 'paper_3']);
        });
    }
};
