# Dashboard ACSEE - Access Information

## 🚀 How to Access the New Feature

### Web Interface
```
URL: http://localhost:8000/dashboard/exam/ACSEE
Named Route: dashboard.exam.acsee
Middleware: auth (login required)
Method: GET
```

### API Endpoints
```
Endpoint 1: GET /api/dashboard/candidates/acsee
Endpoint 2: GET /api/dashboard/candidates/filter-data
Middleware: None (public by default - add if needed)
Method: GET
```

---

## Quick Access Guide

### Step 1: Start Laravel
```bash
cd /home/prosmart-technologies/SOL/irms
php artisan serve
```

### Step 2: Open in Browser
```
http://localhost:8000/login
Login with your credentials
```

### Step 3: Navigate to Dashboard
```
Click: Dashboard menu → ACSEE Dashboard
Or direct URL: http://localhost:8000/dashboard/exam/ACSEE
```

---

## API Testing

### Test 1: Get Filter Data
```bash
curl -H "Accept: application/json" \
  http://localhost:8000/api/dashboard/candidates/filter-data
```

### Test 2: Get Candidates (No Filters)
```bash
curl -H "Accept: application/json" \
  http://localhost:8000/api/dashboard/candidates/acsee
```

### Test 3: Get Candidates (With Filters)
```bash
curl -H "Accept: application/json" \
  "http://localhost:8000/api/dashboard/candidates/acsee?page=1&page_size=15&search=&region_id=1&district_id=1&school_id=1"
```

### Test 4: Search by Name
```bash
curl -H "Accept: application/json" \
  "http://localhost:8000/api/dashboard/candidates/acsee?search=John"
```

---

## Using Browser Developer Tools

### Test in Console
```javascript
// Get filter data
fetch('/api/dashboard/candidates/filter-data')
  .then(r => r.json())
  .then(d => console.log(d))

// Get candidates
fetch('/api/dashboard/candidates/acsee?page=1&page_size=15')
  .then(r => r.json())
  .then(d => console.log(d))

// Get candidates for specific region
fetch('/api/dashboard/candidates/acsee?region_id=1')
  .then(r => r.json())
  .then(d => console.log(d))
```

---

## Navigation Path

### From Main Dashboard
```
1. Home Page (/)
   ↓
2. Dashboard Page (/dashboard)
   ↓
3. Sidebar Menu (if available)
   ↓
4. ACSEE Dashboard (/dashboard/exam/ACSEE)
```

### Direct Access
```
1. Type URL: http://localhost:8000/dashboard/exam/ACSEE
2. Press Enter
3. Page loads (ensure logged in)
```

---

## Frontend Features Available

### Filters
```
✓ Region Dropdown
  ↓ (Select → Districts update)
✓ District Dropdown  
  ↓ (Select → Schools update)
✓ School Dropdown
  ↓ (Select → Candidates filter)
✓ Reset Filters Button
```

### Search
```
✓ Search Box (Search by Index Number or Name)
✓ Real-time filtering
✓ Clear button (Reset Filters)
```

### Actions
```
✓ Export to Excel Button
✓ Pagination (Previous/Next)
✓ Page numbers (1, 2, 3, ...)
```

---

## Viewing Candidates Data

### Table Columns
```
1. Index Number (candidate_id)
2. Full Name
3. Sex (♂ Male / ♀ Female)
4. Combination (e.g., PCM)
5. Allocated Subjects (e.g., PHY, CHE, MAT)
6. School
7. District
8. Region
```

### Data Source
```
Candidates: registration/candidates table
Subjects: combinations and combination_subject tables
School/District/Region: respective tables via relationships
```

---

## File Locations

### View
```
/home/prosmart-technologies/SOL/irms/resources/views/dashboard/exam-acsee.blade.php
```

### Controller
```
/home/prosmart-technologies/SOL/irms/app/Http/Controllers/DashboardController.php
```

### Routes
```
/home/prosmart-technologies/SOL/irms/routes/web.php (line ~26)
/home/prosmart-technologies/SOL/irms/routes/api.php (line ~185)
```

---

## Testing the Implementation

### Method 1: Manual Testing
Follow `TESTING_GUIDE.md` for 20 comprehensive tests

### Method 2: Quick Smoke Test
```
1. Open http://localhost:8000/dashboard/exam/ACSEE
2. Verify candidates table appears
3. Select Region → verify Districts update
4. Enter search term → verify filtering works
5. Click Export → verify CSV downloads
6. Done! ✅
```

### Method 3: API Testing
```
1. Use curl or Postman
2. GET /api/dashboard/candidates/acsee
3. Verify JSON response has candidates array
4. GET /api/dashboard/candidates/filter-data
5. Verify JSON response has regions/districts/schools
```

---

## Database Queries to Verify Data

