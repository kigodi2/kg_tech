# Fix: Subject Form Data Not Persisting to Database

## Problem Diagnosis

When you submit the Subject form:
1. ✅ Data appears in table (DOM/Alpine state)
2. ❌ Data NOT saved to database
3. ❌ Refresh page → data disappears
4. ❌ API call might be failing silently

## Root Cause

The `saveSubject()` method in Alpine.js component calls the API but data isn't being persisted. This could be:

1. **API endpoint not receiving the POST**
2. **Controller method not saving to database**
3. **Camel case vs snake case mismatch**
4. **No error logging to see what fails**

---

## Step 1: Enable Debug Logging

### Update saveSubject() Method

Find and replace the `saveSubject()` method in `/resources/views/exam-types/show.blade.php`:

```javascript
async saveSubject() {
    try {
        console.log('=== SAVE SUBJECT START ===');
        console.log('Subject form data:', this.subjectForm);
        console.log('Exam type:', this.examType.code);

        if (!this.subjectForm.code || !this.subjectForm.name || 
            !this.subjectForm.category || !this.subjectForm.writtenPapers) {
            console.error('Validation failed: Missing required fields');
            this.showMessage('Please fill in all required fields', 'error');
            return;
        }

        const payload = {
            code: this.subjectForm.code,
            name: this.subjectForm.name,
            category: this.subjectForm.category,
            writtenPapers: this.subjectForm.writtenPapers,
            hasPractical: this.subjectForm.hasPractical,
            hasProject: this.subjectForm.hasProject,
            description: this.subjectForm.description,
        };
        
        console.log('Sending payload:', JSON.stringify(payload, null, 2));

        const url = `/api/exam-types/${this.examType.code}/subjects`;
        console.log('POST to URL:', url);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);

        const data = await response.json();
        console.log('Response body:', data);

        if (response.ok) {
            console.log('SUCCESS: Subject saved');
            this.showMessage('Subject added successfully', 'success');
            this.showSubjectModal = false;
            this.filterSubjects();
            await this.loadSubjects();
        } else {
            console.error('ERROR: Status', response.status, 'Message:', data.message);
            this.showMessage(data.message || 'Error saving subject', 'error');
        }
    } catch (error) {
        console.error('EXCEPTION:', error);
        console.error('Stack:', error.stack);
        this.showMessage('Error saving subject: ' + error.message, 'error');
    }
    console.log('=== SAVE SUBJECT END ===');
}
```

### Test It:

1. Open browser DevTools (`F12`)
2. Go to Console tab
3. Fill the Subject form
4. Click "Add Subject"
5. **Check the console output** - you'll see where it fails

---

## Step 2: Verify API Routes

Check if the route is registered:

```bash
php artisan route:list | grep subjects
```

Should show:
```
POST   /api/exam-types/{code}/subjects        ExamTypeController@createSubject
PUT    /api/exam-types/{code}/subjects/{id}   ExamTypeController@updateSubject
DELETE /api/exam-types/{code}/subjects/{id}   ExamTypeController@deleteSubject
GET    /api/exam-types/{code}/subjects        ExamTypeController@getSubjects
```

If missing, check `/routes/api.php` and add:

```php
Route::post('/exam-types/{code}/subjects', [ExamTypeController::class, 'createSubject']);
Route::put('/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'updateSubject']);
Route::delete('/exam-types/{code}/subjects/{id}', [ExamTypeController::class, 'deleteSubject']);
Route::get('/exam-types/{code}/subjects', [ExamTypeController::class, 'getSubjects']);
```

---

## Step 3: Test API Directly

### Test with curl:

```bash
curl -X POST http://127.0.0.1:8001/api/exam-types/ACSEE/subjects \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token-here" \
  -d '{
    "code": "TEST001",
    "name": "Test Subject",
    "category": "SCIENCE",
    "writtenPapers": 2,
    "hasPractical": true,
    "hasProject": false,
    "description": "Test"
  }'
```

Should return:
```json
{
    "message": "Subject created",
    "data": { ... }
}
```

If error, check the response for the actual error message.

