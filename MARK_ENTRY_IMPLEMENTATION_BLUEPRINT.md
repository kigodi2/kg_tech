# MARK ENTRY LIFECYCLE: DETAILED IMPLEMENTATION BLUEPRINT

**Phase 1 Implementation Code**  
**Status**: Ready for Development  
**Target**: 2 weeks  

---

## TABLE OF CONTENTS

1. Route Structure
2. Database Migrations
3. Service Layer
4. Controller Examples
5. Policy & Authorization
6. Frontend Integration
7. Testing Strategy

---

## 1. ROUTE STRUCTURE

### File: `routes/mark-entry.php` (NEW)

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkEntry\{
    Entry\MarkEntryUploadController,
    Entry\MarkEntryApiController,
    Validation\MarkValidationController,
    Moderation\MarkEntryModerationController,
    Submission\MarkEntrySubmissionController,
    Reporting\MarkEntryReportController,
    Audit\MarkEntryMonitoringController,
    Admin\MarkEntryAdminController,
};

Route::middleware(['auth'])->prefix('mark-entry')->group(function () {

    // ============================================================
    // ACSEE MARK ENTRY - 7 LIFECYCLE PHASES
    // ============================================================
    Route::prefix('acsee')->name('mark-entry.acsee.')->group(function () {

        // ─────────────────────────────────────────────────
        // PHASE 1: ENTRY & VALIDATION
        // ─────────────────────────────────────────────────
        Route::prefix('entry-validation')->name('entry-validation.')->group(function () {
            
            // Main entry page
            Route::get('/', [MarkEntryUploadController::class, 'index'])
                ->name('index');

            // Template download
            Route::get('download-template', [MarkEntryUploadController::class, 'downloadTemplate'])
                ->name('download-template')
                ->middleware('can:mark-entry.upload');

            // Single subject upload
            Route::post('upload', [MarkEntryUploadController::class, 'upload'])
                ->name('upload')
                ->middleware('can:mark-entry.upload');

            // School bulk upload
            Route::post('upload-school-bulk', [MarkEntryUploadController::class, 'uploadSchoolBulk'])
                ->name('upload-school-bulk')
                ->middleware('can:mark-entry.upload');

            // District bulk upload
            Route::post('upload-district-bulk', [MarkEntryUploadController::class, 'uploadDistrictBulk'])
                ->name('upload-district-bulk')
                ->middleware('can:mark-entry.upload');

            // View upload status
            Route::get('upload-status', [MarkEntryUploadController::class, 'uploadStatus'])
                ->name('upload-status');

            // Validation report
            Route::get('validation-report/{batchId}', [MarkValidationController::class, 'report'])
                ->name('validation-report');

            // Download error CSV
            Route::get('error-csv/{batchId}', [MarkValidationController::class, 'downloadErrorCsv'])
                ->name('error-csv');

            // Error batches list
            Route::get('error-batches', [MarkValidationController::class, 'errorBatches'])
                ->name('error-batches');

            // Batch details
            Route::get('batch/{batchId}', [MarkEntryUploadController::class, 'batchDetails'])
                ->name('batch-details');
        });

        // ─────────────────────────────────────────────────
        // PHASE 2: MODERATION & REVIEW
        // ─────────────────────────────────────────────────
        Route::prefix('moderation')->name('moderation.')
            ->middleware('can:mark-entry.moderate')
            ->group(function () {
                
                // Moderation dashboard
                Route::get('/', [MarkEntryModerationController::class, 'dashboard'])
                    ->name('dashboard');

                // Review specific batch
                Route::get('batch/{batchId}', [MarkEntryModerationController::class, 'reviewBatch'])
                    ->name('review-batch');

                // Scoresheet preview
                Route::get('batch/{batchId}/scoresheet', [MarkEntryModerationController::class, 'scoresheetPreview'])
                    ->name('scoresheet-preview');

                // Flag issue on candidate
                Route::post('batch/{batchId}/flag-issue', [MarkEntryModerationController::class, 'flagIssue'])
                    ->name('flag-issue');

                // Approve batch
                Route::post('batch/{batchId}/approve', [MarkEntryModerationController::class, 'approveBatch'])
                    ->name('approve');

                // Reject batch
                Route::post('batch/{batchId}/reject', [MarkEntryModerationController::class, 'rejectBatch'])
                    ->name('reject');

                // View feedback log
                Route::get('batch/{batchId}/feedback', [MarkEntryModerationController::class, 'feedbackLog'])
                    ->name('feedback-log');

                // Edit marks (if approved, before lock)
                Route::put('batch/{batchId}/mark/{markId}', [MarkEntryModerationController::class, 'editMark'])
                    ->name('edit-mark');
            });

        // ─────────────────────────────────────────────────
        // PHASE 3: SUBMISSION & LOCKING
        // ─────────────────────────────────────────────────
        Route::prefix('submission')->name('submission.')
            ->middleware('can:mark-entry.lock')
            ->group(function () {
                
                // Lock dashboard
                Route::get('/', [MarkEntrySubmissionController::class, 'dashboard'])
                    ->name('dashboard');

                // Lock status view
                Route::get('lock-status', [MarkEntrySubmissionController::class, 'lockStatus'])
                    ->name('lock-status');

                // Lock batch
                Route::post('lock/{batchId}', [MarkEntrySubmissionController::class, 'lockBatch'])
                    ->name('lock');

                // Submit batch to authority
                Route::post('submit/{batchId}', [MarkEntrySubmissionController::class, 'submitBatch'])
                    ->name('submit');

                // Submission history
                Route::get('submission-history', [MarkEntrySubmissionController::class, 'submissionHistory'])
                    ->name('history');

                // Unlock (admin only)
                Route::post('unlock/{batchId}', [MarkEntrySubmissionController::class, 'unlockBatch'])
                    ->name('unlock')
                    ->middleware('can:mark-entry.unlock');
            });

        // ─────────────────────────────────────────────────
        // PHASE 4 & 5: REPORTS & EXPORTS
        // ─────────────────────────────────────────────────
        Route::prefix('reports')->name('reports.')->group(function () {
            
            // Scoresheet PDF
            Route::get('scoresheet/{batchId}', [MarkEntryReportController::class, 'scoresheet'])
                ->name('scoresheet');

            // Bulk scoresheet export
            Route::get('scoresheet-bulk', [MarkEntryReportController::class, 'scoresheetBulk'])
                ->name('scoresheet-bulk');

            // CSV export
            Route::get('csv-export', [MarkEntryReportController::class, 'csvExport'])
                ->name('csv-export');

            // Daily marks entry report
            Route::get('daily-entry-report', [MarkEntryReportController::class, 'dailyEntryReport'])
                ->name('daily-entry-report');

            // Extremity analysis
            Route::get('extremity-analysis', [MarkEntryReportController::class, 'extremityAnalysis'])
                ->name('extremity-analysis');
        });

        // ─────────────────────────────────────────────────
        // PHASE 6 & 7: MONITORING & AUDIT
        // ─────────────────────────────────────────────────
        Route::prefix('monitoring')->name('monitoring.')
            ->middleware('can:mark-entry.audit')
            ->group(function () {
                
                // Lifecycle dashboard
                Route::get('/', [MarkEntryMonitoringController::class, 'lifecycleDashboard'])
                    ->name('dashboard');

                // Batch history/timeline
                Route::get('batch/{batchId}/history', [MarkEntryMonitoringController::class, 'batchHistory'])
                    ->name('batch-history');

                // Change log
                Route::get('change-log', [MarkEntryMonitoringController::class, 'changeLog'])
                    ->name('change-log');

                // User activity log
                Route::get('user-activity', [MarkEntryMonitoringController::class, 'userActivity'])
                    ->name('user-activity');

                // Audit trail
                Route::get('audit-trail', [MarkEntryMonitoringController::class, 'auditTrail'])
                    ->name('audit-trail');

                // Export audit report
                Route::get('audit-report/export', [MarkEntryMonitoringController::class, 'exportAuditReport'])
                    ->name('audit-report.export');
            });

        // ─────────────────────────────────────────────────
        // ADMINISTRATION
        // ─────────────────────────────────────────────────
        Route::prefix('admin')->name('admin.')
            ->middleware('can:mark-entry.admin')
            ->group(function () {
                
                // Configuration
                Route::get('configuration', [MarkEntryAdminController::class, 'configuration'])
                    ->name('configuration');

                Route::put('configuration', [MarkEntryAdminController::class, 'updateConfiguration'])
                    ->name('configuration.update');

                // Batch management
                Route::get('batches', [MarkEntryAdminController::class, 'manageBatches'])
                    ->name('batches');

                Route::post('batch/{batchId}/delete', [MarkEntryAdminController::class, 'deleteBatch'])
                    ->name('batch.delete');

                Route::post('batch/{batchId}/archive', [MarkEntryAdminController::class, 'archiveBatch'])
                    ->name('batch.archive');

                // Access control
                Route::get('access-control', [MarkEntryAdminController::class, 'accessControl'])
                    ->name('access-control');
            });

        // ─────────────────────────────────────────────────
        // SHARED API ENDPOINTS (Used by all phases)
        // ─────────────────────────────────────────────────
        Route::prefix('api')->name('api.')->group(function () {
            
            Route::get('regions', [MarkEntryApiController::class, 'regions'])
                ->name('regions');

            Route::get('districts', [MarkEntryApiController::class, 'districts'])
                ->name('districts');

            Route::get('schools', [MarkEntryApiController::class, 'schools'])
                ->name('schools');

            Route::get('schools-by-year', [MarkEntryApiController::class, 'schoolsByYear'])
                ->name('schools-by-year');

            Route::get('districts-by-year', [MarkEntryApiController::class, 'districtsByYear'])
                ->name('districts-by-year');

            Route::get('subjects', [MarkEntryApiController::class, 'subjects'])
                ->name('subjects');

            Route::get('subjects-by-school', [MarkEntryApiController::class, 'subjectsBySchool'])
                ->name('subjects-by-school');

            Route::get('exam-years', [MarkEntryApiController::class, 'examYears'])
                ->name('exam-years');

            Route::get('exam-years/active', [MarkEntryApiController::class, 'activeExamYear'])
                ->name('exam-years.active');
        });
    });

    // ============================================================
    // CSEE MARK ENTRY (Similar structure)
    // ============================================================
    Route::prefix('csee')->name('mark-entry.csee.')->group(function () {
        // Mirror structure above, different controllers
    });

    // ============================================================
    // INTERNAL EXAMS (Different structure)
    // ============================================================
    Route::prefix('internal')->name('mark-entry.internal.')->group(function () {
        // Different lifecycle per school config
    });
});
```

### Integration into `routes/web.php`

```php
// At the end of the auth middleware group, before the closing brace
require base_path('routes/mark-entry.php');
```

---

## 2. DATABASE MIGRATIONS

### Migration 1: Create Lifecycle State Tracking

File: `database/migrations/2026_02_13_create_mark_entry_lifecycle_states_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mark_entry_lifecycle_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')
                ->onDelete('cascade');

            // Current state in lifecycle
            $table->enum('current_state', [
                'draft',
                'validating',
                'validated',
                'validation_failed',
                'awaiting_moderation',
                'approved',
                'rejected',
                'processing',
                'processed',
                'submitted',
                'archived'
            ])->default('draft');

            // Previous state (for undo trail)
            $table->string('previous_state')->nullable();

            // Who made the transition
            $table->foreignId('transitioned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // When the transition occurred
            $table->timestamp('transitioned_at')->nullable();

            // Why the transition (e.g., "Approved by HOD", "Locked for submission")
            $table->text('transition_reason')->nullable();

            // JSON history of all transitions
            $table->json('history')->nullable();

            $table->timestamps();

            // Indexes for fast queries
            $table->index('mark_import_batch_id');
            $table->index('current_state');
            $table->index('transitioned_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_entry_lifecycle_states');
    }
};
```

### Migration 2: Create Moderation Reviews Table

File: `database/migrations/2026_02_13_create_mark_moderation_reviews_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mark_moderation_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')
                ->onDelete('cascade');

            // Who reviewed
            $table->foreignId('reviewer_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Type of review (role-based)
            $table->enum('review_type', [
                'school_hod',           // Head of Department
                'district_supervisor',  // District supervisor
                'admin'                 // System admin
            ]);

            // Review status
            $table->enum('status', [
                'pending',      // Awaiting review
                'approved',     // Approved
                'rejected',     // Rejected with feedback
                'conditional'   // Approved with conditions
            ])->default('pending');

            // Feedback text
            $table->longText('feedback')->nullable();

            // Flagged issues (JSON array)
            // [
            //   {
            //     "candidate_id": 123,
            //     "field": "paper_1_marks",
            //     "severity": "high|medium|low",
            //     "description": "..."
            //   }
            // ]
            $table->json('flagged_issues')->nullable();

            // When reviewed
            $table->timestamp('reviewed_at')->nullable();

            // Digital signature (phase 2)
            $table->string('signature')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('mark_import_batch_id');
            $table->index('reviewer_id');
            $table->index('status');
            $table->index('reviewed_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_moderation_reviews');
    }
};
```

### Migration 3: Create Change Tracking Table

File: `database/migrations/2026_02_13_create_mark_entry_changes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mark_entry_changes', function (Blueprint $table) {
            $table->id();
            
            // What was changed
            $table->foreignId('raw_mark_id')
                ->constrained('raw_marks')
                ->onDelete('cascade');

            // Who changed it
            $table->foreignId('changed_by')
                ->constrained('users')
                ->onDelete('cascade');

            // Type of change
            $table->enum('change_type', [
                'upload',               // Initial upload
                'edit',                 // Manual edit
                'validation_fix',       // Fix validation error
                'admin_correction'      // Admin corrected
            ]);

            // Which field changed
            $table->string('field_name');  // e.g., 'paper_1_marks'

            // Old and new values
            $table->decimal('old_value', 6, 2)->nullable();
            $table->decimal('new_value', 6, 2)->nullable();

            // Reason for change
            $table->text('reason')->nullable();

            // When changed
            $table->timestamp('changed_at')->nullable();

            // IP address (for audit)
            $table->string('ip_address')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('raw_mark_id');
            $table->index('changed_by');
            $table->index('changed_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_entry_changes');
    }
};
```

### Migration 4: Create Batch Approvals Table

File: `database/migrations/2026_02_13_create_mark_batch_approvals_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mark_batch_approvals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mark_import_batch_id')
                ->constrained('mark_import_batches')
                ->onDelete('cascade');

            // Approval level
            $table->enum('approval_level', [
                'validation',    // System validation passed
                'moderation',    // Moderation approved
                'submission',    // Submitted to authority
            ]);

            // Who approved
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // When approved
            $table->timestamp('approved_at')->nullable();

            // Digital signature (phase 2)
            $table->text('signature')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['mark_import_batch_id', 'approval_level']);
            $table->index('approved_by');
            $table->index('approved_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('mark_batch_approvals');
    }
};
```

### Migration 5: Enhance mark_import_batches Table

File: `database/migrations/2026_02_13_enhance_mark_import_batches_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            // State machine tracking
            $table->enum('lifecycle_state', [
                'draft',
                'validating',
                'validated',
                'validation_failed',
                'awaiting_moderation',
                'approved',
                'rejected',
                'processing',
                'processed',
                'submitted',
                'archived'
            ])->default('draft')->after('status');

            // Full lifecycle history (JSON)
            $table->json('lifecycle_history')->nullable()->after('lifecycle_state');

            // Rejection reason
            $table->longText('rejection_reason')->nullable()->after('notes');

            // Does this batch need resubmission?
            $table->boolean('requires_resubmission')->default(false);

            // If resubmitted, link to original batch
            $table->foreignId('resubmitted_from_batch_id')
                ->nullable()
                ->constrained('mark_import_batches')
                ->nullOnDelete();

            // Latest moderation review
            $table->foreignId('latest_review_id')
                ->nullable()
                ->constrained('mark_moderation_reviews')
                ->nullOnDelete();

            // Batch hash for integrity verification
            $table->string('batch_hash')->nullable()->unique();
        });
    }

    public function down(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_state',
                'lifecycle_history',
                'rejection_reason',
                'requires_resubmission',
                'resubmitted_from_batch_id',
                'latest_review_id',
                'batch_hash'
            ]);
        });
    }
};
```

---

## 3. SERVICE LAYER

### LifecycleStateService.php

File: `app/Services/MarkEntry/LifecycleStateService.php`

```php
<?php

