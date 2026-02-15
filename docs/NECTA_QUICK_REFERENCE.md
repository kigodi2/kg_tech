# NECTA Quick Reference Card

## Private Candidate Registration Flow

```
Register in /registration/candidates
    ↓
Fill form with:
  - Index: P0652-0501 (auto-detected as PRIVATE)
  - Name, Sex, School, Exam Year, Exam Type: ACSEE
  - Leave Combination blank
    ↓
Click "Register Candidate"
    ↓
Go to EXAM TYPE → ACSEE
    ↓
Find candidate → Click Edit icon
    ↓
Modal opens → Switch to "Manual Subject Selection"
    ↓
Select Exam Year (required)
    ↓
Check subjects:
  ☑ General Studies (111) — MANDATORY
  ☑ Math (code 001)
  ☑ Physics (code 002)
  ☑ Chemistry (code 003)
  [+ optional more subjects]
    ↓
Click "Save Allocation"
    ↓
✓ Subjects allocated successfully
```

---

## Critical NECTA Rules

| Rule | Error If Violated |
|------|-------------------|
| **General Studies (code 111) mandatory** | `"General Studies (code 111) is mandatory for ACSEE candidates"` |
| **Min 3 principal subjects** | `"Minimum 3 principal subjects required (found X)"` |
| **Principal = all except GS** | Auto-calculated; no manual configuration |
| **No duplicates** | Auto-removed with warning |
| **Exam year required** | `"Please select an exam year"` |

---

## Index Number Format

```
P 0652 - 0501
│  │     └─ Serial (4 digits: 0001-9999)
│  └─ Centre code (4 digits: 0000-9999)
└─ Prefix: S (School) or P (Private)
```

**Examples:**
- `S0445-0001` → SCHOOL candidate from centre 0445, serial 0001
- `P0652-0501` → PRIVATE candidate from centre 0652, serial 0501

---

## Database Queries

### Check If Candidate's Subjects Were Saved
```sql
SELECT s.code, s.name, css.is_principal, css.source
FROM candidate_subject_selections css
JOIN subjects s ON s.id = css.subject_id
WHERE css.candidate_id = (SELECT id FROM candidates WHERE candidate_id = 'P0652-0501')
  AND css.year = 2026
ORDER BY css.created_at;
```

### Verify NECTA Compliance
```sql
-- General Studies present?
SELECT COUNT(*) FROM candidate_subject_selections css
JOIN subjects s ON s.id = css.subject_id
WHERE s.code = '111'
  AND css.candidate_id = (SELECT id FROM candidates WHERE candidate_id = 'P0652-0501')
  AND css.year = 2026;
-- Should return: 1

-- Minimum 3 principals?
SELECT COUNT(*) FROM candidate_subject_selections
WHERE is_principal = true
  AND candidate_id = (SELECT id FROM candidates WHERE candidate_id = 'P0652-0501')
  AND year = 2026;
-- Should return: >= 3
```

---

## Common Errors & Fixes

| Error | Fix |
|-------|-----|
| `"Centre not found in system"` | Register the private centre (P####) in SETTINGS → Schools |
| `"General Studies is mandatory"` | Check the General Studies (code 111) checkbox |
| `"Minimum 3 principal subjects required"` | Select 3+ subjects besides General Studies |
| `"This index number is already registered"` | Use a different serial number (last 4 digits) |
| Modal won't save | Check browser console (F12) for validation errors |
| Subjects not showing in list | Refresh page; check if exam year is properly selected |

---

## Field Checklist for Registration

| Field | Required | For Private? | Notes |
|-------|----------|-------------|-------|
| Index Number | ✅ | ✅ | Format: P0652-0501 |
| Full Name | ✅ | ✅ | Any text |
| Sex | ✅ | ✅ | M or F |
| School | ✅ | ✅ | Private centre (e.g., P0652) |
| Exam Type | ✅ | ✅ | Must be ACSEE |
| Exam Year | ✅ | ✅ | E.g., 2026 |
| Combination | ❌ | ❌ | LEAVE BLANK for private |
| Candidate Type | auto | auto | Auto-set to PRIVATE (P prefix) |

---

## Where to Find Things

| Task | Location |
|------|----------|
| Register a candidate | `/registration/candidates` → Click "Register" |
| Allocate subjects | `/exam-types/acsee` → Find candidate → Click edit icon |
| View allocations (SQL) | Query `candidate_subject_selections` table |
| Configure subjects | `/settings` (admin only) |
| Configure exam years | `/settings` (admin only) |
| Create private centre | `/settings` → Schools → Add with registration_number = P#### |

---

## Key Tables & Relationships

```
candidates (1) ──── (M) candidate_subject_selections
    │                      │
    ├─ school_id         ├─ subject_id
    ├─ candidate_id      ├─ is_principal
    └─ candidate_type    ├─ source ('manual', 'template', 'import')
                         └─ created_by (user who allocated)

exam_years (1) ──── (M) candidate_subject_selections
    │
    └─ year_label (e.g., '2026')

subjects (1) ──── (M) candidate_subject_selections
    │
    ├─ code (e.g., '111' for General Studies)
    └─ name (e.g., 'GENERAL STUDIES')

exam_types (1) ──── (M) candidate_subject_selections
    │
    └─ code ('ACSEE')
```

---

## API Endpoints

### Validate Index Number
```
GET /api/index-number/validate?index_number=P0652-0501&exam_year_id=1&exam_type_id=1
```
**Response:** `{ ok: true, parsed: { ... }, errors: [], ... }`

### Allocate Subjects
```
POST /api/exam-types/acsee/allocate-subjects
Content-Type: application/json

{
    "candidate_id": 123,
    "exam_year_id": 1,
    "subject_ids": [111, 1, 2, 3],
    "is_principal_map": { "111": false, "1": true, "2": true, "3": true },
    "replace_allocations": false,
    "source": "manual"
}
```
**Response:** `{ ok: true, message: "...", allocated_subjects: [...] }`

### Get Allocations
```
GET /api/exam-types/ACSEE/candidates/{candidate_id}/allocations?exam_year=2026
```
**Response:** `{ ok: true, allocations: [...] }`

---

## Useful Commands

```bash
# Open tinker to test
php artisan tinker

# Find a candidate
$c = App\Models\Candidate::where('candidate_id', 'P0652-0501')->first();

# See their subjects
$c->subjectSelections()->with('subject')->where('year', 2026)->get();

# Validate an index number
$v = new App\Services\IndexNumber\IndexNumberValidator();
$v->parse('P0652-0501');

# Test allocation validator
$av = new App\Services\AcseeAllocationValidator();
$av->validate($candidate, 1, 1, [111, 1, 2, 3]);
```

---

## Documentation

- **Full Guide:** `docs/NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md`
- **Architecture:** `docs/NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md`
- **Known Issues:** `docs/NECTA_PRIVATE_CANDIDATE_GAPS.md`
- **This Card:** `docs/NECTA_QUICK_REFERENCE.md`

---

## Support

- **Operator Questions:** See NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, Section 5
- **Developer Questions:** See NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md
- **Bug Reports:** Check NECTA_PRIVATE_CANDIDATE_GAPS.md first

---

**Last Updated:** 2026-02-15  
**Status:** ✅ UI-Based Registration Works | ⚠️ CSV Import Has Gap #1
