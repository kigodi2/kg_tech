# PDF Layout Verification Quick Guide

**Template:** `resources/views/mark-entry/pdf/scoresheet.blade.php`  
**Status:** A4-safe, 20mm margins, 11pt font, fixed layout  

---

## ✅ Implementation Checklist

### CSS Rules Applied
- [x] `@page { size: A4 portrait; margin: 20mm; }`
- [x] `body { font-family: DejaVu Sans; font-size: 11pt; }`
- [x] `table { table-layout: fixed; width: 100%; }`
- [x] `thead { display: table-header-group; }`
- [x] `tbody tr { page-break-inside: avoid; }`
- [x] `td { white-space: nowrap; overflow: hidden; }`

### Column Widths (Total: 170mm = A4 - 40mm margins)
```
SN:              18mm (10.6%)
INDEX NUMBER:    55mm (32.4%)
SEX:             18mm (10.6%)
COMB:            18mm (10.6%)
PAPERS (shared): 61mm (35.9%)
─────────────────────────────
TOTAL:          170mm (100%)
```

### Page Layout
- **Header Height:** ~35-40mm (compact)
- **Table:** Full width (170mm), ~32 rows per page
- **Footer:** Fixed at bottom (15mm from base)
- **Watermark:** Top-right, subtle (7% opacity)
- **Margins:** All sides 20mm (enforced by @page)

---

## 🧪 How to Test

### Test 1: Generate a PDF
```bash
# Navigate to Mark Entry → ACSEE Scores
# Select: School, Year, Subject
# Click: "Print Scoresheet (PDF)"
# Download PDF
```

### Test 2: Verify Margins (Adobe Reader)
1. Open PDF in Adobe Reader
2. View → Page Display → Show Ruler
3. Check: All content is within the gray ruler area
4. Measure: Each side should show 20mm from edge

### Test 3: Verify Font
1. View → Page Properties
2. Check: Fonts should list "DejaVu Sans" or similar
3. Size: ~11pt for body text, ~10pt for table

### Test 4: Verify Table Layout
1. Print Preview (Ctrl+P or Cmd+P)
2. Check: Table width = full usable page width
3. Check: INDEX NUMBER column is ~55mm (roughly 1/3 of table width)
4. Check: Paper columns are equal and compact

### Test 5: Verify Page Breaks
1. Open PDF in Adobe Reader
2. Navigate through pages
3. Check: Headers repeat on every page
4. Check: Rows are never split across pages
5. Count rows per page: Should be ~32 rows

### Test 6: Verify Text Wrapping
1. Look for any blank cells or data
2. Check: Mark fields never show wrapped text
3. Check: Index numbers are complete (never split)
4. Check: Sex codes are single character (M/F/U)

---

## 🎯 Expected Appearance

### Header (Page 1)
```
                    ACSEE SCORESHEET
               G10 – GEOGRAPHY
School: S2003 – IRINGA GIRLS SECONDARY SCHOOL    Year: 2026    Region: IRINGA
District: IRINGA MC    Generated: 01/02/2026 11:25    Hash: a1b2c3d4e5f6...
```

### Table Header Row
```
┌────┬─────────────────────┬─────┬──────┬──────┬──────┬──────┐
│ SN │  INDEX NUMBER       │ SEX │ COMB │  P1  │  P2  │  P3  │
├────┼─────────────────────┼─────┼──────┼──────┼──────┼──────┤
│ 1  │ S2003-501           │ F   │ GMAH │      │      │      │
│ 2  │ S2003-502           │ F   │ HSE  │      │      │      │
│ 3  │ S2003-503           │ M   │ PHCH │      │      │      │
└────┴─────────────────────┴─────┴──────┴──────┴──────┴──────┘
```

### Footer (Every Page)
```
Total: 42 candidates        Hash: a1b2c3d4e5...                    IRMS © 2026
```

---

## ⚠️ Common Issues & Solutions

| Issue | Symptom | Solution |
|-------|---------|----------|
| **Content exceeds margin** | Text bleeds off page | Margins defined in `@page` rule; check PDF viewer zoom |
| **Headers don't repeat** | Only appears on page 1 | Verify `thead { display: table-header-group; }` |
| **Rows split** | Row data appears on two pages | `tbody tr { page-break-inside: avoid; }` must be present |
| **Text wraps** | "S2003-5" on line 1, "01" on line 2 | Check `white-space: nowrap; overflow: hidden;` on td |
| **Font looks wrong** | Doesn't match spec | PDF engine needs DejaVu fonts; check system fonts |
| **Table too wide** | Content overflows | Column widths must sum to ≤170mm; check math |

---

## 📊 Calculation Reference

### Usable Space Calculation
```
A4 Page Width:           210mm
Left + Right Margins:    40mm  (20mm + 20mm)
Usable Table Width:      170mm
─────────────────────────────
Per 100% width:          170mm
```

### Paper Column Calculation
```
Fixed columns:    SN + INDEX + SEX + COMB = 109mm
Paper space:      170mm - 109mm = 61mm

If 3 papers:      61mm ÷ 3 = 20.33mm per paper
If 4 papers:      61mm ÷ 4 = 15.25mm per paper
```

### Rows Per Page Calculation
```
A4 Height:                297mm
Top + Bottom Margins:     40mm  (20mm + 20mm)
Header section:           40mm  (title + meta)
Footer section:           20mm  (signature + footer)
Available for table:      197mm

Row height:               6pt ≈ 2.1mm (with padding)
Rows per page:            197mm ÷ 2.1mm ≈ 94 rows (conservative: 32)
```

---

## 🔄 Deployment Steps

1. **Backup Current Version**
   ```bash
   git cp resources/views/mark-entry/pdf/scoresheet.blade.php \
        resources/views/mark-entry/pdf/scoresheet.blade.php.backup
   ```

2. **Deploy New Template**
   ```bash
   # Already in place: resources/views/mark-entry/pdf/scoresheet.blade.php
   git add resources/views/mark-entry/pdf/scoresheet.blade.php
   git add PDF_RENDERING_SPECIFICATION.md
   git add PDF_QUICK_VERIFY.md
   ```

3. **Test in Staging**
   - Generate 5-10 test PDFs
   - Verify against checklist above
   - Print-test (Print to PDF in browser)

4. **Production Rollout**
   - Deploy code
   - Monitor error logs: `storage/logs/laravel.log`
   - User testing: Ask 3-5 users to generate PDFs

---

## 📞 Reference Documents

- **Full Spec:** `PDF_RENDERING_SPECIFICATION.md`
- **Template:** `resources/views/mark-entry/pdf/scoresheet.blade.php`
- **Controller:** `app/Http/Controllers/MarkEntryController.php`

---

**Last Updated:** 2026-02-01  
**Version:** 1.0  
**Status:** READY FOR TESTING
