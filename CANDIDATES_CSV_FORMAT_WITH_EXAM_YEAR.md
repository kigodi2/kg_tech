# Candidates CSV Import Format - With Exam Year Support

**Date**: 2026-02-04
**Update**: Added EXAM_YEAR column to CSV import template for automatic exam registration during import

## CSV Column Format

The candidates import CSV now supports 7 columns:

| Column | Header | Required | Type | Example |
|--------|--------|----------|------|---------|
| 1 | CANDIDATE_ID | No | String | S1378-0501 |
| 2 | FULL_NAME | Yes | String | JOHN SMITH |
| 3 | SEX | Yes | String | M or F |
| 4 | COMBINATION | No* | String | CBE or HGE |
| 5 | SCHOOL_CODE | Yes | String | S1378 |
| 6 | EXAM_TYPE | Yes | String | ACSEE, CSEE, PSLE |
| 7 | EXAM_YEAR | No** | Integer | 2026 |

*Required if EXAM_TYPE is ACSEE
**Optional - if not provided in CSV, falls back to exam year selected in import modal

## Example CSV

```
CANDIDATE_ID,FULL_NAME,SEX,COMBINATION,SCHOOL_CODE,EXAM_TYPE,EXAM_YEAR
,JOHN SMITH,M,CBE,S1378,ACSEE,2026
,JANE DOE,F,HGE,S1378,ACSEE,2026
S0158-0501,AGUSTINO FESTO,M,HGE,S0158,ACSEE,2026
```

## Behavior

### If EXAM_YEAR column is provided:
- Exam registration is created **immediately during import** for the specified year
- No backfill scripts needed
- Subjects automatically registered based on combination

### If EXAM_YEAR column is NOT provided:
- Uses the exam year selected in the import modal
- Exam registration created with modal year
- Subjects automatically registered based on combination

### Fallback Order:
1. **CSV EXAM_YEAR** (column 7) - if provided
2. **Modal EXAM_YEAR** - if CSV year not provided
3. **Skip registration** - if neither provided (only candidate created)

## Advantages

✓ **No backfill scripts needed** - registrations created during import
✓ **Per-candidate year control** - different candidates can have different exam years in same import
✓ **Backward compatible** - column 7 is optional
✓ **Flexible** - modal year acts as default if column empty
✓ **Automatic subject linking** - subjects auto-registered based on combination+year

## Migration Guide

### Old Format (6 columns):
```
CANDIDATE_ID,FULL_NAME,SEX,COMBINATION,SCHOOL_CODE,EXAM_TYPE
,JOHN SMITH,M,CBE,S1378,ACSEE
```

### New Format (7 columns):
```
CANDIDATE_ID,FULL_NAME,SEX,COMBINATION,SCHOOL_CODE,EXAM_TYPE,EXAM_YEAR
,JOHN SMITH,M,CBE,S1378,ACSEE,2026
```

Old format CSVs still work - just leave column 7 empty or use modal year.

## Files Modified

- `routes/web.php` - Updated `/api/candidates/import` endpoint to:
  - Read EXAM_YEAR from column 7 of CSV
  - Use CSV year if provided, otherwise use modal year
  - Create exam registrations during import (not after)

## Testing

1. Download import CSV template
2. Add EXAM_YEAR values in column 7
3. Import CSV
4. Verify exam year appears in Candidates table immediately
5. No need to run backfill scripts

## Status

✅ **IMPLEMENTED** - Exam year can now be specified in CSV for automatic registration during import
