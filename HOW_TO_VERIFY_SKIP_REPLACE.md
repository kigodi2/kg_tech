# How to Manually Verify Skip/Replace Implementation

**Quick Start**: 10-15 minutes to verify the core functionality works

---

## Step 1: Setup Test Data (2 min)

Open a new terminal and run:

```bash
cd /home/prosmart-technologies/SOL/irms
php artisan tinker
```

Then paste this:

```php
// Clean up old test data
\App\Models\Candidate::whereIn('candidate_id', ['S0754-0001', 'S0754-0002', 'S0754-0003'])->delete();

// Get/create school
$school = \App\Models\School::firstOrCreate(['code' => 'S0754'], ['name' => 'Test S0754', 'district_id' => 1]);

// Create two existing candidates
$c1 = \App\Models\Candidate::create([
    'school_id' => $school->id, 'candidate_id' => 'S0754-0001', 'full_name' => 'JOHN DOE',
    'gender' => 'M', 'exam_type' => 'ACSEE', 'combination' => 'PCM', 'candidate_type' => 'SCHOOL',
    'status' => 'registered', 'is_active' => true,
]);

$c2 = \App\Models\Candidate::create([
    'school_id' => $school->id, 'candidate_id' => 'S0754-0002', 'full_name' => 'JANE SMITH',
    'gender' => 'F', 'exam_type' => 'ACSEE', 'combination' => 'HGE', 'candidate_type' => 'SCHOOL',
    'status' => 'registered', 'is_active' => true,
]);

echo "Setup complete. Created:\n";
echo "  - S0754-0001: JOHN DOE\n";
echo "  - S0754-0002: JANE SMITH\n";
exit
```

---

## Step 2: Create Test CSV Files (2 min)

In a new terminal:

```bash
# File B: Mixed (has new + 2 existing)
cat > /tmp/test_file_b.csv << 'EOF'
candidate_id,full_name,gender,school_code,combination,exam_type,exam_year,candidate_type
S0754-0001,JOHN PETER DOE,M,S0754,PCM,ACSEE,2026,SCHOOL
S0754-0003,NEW STUDENT,M,S0754,CBE,ACSEE,2026,SCHOOL
S0754-0002,JANE MARIE SMITH,F,S0754,HGE,ACSEE,2026,SCHOOL
EOF

cat /tmp/test_file_b.csv
```

---

## Step 3: Test SKIP Mode Validation (2 min)

```bash
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip" | jq .
```

**Expected output:**
```json
{
  "success": true,
  "total_rows": 3,
  "create_count": 1,
  "update_count": 0,
  "skip_count": 2,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0001", "status": "SKIP"},
    {"row_number": 2, "candidate_id": "S0754-0003", "status": "NEW"},
    {"row_number": 3, "candidate_id": "S0754-0002", "status": "SKIP"}
  ]
}
```

**✓ Verify:**
- `create_count = 1`
- `skip_count = 2`
- `update_count = 0`
- `can_import = true`
- Row 1 & 3 status = "SKIP"
- Row 2 status = "NEW"

---

## Step 4: Test REPLACE Mode Validation (2 min)

```bash
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace" | jq .
```

**Expected output:**
```json
{
  "success": true,
  "total_rows": 3,
  "create_count": 1,
  "update_count": 2,
  "skip_count": 0,
  "error_count": 0,
  "can_import": true,
  "rows": [
    {"row_number": 1, "candidate_id": "S0754-0001", "status": "REPLACE"},
    {"row_number": 2, "candidate_id": "S0754-0003", "status": "NEW"},
    {"row_number": 3, "candidate_id": "S0754-0002", "status": "REPLACE"}
  ]
}
```

**✓ Verify:**
- `create_count = 1`
- `update_count = 2` (this is the key difference!)
- `skip_count = 0`
- `can_import = true`
- Row 1 & 3 status = "REPLACE"
- Row 2 status = "NEW"

---

## Step 5: Commit SKIP Mode (2 min)

```bash
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/commit \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=skip" | jq .
```

**Expected output:**
```json
{
  "success": true,
  "created_count": 1,
  "updated_count": 0,
  "skipped_count": 2,
  "failed_count": 0,
  "errors": []
}
```

**✓ Verify:**
- `created_count = 1` (S0754-0003 created)
- `updated_count = 0` (nothing updated)
- `skipped_count = 2` (S0754-0001 and S0754-0002 not touched)

Now check database:

```bash
php artisan tinker
>>> \App\Models\Candidate::where('candidate_id', 'S0754-0001')->first()->full_name
# Should be: "JOHN DOE" (NOT updated!)
```

---

## Step 6: Reset Data and Test REPLACE Mode Commit (3 min)

Reset candidates:

