# PHASE 1: FOUNDATION - QUICKSTART GUIDE

**Getting from planning to code in 2 weeks**

---

## DAY 1-2: SETUP & DATABASE

### Step 1: Create Feature Branch

```bash
cd /home/prosmart-technologies/SOL/irms
git checkout -b feature/mark-entry-lifecycle
git pull origin main
```

### Step 2: Create Migration Files

Create these 6 migration files in `database/migrations/`:

**File 1**: `2026_02_13_001_create_mark_entry_lifecycle_states_table.php`

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
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->enum('current_state', [
                'draft', 'validating', 'validated', 'validation_failed',
                'awaiting_moderation', 'approved', 'rejected', 'processing',
                'processed', 'submitted', 'archived'
            ])->default('draft');
            $table->string('previous_state')->nullable();
            $table->foreignId('transitioned_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('transitioned_at')->nullable();
            $table->text('transition_reason')->nullable();
            $table->json('history')->nullable();
            $table->timestamps();
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

**File 2**: `2026_02_13_002_create_mark_moderation_reviews_table.php`

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
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->foreignId('reviewer_id')
                ->constrained('users')->onDelete('cascade');
            $table->enum('review_type', ['school_hod', 'district_supervisor', 'admin']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'conditional'])
                ->default('pending');
            $table->longText('feedback')->nullable();
            $table->json('flagged_issues')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('signature')->nullable();
            $table->timestamps();
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

**File 3**: `2026_02_13_003_create_mark_entry_changes_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mark_entry_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_mark_id')
                ->constrained('raw_marks')->onDelete('cascade');
            $table->foreignId('changed_by')
                ->constrained('users')->onDelete('cascade');
            $table->enum('change_type', ['upload', 'edit', 'validation_fix', 'admin_correction']);
            $table->string('field_name');
            $table->decimal('old_value', 6, 2)->nullable();
            $table->decimal('new_value', 6, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('changed_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
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

**File 4**: `2026_02_13_004_create_mark_batch_approvals_table.php`

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
                ->constrained('mark_import_batches')->onDelete('cascade');
            $table->enum('approval_level', ['validation', 'moderation', 'submission']);
            $table->foreignId('approved_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('signature')->nullable();
            $table->timestamps();
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

**File 5**: `2026_02_13_005_enhance_mark_import_batches_table.php`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->enum('lifecycle_state', [
                'draft', 'validating', 'validated', 'validation_failed',
                'awaiting_moderation', 'approved', 'rejected', 'processing',
                'processed', 'submitted', 'archived'
            ])->default('draft')->after('status');
            $table->json('lifecycle_history')->nullable();
            $table->longText('rejection_reason')->nullable();
            $table->boolean('requires_resubmission')->default(false);
            $table->foreignId('resubmitted_from_batch_id')->nullable()
                ->constrained('mark_import_batches')->nullOnDelete();
            $table->foreignId('latest_review_id')->nullable()
                ->constrained('mark_moderation_reviews')->nullOnDelete();
            $table->string('batch_hash')->nullable()->unique();
        });
    }
    public function down(): void {
        Schema::table('mark_import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'lifecycle_state', 'lifecycle_history', 'rejection_reason',
                'requires_resubmission', 'resubmitted_from_batch_id',
                'latest_review_id', 'batch_hash'
            ]);
        });
    }
};
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

**Expected output:**:
```
Migrating: 2026_02_13_001_create_mark_entry_lifecycle_states_table
Migrated:  2026_02_13_001_create_mark_entry_lifecycle_states_table (XXms)
Migrating: 2026_02_13_002_create_mark_moderation_reviews_table
Migrated:  2026_02_13_002_create_mark_moderation_reviews_table (XXms)
[... etc ...]
```

### Step 4: Verify Database

```bash
php artisan tinker
> DB::table('mark_entry_lifecycle_states')->count()
// Should return 0 (empty table, that's correct)
> exit()
```

---

## DAY 3-4: ROUTES & PERMISSIONS

### Step 1: Create Routes File

Create: `routes/mark-entry.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MarkEntry\Entry\MarkEntryUploadController;
use App\Http\Controllers\MarkEntry\Entry\MarkEntryApiController;
use App\Http\Controllers\MarkEntry\Moderation\MarkEntryModerationController;
use App\Http\Controllers\MarkEntry\Submission\MarkEntrySubmissionController;
use App\Http\Controllers\MarkEntry\Reporting\MarkEntryReportController;
use App\Http\Controllers\MarkEntry\Audit\MarkEntryMonitoringController;
use App\Http\Controllers\MarkEntry\Admin\MarkEntryAdminController;

