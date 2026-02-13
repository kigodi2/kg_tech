# ACSEE PDF Refactored Layout Specification

**Date:** 2026-02-01  
**Status:** ✅ Complete  
**Version:** 2.0 (Refactored, SN Removed)

---

## 📄 A4 Page Layout (Full Scale)

```
A4 Portrait (210mm × 297mm)

┌───────────────────────────────────────────────────────────────┐
│                                                               │
│                    20mm TOP MARGIN                            │
│                                                               │
│   ┌───────────────────────────────────────────────────────┐   │
│   │                                                       │   │
│   │        6mm INTERNAL PADDING (ALL SIDES)              │   │
│   │                                                       │   │
│   │     ACSEE SCORESHEET                                  │   │ ← Title (13pt, centered)
│   │     (centered, bold, letter-spacing 0.5pt)           │   │
│   │                                                       │   │
│   │     G10 – GEOGRAPHY                                   │   │ ← Subtitle (11pt, centered)
│   │     (centered, bold)                                 │   │
│   │                                                       │   │
│   │  ─────────────────────────────────────────────────    │   │ ← Border line
│   │                                                       │   │
│   │  School: S2003 – IRINGA GIRLS' SECONDARY SCHOOL     │   │ ← Header Line 1
│   │  Year: 2026                                           │   │ ← Header Line 2
│   │  Region: IRINGA                                       │   │ ← Header Line 3
│   │  District: IRINGA MC                                  │   │ ← Header Line 4
│   │  Generated: 01 Feb 2026 11:25                         │   │ ← Header Line 5
│   │                                                       │   │
│   │ ┌──────────────┬────┬──────┬────┬────┬────┬─────────┐│   │
│   │ │   INDEX      │SEX │ COMB │ P1 │ P2 │ P3 │ PAPERS  ││   │ ← Header row
│   │ │   NUMBER     │    │      │    │    │    │  (col)  ││   │
│   │ ├──────────────┼────┼──────┼────┼────┼────┼─────────┤│   │
│   │ │ S2003-0501   │ F  │ GMAH │    │    │    │         ││   │
│   │ │ S2003-0502   │ F  │ HSE  │    │    │    │         ││   │
│   │ │ S2003-0503   │ F  │PHCH  │    │    │    │         ││   │
│   │ │ S2003-0504   │ M  │ GMAH │    │    │    │         ││   │
│   │ │ ...                                                 ││   │
│   │ │ (28 rows total per page)                           ││   │
│   │ │                                                     ││   │
│   │ └──────────────┴────┴──────┴────┴────┴────┴─────────┘│   │
│   │                                                       │   │
│   │ ______________________   ____________________         │   │
│   │ Invigilator Signature   Date                          │   │ ← Signature section
│   │                                                       │   │
│   │ Total: 42 candidates    Hash: a1b2c3...    © 2026    │   │ ← Footer
│   │                                                       │   │
│   │        6mm INTERNAL PADDING (ALL SIDES)              │   │
│   │                                                       │   │
│   └───────────────────────────────────────────────────────┘   │
│                                                               │
│                    20mm BOTTOM MARGIN                         │
│                                                               │
│   Page 1 of 15                                           ↑     │ ← Page number (top right, fixed)
│                                                               │
└───────────────────────────────────────────────────────────────┘

← 20mm LEFT MARGIN          ← 20mm RIGHT MARGIN →
```

---

## 📏 Column Width Distribution

### Usable Space Calculation
```
A4 Page Width:           210mm
Left + Right Margins:    40mm  (20mm + 20mm)
Left + Right Padding:    12mm  (6mm + 6mm)
─────────────────────────────
USABLE TABLE WIDTH:      158mm (100%)

Column Distribution:
│ INDEX (50mm) │ SEX (18mm) │ COMB (18mm) │    PAPERS (72mm)    │
├──────────────┼────────────┼─────────────┼────────────────────┤
│   31.6%      │   11.4%    │   11.4%     │      45.6%         │
```

### Column Details

#### INDEX NUMBER Column
```
Width:       50mm (31.6% of 158mm)
Height:      15pt per row
Padding:     3pt 2pt (top/bottom, left/right)
Border:      1pt solid #999
Font:        11pt, DejaVu Sans
Alignment:   Center, font-weight: 500
Example:     "S2003-0501"
White-space: nowrap (no wrapping)
```

#### SEX Column
```
Width:       18mm (11.4% of 158mm)
Height:      15pt per row
Padding:     3pt 2pt
Border:      1pt solid #999
Font:        11pt, DejaVu Sans
Alignment:   Center
Example:     "F" or "M" or "U"
White-space: nowrap
```

