# ACSEE Scoresheet PDF Layout Diagram

**Visual Reference for A4 Portrait PDF Rendering**

---

## 📄 A4 Page Layout (Full Scale)

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│                     Page Info: 1/2                      │  ← 8pt, right-aligned
│                                                         │
│              ┏━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓             │
│              ┃     ACSEE SCORESHEET        ┃             │
│              ┃   G10 – GEOGRAPHY           ┃             │
│              ┃━━━━━━━━━━━━━━━━━━━━━━━━━━━━┃             │  ← Header
│              ┃ School: S2003 – IRINGA...   ┃             │   (35-40mm)
│              ┃ Year: 2026  Region: IRINGA  ┃             │
│              ┃ District: IRINGA MC   Hash: ┃             │
│              ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛             │
│                                                         │
│  ┌───┬──────────────────┬─────┬──────┬────┬────┬────┐   │
│  │SN │ INDEX NUMBER     │ SEX │ COMB │ P1 │ P2 │ P3 │   │  ← Header row
│  ├───┼──────────────────┼─────┼──────┼────┼────┼────┤   │     (repeats each page)
│  │1  │ S2003-501        │ F   │ GMAH │    │    │    │   │
│  │2  │ S2003-502        │ F   │ HSE  │    │    │    │   │
│  │3  │ S2003-503        │ M   │ PHCH │    │    │    │   │
│  │4  │ S2003-504        │ M   │ GMAH │    │    │    │   │
│  │5  │ S2003-505        │ F   │ HSE  │    │    │    │   │
│  │... (rows continue...)                           │   │
│  │31 │ S2003-531        │ M   │ GMAH │    │    │    │   │
│  │32 │ S2003-532        │ F   │ PHCH │    │    │    │   │
│  └───┴──────────────────┴─────┴──────┴────┴────┴────┘   │  ← Table
│                                                         │     (~32 rows per page)
│    ________________          ________________          │
│   │Invigilator    │        │Date            │         │  ← Signature section
│   │Signature      │        │                │         │     (~12pt space)
│                                                         │
│  Total: 42 candidates    Hash: a1b2c3d4e5...      ©2026
│  ─────────────────────────────────────────────────────  ← Footer (8pt)
│                                                         │
│                                                         │
│                                                         │
│           [WATERMARK: "IRMS – CONFIDENTIAL"            │
│            80mm from top, 7% opacity, -45°]            │
│                                                         │
└─────────────────────────────────────────────────────────┘

  ↑                                                        ↑
 20mm                                                     20mm
 LEFT                                                    RIGHT

     ↑ 20mm TOP MARGIN
     ↓ 20mm BOTTOM MARGIN

══════════════════════════════════════════════════════════

A4 Page Dimensions:
  Physical: 210mm × 297mm
  Usable:   170mm × 257mm (after 20mm margins)
```

---

## 📏 Column Width Breakdown

### Total Usable Width: 170mm

```
┌─────────────────────────────────────────────────────────┐
│ ├─SN─┤ ├──INDEX NUMBER──┤ ├SEX┤ ├COMB┤ │    PAPERS    │ │
│ │18mm│ │     55mm       │ │18mm│18mm│ │   61mm split  │ │
│ └─────┘ └────────────────┘ └───┘└────┘ └──────────────┘ │
│  ▲ 10.6%     ▲ 32.4%         ▲ 10.6%  ▲ 35.9%          │
│                                                          │
│   170mm TOTAL (100%)                                    │
└─────────────────────────────────────────────────────────┘

Column Distribution:
  SN:           18mm (10.6%)  ← Row counter
  INDEX:        55mm (32.4%)  ← Candidate ID (LARGEST)
  SEX:          18mm (10.6%)  ← M/F/U
  COMB:         18mm (10.6%)  ← Combination code
  ──────────────────────────
  FIXED:       109mm (64.1%)

  PAPERS:       61mm (35.9%)  ← Split equally among papers
                              
  For 3 papers:  61 ÷ 3 = 20.33mm per paper
  For 4 papers:  61 ÷ 4 = 15.25mm per paper
  For 5 papers:  61 ÷ 5 = 12.20mm per paper
