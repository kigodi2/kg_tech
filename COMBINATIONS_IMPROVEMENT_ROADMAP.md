# Combinations Implementation Improvement Roadmap

## Overview
This roadmap outlines the steps to improve the Combinations management system in the current Laravel/Alpine.js implementation by adopting best practices from the Django backup system.

---

## Phase 1: Database Schema Enhancement (Critical)

### 1.1 Create Pivot Table
```php
// Create migration: create_combination_subject_table
Schema::create('combination_subject', function (Blueprint $table) {
    $table->id();
    $table->foreignId('combination_id')->constrained('combinations')->onDelete('cascade');
    $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
    $table->timestamps();
    
    $table->unique(['combination_id', 'subject_id']);
    $table->index(['combination_id', 'subject_id']);
});
```

### 1.2 Update Combinations Table
```php
// Modify existing migration
Schema::table('combinations', function (Blueprint $table) {
    // Add missing fields
    $table->string('category')->default('ARTS')->comment('ARTS, SCIENCE, BUSINESS');
    $table->text('description')->nullable();
    
    // Add unique constraint
    $table->unique(['exam_type_id', 'code']);
    
    // Remove or deprecate old subjects column
    // $table->dropColumn('subjects');  // After migration
});
```

### 1.3 Data Migration Script
```php
// Create command: MigrateCombinationSubjects
class MigrateCombinationSubjects extends Command {
    public function handle() {
        Combination::all()->each(function ($combination) {
            // Parse existing subjects string
            if (empty($combination->subjects)) return;
            
            $subjectCodes = array_map('trim', explode(',', $combination->subjects));
            
            foreach ($subjectCodes as $code) {
                $subject = Subject::where('code', $code)->first();
                if ($subject) {
                    $combination->subjects()->syncWithoutDetaching($subject->id);
                }
            }
        });
        
        $this->info('Migration complete');
    }
}
```

---

## Phase 2: Model Enhancement