Route::middleware(['auth'])->prefix('mark-entry')->group(function () {

    Route::prefix('acsee')->name('mark-entry.acsee.')->group(function () {

        // ENTRY & VALIDATION PHASE
        Route::prefix('entry-validation')->name('entry-validation.')->group(function () {
            Route::get('/', [MarkEntryUploadController::class, 'index'])->name('index');
            Route::get('download-template', [MarkEntryUploadController::class, 'downloadTemplate'])->name('download-template')->middleware('can:mark-entry.upload');
            Route::post('upload', [MarkEntryUploadController::class, 'upload'])->name('upload')->middleware('can:mark-entry.upload');
            Route::get('batch/{batchId}', [MarkEntryUploadController::class, 'batchDetails'])->name('batch-details');
        });

        // MODERATION & REVIEW PHASE
        Route::prefix('moderation')->name('moderation.')
            ->middleware('can:mark-entry.moderate')->group(function () {
            Route::get('/', [MarkEntryModerationController::class, 'dashboard'])->name('dashboard');
            Route::get('batch/{batchId}', [MarkEntryModerationController::class, 'reviewBatch'])->name('review-batch');
            Route::post('batch/{batchId}/approve', [MarkEntryModerationController::class, 'approveBatch'])->name('approve');
            Route::post('batch/{batchId}/reject', [MarkEntryModerationController::class, 'rejectBatch'])->name('reject');
        });

        // SUBMISSION & LOCKING PHASE
        Route::prefix('submission')->name('submission.')
            ->middleware('can:mark-entry.lock')->group(function () {
            Route::get('/', [MarkEntrySubmissionController::class, 'dashboard'])->name('dashboard');
            Route::post('lock/{batchId}', [MarkEntrySubmissionController::class, 'lockBatch'])->name('lock');
        });

        // REPORTING PHASE
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('scoresheet/{batchId}', [MarkEntryReportController::class, 'scoresheet'])->name('scoresheet');
        });

        // MONITORING & AUDIT PHASE
        Route::prefix('monitoring')->name('monitoring.')
            ->middleware('can:mark-entry.audit')->group(function () {
            Route::get('/', [MarkEntryMonitoringController::class, 'lifecycleDashboard'])->name('dashboard');
            Route::get('audit-trail', [MarkEntryMonitoringController::class, 'auditTrail'])->name('audit-trail');
        });

        // SHARED API ENDPOINTS
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('regions', [MarkEntryApiController::class, 'regions'])->name('regions');
            Route::get('districts', [MarkEntryApiController::class, 'districts'])->name('districts');
            Route::get('schools', [MarkEntryApiController::class, 'schools'])->name('schools');
            Route::get('subjects', [MarkEntryApiController::class, 'subjects'])->name('subjects');
            Route::get('exam-years', [MarkEntryApiController::class, 'examYears'])->name('exam-years');
        });
    });
});
```

### Step 2: Register Routes in web.php

Edit: `routes/web.php` - Add at the end before closing brace:

```php
// Mark Entry Routes (NEW)
require base_path('routes/mark-entry.php');
```

### Step 3: Add Permissions to AuthServiceProvider

Edit: `app/Providers/AuthServiceProvider.php`

Add to `boot()` method:

```php
// Mark Entry Permissions
Gate::define('mark-entry.upload', function (User $user) {
    return in_array($user->role->code ?? null, ['teacher', 'school_registrar', 'admin']);
});

