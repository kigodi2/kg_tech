<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Enforces explicit exam_year_id relationships in ACSEE registration tables.
     *
     * This migration:
     * 1. Adds exam_year_id foreign key to candidate_exam_registrations
     * 2. Adds exam_year_id foreign key to candidate_subject_selections
     * 3. Adds compound indexes for query optimization
     * 4. Backlills existing data (maps loose 'year' to exam_year_id)
     * 5. Sets NOT NULL constraint after backfill
     *
     * Key principle: exam_year_id is a first-class domain boundary.
     * All candidates and subjects MUST be explicitly tied to an exam year.
     */
    public function up(): void
    {
        // Check if we have exam_years table first
        $examYearsCount = 0;
        if (Schema::hasTable('exam_years')) {
            $examYearsCount = DB::table('exam_years')->count();
        }

        // 1. Add exam_year_id to candidate_exam_registrations (nullable first, then backfill, then NOT NULL)
        if (Schema::hasTable('candidate_exam_registrations') && !Schema::hasColumn('candidate_exam_registrations', 'exam_year_id')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->foreignId('exam_year_id')
                    ->nullable()
                    ->after('exam_type_id')
                    ->constrained('exam_years')
                    ->cascadeOnDelete()
                    ->comment('Reference to exam year - MANDATORY');
                
                // Add compound indexes for query optimization
                $table->index(['exam_year_id', 'candidate_id']);
                $table->index(['exam_year_id', 'exam_type_id']);
            });

            // 2. Backfill exam_year_id from 'year' column
            // Get all records that need backfilling
            $registrationsToUpdate = DB::table('candidate_exam_registrations')
                ->whereNull('exam_year_id')
                ->whereNotNull('year')
                ->get();

            if ($registrationsToUpdate->isNotEmpty() && $examYearsCount > 0) {
                // Get available exam years
                $examYears = DB::table('exam_years')->orderByDesc('id')->get();
                
                if ($examYears->isNotEmpty()) {
                    // For each registration, find matching year or use most recent
                    foreach ($registrationsToUpdate as $reg) {
                        // Try to find matching year_label
                        $matchingYear = $examYears->firstWhere('year_label', (string)$reg->year);
                        
                        if ($matchingYear) {
                            DB::table('candidate_exam_registrations')
                                ->where('id', $reg->id)
                                ->update(['exam_year_id' => $matchingYear->id]);
                        } else {
                            // Use most recent exam year if no match
                            DB::table('candidate_exam_registrations')
                                ->where('id', $reg->id)
                                ->update(['exam_year_id' => $examYears->first()->id]);
                        }
                    }
                }
            }

            // 3. Make NOT NULL after backfill (if we have exam_years table)
            if ($examYearsCount > 0) {
                Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                    $table->foreignId('exam_year_id')
                        ->nullable(false)
                        ->change()
                        ->comment('Reference to exam year - MANDATORY');
                });
            }
        }

        // 4. Add exam_year_id to candidate_subject_selections (nullable first, then backfill, then NOT NULL)
        if (Schema::hasTable('candidate_subject_selections') && !Schema::hasColumn('candidate_subject_selections', 'exam_year_id')) {
            Schema::table('candidate_subject_selections', function (Blueprint $table) {
                $table->foreignId('exam_year_id')
                    ->nullable()
                    ->after('exam_type_id')
                    ->constrained('exam_years')
                    ->cascadeOnDelete()
                    ->comment('Reference to exam year - MANDATORY');
                
                // Add compound indexes
                $table->index(['exam_year_id', 'candidate_id']);
                $table->index(['exam_year_id', 'subject_id']);
            });

            // 5. Backfill exam_year_id from 'year' column
            // Get all records that need backfilling
            $selectionsToUpdate = DB::table('candidate_subject_selections')
                ->whereNull('exam_year_id')
                ->whereNotNull('year')
                ->get();

            if ($selectionsToUpdate->isNotEmpty()) {
                // Get available exam years
                $examYears = DB::table('exam_years')->orderByDesc('id')->get();
                
                if ($examYears->isNotEmpty()) {
                    // For each selection, find matching year or use most recent
                    foreach ($selectionsToUpdate as $sel) {
                        // Try to find matching year_label
                        $matchingYear = $examYears->firstWhere('year_label', (string)$sel->year);
                        
                        if ($matchingYear) {
                            DB::table('candidate_subject_selections')
                                ->where('id', $sel->id)
                                ->update(['exam_year_id' => $matchingYear->id]);
                        } else {
                            // Use most recent exam year if no match
                            DB::table('candidate_subject_selections')
                                ->where('id', $sel->id)
                                ->update(['exam_year_id' => $examYears->first()->id]);
                        }
                    }
                }
            }

            // 6. Make NOT NULL after backfill
            if ($examYearsCount > 0) {
                Schema::table('candidate_subject_selections', function (Blueprint $table) {
                    $table->foreignId('exam_year_id')
                        ->nullable(false)
                        ->change()
                        ->comment('Reference to exam year - MANDATORY');
                });
            }
        }

        // 7. Add audit log table for year-based operations
        if (!Schema::hasTable('exam_year_audit_logs')) {
            Schema::create('exam_year_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('exam_year_id')->constrained('exam_years')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100)->comment('REGISTER, LOCK, PUBLISH, BACKFILL, etc.');
                $table->integer('affected_records')->default(0);
                $table->text('details')->nullable()->comment('JSON: { candidates: [...], subjects: [...] }');
                $table->timestamp('executed_at')->useCurrent();
                $table->timestamps();
                
                $table->index(['exam_year_id', 'action']);
                $table->index('executed_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove audit log table
        Schema::dropIfExists('exam_year_audit_logs');

        // Remove exam_year_id from candidate_subject_selections
        if (Schema::hasTable('candidate_subject_selections')) {
            Schema::table('candidate_subject_selections', function (Blueprint $table) {
                $table->dropForeign(['exam_year_id']);
                $table->dropIndex(['exam_year_id', 'candidate_id']);
                $table->dropIndex(['exam_year_id', 'subject_id']);
                $table->dropColumn('exam_year_id');
            });
        }

        // Remove exam_year_id from candidate_exam_registrations
        if (Schema::hasTable('candidate_exam_registrations')) {
            Schema::table('candidate_exam_registrations', function (Blueprint $table) {
                $table->dropForeign(['exam_year_id']);
                $table->dropIndex(['exam_year_id', 'candidate_id']);
                $table->dropIndex(['exam_year_id', 'exam_type_id']);
                $table->dropColumn('exam_year_id');
            });
        }
    }
};