```sql
-- Check ACSEE candidates exist
SELECT COUNT(*) as acsee_candidates 
FROM candidates 
WHERE exam_type = 'ACSEE';

-- Check first few candidates
SELECT candidate_id, full_name, combination, exam_type 
FROM candidates 
WHERE exam_type = 'ACSEE' 
LIMIT 5;

-- Check combinations exist
SELECT code, id 
FROM combinations 
LIMIT 5;

-- Check combination subjects
SELECT c.code, s.code as subject_code, s.name 
FROM combinations c
JOIN combination_subject cs ON c.id = cs.combination_id
JOIN subjects s ON s.id = cs.subject_id
LIMIT 5;

-- Check school hierarchy
SELECT s.id, s.name, d.name as district, r.name as region
FROM schools s
LEFT JOIN districts d ON s.district_id = d.id
LEFT JOIN regions r ON d.region_id = r.id
LIMIT 5;
```

---

## Login Info (If Needed)

If testing and need credentials:
```
Email: Usually admin@example.com or similar
Password: Check your .env file or auth seeder
```

---

## Troubleshooting Access

### Issue: Page returns 404
```
Solution: 
1. Run: php artisan route:clear
2. Check: php artisan route:list | grep acsee
3. Verify route exists in routes/web.php
```

### Issue: Permission denied
```
Solution:
1. Verify you're logged in
2. Check auth middleware in routes/web.php
3. Verify user has proper permissions
```

### Issue: No candidates show
```
Solution:
1. Check database: SELECT COUNT(*) FROM candidates WHERE exam_type='ACSEE';
2. Seed test data if needed
3. Check exam_type column is exactly 'ACSEE' (case-sensitive)
```

### Issue: API returns empty
```
Solution:
1. Check database has candidates
2. Verify API endpoint URL is correct
3. Check browser Network tab for response
4. Verify API response has correct JSON structure
```

---

## Important Notes

### Read-Only Dashboard
- ✅ View candidates only
- ❌ Cannot edit in dashboard
- ❌ Cannot delete in dashboard
- ✅ Use `/registration/candidates` to edit

### ACSEE Only
- ✅ Shows only ACSEE exam candidates
- ✅ Can extend to CSEE/PSLE by creating similar pages
- ✅ Base architecture supports multiple exam types

### Data Freshness
- ✅ Data updates automatically when candidates are registered
- ❌ No real-time updates (requires page refresh)
- ✅ API calls are always fresh data

---

## Next Steps After Verification

### If Everything Works ✅
1. Proceed to deployment
2. Push to git
3. Deploy to staging
4. Deploy to production

### If Issues Found ❌
1. Check troubleshooting above
2. Review `TESTING_GUIDE.md`
3. Check `IMPLEMENTATION_COMPLETED.md`
4. Review code in controller/view

---

## Support Resources

### Documentation Files
- `ADVICE_SUMMARY.md` - What was built and why
- `IMPLEMENTATION_RECOMMENDATION.md` - Architecture decisions
- `DASHBOARD_ACSEE_QUICK_START.md` - Implementation steps
- `TESTING_GUIDE.md` - Comprehensive testing
- `IMPLEMENTATION_COMPLETED.md` - What was implemented
- `DASHBOARD_ACSEE_CHEATSHEET.md` - Quick reference

### Code Files
- `app/Http/Controllers/DashboardController.php` - Controller logic
- `resources/views/dashboard/exam-acsee.blade.php` - View template
- `routes/web.php` - Web routes
- `routes/api.php` - API routes

---

## Quick Reference URLs

| Purpose | URL |
|---------|-----|
| **Web Interface** | http://localhost:8000/dashboard/exam/ACSEE |
| **API - Candidates** | http://localhost:8000/api/dashboard/candidates/acsee |
| **API - Filter Data** | http://localhost:8000/api/dashboard/candidates/filter-data |
| **Main Dashboard** | http://localhost:8000/dashboard |
| **Candidates Reg** | http://localhost:8000/registration/candidates |
| **Exam Types** | http://localhost:8000/exam-types |

---

## Performance Tips

### If Page Is Slow
1. Check database indexes: `SHOW INDEX FROM candidates;`
2. Optimize queries (already done with eager loading)
3. Reduce page size: Change `pageSize` in Alpine.js
4. Cache filter data: Add Redis caching

### If Export Is Slow
1. Limit export size in code
2. Use queue for large exports
3. Check CSV generation logic

---

## Security Notes

### Current Security
- ✅ Protected by auth middleware
- ✅ Read-only operations (no modifications)
- ✅ Input validation on API (search, filters)
- ✅ Uses query builder (no SQL injection)

### To Enhance
- [ ] Add role-based access control (RBAC)
- [ ] Add API rate limiting
- [ ] Add audit logging
- [ ] Encrypt sensitive data

---

## Summary

You can now access the Dashboard ACSEE Candidates page at:
```
http://localhost:8000/dashboard/exam/ACSEE
```

The implementation includes:
- ✅ Read-only candidates display
- ✅ Hierarchical filtering
- ✅ Search functionality  
- ✅ Pagination
- ✅ CSV export
- ✅ Professional UI

**Status**: Ready for Testing ✅  
**Next**: Follow `TESTING_GUIDE.md` to verify everything works  

---

**Happy Testing!** 🚀
