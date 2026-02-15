# NECTA Phase 2 CSV Import Service Implementation
**Date**: 2026-02-15  
**Feature**: Updated CSV Import for SCHOOL + PRIVATE Candidates  
**Status**: Implementation Guide

---

## Overview

This guide explains how to update the existing CSV import service to support NECTA Phase 2:
- SCHOOL candidates (uses combination templates)
- PRIVATE candidates (manual subject selection)

---

## Implementation Steps

### Step 1: Update Candidate Import Service

**File**: `app/Services/Candidates/CandidateImportService.php`

Add support for `candidate_type` and `subjects` fields:

```php
<?php

namespace App\Services\Candidates;

use App\Models\Candidate;
use App\Models\CandidateSubjectSelection;
use App\Models\Combination;
use App\Models\Subject;
use App\Services\AcseeAllocationValidator;
use Illuminate\Support\Facades\DB;

class CandidateImportService
{
    protected $validator;
    protected $errors = [];
    protected $imported = 0;

    public function __construct(AcseeAllocationValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Import candidates from CSV
     * 
     * @param array $rows CSV rows as associative arrays
     * @param int $examTypeId ACSEE exam type ID
     * @param int $examYearId Exam year ID
     * @return array {imported: int, failed: int, errors: []}
     */
    public function import(array $rows, int $examTypeId, int $examYearId): array
    {
        $this->imported = 0;
        $this->errors = [];

        DB::transaction(function () use ($rows, $examTypeId, $examYearId) {
            foreach ($rows as $rowNum => $row) {
                try {
                    $this->processRow($row, $examTypeId, $examYearId, $rowNum + 2); // +2 for header + 1-indexing
                } catch (\Exception $e) {
                    $this->errors[] = [
                        'row' => $rowNum + 2,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return [
            'imported' => $this->imported,
            'failed' => count($this->errors),
            'errors' => $this->errors,
        ];
    }

    /**
     * Process a single row from CSV
     */
    protected function processRow(array $row, int $examTypeId, int $examYearId, int $rowNum): void
    {
        // Validate required fields
        $candidateId = $row['candidate_id'] ?? null;
        $fullName = $row['full_name'] ?? null;
        $gender = $row['gender'] ?? null;
        $candidateType = $row['candidate_type'] ?? 'SCHOOL';

        if (!$candidateId || !$fullName || !$gender) {
            throw new \Exception("Missing required fields: candidate_id, full_name, gender");
        }

        // Validate candidate type
        if (!in_array($candidateType, ['SCHOOL', 'PRIVATE'])) {
            throw new \Exception("candidate_type must be SCHOOL or PRIVATE");
        }

        // Create or update candidate
        $candidate = Candidate::updateOrCreate(
            ['registration_number' => $candidateId],
            [
                'full_name' => $fullName,
                'gender' => $gender,
                'candidate_type' => $candidateType,
                'exam_type_id' => $examTypeId,
            ]
        );

        // Handle subject allocation based on type
        if ($candidateType === 'SCHOOL') {
            $this->allocateFromCombination($candidate, $row, $examTypeId, $examYearId);
        } else {
            $this->allocateFromSubjects($candidate, $row, $examTypeId, $examYearId);
        }

        $this->imported++;
    }

    /**
     * Allocate subjects from combination (SCHOOL candidates)
     */
    protected function allocateFromCombination(
        Candidate $candidate,
        array $row,
        int $examTypeId,
        int $examYearId
    ): void {
        $combinationCode = $row['combination'] ?? null;

        if (!$combinationCode) {
            throw new \Exception("Combination required for SCHOOL candidates");
        }

        // Find combination
        $combination = Combination::where('code', $combinationCode)
            ->orWhere('name', $combinationCode)
            ->first();

        if (!$combination) {
            throw new \Exception("Combination not found: $combinationCode");
        }

        // Validate using validator service
        $result = $this->validator->validateFromCombination(
            $candidate,
            $combination->id,
            $examTypeId,
            $examYearId
        );

        if (!$result['ok']) {
            throw new \Exception(implode('; ', $result['errors']));
        }

        // Allocate subjects
        $this->allocateSubjectsToDB(
            $candidate,
            $result['principal_subject_ids'],
            $examYearId,
            'template'
        );
    }

    /**
     * Allocate subjects manually (PRIVATE candidates)
     */
    protected function allocateFromSubjects(
        Candidate $candidate,
        array $row,
        int $examTypeId,
        int $examYearId
    ): void {
        $subjectsStr = $row['subjects'] ?? null;

        if (!$subjectsStr) {
            throw new \Exception("Subjects required for PRIVATE candidates (format: 111|102|103|104)");
        }

        // Parse subject IDs
        $subjectIds = array_map('intval', explode('|', $subjectsStr));
        $subjectIds = array_filter($subjectIds); // Remove empty values

        if (empty($subjectIds)) {
            throw new \Exception("Invalid subject format. Use pipe-separated IDs: 111|102|103|104");
        }

        // Validate subjects exist
        $invalidIds = [];
        foreach ($subjectIds as $subjectId) {
            if (!Subject::find($subjectId)) {
                $invalidIds[] = $subjectId;
            }
        }

        if (!empty($invalidIds)) {
            throw new \Exception("Subject(s) not found: " . implode(', ', $invalidIds));
        }

        // Validate using validator service
        $result = $this->validator->validate(
            $candidate,
            $examTypeId,
            $examYearId,
            $subjectIds
        );

        if (!$result['ok']) {
            throw new \Exception(implode('; ', $result['errors']));
        }

        // Allocate subjects
        $this->allocateSubjectsToDB(
            $candidate,
            $result['principal_subject_ids'],
            $examYearId,
            'import'
        );
    }

    /**
     * Save subject allocations to database
     */
    protected function allocateSubjectsToDB(
        Candidate $candidate,
        array $subjectIds,
        int $examYearId,
        string $source
    ): void {
        // Delete existing allocations for this exam year (if needed)
        CandidateSubjectSelection::where('candidate_id', $candidate->id)
            ->where('exam_year_id', $examYearId)
            ->delete();

        // Insert new allocations
        foreach ($subjectIds as $subjectId) {
            CandidateSubjectSelection::create([
                'candidate_id' => $candidate->id,
                'subject_id' => $subjectId,
                'exam_year_id' => $examYearId,
                'is_principal' => true,
                'source' => $source,
                'created_by' => auth()->id() ?? 1,
            ]);
        }
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

---

### Step 2: Update Controller

**File**: `app/Http/Controllers/CandidatesController.php`

```php
public function importCSV(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:csv,txt',
        'exam_type' => 'required|exists:exam_types,id',
        'exam_year' => 'required|exists:exam_years,id',
    ]);

    // Read CSV
    $file = $request->file('file');
    $rows = array_map('str_getcsv', file($file->getRealPath()));
    $headers = array_shift($rows);

    // Convert to associative arrays
    $data = [];
    foreach ($rows as $row) {
        if (count($row) < 2) continue; // Skip empty rows
        $data[] = array_combine($headers, $row);
    }

    // Import
    $service = new CandidateImportService(new AcseeAllocationValidator());
    $result = $service->import(
        $data,
        $request->input('exam_type'),
        $request->input('exam_year')
    );

    return response()->json($result);
}
```

---

### Step 3: Update Database Migration (if needed)

The Phase 1 migration already added these columns, but verify they exist:

```bash
php artisan tinker
# Inside tinker:
Schema::hasColumn('candidates', 'candidate_type')  // Should be true
Schema::hasColumn('candidate_subject_selections', 'is_principal')  // Should be true
Schema::hasColumn('candidate_subject_selections', 'source')  // Should be true
exit
```

---

## Validation Rules Applied During Import

### SCHOOL Candidates

✅ **Fields Required:**
- `candidate_id`
- `full_name`
- `gender`
- `candidate_type` = "SCHOOL"
- `combination` (not empty)
- `school_code`
- `exam_type`
- `exam_year`

✅ **Validation:**
- Combination exists
- School code valid
- Subjects from combination are allocated
- General Studies (111) is in combination
- At least 3 principal subjects allocated

### PRIVATE Candidates

✅ **Fields Required:**
- `candidate_id`
- `full_name`
- `gender`
- `candidate_type` = "PRIVATE"
- `subjects` (not empty, pipe-separated)
- `district`
- `exam_type`
- `exam_year`

✅ **Validation:**
- All subject IDs exist
- General Studies (111) is included
- At least 3 principal subjects (besides GS)
- Total: 4+ subjects minimum
- No duplicates

---

## Usage Examples

### Import SCHOOL Candidates

```bash
php artisan candidates:import \
  --file=templates/candidates_school_import_example.csv \
  --exam-type=ACSEE \
  --exam-year=2026
