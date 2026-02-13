# IRMS Exam Years - Quick Start Guide

## 5-Minute Setup

### 1. Run Migrations
```bash
php artisan migrate
```

Creates:
- `exam_years` table
- Adds `exam_year_id` to all exam tables

### 2. Register Middleware
Edit `app/Http/Kernel.php`:
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\SetExamYearContext::class,
    ],
];
```

### 3. Register Policy
Edit `app/Providers/AuthServiceProvider.php`:
```php
protected $policies = [
    ExamYear::class => ExamYearPolicy::class,
];
```

### 4. Migrate Data
```bash
php artisan db:seed --class=MigrateExistingDataToExamYear
```

Creates legacy year and backfills all data.

### 5. Add Routes
Edit `routes/web.php`:
```php
Route::resource('exam-years', ExamYearController::class);
Route::post('exam-years/{examYear}/activate', [ExamYearController::class, 'activate']);
Route::post('exam-years/{examYear}/publish', [ExamYearController::class, 'publish']);
```

### 6. Add UI Component
Edit `resources/views/layouts/app.blade.php`:
```blade
<x-exam-year-selector 
    :examYears="$examYears ?? []" 
    :selected="session('exam_year')" 
/>
```

---

## Key Files

| File | Purpose |
|------|---------|
| `app/Models/ExamYear.php` | Core domain model |
| `app/Http/Middleware/SetExamYearContext.php` | Year resolution & locking |
| `app/Policies/ExamYearPolicy.php` | Authorization |
| `app/Services/ExamYears/ExamYearService.php` | Business logic |
| `app/Http/Controllers/ExamYearController.php` | REST endpoints |
| `database/migrations/2024_02_01_000001_*.php` | Database schema |
| `database/seeders/MigrateExistingDataToExamYear.php` | Data migration |
| `resources/views/components/exam-year-selector.blade.php` | Year selector UI |
| `app/Models/Traits/BelongsToExamYear.php` | Reusable trait for models |

---

## How It Works

### 1. Year Selection
```
User selects year → Middleware sets context → Available app('examYear')
```

### 2. Locked Year Protection
```
User tries to POST/PUT/DELETE → Middleware checks is_locked
If locked → Return 423 response
If unlocked → Proceed to controller
```

### 3. Publishing (Locking)
```
Admin clicks Publish → ExamYearService.publishResults()
→ Sets published_at = now() and is_locked = true
→ All further writes blocked
```

---

## Usage in Controllers

### Access Current Year
```php
class MarkEntryController extends Controller {
    public function store(Request $request) {
        $examYear = app('examYear');
        
        if ($examYear->isLocked()) {
            return response()->json(['error' => 'Locked'], 423);
        }
        
        Mark::create([
            'exam_year_id' => $examYear->id,
            'candidate_id' => $request->candidate_id,
            'marks' => $request->marks,
        ]);
    }
}
```

### Query by Year
```php
// Always include year in queries
$candidates = Candidate::where('exam_year_id', $examYear->id)->get();

// Or use scope
$candidates = Candidate::currentYear()->get();
```

### Check if Locked
```php
if ($examYear->isLocked()) {
    // Show read-only UI
} else {
    // Show edit UI
}
```

---

## Database Relationships

All exam tables now have:
```php
$table->foreignId('exam_year_id')
    ->constrained('exam_years')
    ->cascadeOnDelete();
```

Use trait in models:
```php
class Candidate extends Model {
    use BelongsToExamYear;
    
    public function examYear() { /* provided by trait */ }
}
```

---

## API Endpoints

| Method | Endpoint | Action |
|--------|----------|--------|
| POST | `/exam-years` | Create year |
| GET | `/exam-years` | List all |
| GET | `/exam-years/{id}` | View year |
| PUT | `/exam-years/{id}` | Update year |
| DELETE | `/exam-years/{id}` | Delete year |
| POST | `/exam-years/{id}/activate` | Activate year |
| POST | `/exam-years/{id}/publish` | Publish & lock |
| GET | `/api/exam-years` | JSON list |

---

## Blade Component

```blade
<x-exam-year-selector 
    :examYears="$examYears" 
    :selected="$selected" 
/>
```

Features:
- Year dropdown
- Lock status badge (🔒 Read-Only / ✓ Editable)
- Automatic page reload on year change
- Alpine.js reactive

---

## Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful GET/POST/PUT |
| 201 | Created | Year created |
| 400 | Bad Request | Invalid input |
| 403 | Forbidden | User not authorized |
| 422 | Unprocessable | Business logic violation |
| 423 | Locked | Write to locked year |

---

## Common Tasks

### Create New Year
```php
$service = app(ExamYearService::class);
$year = $service->create(['year_label' => '2025']);
```

### Activate Year
```php
$service->activate($year->id);
// Or via controller: POST /exam-years/{id}/activate
```

### Publish Results (Lock Year)
```php
$service->publishResults($year->id);
// After this, all writes blocked (423 response)
```

### Get Statistics
```php
$stats = $service->getStatistics($year->id);
// Returns: candidates_count, marks_count, results_count, etc.
```

---

## Testing

### Run Tests
```bash
php artisan test
```

### Test Locked Year Behavior
```php
$year = ExamYear::factory()->locked()->create();

// Try to write
$response = $this->post('/marks', ['exam_year_id' => $year->id]);
$response->assertStatus(423); // Locked
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "No exam years available" | Run seeder: `php artisan db:seed --class=MigrateExistingDataToExamYear` |
| "Cannot write to locked year" (expected) | This is correct behavior - year is locked |
| "Exam year not found" | Check if year exists in database |
| Component not showing | Ensure registered in config/view.php or use full path |

---

## Next Steps

1. ✅ Run migrations
2. ✅ Migrate existing data
3. ✅ Test year switching
4. ✅ Test locked year behavior
5. 📝 Update API documentation
6. 🎓 Train users on year selector
7. 📊 Add year-specific reports
8. 🔒 Implement fine-grained authorization

---

## Files Added

**Migrations:**
- `database/migrations/2024_02_01_000001_create_exam_years_table.php`
- `database/migrations/2024_02_01_000002_add_exam_year_id_to_exam_tables.php`

**Models:**
- `app/Models/ExamYear.php`
- `app/Models/Traits/BelongsToExamYear.php`

**Middleware:**
- `app/Http/Middleware/SetExamYearContext.php`

**Authorization:**
- `app/Policies/ExamYearPolicy.php`

**Services:**
- `app/Services/ExamYears/ExamYearService.php`

**Controllers:**
- `app/Http/Controllers/ExamYearController.php`

**Views:**
- `resources/views/components/exam-year-selector.blade.php`

**Seeders:**
- `database/seeders/MigrateExistingDataToExamYear.php`

**Documentation:**
- `IRMS_EXAM_YEARS_IMPLEMENTATION.md` (comprehensive)
- `IRMS_EXAM_YEARS_QUICK_START.md` (this file)

---

## Support

Refer to full documentation: `IRMS_EXAM_YEARS_IMPLEMENTATION.md`

