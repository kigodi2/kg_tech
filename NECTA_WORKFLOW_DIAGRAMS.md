# NECTA ACSEE Registration Workflows - Visual Diagrams

**Created:** 2026-02-15

---

## WORKFLOW 1: SCHOOL CANDIDATE REGISTRATION

```
┌─────────────────────────────────────────────────────────────────┐
│              SCHOOL CANDIDATE REGISTRATION FLOW                  │
└─────────────────────────────────────────────────────────────────┘

User navigates to /registration/candidates
         │
         ↓
    Click "Register"
         │
         ↓
    ┌─────────────────────────────────────────┐
    │    REGISTRATION MODAL OPENS              │
    │  ┌─────────────────────────────────────┐ │
    │  │ Candidate Type: ◉ SCHOOL  ○ PRIVATE │ │
    │  │                                     │ │
    │  │ [SCHOOL fields shown]               │ │
    │  │ - School: [Dropdown required]       │ │
    │  │ - Combination: [Dropdown required]  │ │
    │  │ - Candidate ID: [Text]              │ │
    │  │ - Full Name: [Text]                 │ │
    │  │ - Gender: [Radio M/F]               │ │
    │  │ - DOB: [Date picker]                │ │
    │  │                                     │ │
    │  │ [Save] [Cancel]                     │ │
    │  └─────────────────────────────────────┘ │
    └─────────────────────────────────────────┘
         │
         ↓
    User fills form and clicks Save
         │
         ↓
    Frontend validation: All required fields
         │ ✓ Valid
         ↓
    POST /api/candidates
         │
    ┌────────────────────────────────────────────┐
    │          BACKEND PROCESSING                 │
    ├────────────────────────────────────────────┤
    │                                             │
    │  1. Validate input                          │
    │  2. Check authorization (school)            │
    │  3. Create Candidate:                       │
    │     - candidate_type = 'SCHOOL'             │
    │     - combination = 'PCM'                   │
    │     - combination_id = FK to combinations   │
    │                                             │
    │  4. Create CandidateExamRegistration:       │
    │     - exam_type_id = ACSEE                  │
    │     - exam_year_id = 2026                   │
    │                                             │
    │  5. Fetch combination subjects:             │
    │     SELECT * FROM subjects                  │
    │     INNER JOIN combination_subject          │
    │     WHERE combination_id = ?                │
    │     Result: [Physics, Chemistry, Math]      │
    │                                             │
    │  6. Auto-attach subjects:                   │
    │     FOR each subject:                       │
    │       CREATE CandidateSubjectSelection:     │
    │       - is_principal = FALSE (default)      │
    │       - source = 'template'                 │
    │       - created_by = NULL                   │
    │                                             │
    │  7. Validate ACSEE rules:                   │
    │     ✓ Subjects = 3 (PCM)                    │
    │     ⚠ Principals = 0 (not marked yet)       │
    │     ✗ Missing General Studies               │
    │     → Store validation result               │
    │                                             │
    │  8. Return success response                 │
    │     {success: true, candidate: {...}}       │
    │                                             │
    └────────────────────────────────────────────┘
         │
         ↓
    Frontend receives success
         │
         ↓
    ┌──────────────────────────────────────┐
    │  SUCCESS MESSAGE SHOWN                │
    │  "Candidate registered with 3         │
    │   subjects from PCM combination"      │
    │                                      │
    │  [View] [Add Another] [Close]        │
    └──────────────────────────────────────┘
         │
         ↓
    Candidate appears in table with:
         │
    ├─ Candidate ID: S1234-001
    ├─ Full Name: John Doe
    ├─ Sex: M
    ├─ Combination: PCM
    ├─ School: School 1234
    ├─ Exam Type: ACSEE
    ├─ Exam Year: 2026
    ├─ Status: Registered ✓
    │
    └─ Allocation Status: ⚠ Incomplete
       (missing principals marking and General Studies)

DATABASE STATE:
┌────────────────────────────────────────────┐
│ candidates                                  │
│ - id: 1                                    │
│ - candidate_id: 'S1234-001'                │
│ - school_id: 1                             │
│ - full_name: 'John Doe'                    │
│ - candidate_type: 'SCHOOL' ←── NEW         │
│ - combination: 'PCM'                       │
│ - combination_id: 5 ←── NEW                │
│ - exam_type: 'ACSEE'                       │
│ - created_at: 2026-02-15 10:00:00          │
└────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ candidate_subject_selections                  │
├──────────────────────────────────────────────┤
│ id │ candidate_id │ subject_id │ is_principal│ source │ created_by │
├────┼──────────────┼────────────┼─────────────┼────────┼────────────┤
│ 1  │ 1            │ 10         │ FALSE ←NEW  │ template │ NULL ←NEW  │
│ 2  │ 1            │ 11         │ FALSE ←NEW  │ template │ NULL ←NEW  │
│ 3  │ 1            │ 12         │ FALSE ←NEW  │ template │ NULL ←NEW  │
└──────────────────────────────────────────────┘

END RESULT: SCHOOL candidate ready for mark entry
```

