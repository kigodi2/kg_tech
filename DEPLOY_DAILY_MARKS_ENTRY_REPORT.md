# Deployment Guide: Daily Marks Entry Report

## Quick Summary
This guide deploys the Daily Marks Entry Report feature to the ACSEE Evaluations system.

## 📦 Files to Deploy

### 1. Modified File
```
resources/views/evaluations/acsee.blade.php
```
**Changes**: Added Daily Marks Entry Report HTML + JavaScript (~400 lines added)

### 2. New File
```
app/Http/Controllers/DailyMarksEntryReportController.php
```
**Purpose**: Handles API requests for report data

### 3. Modified File
```
routes/api.php
```
**Changes**: Added import + API route for daily marks endpoint

## 🔧 Deployment Steps

### Step 1: Backup Current Files
```bash
cd /home/prosmart-technologies/SOL/irms

# Backup the view file
cp resources/views/evaluations/acsee.blade.php \
   resources/views/evaluations/acsee.blade.php.backup.$(date +%Y%m%d_%H%M%S)

# Backup the routes file
cp routes/api.php routes/api.php.backup.$(date +%Y%m%d_%H%M%S)
```

### Step 2: Copy New Files
```bash
# New controller is already in place
# (No additional copy needed for new file)
```

### Step 3: Clear Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

### Step 4: Verify Deployment
```bash
# Check controller exists
ls -la app/Http/Controllers/DailyMarksEntryReportController.php

# Check routes are defined
grep -n "daily-marks-entry-report" routes/api.php

# Check view is updated
grep -c "entry-regional-subjects" resources/views/evaluations/acsee.blade.php
# Should return: 2 (appears in both menu and content)
```

### Step 5: Test in Browser
1. Go to: http://127.0.0.1:8000/evaluations/acsee
2. In sidebar: ENTRY REPORT → REGIONAL LEVEL → SUBJECTS
3. Verify:
   - [ ] Page loads without console errors (F12)
   - [ ] Filter dropdowns populate
   - [ ] Table displays
   - [ ] Can change filters
   - [ ] Export CSV button works
   - [ ] Print button works

## 🐛 Troubleshooting

### Issue: 404 Not Found on API call
```
Error: GET /api/daily-marks-entry-report 404
```

**Solution**:
1. Verify route added to `routes/api.php`
2. Run: `php artisan route:clear`
3. Check controller path is correct

### Issue: Blade template syntax error
```
Error: Parse error in acsee.blade.php
```

**Solution**:
1. Check file was copied completely
2. Verify no truncation occurred
3. Restore from backup and re-copy

### Issue: Table doesn't appear
```
Page loads but table is hidden/not visible
```

**Solution**:
1. Check console errors (F12 → Console tab)
2. Verify Alpine.js is loaded
3. Check activeTab variable is set correctly

### Issue: Filter dropdowns empty
```
Dropdowns show but have no options
```

**Solution**:
1. Check `/api/exam-years`, `/api/regions`, `/api/subjects` endpoints exist
2. Verify data exists in database
3. Check user is authenticated and has admin role

### Issue: "No data available" always shows
```
Report doesn't show any data
```

**Possible causes**:
- No marks in database yet
- Filters are too restrictive
- Wrong exam year selected

**Solution**:
1. Try with all filters empty (don't select any)
2. Check database: `SELECT COUNT(*) FROM subject_marks;`
3. If 0 rows, data needs to be imported first

## ✅ Verification Checklist

- [ ] Files backed up
- [ ] Cache cleared
- [ ] Page loads without errors
- [ ] Menu navigation works (sidebar → ENTRY REPORT → REGIONAL LEVEL → SUBJECTS)
- [ ] Filters load with data
- [ ] Changing filters updates table
- [ ] CSV export downloads
- [ ] CSV file is valid format
- [ ] Print preview opens and shows table
- [ ] No console errors (F12)
- [ ] Mobile view works (responsive)
- [ ] Admin access is required

## 🔙 Rollback (if needed)

If deployment causes issues:

```bash
# Restore backed up files
cp resources/views/evaluations/acsee.blade.php.backup.YYYYMMDD_HHMMSS \
   resources/views/evaluations/acsee.blade.php

cp routes/api.php.backup.YYYYMMDD_HHMMSS routes/api.php

# Clear cache again
php artisan cache:clear
php artisan view:clear

# Or undo git changes
git checkout resources/views/evaluations/acsee.blade.php
git checkout routes/api.php
```

## 📊 Database Considerations

**No migrations needed** - Uses existing tables:
- `subject_marks` - Mark entries with timestamps
- `subjects` - Subject lookup
- `candidates` - Candidate data
- `schools` - School data
- `regions` - Regional grouping

**Required columns** (all exist):
- `subject_marks.created_at` - For date grouping
- `subject_marks.subject_id` - Foreign key
- `subject_marks.candidate_id` - For linking

## 🔐 Security Checklist

- [ ] Route has middleware: `['auth', 'admin']`
- [ ] Only admins can access report
- [ ] No sensitive data in export
- [ ] Input parameters are validated
- [ ] Query is parameterized (no SQL injection)

## 📈 Performance Notes

**Expected performance**:
- Initial load: ~1 second
- Filter change: ~500ms
- CSV export: ~2 seconds
- Print preview: ~1 second

**For large datasets (>100k marks)**:
- May need to add pagination
- Consider caching filtered results
- Monitor database query performance

## 📞 Support & Contact

If deployment fails:

1. **Check logs**: `storage/logs/laravel.log`
2. **Browser console**: F12 → Console for JS errors
3. **Network tab**: F12 → Network for API failures
4. **Database**: Verify data exists in tables
5. **Permissions**: Ensure user is logged in as admin

## 🎉 Success Indicators

Deployment successful when:
- ✓ Menu item appears in sidebar
- ✓ Page loads and displays table
- ✓ All filters work
- ✓ Data displays correctly
- ✓ Export/Print work
- ✓ No console errors
- ✓ Mobile responsive

## 📅 Post-Deployment

After successful deployment:

1. **Announce to users**: Feature is now available
2. **Create user guide**: Share DAILY_MARKS_ENTRY_QUICKSTART.md
3. **Monitor usage**: Check logs for errors
4. **Gather feedback**: Ask users for improvement suggestions
5. **Plan enhancements**: Consider Phase 2 improvements

## 🚀 Timeline

| Step | Duration | Status |
|------|----------|--------|
| Backup files | 2 min | ✓ |
| Deploy files | 1 min | ✓ |
| Clear cache | 2 min | ✓ |
| Test functionality | 10 min | ⏳ |
| Verify security | 5 min | ⏳ |
| **Total** | **~20 min** | |

## 📝 Sign-Off Template

Use this template to confirm deployment:

```
DEPLOYMENT SIGN-OFF
═══════════════════════════════════════════════════════════

Feature: Daily Marks Entry Report
Deployed by: [Your Name]
Deployment Date: [Date]
Deployment Time: [Time]
Environment: [Development/Staging/Production]

Files Deployed:
☐ resources/views/evaluations/acsee.blade.php (modified)
☐ app/Http/Controllers/DailyMarksEntryReportController.php (new)
☐ routes/api.php (modified)

Testing Completed:
☐ Page loads without errors
☐ Navigation works (menu → feature)
☐ All filters functional
☐ Export works
☐ Print works
☐ Admin access enforced
☐ No database errors

Status: ✓ READY FOR PRODUCTION

Notes:
[Any special considerations or known issues]

Sign-off: _________________________ Date: _____________
```

---

**Ready to Deploy!** All files are in place. Follow steps above to activate the feature.