namespace App\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LifecycleStateService {
    
    // Valid transitions for state machine
    private const VALID_TRANSITIONS = [
        'draft' => ['validating', 'rejected'],
        'validating' => ['validated', 'validation_failed'],
        'validated' => ['awaiting_moderation', 'draft'],
        'validation_failed' => ['draft'],
        'awaiting_moderation' => ['approved', 'rejected'],
        'approved' => ['submitted', 'draft'],
        'rejected' => ['draft', 'deleted'],
        'processing' => ['processed'],
        'processed' => ['submitted'],
        'submitted' => ['archived'],
        'archived' => [],
    ];

    /**
     * Transition batch to new state
     * 
     * @param MarkImportBatch $batch
     * @param string $newState
     * @param User $user
     * @param string|null $reason
     * @return MarkEntryLifecycleState
     * @throws \Exception
     */
    public function transition(
        MarkImportBatch $batch,
        string $newState,
        User $user,
        ?string $reason = null
    ): MarkEntryLifecycleState {
        
        return DB::transaction(function () use ($batch, $newState, $user, $reason) {
            
            // Validate transition is allowed
            $currentState = $batch->lifecycle_state ?? 'draft';
            if (!$this->isValidTransition($currentState, $newState)) {
                throw new \Exception(
                    "Cannot transition from '{$currentState}' to '{$newState}'"
                );
            }

            // Create new lifecycle state record
            $lifecycle = MarkEntryLifecycleState::create([
                'mark_import_batch_id' => $batch->id,
                'current_state' => $newState,
                'previous_state' => $currentState,
                'transitioned_by' => $user->id,
                'transitioned_at' => now(),
                'transition_reason' => $reason ?? $this->getDefaultReason($newState),
            ]);

            // Update batch with new state
            $batch->update([
                'lifecycle_state' => $newState,
                'lifecycle_history' => $this->buildHistory($batch, $lifecycle),
            ]);

            // Log to audit trail
            \Log::info("Batch {$batch->id} transitioned: {$currentState} → {$newState}", [
                'user_id' => $user->id,
                'reason' => $reason,
            ]);

            return $lifecycle;
        });
    }