```

---

## 🎨 Header Section Details

### Dimensions & Spacing

```
┌────────────────────────────────────────────────────────┐
│                                                        │
│              ACSEE SCORESHEET                          │  13pt bold
│          (letter-spacing: 0.5pt)                       │
│                                                        │
│        G10 – GEOGRAPHY                                 │  10pt font-weight: 600
│        (color: #333)                                   │
│                                                        │
│ School: S2003 – IRINGA...   Year: 2026  Region: IRINGA│  9pt
│ District: IRINGA MC   Generated: 01/02/2026  Hash:... │  9pt
│                                                        │
├────────────────────────────────────────────────────────┤  1.5pt border
│                                                        │
│                   (padding-bottom: 8pt)                │
│                                                        │
└────────────────────────────────────────────────────────┘

Header section:
  • margin-bottom: 12pt
  • padding-bottom: 8pt
  • border-bottom: 1.5pt solid #000
  • Total height: ~35-40mm
  • Meta items in 2 rows × 3 columns (table layout)
```

---

## 📊 Table Row Details

### Single Row Structure

```
┌───┬──────────────────┬─────┬──────┬────┬────┬────┐
│32 │ S2003-532        │ F   │ PHCH │ 78 │ 82 │ 85 │
└───┴──────────────────┴─────┴──────┴────┴────┴────┘
 ▲   ▲                  ▲     ▲      ▲ ▲  ▲ ▲  ▲ ▲
18mm 55mm              18mm  18mm   20mm ea per paper
 ▲    ▲                ▲     ▲       ▲
 SN  INDEX             SEX   COMB   PAPERS (if 3)

Cell Styling:
  • height: 14pt (with padding)
  • padding: 3pt 2pt (top/bottom, left/right)
  • border: 0.75pt solid #999
  • text-align: center
  • vertical-align: middle
  • white-space: nowrap
  • overflow: hidden
  • font-size: 10pt

Row Properties:
  • page-break-inside: avoid (never split rows)
  • Alternating colors: #fff / #f5f5f5
```

---

## 🔑 Key Spacing & Sizing

### Vertical Spacing (from top)

```
0mm      ─┐ Top Margin (20mm)
           │
20mm     ─┤ Page Number (fixed, top-right)
           │
           ├─ Header Section (35-40mm)
           │
55mm     ─┤ Table Start
           │  Header Row (5mm)
           │
60mm     ─┤ Data Rows (14pt height each)
           │  ≈ 2.1mm per row
           │
192mm    ─┤ Table End (32 rows × 2.1mm + 14mm overhead)
           │
           ├─ Signature Section (12pt)
           │
277mm    ─┤ Footer (fixed, bottom: 15mm)
           │
297mm    ─┴ Bottom Margin (20mm)

Usable height: 257mm
Used by:
  • Header: 40mm
  • Table: 192mm
  • Signature: 12pt
  • Footer: 20mm
  ─────────────
  • Total: 257mm ✓
```

---

## 🎨 Color Scheme

### Professional NECTA-Style

```
Header Background:
  • Color: White (#fff)
  • Border: 1.5pt black (#000)
  
Table Header:
  • Background: Dark Blue (#2c3e50)
  • Text: White (#fff)
  • Font-weight: 600
  • Height: 5mm (16pt)
  
Table Body:
  • Row odd:  White (#fff)
  • Row even: Light Gray (#f5f5f5)
  • Borders:  Light Gray (#999)
  
Text:
  • Main:     Black (#000)
  • Meta:     Dark Gray (#444)
  • Footer:   Medium Gray (#666)
  • Subtle:   Light Gray (#aaa)

Watermark:
  • Color: Light Gray (rgba(200, 200, 200, 0.07))
  • Opacity: 7% (barely visible, doesn't block content)
```

---

## 📄 Multi-Page Layout

### Page Breaks & Headers

```
PAGE 1:
┌─────────────────────────────┐
│  ACSEE SCORESHEET           │
│  G10 – GEOGRAPHY            │
├─────────────────────────────┤
│ SN │ INDEX │ SEX │ COMB │... │
├────┼───────┼─────┼──────┼────┤
│ 1  │ ...   │ F   │ ...  │    │
│ 2  │ ...   │ M   │ ...  │    │
│... (32 rows total per page)
└─────────────────────────────┘

PAGE 2:
┌─────────────────────────────┐
│ SN │ INDEX │ SEX │ COMB │... │  ← Headers repeat (table-header-group)
├────┼───────┼─────┼──────┼────┤
│33  │ ...   │ F   │ ...  │    │  ← Continuation from previous page
│34  │ ...   │ M   │ ...  │    │
│... (remaining rows)
└─────────────────────────────┘

KEY FEATURES:
  ✓ Headers repeat automatically
  ✓ Rows never split across pages
  ✓ ~32 rows per page (deterministic)
  ✓ Clean page breaks between complete rows
```

---

## 🔐 Watermark Positioning

### Subtle Background Mark

```
Top of page:
┌─────────────────────────────┐
│                             │
│              (WATERMARK)    │  ← 80mm from top
│       IRMS – CONFIDENTIAL    │  ← 84pt font
│          [rotated -45°]      │  ← Barely visible (7% opacity)
│    [centered horizontally]   │
│                             │
│  ACSEE SCORESHEET           │  ← Actual content (visible, on top)
│  Header...                  │
│                             │
└─────────────────────────────┘

CSS Properties:
  • position: fixed
  • top: 80mm
  • left: 50%
  • transform: translate(-50%, 0) rotate(-45deg)
  • font-size: 84pt
  • color: rgba(200, 200, 200, 0.07)  [7% opacity]
  • z-index: -1  [behind content]
  • pointer-events: none  [won't interfere]

Result: Subtle audit mark, doesn't block table content
```

---

## 📋 Signature Section

### Bottom Section (Before Footer)

```
┌─────────────────────────────────────────┐
│                                         │
│ ___________________   _________________  │  ← Signature lines
│ Invigilator Signature   Date            │  ← Labels (8pt, gray)
│                                         │
│ Width per field: 70mm                  │
│ Gap between: 15mm                      │
│                                         │
│ ─────────────────────────────────────── │  ← Footer border
│ Total: 42 candidates    Hash: ...  ©2026
└─────────────────────────────────────────┘

CSS:
  .signature-section { display: table; margin-top: 6pt; }
  .signature-field { display: table-cell; width: 70mm; }
  .signature-line { border-bottom: 0.75pt; height: 20pt; }
  .signature-label { font-size: 8pt; color: #666; }
```

---

## ✅ Specification Compliance Checklist

```
╔═══════════════════════════════════════════════════════╗
║             PDF RENDERING CHECKLIST                   ║
╠═════════════════╦═══════════════════════════════════╣
║ REQUIREMENT     ║ STATUS                            ║
╠═════════════════╬═══════════════════════════════════╣
║ A4 Page Size    ║ ✓ @page { size: A4 portrait; }   ║
║ 20mm Margins    ║ ✓ @page { margin: 20mm; }        ║
║ 11pt Font       ║ ✓ body { font-size: 11pt; }      ║
║ PDF-Safe Fonts  ║ ✓ DejaVu Sans + fallbacks        ║
║ Table Layout    ║ ✓ table-layout: fixed            ║
║ No Overflow     ║ ✓ 100% width = 170mm ≤ A4 usable ║
║ No Wrapping     ║ ✓ white-space: nowrap            ║
║ Page Breaks     ║ ✓ table-header-group + avoid     ║
║ No Flexbox      ║ ✓ Table-based layouts only       ║
║ No Grid         ║ ✓ Table-based layouts only       ║
║ Deterministic   ║ ✓ All units absolute (mm/pt)     ║
║ Professional    ║ ✓ NECTA-compliant design         ║
║ Comments        ║ ✓ Inline CSS explaining choices  ║
╚═════════════════╩═══════════════════════════════════╝
```

---

## 📐 Mathematical Validation

### A4 Geometry

```
Physical Page: 210mm (W) × 297mm (H)
Margins: 20mm all sides
───────────────────────────────────────
Usable Width:  210 - (20+20) = 170mm ✓
Usable Height: 297 - (20+20) = 257mm ✓

Table Columns (170mm total):
  SN:     18mm
  INDEX:  55mm
  SEX:    18mm
  COMB:   18mm
  PAPERS: 61mm
  ─────────────
  TOTAL: 170mm ✓

Rows Per Page (257mm usable height):
  Header: 40mm
  Table:  192mm ÷ 14pt (2.1mm/row) = 91 rows (max)
  Conservative: 32 rows/page
  Signature: 12pt
  Footer: 20mm
  ─────────────
  TOTAL: 257mm ✓

Paper Column Distribution:
  3 papers: 61 ÷ 3 = 20.33mm each ✓
  4 papers: 61 ÷ 4 = 15.25mm each ✓
  5 papers: 61 ÷ 5 = 12.20mm each ✓
```

---

**Layout Version:** 1.0  
**PDF Engine:** Dompdf / mPDF Compatible  
**Status:** ✅ PRODUCTION READY  
**Last Updated:** 2026-02-01
