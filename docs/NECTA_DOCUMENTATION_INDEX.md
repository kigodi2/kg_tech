# NECTA Documentation Index

## Overview
This folder contains comprehensive documentation for the NECTA ACSEE (Advanced Certificate of Secondary Education Examination) alignment implementation in IRMS. All documentation is based on actual code analysis and production implementation.

---

## Quick Links by Role

### 👨‍💼 IRMS Operators / Data Entry Staff
**Start here:** [`NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md`](NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md)

**Contents:**
- How to register PRIVATE candidates (step-by-step)
- How to assign subjects to private candidates
- NECTA validation rules and error messages
- Database verification queries
- Common troubleshooting
- CSV bulk import format

**Time to read:** 20 minutes

---

### 👨‍💻 Developers / System Integrators
**Start here:** [`NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md`](NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md)

**Contents:**
- Architecture overview
- Component locations and responsibilities
- Database schema changes
- Workflow examples (code-level)
- Known technical gaps
- Testing guide
- Configuration reference

**Time to read:** 30 minutes

**Then read:** [`NECTA_PRIVATE_CANDIDATE_GAPS.md`](NECTA_PRIVATE_CANDIDATE_GAPS.md) (if integrating bulk import)

---

### ⚡ Quick Lookup / Cheat Sheet
**Use this:** [`NECTA_QUICK_REFERENCE.md`](NECTA_QUICK_REFERENCE.md)

**Contents:**
- Registration flow diagram
- NECTA rules checklist
- Index number format
- Common error fixes
- Database queries
- API endpoints
- Useful commands

**Time to reference:** 2-3 minutes

---

## Document Descriptions

### 1. NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md
**Purpose:** Complete operator/developer guide for registering private candidates and assigning subjects

**Sections:**
1. Registering a PRIVATE Candidate (step-by-step)
2. Assigning Subjects to a PRIVATE Candidate (Manual Mode)
3. Validation Rules & Error Messages
4. Verification & Database Queries
5. Special Notes (combinations, replacements, etc.)
6. Common Errors & Troubleshooting
7. Bulk Import via CSV (format + examples)

**Audience:** Operators, system admins, developers implementing bulk imports

**Key Features:**
- Real error messages from actual code
- SQL query examples for verification
- Step-by-step screenshots guide
- NECTA rule explanations
- CSV format with examples

---

### 2. NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md
**Purpose:** Technical architecture and implementation overview

**Sections:**
1. Key Components (Index Number Validator, Registration, Subject Allocation)
2. Database Schema (migration details)
3. Workflow Examples (school vs. private candidates)
4. Known Gaps (with priority levels)
5. Testing Guide
6. Performance Notes
7. Security & Audit Trail
8. Configuration Reference

**Audience:** Developers, architects, QA engineers

**Key Features:**
- Component locations and responsibilities
- Data flow diagrams (text-based)
- Request/response payload examples
- Known bugs with severity levels
- Unit test references
- Performance characteristics

---

### 3. NECTA_PRIVATE_CANDIDATE_GAPS.md
**Purpose:** Detailed analysis of implementation gaps and recommended fixes

**Sections:**
1. Gap #1: CSV Import for PRIVATE Candidates Fails (HIGH PRIORITY)
   - Root cause analysis
   - Minimal non-destructive fix options
   - Testing guide
2. Gap #2: Unclear CSV Documentation (MEDIUM PRIORITY)
3. Gap #3: No Private Centre Model (LOW PRIORITY)

**Audience:** Developers implementing fixes, QA testers, project managers

**Key Features:**
- Exact file/line references for bugs
- Two fix options with trade-offs
- Before/after code samples
- Test cases for verification
- Risk assessment for each gap

---

### 4. NECTA_QUICK_REFERENCE.md
**Purpose:** Fast lookup guide for common tasks and errors

**Sections:**
1. Registration Flow (diagram)
2. Critical NECTA Rules (checklist)
3. Index Number Format (examples)
4. Database Queries (copy-paste ready)
5. Common Errors & Fixes (table)
6. Field Checklist (quick validation)
7. Where to Find Things
8. Key Tables & Relationships
9. API Endpoints
10. Useful Commands

**Audience:** Operators in a hurry, developers doing quick lookups

**Key Features:**
- Diagrams and flowcharts
- Copy-paste SQL queries
- Error → Fix mapping table
- API endpoint reference
- Useful Tinker commands

---

### 5. NECTA_DOCUMENTATION_INDEX.md
**Purpose:** This file — guide you to the right documentation

**Audience:** Everyone

---

## How to Use This Documentation

### Scenario 1: "I need to register a PRIVATE candidate"
1. Read: NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, **Section 1**
2. Reference: NECTA_QUICK_REFERENCE.md, **Field Checklist**
3. If error occurs: NECTA_QUICK_REFERENCE.md, **Common Errors & Fixes**

### Scenario 2: "I'm integrating bulk candidate import"
1. Read: NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md (overview)
2. Read: NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, **Section 7** (CSV format)
3. Read: NECTA_PRIVATE_CANDIDATE_GAPS.md, **Gap #1** (fix the bug!)
4. Test with: NECTA_PRIVATE_CANDIDATE_GAPS.md, **Testing the Fix**

### Scenario 3: "Subjects won't allocate — I'm getting an error"
1. Find error message in: NECTA_QUICK_REFERENCE.md, **Common Errors & Fixes**
2. Read detailed fix in: NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, **Section 6**
3. Verify with database query: NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, **Section 4**

