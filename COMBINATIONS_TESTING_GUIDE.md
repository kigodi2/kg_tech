# Combinations Testing Guide

**Purpose:** Step-by-step testing procedures for the new Combinations implementation  
**Status:** Ready for Testing Phase  
**Estimated Time:** 2-3 hours

---

## Pre-Testing Checklist

- [ ] All code changes reviewed
- [ ] Backup of production database made
- [ ] Development environment ready
- [ ] PHP artisan working
- [ ] Database connection verified

---

## Phase 1: Database Testing

### 1.1 Run Migrations
```bash
cd /home/prosmart-technologies/SOL/irms

# Run migrations
php artisan migrate

# Check status
php artisan migrate:status
```

**Expected Result:** ✅ Two new migrations applied successfully

### 1.2 Verify Schema
```bash
php artisan tinker

# Check combinations table has new columns
> Schema::getColumns('combinations')

# Check pivot table exists
> Schema::hasTable('combination_subject')
```

**Expected Result:** ✅ New columns visible, pivot table exists

### 1.3 Run Data Migration
```bash
# This command migrates existing subjects string to relationships
php artisan migrate:combination-subjects

# Should output summary of migrated combinations
```

**Expected Result:** ✅ All combinations migrated without errors

### 1.4 Verify Relationships
```bash
php artisan tinker

# Get a combination with subjects
> $combo = Combination::with('subjects')->first();
> $combo->toArray()

# Should show subjects as array, not string!
# Check if relationships load
> $combo->subjects()->count()
> $combo->subjects()->first()
```

**Expected Result:** ✅ Relationships working, subjects loaded correctly

---

## Phase 2: Model Testing

### 2.1 Test Combination Model
```bash
php artisan tinker

# Test accessors
> $combo = Combination::first();
> $combo->subject_count  # Should return number
> $combo->subject_codes  # Should return comma-separated codes

# Test scopes
> Combination::byCategory('SCIENCE')->count()
> Combination::search('SC1')->get()

# Test methods
> $combo->hasSubject(1)  # true/false
> $combo->getSubjectsWithDetails()
```

**Expected Result:** ✅ All scopes and methods work correctly

### 2.2 Test Subject Model
```bash
php artisan tinker

# Test reverse relationship
> $subject = Subject::first();
> $subject->combinations()->count()  # Should work
> $subject->combinations()->first()
```

**Expected Result:** ✅ Reverse relationships working

### 2.3 Test Unique Constraint
```bash
php artisan tinker

# Try to create duplicate
> Combination::create(['exam_type_id' => 1, 'code' => 'SC1', 'category' => 'SCIENCE'])
> Combination::create(['exam_type_id' => 1, 'code' => 'SC1', 'category' => 'SCIENCE'])

# Second should fail with integrity constraint error
```

**Expected Result:** ✅ Second creation fails with constraint error

---

## Phase 3: API Testing

### 3.1 Test List Endpoint
```bash
# Terminal 1: Check server running
cd /home/prosmart-technologies/SOL/irms
php artisan serve --port=8001

# Terminal 2: Test API
curl -s http://localhost:8001/api/exam-types/ACSEE/combinations | jq .

# Should return JSON with:
# - success: true
# - data: array of combinations
# - pagination: page info
```

**Expected Result:** ✅ Returns paginated list with relationships

### 3.2 Test Create Endpoint
```bash
curl -X POST http://localhost:8001/api/exam-types/ACSEE/combinations \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: $(curl -s http://localhost:8001 | grep -oP 'csrf-token.*?content="\K[^"]+' | head -1)" \
  -d '{
    "code": "TEST1",
    "category": "SCIENCE",
    "description": "Test combination",
    "subject_ids": [1, 2, 3]
  }' | jq .

# Should return:
# - success: true
# - data: created combination with subjects
```

**Expected Result:** ✅ Combination created with subjects

### 3.3 Test Update Endpoint
```bash
# First get ID of the combination just created
COMBO_ID=$(curl -s http://localhost:8001/api/exam-types/ACSEE/combinations | jq '.data[0].id')

# Update it
curl -X PUT http://localhost:8001/api/exam-types/ACSEE/combinations/$COMBO_ID \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: ..." \
  -d '{
    "code": "TEST2",
    "category": "ARTS",
    "description": "Updated",
    "subject_ids": [4, 5]
  }' | jq .

# Should return updated combination
```

**Expected Result:** ✅ Combination updated successfully

### 3.4 Test Search
```bash
curl -s "http://localhost:8001/api/exam-types/ACSEE/combinations?search=TEST" | jq '.data[].code'

# Should return only combinations with TEST in code/description
```

**Expected Result:** ✅ Search filters correctly

