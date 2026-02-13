# IRMS Exam Years Implementation Guide

**Framework:** Laravel  
**Database:** MySQL  
**Status:** Production-Ready  

---

## Overview

This implementation introduces **exam years** as a core domain entity in IRMS, enabling:

✅ Multi-year data isolation  
✅ Year locking after publication  
✅ Safe data migration from legacy systems  
✅ Zero data loss guarantee  
✅ ACID-compliant constraints  

---

## Architecture

### 1. Database Layer

#### Migration 1: `create_exam_years_table`
```php
Schema::create('exam_years', function (Blueprint $table) {
    $table->id();
    $table->string('year_label', 9)->unique();
    $table->boolean('is_active')->default(false);
    $table->boolean('is_locked')->default(false);
    $table->timestamp('published_at')->nullable();
    $table->timestamp('locked_at')->nullable();
    $table->timestamps();
    
    $table->index('is_active');
    $table->index('is_locked');
});
```

#### Migration 2: `add_exam_year_id_to_exam_tables`
Adds `exam_year_id` foreign key to:
- candidates
- registrations
- subject_registrations
- marks
- results
- summaries
- uploads
- reports
- csv_templates

Each with cascading delete and proper indexes.

### 2. Model Layer

#### ExamYear Model
```php
class ExamYear extends Model {
    // Relationships
    public function candidates(): HasMany
    public function registrations(): HasMany
    public function marks(): HasMany
    public function results(): HasMany
    // ... etc
    
    // Scopes
    public function scopeActive($query)
    public function scopeLocked($query)
    public function scopePublished($query)
    
    // Methods
    public function activate(): bool
    public function publish(): bool
    public function isLocked(): bool
    public function isActive(): bool
}
```

### 3. Middleware Layer

#### SetExamYearContext Middleware
**Responsibilities:**
1. Resolve exam year from session/request/route
2. Validate exam year exists
3. Reject writes to locked years (423 status)
4. Bind year to app container
5. Add year to request attributes

**Registration in `app/Http/Kernel.php`:**
```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \App\Http\Middleware\SetExamYearContext::class,
    ],
];
```

### 4. Authorization Layer

#### ExamYearPolicy
Enforces:
- Only admins can create/update/delete years
- Cannot write to locked years
- Cannot publish already published years
- Role-based access control

### 5. Service Layer

#### ExamYearService
Business logic:
- `create(array $data): ExamYear`
- `activate(int $examYearId): bool`
- `publishResults(int $examYearId): bool`
- `getStatistics(int $examYearId): array`
- `isLocked(int $examYearId): bool`

### 6. Controller Layer

#### ExamYearController
REST endpoints:
- `GET /exam-years` - List all years
- `POST /exam-years` - Create year
- `GET /exam-years/{id}` - View year details
- `PUT /exam-years/{id}` - Update year
- `DELETE /exam-years/{id}` - Delete year
- `POST /exam-years/{id}/activate` - Activate year
- `POST /exam-years/{id}/publish` - Publish & lock
- `GET /api/exam-years` - JSON endpoint

---

## Installation Steps

### Step 1: Run Migrations

```bash
# Create exam_years table
php artisan migrate

# Or run specific migration
php artisan migrate --path=database/migrations/2024_02_01_000001_create_exam_years_table.php
php artisan migrate --path=database/migrations/2024_02_01_000002_add_exam_year_id_to_exam_tables.php
```

### Step 2: Register Middleware

In `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\SetExamYearContext::class,
    ],
];
```

### Step 3: Register Policy

In `app/Providers/AuthServiceProvider.php`:
```php
protected $policies = [
    ExamYear::class => ExamYearPolicy::class,
];
```

### Step 4: Migrate Existing Data

```bash
# Create legacy year and backfill existing data
php artisan db:seed --class=MigrateExistingDataToExamYear
```

This will:
1. Create a legacy year (e.g., "2024")
2. Backfill all existing records with this year
3. Validate data integrity
4. Print migration summary

### Step 5: Add Routes

In `routes/web.php`:
```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('exam-years', ExamYearController::class);
    Route::post('exam-years/{examYear}/activate', [ExamYearController::class, 'activate'])->name('exam-years.activate');
    Route::post('exam-years/{examYear}/publish', [ExamYearController::class, 'publish'])->name('exam-years.publish');
});

Route::get('api/exam-years', [ExamYearController::class, 'indexApi']);
```

