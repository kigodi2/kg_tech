<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds NECTA-aligned columns to support both SCHOOL and PRIVATE candidate types.
     * All changes are backward compatible with default values.
     */
    public function up(): void
    {
        // 1. Add candidate_type to candidates table
        // Distinguishes between SCHOOL (combination-based) and PRIVATE (subject-based) candidates
        Schema::table('candidates', function (Blueprint $table) {
            $table->enum('candidate_type', ['SCHOOL', 'PRIVATE'])
                ->default('SCHOOL')
                ->after('exam_type')
                ->comment('SCHOOL: combination-based, PRIVATE: subject-based registration');
        });

        // 2. Add optional FK to combinations (replaces string combination code)
        // Allows proper relational lookup of combination details
        // Kept as optional to not break existing string 'combination' field
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('combination_id')
                ->nullable()
                ->after('combination')
                ->constrained('combinations')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('FK to combinations table for relational queries');
        });

        // 3. Enhance candidate_subject_selections with principal subject tracking
        // Required for ACSEE: minimum 3 principal subjects
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            $table->boolean('is_principal')
                ->default(false)
                ->after('subject_id')
                ->comment('True if this is one of the principal (major) subjects');
        });

        // 4. Add source tracking to candidate_subject_selections
        // Tracks how the subject allocation occurred (manual selection, import, or template auto-attach)
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            $table->enum('source', ['manual', 'import', 'template'])
                ->default('template')
                ->after('is_principal')
                ->comment('How this subject was allocated: manual=user selected, import=CSV import, template=auto from combination');
        });

        // 5. Track who manually allocated subjects
        // Used for auditing and contact if clarification needed
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('source')
                ->constrained('users')
                ->onDelete('set null')
                ->onUpdate('cascade')
                ->comment('User who manually allocated this subject (if source=manual)');
        });

        // 6. Create additional index for is_principal queries
        // Performance optimization for "count principals" queries
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            $table->index(['candidate_id', 'is_principal', 'exam_type_id'], 
                'idx_principal_subjects');
        });

        // 7. Create index for source tracking
        // Performance optimization for filtering by allocation source
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            $table->index(['source', 'created_by'], 
                'idx_allocation_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_subject_selections', function (Blueprint $table) {
            if (Schema::hasColumn('candidate_subject_selections', 'source')) {
                if (DB::getDriverName() !== 'sqlite') {
                    try { $table->dropIndex('idx_allocation_source'); } catch (\Exception $e) {}
                    try { $table->dropIndex('idx_principal_subjects'); } catch (\Exception $e) {}
                    try { $table->dropForeign(['created_by']); } catch (\Exception $e) {}
                }
                $table->dropColumn(['created_by', 'source', 'is_principal']);
            }
        });

        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'combination_id')) {
                if (DB::getDriverName() !== 'sqlite') {
                    try { $table->dropForeign(['combination_id']); } catch (\Exception $e) {}
                }
                $table->dropColumn(['combination_id', 'candidate_type']);
            }
        });
    }
};
