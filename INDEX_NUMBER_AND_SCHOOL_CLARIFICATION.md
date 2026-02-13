# Index Number Format & School Assignment Clarification

## Your CSV Format Structure

Your Index Number format is **hierarchical**:

```
Index Number: S0108-0801
└─ School Code: S0108 (first 5 characters)
└─ Candidate Number: 0801 (last 4 characters)
```

This means each school's candidates are numbered sequentially within that school.

---

## What Your Data Shows

### All 363 Candidates from School S0108

```
S0108-0801 → School S0108, Candidate #0801
S0108-0802 → School S0108, Candidate #0802
S0108-0803 → School S0108, Candidate #0803
...
S0108-0863 → School S0108, Candidate #0863
```

### Why All Show "School S0108"?

Because **your CSV contains candidates only from school S0108**.

---

## Index Number vs School ID Column

Your CSV has TWO ways to identify the school:

```
CSV Columns:
Col 0: Index Number    → S0108-0801 (encoded school code)
Col 4: School ID       → S0108 (explicit school code)
```

**Both contain the same information**:
- Index Number: Encodes school code + candidate number
- School ID Column: Explicit school code for direct lookup

The system uses **School ID column** (Column 4) to link candidates to schools.

---

## Example: Multiple Schools

If your CSV had candidates from **different schools**, it would look like:

```
Index Number  | Full Name           | Sex | Combination | School ID | Exam Type
──────────────┼─────────────────────┼─────┼─────────────┼───────────┼──────────
S0108-0801    | AISHA FELIX...      | F   | CBG         | S0108     | ACSEE
S0108-0802    | ANTHONIA BLANCE...  | F   | CBG         | S0108     | ACSEE
S0109-0501    | JOHN SMITH...       | M   | HGL         | S0109     | ACSEE
S0109-0502    | JANE DOE...         | F   | HGL         | S0109     | ACSEE
```

Then the SCHOOL column would show:
- S0108-0801 → School S0108
- S0108-0802 → School S0108
- **S0109-0501 → School S0109** (different!)
- **S0109-0502 → School S0109** (different!)

---

## Current Status

✅ **System Working Correctly**
- 363 candidates imported from school S0108
- All linked to correct school
- Index numbers preserve school information
- School ID column correctly maps to database school

---

## If You Have Multiple Schools

If your actual CSV file has candidates from multiple schools (S0108, S0109, S0110, etc.):

1. **All schools must exist in database** first
   ```bash
   # Create other schools
   php artisan tinker
   App\Models\School::create([
       'registration_number' => 'S0109',
       'code' => 'S0109',
       'name' => 'School S0109',
       // ... other fields
   ]);
   ```

2. **Then re-import CSV**
   - System will detect each candidate's school code (Column 4)
   - Assign to correct school automatically

3. **Result**: Candidates distributed across multiple schools

---

## Database Verification

Check how many candidates per school:

```bash
php artisan tinker

# Count by school ID
App\Models\Candidate::groupBy('school_id')
    ->selectRaw('school_id, COUNT(*) as count')
    ->get()
    ->each(function($c) {
        $school = App\Models\School::find($c->school_id);
        echo $school->registration_number . ' → ' . $c->count . " candidates\n";
    });
```

---

## Summary

| Item | Your Data |
|------|-----------|
| **Total Candidates** | 363 |
| **Schools Represented** | 1 (S0108) |
| **Index Number Pattern** | S0108-0801 to S0108-0863 |
| **School Assignment** | All to School S0108 |
| **System Status** | ✅ Working correctly |

All candidates are correctly assigned to school S0108 because that's the only school in your CSV data.

If you have candidates from multiple schools, upload a CSV with entries from S0109, S0110, etc., and the system will distribute them accordingly.

