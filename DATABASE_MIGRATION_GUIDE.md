# Database Migration Guide - Subject Fields Update

## Overview
A new migration has been created to add the new subject fields to the database.

## Files Modified

### 1. Migration File
**File**: `/database/migrations/2026_01_29_150000_add_subject_fields_to_subjects_table.php`

**Status**: ✅ Created

**Changes**:
- `category` (enum) - Values: ARTS, SCIENCE, BUSINESS (default: SCIENCE)
- `written_papers` (integer) - Number of papers: 1, 2, or 3 (default: 1)
- `has_practical` (boolean) - Whether subject has practical component (default: false)
- `has_project` (boolean) - Whether subject has project component (default: false)

### 2. Model File
**File**: `/app/Models/Subject.php`

**Status**: ✅ Updated

**Changes**:
```php
protected $fillable = [
    'code',
    'name',
    'category',              // NEW
    'written_papers',        // NEW
    'has_practical',         // NEW
    'has_project',           // NEW
    'exam_type_id',
    'max_marks',
    'description',
    'is_active',
];

protected $casts = [
    'is_active' => 'boolean',
    'has_practical' => 'boolean',  // NEW
    'has_project' => 'boolean',    // NEW
    'written_papers' => 'integer', // NEW
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
];
```

### 3. Controller File
**File**: `/app/Http/Controllers/ExamTypeController.php`

**Status**: ✅ Updated

**Methods Updated**:
1. `createSubject()` - Added validation for new fields
2. `updateSubject()` - Added validation for new fields

**Validation Rules Added**:
```php
'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
'written_papers' => 'required|integer|in:1,2,3',
'has_practical' => 'boolean',
'has_project' => 'boolean',
```

---

## Database Schema

### Before
```sql
CREATE TABLE subjects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    exam_type_id BIGINT NOT NULL,
    code VARCHAR(30),
    name VARCHAR(100),
    description TEXT NULL,
    max_marks INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    timestamps,
    UNIQUE (exam_type_id, code),
    INDEX (is_active)
);
```

### After
```sql
CREATE TABLE subjects (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    exam_type_id BIGINT NOT NULL,
    code VARCHAR(30),
    name VARCHAR(100),
    category ENUM('ARTS', 'SCIENCE', 'BUSINESS') DEFAULT 'SCIENCE',  -- NEW
    written_papers INT DEFAULT 1,                                      -- NEW
    has_practical BOOLEAN DEFAULT FALSE,                               -- NEW
    has_project BOOLEAN DEFAULT FALSE,                                 -- NEW
    description TEXT NULL,
    max_marks INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    timestamps,
    UNIQUE (exam_type_id, code),
    INDEX (is_active)
);
```

---

## How to Run the Migration

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Verify Migration
```bash
php artisan migrate:status
```

You should see:
```
2026_01_29_150000_add_subject_fields_to_subjects_table  Yes
```

### Step 3: Check Database
```bash
php artisan tinker

# Check the subjects table structure
DB::statement("DESCRIBE subjects");
```

---

## API Endpoint Examples

### Create Subject with New Fields
```bash
POST /api/exam-types/ACSEE/subjects

{
    "code": "MATH001",
    "name": "Mathematics",
    "category": "SCIENCE",
    "written_papers": 2,
    "has_practical": true,
    "has_project": false,
    "max_marks": 100,
    "description": "Advanced mathematics course"
}
```

### Update Subject with New Fields
```bash
PUT /api/exam-types/ACSEE/subjects/{id}

{
    "code": "MATH001",
    "name": "Mathematics",
    "category": "SCIENCE",
    "written_papers": 3,
    "has_practical": true,
    "has_project": true,
    "max_marks": 100,
    "description": "Updated mathematics course"
}
```

---

## Rollback Instructions

If you need to rollback this migration:

```bash
php artisan migrate:rollback
```

This will:
- Remove the `category` column
- Remove the `written_papers` column
- Remove the `has_practical` column
- Remove the `has_project` column

---

## Data Persistence

### What Happens to Existing Data?
When the migration runs:
- New rows get default values
- Existing rows keep their old data
- New columns are added with defaults

### Default Values
```
category: 'SCIENCE'
written_papers: 1
has_practical: false
has_project: false
```

---

## Frontend-Backend Integration

### Form to Database Flow
```
User fills Subject Modal Form
    ↓
Send POST/PUT request with form data
    ↓
Controller validates input
    ↓
Controller creates/updates Subject model
    ↓
Model uses fillable array to assign attributes
    ↓
Database stores new values
    ↓
Casts convert data types (boolean, integer)
    ↓
Response returned to frontend
```

### Example Flow
```
Form Input:
{
    code: "MATH001",
    name: "Mathematics",
    category: "SCIENCE",
    writtenPapers: "2",
    hasPractical: true,
    hasProject: false,
    description: "..."
}
    ↓
Controller converts writtenPapers string to integer (2)
    ↓
Database stores:
{
    category: "SCIENCE",
    written_papers: 2,
    has_practical: 1,  (boolean stored as 1/0)
    has_project: 0
}
    ↓
Model casts convert back to proper types when retrieved
```

---

## Testing the Changes

### Unit Test Example
```php
public function test_create_subject_with_new_fields()
{
    $response = $this->post('/api/exam-types/ACSEE/subjects', [
        'code' => 'TEST001',
        'name' => 'Test Subject',
        'category' => 'SCIENCE',
        'written_papers' => 2,
        'has_practical' => true,
        'has_project' => false,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('subjects', [
        'code' => 'TEST001',
        'category' => 'SCIENCE',
        'written_papers' => 2,
    ]);
}
```

---

## Field Descriptions

### category
- **Type**: ENUM
- **Allowed Values**: ARTS, SCIENCE, BUSINESS
- **Default**: SCIENCE
- **Required**: Yes
- **Description**: Subject category/stream

### written_papers
- **Type**: INTEGER
- **Allowed Values**: 1, 2, 3
- **Default**: 1
- **Required**: Yes
- **Description**: Number of written examination papers

### has_practical
- **Type**: BOOLEAN
- **Values**: true/false
- **Default**: false
- **Required**: No
- **Description**: Whether subject includes practical component

### has_project
- **Type**: BOOLEAN
- **Values**: true/false
- **Default**: false
- **Required**: No
- **Description**: Whether subject includes project component

---

## Validation Rules

### In Controller
```php
'category' => 'required|in:ARTS,SCIENCE,BUSINESS',
'written_papers' => 'required|integer|in:1,2,3',
'has_practical' => 'boolean',
'has_project' => 'boolean',
```

### In Database
```sql
ENUM constraint on category column
CHECK (written_papers IN (1, 2, 3))
```

---

## Migration Status Checklist

- [x] Migration file created
- [x] Model updated with fillable attributes
- [x] Model updated with casts
- [x] Controller validation updated
- [x] createSubject method updated
- [x] updateSubject method updated
- [ ] Run migration (`php artisan migrate`)
- [ ] Test in development
- [ ] Deploy to production
- [ ] Monitor for errors

---

## Support

If you encounter issues:

1. **Validation Error**: Check that required fields are provided
2. **Migration Error**: Verify table exists, run `php artisan migrate:refresh` if needed
3. **Casts Error**: Ensure Model casts are properly defined
4. **API Error**: Check controller validation and error messages

---

**Migration Date**: January 29, 2026
**Status**: READY TO RUN
**Next Step**: Execute `php artisan migrate`