Gate::define('mark-entry.moderate', function (User $user) {
    return in_array($user->role->code ?? null, ['school_hod', 'district_supervisor', 'admin']);
});

Gate::define('mark-entry.lock', function (User $user) {
    return in_array($user->role->code ?? null, ['school_hod', 'district_supervisor', 'admin']);
});

Gate::define('mark-entry.unlock', function (User $user) {
    return $user->isAdmin();
});

Gate::define('mark-entry.audit', function (User $user) {
    return $user->isAdmin();
});

Gate::define('mark-entry.admin', function (User $user) {
    return $user->isAdmin();
});
```

### Step 4: Verify Routes

```bash
php artisan route:list | grep "mark-entry"
```

**Should see 20+ routes listed**

---

## DAY 5-6: SERVICES & POLICIES

### Step 1: Create Service Directory

```bash
mkdir -p app/Services/MarkEntry
mkdir -p app/Services/MarkEntry/Shared
mkdir -p app/Services/MarkEntry/Entry
mkdir -p app/Services/MarkEntry/Moderation
```

### Step 2: Create LifecycleStateService

Create: `app/Services/MarkEntry/Shared/LifecycleStateService.php`

```php
<?php

namespace App\Services\MarkEntry\Shared;

use App\Models\MarkImportBatch;
use App\Models\MarkEntryLifecycleState;
use Illuminate\Support\Facades\DB;

class LifecycleStateService {
    
    private const VALID_TRANSITIONS = [
        'draft' => ['validating', 'rejected'],
        'validating' => ['validated', 'validation_failed'],
        'validated' => ['awaiting_moderation', 'draft'],
        'validation_failed' => ['draft'],
        'awaiting_moderation' => ['approved', 'rejected'],
        'approved' => ['submitted', 'draft'],
        'rejected' => ['draft'],
        'processing' => ['processed'],
        'processed' => ['submitted'],
        'submitted' => ['archived'],
        'archived' => [],
    ];

    public function transition(
        MarkImportBatch $batch,
        string $newState,
        $user,
        ?string $reason = null
    ): MarkEntryLifecycleState {
        
        return DB::transaction(function () use ($batch, $newState, $user, $reason) {
            $currentState = $batch->lifecycle_state ?? 'draft';
            
            if (!$this->isValidTransition($currentState, $newState)) {
                throw new \Exception(
                    "Cannot transition from '{$currentState}' to '{$newState}'"
                );
            }

            $lifecycle = MarkEntryLifecycleState::create([
                'mark_import_batch_id' => $batch->id,
                'current_state' => $newState,
                'previous_state' => $currentState,
                'transitioned_by' => $user->id ?? null,
                'transitioned_at' => now(),
                'transition_reason' => $reason ?? $this->getDefaultReason($newState),
            ]);

            $batch->update(['lifecycle_state' => $newState]);

            \Log::info("Batch {$batch->id} transitioned: {$currentState} → {$newState}");

            return $lifecycle;
        });
    }

    private function isValidTransition(string $from, string $to): bool {
        $allowed = self::VALID_TRANSITIONS[$from] ?? [];
        return in_array($to, $allowed);
    }

    private function getDefaultReason(string $state): string {
        return match ($state) {
            'validating' => 'Validation in progress',
            'validated' => 'All validation rules passed',
            'awaiting_moderation' => 'Waiting for moderator review',
            'approved' => 'Approved by moderator',
            'rejected' => 'Rejected, awaiting resubmission',
            'submitted' => 'Submitted to exam authority',
            default => ucwords(str_replace('_', ' ', $state)),
        };
    }

    public function getCurrentState(MarkImportBatch $batch): string {
        return $batch->lifecycle_state ?? 'draft';
    }

    public function canTransition(MarkImportBatch $batch, string $targetState): bool {
        $current = $this->getCurrentState($batch);
        return $this->isValidTransition($current, $targetState);
    }

