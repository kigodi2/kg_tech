# Bulk Import Optimization - Testing Guide

## How to Test the Optimizations

### Option 1: Test with Existing Data

**Step 1:** Clear existing imports (optional)
```bash
php artisan tinker

# In Tinker shell
DB::table('raw_marks')->truncate();
DB::table('subject_marks')->truncate();
DB::table('bulk_import_files')->truncate();
DB::table('bulk_imports')->truncate();
exit
```

**Step 2:** Prepare test CSV file

Create a small CSV with 100-1000 rows:
```
Index Number,Name,Paper 1,Paper 2,Paper 3
KLA0001,John Doe,85,92,78
KLA0002,Jane Smith,91,87,95
KLA0003,Bob Johnson,78,81,75
...
```

**Step 3:** Upload via the UI

Go to: **MARK ENTRY** → **School Bulk ZIP** → Upload your test file

**Step 4:** Monitor Performance

Check logs in real-time:
```bash
tail -f storage/logs/laravel.log | grep "Bulk Import"
```

You should see:
```
[2026-02-07 ...] local.INFO: Bulk Import: Bulk Import File: marks_2026_SUBJ_01.csv (5000 marks) {
    "time": "3.45s",
    "time_seconds": 3.45,
    "memory_mb": 5.25,
    "rows_inserted": 5000,
    "rows_per_second": 1449
}
```

---

### Option 2: Test with Console Command

Create a quick test command:

```bash
php artisan tinker
```

Then in Tinker:
```php
$service = app(\App\Services\MarkImport\MarkImportService::class);

// Create a test batch
$batch = \App\Models\MarkImportBatch::create([
    'batch_code' => 'TEST-' . now()->timestamp,
    'exam_year' => 2026,
    'school_id' => 1,
    'region_id' => 1,
    'district_id' => 1,
    'subject_id' => 1,
    'exam_type_id' => 2,
    'status' => 'draft',
    'imported_by' => auth()->user()?->id ?? 1,
]);

// Test the import
$result = $service->processCSVUpload($batch, $file, 2026, 1, 1);
print_r($result['metrics']);
```

---

### Option 3: Benchmark Comparison

To compare old vs new performance:

**Create temporary backup:**
```bash
cp app/Jobs/ProcessBulkImportFile.php app/Jobs/ProcessBulkImportFile.php.optimized
git checkout HEAD -- app/Jobs/ProcessBulkImportFile.php  # Revert to old version
```

**Run test with old code:**
```bash
# Upload same test file
# Check logs for time taken
```

**Restore optimized version:**
```bash
cp app/Jobs/ProcessBulkImportFile.php.optimized app/Jobs/ProcessBulkImportFile.php
```

**Run test with new code:**
```bash
# Upload same test file again
# Check logs for time taken
# Compare!
```

---

## What to Look For

### ✅ Success Indicators

1. **Faster execution time**
   - Before: 30-60 seconds for 5000 marks
   - After: 2-3 seconds for 5000 marks

2. **Constant memory usage**
   - Should stay under 10MB
   - Should not grow with file size

3. **Same accuracy**
   - Same number of rows inserted
   - Same number of errors logged
   - Same validation results

4. **Performance metrics in logs**
   - Time in seconds
   - Memory used
   - Rows per second rate

### ⚠️ Issues to Watch For

1. **Memory growing**
   - Indicates LazyCollection not working
   - Check: `memory_mb` in logs

2. **Slow performance**
   - Check if queries are being logged
   - Verify `DB::disableQueryLog()` is being called

3. **Missing rows**
   - Run count: `SELECT COUNT(*) FROM subject_marks WHERE exam_year_id = 2026`
   - Should equal rows imported

4. **Errors not logged**
   - Check `bulk_import_files.error_log` column
   - Check database error logs

---

## Performance Baseline Test

To establish a baseline, run this test:

```bash
# Time the import using bash
time php artisan tinker << 'EOF'
$start = microtime(true);

// ... run your import

$end = microtime(true);
echo "\nTotal time: " . round($end - $start, 2) . " seconds\n";
EOF
```

---

## Database Verification

After import, verify data integrity:

```bash
php artisan tinker

# Count marks inserted
DB::table('subject_marks')->count()

# Check for candidate_id NULL values (bad)
DB::table('subject_marks')
    ->whereNull('candidate_id')
    ->count()

# Check for valid exam_year_id
DB::table('subject_marks')
    ->where('exam_year_id', 2026)
    ->count()

exit
```

---

## Production Testing Checklist

Before deploying to production:

- [ ] Test with 1000 rows → Should complete in < 5 seconds
- [ ] Test with 10000 rows → Should complete in < 30 seconds  
- [ ] Test with 100000 rows → Should complete in < 5 minutes
- [ ] Verify memory doesn't exceed 50MB
- [ ] Verify all rows are inserted correctly
- [ ] Verify error logging works
- [ ] Check logs contain performance metrics
- [ ] Test error handling (invalid candidate ID)
- [ ] Test rollback on failure
- [ ] Monitor CPU usage (should not spike)

---

## Troubleshooting

### Problem: Still slow after optimization

**Check 1:** Is query logging enabled?
```bash
php artisan tinker
DB::getQueryLog();  # Should be empty if logging disabled
exit
```

**Check 2:** Are foreign key checks disabled?
```bash
php artisan tinker
DB::select('SELECT @@foreign_key_checks');  # Should be 0 during import
exit
```

**Check 3:** Are batch inserts working?
```bash
# Monitor with:
watch -n 1 "mysql -u root -p -e \"SELECT COUNT(*) FROM subject_marks;\""
# Should jump in chunks of 500, not 1 by 1
```

### Problem: Out of memory

**Cause:** LazyCollection not working  
**Fix:** Check PHP memory_limit in php.ini

```bash
php -i | grep "memory_limit"
```

Should be at least 256MB.

### Problem: Rows missing after import

**Check:**
```bash
php artisan tinker
// Check if import completed
DB::table('bulk_import_files')->latest()->first()

// Count actual marks in database
DB::table('subject_marks')->count()

// Check error logs
DB::table('bulk_import_file_errors')->count()
```

---

## Performance Expectations

**With 5000 rows:**
- Execution time: 2-5 seconds
- Memory used: 5-10 MB
- Queries executed: 5-10
- Rows per second: 1000-2500

**With 50000 rows:**
- Execution time: 20-40 seconds
- Memory used: 5-15 MB
- Queries executed: 50-100
- Rows per second: 1000-2500

**With 500000 rows:**
- Execution time: 3-8 minutes
- Memory used: 10-20 MB
- Queries executed: 500-1000
- Rows per second: 1000-2500

---

## Sample Test CSV

Create a test file: `test-marks.csv`

```csv
Index Number,Name,Paper 1,Paper 2,Paper 3
KLA0001,John Doe,85,92,78
KLA0002,Jane Smith,91,87,95
KLA0003,Bob Johnson,78,81,75
KLA0004,Alice Williams,88,90,87
KLA0005,Charlie Brown,92,88,91
```

Make sure candidate IDs (KLA0001, etc.) exist in your database first!

---

## Next Steps After Testing

1. **If all tests pass:**
   - Deploy to production
   - Remove old code paths
   - Update documentation

2. **If performance still not good:**
   - Consider PDO prepared statements (Option 3)
   - Consider MySQL LOAD DATA INFILE (Option 4)
   - Check database indexes on marks table

3. **Monitor in production:**
   - Watch for slow imports in logs
   - Alert if memory exceeds 50MB
   - Track average rows/second

---

**Good luck with testing! The optimizations should give you a 10-20x speedup.**