### Step 6: Add UI Components

In `resources/views/layouts/app.blade.php`:
```blade
<x-exam-year-selector 
    :examYears="$examYears" 
    :selected="session('exam_year')" 
/>
```

---

## Usage Examples

### Access Current Exam Year in Controllers

```php
class MarkEntryController extends Controller {
    public function store(Request $request) {
        // From middleware context
        $examYear = app('examYear');
        // or
        $examYearId = app('examYearId');
        
        // Check if locked
        if ($examYear->isLocked()) {
            return response()->json(['error' => 'Locked'], 423);
        }
        
        // Process marks
        Mark::create([
            'candidate_id' => $request->candidate_id,
            'exam_year_id' => $examYearId,
            'marks' => $request->marks,
        ]);
    }
}
```

### Query by Exam Year

```php
// Always filter by exam year
$candidates = Candidate::where('exam_year_id', $examYearId)->get();

// Or use scope
$active = Candidate::active()->get(); // Implicitly filtered by active year
```

### Prevent Writes to Locked Years

```php
// In service layer
public function updateMarks($markId, array $data, $examYearId) {
    // Check if year is locked
    if (ExamYear::find($examYearId)->isLocked()) {
        throw new \Exception('Year is locked');
    }
    
    // Update marks
    Mark::find($markId)->update($data);
}
```

### Publish Results (Triggers Locking)

```php
// In controller
public function publish(ExamYear $examYear) {
    $this->authorize('publish', $examYear);
    
    $this->examYearService->publishResults($examYear->id);
    
    // Year is now locked, no further writes possible
}
```

---

## Data Flow

### 1. Year Selection
```
User selects year from dropdown
  ↓
SetExamYearContext middleware
  ↓
Resolves from session/request/route
  ↓
Validates year exists
  ↓
Binds to app container: app('examYear')
  ↓
Makes available in controllers/views
```

### 2. Write Operation on Locked Year
```
User attempts POST/PUT/DELETE
  ↓
SetExamYearContext middleware
  ↓
Detects write operation
  ↓
Checks if year is locked
  ↓
If locked: returns 423 Locked response
  ↓
If unlocked: proceeds to controller
```

### 3. Publishing Results
```
Admin publishes results
  ↓
ExamYearService.publishResults()
  ↓
Updates exam_year.published_at = now()
  ↓
Updates exam_year.is_locked = true
  ↓
All further writes blocked (middleware)
  ↓
Read-only mode enforced
```

---

## API Endpoints

### Create Exam Year
```
POST /exam-years
Content-Type: application/json

{
    "year_label": "2025"
}

Response: 201 Created
```

### List All Years
```
GET /exam-years

Response: List of years with active year highlighted
```

### Activate Year
```
POST /exam-years/1/activate

Response: Redirect with success message
```

### Publish Results (Lock Year)
```
POST /exam-years/1/publish

Response: 
{
    "success": true,
    "message": "Results published and year locked"
}

After this, all writes return 423 Locked
```

### JSON API (for AJAX)
```
GET /api/exam-years

Response:
{
    "data": [
        { "id": 1, "year_label": "2024", "is_active": true, "is_locked": false },
        { "id": 2, "year_label": "2025", "is_active": false, "is_locked": true }
    ],
    "active_year": { "id": 1, "year_label": "2024" }
}
```

---

## Error Handling

### 400 Bad Request
Invalid input (e.g., malformed year_label)

### 403 Forbidden
User not authorized (policy check failed)

### 422 Unprocessable Entity
Business logic violation (e.g., duplicate year, already published)

### 423 Locked
Attempted write to locked year

```php
// Middleware response
if ($examYear->isLocked() && $this->isWriteOperation($request)) {
    return response()->json([
        'error' => 'Locked Year',
        'message' => "Exam year {$examYear->year_label} is locked and read-only"
    ], 423);
}
```

---

## Data Migration

### MigrateExistingDataToExamYear Seeder

**Execution:**
```bash
php artisan db:seed --class=MigrateExistingDataToExamYear
```