```bash
php artisan tinker
\App\Models\Candidate::whereIn('candidate_id', ['S0754-0001', 'S0754-0002', 'S0754-0003'])->delete();

$school = \App\Models\School::where('code', 'S0754')->first();
$c1 = \App\Models\Candidate::create([
    'school_id' => $school->id, 'candidate_id' => 'S0754-0001', 'full_name' => 'JOHN DOE',
    'gender' => 'M', 'exam_type' => 'ACSEE', 'combination' => 'PCM', 'candidate_type' => 'SCHOOL',
    'status' => 'registered', 'is_active' => true,
]);
$c2 = \App\Models\Candidate::create([
    'school_id' => $school->id, 'candidate_id' => 'S0754-0002', 'full_name' => 'JANE SMITH',
    'gender' => 'F', 'exam_type' => 'ACSEE', 'combination' => 'HGE', 'candidate_type' => 'SCHOOL',
    'status' => 'registered', 'is_active' => true,
]);
exit
```

Now commit with REPLACE mode:

```bash
curl -s -X POST http://127.0.0.1:8000/api/candidates/import/commit \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" \
  -F "on_exists_mode=replace" | jq .
```

**Expected output:**
```json
{
  "success": true,
  "created_count": 1,
  "updated_count": 2,
  "skipped_count": 0,
  "failed_count": 0,
  "errors": []
}
```

Check database:

```bash
php artisan tinker
>>> \App\Models\Candidate::where('candidate_id', 'S0754-0001')->first()->full_name
# Should be: "JOHN PETER DOE" (UPDATED!)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0002')->first()->full_name
# Should be: "JANE MARIE SMITH" (UPDATED!)

>>> \App\Models\Candidate::where('candidate_id', 'S0754-0003')->exists()
# Should be: true (newly created)

exit
```

---

## Step 7: Test Backward Compatibility (1 min)

Test without on_exists_mode parameter (should default to skip):

```bash
php artisan tinker
\App\Models\Candidate::whereIn('candidate_id', ['S0754-0001', 'S0754-0002'])->update(['full_name' => 'RESET NAME']);
exit

curl -s -X POST http://127.0.0.1:8000/api/candidates/import/validate \
  -F "file=@/tmp/test_file_b.csv" \
  -F "exam_year=2026" \
  -F "exam_type=ACSEE" | jq '.skip_count'
```

**Expected output:**
```
2
```

✓ Verify: Defaults to skip mode correctly

---

## Quick Summary

If all steps show expected output, the implementation is working correctly:

| Step | Mode | Expected Result | ✓ Verified |
|------|------|-----------------|-----------|
| 3 | SKIP validate | create=1, skip=2, update=0 | ⏳ |
| 4 | REPLACE validate | create=1, skip=0, update=2 | ⏳ |
| 5 | SKIP commit | created=1, updated=0, skipped=2 | ⏳ |
| 5 | SKIP DB check | S0754-0001 name unchanged | ⏳ |
| 6 | REPLACE commit | created=1, updated=2, skipped=0 | ⏳ |
| 6 | REPLACE DB check | S0754-0001 name updated | ⏳ |
| 7 | No param | Defaults to skip | ⏳ |

---

## Testing the UI

Open http://127.0.0.1:8000/registration/candidates

1. Click **Tools** → **Import CSV**
2. See **Step 1**:
   - [ ] Two radio buttons: "Skip existing" and "Replace existing"
   - [ ] Can toggle between them
3. Upload `/tmp/test_file_b.csv`
4. Click **Validate**
5. See **Step 2**:
   - [ ] Summary cards show: Total=3, New=1, Will Skip/Update (depending on choice)
   - [ ] Import Plan table shows status badges
   - [ ] Import button shows "Import X Records" (where X = create + update)
6. If Replace selected: See orange warning
7. Click **Import**
8. See success message with counts

---

## Common Issues & Fixes

### Issue: `candidate_id already exists` error
**Cause**: Old code treating existence as error  
**Fix**: Implementation should mark as SKIP or REPLACE, not ERROR

### Issue: API returns `404`
**Cause**: Routes not loaded  
**Fix**: Ensure routes/web.php has CandidateImportController routes

### Issue: Response missing `skip_count`, `update_count`, `create_count`
**Cause**: Service not returning new fields  
**Fix**: Check CandidateImportService::validateCSV() returns correct response

### Issue: Names not updating in Replace mode
**Cause**: updateCandidate() method missing or broken  
**Fix**: Verify updateCandidate() in service updates full_name field

### Issue: UI not showing mode radio buttons
**Cause**: Blade template not updated  
**Fix**: Check candidates.blade.php for onExistsMode state and radio input

---

## Success Criteria

✅ **You've successfully implemented Skip/Replace if:**

1. **SKIP mode**: Existing candidates are NOT modified, only new ones created
2. **REPLACE mode**: Existing candidates ARE updated (name, gender, school)
3. **API**: Returns correct counts (create, update, skip)
4. **UI**: Shows mode selection, summary cards, status badges
5. **Safety**: Marks/registrations never deleted
6. **Backward compat**: Works without on_exists_mode parameter

---

**Done?** You're ready for full TEST PLAN verification in `SKIP_REPLACE_VERIFICATION_CHECKLIST.md`