### 3.5 Test Delete Endpoint
```bash
curl -X DELETE http://localhost:8001/api/exam-types/ACSEE/combinations/$COMBO_ID \
  -H "X-CSRF-TOKEN: ..." | jq .

# Should return success: true

# Verify deletion
curl -s http://localhost:8001/api/exam-types/ACSEE/combinations | jq '.data | length'
```

**Expected Result:** ✅ Combination deleted

### 3.6 Test Import Endpoint
```bash
# Create sample CSV file
cat > /tmp/combinations.csv << 'EOF'
CODE,CATEGORY,DESCRIPTION,SUBJECT_CODES
IMP1,SCIENCE,Imported combination,PHY,CHE,BIO
IMP2,ARTS,Another import,ENG,HIS,CIV
EOF

# Upload
curl -X POST http://localhost:8001/api/exam-types/ACSEE/combinations/import \
  -F "file=@/tmp/combinations.csv" \
  -H "X-CSRF-TOKEN: ..." | jq .

# Should show imported_count: 2
```

**Expected Result:** ✅ CSV imported successfully

### 3.7 Test Export Endpoint
```bash
curl -s http://localhost:8001/api/exam-types/ACSEE/combinations/export \
  -H "X-CSRF-TOKEN: ..." \
  > /tmp/export.csv

# Check file created
cat /tmp/export.csv

# Should have CSV format with CODE,CATEGORY,DESCRIPTION,SUBJECT_CODES
```

**Expected Result:** ✅ CSV exported correctly

---

## Phase 4: Frontend Testing

### 4.1 Test in Browser
```
1. Open: http://localhost:8001/exam-types/acsee
2. Look for Combinations tab in sidebar
3. Click "COMBINATIONS" button
```

**Expected Result:** ✅ Combinations tab loads with data

### 4.2 Test List Display
```
1. Verify table shows:
   - SN (sequence number)
   - CODE (combination code)
   - ALLOCATED SUBJECTS (list of subject codes)
   - CATEGORY (Arts/Science/Business badge)
   - ACTIONS (View, Edit, Delete buttons)
2. Verify pagination controls at bottom
```

**Expected Result:** ✅ All columns display correctly

### 4.3 Test Search
```
1. Type in search box: "SC"
2. Verify table filters to show matching combinations
3. Clear search box
4. Verify table resets to show all
```

**Expected Result:** ✅ Search filters data from server

### 4.4 Test Add Modal
```
1. Click "Add Combination" button
2. Modal opens
3. Enter:
   - Code: "NEW1"
   - Category: "SCIENCE" (dropdown)
   - Description: "New test"
   - Select subjects: Check physics, chemistry, biology
4. Click Save
```

**Expected Result:** ✅ Combination added, modal closes, list updates

### 4.5 Test View Modal
```
1. Click View (eye) icon on combination
2. Modal opens showing:
   - Code (readonly)
   - Category (readonly)
   - Description (readonly)
   - Subjects (list view)
3. Click "Edit" button
4. Switches to edit modal
```

**Expected Result:** ✅ View modal displays, Edit button works

### 4.6 Test Edit Modal
```
1. Click Edit (pencil) icon
2. Modal opens with data prefilled:
   - Code field has current code
   - Category dropdown shows current
   - Description populated
   - Subjects are checked
3. Change code to: "NEW2"
4. Change category to: "ARTS"
5. Click Save
```

**Expected Result:** ✅ Combination updated, list refreshes

### 4.7 Test Delete
```
1. Click Delete (trash) icon
2. Confirm dialog appears
3. Click OK
4. Combination removed from list
```

**Expected Result:** ✅ Combination deleted successfully

### 4.8 Test Pagination
```
1. Add multiple combinations (test with 30+)
2. Verify "25 per page" setting
3. Click "Next" page button
4. Verify new page loads
5. Click page number "2"
6. Verify page changes
```

**Expected Result:** ✅ Pagination works correctly

### 4.9 Test Import
```
1. Click "Import CSV" button
2. Select the /tmp/combinations.csv file created earlier
3. Wait for upload
4. Verify success message
5. Verify new combinations in list
```

**Expected Result:** ✅ CSV imported via modal, list updated

### 4.10 Test Export
```
1. Click "Export CSV" button
2. CSV file downloads
3. Open file in text editor
4. Verify format:
   - Header: CODE,CATEGORY,DESCRIPTION,SUBJECT_CODES
   - Data rows with all combinations
```

**Expected Result:** ✅ CSV exported correctly

---

## Phase 5: Data Integrity Testing

