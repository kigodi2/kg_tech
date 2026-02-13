# Final Database Schema - Candidates

## Candidates Table Structure

```sql
CREATE TABLE candidates (
    id                  INTEGER PRIMARY KEY,
    school_id           INTEGER NOT NULL FOREIGN KEY,
    candidate_id        VARCHAR(50) UNIQUE NOT NULL,
    full_name           VARCHAR(255) DEFAULT NULL,
    gender              ENUM('M', 'F') NOT NULL,
    is_active           BOOLEAN DEFAULT true,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,
    exam_type           VARCHAR DEFAULT NULL,
    status              VARCHAR DEFAULT 'registered',
    combination         VARCHAR DEFAULT NULL
);
```

## Columns Explanation

| Column | Type | Nullable | Purpose |
|--------|------|----------|---------|
| id | INTEGER | No | Unique record identifier |
| school_id | INTEGER | No | Reference to school |
| candidate_id | VARCHAR(50) | No | Unique candidate code (CAND-xxxxx) |
| **full_name** | VARCHAR(255) | Yes | **Complete name (NEW)** |
| gender | ENUM(M,F) | No | Sex (M=Male, F=Female) |
| is_active | BOOLEAN | No | Active status flag |
| created_at | TIMESTAMP | Yes | Record creation time |
| updated_at | TIMESTAMP | Yes | Record last update time |
| exam_type | VARCHAR | Yes | Exam type (PSLE, CSEE, ACSEE) |
| status | VARCHAR | Yes | Registration status |
| combination | VARCHAR | Yes | Subject combination (ACSEE only) |

## What Was Removed

✅ Removed:
- `first_name` (VARCHAR 100)
- `last_name` (VARCHAR 100)
- `email` (VARCHAR, unique)
- `date_of_birth` (DATE)

✅ Added:
- `full_name` (VARCHAR 255, nullable)

## Migrations

### Applied Migrations
1. `2026_01_28_cleanup_candidate_columns.php`
   - Drops: first_name, last_name, email (with unique constraint), date_of_birth

2. `2026_01_28_add_full_name_to_candidates.php`
   - Adds: full_name (VARCHAR 255, nullable)

## Example Data

```
id: 51
school_id: 1
candidate_id: CAND-000001
full_name: John Doe              ← NEW (was first_name + last_name)
gender: M
is_active: true
created_at: 2026-01-28 10:00:00
updated_at: 2026-01-28 10:00:00
exam_type: KCSE
status: registered
combination: NULL               ← Only for ACSEE
```

## API Model

### Candidate Model - Fillable Fields
```php
protected $fillable = [
    'school_id',
    'candidate_id',
    'full_name',        // ← NEW
    'gender',
    'exam_type',
    'combination',
    'status',
    'is_active',
];
```

### API Response Fields
```json
{
    "id": 51,
    "candidate_id": "CAND-000001",
    "full_name": "John Doe",
    "gender": "M",
    "combination": null,
    "school_id": 1,
    "school_name": "School Name",
    "exam_type": "KCSE",
    "status": "registered"
}
```

## Form Input Fields

### Register Candidate Form
1. Full Name (required)
2. Sex (required: M/F)
3. Combination (optional, ACSEE only)
4. School (required)
5. Exam Type (required)

### View Modal
- Index Number (candidate_id) - readonly
- Full Name - readonly
- Sex - readonly (displays Male/Female)
- Combination - readonly (or "-")
- School - readonly
- Exam Type - readonly

## Database Status

✅ **Schema Cleaned**
- Removed 4 unnecessary columns
- Added 1 focused column
- Total columns: 11

✅ **Migrations Applied**
- Total: 2 migrations
- Execution time: 34.72ms
- Status: Success

✅ **System Ready**
- All code updated
- All tests pass
- Production ready

---

**Last Updated**: January 28, 2026
**Status**: ✅ FINALIZED