### Scenario 4: "I'm a developer and need to understand the architecture"
1. Read: NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md (full)
2. Check: NECTA_PRIVATE_CANDIDATE_GAPS.md for known issues
3. Run tests: NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md, **Testing Guide**
4. Reference code: Use file paths provided in "Location" columns

### Scenario 5: "I forgot the index number format"
1. Quick look: NECTA_QUICK_REFERENCE.md, **Index Number Format**

---

## Key Concepts

### Index Number (NECTA Format)
```
P 0652 - 0501
│ │      └─ Serial: 0001-9999
│ └─ Centre Code: 0000-9999
└─ Prefix: S (School) or P (Private)
```
- **S prefix** → Automatically detected as SCHOOL candidate
- **P prefix** → Automatically detected as PRIVATE candidate
- Format is strictly validated via regex

### NECTA Rules for Subject Allocation
All ACSEE candidates must have:
1. ✅ General Studies (code 111) — **MANDATORY**
2. ✅ At least 3 PRINCIPAL subjects (any subject except GS)
3. ✅ No duplicate subject allocations

Example valid allocation for private candidate:
```
General Studies (111)  ← Mandatory
Mathematics (001)      ← Principal 1
Physics (002)          ← Principal 2
Chemistry (003)        ← Principal 3
English (005)          ← Optional additional principal
```

### Candidate Types
- **SCHOOL:** Uses combination templates (e.g., "PCM" = Physics + Chemistry + Math)
- **PRIVATE:** Manually selects individual subjects; no templates

---

## Implementation Status

| Component | Status | Location |
|-----------|--------|----------|
| **Index Number Validation** | ✅ Fully Implemented | `app/Services/IndexNumber/` |
| **Candidate Registration (UI)** | ✅ Fully Implemented | `resources/views/registration/candidates.blade.php` |
| **Subject Allocation (UI)** | ✅ Fully Implemented | `resources/views/exam-types/acsee.blade.php` |
| **NECTA Rule Validation** | ✅ Fully Implemented | `app/Services/AcseeAllocationValidator.php` |
| **Database Schema** | ✅ Fully Implemented | `database/migrations/2026_02_15_...` |
| **CSV Bulk Import** | ⚠️ Has Known Bug | `app/Services/Candidates/CandidateImportService.php` (line 497) |
| **Tinker CLI Support** | ✅ Fully Implemented | All models + queries supported |

---

## Quick Stats

- **Total Documentation Pages:** 5 (this index + 4 guides)
- **Total Words:** ~15,000
- **Code Files Referenced:** 20+
- **Database Tables Involved:** 4 (candidates, candidate_subject_selections, subjects, exam_years)
- **API Endpoints:** 3+ (registration, allocation, validation)
- **Known Bugs:** 1 (HIGH priority, easy fix)
- **Test Coverage:** Unit + Feature tests provided

---

## Document Metadata

| Attribute | Value |
|-----------|-------|
| **Created** | 2026-02-15 |
| **Last Updated** | 2026-02-15 |
| **Version** | 1.0 |
| **NECTA Compliance** | ACSEE Subject Allocation Standards |
| **Laravel Version** | 10.x |
| **Frontend** | Alpine.js |
| **Status** | Production Ready (except Gap #1) |

---

## Feedback & Updates

If you find:
- **Unclear instructions** → Update NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md, Section 5 (Troubleshooting)
- **Missing information** → Add to appropriate guide (or create new section)
- **Code changes** → Update component location references in NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md
- **New errors** → Add to NECTA_QUICK_REFERENCE.md and NECTA_PRIVATE_CANDIDATE_GAPS.md

---

## Related Code Files

### Controllers
- `app/Http/Controllers/CandidateController.php` — Registration logic
- `app/Http/Controllers/ExamTypeController.php` — ACSEE dashboard

### Services
- `app/Services/IndexNumber/IndexNumberValidator.php` — Index number validation
- `app/Services/AcseeAllocationValidator.php` — NECTA rule validation
- `app/Services/Candidates/CandidateImportService.php` — Bulk import (has Gap #1)

### Models
- `app/Models/Candidate.php`
- `app/Models/CandidateSubjectSelection.php`
- `app/Models/ExamYear.php`
- `app/Models/Subject.php`

### Views
- `resources/views/registration/candidates.blade.php` — Registration modal
- `resources/views/exam-types/acsee.blade.php` — ACSEE dashboard + allocation modal

### Routes
- `routes/web.php` — API endpoints (lines 589–1460)

### Migrations
- `database/migrations/2026_02_15_add_necta_alignment_columns.php`

### Tests
- `tests/Feature/IndexNumberValidationTest.php`
- `tests/Unit/Services/AcseeAllocationValidatorTest.php`

---

## Need Help?

1. **Quick answer** → NECTA_QUICK_REFERENCE.md
2. **Detailed walkthrough** → NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md
3. **Technical deep-dive** → NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md
4. **Debug a specific issue** → NECTA_PRIVATE_CANDIDATE_GAPS.md
5. **Search this index** → Ctrl+F in this file

---

**Navigation:**
- [Full Operator Guide](NECTA_PRIVATE_CANDIDATE_REGISTRATION_GUIDE.md)
- [Technical Architecture](NECTA_ALIGNMENT_IMPLEMENTATION_SUMMARY.md)
- [Known Gaps & Fixes](NECTA_PRIVATE_CANDIDATE_GAPS.md)
- [Quick Reference](NECTA_QUICK_REFERENCE.md)

---

**Version:** 1.0 | **Last Updated:** 2026-02-15 | **Status:** Production Ready
