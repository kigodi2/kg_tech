# ACSEE Mark Entry - User & System Workflows

## 1️⃣ User Workflow (Data Entry Officer)

```
START
  ↓
[LOGIN to System]
  ↓
[Access /mark-entry]
  ↓
┌─────────────────────────────────────────┐
│ STEP 1: SELECT CONTEXT                  │
├─────────────────────────────────────────┤
│ 1. Select Exam Year (e.g., 2026)       │
│ 2. Select Region (e.g., IRINGA)        │
│ 3. Select District (e.g., IRINGA MC)   │
│ 4. Select School (e.g., KLERRUU)       │
│ 5. Select Subject (e.g., MATHEMATICS)  │
│ 6. Select Combination (e.g., CBE)      │
└─────────────────────────────────────────┘
  ↓
[System Validates Context]
  ├─ School exists? ✓
  ├─ Subject is ACSEE? ✓
  ├─ Combination is active? ✓
  ↓ (All pass)
  ↓
┌─────────────────────────────────────────┐
│ STEP 2: DOWNLOAD TEMPLATE               │
├─────────────────────────────────────────┤
│ Click "Download CSV Template"           │
│ ↓                                       │
│ System generates CSV with:              │
│ • Headers (Index, Name, Paper 1, ...)  │
│ • Paper structure (dynamic)             │
│ • Sample rows (3 rows)                  │
│ • User instructions                     │
│ ↓                                       │
│ File saved: mark-entry-MATH-CBE.csv    │
└─────────────────────────────────────────┘
  ↓
┌─────────────────────────────────────────┐
│ STEP 3: FILL DATA (OFFLINE)             │
├─────────────────────────────────────────┤
│ Open in Excel/Google Sheets:            │
│ • Delete sample rows                    │
│ • Enter candidate index numbers         │
│ • Enter paper marks (0-100)             │
│ • Enter practical/project if needed     │
│ • Save as CSV (UTF-8)                   │
│                                         │
│ Example:                                │
│ S1378-0501,CANDIDATE NAME,75,82,88     │
│ S1378-0502,CANDIDATE NAME,68,71,79     │
│ ...                                     │
└─────────────────────────────────────────┘
  ↓
┌─────────────────────────────────────────┐
│ STEP 4: UPLOAD CSV                      │
├─────────────────────────────────────────┤
│ 1. Click file upload area (or drag-drop)│
│ 2. Select the CSV file                  │
│ 3. Click "Upload Marks"                 │
│                                         │
│ System processes:                       │
│ • Parse CSV                             │
│ • Create batch (DRAFT)                  │
│ • Create raw mark records               │
│ • Validate each row                     │
│ • Update statistics                     │
└─────────────────────────────────────────┘
  ↓
┌──────────────────┬──────────────────┐
│   VALIDATION     │     VALIDATION   │
│    PASSED ✓      │      FAILED ✗    │
├──────────────────┼──────────────────┤
│                  │                  │
│  • 0 errors      │ • Some errors    │
│  • All valid     │ • See summary    │
│  • Ready to lock │ • Download       │
│                  │   error report   │
│                  │ • Fix errors     │
│                  │ • Re-upload      │
└──────────────────┴──────────────────┘
  ↓ (if passed)
┌─────────────────────────────────────────┐
│ STEP 5: LOCK BATCH                      │
├─────────────────────────────────────────┤
│ Review Summary:                         │
│ • Total Records: 450                    │
│ • Valid Records: 450                    │
│ • Errors: 0                             │
│ • Status: Ready                         │
│                                         │
│ Click "Lock Batch"                      │
│ ↓                                       │
│ Status changes to LOCKED                │
│ (No further changes allowed)            │
└─────────────────────────────────────────┘
  ↓
[System notifies Admin]
  ↓
END (Ready for grade processing)
```

---

## 2️⃣ System Workflow (Backend Processing)