---

## Step 4: Check ExamTypeController::createSubject()

Verify the method exists and saves properly:

```php
public function createSubject(Request $request, $examTypeCode)
{
    $examType = ExamType::where('code', strtoupper($examTypeCode))->firstOrFail();
    
    $validated = $request->validate([
        'code' => 'required|unique:subjects',
        'name' => 'required|string',
        'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
        'writtenPapers' => 'required|integer|in:1,2,3',
        'hasPractical' => 'boolean',
        'hasProject' => 'boolean',
        'description' => 'nullable|string',
        'max_marks' => 'nullable|numeric',
        'is_active' => 'boolean',
    ]);

    // CRITICAL: Convert camelCase to snake_case
    $validated['written_papers'] = $validated['writtenPapers'];
    $validated['has_practical'] = $validated['hasPractical'] ?? false;
    $validated['has_project'] = $validated['hasProject'] ?? false;
    unset($validated['writtenPapers']);
    unset($validated['hasPractical']);
    unset($validated['hasProject']);

    $validated['exam_type_id'] = $examType->id;
    
    // DEBUG LOG
    \Log::info('Creating subject', $validated);

    try {
        $subject = Subject::create($validated);
        \Log::info('Subject created successfully', ['id' => $subject->id]);
        
        return response()->json([
            'message' => 'Subject created',
            'data' => $subject
        ], 201);
    } catch (\Exception $e) {
        \Log::error('Failed to create subject', ['error' => $e->getMessage()]);
        return response()->json([
            'message' => 'Error creating subject',
            'error' => $e->getMessage()
        ], 500);
    }
}
```

---

## Step 5: Check Laravel Logs

After attempting to save, check the logs:

```bash
tail -f storage/logs/laravel.log
```

Look for:
- `Creating subject...` (info)
- `Subject created successfully...` (info)
- `Failed to create subject...` (error)

This will show you exactly what went wrong.

---

## Step 6: Verify Database Migration

Make sure migration ran successfully:

```bash
php artisan migrate:status
```

Should show:
```
2026_01_29_150000_add_subject_fields_to_subjects_table  Yes
```

If not, the columns don't exist:

```bash
php artisan migrate
```

---

## Quick Checklist

- [ ] Enable debug logging in saveSubject()
- [ ] Open browser DevTools and check console output
- [ ] Run `php artisan route:list | grep subjects`
- [ ] Test API with curl command
- [ ] Check Laravel logs in storage/logs/
- [ ] Verify migration ran
- [ ] Check ExamTypeController::createSubject() method
- [ ] Verify camelCase to snake_case conversion

---

## Expected Output When Working

### Browser Console:
```
=== SAVE SUBJECT START ===
Subject form data: {code: "MATH001", name: "Mathematics", category: "SCIENCE", ...}
Exam type: ACSEE
Sending payload: {...}
POST to URL: /api/exam-types/ACSEE/subjects
Response status: 201
Response body: {message: "Subject created", data: {id: 1, ...}}
SUCCESS: Subject saved
=== SAVE SUBJECT END ===
```

### Laravel Log:
```
[2026-01-29 10:30:45] local.INFO: Creating subject {"code":"MATH001","name":"Mathematics","category":"SCIENCE",...}
[2026-01-29 10:30:46] local.INFO: Subject created successfully {"id":1}
```

### Database:
```sql
SELECT * FROM subjects WHERE code = 'MATH001';
-- Returns the subject with written_papers = 2
```

### Table Display:
```
Code     | Subject Name | Papers   | Category | Actions
---------|--------------|----------|----------|----------
MATH001  | Mathematics  | 2 Papers | SCIENCE  | 👁 ✏️ 🗑️
```

---

## If It's Still Not Working

1. **Check for 404 error**: Route might not exist
2. **Check for 422 error**: Validation might be failing (missing field)
3. **Check for 500 error**: Server error - check Laravel logs
4. **Check database**: `php artisan tinker` → `Subject::all()`
5. **Check migrations**: Columns might not exist

Once you run through these steps and send the console output, I can identify exactly where it's failing!