    public function getAvailableTransitions(MarkImportBatch $batch): array {
        $current = $this->getCurrentState($batch);
        return self::VALID_TRANSITIONS[$current] ?? [];
    }
}
```

### Step 3: Create MarkModerationService

Create: `app/Services/MarkEntry/Moderation/MarkModerationService.php`

```php
<?php

namespace App\Services\MarkEntry\Moderation;

use App\Models\MarkImportBatch;
use App\Models\MarkModerationReview;
use App\Services\MarkEntry\Shared\LifecycleStateService;
use Illuminate\Support\Facades\DB;

class MarkModerationService {

    private LifecycleStateService $lifecycleService;

    public function __construct(LifecycleStateService $lifecycleService) {
        $this->lifecycleService = $lifecycleService;
    }

    public function createReview(
        MarkImportBatch $batch,
        $reviewer,
        string $reviewType
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $reviewer, $reviewType) {
            
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

    public function approveBatch(
        MarkImportBatch $batch,
        $approver,
        ?string $feedback = null
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $approver, $feedback) {
            
            $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                ->latest('id')->first();
            
            if (!$review) {
                throw new \Exception('No active moderation review found');
            }

            $review->update([
                'status' => 'approved',
                'feedback' => $feedback,
                'reviewed_at' => now(),
                'reviewer_id' => $approver->id,
            ]);

            $this->lifecycleService->transition(
                $batch,
                'approved',
                $approver,
                "Approved by " . $approver->name
            );

            \Log::info("Batch {$batch->id} approved by {$approver->name}");

            return $review;
        });
    }

    public function rejectBatch(
        MarkImportBatch $batch,
        $rejector,
        string $reason
    ): MarkModerationReview {
        
        return DB::transaction(function () use ($batch, $rejector, $reason) {
            
            $review = MarkModerationReview::where('mark_import_batch_id', $batch->id)
                ->latest('id')->first();
            
            if (!$review) {
                throw new \Exception('No active moderation review found');
            }

            $review->update([
                'status' => 'rejected',
                'feedback' => $reason,
                'reviewed_at' => now(),
                'reviewer_id' => $rejector->id,
            ]);

            $this->lifecycleService->transition(
                $batch,
                'rejected',
                $rejector,
                "Rejected: {$reason}"
            );

            $batch->update([
                'requires_resubmission' => true,
                'rejection_reason' => $reason,
            ]);

            \Log::warning("Batch {$batch->id} rejected by {$rejector->name}");

            return $review;
        });
    }
}
```

### Step 4: Create Authorization Policy

Create: `app/Policies/MarkImportBatchPolicy.php`

```php
<?php

namespace App\Policies;

use App\Models\MarkImportBatch;
use App\Models\User;

class MarkImportBatchPolicy {

    public function moderate(User $user, MarkImportBatch $batch): bool {
        
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->role->code === 'school_hod') {
            return $batch->school_id === $user->school_id;
        }

        if ($user->role->code === 'district_supervisor') {
            return $batch->school->district_id === $user->district_id;
        }

