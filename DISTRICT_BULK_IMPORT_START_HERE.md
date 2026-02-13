# District Bulk Import - START HERE

## 🚀 Quick Start (5 minutes)

### 1. Verify Installation
```bash
# Check migrations are applied
php artisan migrate:status | grep bulk_import

# Verify queue worker running
php artisan queue:work --stop-when-empty
```

### 2. Create Test Data
```bash
# In tinker
php artisan tinker

# Create test district
$district = District::factory()->create([
    'code' => 'TEST_D',
    'name' => 'Test District'
]);

# Create 2 test schools
$s1 = School::factory()->create([
    'district_id' => $district->id,
    'code' => 'S0001',
    'name' => 'Test School 1'
]);

$s2 = School::factory()->create([
    'district_id' => $district->id,
    'code' => 'S0002',
    'name' => 'Test School 2'
]);

# Create exam year
$year = ExamYear::factory()->create(['year' => 2025]);

# Create test candidates with registrations
$c1 = Candidate::factory()->create();
$r1 = CandidateExamRegistration::factory()->create([
    'candidate_id' => $c1->id,
    'exam_year_id' => $year->id,
    'school_id' => $s1->id
]);
```

### 3. Create Test ZIP
```bash
# Create test ZIP structure
mkdir -p /tmp/test_district/S0001_Test\ School\ 1
mkdir -p /tmp/test_district/S0002_Test\ School\ 2

# Create manifest.json
cat > /tmp/test_district/manifest.json << 'EOF'
{
  "exam": "ACSEE",
  "exam_year": 2025,
  "scope": {
    "type": "district",
    "code": "TEST_D"
  },
  "generated_at": "2025-02-01T10:00:00Z",
  "generated_by": {
    "user_id": 1,
    "role": "district_officer"
  },
  "schools": [
    {
      "school_code": "S0001",
      "school_name": "Test School 1",
      "candidates": 1,
      "subjects": [
        {
          "code": "PHY",
          "papers": ["P1", "P2"],
          "candidates": 1,
          "checksum": "sha256:aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
        }
      ]
    },
    {
      "school_code": "S0002",
      "school_name": "Test School 2",
      "candidates": 0,
      "subjects": []
    }
  ],
  "zip_checksum": "sha256:bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb"
}
EOF

# Create CSV for school 1
cat > /tmp/test_district/S0001_Test\ School\ 1/PHY.csv << 'EOF'
index_number,sex,papers,paper_1,paper_2,paper_3
CAND0001,M,P1;P2,45,52,
EOF

# Create ZIP
cd /tmp/test_district
zip -r test_district.zip . 
cp test_district.zip ~/

echo "✅ Test ZIP created at ~/test_district.zip"
```

### 4. Test Upload via API
```bash
# Login first (get your auth token)
# Then upload ZIP

curl -X POST http://localhost:8000/api/bulk-import/preview \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "zip_file=@~/test_district.zip"

# Response should show scope_type: "district"
```

### 5. Start Import
```bash
curl -X POST http://localhost:8000/api/bulk-import/district/start \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "district_id": 1,
    "exam_year_id": 1
  }'

# Response:
# {
#   "success": true,
#   "bulk_import_id": 100,
#   "message": "District-level bulk import started"
# }
```

### 6. Monitor Progress
```bash
curl -X GET http://localhost:8000/api/bulk-import/100/progress \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response shows schools, progress percentage, status
```

---

## 📚 Documentation Index

### By Role

**District Officer** (Who uploads)
→ Read: `DISTRICT_BULK_IMPORT_QUICK_REFERENCE.md` (Sections: API Usage, Common Scenarios)

**Developer** (Who codes)
→ Read: `DISTRICT_BULK_IMPORT_IMPLEMENTATION.md` (Full technical guide)

**QA/Tester** (Who tests)
→ Read: `DISTRICT_BULK_IMPORT_TESTING_GUIDE.md` (Test cases and manual tests)

**DBA** (Who manages database)
→ Read: `DISTRICT_BULK_IMPORT_DATABASE_SCHEMA.md` (Schema and queries)

**System Admin** (Who deploys)
→ Read: `DISTRICT_BULK_IMPORT_DELIVERY_SUMMARY.md` (Deployment & support)

### By Task

| Task | Documentation |
|------|---|
| "How do I upload a district ZIP?" | QUICK_REFERENCE.md → API Usage Examples |
| "What's the ZIP format?" | IMPLEMENTATION.md → ZIP STRUCTURE |
| "How does retry work?" | QUICK_REFERENCE.md → Common Scenarios |
| "What tables are affected?" | DATABASE_SCHEMA.md → Tables Overview |
| "How do I authorize users?" | IMPLEMENTATION.md → PERMISSIONS & SECURITY |
| "How do I run tests?" | TESTING_GUIDE.md → Test Structure |
| "What endpoints exist?" | IMPLEMENTATION.md → API ENDPOINTS |
| "How do I debug?" | QUICK_REFERENCE.md → Debugging |

---

## 🔑 Key Concepts (2 minutes)

### Scope
```
scope_type: 'district'  → Multiple schools in one import
scope_type: 'school'    → Single school (existing functionality)
```

### Status Flow
```
validating → importing → {completed|partial|failed}

completed = All schools succeeded
partial   = Some schools failed
failed    = All schools failed
```

### Recovery
```
You can retry failed schools individually:
  POST /api/bulk-import/{id}/retry-school

Or retry all at once:
  POST /api/bulk-import/{id}/retry-all

Check what failed:
  GET /api/bulk-import/{id}/recovery-status
```

