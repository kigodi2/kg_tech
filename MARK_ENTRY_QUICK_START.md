# ACSEE Mark Entry - Quick Start Guide

## Installation

### 1. Run Migrations
```bash
php artisan migrate
```

This creates:
- `mark_import_batches` table
- `raw_marks` table

### 2. Register Service Provider (Optional)
Services are auto-discovered via Laravel's service discovery.

## Access the Module

**URL**: `http://127.0.0.1:8000/mark-entry`

Requires authentication (login first).

## How to Use

### Basic Workflow

#### 1. Select Context
```
Exam Year: 2026
Region: IRINGA
District: IRINGA MC
School: KLERRUU TEACHERS COLLEGE
Subject: MATHEMATICS (or any ACSEE subject)
Combination: CBE (or student's combination)
```

#### 2. Download Template
```
Click "Download CSV Template"
→ Saves mark-entry-MATH-CBE-202601312345.csv
```

#### 3. Fill Data (in Excel/Google Sheets)
```csv
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Practical (out of 100)
S1378-0501,ADVENTINA GIDIONI ELIA,78,82,85
S1378-0502,AGRIPINA MAKOBE LUSATO,65,71,70
S1378-0503,ASIFIWEELI SENYAELI PALLANGYO,88,92,90
```

**Important**:
- Do NOT modify header row
- Do NOT add extra columns
- Delete the sample rows provided
- All marks must be 0-100

#### 4. Upload CSV
```
Click upload area → Select file → Click "Upload Marks"
```

#### 5. Review Summary
```
✅ Total Records: 450
✅ Valid Records: 450
✅ Errors: 0
✅ Status: Ready to Lock
```

Or if errors:

```
⚠️ Total Records: 450
⚠️ Valid Records: 445
❌ Errors: 5
→ Click "Download Error Report"
→ Fix the 5 errors
→ Re-upload
```

#### 6. Lock Batch
```
Once validation passes:
Click "Lock Batch (No Changes Allowed)"
→ Prevents accidental modifications
```

## CSV Template Structure

The template is **dynamically generated** based on subject paper structure:

### For 2-Paper Subject (e.g., Mathematics)
```csv
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100)
S1378-0501,SAMPLE,75,75
```

### For Subject with Practical (e.g., Biology)
```csv
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Practical (out of 100)
S1378-0501,SAMPLE,75,75,80
```

### For Subject with Project (e.g., Technology)
```csv
Index Number,Full Name,Paper 1 (out of 100),Paper 2 (out of 100),Project (out of 100)
S1378-0501,SAMPLE,75,75,85
```

## Common Issues & Solutions

### Issue: "Candidate not found"
**Cause**: Index number doesn't match database  
**Solution**: Verify spelling, check student registration

### Issue: "Subject not in combination"
**Cause**: Selected subject doesn't belong to student's combination  
**Solution**: Check student's actual combination, upload for correct subject

### Issue: "Marks must be between 0 and 100"
**Cause**: Invalid mark value (negative, >100, or non-numeric)  
**Solution**: Edit CSV, correct the mark, re-upload

### Issue: "Paper marks missing"
**Cause**: Student has empty cell in required paper column  
**Solution**: Fill in all paper marks, leave empty only if optional

### Issue: Can't modify batch after locking
**Cause**: Batch is locked to prevent accidents  
**Solution**: Lock only when completely sure. Unlock requests go to admin

## Data Validation Rules

| Rule | Details | Error Message |
|------|---------|---------------|
| Candidate exists | Index number in database | "Candidate with index number 'X' not found" |
| ACSEE registered | Candidate exam_type = 'ACSEE' | "Candidate is not registered for ACSEE" |
| Combination match | Subject in student's combination | "Subject X is not in candidate's combination" |
| Papers complete | All required papers have marks | "Paper 1 marks are missing or empty" |
| Mark range | 0 ≤ mark ≤ 100 | "Paper 1 marks must be between 0 and 100" |
| Mark type | Numeric value | "Paper 1 marks must be numeric" |

## Batch States Explained

| Status | Meaning | Can Edit? | What Next? |
|--------|---------|-----------|-----------|
| **DRAFT** | Uploaded, being validated | Yes | Fix errors, re-upload, or lock |
| **VALIDATED** | All validations passed | Yes | Review errors report if any, or lock |
| **LOCKED** | Approved, prevents changes | No | Admin processes grades (future) |
| **PROCESSED** | Grades computed & stored | No | Archive/export |

## API Endpoints

For developers integrating with the mark entry system:

```bash
# Get available regions
GET /api/mark-entry/regions
→ [{ id, code, name }, ...]

# Get districts in region
GET /api/mark-entry/districts?region_id=4
→ [{ id, code, name }, ...]

# Get schools in district
GET /api/mark-entry/schools?district_id=15
→ [{ id, code, name }, ...]

# Get ACSEE subjects
GET /api/mark-entry/subjects
→ [{ id, code, name, written_papers, has_practical, has_project }, ...]

# Get ACSEE combinations
GET /api/mark-entry/combinations
→ [{ id, code, description }, ...]

# Upload marks (multipart form data)
POST /mark-entry/upload
  exam_year: 2026
  school_id: 65
  subject_id: 4
  combination_id: 2
  file: <csv file>
→ { success, batch_id, batch_code, message, validation }

# Get batch details
GET /mark-entry/batch/123
→ { batch: {...}, raw_marks: [...] }

# Download error report
GET /mark-entry/batch/123/error-report
→ CSV file with errors

# Lock batch
POST /mark-entry/batch/123/lock
→ { success, message }
```

## Performance Tips

1. **Large Uploads**: Keep CSV under 5MB (usually ~500+ candidates)
2. **Multiple Subjects**: Process one subject at a time
3. **Batch Code**: Auto-generated, includes timestamp (unique identifier)
4. **Duplicate Index Numbers**: Last one wins (updateOrCreate)

## For System Administrators

### Monitor Imports
```php
// Get recent imports
$batches = MarkImportBatch::latest('imported_at')->limit(10)->get();

// Get batches with errors
$problemBatches = MarkImportBatch::where('error_records', '>', 0)->get();

// Get locked batches ready for processing
$readyForProcessing = MarkImportBatch::where('status', 'locked')->get();
```

### Re-validate Batch
```php
$batch = MarkImportBatch::find(123);
$service = app(MarkImportService::class);
$result = $service->validateBatch($batch);
```

### Generate Error Report
```php
$batch = MarkImportBatch::find(123);
$errors = $batch->rawMarks()->where('has_errors', true)->get();
// Send to user as CSV
```

## Database Queries for Analysis

```sql
-- Total marks by school
SELECT school_id, COUNT(*) as total, 
       SUM(IF(has_errors=0, 1, 0)) as valid,
       SUM(IF(has_errors=1, 1, 0)) as errors
FROM raw_marks
GROUP BY school_id;

-- Batches pending processing
SELECT * FROM mark_import_batches 
WHERE status = 'locked' AND processed_at IS NULL;

-- Error summary
SELECT mark_import_batch_id, COUNT(*) as error_count
FROM raw_marks
WHERE has_errors = 1
GROUP BY mark_import_batch_id;
```

## Support & Troubleshooting

**Module Version**: 1.0  
**Last Updated**: January 31, 2026  
**Status**: Production Ready

For issues:
1. Check error report (CSV download)
2. Verify CSV format matches template
3. Verify candidate registration (Registration → Candidates)
4. Check ACSEE exam setup (EXAM TYPE → ACSEE)
5. Contact system administrator

---

**Built for NECTA ACSEE examinations**  
**Professional Grade Educational Data Management**