        return false;
    }

    public function lock(User $user, MarkImportBatch $batch): bool {
        return $this->moderate($user, $batch) && 
               $batch->lifecycle_state === 'approved';
    }

    public function unlock(User $user): bool {
        return $user->isAdmin();
    }
}
```

### Step 5: Register Policy in AuthServiceProvider

Add to `boot()` method:

```php
Gate::policy(MarkImportBatch::class, MarkImportBatchPolicy::class);
```

---

## DAY 7-9: CONTROLLERS

### Step 1: Create Controller Directories

```bash
mkdir -p app/Http/Controllers/MarkEntry/Entry
mkdir -p app/Http/Controllers/MarkEntry/Moderation
mkdir -p app/Http/Controllers/MarkEntry/Submission
mkdir -p app/Http/Controllers/MarkEntry/Reporting
mkdir -p app/Http/Controllers/MarkEntry/Audit
mkdir -p app/Http/Controllers/MarkEntry/Admin
```

### Step 2: Create Stub Controllers

Create: `app/Http/Controllers/MarkEntry/Entry/MarkEntryUploadController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Entry;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarkEntryUploadController extends Controller {
    
    public function index() {
        return view('mark-entry.index');
    }

    public function downloadTemplate(Request $request) {
        return response()->json(['message' => 'Template download']);
    }

    public function upload(Request $request) {
        return response()->json(['success' => true]);
    }

    public function batchDetails($batchId) {
        return response()->json(['batch_id' => $batchId]);
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Entry/MarkEntryApiController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Entry;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarkEntryApiController extends Controller {
    
    public function regions() {
        return response()->json(['data' => []]);
    }

    public function districts(Request $request) {
        return response()->json(['data' => []]);
    }

    public function schools(Request $request) {
        return response()->json(['data' => []]);
    }

    public function subjects(Request $request) {
        return response()->json(['data' => []]);
    }

    public function examYears() {
        return response()->json(['data' => []]);
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Moderation/MarkEntryModerationController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Moderation;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryModerationController extends Controller {
    
    public function dashboard() {
        $batches = MarkImportBatch::where('lifecycle_state', 'awaiting_moderation')
            ->paginate(20);
        return view('mark-entry.moderation.dashboard', ['batches' => $batches]);
    }

    public function reviewBatch(MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        return view('mark-entry.moderation.review-batch', ['batch' => $batch]);
    }

    public function approveBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        return response()->json(['success' => true]);
    }

    public function rejectBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('moderate', $batch);
        return response()->json(['success' => true]);
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Submission/MarkEntrySubmissionController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Submission;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntrySubmissionController extends Controller {
    
    public function dashboard() {
        $batches = MarkImportBatch::where('lifecycle_state', 'approved')->paginate(20);
        return view('mark-entry.submission.dashboard', ['batches' => $batches]);
    }

    public function lockBatch(Request $request, MarkImportBatch $batch) {
        $this->authorize('lock', $batch);
        return response()->json(['success' => true]);
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Reporting/MarkEntryReportController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Reporting;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryReportController extends Controller {
    
    public function scoresheet(MarkImportBatch $batch) {
        return response()->json(['batch_id' => $batch->id]);
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Audit/MarkEntryMonitoringController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Audit;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryMonitoringController extends Controller {
    
    public function lifecycleDashboard() {
        $batches = MarkImportBatch::all();
        return view('mark-entry.monitoring.dashboard', ['batches' => $batches]);
    }

    public function auditTrail() {
        return view('mark-entry.monitoring.audit-trail');
    }
}
```

Create: `app/Http/Controllers/MarkEntry/Admin/MarkEntryAdminController.php`

```php
<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarkEntryAdminController extends Controller {
    
    public function configuration() {
        return view('mark-entry.admin.configuration');
    }
}
```

---

## DAY 10: TESTING

### Step 1: Create Test for LifecycleStateService

Create: `tests/Unit/Services/MarkEntry/LifecycleStateServiceTest.php`

```php
<?php

namespace Tests\Unit\Services\MarkEntry;

use Tests\TestCase;
use App\Models\MarkImportBatch;
use App\Models\User;
use App\Services\MarkEntry\Shared\LifecycleStateService;

class LifecycleStateServiceTest extends TestCase {
    
    private LifecycleStateService $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = app(LifecycleStateService::class);
    }

    public function test_can_transition_from_draft_to_validating() {
        $batch = MarkImportBatch::factory()->create(['lifecycle_state' => 'draft']);
        $user = User::factory()->create();

        $transition = $this->service->transition($batch, 'validating', $user);

        $this->assertEquals('validating', $transition->current_state);
        $this->assertEquals('draft', $transition->previous_state);
        $this->assertTrue($batch->fresh()->lifecycle_state === 'validating');
    }

    public function test_cannot_transition_invalid_path() {
        $batch = MarkImportBatch::factory()->create(['lifecycle_state' => 'draft']);
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->service->transition($batch, 'submitted', $user);
    }

    public function test_get_current_state() {
        $batch = MarkImportBatch::factory()->create(['lifecycle_state' => 'approved']);
        
        $state = $this->service->getCurrentState($batch);
        
        $this->assertEquals('approved', $state);
    }

    public function test_can_transition_checks() {
        $batch = MarkImportBatch::factory()->create(['lifecycle_state' => 'validated']);
        
        $this->assertTrue($this->service->canTransition($batch, 'awaiting_moderation'));
        $this->assertFalse($this->service->canTransition($batch, 'submitted'));
    }
}
```

### Step 2: Run Tests

```bash
php artisan test tests/Unit/Services/MarkEntry/LifecycleStateServiceTest.php
```

**Expected**: 4 tests passing

### Step 3: Check Test Coverage

```bash
php artisan test --coverage tests/Unit/Services/MarkEntry/
```

---

## DAY 11-12: FINAL VERIFICATION

### Step 1: Test All Routes

```bash
php artisan route:list | grep mark-entry | wc -l
```

**Should show ~20+ routes**

### Step 2: Test Authorization

```bash
php artisan tinker
> $batch = \App\Models\MarkImportBatch::first();
> $user = \App\Models\User::first();
> Gate::allows('mark-entry.moderate', $batch)
// Should return true or false based on user role
```

### Step 3: Verify Models

```bash
php artisan tinker
> \App\Models\MarkEntryLifecycleState::count()
// Should return 0 (empty, correct)
> \App\Models\MarkModerationReview::count()
// Should return 0 (empty, correct)
```

### Step 4: Database Structure Check

```bash
php artisan schema:show mark_entry_lifecycle_states
php artisan schema:show mark_moderation_reviews
php artisan schema:show mark_entry_changes
php artisan schema:show mark_batch_approvals
```

---

## PHASE 1 COMPLETION CHECKLIST

By end of day 12, verify:

- [ ] All 6 migrations applied successfully
- [ ] `routes/mark-entry.php` created and registered
- [ ] 20+ routes visible in `php artisan route:list`
- [ ] `LifecycleStateService` implemented with 6 methods
- [ ] `MarkModerationService` implemented with 3 methods
- [ ] `MarkImportBatchPolicy` created and registered
- [ ] 8 controller stubs created in proper namespace
- [ ] 4 unit tests passing for LifecycleStateService
- [ ] No PHP errors or warnings
- [ ] Feature branch ready to commit
- [ ] All 4 new tables in database
- [ ] Authorization gates defined in AuthServiceProvider

---

## NEXT STEPS

After Phase 1 complete:

1. **Commit to branch**:
   ```bash
   git add .
   git commit -m "feat: mark-entry-lifecycle Phase 1 foundation complete"
   ```

2. **Create pull request** for code review

3. **Demo to stakeholders** - Show:
   - Routes working
   - Database migrations applied
   - Services functional
   - Tests passing

4. **Start Phase 2** - Moderation workflows

---

## TROUBLESHOOTING

**Problem**: Migrations fail  
**Solution**: Check `php artisan migrate:status`, run `php artisan migrate:refresh`

**Problem**: Routes not showing  
**Solution**: Run `php artisan route:cache && php artisan route:clear`

**Problem**: Tests fail  
**Solution**: Ensure test database exists, run `php artisan migrate --env=testing`

**Problem**: Authorization not working  
**Solution**: Verify `AuthServiceProvider` has Gate definitions, check user role setup

---

## SUPPORT

Refer to these documents for detailed explanations:
- **MARK_ENTRY_IMPLEMENTATION_BLUEPRINT.md** - Code examples
- **MARK_ENTRY_LIFECYCLE_ARCHITECTURE_AUDIT.md** - Architecture details
- **START_HERE_IMPLEMENTATION_GUIDE.md** - Getting started

---

**Total Time**: 12 days  
**Deliverable**: Foundation complete, ready for Phase 2  
**Status**: Follow this guide exactly, all tests should pass

Begin NOW. Execute methodically. No shortcuts. Success guaranteed. 🚀

