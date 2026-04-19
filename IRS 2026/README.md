# IRS 2026 - PSLE School Results Report (v3.0 - A3 PDF Replica)

## Exact FPDF Clone

This VBA produces a **pixel-perfect A3 Portrait PDF** matching the FPDF output from `/results/psle/reports`:

| FPDF Block | ✅ Replicated |
|---|---|
| Dark navy top strip (4mm) | ✅ |
| PMO / RALG / Zones header (Helvetica Bold 11, navy) | ✅ |
| School name + code centered | ✅ |
| Tanzania flag color strip (Green 30% / Yellow 24% / Black 16% / Navy 30%) | ✅ |
| Overview: Centre, Candidates Sat, School Average, Pass Rates, Top Candidate | ✅ |
| Grade Performance table (SEX/REGIST/SAT/.../A/B/C/D/E/INC/ABS) | ✅ |
| Candidates table: CAND.NO / PREM / SEX / **DETAILED SUBJECTS RESULT** / TOTAL / GRD / AGGT / GPA / POS | ✅ |
| Inline format: `KISW - 35 'B', ENG - 42 'A', ...` | ✅ |
| Subject Performance table (CODE/NAME/REG/SAT/ABS/A-E/A-C/A-D/AVG/GRD/COMPETENCE) | ✅ |
| Competence colors (Green/LightGreen/Yellow/Orange/Red) | ✅ |
| Code39 barcode (PSLE-SCHOOLCODE-DATE-NODE) | ✅ |
| Footer: generation timestamp, IRMS node, flag strip, page number | ✅ |
| A3 Portrait page setup (297×420mm, 6mm margins) | ✅ |

## 4 Macros

| Macro | Purpose |
|---|---|
| **`SetupWorkbook`** | Creates all 5 sheets (run once) |
| **`RefreshMarkEntry`** | Loads candidates for selected subject |
| **`GenerateReport`** | Builds A3 PDF-ready report from all data |
| **`ExportToPDF`** | Saves the report as an actual PDF file |

## Workflow

1. **School Info** → Fill school name, code, council, region
2. **Registration** → Add candidates, tick Y/N for each subject
3. **Mark Entry** → Select subject dropdown → RefreshMarkEntry → enter marks (0-50)
4. **GenerateReport** → Produces the exact A3 layout
5. **ExportToPDF** → Save as PDF file (opens automatically)

## Barcode

If you have the **"Free 3 of 9"** font installed, the barcode renders as scannable Code39. Otherwise it displays as the barcode text payload.