    /**
     * Check if transition is valid
     */
    private function isValidTransition(string $from, string $to): bool {
        $allowed = self::VALID_TRANSITIONS[$from] ?? [];
        return in_array($to, $allowed);
    }

    /**
     * Get default reason for state transition
     */
    private function getDefaultReason(string $state): string {
        return match ($state) {
            'validating' => 'Validation in progress',
            'validated' => 'All validation rules passed',
            'validation_failed' => 'Validation failed, see error report',
            'awaiting_moderation' => 'Waiting for moderator review',
            'approved' => 'Approved by moderator',
            'rejected' => 'Rejected, awaiting resubmission',
            'submitted' => 'Submitted to exam authority',
            'archived' => 'Archived for historical records',
            default => ucwords(str_replace('_', ' ', $state)),
        };
    }

    /**
     * Build complete history array
     */
    private function buildHistory(MarkImportBatch $batch, MarkEntryLifecycleState $lifecycle): array {
        $current = json_decode($batch->lifecycle_history ?? '[]', true);
        
        $current[] = [
            'from' => $lifecycle->previous_state,
            'to' => $lifecycle->current_state,
            'by' => auth()->user()?->name ?? 'System',
            'at' => $lifecycle->transitioned_at->toIso8601String(),
            'reason' => $lifecycle->transition_reason,
        ];

        return $current;
    }

