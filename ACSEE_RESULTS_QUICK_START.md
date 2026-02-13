# ACSEE Results Module - Quick Start Guide

## 🚀 Installation

### 1. Run Migration
```bash
php artisan migrate
```

This creates the `export_audit_logs` table for tracking all data exports.

### 2. Clear Cache
```bash
php artisan cache:clear
```

### 3. Verify Installation
Visit: `http://localhost:8000/results/acsee`

You should see the results page (may be empty if no published results).

---

## 📊 Accessing Results

### Entry Point
```
GET /results/acsee
```

### Requirements
1. **Authentication**: Must be logged in
2. **Role**: Super Admin, Regional Admin, District Admin, or School User
3. **Published Results**: Exam year must be locked and published

### Filter Results By
- **Exam Year** (Required) - Select from dropdown
- **Region** (Super Admin only)
- **District** (Super/Regional Admins)
- **School** (Super/Regional/District Admins)
- **Search** - Index number or candidate name

---

## 📥 Exporting Data

### PDF Export
```
POST /results/acsee/export-pdf
```

**When to use**: School needs formatted results sheet

**What you get**:
- NECTA-compliant layout
- Grouped by school
- Color-coded grades and divisions
- Printable format

**File format**: `ACSEE-Results-2024-School-5.pdf`

### CSV Export
```
POST /results/acsee/export-csv
```

**When to use**: Analysis in Excel, R, or Python

**What you get**:
- Spreadsheet-ready format
- All candidate data
- Subject grades as columns
- UTF-8 encoded

**File format**: `ACSEE-Results-2024-20260203-153000.csv`

---

## 🔐 Role-Based Access

| Role | View | Filter | Export |
|------|------|--------|--------|
| Super Admin | All data | Region, District, School | ✅ Full |
| Regional Admin | Own region | District, School | ✅ Own region |
| District Admin | Own district | School | ✅ Own district |
| School User | Own school | None | ✅ Own school |

### User Cannot:
- ❌ See data outside their jurisdiction
- ❌ Edit any results
- ❌ Delete or modify data
- ❌ View unpublished results
- ❌ See raw marks (only grades)

---

## 📋 What's Displayed

### Per Candidate
- **Index Number** - Unique identifier (A001234)
- **Name** - Full candidate name
- **Sex** - Male/Female
- **Subject Grades** - A, B, C, D, E (color-coded)
- **Total Points** - Grade point total
- **Division** - I, II, III, IV (highlighted)
- **School** - Institution name

### NOT Displayed
- ❌ Raw marks
- ❌ Personal ID numbers
- ❌ Date of birth
- ❌ Unpublished results
- ❌ Verification status

---

## 🔍 Search Examples

Search for candidates by:
```
A001234          # Index number (exact match)
John             # Candidate name (partial match)
A001             # Index prefix
```

---

## ⚙️ Troubleshooting

### "No Results Found"
1. Check exam year is selected (required)
2. Verify year is published in Exam Years section
3. Check you have access to that region/district/school

### Export Not Working
1. Ensure mPDF/DOMPDF is installed
2. Check `/storage` directory is writable
3. Try CSV export first (simpler format)
4. Check browser console for errors

### Filters Not Showing
1. Must have published results in that year
2. Your role must have access to that level
3. Refresh page (Ctrl+F5)
4. Clear browser cache

### Slow Loading
1. Reduce `per_page` parameter (default 50)
2. Add school filter (narrows data)
3. Search for specific candidate
4. Clear cache: `php artisan cache:clear`

---

## 📈 Performance Tips

### For Large Datasets (1000+ candidates)
1. **Filter by school** - Reduces dataset dramatically
2. **Use CSV export** - Faster than PDF
3. **Export by year** - Don't try all years at once
4. **Use pagination** - Show 50 per page (default)

### Database Queries
```sql
-- Fast: Filtered by school
SELECT * FROM candidate_results 
WHERE is_published = 1 AND year = 2024 
AND candidate_id IN (
  SELECT id FROM candidates WHERE school_id = 5
)

-- Slow: All results for year
SELECT * FROM candidate_results 
WHERE is_published = 1 AND year = 2024
```

---

## 🔐 Security Features

### Authorization
- ✅ Role-based access control
- ✅ Jurisdiction enforcement
- ✅ Published results only

### Audit Trail
- ✅ All exports logged
- ✅ Tracks who, when, what
- ✅ Cannot be deleted

### Data Protection
- ✅ No inline editing
- ✅ No mass operations
- ✅ CSRF tokens on forms
- ✅ Read-only module

---

## 📊 CSV Column Reference

When exporting to CSV, columns are:

| Column | Description | Example |
|--------|-------------|---------|
| Index Number | Candidate ID | A001234 |
| Candidate Name | Full name | John Doe |
| Sex | Male/Female | Male |
| Grade-MATH | Math grade | A |
| Grade-ENG | English grade | B |
| Grade-[SUBJECT] | Other subjects | ... |
| Total Points | Grade points | 16 |
| Division | Final division | I |
| School | School name | Mkuranga Secondary |
| District | District name | Dar es Salaam |
| Region | Region name | Dar es Salaam |
| Exam Year | Year of exam | 2024 |

---

## 🎯 Common Tasks

### View My School's Results
```
1. Go to /results/acsee
2. Select Exam Year: 2024
3. Click Apply Filters
4. Download as PDF for printing
```

### Export Region's Results for Analysis
```
1. Go to /results/acsee
2. Select Year: 2024
3. Select Region: Dar es Salaam
4. Click CSV
5. Open in Excel/R/Python
```

### Check Single Candidate
```
1. Go to /results/acsee
2. Search: A001234
3. View row with all details
4. Click subject to see breakdown
```

### Audit Export Activity
```
1. Go to Admin Panel (if Super Admin)
2. Check Export Audit Log
3. See: who exported, when, what scope
```

---

## 📱 Browser Compatibility

Works on:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

**Not recommended**: 
- ❌ Internet Explorer (unsupported)
- ❌ Mobile (better on desktop for large tables)

---

## 💾 Export File Sizes

### PDF
- 50 candidates: ~200 KB
- 500 candidates: ~2 MB
- 1000 candidates: ~4 MB

### CSV
- 50 candidates: ~50 KB
- 500 candidates: ~500 KB
- 1000 candidates: ~1 MB

---

## 🆘 Getting Help

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

### Run Diagnostic
```bash
php artisan tinker

# Test service
$service = app('App\Services\Results\AcseeResultsService');
$years = $service->getAvailableExamYears(auth()->user());
print_r($years);
```

### Contact Admin
If results aren't showing:
1. Verify exam year is published
2. Check your role/scope
3. Ask admin to publish results
4. Clear cache: `php artisan cache:clear`

---

## 📞 Support

**Questions?** Check the full documentation:
- `ACSEE_RESULTS_IMPLEMENTATION.md` - Complete technical guide
- Admin Panel → Backups & Restore → View logs

---

**Last Updated**: 2026-02-03  
**Version**: 1.0
