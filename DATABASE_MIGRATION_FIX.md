# Database Migration Fix - Combinations Table

## Issue
When attempting to add a combination, the database returned error:
```
General error: 1 table combinations has no column named category
```

The `combinations` table was missing the `category` and `description` columns that were required by the new combination functionality.

## Root Cause
A migration file existed to add these columns (`2026_01_30_update_combinations_table.php`) but had not been executed on the database. Additionally, the migration had issues:
1. Attempted to create a unique constraint that already existed
2. SQLite doesn't support certain constraint operations

## Solution Applied

### 1. Updated Migration File
**File**: `database/migrations/2026_01_30_update_combinations_table.php`

**Changes**:
- Added conditional checks to verify columns don't already exist before adding them
- Removed duplicate unique constraint creation (already exists in original migration)
- Removed SQLite-incompatible index operations

**Before**:
```php
$table->string('category')->default('ARTS')...
$table->text('description')->nullable()...
$table->unique(['exam_type_id', 'code']); // Would fail if already exists
$table->index(['exam_type_id']);
$table->index(['category']);
```

**After**:
```php
if (!Schema::hasColumn('combinations', 'category')) {
    $table->string('category')->default('ARTS')...
}
if (!Schema::hasColumn('combinations', 'description')) {
    $table->text('description')->nullable()...
}
// No duplicate constraint operations
```

### 2. Updated Combination Model
**File**: `app/Models/Combination.php`

**Changes**:
- Added `subjects` field to the `$fillable` array (was missing)

### 3. Executed Migration
```bash
php artisan migrate
```

Successfully applied `2026_01_30_update_combinations_table` migration.

## Database Schema

### Combinations Table Structure
```
Column Name    | Type      | Properties
---------------|-----------|---------------------------
id             | integer   | Primary Key
exam_type_id   | integer   | Foreign Key -> exam_types
code           | string    | Max 50 chars
category       | string    | ARTS, SCIENCE, BUSINESS (NEW)
description    | text      | Optional description (NEW)
subjects       | text      | Comma-separated subject codes
is_active      | boolean   | Default: true
created_at     | timestamp |
updated_at     | timestamp |
```

## Validation Applied

**Verified columns exist**:
- ✓ id
- ✓ exam_type_id
- ✓ code
- ✓ subjects
- ✓ is_active
- ✓ created_at
- ✓ updated_at
- ✓ category (NEW)
- ✓ description (NEW)

## Files Modified

1. `database/migrations/2026_01_30_update_combinations_table.php`
   - Made column additions conditional
   - Removed duplicate constraint operations

2. `app/Models/Combination.php`
   - Added `subjects` to `$fillable` array

## Testing
After this fix:
- ✓ "Add Combination" button now works
- ✓ Combination creation succeeds
- ✓ All combination CRUD operations function properly
- ✓ Category field is properly saved
- ✓ Description field is properly saved

## Next Steps
Users can now:
1. Click "Add Combination" to open the modal
2. Fill in combination name, category, and select subjects
3. Save the combination successfully
4. Edit or delete combinations as needed
