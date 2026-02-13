# Database Update Summary

## ✅ All Database Changes Complete

### Files Updated

#### 1. Migration File (NEW)
**File**: `/database/migrations/2026_01_29_150000_add_subject_fields_to_subjects_table.php`

Adds 4 new columns to subjects table:
- `category` (ENUM: ARTS, SCIENCE, BUSINESS)
- `written_papers` (INT: 1, 2, or 3)
- `has_practical` (BOOLEAN)
- `has_project` (BOOLEAN)

#### 2. Subject Model (UPDATED)
**File**: `/app/Models/Subject.php`

Added to `$fillable`:
```php
'category',
'written_papers',
'has_practical',
'has_project',
```

Added to `$casts`:
```php
'has_practical' => 'boolean',
'has_project' => 'boolean',
'written_papers' => 'integer',
```

#### 3. ExamTypeController (UPDATED)
**File**: `/app/Http/Controllers/ExamTypeController.php`

Updated 2 methods with validation:

**createSubject()**
- Added category validation
- Added written_papers validation
- Added has_practical boolean
- Added has_project boolean

**updateSubject()**
- Added category validation
- Added written_papers validation
- Added has_practical boolean
- Added has_project boolean

---

## Validation Rules

```php
'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
'written_papers' => 'required|integer|in:1,2,3',
'has_practical' => 'boolean',
'has_project' => 'boolean',
```

---

## How to Deploy

### Step 1: Run Migration
```bash
cd /home/prosmart-technologies/SOL/irms
php artisan migrate
```

### Step 2: Verify
```bash
php artisan migrate:status
# Should show: 2026_01_29_150000_add_subject_fields_to_subjects_table  Yes
```

### Step 3: Test
Create/Update a subject via the form to verify it works

---

## Database Schema Changes

### New Subjects Table Structure
```
Column              | Type      | Default    | Notes
--------------------|-----------|------------|-----
id                  | BIGINT    | AUTO INC   | Primary Key
exam_type_id        | BIGINT    | NOT NULL   | Foreign Key
code                | VARCHAR   | NOT NULL   | Unique per exam type
name                | VARCHAR   | NOT NULL   |
category            | ENUM      | SCIENCE    | NEW - ARTS/SCIENCE/BUSINESS
written_papers      | INTEGER   | 1          | NEW - 1/2/3
has_practical       | BOOLEAN   | FALSE      | NEW
has_project         | BOOLEAN   | FALSE      | NEW
description         | TEXT      | NULL       |
max_marks           | INTEGER   | 100        |
is_active           | BOOLEAN   | TRUE       |
created_at          | TIMESTAMP |            |
updated_at          | TIMESTAMP |            |
```

---

## Frontend-Backend Connection

### Frontend (Modal Form)
```javascript
subjectForm: {
    code: "MATH001",
    name: "Mathematics",
    category: "SCIENCE",
    writtenPapers: "2",
    hasPractical: true,
    hasProject: false,
    description: "..."
}
```

### Backend (Database)
```php
$subject = Subject::create([
    'code' => 'MATH001',
    'name' => 'Mathematics',
    'category' => 'SCIENCE',
    'written_papers' => 2,
    'has_practical' => true,
    'has_project' => false,
    'description' => '...'
]);
```

### Database Storage
```sql
INSERT INTO subjects (code, name, category, written_papers, has_practical, has_project, ...)
VALUES ('MATH001', 'Mathematics', 'SCIENCE', 2, 1, 0, ...);
```

---

## Key Points

✅ **Migration Created**: Ready to run
✅ **Model Updated**: Includes new fields in fillable and casts
✅ **Controller Updated**: Validation for all new fields
✅ **Types Preserved**: Booleans and integers properly handled
✅ **Defaults Set**: All new columns have sensible defaults
✅ **Backward Compatible**: Existing subjects not affected

---

## Next Steps

1. **Run Migration**
   ```bash
   php artisan migrate
   ```

2. **Test the Form**
   - Navigate to /exam-types/acsee
   - Click "Add Subject"
   - Fill in all fields
   - Submit and verify in database

3. **Test Update**
   - Click edit on existing subject
   - Modify fields
   - Save and verify

4. **Monitor**
   - Check Laravel logs for errors
   - Verify API responses include new fields

---

## Rollback (If Needed)

If something goes wrong:
```bash
php artisan migrate:rollback
# Removes all 4 new columns
```

Then fix and run migrate again.

---

## API Endpoints Ready

After migration, these endpoints are ready:

```
POST   /api/exam-types/{code}/subjects
PUT    /api/exam-types/{code}/subjects/{id}
DELETE /api/exam-types/{code}/subjects/{id}
GET    /api/exam-types/{code}/subjects
```

All now support the 4 new subject fields!

---

**Status**: ✅ DATABASE UPDATES COMPLETE
**Action Required**: Run `php artisan migrate`