```
┌─────────────────────────────────────────────────────┐
│ USER UPLOADS CSV FILE                               │
└────────────────────┬────────────────────────────────┘
                     ↓
        ┌────────────────────────┐
        │ MarkEntryController    │
        │ uploadMarks()          │
        └────────────┬───────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ STEP 1: VALIDATE REQUEST               │
        ├────────────────────────────────────────┤
        │ • exam_year (integer, 2000-2027)      │
        │ • school_id (exists in DB)            │
        │ • subject_id (exists in DB)           │
        │ • combination_id (exists in DB)       │
        │ • file (CSV, max 5MB)                 │
        │                                        │
        │ If invalid → Return 422 error         │
        └────────────┬───────────────────────────┘
                     ↓ (Valid)
        ┌────────────────────────────────────────┐
        │ BEGIN DATABASE TRANSACTION             │
        │ (All-or-nothing: commit/rollback)     │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ MarkImportService::createBatch()       │
        ├────────────────────────────────────────┤
        │ • Generate unique batch_code           │
        │ • Create MarkImportBatch record        │
        │ • Status: DRAFT                        │
        │ • Record importer user_id & timestamp  │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ MarkImportService::processCSVUpload()  │
        ├────────────────────────────────────────┤
        │ 1. Parse CSV file                      │
        │ 2. Get paper structure from Subject    │
        │ 3. For each row:                       │
        │    • Skip empty rows                   │
        │    • Skip header row                   │
        │    • Extract index number              │
        │    • Extract marks (paper1-3,          │
        │      practical, project)               │
        │    • Create RawMark record             │
        │    • Find & link Candidate            │
        │ 4. Update batch statistics             │
        │    (total_records)                     │
        │ 5. Return result                       │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ MarkImportService::validateBatch()     │
        ├────────────────────────────────────────┤
        │ For each RawMark:                      │
        │                                        │
        │ 1. MarkValidationService::              │
        │    validateRawMark($mark, $batch)     │
        │    ↓                                   │
        │    [Validation Rules]:                 │
        │    ✓ Candidate exists?                │
        │    ✓ ACSEE registered?                │
        │    ✓ Subject in combination?          │
        │    ✓ All papers present?              │
        │    ✓ All marks numeric?               │
        │    ✓ All marks 0-100?                 │
        │                                        │
        │ 2. If errors:                         │
        │    • Update RawMark with error_msgs   │
        │    • Set has_errors = true            │
        │    • Increment error_records count    │
        │                                        │
        │ 3. If valid:                          │
        │    • Clear any previous errors        │
        │    • Increment valid_records count    │
        │                                        │
        │ 4. Return validation result           │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ UPDATE BATCH STATISTICS                │
        ├────────────────────────────────────────┤
        │ • valid_records = X                    │
        │ • error_records = Y                    │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ COMMIT TRANSACTION                     │
        │ (All changes permanent)                │
        │                                        │
        │ OR if error: ROLLBACK TRANSACTION      │
        │ (Undo all changes, batch deleted)     │
        └────────────┬───────────────────────────┘
                     ↓
        ┌────────────────────────────────────────┐
        │ RETURN RESPONSE TO CLIENT              │
        ├────────────────────────────────────────┤
        │ {                                      │
        │   "success": true,                     │
        │   "batch_id": 123,                     │
        │   "batch_code": "BATCH-65-4...",       │
        │   "message": "450 records imported",   │
        │   "validation": {                      │
        │     "valid": 450,                      │
        │     "invalid": 0,                      │
        │     "total": 450,                      │
        │     "errors": []                       │
        │   }                                    │
        │ }                                      │
        └────────────┬───────────────────────────┘
                     ↓
        UPDATE FRONTEND UI WITH RESULTS
```

---

## 3️⃣ Error Handling Workflow

```
┌─────────────────────────────────────────┐
│ VALIDATION FINDS ERRORS                 │
└─────────────────────────┬───────────────┘
                          ↓
        ┌─────────────────────────────────┐
        │ Update RawMark:                 │
        │ • has_errors = true             │
        │ • error_messages = [            │
        │    "Candidate not found",       │
        │    "Invalid marks: 150",        │
        │    ...                          │
        │  ]                              │
        │ • Update batch error_records++  │
        └──────────┬──────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ SHOW ERROR SUMMARY IN UI:        │
        │                                  │
        │ ⚠️  Errors Found: 5              │
        │ → Download Error Report          │
        │ → Fix Errors                     │
        │ → Re-upload                      │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ USER DOWNLOADS ERROR CSV:        │
        │                                  │
        │ Row | Index# | Name | Errors    │
        │-----|--------|------|----------│
        │ 12  | S1378- | JOHN | Candidate│
        │     | XXXX   |      | not found│
        │ 28  | S1378- | JANE | Marks    │
        │     | YYYY   |      | must be  │
        │     |        |      | 0-100    │
        │ ... | ... | ... | ...          │
        │                                  │
        │ Opens in Excel → Fixes errors   │
        │ Re-saves as CSV                 │
        └──────────┬───────────────────────┘
                   ↓
        ┌──────────────────────────────────┐
        │ USER RE-UPLOADS FIXED CSV:       │
        │                                  │
        │ Same workflow as first upload    │
        │ (New batch created)              │
        │                                  │
        │ If still errors:                 │
        │ → Download error report again    │
        │ → Fix again                      │
        │ → Re-upload again                │
        │                                  │
        │ Loop until valid (0 errors)      │
        └──────────┬───────────────────────┘
                   ↓
        ✅ VALIDATION PASSES (0 errors)
```