    /**
     * Get current state
     */
    public function getCurrentState(MarkImportBatch $batch): string {
        return $batch->lifecycle_state ?? 'draft';
    }

    /**
     * Check if batch can transition to state
     */
    public function canTransition(MarkImportBatch $batch, string $targetState): bool {
        $current = $this->getCurrentState($batch);
        return $this->isValidTransition($current, $targetState);
    }

    /**
     * Get available next states
     */
    public function getAvailableTransitions(MarkImportBatch $batch): array {
        $current = $this->getCurrentState($batch);
        return self::VALID_TRANSITIONS[$current] ?? [];
    }
}
```

### MarkModerationService.php

File: `app/Services/MarkEntry/MarkModerationService.php`

```php
<?php

namespace App\Services\MarkEntry;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkModerationService {

    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService) {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Create moderation review
     */
    public function createReview(
        MarkImportBatch $batch,
        User $reviewer,
        string $reviewType
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $reviewer, $reviewType) {
            
            // Transition to awaiting_moderation if not already
            if ($batch->lifecycle_state === 'validated') {
                $this->lifecycleService->transition(
                    $batch,
                    'awaiting_moderation',
                    auth()->user(),
                    'Sent to moderator for review'
                );
            }

            return MarkModerationReview::create([
                'mark_import_batch_id' => $batch->id,
                'reviewer_id' => $reviewer->id,
                'review_type' => $reviewType,
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Approve batch
     */
    public function approveBatch(
        MarkImportBatch $batch,
        User $approver,
        ?string $feedback = null,
        ?array $conditions = null
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $approver, $feedback, $conditions) {
            
            // Update review status
            $review = $batch->latestReview();
            $review->update([
                'status' => $conditions ? 'conditional' : 'approved',
                'feedback' => $feedback,
                'reviewed_at' => now(),
                'reviewer_id' => $approver->id,
            ]);

            // Transition batch to approved
            $this->lifecycleService->transition(
                $batch,
                'approved',
                $approver,
                $conditions 
                    ? "Approved with conditions: " . json_encode($conditions)
                    : "Approved by " . $approver->name
            );

            // Log action
            \Log::info("Batch {$batch->id} approved by {$approver->name}", [
                'conditions' => $conditions,
            ]);

            return $review;
        });
    }

    /**
     * Reject batch with feedback
     */
    public function rejectBatch(
        MarkImportBatch $batch,
        User $rejector,
        string $reason,
        array $flaggedIssues = []
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $rejector, $reason, $flaggedIssues) {
            
            // Update review
            $review = $batch->latestReview();
            $review->update([
                'status' => 'rejected',
                'feedback' => $reason,
                'flagged_issues' => $flaggedIssues,
                'reviewed_at' => now(),
                'reviewer_id' => $rejector->id,
            ]);

            // Transition to rejected
            $this->lifecycleService->transition(
                $batch,
                'rejected',
                $rejector,
                "Rejected: {$reason}"
            );

            // Mark batch for resubmission
            $batch->update([
                'requires_resubmission' => true,
                'rejection_reason' => $reason,
            ]);

            // Log action
            \Log::warning("Batch {$batch->id} rejected by {$rejector->name}", [
                'reason' => $reason,
                'issue_count' => count($flaggedIssues),
            ]);

            return $review;
        });
    }

    /**
     * Flag issue on specific candidate in batch
     */
    public function flagIssue(
        MarkImportBatch $batch,
        int $candidateId,
        string $field,
        string $severity,
        string $description
    ): void {
        
        $review = $batch->latestReview();
        if (!$review) {
            throw new \Exception('No active moderation review for this batch');
        }

        $flagged = $review->flagged_issues ?? [];
        $flagged[] = [
            'candidate_id' => $candidateId,
            'field' => $field,
            'severity' => $severity,  // high, medium, low
            'description' => $description,
            'flagged_at' => now()->toIso8601String(),
        ];

        $review->update(['flagged_issues' => $flagged]);
    }

    /**
     * Get outlier candidates in batch
     */
    public function detectOutliers(MarkImportBatch $batch): array {
        $rawMarks = $batch->rawMarks()->get();
        $outliers = [];

        foreach ($rawMarks as $mark) {
            // Simple outlier detection: marks > 95
            if ($mark->paper_1_marks > 95 || $mark->paper_2_marks > 95) {
                $outliers[] = [
                    'candidate_id' => $mark->candidate_id,
                    'index_number' => $mark->candidate_index_number,
                    'field' => $mark->paper_1_marks > 95 ? 'paper_1' : 'paper_2',
                    'value' => max($mark->paper_1_marks, $mark->paper_2_marks),
                    'severity' => 'medium',
                ];
            }
        }

        return $outliers;
    }
}
```

---

## 4. CONTROLLER EXAMPLES

### MarkEntryModerationController.php

File: `app/Http/Controllers/MarkEntry/Moderation/MarkEntryModerationController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Moderation;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use App\Services\MarkEntry\MarkModerationService;
use App\Services\MarkEntry\LifecycleStateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MarkEntryModerationController extends Controller {

    private MarkModerationService $moderationService;
    private LifecycleStateService $lifecycleService;

    public function __construct(
        MarkModerationService $moderationService,
        LifecycleStateService $lifecycleService
    ) {
        $this->moderationService = $moderationService;
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Show moderation dashboard
     */
    public function dashboard(): View {
        
        $user = auth()->user();
        
        // Get batches awaiting moderation for this user
        $query = MarkImportBatch::where('lifecycle_state', 'awaiting_moderation');

        // Filter by user's scope (HOD for school, Supervisor for district)
        if ($user->role_id === Role::CODE_SCHOOL_HOD) {
            $query->where('school_id', $user->school_id);
        } elseif ($user->role_id === Role::CODE_DISTRICT_SUPERVISOR) {
            $query->whereHas('school.district', function($q) use ($user) {
                $q->where('district_id', $user->district_id);
            });
        }

        $batches = $query
            ->with('school', 'subject', 'examYear')
            ->latest('created_at')
            ->paginate(20);

        return view('mark-entry.moderation.dashboard', [
            'batches' => $batches,
            'totalAwaiting' => $query->count(),
            'totalApproved' => $this->getApprovedCount($user),
            'totalRejected' => $this->getRejectedCount($user),
        ]);
    }

    /**
     * Show batch review page
     */
    public function reviewBatch(MarkImportBatch $batch): View {
        
        $this->authorize('moderate', $batch);

        $rawMarks = $batch->rawMarks()
            ->with('candidate')
            ->get();

        $outliers = $this->moderationService->detectOutliers($batch);
        $review = $batch->latestReview();

        return view('mark-entry.moderation.review-batch', [
            'batch' => $batch,
            'marks' => $rawMarks,
            'outliers' => $outliers,
            'review' => $review,
            'canApprove' => $this->lifecycleService->canTransition($batch, 'approved'),
            'canReject' => $this->lifecycleService->canTransition($batch, 'rejected'),
        ]);
    }

    /**
     * Approve batch
     */
    public function approveBatch(Request $request, MarkImportBatch $batch): JsonResponse {
        
        $this->authorize('moderate', $batch);

        try {
            $validated = $request->validate([
                'feedback' => 'nullable|string|max:1000',
                'conditions' => 'nullable|array',
            ]);

            $this->moderationService->approveBatch(
                $batch,
                auth()->user(),
                $validated['feedback'] ?? null,
                $validated['conditions'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch approved successfully',
                'batch_id' => $batch->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject batch
     */
    public function rejectBatch(Request $request, MarkImportBatch $batch): JsonResponse {
        
        $this->authorize('moderate', $batch);

        try {
            $validated = $request->validate([
                'reason' => 'required|string|min:10|max:1000',
                'flagged_issues' => 'nullable|array',
            ]);

            $this->moderationService->rejectBatch(
                $batch,
                auth()->user(),
                $validated['reason'],
                $validated['flagged_issues'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Batch rejected. School has been notified.',
                'batch_id' => $batch->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Flag issue on candidate
     */
    public function flagIssue(Request $request, MarkImportBatch $batch): JsonResponse {
        
        $this->authorize('moderate', $batch);

        try {
            $validated = $request->validate([
                'candidate_id' => 'required|integer|exists:candidates,id',
                'field' => 'required|string|in:paper_1_marks,paper_2_marks,paper_3_marks,practical_marks,project_marks',
                'severity' => 'required|in:high,medium,low',
                'description' => 'required|string|max:500',
            ]);

            $this->moderationService->flagIssue(
                $batch,
                $validated['candidate_id'],
                $validated['field'],
                $validated['severity'],
                $validated['description']
            );

            return response()->json([
                'success' => true,
                'message' => 'Issue flagged',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * View feedback log
     */
    public function feedbackLog(MarkImportBatch $batch): View {
        
        $reviews = $batch->reviews()->latest('reviewed_at')->get();

        return view('mark-entry.moderation.feedback-log', [
            'batch' => $batch,
            'reviews' => $reviews,
        ]);
    }

    // Helper methods
    private function getApprovedCount($user) {
        // Count approved batches for this user
        return MarkModerationReview::where('reviewer_id', $user->id)
            ->where('status', 'approved')
            ->count();
    }

    private function getRejectedCount($user) {
        return MarkModerationReview::where('reviewer_id', $user->id)
            ->where('status', 'rejected')
            ->count();
    }
}
```

---

## 5. POLICY & AUTHORIZATION

### MarkImportBatchPolicy.php

File: `app/Policies/MarkImportBatchPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\MarkImportBatch;
use App\Models\User;
use App\Models\Role;

class MarkImportBatchPolicy {

    /**
     * Can upload marks
     */
    public function upload(User $user): bool {
        return $user->can('mark-entry.upload');
    }

    /**
     * Can view own uploaded batches
     */
    public function viewOwn(User $user, MarkImportBatch $batch): bool {
        return $batch->imported_by === $user->id;
    }

    /**
     * Can view batch (based on school/district scope)
     */
    public function view(User $user, MarkImportBatch $batch): bool {
        
        // Admins can view all
        if ($user->isAdmin()) {
            return true;
        }

        // HOD can view school batches
        if ($user->hasRole(Role::CODE_SCHOOL_HOD)) {
            return $batch->school_id === $user->school_id;
        }

        // District supervisor can view district batches
        if ($user->hasRole(Role::CODE_DISTRICT_SUPERVISOR)) {
            return $batch->school->district_id === $user->district_id;
        }

        // Teachers can view own batches
        return $batch->imported_by === $user->id;
    }

    /**
     * Can moderate batch
     */
    public function moderate(User $user, MarkImportBatch $batch): bool {
        
        if ($user->isAdmin()) {
            return true;
        }

        // HOD can moderate school batches
        if ($user->hasRole(Role::CODE_SCHOOL_HOD)) {
            return $batch->school_id === $user->school_id;
        }

        // District supervisor can moderate district batches
        if ($user->hasRole(Role::CODE_DISTRICT_SUPERVISOR)) {
            return $batch->school->district_id === $user->district_id;
        }

        return false;
    }

    /**
     * Can approve batch
     */
    public function approve(User $user, MarkImportBatch $batch): bool {
        return $this->moderate($user, $batch) 
            && $batch->lifecycle_state === 'awaiting_moderation';
    }

    /**
     * Can reject batch
     */
    public function reject(User $user, MarkImportBatch $batch): bool {
        return $this->moderate($user, $batch) 
            && in_array($batch->lifecycle_state, ['awaiting_moderation', 'approved']);
    }

    /**
     * Can lock batch
     */
    public function lock(User $user, MarkImportBatch $batch): bool {
        return $user->can('mark-entry.lock')
            && $batch->lifecycle_state === 'approved';
    }

    /**
     * Can unlock batch (admin only)
     */
    public function unlock(User $user): bool {
        return $user->can('mark-entry.unlock');
    }

    /**
     * Can delete batch
     */
    public function delete(User $user, MarkImportBatch $batch): bool {
        return $user->isAdmin() && in_array($batch->lifecycle_state, ['draft', 'rejected']);
    }

    /**
     * Can export batch
     */
    public function export(User $user, MarkImportBatch $batch): bool {
        return $this->view($user, $batch) && $user->can('mark-entry.export');
    }
}
```

### AuthServiceProvider.php Update

File: `app/Providers/AuthServiceProvider.php`

```php
public function boot(): void {
    Gate::policy(MarkImportBatch::class, MarkImportBatchPolicy::class);

    // Define permissions as gates
    Gate::define('mark-entry.upload', function (User $user) {
        return in_array($user->role->code ?? null, [
            'teacher',
            'school_registrar',
            'admin'
        ]);
    });

    Gate::define('mark-entry.moderate', function (User $user) {
        return in_array($user->role->code ?? null, [
            'school_hod',
            'district_supervisor',
            'admin'
        ]);
    });

    Gate::define('mark-entry.lock', function (User $user) {
        return in_array($user->role->code ?? null, [
            'school_hod',
            'district_supervisor',
            'admin'
        ]);
    });

    Gate::define('mark-entry.unlock', function (User $user) {
        return $user->isAdmin();
    });

    Gate::define('mark-entry.export', function (User $user) {
        return $user->isAdmin() || $user->hasRole('school_hod');
    });

    Gate::define('mark-entry.audit', function (User $user) {
        return $user->isAdmin();
    });

    Gate::define('mark-entry.admin', function (User $user) {
        return $user->isAdmin();
    });
}
```

---

## 6. FRONTEND INTEGRATION

### Sidebar Menu Structure (Blade)

File: `resources/views/partials/sidebar-mark-entry.blade.php`

```blade
<nav class="space-y-1">

    <!-- GROUP 1: Entry & Validation -->
    <div class="px-4 py-4 border-b border-gray-200">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">📊 ENTRY & VALIDATION</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.entry-validation.index') }}"
        class="px-4 py-2 text-sm hover:bg-blue-50 {{ request()->routeIs('mark-entry.acsee.entry-validation.*') ? 'bg-blue-100 border-r-4 border-blue-600' : '' }}">
        📤 Upload Marks
    </a>

    <a href="{{ route('mark-entry.acsee.entry-validation.error-batches') }}"
        class="px-4 py-2 text-sm hover:bg-red-50 {{ request()->routeIs('mark-entry.acsee.entry-validation.error-batches') ? 'bg-red-100 border-r-4 border-red-600' : '' }}">
        ⚠️ Error Batches
    </a>

    <!-- GROUP 2: Moderation (if user can moderate) -->
    @can('mark-entry.moderate')
    <div class="px-4 py-4 border-b border-gray-200 mt-6">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">🔍 MODERATION & REVIEW</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.moderation.dashboard') }}"
        class="px-4 py-2 text-sm hover:bg-purple-50 {{ request()->routeIs('mark-entry.acsee.moderation.*') ? 'bg-purple-100 border-r-4 border-purple-600' : '' }}">
        👁️ Review Dashboard
    </a>
    @endcan

    <!-- GROUP 3: Submission & Locking (if user can lock) -->
    @can('mark-entry.lock')
    <div class="px-4 py-4 border-b border-gray-200 mt-6">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">🔒 SUBMISSION & LOCKING</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.submission.dashboard') }}"
        class="px-4 py-2 text-sm hover:bg-green-50 {{ request()->routeIs('mark-entry.acsee.submission.*') ? 'bg-green-100 border-r-4 border-green-600' : '' }}">
        📌 Lock Approved Batches
    </a>
    @endcan

    <!-- GROUP 4: Reports -->
    <div class="px-4 py-4 border-b border-gray-200 mt-6">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">📑 REPORTS & EXPORTS</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.reports.scoresheet-bulk') }}"
        class="px-4 py-2 text-sm hover:bg-indigo-50">
        📋 Scoresheet PDFs
    </a>

    <a href="{{ route('mark-entry.acsee.reports.csv-export') }}"
        class="px-4 py-2 text-sm hover:bg-indigo-50">
        📊 CSV/Excel Exports
    </a>

    <!-- GROUP 5: Monitoring (if user can audit) -->
    @can('mark-entry.audit')
    <div class="px-4 py-4 border-b border-gray-200 mt-6">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">🕐 MONITORING & AUDIT</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.monitoring.dashboard') }}"
        class="px-4 py-2 text-sm hover:bg-gray-100">
        ⏱️ Lifecycle Dashboard
    </a>

    <a href="{{ route('mark-entry.acsee.monitoring.audit-trail') }}"
        class="px-4 py-2 text-sm hover:bg-gray-100">
        🔐 Audit Trail
    </a>
    @endcan

    <!-- GROUP 6: Admin (if user is admin) -->
    @can('mark-entry.admin')
    <div class="px-4 py-4 border-b border-gray-200 mt-6">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider">⚙️ ADMINISTRATION</h3>
    </div>

    <a href="{{ route('mark-entry.acsee.admin.configuration') }}"
        class="px-4 py-2 text-sm hover:bg-gray-100">
        🎓 ACSEE Configuration
    </a>

    <a href="{{ route('mark-entry.acsee.admin.access-control') }}"
        class="px-4 py-2 text-sm hover:bg-gray-100">
        🔐 Access Control
    </a>
    @endcan
</nav>
```

---

## NEXT STEPS

1. **Create the controllers** using the template structure above
2. **Run migrations** to create new tables
3. **Implement services** and inject into controllers
4. **Create blade views** for each phase (entry, moderation, submission, etc.)
5. **Write tests** for each service and controller
6. **Deploy to staging** and validate with real data
7. **Train users** on new moderation workflow
8. **Go live** with phased rollout (entry first, then moderation, etc.)

---

**Status**: Ready for implementation  
**Estimated Effort**: 10-12 development days  
**Testing**: 3-4 days  
**Deployment**: 2 days  

**Total Timeline**: ~3 weeks for full Phase 1