```

### Import PRIVATE Candidates

```bash
php artisan candidates:import \
  --file=templates/candidates_private_import_example.csv \
  --exam-type=ACSEE \
  --exam-year=2026
```

### Import Mixed

```bash
php artisan candidates:import \
  --file=templates/candidates_mixed_import_example.csv \
  --exam-type=ACSEE \
  --exam-year=2026
```

---

## Error Handling

### Example Error Response

```json
{
  "imported": 5,
  "failed": 2,
  "errors": [
    {
      "row": 3,
      "error": "Combination not found: INVALID_COMBO"
    },
    {
      "row": 7,
      "error": "General Studies (111) is mandatory for ACSEE candidates"
    }
  ]
}
```

---

## Testing Import

### Test with Example Files

```bash
# Create test directory
mkdir -p tests/csv

# Copy example templates
cp templates/candidates_school_import_example.csv tests/csv/
cp templates/candidates_private_import_example.csv tests/csv/
cp templates/candidates_mixed_import_example.csv tests/csv/

# Run imports with test data
php artisan candidates:import --file=tests/csv/candidates_school_import_example.csv --exam-type=1 --exam-year=1
php artisan candidates:import --file=tests/csv/candidates_private_import_example.csv --exam-type=1 --exam-year=1
php artisan candidates:import --file=tests/csv/candidates_mixed_import_example.csv --exam-type=1 --exam-year=1
```

### Verify Results

```bash
# Check imported count
mysql -e "SELECT COUNT(*) as total FROM candidates WHERE exam_type_id = 1;"