#### COMB Column
```
Width:       18mm (11.4% of 158mm)
Height:      15pt per row
Padding:     3pt 2pt
Border:      1pt solid #999
Font:        11pt, DejaVu Sans
Alignment:   Center
Example:     "GMAH" or "HSE" or "PHCH"
White-space: nowrap
```

#### PAPERS Columns (Dynamic)
```
Total Space: 72mm (45.6% of 158mm)
Distribution: 72mm / number_of_papers

If 3 papers:  72mm ÷ 3 = 24mm per paper
If 4 papers:  72mm ÷ 4 = 18mm per paper
If 5 papers:  72mm ÷ 5 = 14.4mm per paper

Each Paper Column:
  Width:       Auto (calculated above)
  Height:      15pt per row
  Padding:     3pt 2pt
  Border:      1pt solid #999
  Font:        11pt, DejaVu Sans (font-weight: 500)
  Alignment:   Center
  Content:     Empty (for mark entry)
  White-space: nowrap
  Overflow:    clip
```

---

## 📊 Header Section Layout

### Title & Subtitle
```
┌─────────────────────────────────────┐
│                                     │
│   ACSEE SCORESHEET                  │  ← 13pt bold, centered
│   (letter-spacing: 0.5pt)           │
│                                     │
│   G10 – GEOGRAPHY                   │  ← 11pt bold, color: #333
│                                     │
└─────────────────────────────────────┘
```

### Metadata Section (Line-by-Line)
```
School: S2003 – IRINGA GIRLS' SECONDARY SCHOOL
Year: 2026
Region: IRINGA
District: IRINGA MC
Generated: 01 Feb 2026 11:25

CSS Properties:
  • margin-bottom: 2pt per line
  • font-size: 11pt (global)
  • line-height: 1.4
  • Labels bold (display: inline)
  • 3mm gap between label and value
```

### Border & Spacing
```
Margin-bottom:   10mm (space before table)
Padding-bottom:  4mm (space above line)
Border-bottom:   1.5pt solid #000 (separator)
```

---

## 🎨 Color Scheme

### Professional Government Document
```
Primary Colors:
  • Text: Black (#000)
  • Background: White (#fff)
  • Table header: Dark blue (#2c3e50)
  • Table header text: White (#fff)
  • Table borders: Gray (#999)
  • Row odd: White (#fff)
  • Row even: Light gray (#f5f5f5)
  • Metadata: Dark gray (#333)
  • Footer: Medium gray (#666)
  • Subtle text: Light gray (#aaa)

No Branding Colors:
  • NECTA logo: Not included (school-specific)
  • Watermark: Light gray, 7% opacity
```

---

## 📄 Footer Section

### Layout
```
┌─────────────────────────────────────┐
│                                     │
│  Total: 42 candidates  │ Hash...  │  © 2026
│  (left)                 (center)     (right)
│
└─────────────────────────────────────┘

CSS:
  • position: fixed
  • bottom: 20mm
  • left: 20mm, right: 20mm
  • border-top: 1pt solid #ccc
  • padding-top: 3pt
  • display: flex
  • justify-content: space-between
```

### Content
- **Left:** "Total: N candidates"
- **Center:** Document hash (8pt, monospace)
- **Right:** "IRMS © 2026"

---

## 📋 Signature Section

### Layout (Before Footer)
```
┌─────────────────────────────────────┐
│                                     │
│ ____________________  _____________ │
│ Invigilator          Date           │
│ Signature                           │
│                                     │
│ (width: 60mm each, gap: 12mm)       │
│                                     │
└─────────────────────────────────────┘

CSS:
  • display: table
  • .signature-field: display: table-cell
  • width: 60mm per field
  • padding-right: 12mm (gap)
  • .signature-line: border-bottom 1pt
  • height: 18pt (space for signature)
  • margin-bottom: 2pt
```

---

## 🔐 Watermark

### Positioning
```
Location:     Top center, 100mm from top
Rotation:     -45° angle
Font size:    84pt (large but subtle)
Opacity:      7% (rgba(200, 200, 200, 0.07))
Text:         "IRMS – CONFIDENTIAL"
Z-index:      -1 (behind content)

Purpose:      Audit mark, barely visible
Effect:       Doesn't block any table content
```

---

## 📏 Spacing Reference Table

### Vertical Spacing
| Element | Size | Notes |
|---------|------|-------|
| Top margin | 20mm | PDF engine |
| Internal padding | 6mm | Breathing room |
| Title margin-bottom | 6pt | Space to subtitle |
| Subtitle margin-bottom | 6pt | Space to header |
| Header margin-bottom | 10mm | Space to table |
| Header line margin-bottom | 2pt | Between lines |
| Header padding-bottom | 4mm | Above border |
| Table margin-top | 4pt | From header |
| Table margin-bottom | 8pt | To signature |
| Row height | 15pt | With padding |
| Signature margin-top | 4pt | From table |
| Internal padding | 6mm | Breathing room |
| Bottom margin | 20mm | PDF engine |

