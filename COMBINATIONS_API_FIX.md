# Combinations API Response Format Fix

## Issue
When viewing the subjects page, an error message banner stating "Error loading combinations" was appearing at the top right of the screen.

## Root Cause
The `getCombinations()` API endpoint in the `ExamTypeController` was returning a simple response format:
```json
{
  "data": [...]
}
```

However, the frontend JavaScript expected a more comprehensive response with pagination:
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 25,
    "total": 0,
    "total_pages": 0
  }
}
```

When the frontend tried to access `data.success`, it was `undefined`, causing an error condition on line 1092 of the view:
```javascript
if (data.success) {
    // ...
} else {
    throw new Error(data.message || 'Failed to load combinations');
}
```

## Fix Applied
Updated `ExamTypeController::getCombinations()` to:
1. Return the expected response format with `success: true`
2. Add pagination support with `page` and `page_size` query parameters
3. Add search functionality with the `search` parameter
4. Order results by creation date (newest first)

### Changes Made
**File**: `app/Http/Controllers/ExamTypeController.php`

**Before**:
```php
public function getCombinations($examTypeCode)
{
    $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
    $combinations = $examType->combinations()->get();
    return response()->json(['data' => $combinations]);
}
```

**After**:
```php
public function getCombinations($examTypeCode)
{
    $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
    $page = request()->get('page', 1);
    $pageSize = request()->get('page_size', 25);
    $search = request()->get('search', '');

    $query = $examType->combinations();
    
    if ($search) {
        $query->where('code', 'like', "%{$search}%")
              ->orWhere('subjects', 'like', "%{$search}%");
    }

    $total = $query->count();
    $combinations = $query->orderBy('created_at', 'desc')
                          ->skip(($page - 1) * $pageSize)
                          ->take($pageSize)
                          ->get();

    return response()->json([
        'success' => true,
        'data' => $combinations,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $pageSize,
            'total' => $total,
            'total_pages' => ceil($total / $pageSize),
        ]
    ]);
}
```

## Impact
- Error message will no longer appear when loading the exam types page
- Combinations will load properly when the ACSEE tab is accessed
- Frontend pagination and search will work correctly for combinations
- Consistency with other API endpoints that return paginated data

## Testing
1. Navigate to an ACSEE exam type
2. View the Subjects tab - no error message should appear
3. Click on Combinations tab - combinations should load without errors
4. Search functionality should work if combinations exist