---

## WORKFLOW 2: PRIVATE CANDIDATE REGISTRATION & ALLOCATION

```
┌──────────────────────────────────────────────────────────┐
│           PRIVATE CANDIDATE REGISTRATION FLOW            │
└──────────────────────────────────────────────────────────┘

User navigates to /registration/candidates
         │
         ↓
    Click "Register"
         │
         ↓
    ┌─────────────────────────────────────────┐
    │    REGISTRATION MODAL OPENS              │
    │  ┌─────────────────────────────────────┐ │
    │  │ Candidate Type: ○ SCHOOL  ◉ PRIVATE │ │
    │  │                                     │ │
    │  │ [PRIVATE fields shown]               │ │
    │  │ - School: [disabled/hidden] ×        │ │
    │  │ - Combination: [optional dropdown]   │ │
    │  │ - Candidate ID: [Text]               │ │
    │  │ - Full Name: [Text]                  │ │
    │  │ - Gender: [Radio M/F]                │ │
    │  │ - DOB: [Date picker]                 │ │
    │  │                                     │ │
    │  │ [Save] [Cancel]                     │ │
    │  └─────────────────────────────────────┘ │
    └─────────────────────────────────────────┘
         │
         ↓
    User fills form (no school required) and clicks Save
         │
         ↓
    Frontend validation: Required fields only
         │ ✓ Valid
         ↓
    POST /api/candidates
         │
    ┌────────────────────────────────────────────┐
    │          BACKEND PROCESSING                 │
    ├────────────────────────────────────────────┤
    │                                             │
    │  1. Validate input                          │
    │     - school_id: nullable (OK if empty)     │
    │  2. Create Candidate:                       │
    │     - candidate_type = 'PRIVATE' ←KEY       │
    │     - school_id = NULL (no school)          │
    │     - combination = NULL (optional)         │
    │     - combination_id = NULL                 │
    │                                             │
    │  3. Create CandidateExamRegistration:       │
    │     - exam_type_id = ACSEE                  │
    │     - exam_year_id = 2026                   │
    │                                             │
    │  4. DO NOT auto-attach subjects             │
    │     (This is the key difference!)           │
    │                                             │
    │  5. Return response with redirect:          │
    │     {                                       │
    │       success: true,                        │
    │       candidate: {...},                     │
    │       redirect: '/exam-types/acsee?        │
    │                  allocate=P5678-001'        │
    │     }                                       │
    │                                             │
    └────────────────────────────────────────────┘
         │
         ↓
    Frontend receives success
         │
         ↓
    ┌──────────────────────────────────────────────┐
    │  REDIRECT MESSAGE SHOWN                      │
    │  "Candidate registered successfully!         │
    │   Redirecting to subject allocation..."     │
    │                                             │
    │  [Or click here if not redirected]          │
    └──────────────────────────────────────────────┘
         │
         ↓ (2 second auto-redirect)
         │
         ↓
    Navigate to /exam-types/acsee?allocate=PRIV-001
         │
         ↓
    ┌──────────────────────────────────────────────────────┐
    │          ACSEE SUBJECT ALLOCATOR MODAL               │
    │  ┌────────────────────────────────────────────────┐  │
    │  │  ALLOCATE SUBJECTS FOR: PRIV-001               │  │
    │  │  Jane Smith                                    │  │
    │  │                                                │  │
    │  │  ┌─ Required ─────────────────────────┐         │  │
    │  │  │ ☑ General Studies (GS) ← Mandatory │         │  │
    │  │  │   □ Mark as Principal              │         │  │
    │  │  └────────────────────────────────────┘         │  │
    │  │                                                │  │
    │  │  ┌─ Select Subjects ──────────────────┐         │  │
    │  │  │ Principal? │ Subject       │       │         │  │
    │  │  │ ◉ ○ ○      │ Physics       │ Details │       │  │
    │  │  │ ◉ ○ ○      │ Chemistry     │         │       │  │
    │  │  │ ◉ ○ ○      │ Mathematics   │         │       │  │
    │  │  │ ○ ○ ○      │ Biology       │         │       │  │
    │  │  │ ○ ○ ○      │ History       │         │       │  │
    │  │  │ ○ ○ ○      │ English       │         │       │  │
    │  │  │ ○ ○ ○      │ Geography     │         │       │  │
    │  │  └────────────────────────────────────┘         │  │
    │  │                                                │  │
    │  │  ✓ Validation Summary:                         │  │
    │  │    - Selected: 4 subjects                      │  │
    │  │    - Principal: 3 (minimum required) ✓         │  │
    │  │    - General Studies: Yes ✓                    │  │
    │  │    - Status: READY TO SAVE ✓                   │  │
    │  │                                                │  │
    │  │  [SAVE] [CANCEL] [VALIDATION HELP]             │  │
    │  └────────────────────────────────────────────────┘  │
    │                                                      │
    └──────────────────────────────────────────────────────┘
         │
         ↓
    User selects subjects and marks principals
         │
         ↓
    Real-time validation feedback:
         │
    ├─ Users marks Physics, Chemistry, Math as Principal
    │  (count = 3, minimum = 3) ✓
    │
    ├─ User selects Biology too (count = 4)
    │  (no errors, within max of 8) ✓
    │
    ├─ General Studies auto-selected and marked as principal
    │  (mandatory requirement) ✓
    │
    └─ All validation rules pass → SAVE button enabled ✓
         │
         ↓
    User clicks SAVE
         │
         ↓
    POST /api/candidates/{id}/allocation
    Body: {
        subject_ids: [10, 11, 12, 13, 1],  // Physics, Chem, Math, Bio, GS
        principal_ids: [10, 11, 12, 1]     // Physics, Chem, Math, GS
    }
         │
    ┌────────────────────────────────────────────┐
    │          BACKEND PROCESSING                 │
    ├────────────────────────────────────────────┤
    │                                             │
    │  1. Fetch candidate (PRIVATE)               │
    │  2. Validate request (authorization)        │
    │  3. Clear existing selections (if update)   │
    │  4. FOR each subject_id:                    │
    │       CREATE CandidateSubjectSelection:     │
    │       - candidate_id = PRIV-001             │
    │       - subject_id = 10, 11, 12, 13, 1      │
    │       - is_principal = TRUE if in principal_ids │
    │       - source = 'manual' ← KEY             │
    │       - created_by = auth()->user()->id     │
    │                                             │
    │  5. Validate ACSEE rules:                   │
    │     ✓ Principals = 4 (minimum 3) ✓          │
    │     ✓ General Studies = YES ✓               │
    │     ✓ No duplicates = OK ✓                  │
    │     ✓ Count = 5 (max 8) ✓                   │
    │     → All rules PASS                        │
    │                                             │
    │  6. Return success:                         │
    │     {                                       │
    │       success: true,                        │
    │       validation: {                         │
    │         valid: true,                        │
    │         principals_count: 4,                │
    │         subjects_count: 5,                  │
    │         errors: [],                         │
    │         warnings: []                        │
    │       }                                     │
    │     }                                       │
    │                                             │
    └────────────────────────────────────────────┘
         │
         ↓
    Frontend receives success
         │
         ↓
    ┌──────────────────────────────────────────┐
    │  SUCCESS MESSAGE SHOWN                    │
    │  "✓ Subjects allocated successfully!      │
    │   4 principal subjects, 5 total"          │
    │                                          │
    │  [View Candidate] [Add Another] [Close]  │
    └──────────────────────────────────────────┘
         │
         ↓
    Redirect to ACSEE candidates list
         │
         ↓
    Candidate appears in table:
         │
    ├─ Candidate ID: PRIV-001
    ├─ Full Name: Jane Smith
    ├─ Sex: F
    ├─ Combination: (none)
    ├─ Allocated Subjects: GS, Physics, Chemistry, Mathematics, Biology
    ├─ School: (none)
    ├─ Status: Registered ✓
    │
    └─ Allocation Status: ✓ Valid
       (All NECTA rules satisfied)

DATABASE STATE:
┌────────────────────────────────────────────┐
│ candidates                                  │
│ - id: 2                                    │
│ - candidate_id: 'PRIV-001'                 │
│ - school_id: NULL ←── NEW (no school)      │
│ - full_name: 'Jane Smith'                  │
│ - candidate_type: 'PRIVATE' ←── NEW        │
│ - combination: NULL                        │
│ - combination_id: NULL                     │
│ - exam_type: 'ACSEE'                       │
│ - created_at: 2026-02-15 11:00:00          │
└────────────────────────────────────────────┘

┌──────────────────────────────────────────────┐
│ candidate_subject_selections                  │
├──────────────────────────────────────────────┤
│ id │ candidate_id │ subject_id │ is_principal│
├────┼──────────────┼────────────┼─────────────┤
│ 10 │ 2            │ 1          │ TRUE (GS)   │
│ 11 │ 2            │ 10         │ TRUE        │
│ 12 │ 2            │ 11         │ TRUE        │
│ 13 │ 2            │ 12         │ TRUE        │
│ 14 │ 2            │ 13         │ FALSE       │
└──────────────────────────────────────────────┘

And fields:
│ source   │ created_by │ created_at          │
├──────────┼────────────┼─────────────────────┤
│ manual   │ 5          │ 2026-02-15 11:05:00 │
│ manual   │ 5          │ 2026-02-15 11:05:00 │
│ manual   │ 5          │ 2026-02-15 11:05:00 │
│ manual   │ 5          │ 2026-02-15 11:05:00 │
│ manual   │ 5          │ 2026-02-15 11:05:00 │
└──────────────────────────────────────────────┘

END RESULT: PRIVATE candidate ready for mark entry
```