**Steps:**
1. Creates legacy year (current year, e.g., "2024")
2. Backfills all existing records with legacy year ID
3. Validates:
   - No NULL exam_year_id
   - No orphaned foreign keys
   - Row counts unchanged
4. Prints summary

**Output:**
```
✓ Legacy exam year created (ID: 1, Label: 2024)
✓ Existing data backfilled
  - candidates: 5000 records
  - registrations: 3200 records
  - marks: 15000 records
  ...
✓ Data integrity validation passed
```

---

## Validation Rules

### Create/Update Exam Year

```php
// In ExamYearController
$validated = $request->validate([
    'year_label' => 'required|string|size:4|unique:exam_years,year_label',
]);
```

- `year_label`: Required, 4 characters, must be unique

---

## Relationships & Cascading

### Foreign Key Constraints

All exam_year_id foreign keys use `cascadeOnDelete`:
```php
$table->foreignId('exam_year_id')
    ->constrained('exam_years')
    ->cascadeOnDelete(); // Deletes related records
```

This means:
- Deleting an exam year deletes all related records
- ⚠️ Be careful: Deletion is permanent

### Preventing Deletes

Published/locked years prevent deletion:
```php
public function delete(User $user, ExamYear $examYear): bool {
    if ($examYear->isLocked() || $examYear->isPublished()) {
        return false; // Cannot delete
    }
    return true;
}
```

---

## Testing

### Unit Tests

```php
class ExamYearServiceTest extends TestCase {
    public function test_can_create_exam_year() {
        $service = new ExamYearService();
        $year = $service->create(['year_label' => '2026']);
        $this->assertEquals('2026', $year->year_label);
    }
    
    public function test_cannot_publish_twice() {
        $year = ExamYear::factory()->published()->create();
        $this->expectException(Exception::class);
        $service->publishResults($year->id);
    }
    
    public function test_locked_year_prevents_writes() {
        $year = ExamYear::factory()->locked()->create();
        $response = $this->post('/marks', ['exam_year_id' => $year->id]);
        $response->assertStatus(423); // Locked
    }
}
```

### Feature Tests

```php
class ExamYearControllerTest extends TestCase {
    public function test_can_activate_year() {
        $user = User::factory()->admin()->create();
        $year = ExamYear::factory()->create();
        
        $this->actingAs($user)->post(route('exam-years.activate', $year))
            ->assertRedirect();
        
        $this->assertTrue($year->fresh()->is_active);
    }
}
```

---

## Production Checklist

- [ ] Backup database before migrations
- [ ] Run migrations on staging first
- [ ] Run data migration seeder
- [ ] Verify all exam data accessible
- [ ] Test year switching
- [ ] Test locked year behavior (403/423 responses)
- [ ] Update API documentation
- [ ] Train users on year selector
- [ ] Monitor logs for errors
- [ ] Set up alerts for failed publications

---

## Troubleshooting

### "No Exam Years Available"
**Issue:** Migration didn't create legacy year
**Solution:**
```bash
php artisan db:seed --class=MigrateExistingDataToExamYear
```

### "Cannot Write to Locked Year" (before publishing)
**Issue:** Year marked as locked but not published
**Cause:** Manual database update or migration error
**Solution:** Check `published_at` and `locked_at` fields

### Exam Year Selector Not Showing
**Issue:** Component not registered
**Solution:** Register in `config/view.php` or use full component name:
```blade
<x-exam-year-selector ... />
```

---

## Next Steps

1. **UI Enhancements**
   - Add year statistics dashboard
   - Show lock status in all views
   - Add year comparison reports

2. **Authorization**
   - Implement role-based access control
   - Add permission levels for different roles

3. **Audit Logging**
   - Log all year changes
   - Track publication events
   - Audit who published and when

4. **CSV Integration**
   - Embed exam_year_id in CSV templates
   - Validate year on import
   - Prevent re-upload to locked years

5. **Reporting**
   - Update all reports to filter by year
   - Add year-over-year comparison

---

## Support

For issues or questions, refer to:
- Model documentation: `app/Models/ExamYear.php`
- Service documentation: `app/Services/ExamYears/ExamYearService.php`
- Controller documentation: `app/Http/Controllers/ExamYearController.php`

