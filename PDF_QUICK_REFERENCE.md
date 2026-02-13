# ACSEE Scoresheet PDF - Quick Reference

## Column Widths at a Glance

| Column | Width | % | Use |
|--------|-------|---|-----|
| INDEX NUMBER | 59.5mm | 32% | Candidate ID (widest) |
| SEX | 7.4mm | 4% | Gender (M/F/U) |
| COMB | 9.3mm | 5% | Combo code |
| Papers | Variable | 59% | Mark columns (shared) |

**By Subject Type:**
- 2 papers (History, Geo, Econ): 54.9mm each
- 3 papers (Physics, Chem, Bio): 36.6mm each
- With practical: 27.5mm each

## Page Breaks

✅ **Headers repeat on every page** (display: table-header-group)
✅ **Rows never break** (page-break-inside: avoid)
✅ **No orphaned content** (cells protected from breaking)

## PDF Output

- **Format**: A4 Portrait
- **Margins**: 15mm (top), 10mm (bottom), 12mm (sides)
- **Usable**: 186 × 272mm
- **Rows/page**: ~33 rows (10mm height each)
- **Multi-page**: Headers repeat on each page

## Files Created

1. **PDF_LAYOUT_MOCK.md** - Exact dimensions and column widths
2. **PDF_PAGE_BREAK_SAFE_IMPLEMENTATION.md** - CSS specifications
3. **PDF_LAYOUT_AND_PAGEBREAK_FINAL.md** - Complete documentation

## CSS Properties Applied

```css
table { page-break-inside: avoid; }
thead { display: table-header-group; }
tbody { page-break-inside: auto; }
tbody tr { page-break-inside: avoid; }
tbody td { page-break-inside: avoid; }
```

## Verified

✅ All test subjects (History, Physics, Chemistry, Biology)
✅ Multi-page rendering
✅ Header repetition
✅ Row integrity across pages
✅ Professional formatting

**Status: PRODUCTION READY**
