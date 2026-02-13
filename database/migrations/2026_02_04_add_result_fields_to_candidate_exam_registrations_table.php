<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds result tracking fields to candidate_exam_registrations for:
     * - Grade (A, B, C, D, F, S, ABS)
     * - GPA (0.0 - 4.0)
     * - Division (I, II, III, IV, 0)
     * - Result status (draft, final, published)
     */
    public function up(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            
            // Grade (A, B, C, D, F, S=Special, ABS=Absent)
            $table->after('year', function (Blueprint $table) {
                $table->char('grade', 3)->nullable()->comment('A, B, C, D, F, S (Special), ABS (Absent)');
            });
            
            // GPA (0.0 to 4.0)
            $table->decimal('gpa', 3, 2)->nullable()->comment('Grade Point Average 0.0 - 4.0');
            
            // Division (I=1, II=2, III=3, IV=4, 0=Fail)
            $table->tinyInteger('division')->nullable()->comment('Division: 1 (First), 2 (Second), 3 (Third), 4 (Fourth), 0 (Fail)');
            
            // Result status (draft, final, published)
            $table->enum('result_status', ['draft', 'final', 'published'])->default('draft')->index();
            
            // When result was published
            $table->timestamp('published_at')->nullable();
            
            // Indexes for filtering
            $table->index('grade');
            $table->index('division');
            $table->index(['result_status', 'exam_year_id']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_exam_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'grade',
                'gpa',
                'division',
                'result_status',
                'published_at',
            ]);
            
            $table->dropIndex(['result_status', 'exam_year_id']);
        });
    }
};