### 5.1 Verify Cascade Delete
```bash
php artisan tinker

# Get exam type ID
> $examType = ExamType::where('code', 'ACSEE')->first();
> $examTypeId = $examType->id;

# Get combination count before
> Combination::where('exam_type_id', $examTypeId)->count()

# Delete exam type (cascade should delete combinations)
> ExamType::where('code', 'TEST')->delete()

# Verify combinations still exist (different exam type)
> Combination::count()
```

**Expected Result:** ✅ No orphaned records

### 5.2 Verify Relationship Integrity
```bash
php artisan tinker

# Get all combinations
> $combos = Combination::with('subjects')->get();

# Verify all have subjects
> $combos->each(function($c) { 
    if($c->subjects->count() == 0) echo "Empty: {$c->code}\n";
  })

# Should output nothing (all have subjects)
```

**Expected Result:** ✅ All combinations have subjects

### 5.3 Verify No Duplicates
```bash
php artisan tinker

> Combination::select('exam_type_id', 'code')
    ->groupBy('exam_type_id', 'code')
    ->havingRaw('count(*) > 1')
    ->get()

# Should return empty collection
```

**Expected Result:** ✅ No duplicate codes per exam type

---

## Phase 6: Performance Testing

### 6.1 Load Test with Large Dataset
```bash
php artisan tinker

# Create 500 combinations (takes ~1 minute)
> for($i = 0; $i < 500; $i++) {
    $c = Combination::create([
        'exam_type_id' => 1,
        'code' => 'PERF' . $i,
        'category' => ['ARTS', 'SCIENCE', 'BUSINESS'][rand(0,2)],
    ]);
    $c->syncSubjects([1, 2, 3]);
  }

# Measure list load time
> $start = microtime(true);
> $combos = Combination::with('subjects')->paginate(25);
> echo (microtime(true) - $start) . " seconds";

# Should be < 0.5 seconds
```

**Expected Result:** ✅ Fast queries even with 500+ items

### 6.2 Search Performance
```bash
# Test search on 500 items
# Should return results in < 0.2 seconds
# Open browser DevTools Network tab
# Check API response time
```

**Expected Result:** ✅ Sub-second search response

---

## Checklist for Sign-Off

### Database
- [ ] Migrations run successfully
- [ ] Pivot table created correctly
- [ ] New columns added
- [ ] Unique constraint works
- [ ] Data migrated correctly

### API
- [ ] List endpoint returns data with relationships
- [ ] Create endpoint validates and saves
- [ ] Update endpoint works correctly
- [ ] Delete endpoint removes records
- [ ] Search/filter works server-side
- [ ] Pagination works correctly
- [ ] Import/export endpoints function
- [ ] Error handling returns proper responses

### Frontend
- [ ] Combinations tab displays
- [ ] Table shows all columns correctly
- [ ] Add button opens modal
- [ ] View modal displays read-only
- [ ] Edit modal allows changes
- [ ] Delete removes from list
- [ ] Search filters data
- [ ] Pagination controls work
- [ ] Import/export buttons work
- [ ] Success/error messages display

### Data Integrity
- [ ] Cascade deletes work
- [ ] No orphaned records
- [ ] No duplicate combinations
- [ ] All subjects load correctly
- [ ] Relationships intact

### Performance
- [ ] List loads in < 0.5s
- [ ] Search responds in < 0.2s
- [ ] Pagination smooth
- [ ] Export file generated quickly
- [ ] Import processes quickly

---

## Rollback Procedure

If issues found during testing:

```bash
# Rollback migrations
php artisan migrate:rollback

# Or specific migration
php artisan migrate:rollback --step=2

# Restore database from backup
# (Use your backup tool)

# Revert code changes
git revert <commit-hash>
```

---

## Common Issues & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| 404 on API call | ExamType doesn't exist | Verify code=ACSEE exists |
| Subject IDs invalid | Subject doesn't exist | Verify subject IDs are correct |
| Search not working | Spaces in search string | Trim input before sending |
| Modal won't close | JavaScript error | Check browser console |
| Import fails | CSV format wrong | Use provided template |
| Pagination error | Page out of range | Reset to page 1 |

---

## Testing Sign-Off

**Tester Name:** _______________  
**Date:** _______________  
**Total Tests Run:** _______________  
**Tests Passed:** _______________  
**Tests Failed:** _______________  
**Issues Found:** _______________  

**Ready for Deployment:** [ ] YES [ ] NO

**Notes:**  
_________________________________  
_________________________________  
_________________________________

---

## Next Steps After Testing

1. ✅ All tests pass → Ready for production deployment
2. ❌ Issues found → Fix and retest
3. ⚠️ Performance concerns → Optimize queries
4. 📝 Documentation → Update with findings

**Estimated Testing Time:** 2-3 hours  
**Ready to Start:** Yes ✅