---

## 4️⃣ Batch Locking Workflow

```
┌──────────────────────────┐
│ VALIDATION PASSES (✓)    │
│ error_records = 0        │
└──────────┬───────────────┘
           ↓
┌──────────────────────────────────────┐
│ SHOW LOCK BUTTON:                    │
│                                      │
│ [Lock Batch (No Changes Allowed)]    │
│                                      │
│ Click to lock                        │
└──────────┬───────────────────────────┘
           ↓
┌──────────────────────────────────────┐
│ MarkEntryController::lockBatch()     │
├──────────────────────────────────────┤
│ 1. Check batch status = VALIDATED    │
│ 2. Check error_records = 0           │
│                                      │
│ If OK:                               │
│  • Update status to LOCKED           │
│  • Set locked_by = current_user_id   │
│  • Set locked_at = now()             │
│  • Return success response           │
│                                      │
│ If invalid:                          │
│  • Return 422 error                  │
│  • Message: "Cannot lock batch with  │
│    errors" or "Must validate first"  │
└──────────┬───────────────────────────┘
           ↓
┌──────────────────────────────────────┐
│ BATCH STATUS: LOCKED                 │
│                                      │
│ ✓ Prevents accidental modification   │
│ ✓ Ready for admin review             │
│ ✓ Audit trail complete               │
│ ✓ Awaiting grade processing          │
└──────────────────────────────────────┘
```

---

## 5️⃣ Data Validation Rules Flow

```
┌─────────────────────────────────────────────────────┐
│ RECEIVED RAW MARK RECORD                            │
└──────────────────────┬────────────────────────────────┘
                       ↓
          ┌────────────────────────────┐
          │ RULE 1: Candidate Exists?  │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ Found in DB          ✗ Error: "Candidate with
          │                     index number 'X'
          │                     not found"
          ↓                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
          ┌────────────────────────────┐
          │ RULE 2: ACSEE Registered?  │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ exam_type='ACSEE'     ✗ Error: "Candidate is
          │                     not registered for ACSEE"
          │                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
          ┌────────────────────────────┐
          │ RULE 3: Combo Match?       │
          │ Subject in student's       │
          │ combination?               │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ Subject found     ✗ Error: "Subject MAT not
          │                  in combination CBE"
          │                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
          ┌────────────────────────────┐
          │ RULE 4: Papers Complete?   │
          │ All required papers have   │
          │ marks?                     │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ All papers filled     ✗ Error: "Paper 1 marks
          │                     are missing or empty"
          │                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
          ┌────────────────────────────┐
          │ RULE 5: Numeric?           │
          │ All marks are numbers?     │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ Numeric values    ✗ Error: "Paper 1 marks
          │                  must be numeric (got: ABC)"
          │                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
          ┌────────────────────────────┐
          │ RULE 6: Range Valid?       │
          │ All marks 0-100?           │
          └────────────┬───────────────┘
                       ↓
          ┌─────────────┴──────────────┐
          │                            │
      YES │                        NO  │
          ↓                            ↓
      ✓ In range [0-100]  ✗ Error: "Paper 1 marks
          │                  must be 0-100 (got: 150)"
          │                            │
          │                            └──→ [ADD ERROR]
          │
          ↓
     ┌────────────────────────┐
     │ ALL RULES CHECK DONE   │
     └────────────┬───────────┘
                  ↓
          ┌───────┴──────────┐
          │                  │
      NO ERRORS          HAS ERRORS
          │                  │
          ↓                  ↓
      ✅ VALID          ❌ INVALID
      has_errors=false  has_errors=true
      error_msgs=[]     error_msgs=[...]
```