---

## WORKFLOW 3: CSV IMPORT

```
┌──────────────────────────────────────────────────────────┐
│              CSV IMPORT WITH ALLOCATION                  │
└──────────────────────────────────────────────────────────┘

User downloads CSV template from /registration/candidates
         │
         ↓
    Template file:
    ┌──────────────────────────────────────────────────┐
    │ candidate_id,full_name,gender,candidate_type,... │
    │ S1234-001,John Doe,M,SCHOOL,PCM,S1234,ACSEE,... │
    │ PRIV-001,Jane Smith,F,PRIVATE,,AUTO,ACSEE,... │
    │ S1234-002,Bob Jones,M,SCHOOL,PCB,S1234,ACSEE,... │
    └──────────────────────────────────────────────────┘
         │
         ↓
    User fills in data and saves as candidates_import.csv
         │
         ↓
    User returns to /registration/candidates
         │
         ↓
    Clicks Tools → Import CSV
         │
         ↓
    Selects file: candidates_import.csv
    Selects exam year: 2026
    Selects exam type: ACSEE
         │
         ↓
    Click "Import & Review"
         │
         ↓
    ┌────────────────────────────────────────────────────┐
    │          BACKEND IMPORT PROCESSING                  │
    ├────────────────────────────────────────────────────┤
    │                                                    │
    │  FOR each CSV row:                                 │
    │                                                    │
    │  Row 1: S1234-001, John Doe, M, SCHOOL, PCM, ...  │
    │    ✓ Parse row                                     │
    │    ✓ Check if candidate exists: NO                │
    │    ✓ Create Candidate:                             │
    │      - candidate_type = 'SCHOOL'                   │
    │      - school_code = 'S1234' → school_id = 1      │
    │      - combination = 'PCM'                         │
    │    ✓ Create ExamRegistration                       │
    │    ✓ Fetch combination subjects: [Physics, ...] │
    │    ✓ Auto-attach subjects (source='import')       │
    │    → CREATED ✓                                     │
    │                                                    │
    │  Row 2: PRIV-001, Jane Smith, F, PRIVATE, , ...   │
    │    ✓ Parse row                                     │
    │    ✓ Check if candidate exists: NO                │
    │    ✓ Create Candidate:                             │
    │      - candidate_type = 'PRIVATE'                  │
    │      - school_id = NULL (PRIVATE)                  │
    │      - combination = NULL                          │
    │    ✓ Create ExamRegistration                       │
    │    × No auto-attach (PRIVATE type)                 │
    │    → CREATED (NEEDS ALLOCATION) ⚠                  │
    │                                                    │
    │  Row 3: S1234-002, Bob Jones, M, SCHOOL, PCB, ... │
    │    ✓ Parse row                                     │
    │    ✓ Check if candidate exists: NO                │
    │    ✓ Create Candidate                              │
    │    ✓ Auto-attach subjects (PCB: Phys, Chem, Bio)  │
    │    → CREATED ✓                                     │
    │                                                    │
    │  IMPORT SUMMARY:                                   │
    │    Total rows: 3                                   │
    │    Created: 3                                      │
    │    Updated: 0                                      │
    │    Skipped: 0                                      │
    │    Errors: 0                                       │
    │                                                    │
    │  ALLOCATION STATUS:                                │
    │    SCHOOL auto-attached: 2                         │
    │    PRIVATE needing allocation: 1                   │
    │                                                    │
    └────────────────────────────────────────────────────┘
         │
         ↓
    ┌──────────────────────────────────────────────────────┐
    │          IMPORT REPORT MODAL SHOWN                   │
    │  ┌────────────────────────────────────────────────┐  │
    │  │  IMPORT RESULTS                                │  │
    │  │                                                │  │
    │  │  ✓ Successfully processed 3 records            │  │
    │  │                                                │  │
    │  │  Summary:                                      │  │
    │  │    • Created: 3                                │  │
    │  │    • Updated: 0                                │  │
    │  │    • Skipped: 0                                │  │
    │  │    • Errors: 0                                 │  │
    │  │                                                │  │
    │  │  Allocation Status:                            │  │
    │  │    ✓ SCHOOL auto-attached: 2                   │  │
    │  │    ⚠ PRIVATE need allocation: 1                │  │
    │  │      - PRIV-001 (Jane Smith)                   │  │
    │  │        Action: [Allocate Now]                  │  │
    │  │                                                │  │
    │  │  [Close] [View Details] [Allocate Now]         │  │
    │  └────────────────────────────────────────────────┘  │
    │                                                      │
    └──────────────────────────────────────────────────────┘
         │
         ↓
    User clicks "Allocate Now" for PRIV-001
         │
         ↓
    Redirects to subject allocator
         │
         └─→ [Continue with Workflow 2 above]

RESULT:
  ✓ 2 SCHOOL candidates registered with auto-attached subjects
  ✓ 1 PRIVATE candidate created, awaiting manual allocation
  ✓ All data validated and in database
  ✓ Clear action items for incomplete registrations
```

