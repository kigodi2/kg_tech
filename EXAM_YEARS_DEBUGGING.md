# Exam Years - Debugging Guide

**Issue**: Exam years not appearing in dropdown

## Quick Diagnostics

### 1. Check if exam years exist in database
```php
php artisan tinker

>>> \App\Models\ExamYear::all()
// Should return exam year records
// If empty, you need to create test data
```

### 2. Check API endpoint
```bash
curl http://localhost/api/exam-years

# Should return:
# {
#   "exam_years": [
#     {"id": 1, "year_label": "2025", ...},
#     ...
#   ]
# }
```

### 3. Check browser console
```javascript
fetch('/api/exam-years')
  .then(r => r.json())
  .then(d => console.log(d))

// Should log the exam years
```

## If Exam Years Not Returning

### Create Test Data
```php
php artisan tinker

>>> \App\Models\ExamYear::create([
    'year_label' => '2025',
    'is_active' => true,
    'is_locked' => false,
])

>>> \App\Models\ExamYear::create([
    'year_label' => '2024',
    'is_active' => false,
    'is_locked' => false,
])

>>> exit
```

### Verify Creation
```php
php artisan tinker
>>> \App\Models\ExamYear::all()
// Should now show both records
>>> exit
```

## If Still Not Working

### Check JavaScript Errors
1. Open browser DevTools (F12)
2. Go to Console tab
3. Look for red error messages
4. Check Network tab:
   - Find `/api/exam-years` request
   - Check if it returns 200 OK
   - Check response body

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Reload Page
- Hard refresh: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
- This clears browser cache

## Response Format Expected

```json
{
  "exam_years": [
    {
      "id": 1,
      "year_label": "2025",
      "is_active": true,
      "is_locked": false,
      "published_at": null,
      "locked_at": null,
      "created_at": "2026-02-01T...",
      "updated_at": "2026-02-01T..."
    }
  ]
}
```

The key `"exam_years"` (plural) is important - frontend looks for this exact key.

## Changes Made

1. **Added endpoint**: `GET /api/exam-years`
2. **Fixed filter**: School dropdown now enables after year selected
3. **Simplified logic**: Shows all schools (not filtered by year availability)

## Next Steps After Fixing

1. Verify exam years appear in dropdown
2. Select an exam year
3. School dropdown should enable
4. Select a school
5. Upload and test import

---

**If still not working, run the diagnostics above and share the output.**