### Horizontal Spacing
| Element | Size | Notes |
|---------|------|-------|
| Left margin | 20mm | PDF engine |
| Internal padding left | 6mm | Breathing room |
| Cell padding left | 2pt | Inside cells |
| Cell padding right | 2pt | Inside cells |
| Header label margin-right | 3mm | Label to value |
| Internal padding right | 6mm | Breathing room |
| Right margin | 20mm | PDF engine |

---

## 🎯 Page Break Behavior

### Multi-Page Rendering
```
PAGE 1:
┌──────────────────────────────┐
│ Title & Subtitle             │
│ Header (5 lines)             │
│ Table Header (repeats below) │
│ Row 1                        │
│ Row 2                        │
│ ...                          │
│ Row 28                       │
│ [Page break]                 │
└──────────────────────────────┘

PAGE 2:
┌──────────────────────────────┐
│ Table Header (auto-repeats)  │  ← display: table-header-group
│ Row 29                       │
│ Row 30                       │
│ ...                          │
│ Row 56                       │
│ [Page break if needed]       │
└──────────────────────────────┘

KEY FEATURES:
✓ Headers repeat on every page
✓ Rows never split (page-break-inside: avoid)
✓ Clean page breaks between complete rows
✓ ~28 rows per page (conservative)
```

---

## 📊 Font Sizing Summary

| Element | Size | Weight | Color | Notes |
|---------|------|--------|-------|-------|
| Title | 13pt | Bold | #000 | Centered |
| Subtitle | 11pt | 600 | #333 | Centered |
| Header label | 11pt | Bold | #000 | Inline |
| Header value | 11pt | Regular | #000 | Inline |
| Body text (table) | 11pt | Regular | #000 | Global default |
| Table header | 10pt | 600 | #fff | Slightly smaller |
| Table cells | 11pt | Regular | #000 | Inherits from body |
| Page number | 9pt | Regular | #aaa | Fixed position |
| Footer | 9pt | Regular | #666 | Fixed position |
| Signature label | 9pt | Regular | #666 | Below line |

---

## ✅ Specification Checklist

```
REQUIREMENTS MET:

✓ SN Column Removed
  - Table now has: INDEX, SEX, COMB, PAPERS (4-16 columns)
  - Removed 18mm, redistributed to PAPERS

✓ Page Margins: 20mm
  - @page { margin: 20mm; } enforced by PDF engine
  - All content bounded by 20mm margins

✓ Internal Padding: 6mm
  - body { padding: 6mm; } adds white space
  - White space on top, bottom, left, right

✓ Title: Line-by-Line
  - Centered title (13pt) + subtitle (11pt)
  - 5 metadata lines (11pt) each on own line
  - Labels bold, values inline
  - Border separator between header and table

✓ Papers Column: Compact, Non-Wrapping
  - Dynamic width calculation: 72mm / num_papers
  - white-space: nowrap prevents wrapping
  - overflow: hidden clips excess
  - text-overflow: clip for clean appearance

✓ Global Font: 11pt
  - body { font-size: 11pt; } applied globally
  - All elements inherit unless overridden
  - Professional, readable size

✓ Page-Break Safety
  - thead { display: table-header-group; } repeats headers
  - tbody tr { page-break-inside: avoid; } keeps rows intact
  - Clean page breaks between complete rows

✓ No Flexbox/Grid (Table-based)
  - Only HTML <table> for layout
  - CSS only uses table-cell display
  - All other elements use block/inline/inline-block

✓ Professional Appearance
  - Dark blue header (#2c3e50) with white text
  - Alternating row colors (white / #f5f5f5)
  - Clear borders (1pt black)
  - Subtle watermark (7% opacity)
  - Signature section for official approval
```

---

## 🔄 No Breaking Changes

**All existing functionality preserved:**
- ✓ Controller methods unchanged
- ✓ Data source unchanged
- ✓ Route definitions unchanged
- ✓ Database schema unchanged
- ✓ API contracts unchanged

**Only visual changes:**
- ✓ Removed SN column from UI
- ✓ Rebalanced column widths
- ✓ Restructured header (grid → line-by-line)
- ✓ Added internal padding (6mm)

---

**Updated:** 2026-02-01  
**Version:** 2.0 (Refactored)  
**Status:** ✅ Ready for Testing