---

## WORKFLOW 4: ACSEE CANDIDATES PAGE WITH ALLOCATION

```
┌──────────────────────────────────────────────────────────┐
│         ACSEE CANDIDATES PAGE - ALLOCATION MANAGEMENT    │
└──────────────────────────────────────────────────────────┘

User navigates to /exam-types/acsee → Candidates tab
         │
         ↓
    Page loads with candidates table:
    ┌──────────────────────────────────────────────────────┐
    │  Index# │ Name      │ Sex │ Combination │ Subjects  │ Status │ Action │
    ├─────────┼───────────┼─────┼─────────────┼───────────┼────────┼────────┤
    │ S1234-01│ John Doe  │ M   │ PCM         │ P,C,M     │ ✓Valid │ [View] │
    │ PRIV-01 │ Jane Smith│ F   │ (none)      │ (pending) │ ⚠Need  │[Allocat│
    │ S1234-02│ Bob Jones │ M   │ PCB         │ P,C,B     │ ✓Valid │ [View] │
    │ PRIV-02 │ Mary Wong │ F   │ (none)      │ (pending) │ ⚠Need  │[Allocat│
    └──────────────────────────────────────────────────────┘
         │
         ↓
    SCHOOL CANDIDATE (S1234-01):
    User clicks [View]
         │
         ↓
    ┌────────────────────────────────────────────────────┐
    │  CANDIDATE DETAILS MODAL                            │
    │  S1234-01 | John Doe                               │
    │  ────────────────────────────────────────────────  │
    │  Type: SCHOOL                                      │
    │  School: School 1234                               │
    │  Combination: PCM (template)                        │
    │  ────────────────────────────────────────────────  │
    │  ALLOCATED SUBJECTS:                               │
    │    ✓ is_principal │ Physics       │ source: template  │
    │    ✓ is_principal │ Chemistry     │ source: template  │
    │    ✓ is_principal │ Mathematics   │ source: template  │
    │    ✓ is_principal │ General Studies │ source: template │
    │  ────────────────────────────────────────────────  │
    │  VALIDATION STATUS: ✓ All rules satisfied          │
    │    - Principals: 4 (minimum 3) ✓                   │
    │    - General Studies: Yes ✓                        │
    │    - Subjects: 4 (max 8) ✓                         │
    │  ────────────────────────────────────────────────  │
    │  [Close] [Edit Principals] [Export]                │
    │  (Edit disabled for SCHOOL - use combination)      │
    │                                                    │
    └────────────────────────────────────────────────────┘
         │
         ↓
    Click [Close] to return to list
         │
         ↓
    PRIVATE CANDIDATE (PRIV-01):
    User clicks [Allocate]
         │
         ↓
    ┌──────────────────────────────────────────────────────┐
    │          ACSEE SUBJECT ALLOCATOR MODAL               │
    │  ┌────────────────────────────────────────────────┐  │
    │  │  ALLOCATE SUBJECTS FOR: PRIV-01                │  │
    │  │  Jane Smith (Female)                           │  │
    │  │                                                │  │
    │  │  ┌─ Required ─────────────────────────┐         │  │
    │  │  │ ☑ General Studies (GS)             │         │  │
    │  │  │   Mark as Principal: ☐             │         │  │
    │  │  └────────────────────────────────────┘         │  │
    │  │                                                │  │
    │  │  ┌─ Available Subjects ──────────────┐         │  │
    │  │  │ P │ Subject       │ Status         │         │  │
    │  │  │ ☐  │ Physics       │ Not selected   │         │  │
    │  │  │ ☐  │ Chemistry     │ Not selected   │         │  │
    │  │  │ ☐  │ Mathematics   │ Not selected   │         │  │
    │  │  │ ☐  │ Biology       │ Not selected   │         │  │
    │  │  │ ☐  │ History       │ Not selected   │         │  │
    │  │  │ ☐  │ Geography     │ Not selected   │         │  │
    │  │  │ ☐  │ English       │ Not selected   │         │  │
    │  │  └────────────────────────────────────┘         │  │
    │  │                                                │  │
    │  │  ✓ Validation:                                 │  │
    │  │    Status: INCOMPLETE                          │  │
    │  │    Errors: Select at least 3 principal subjects│  │  │
    │  │                                                │  │
    │  │  [SAVE (DISABLED)] [CANCEL] [HELP]             │  │
    │  └────────────────────────────────────────────────┘  │
    │                                                      │
    └──────────────────────────────────────────────────────┘
         │
         ↓
    User selects subjects:
         │
    ├─ Clicks on Physics, Chemistry, Mathematics
    │  Checkmarks appear
    │
    ├─ Real-time validation:
    │  ✓ Selected: 3 subjects (+ 1 GS = 4 total)
    │  ✓ Principals marked: 3 (minimum required) ✓
    │  ✓ General Studies: YES ✓
    │  → SAVE button becomes ENABLED
    │
    └─ Marks first 3 (Physics, Chemistry, Math) as Principal
         │
         ↓
    User clicks [SAVE]
         │
         ↓
    POST /api/candidates/2/allocation
         │
    ┌────────────────────────────────────────────────────┐
    │  BACKEND ALLOCATION SAVE                            │
    ├────────────────────────────────────────────────────┤
    │  1. Fetch candidate (PRIV-01)                      │
    │  2. Validate request                               │
    │  3. Store selections:                              │
    │     - Physics (is_principal=TRUE, source=manual)   │
    │     - Chemistry (is_principal=TRUE, source=manual) │
    │     - Mathematics (is_principal=TRUE, source=manual)
    │     - GS (is_principal=TRUE, source=manual)        │
    │  4. Validate ACSEE rules: ALL PASS ✓              │
    │  5. Return success                                 │
    │                                                    │
    └────────────────────────────────────────────────────┘
         │
         ↓
    ┌──────────────────────────────────────────┐
    │  SUCCESS MESSAGE                          │
    │  "✓ Subjects allocated successfully!      │
    │   4 principal subjects, 4 total"          │
    │  [Close]                                 │
    └──────────────────────────────────────────┘
         │
         ↓
    Modal closes, return to table
         │
         ↓
    Table refreshes, PRIV-01 now shows:
    ┌──────────────────────────────────────────────────────┐
    │  Index# │ Name      │ Sex │ Combination │ Subjects  │ Status │
    ├─────────┼───────────┼─────┼─────────────┼───────────┼────────┤
    │ PRIV-01 │ Jane Smith│ F   │ (none)      │ P,C,M,GS  │ ✓Valid │
    └──────────────────────────────────────────────────────┘
         │
         ↓
    Status changed from ⚠ Need to ✓ Valid
    Ready for mark entry

RESULT: Both SCHOOL and PRIVATE candidates fully registered and ready
```

