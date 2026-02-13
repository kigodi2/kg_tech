# Testing Guide - Year Alignment Implementation

**Server**: http://127.0.0.1:8001  
**Status**: ✅ Running  

---

## 🧪 API Testing

### Test 1: Get Mark Entry Subjects (Valid Year)

```bash
curl -X GET "http://127.0.0.1:8001/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1" \
  -H "Accept: application/json"
```

**Expected Response**: 
- Status: `200 OK` or `422 Unprocessable Entity`
- Should NOT return `500 Internal Server Error`

### Test 2: Test with Locked Year

First, lock a year in database (if you have data):
```bash
php artisan tinker
> $year = ExamYear::find(1);
> $year->update(['is_locked' => true]);
> exit
```

Then test:
```bash
curl -X GET "http://127.0.0.1:8001/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=1"
```

**Expected Response**:
- Status: `422 Unprocessable Entity`
- Code: `YEAR_LOCKED`
- Message: "Year is locked..."

### Test 3: Test with Invalid School

```bash
curl -X GET "http://127.0.0.1:8001/api/mark-entry/acsee/subjects-by-school?exam_year=2024&school_id=999"
```

**Expected Response**:
- Status: `422 Unprocessable Entity` (if no candidates)
- Message: "No ACSEE candidates registered..."

---

## 🖥️ Browser Testing

### Open Mark Entry Page
```
http://127.0.0.1:8001/mark-entry
```

**Verify:**
- Page loads without errors
- Check browser console (F12) for any JS errors
- Year input accepts numbers
- School dropdown loads

### Test Mark Entry Workflow

1. **Select Year**: 2024
2. **Select School**: (pick any school with candidates)
3. **Observe**: 
   - If subjects show: ✅ Year has registrations
   - If warning shows: ✅ No registrations for this year
   - If lock icon shows: ✅ Year is locked

### Test Error Scenarios

**Scenario 1: Year with no candidates**
- Select year with no ACSEE registrations
- Observe: Yellow warning message
- Subject dropdown: Disabled

**Scenario 2: Locked year**
- Select locked year (if available)
- Observe: Red lock icon
- Subject dropdown: Disabled
- Message: "Year is locked. Mark entry is disabled."

---

## ⚙️ Database Testing

### Check Migration Applied

```bash
php artisan tinker
```

```php
// Check columns exist
Schema::hasColumn('candidate_exam_registrations', 'exam_year_id')  // Should be true
Schema::hasColumn('candidate_subject_selections', 'exam_year_id')  // Should be true

// Check table exists
Schema::hasTable('exam_year_audit_logs')  // Should be true

// Check relationships work
$reg = CandidateExamRegistration::first();
$reg->examYear()  // Should work

exit
```

---

## 📊 Testing Checklist

### API Tests
- [ ] GET subjects with valid year → 200 OK
- [ ] GET subjects with locked year → 422 YEAR_LOCKED
- [ ] GET subjects with no candidates → 422 NO_CANDIDATES
- [ ] Response includes `code` field
- [ ] Response includes `success` field

### UI Tests
- [ ] Mark entry page loads
- [ ] Year input accepts numbers
- [ ] School dropdown loads
- [ ] Subject dropdown populated (if candidates exist)
- [ ] Warning message shows (if no candidates)
- [ ] Lock icon shows (if year locked)
- [ ] No console errors (F12)

### Database Tests
- [ ] Columns added to registration tables
- [ ] Audit logs table created
- [ ] Model relationships work
- [ ] FK constraints prevent violations

### Validation Tests
- [ ] ExamYearValidationService loads
- [ ] Validation methods return correct codes
- [ ] Error messages are clear

---

## 🐛 Troubleshooting

### Server won't start
```bash
# Kill process on port 8000
lsof -ti:8000 | xargs kill -9

# Start fresh
php artisan serve
```

### API returns 500 error
1. Check logs: `tail -f storage/logs/laravel.log`
2. Run: `php artisan migrate` (verify migration applied)
3. Verify: Models have relationships

### UI not updating on year change
1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify API endpoint is being called
4. Check response status code (should be 200 or 422)

### Database errors
```bash
php artisan tinker
> Schema::getColumnListing('candidate_exam_registrations')
# Should include 'exam_year_id'
```

---

## 📈 Performance Testing

### Subject Query Performance
```bash
php artisan tinker
> use Illuminate\Support\Facades\DB;
> DB::enableQueryLog();
> app(App\Services\MarkImport\SubjectFilterService::class)->getSubjectsBySchoolAndYear(1, 2024);
> dd(DB::getQueryLog());
```

**Expected**: Query should use indexes and execute in < 100ms

---

## ✅ Final Verification

Run this script to verify everything:

```bash
php artisan tinker << 'EOF'
echo "=== FINAL VERIFICATION ===\n\n";

// 1. Check migration
echo "1. Migration: " . (Schema::hasColumn('candidate_exam_registrations', 'exam_year_id') ? '✅' : '❌') . "\n";

// 2. Check relationships
echo "2. Relationships: " . (method_exists(CandidateExamRegistration::class, 'examYear') ? '✅' : '❌') . "\n";

// 3. Check validation service
echo "3. Service: ";
try {
    app(App\Services\ExamYear\ExamYearValidationService::class);
    echo "✅\n";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

// 4. Check audit logs table
echo "4. Audit Table: " . (Schema::hasTable('exam_year_audit_logs') ? '✅' : '❌') . "\n";

echo "\n=== ALL VERIFIED ===\n";
EOF
```

---

## 🚀 Next Steps

### Option 1: Continue Testing
- Test all scenarios in this guide
- Verify error handling
- Check database constraints

### Option 2: Deploy
- Follow `DEPLOYMENT_QUICK_START.md`
- Deploy to staging
- Run full test suite

### Option 3: Review Code
- Check models: `app/Models/`
- Check services: `app/Services/ExamYear/`
- Check controllers: `app/Http/Controllers/`
- Check inline comments (marked IMPORTANT)

---

## 📞 Having Issues?

**API returning 500?**
→ Check error logs: `storage/logs/laravel.log`

**Migration didn't apply?**
→ Run: `php artisan migrate`

**Can't load validation service?**
→ Run: `composer autoload -d`

**Browser console errors?**
→ Open DevTools (F12) → Console tab

**Database questions?**
→ See: `MIGRATION_COMPATIBILITY_FIX.md`

---

## 📖 Documentation

- **Quick Start**: `START_HERE_YEAR_ALIGNMENT.md`
- **API Reference**: `YEAR_ALIGNMENT_QUICK_REFERENCE.md`
- **Full Guide**: `YEAR_ALIGNMENT_IMPLEMENTATION_GUIDE.md`

---

**Server is running on: http://127.0.0.1:8001** ✅

Start testing now and enjoy your new year-aligned system! 🎉