### 2.1 Update Combination Model
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Combination extends Model {
    protected $table = 'combinations';
    
    protected $fillable = [
        'exam_type_id',
        'code',
        'category',
        'description',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    protected $appends = ['subject_count'];
    
    // ==================== RELATIONSHIPS ====================
    
    public function examType(): BelongsTo {
        return $this->belongsTo(ExamType::class);
    }
    
    public function subjects(): BelongsToMany {
        return $this->belongsToMany(
            Subject::class,
            'combination_subject',
            'combination_id',
            'subject_id'
        )->withTimestamps();
    }
    
    // ==================== SCOPES ====================
    
    public function scopeByCategory($query, $category) {
        return $query->where('category', $category);
    }
    
    public function scopeByExamType($query, $examTypeId) {
        return $query->where('exam_type_id', $examTypeId);
    }
    
    public function scopeSearch($query, $search) {
        return $query->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
    }
    
    // ==================== ACCESSORS ====================
    
    public function getSubjectCountAttribute() {
        return $this->subjects()->count();
    }
    
    public function getSubjectCodesAttribute() {
        return $this->subjects()->pluck('code')->join(', ');
    }
    
    // ==================== METHODS ====================
    
    public function syncSubjects($subjectIds) {
        $this->subjects()->sync($subjectIds ?? []);
    }
    
    public function hasSubject($subjectId) {
        return $this->subjects()->where('subject_id', $subjectId)->exists();
    }
}
```

### 2.2 Update Subject Model (if needed)
```php
public function combinations(): BelongsToMany {
    return $this->belongsToMany(
        Combination::class,
        'combination_subject',
        'subject_id',
        'combination_id'
    )->withTimestamps();
}
```

---

## Phase 3: API Layer Enhancement

### 3.1 Create CombinationController
```php
<?php
namespace App\Http\Controllers\Api;

use App\Models\ExamType;
use App\Models\Combination;
use App\Http\Requests\StoreCombinationRequest;
use App\Http\Requests\UpdateCombinationRequest;
use Illuminate\Http\JsonResponse;

class CombinationController extends Controller {
    
    // GET /api/exam-types/{code}/combinations
    public function index($code) {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();
            
            $combinations = $examType->combinations()
                ->with('subjects')
                ->when(request('search'), function ($query) {
                    $query->search(request('search'));
                })
                ->when(request('category'), function ($query) {
                    $query->where('category', request('category'));
                })
                ->orderBy('category')
                ->orderBy('code')
                ->paginate(request('page_size', 25));
            
            return response()->json([
                'success' => true,
                'data' => $combinations->items(),
                'pagination' => [
                    'page' => $combinations->currentPage(),
                    'per_page' => $combinations->perPage(),
                    'total' => $combinations->total(),
                    'total_pages' => $combinations->lastPage(),
                    'has_previous' => $combinations->currentPage() > 1,
                    'has_next' => $combinations->hasMorePages(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading combinations: ' . $e->getMessage()
            ], 400);
        }
    }
    
    // POST /api/exam-types/{code}/combinations
    public function store($code, StoreCombinationRequest $request) {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();
            
            $combination = $examType->combinations()->create([
                'code' => $request->code,
                'category' => $request->category,
                'description' => $request->description,
            ]);
            
            // Attach subjects
            if ($request->has('subject_ids')) {
                $combination->syncSubjects($request->subject_ids);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Combination created successfully',
                'data' => $combination->load('subjects')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating combination: ' . $e->getMessage()
            ], 400);
        }
    }
    
    // PUT /api/exam-types/{code}/combinations/{id}
    public function update($code, $id, UpdateCombinationRequest $request) {
        try {
            $combination = Combination::findOrFail($id);
            
            $combination->update($request->validated());
            
            if ($request->has('subject_ids')) {
                $combination->syncSubjects($request->subject_ids);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Combination updated successfully',
                'data' => $combination->load('subjects')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating combination: ' . $e->getMessage()
            ], 400);
        }
    }
    
    // DELETE /api/exam-types/{code}/combinations/{id}
    public function destroy($code, $id) {
        try {
            $combination = Combination::findOrFail($id);
            $combination->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Combination deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting combination: ' . $e->getMessage()
            ], 400);
        }
    }
    
    // POST /api/exam-types/{code}/combinations/import
    public function import($code) {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();
            $file = request()->file('file');
            
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file provided'
                ], 400);
            }
            
            $importCount = 0;
            $errors = [];
            
            $rows = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_shift($rows);
            
            foreach ($rows as $row) {
                try {
                    $data = array_combine($headers, $row);
                    
                    $combination = $examType->combinations()->updateOrCreate(
                        ['code' => $data['CODE'] ?? ''],
                        [
                            'category' => $data['CATEGORY'] ?? 'ARTS',
                            'description' => $data['DESCRIPTION'] ?? ''
                        ]
                    );
                    
                    // Attach subjects by code
                    if (!empty($data['SUBJECT_CODES'])) {
                        $codes = array_map('trim', explode(',', $data['SUBJECT_CODES']));
                        $subjects = Subject::whereIn('code', $codes)->get();
                        $combination->syncSubjects($subjects->pluck('id'));
                    }
                    
                    $importCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row: {$errors}: {$e->getMessage()}";
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "$importCount combinations imported",
                'imported_count' => $importCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing combinations: ' . $e->getMessage()
            ], 400);
        }
    }
    
    // GET /api/exam-types/{code}/combinations/export
    public function export($code) {
        try {
            $examType = ExamType::where('code', $code)->firstOrFail();
            $combinations = $examType->combinations()->with('subjects')->get();
            
            $csv = "CODE,CATEGORY,DESCRIPTION,SUBJECT_CODES\n";
            
            foreach ($combinations as $combo) {
                $subjectCodes = $combo->subjects()->pluck('code')->join(',');
                $csv .= "\"{$combo->code}\",\"{$combo->category}\",\"{$combo->description}\",\"{$subjectCodes}\"\n";
            }
            
            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"combinations_{$code}.csv\"");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting combinations: ' . $e->getMessage()
            ], 400);
        }
    }
}
```

### 3.2 Create Form Requests
```php
// StoreCombinationRequest
class StoreCombinationRequest extends FormRequest {
    public function rules() {
        return [
            'code' => 'required|string|max:50|unique:combinations,code',
            'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
            'description' => 'nullable|string',
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id'
        ];
    }
}

// UpdateCombinationRequest
class UpdateCombinationRequest extends FormRequest {
    public function rules() {
        return [
            'code' => "required|string|max:50|unique:combinations,code,{$this->route('id')}",
            'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
            'description' => 'nullable|string',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => 'integer|exists:subjects,id'
        ];
    }
}
```

---

## Phase 4: Frontend Update

### 4.1 Update Alpine Component
```javascript
x-data="examTypeManager()" 
x-init="init()"

// In component:
async loadCombinations() {
    const response = await fetch(
        `/api/exam-types/${this.examType.code}/combinations?` + 
        `page=${this.currentPage}&page_size=${this.pageSize}`
    );
    const data = await response.json();
    
    if (data.success) {
        this.combinations = data.data;
        this.filteredCombinations = this.combinations;
        this.totalPages = data.pagination.total_pages;
        this.totalCount = data.pagination.total;
    }
}