---

## VALIDATION RULES FLOWCHART

```
┌────────────────────────────────────────────────────────┐
│         ACSEE VALIDATION RULES ENGINE                   │
└────────────────────────────────────────────────────────┘

Input: Candidate with allocated subjects
         │
         ↓
    ┌─────────────────────────────────────┐
    │  RULE 1: Count Principals           │
    │  is_principal = TRUE ?              │
    │  Count >= 3 ?                       │
    │                                     │
    │  ├─ YES: ✓ Pass                     │
    │  └─ NO:  ✗ Error                    │
    │         "Need minimum 3 principals" │
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  RULE 2: General Studies            │
    │  subject.code == 'GS' ?             │
    │                                     │
    │  ├─ YES: ✓ Pass                     │
    │  └─ NO:  ✗ Error                    │
    │         "General Studies mandatory" │
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  RULE 3: No Duplicates              │
    │  DISTINCT subject_id count ==       │
    │  Total selected count ?             │
    │                                     │
    │  ├─ YES: ✓ Pass                     │
    │  └─ NO:  ✗ Error                    │
    │         "Duplicate subjects found"  │
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  RULE 4: Maximum Subjects           │
    │  Total count <= 8 ?                 │
    │                                     │
    │  ├─ YES: ✓ Pass                     │
    │  └─ NO:  ✗ Error                    │
    │         "Maximum 8 subjects allowed"│
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  RULE 5: Subject Conflicts          │
    │  Check configurable rules           │
    │  (e.g., Phys + Hist conflict?)     │
    │                                     │
    │  ├─ NO conflicts: ✓ Pass            │
    │  └─ Conflicts:   ✗ Error            │
    │         "Subject conflict found"    │
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  WARNINGS (Non-blocking)            │
    │  - Too many principals (>5) ?       │
    │  - Other quality indicators         │
    │                                     │
    │  Return warnings list               │
    └─────────────────────────────────────┘
         │
         ↓
    ┌─────────────────────────────────────┐
    │  OUTPUT: ValidationResult           │
    │  {                                  │
    │    valid: true/false                │
    │    errors: [list],                  │
    │    warnings: [list],                │
    │    principals_count: N,             │
    │    subjects_count: N                │
    │  }                                  │
    └─────────────────────────────────────┘
         │
         ↓
    If valid = true → SAVE ALLOWED
    If valid = false → SAVE BLOCKED, show errors
```

---

**Document Status:** VISUAL WORKFLOWS COMPLETE  
**Created:** 2026-02-15  
**For Reference:** During implementation and testing
