# Quick Test Guide - Two-Phase Import System

## 5-Minute Quick Test

### 1. Test Schools Import (2 minutes)
```
1. Open: http://your-app/registration/schools
2. Click: Tools dropdown → "Import (Advanced)" (NEW BUTTON)
3. In Modal: Click "Download" button
4. Save: schools_template.csv
5. Open file, add one school:
   - Code: TEST001
   - Name: Test School
   - District Code: (use existing, e.g., IRIG)
   - Ownership: GOVERNMENT
6. Upload: Modified CSV back into modal
7. Verify: Shows 1 valid row, 0 errors
8. Click: "Import 1 Items" button
9. Confirm: Modal closes and data appears in table
```

### 2. Test Districts Import (2 minutes)
```
1. Open: http://your-app/registration/districts
2. Click: Tools dropdown → "Import (Advanced)" (NEW BUTTON)
3. In Modal: Click "Download" button
4. Save: district_template.csv
5. Open file, add one district:
   - Code: TEST01
   - Name: Test District
   - Region Code: (use existing, e.g., IR)
6. Upload: Modified CSV back into modal
7. Verify: Shows 1 valid row, 0 errors
8. Click: "Import 1 Items" button
9. Confirm: Modal closes and data appears in table
```

### 3. Test Regions Import (1 minute)
```
1. Open: http://your-app/registration/regions
2. Click: Tools dropdown → "Import (Advanced)" (NEW BUTTON)
3. In Modal: Click "Download" button
4. Save: region_template.csv
5. Open file, add one region:
   - Code: TE
   - Name: Test Region
6. Upload: Modified CSV back into modal
7. Verify: Shows 1 valid row, 0 errors
8. Click: "Import 1 Items" button
9. Confirm: Modal closes and data appears in table
```

---

## Validation Test (Error Scenarios)

### Test Missing Required Field
```
1. Districts page → Import (Advanced)
2. Create CSV missing the region_code column:
   "code","name"
   "TEST02","Missing Region"
3. Upload: File
4. Expected: 1 error - "Region code is required"
5. Verify: Error table shows the error
6. Click: "Download Errors" button
7. Verify: CSV downloads with error details
```

### Test Invalid Foreign Key
```
1. Schools page → Import (Advanced)
2. Create CSV with non-existent district:
   "code","name","district_code","ownership"
   "TEST002","Bad District School","INVALID","GOVERNMENT"
3. Upload: File
4. Expected: 1 error - "District 'INVALID' not found"
5. Verify: Error clearly displayed
```

### Test Duplicate Code
```
1. Regions page → Import (Advanced)
2. Create CSV with duplicate code in file:
   "code","name"
   "TE2","Test Region"
   "TE2","Another Region"  ← Duplicate
3. Upload: File
4. Expected: 1 error - "Duplicate region code in file"
5. Verify: Error shows which row has duplicate
```

---

## Browser Developer Tools Check

### Network Tab
```
1. Open: DevTools (F12)
2. Go to: Network tab
3. Start Import with test file
4. Expected Requests:
   ✓ POST /api/registration/school/import/validate
   ✓ POST /api/registration/school/import/commit
5. Verify: Both return 200 OK
6. Response should contain:
   - success: true
   - imported: 1
   - updated: 0
```

### Console Tab
```
1. Open: DevTools (F12)
2. Go to: Console tab
3. Start Import
4. Expected: No JavaScript errors (check for red X)
5. Should see: State transitions logged
   - "Import validation started"
   - "Import validation complete"
   - "Import commit successful"
```

---

## Data Verification

### After Successful School Import
```SQL
SELECT * FROM schools WHERE code = 'TEST001' LIMIT 1;
-- Should show: name='Test School', ownership='GOVERNMENT', is_active=1
```

### After Successful District Import
```SQL
SELECT * FROM districts WHERE code = 'TEST01' LIMIT 1;
-- Should show: name='Test District', region_id=<correct_id>
```