async saveCombination() {
    const url = this.editingCombinationId 
        ? `/api/exam-types/${this.examType.code}/combinations/${this.editingCombinationId}`
        : `/api/exam-types/${this.examType.code}/combinations`;
    
    const payload = {
        ...this.combinationForm,
        subject_ids: this.selectedSubjectIds  // Array of IDs, not string
    };
    
    const response = await fetch(url, {
        method: this.editingCombinationId ? 'PUT' : 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(payload)
    });
    
    const data = await response.json();
    if (data.success) {
        this.showMessage('Combination saved successfully', 'success');
        this.showCombinationModal = false;
        await this.loadCombinations();
    }
}

async importCombinationsCSV(event) {
    const file = event.target.files[0];
    const formData = new FormData();
    formData.append('file', file);
    
    const response = await fetch(
        `/api/exam-types/${this.examType.code}/combinations/import`,
        {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        }
    );
    
    const data = await response.json();
    if (data.success) {
        this.showMessage(`${data.imported_count} combinations imported`, 'success');
        await this.loadCombinations();
    }
}

async exportCombinationsCSV() {
    const response = await fetch(
        `/api/exam-types/${this.examType.code}/combinations/export`
    );
    
    if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `combinations_${this.examType.code}.csv`;
        a.click();
    }
}
```

---

## Phase 5: Routes and Configuration

### 5.1 Update Routes
```php
// routes/api.php
Route::middleware('auth')->prefix('exam-types/{code}')->group(function () {
    Route::prefix('combinations')->group(function () {
        Route::get('/', [CombinationController::class, 'index']);
        Route::post('/', [CombinationController::class, 'store']);
        Route::put('{id}', [CombinationController::class, 'update']);
        Route::delete('{id}', [CombinationController::class, 'destroy']);
        Route::post('import', [CombinationController::class, 'import']);
        Route::get('export', [CombinationController::class, 'export']);
    });
});
```

---

## Phase 6: Testing

### 6.1 Unit Tests
```php
class CombinationTest extends TestCase {
    public function test_combination_has_many_subjects() {
        $combination = Combination::factory()->create();
        $subjects = Subject::factory(3)->create();
        $combination->syncSubjects($subjects->pluck('id'));
        
        $this->assertCount(3, $combination->subjects);
    }
    
    public function test_combination_code_is_unique_per_exam_type() {
        $examType = ExamType::factory()->create();
        $combination1 = Combination::factory()->create([
            'exam_type_id' => $examType->id,
            'code' => 'SC1'
        ]);
        
        $this->expectException(QueryException::class);
        Combination::factory()->create([
            'exam_type_id' => $examType->id,
            'code' => 'SC1'
        ]);
    }
}
```

### 6.2 API Tests
```php
class CombinationApiTest extends TestCase {
    public function test_list_combinations_with_pagination() {
        $examType = ExamType::factory()->create();
        Combination::factory(30)->create(['exam_type_id' => $examType->id]);
        
        $response = $this->getJson("/api/exam-types/{$examType->code}/combinations?page_size=10");
        
        $response->assertJson(['success' => true]);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(3, $response->json('pagination.total_pages'));
    }
    
    public function test_create_combination_with_subjects() {
        $examType = ExamType::factory()->create();
        $subjects = Subject::factory(3)->create();
        
        $response = $this->postJson(
            "/api/exam-types/{$examType->code}/combinations",
            [
                'code' => 'SC1',
                'category' => 'SCIENCE',
                'description' => 'Science combination',
                'subject_ids' => $subjects->pluck('id')->toArray()
            ]
        );
        
        $response->assertStatus(201);
        $this->assertCount(3, Combination::first()->subjects);
    }
}
```

---

## Implementation Timeline

| Phase | Duration | Priority | Status |
|-------|----------|----------|--------|
| Phase 1: Database | 2-3 days | **CRITICAL** | Pending |
| Phase 2: Models | 1-2 days | **CRITICAL** | Pending |
| Phase 3: API | 3-4 days | **HIGH** | Pending |
| Phase 4: Frontend | 2-3 days | **HIGH** | Pending |
| Phase 5: Routes | 1 day | **MEDIUM** | Pending |
| Phase 6: Testing | 2-3 days | **HIGH** | Pending |

**Total Estimated Time:** 11-16 days

---

## Success Criteria

- ✓ All combinations use ManyToMany relationships
- ✓ Category validation enforced
- ✓ Unique code constraint per exam type
- ✓ API endpoints support import/export
- ✓ Server-side pagination working
- ✓ Server-side search/filter working
- ✓ All tests passing
- ✓ Frontend modal system remains responsive
- ✓ Backward compatibility maintained

---

## Rollback Plan

If issues occur:
1. Keep old `subjects` column during phase 1
2. Dual-write to both old and new during phase 2-3
3. Gradually migrate existing combinations
4. Only remove old column after full verification

---

## Post-Implementation

After successful implementation:
1. Document new relationships and usage
2. Update API documentation
3. Train team on new patterns
4. Schedule deprecation of old patterns
5. Plan cleanup of technical debt