# Check allocation breakdown
mysql -e "
SELECT 
  c.candidate_type,
  COUNT(c.id) as candidate_count,
  AVG(subject_count) as avg_subjects
FROM candidates c
LEFT JOIN (
  SELECT candidate_id, COUNT(*) as subject_count
  FROM candidate_subject_selections
  GROUP BY candidate_id
) css ON c.id = css.candidate_id
GROUP BY c.candidate_type;
"

# Check General Studies present
mysql -e "
SELECT 
  COUNT(DISTINCT candidate_id) as candidates_with_gs
FROM candidate_subject_selections
WHERE subject_id = (SELECT id FROM subjects WHERE code = '111');
"
```

---

## Performance Considerations

### For Large Imports (1000+ candidates)

1. **Use batching:**
```php
$batchSize = 500;
$batches = array_chunk($data, $batchSize);

foreach ($batches as $batch) {
    $service->import($batch, $examTypeId, $examYearId);
}
```

2. **Increase PHP limits:**
```bash
php -d memory_limit=1024M artisan candidates:import --file=large_file.csv
```

3. **Use background job:**
```php
// Dispatch async import
CandidateImportJob::dispatch($file, $examTypeId, $examYearId);
```

---

## Rollback Import

If import has issues, rollback:

```bash
# Via database backup
php artisan backup:restore --from=backup-before-import.sql

# Or manually delete by exam year
DELETE FROM candidate_subject_selections WHERE exam_year_id = 1;
DELETE FROM candidates WHERE exam_type_id = 1 AND created_at > '2026-02-15 00:00:00';
```

---

## Documentation

See: `NECTA_CSV_IMPORT_TEMPLATE_2026_02_15.md` for:
- CSV template formats
- Subject ID reference
- Best practices
- Troubleshooting

---

**Version**: 1.0  
**Created**: 2026-02-15  
**Status**: Ready for Implementation