### After Successful Region Import
```SQL
SELECT * FROM regions WHERE code = 'TE' LIMIT 1;
-- Should show: name='Test Region'
```

---

## Performance Check

### Large Import Test
```
1. Create CSV with 500 rows of valid data
2. Import → Validate (should be <10 seconds)
3. Import → Commit (should be <5 seconds)
4. Verify: All 500 rows in database
5. Check: No duplicate records created
```

### Concurrent Users Test
```
1. User A: Start import on Schools
2. User B: Start import on Districts (different entity)
3. Expected: Both complete successfully without interference
4. Verify: All data correct in both tables
```

---

## Browser Compatibility Check

### Test in Different Browsers
- [ ] Chrome/Chromium: ✓ Alpine.js works
- [ ] Firefox: ✓ Drag-drop works
- [ ] Safari: ✓ Modal styling correct
- [ ] Edge: ✓ All features functional

### Test Responsive Design
- [ ] Desktop (1920px): Modal fits screen
- [ ] Laptop (1366px): Modal readable
- [ ] Tablet (768px): Modal scrollable
- [ ] Mobile (375px): Works but may need scrolling

---

## Rollback Plan (If Issues Found)

### Issue: Import button not showing
```
1. Check: Browser developer tools Network tab
2. Verify: CSS/JS loaded correctly
3. Solution: Hard refresh (Ctrl+Shift+R)
4. If persists: Clear browser cache completely
```

### Issue: Import validation fails
```
1. Check: Backend logs (storage/logs/laravel.log)
2. Verify: CSV format matches template
3. Verify: Database connectivity
4. Solution: Restart Laravel if needed
```

### Issue: Need to rollback code
```
git revert <commit-hash>
git push
Clear cache and reload
```

---

## Test Automation (Optional)

Create a simple PHP test script to verify all endpoints:

```php
<?php
$entities = ['school', 'district', 'region'];
$validResults = 0;

foreach ($entities as $entity) {
    // Test template endpoint
    $response = file_get_contents(
        "http://localhost/api/registration/$entity/import/template"
    );
    if (strpos($response, 'code') !== false) {
        $validResults++;
        echo "✓ Template endpoint working for $entity\n";
    }
}

echo "\n$validResults/" . count($entities) . " tests passed\n";
?>
```

Run with: `php test-import.php`

---

## Success Criteria

### ✅ Import System Working If:
1. "Import (Advanced)" button visible on all 3 pages
2. Template download works for each entity
3. Validation detects at least one error correctly
4. Import completes and data appears in table
5. No JavaScript errors in console
6. Error export produces valid CSV
7. All required validations enforced

### ❌ Issues to Watch For:
1. Modal doesn't open (check browser console)
2. Upload button unresponsive (check network)
3. Validation hangs (check timeout settings)
4. Error table empty but shows errors count (pagination issue)
5. Data not saved after commit (transaction issue)

---

## Support Commands

### Check Laravel Log
```bash
tail -f storage/logs/laravel.log | grep -i import
```

### Clear Cache
```bash
php artisan config:cache
php artisan view:cache
php artisan route:cache
```

### Reset Database (Development Only)
```bash
php artisan migrate:refresh
php artisan db:seed
```

---

## Quick Reference URLs

- Schools Import: `/registration/schools` (click Tools)
- Districts Import: `/registration/districts` (click Tools)
- Regions Import: `/registration/regions` (click Tools)
- API Template School: `/api/registration/school/import/template`
- API Template District: `/api/registration/district/import/template`
- API Template Region: `/api/registration/region/import/template`

---

## Estimated Test Time: 10-15 minutes

This covers:
- Basic functionality for 3 pages (6 min)
- Error handling scenarios (4 min)
- Developer tools verification (3 min)
- Data verification (2 min)

---

**Test Date:** __________  
**Tester Name:** ___________  
**Result:** ☐ PASS ☐ FAIL ☐ NEEDS FIXES