---

## 6️⃣ Database Transaction Safety

```
REQUEST RECEIVED
  ↓
BEGIN TRANSACTION
  ├─ Lock tables
  ├─ Create savepoint
  ↓
EXECUTE OPERATIONS
  ├─ 1. Create MarkImportBatch
  ├─ 2. Parse CSV (no DB changes yet)
  ├─ 3. For each row:
  │   ├─ Create RawMark
  │   ├─ Link Candidate (if found)
  ├─ 4. Validate all rows
  │   ├─ Check each RawMark
  │   ├─ Update has_errors flag
  ├─ 5. Update batch statistics
  │   ├─ Set valid_records count
  │   ├─ Set error_records count
  ↓
IF ANY ERROR OCCURRED:
  │ (e.g., database connection lost, constraint violated)
  ├─ ROLLBACK TRANSACTION
  │   └─ Undo all changes
  │   └─ Batch not created
  │   └─ No RawMarks created
  │   └─ Clean state restored
  ├─ Return error response
  ↓
ELSE (NO ERRORS):
  ├─ COMMIT TRANSACTION
  │   └─ All changes permanent
  │   └─ Batch created with status DRAFT
  │   └─ All RawMarks created
  │   └─ Validation results stored
  ├─ Return success response
  ↓
UNLOCK TABLES
  ↓
RESPONSE SENT TO CLIENT
```

---

## 7️⃣ Cascading Filter Dependencies

```
┌──────────────────────────────────────┐
│ EXAM YEAR                            │
│ (Integer, free selection)            │
│ └─ No dependencies                   │
└──────────────────────────────────────┘
                ↓
┌──────────────────────────────────────┐
│ REGION                               │
│ (Load all active regions)            │
│ └─ Independent selection             │
│    (not filtered by year)            │
└──────────────────────────────────────┘
        ↑  ↓ (when selected)
        │  └→ [LOAD DISTRICTS]
        │
┌───────────────────────────────────────┐
│ DISTRICT                              │
│ (Load districts WHERE region_id=X)   │
│ └─ Depends on: REGION                 │
│ └─ Disabled if no region              │
└───────────────────────────────────────┘
        ↑  ↓ (when selected)
        │  └→ [LOAD SCHOOLS]
        │
┌───────────────────────────────────────┐
│ SCHOOL                                │
│ (Load schools WHERE district_id=X)   │
│ └─ Depends on: DISTRICT               │
│ └─ Disabled if no district            │
└───────────────────────────────────────┘
        ↑  ↓ (when selected)
        │  └→ [READY FOR SUBJECT]
        │
┌──────────────────────────────────────┐
│ SUBJECT                              │
│ (Load all ACSEE subjects)            │
│ └─ Independent (pre-loaded)          │
│ └─ Filtered by exam_type='ACSEE'     │
└──────────────────────────────────────┘
        ↑  ↓ (when selected)
        │  └→ [SHOW PAPER INFO]
        │
┌──────────────────────────────────────┐
│ COMBINATION                          │
│ (Load all ACSEE combinations)        │
│ └─ Independent (pre-loaded)          │
│ └─ Filtered by exam_type='ACSEE'     │
└──────────────────────────────────────┘
        ↑  ↓ (when selected)
        │  └→ [ENABLE DOWNLOAD]
        │
┌──────────────────────────────────────┐
│ TEMPLATE DOWNLOAD                    │
│ (Requires: School, Subject, Combo)   │
│ └─ Disabled if any missing           │
│ └─ Enabled when all selected         │
└──────────────────────────────────────┘
```

---

## 📊 State Diagram

```
                    [DRAFT]
                       ↑
                       │
                 CSV uploaded,
                 validation run
                       │
        ┌──────────────┼──────────────┐
        │              │              │
    Errors=0       Errors>0       Errors=0
    No lock        Re-upload       No lock
        │              │              │
        ↓              └──→ Re-validate
        │                   (back to DRAFT)
        │
    [VALIDATED]
        ↓
     Click "Lock"
        ↓
    [LOCKED]
        ↓
   (No changes)
        ↓
  Admin processes
  (future: grade computation)
        ↓
    [PROCESSED]
        ↓
      FINAL
```

---

**Total Workflow Documentation** ✓  
Clear, visual representation of all system processes