### Structure
```
District ZIP
├── manifest.json (required)
├── School1_Name/ (directory)
│   ├── SUBJECT1.csv
│   └── SUBJECT2.csv
└── School2_Name/ (directory)
    ├── SUBJECT1.csv
    └── SUBJECT2.csv
```

---

## ❌ Common Mistakes & Fixes

| Mistake | Fix |
|---------|-----|
| "School not found" | Verify school code in manifest matches database |
| "CSV file not found" | Check directory name matches pattern: `<CODE>_<NAME>/` |
| "Manifest validation failed" | Validate manifest.json is valid JSON with correct structure |
| "Import stuck in 'importing'" | Check: `php artisan queue:work --stop-when-empty` |
| "CSV data not imported" | Check candidate exists with `CandidateExamRegistration` |
| "Cannot retry" | Check import status is 'partial' or 'failed' |
| "Unauthorized" | Verify user role is district_officer or admin |

---

## 🧪 Testing Checklist

### Basic Flow (10 minutes)
- [ ] Create test ZIP with 1 school, 1 subject
- [ ] Upload ZIP via API (preview)
- [ ] Start import
- [ ] Monitor progress
- [ ] Verify marks in database
- [ ] Check audit log

### Failure & Recovery (15 minutes)
- [ ] Create ZIP with invalid school code
- [ ] Verify validation catches error
- [ ] Create ZIP with missing CSV
- [ ] Start import (should show error on that school)
- [ ] Check recovery-status endpoint
- [ ] Fix ZIP and retry
- [ ] Verify marks imported after retry

### Authorization (10 minutes)
- [ ] Login as school officer
- [ ] Try to start district import → should fail (403)
- [ ] Login as district officer
- [ ] Start import for own district → should succeed (200)
- [ ] Try to start import for other district → should fail (403)
- [ ] Login as admin
- [ ] Start import for any district → should succeed (200)

---

## 🔍 Verification Steps

### 1. Check Database Setup
```sql
-- Verify tables exist
SHOW TABLES LIKE 'bulk_import%';

-- Verify columns
DESCRIBE bulk_imports;
DESCRIBE bulk_import_schools;

-- Should show:
-- - scope_type (enum: school|district)
-- - scope_id (bigint)
-- - district_id (FK)
```

### 2. Check Routes
```bash
php artisan route:list | grep bulk-import

# Should show:
# POST   /api/bulk-import/preview
# POST   /api/bulk-import/start
# POST   /api/bulk-import/district/start
# GET    /api/bulk-import/{id}/progress
# GET    /api/bulk-import/{id}
# GET    /api/bulk-import/{id}/recovery-status
# POST   /api/bulk-import/{id}/retry-school
# POST   /api/bulk-import/{id}/retry-all
```

### 3. Check Services
```bash
# Verify files exist
ls -la app/Services/MarkImport/District*

# Should show:
# - DistrictBulkImportOrchestrator.php
# - DistrictManifestValidator.php
# - DistrictImportRecoveryService.php
```

### 4. Check Queue
```bash
# Start queue worker
php artisan queue:work --stop-when-empty

# In another terminal, start import
# Queue worker should show:
# Processing: App\Jobs\ProcessBulkImportSchool
```

### 5. Check Logs
```bash
# Tail audit log
tail -f storage/logs/audit.log

# Should show:
# District Bulk Import Started
# School import started/completed
# ZIP Signature Event
```

---

## 📞 Quick Help

### "How do I..."

**...upload a district ZIP?**
1. Create ZIP with `manifest.json` and school subdirs
2. `POST /api/bulk-import/preview` to validate
3. `POST /api/bulk-import/district/start` to import

**...check if import succeeded?**
```
GET /api/bulk-import/{id}/progress
→ Check "status" field
→ Check "progress_percentage"
→ Check "schools" array for each school status
```

**...fix a failed school?**
```
GET /api/bulk-import/{id}/recovery-status
→ See which schools failed
POST /api/bulk-import/{id}/retry-school {"school_id": 123}
→ Monitor with /progress endpoint
```

**...debug what went wrong?**
1. Check response from API call
2. Check `storage/logs/audit.log`
3. Check database:
   ```sql
   SELECT * FROM bulk_imports WHERE id = 100;
   SELECT * FROM bulk_import_schools WHERE bulk_import_id = 100;
   SELECT * FROM bulk_import_files WHERE bulk_import_id = 100;
   ```

**...verify data was imported?**
```sql
SELECT * FROM subject_marks 
WHERE exam_year_id = 2025 
AND subject_id = 1 
LIMIT 10;
```

---

## 🎓 Next Steps

### If You're New
1. Read: QUICK_REFERENCE.md (5 min read)
2. Try: Create and upload test ZIP (10 min)
3. Read: IMPLEMENTATION.md → your specific section

### If You're Testing
1. Review: TESTING_GUIDE.md
2. Set up: Test database
3. Run: Manual test checklist
4. Document: Any issues found

### If You're Deploying
1. Review: DELIVERY_SUMMARY.md → Deployment Checklist
2. Run: `php artisan migrate`
3. Configure: Audit logging
4. Monitor: First week of usage

### If You're Supporting Users
1. Keep: QUICK_REFERENCE.md handy
2. Know: Common issues & fixes (above)
3. Check: Logs first, ask questions second
4. Point users to: Appropriate documentation

---

## 📝 Notes

- All code is production-ready
- All tests have example implementations
- All documentation is complete
- Backward compatible: existing school imports work unchanged
- Zero breaking changes to existing APIs

**Start with the role-specific documentation above, then dive deeper as needed.**

Good luck! 🚀
