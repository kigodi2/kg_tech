# GPA Competence & Public Results Portal - Quick Reference

## What Changed?

### 1. GPA Display Format
**Before:** "3.5000 Grade C (Good)"
**After:** "3.5000 Good"
- Cleaner, simpler format
- Same color coding maintained

### 2. Sorting in Results
**Internal Hierarchy:**
- Passed candidates sorted by GPA (lower = better)
- Then failed/absent candidates
- Within each group: by average mark

**Public Results:**
- Passed candidates first (divisions I-IV)
- Failed/absent candidates last (division 0)
- Within each: by GPA ascending

### 3. Sex Row Display
- Female (F) row shows only if females registered
- Male (M) row shows only if males registered
- Total (T) row always shows

---

## GPA Competence Scale

| GPA Range | Grade | Competence | Color | Display |
|-----------|-------|-----------|-------|---------|
| 1.0-1.4 | A | Excellent | 🟢 #00A82A | 1.2000 Excellent |
| 1.5-2.4 | B | Very Good | 🟢 #1FEE0B | 1.8000 Very Good |
| 2.5-3.4 | C | Good | 🟢 #1FEE0B | 3.2000 Good |
| 3.5-4.4 | D | Average | 🟡 #DEF043 | 4.0000 Average |
| 4.5-5.4 | E | Satisfactory | 🟡 #DEF043 | 5.0000 Satisfactory |
| 5.5-6.4 | S | Unsatisfactory | 🟠 #FF772F | 6.0000 Unsatisfactory |
| 6.5-7.0 | F | Fail | 🔴 #FF272F | 7.0000 Fail |

---

## Public Results Portal Access

### URL: `/results/2026/acsee`

### Search Options:
1. **By Index Number** - Enter candidate index (partial matches work)
2. **By School Name** - Enter school name or code
3. **Both** - Use both fields together for narrower results

### What You'll See:
- Search results with candidate details
- Click candidate to see individual result slip
- Click school to see full school results

---

## School Results Format

### Section 1: Division Performance Summary
Table showing:
- Sex (F/M/T)
- Division counts (I, II, III, IV, 0)
- Incomplete (INC) and Absent (ABS) counts

### Section 2: Detailed Results Table
Complete candidate list with:
- Candidate index number (CNO)
- Gender (SEX)
- Subject combination (COMB)
- All subject grades
- Total marks and average
- Grade from average (GRD)
- Total points (PTS)
- Division (DIV)
- GPA (with color)
- Position (POS)

**Order:** Passed candidates first (by GPA), then ABS/failed

### Section 3: Examination Centre Overall Performance
Summary statistics:
- Total candidates
- Passed vs failed counts
- Average GPA by gender
- Division distribution

---

## Internal Hierarchy Access

### Navigation Path:
`Results` → `ACSEE` → Select Region → Select District → Select School

### What You'll See:
Same format as public results but with additional internal-only features

---

## Important Notes

✓ **GPA Calculation:**
- Based on average of grade points (A=1, B=2, ... F=7)
- Excludes GENERAL STUDIES and BASIC APPLIED MATHEMATICS
- Only includes complete subjects

✓ **Sorting Rules:**
- Lower GPA = Better performance (1.0 is best)
- Passed candidates always listed first
- Sex rows appear dynamically

✓ **Color Display:**
- Colors help identify competence level at a glance
- Consistent across all result views
- NECTA standard colors

---

## Troubleshooting

### Problem: Competence label shows "Grade A (Excellent)"
**Solution:** Clear browser cache (Ctrl+F5 or Cmd+Shift+R)

### Problem: Wrong sorting order
**Solution:** Refresh page - sorting is automatic

### Problem: Sex row not showing
**Solution:** Check if candidates of that gender are registered

### Problem: Public results not accessible
**Solution:** URL should be exactly `/results/2026/acsee`

---

## Quick Tips

1. **Searching:** Partial matches work - enter "1001" to find index starting with 1001
2. **School Search:** Works with both full name and school code
3. **Sorting:** Automatic - don't click columns to sort
4. **Printing:** Use browser print function (Ctrl+P) for professional printout
5. **Export:** Results table can be copied to Excel for analysis

---

## Examples

### Example 1: Search by Index
```
Search Field: 1001
Result: Shows candidate with index starting with 1001
```

### Example 2: Search by School
```
Search Field: MOSHI or MSH
Result: Shows all candidates from school with name/code matching
```

### Example 3: Interpret GPA Display
```
Display: "2.3500 Very Good"
Meaning: Student achieved GPA of 2.35 (Very Good competence)
Grade: B level performance
Color: Green (#1FEE0B)
```

---

## Key Files Modified

- `app/Services/Results/NectaGradingService.php` - GPA mappings
- `app/Helpers/GradeHelpers.php` - Display functions
- `app/Http/Controllers/PublicResultsController.php` - Public API
- `resources/views/hierarchy/school-results.blade.php` - Internal view
- `resources/views/public/results/` - Public views

---

## Support

For issues or questions:
1. Check the deployment documentation
2. Review this quick reference
3. Check system logs for errors
4. Contact system administrator

---

**Last Updated:** February 9, 2026
**Status:** ✅ Live and Operational
