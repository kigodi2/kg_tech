# RMS Exam Years Implementation Guide

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Installation Steps](#installation-steps)
4. [Migration Execution](#migration-execution)
5. [API Integration](#api-integration)
6. [Testing & Validation](#testing--validation)
7. [Rollback Procedures](#rollback-procedures)
8. [Troubleshooting](#troubleshooting)

---

## Overview

### What This Implements

This implementation introduces **exam years** as a first-class domain entity in the RMS, enabling:

- ✅ Multi-year data isolation (students, marks, results)
- ✅ Year locking after publication (immutable results)
- ✅ Safe migration from legacy single-year systems
- ✅ Zero data loss guarantee
- ✅ ACID-compliant constraints

### Key Components

1. **Database Layer**
   - `exam_years` table (foundation)
   - `exam_year_id` columns on all exam-related tables
   - Foreign key constraints (ON DELETE RESTRICT)
   - Triggers for write protection on locked years

2. **Service Layer (Python)**
   - `BaseService` with year validation
   - `CandidateService`, `MarksService`, etc. (year-aware)
   - Mandatory `exam_year_id` parameter (no fallback)

3. **API Layer**
   - `require_exam_year` middleware
   - Year context in request (query, body, or header)
   - Year-aware endpoints

4. **Migration Tools**
   - Python migration script (safe, transactional)
   - SQL migration script (manual control)
   - Validation checks (pre/post)

---

## Architecture

### Database Schema

```
exam_years (NEW - FOUNDATION)
├── id (PK)
├── year_label (UNIQUE, e.g., "2024")
├── is_active (only one TRUE at a time)
├── is_locked (TRUE after publication, immutable)
├── published_at, locked_at
└── Indexes: is_active, is_locked, year_label

candidates (MODIFIED)
├── exam_year_id (FK -> exam_years)
├── ... existing columns ...
└── Trigger: prevent_write_to_locked_years

marks, results, registrations, etc. (MODIFIED)
├── exam_year_id (FK -> exam_years)
└── Trigger: prevent_write_to_locked_years
```

### Service Layer Pattern

```python
# BEFORE: Year was implicit/global
get_registered_candidates(subject_id, school_id)

# AFTER: Year is explicit and mandatory
get_registered_candidates(subject_id, school_id, exam_year_id)
```

All service methods:
- Accept `exam_year_id` as explicit parameter
- Call `_validate_exam_year(exam_year_id)` first
- Call `_check_year_not_locked(exam_year_id)` before writes
- Raise exceptions if year is missing or locked

---

## Installation Steps

### Step 1: Backup Database

```bash
# Create backup before migration
mysqldump -u root -p rms > rms_backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
ls -lh rms_backup_*.sql
```

### Step 2: Prepare Migration Scripts

```bash
# Copy migration files to project
cp RMS_EXAM_YEARS_MIGRATION_SQL.sql ./migrations/
cp rms_exam_years_migration.py ./scripts/

# Make Python script executable
chmod +x ./scripts/rms_exam_years_migration.py
```

### Step 3: Review Configuration

Edit database config in `rms_exam_years_migration.py`:

```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': 'your_password',  # ← UPDATE THIS
    'database': 'rms'
}
```

### Step 4: Run Pre-Migration Tests

```bash
# Test database connection
python3 ./scripts/rms_exam_years_migration.py --test-connection

# Count records (dry run)
python3 ./scripts/rms_exam_years_migration.py --count-only
```

---

## Migration Execution

### Option A: Automated Migration (Recommended)

```bash
# Run Python migration script
python3 ./scripts/rms_exam_years_migration.py

# Output: migration.log + console summary
# Review log for any warnings/errors
cat migration.log
```

### Option B: Manual SQL Migration (Advanced)

For more control, execute SQL phases manually:

```bash
mysql -u root -p rms < migrations/RMS_EXAM_YEARS_MIGRATION_SQL.sql
```

**Before executing all at once:**
1. Create exam_years table
2. Insert legacy year manually
3. Record the ID: `SELECT @legacy_year_id := id FROM exam_years ORDER BY id DESC LIMIT 1;`
4. Update all backfill statements with actual ID
5. Execute backfill queries
6. Run validation checks
7. Add constraints

### Step-by-Step Manual Migration

```sql
-- 1. Create exam_years table
CREATE TABLE exam_years (...);

-- 2. Create legacy year
INSERT INTO exam_years (year_label, is_active) VALUES ('2024', TRUE);
SELECT @legacy_year_id := LAST_INSERT_ID();

-- 3. Add columns to all tables
ALTER TABLE candidates ADD COLUMN exam_year_id INT NOT NULL DEFAULT 0;
-- ... repeat for all tables ...

-- 4. Backfill data (replace <legacy_year_id> with actual value)
UPDATE candidates SET exam_year_id = <legacy_year_id> WHERE exam_year_id = 0;
-- ... repeat for all tables ...

-- 5. Validate integrity (run all validation queries from script)
SELECT ... FROM candidates WHERE exam_year_id IS NULL;  -- Should return 0

-- 6. Add constraints (only after validation passes)
ALTER TABLE candidates ADD CONSTRAINT fk_candidates_exam_year
FOREIGN KEY (exam_year_id) REFERENCES exam_years(id);
-- ... repeat for all tables ...
```

---

## API Integration

### Python Service Usage

```python
from services.candidate_service import CandidateService

service = CandidateService(db_config)
service.connect()

try:
    # Get candidates for a specific year (MANDATORY parameter)
    candidates = service.get_registered_candidates(
        subject_id=5,
        school_id=10,
        exam_year_id=1  # ← REQUIRED, not optional
    )
    
    # Create candidate for specific year
    new_id = service.create_candidate(
        {
            'candidate_id': 'S000001',
            'full_name': 'John Doe',
            'gender': 'M',
            'school_id': 10
        },
        exam_year_id=1  # ← REQUIRED
    )
    
except ExamYearRequiredError as e:
    print(f"Year missing or invalid: {e}")
except YearLockedError as e:
    print(f"Year is locked: {e}")
finally:
    service.disconnect()
```

### Flask API Endpoints

```python
from flask import Blueprint, request, jsonify, g
from middleware.exam_year_middleware import require_exam_year

@app.route('/api/candidates/by-subject', methods=['GET'])
@require_exam_year
def get_candidates_by_subject():
    """
    Requires exam_year_id via:
    - Query param: ?exam_year_id=1
    - Header: X-Exam-Year-ID: 1
    - JSON body: {"exam_year_id": 1}
    """
    exam_year_id = g.exam_year_id  # Middleware sets this
    
    candidates = service.get_registered_candidates(
        request.args.get('subject_id', type=int),
        request.args.get('school_id', type=int),
        exam_year_id
    )
    
    return jsonify({
        'success': True,
        'exam_year_id': exam_year_id,
        'count': len(candidates),
        'data': candidates
    })
```

### API Request Examples

```bash
# 1. Query parameter
curl "http://localhost:5000/api/candidates/by-subject?subject_id=5&school_id=10&exam_year_id=1"

# 2. HTTP header
curl -H "X-Exam-Year-ID: 1" \
     "http://localhost:5000/api/candidates/by-subject?subject_id=5&school_id=10"

# 3. JSON body
curl -X POST "http://localhost:5000/api/candidates" \
     -H "Content-Type: application/json" \
     -d '{
       "exam_year_id": 1,
       "candidate_id": "S000001",
       "full_name": "John Doe"
     }'
```

---

## Testing & Validation

### Test Suite

Create `test_exam_years.py`:

```python
import unittest
from services.candidate_service import CandidateService
from services.base_service import ExamYearRequiredError, YearLockedError

class TestExamYears(unittest.TestCase):
    
    def setUp(self):
        self.service = CandidateService(DB_CONFIG)
        self.service.connect()
    
    def tearDown(self):
        self.service.disconnect()
    
    def test_missing_exam_year_id_raises_error(self):
        """Year ID is mandatory"""
        with self.assertRaises(ExamYearRequiredError):
            self.service.get_registered_candidates(5, 10, None)
    
    def test_invalid_exam_year_id_raises_error(self):
        """Invalid year ID raises error"""
        with self.assertRaises(ExamYearRequiredError):
            self.service.get_registered_candidates(5, 10, 99999)
    
    def test_create_candidate_in_unlocked_year(self):
        """Can create candidate in active, unlocked year"""
        candidate_id = self.service.create_candidate(
            {
                'candidate_id': 'TEST001',
                'full_name': 'Test Candidate',
                'gender': 'M',
                'school_id': 10
            },
            exam_year_id=1
        )
        self.assertIsNotNone(candidate_id)
    
    def test_create_candidate_in_locked_year_fails(self):
        """Cannot create candidate in locked year"""
        # Assuming year 2 is locked
        with self.assertRaises(YearLockedError):
            self.service.create_candidate(
                {
                    'candidate_id': 'TEST002',
                    'full_name': 'Test Candidate',
                    'gender': 'F',
                    'school_id': 10
                },
                exam_year_id=2  # Locked
            )
    
    def test_get_candidates_by_year(self):
        """Can retrieve candidates specific to year"""
        candidates = self.service.get_candidates_by_year(exam_year_id=1)
        self.assertIsInstance(candidates, list)
    
    def test_year_isolation(self):
        """Year 1 candidates don't appear in Year 2 queries"""
        year1_candidates = self.service.get_candidates_by_year(1)
        year2_candidates = self.service.get_candidates_by_year(2)
        
        # Should be different datasets
        year1_ids = {c['id'] for c in year1_candidates}
        year2_ids = {c['id'] for c in year2_candidates}
        
        # Validate isolation
        self.assertEqual(len(year1_ids.intersection(year2_ids)), 0)

if __name__ == '__main__':
    unittest.main()
```

### Run Tests

```bash
python3 -m unittest test_exam_years -v

# Output:
# test_missing_exam_year_id_raises_error ... ok
# test_invalid_exam_year_id_raises_error ... ok
# test_create_candidate_in_unlocked_year ... ok
# test_create_candidate_in_locked_year_fails ... ok
# test_get_candidates_by_year ... ok
# test_year_isolation ... ok
```

### Validation Queries

```sql
-- 1. Check no NULL exam_year_id
SELECT table_name, NULL_COUNT FROM (
  SELECT 'candidates' as table_name, COUNT(*) as NULL_COUNT 
  FROM candidates WHERE exam_year_id IS NULL
  UNION ALL
  SELECT 'marks', COUNT(*) FROM marks WHERE exam_year_id IS NULL
  UNION ALL
  SELECT 'results', COUNT(*) FROM results WHERE exam_year_id IS NULL
) null_check WHERE NULL_COUNT > 0;

-- Expected: No rows returned

-- 2. Check referential integrity
SELECT table_name, ORPHANED_COUNT FROM (
  SELECT 'candidates' as table_name, COUNT(*) as ORPHANED_COUNT
  FROM candidates c
  LEFT JOIN exam_years y ON c.exam_year_id = y.id
  WHERE c.exam_year_id > 0 AND y.id IS NULL
  UNION ALL
  SELECT 'marks', COUNT(*)
  FROM marks m
  LEFT JOIN exam_years y ON m.exam_year_id = y.id
  WHERE m.exam_year_id > 0 AND y.id IS NULL
) orphan_check WHERE ORPHANED_COUNT > 0;

-- Expected: No rows returned

-- 3. Check year lock enforcement
-- Try INSERT into locked year (should fail)
INSERT INTO marks (candidate_id, subject_id, exam_year_id, paper_1)
VALUES (1, 5, 2, 75);  -- If year 2 is locked, should get error:
-- "Cannot insert marks: Exam year is locked"
```

---

## Rollback Procedures

### If Migration Fails During Execution

```bash
# 1. Stop the migration script (Ctrl+C)

# 2. Check migration status
mysql -u root -p rms -e "SELECT COUNT(*) FROM exam_years;"
mysql -u root -p rms -e "SELECT COUNT(DISTINCT exam_year_id) FROM candidates;"

# 3. If migration is incomplete, restore backup
mysql -u root -p rms < rms_backup_YYYYMMDD_HHMMSS.sql

# 4. Fix any issues and retry
```

### Full Rollback to Pre-Migration State

```sql
-- 1. Drop foreign key constraints
ALTER TABLE candidates DROP FOREIGN KEY fk_candidates_exam_year;
ALTER TABLE registrations DROP FOREIGN KEY fk_registrations_exam_year;
-- ... repeat for all tables ...

-- 2. Remove exam_year_id columns and indexes
ALTER TABLE candidates 
DROP FOREIGN KEY fk_candidates_exam_year,
DROP COLUMN exam_year_id,
DROP INDEX idx_exam_year_id,
DROP INDEX idx_exam_year_school;
-- ... repeat for all tables ...

-- 3. Drop triggers
DROP TRIGGER lock_year_after_publication;
DROP TRIGGER prevent_insert_candidates_locked_year;
-- ... drop all other triggers ...

-- 4. Drop procedures
DROP PROCEDURE deactivate_all_other_years;
DROP PROCEDURE publish_exam_year_results;
DROP PROCEDURE get_active_exam_year;

-- 5. Drop exam_years table
DROP TABLE exam_years;

-- Verify rollback
SHOW TABLES;  -- Should not include exam_years
DESC candidates;  -- Should not have exam_year_id
```

---

## Troubleshooting

### Issue: "Duplicate column name"

**Cause:** Column already exists from previous migration attempt

**Solution:**
```sql
-- Check if column exists
DESCRIBE candidates;

-- If exam_year_id exists, skip the ADD COLUMN step
-- Or drop it first:
ALTER TABLE candidates DROP COLUMN exam_year_id;
```

### Issue: "Foreign key constraint fails"

**Cause:** exam_year_id values reference non-existent year IDs

**Solution:**
```sql
-- Check for orphaned references
SELECT DISTINCT exam_year_id FROM candidates
WHERE exam_year_id NOT IN (SELECT id FROM exam_years);

-- If found, either:
-- 1. Update to valid year ID
UPDATE candidates SET exam_year_id = 1 
WHERE exam_year_id NOT IN (SELECT id FROM exam_years);

-- 2. Or backfill again
UPDATE candidates SET exam_year_id = 1 WHERE exam_year_id = 0;
```

### Issue: Migration script hangs

**Cause:** Long-running UPDATE on large table

**Solution:**
```bash
# In another terminal, monitor progress
watch 'SELECT COUNT(*) FROM candidates WHERE exam_year_id = 0;'

# If stuck, check slow queries
SHOW PROCESSLIST;

# If needed, kill long-running query
KILL <query_id>;
```

### Issue: "Only one active year allowed" constraint violation

**Cause:** Trying to set multiple years as active

**Solution:**
```python
# Use stored procedure to switch years safely
service.set_active_exam_year(exam_year_id)  # Automatically deactivates others
```

---

## Post-Migration Checklist

- [ ] Backup created and verified
- [ ] Migration script executed successfully
- [ ] All validation queries passed (0 NULLs, referential integrity)
- [ ] Python test suite passed
- [ ] Application starts without errors
- [ ] Year selector appears in UI
- [ ] Can query candidates by year
- [ ] Cannot modify locked year (403 error)
- [ ] Can publish results and lock year
- [ ] Audit logs show year context

---

## Next Steps

1. **Update UI** - Add exam year selector dropdown
2. **Implement authorization** - Restrict year locking to admins
3. **Add audit logging** - Track year changes
4. **Update CSV imports** - Include exam_year_id validation
5. **Update reports** - Filter by year
6. **Test with real data** - Run full test suite

---

## Support & Documentation

- Architecture: `RMS_EXAM_YEARS_ARCHITECTURE.md`
- SQL Script: `RMS_EXAM_YEARS_MIGRATION_SQL.sql`
- Python Migration: `rms_exam_years_migration.py`
- Log File: `migration.log` (generated after execution)

