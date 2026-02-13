# Hierarchy System - Section 3 Update

**Date:** February 4, 2026  
**Modification:** Show only unique registered subjects in Section 3  
**Status:** ✅ COMPLETE

## What Changed

Modified the "Examination Centre Subjects Performance" (Section 3) on the results page to display only the unique subjects that candidates in that school are actually registered for through their combinations, rather than showing all subjects in the system.

## Before

- Section 3 displayed all 21 subjects in the system
- Many subjects had zero registrations
- Cluttered results page with irrelevant subjects

## After

- Section 3 displays only unique subjects registered by candidates
- Example: IRINGA GIRLS' SECONDARY SCHOOL shows 12 subjects instead of 21
- Cleaner, more relevant results display

## Implementation Details

**Modified File:** `app/Http/Controllers/HierarchyController.php`

**New Logic in `schoolResults()` method:**

1. Query `CandidateSubjectSelection` table for the school
2. Filter by ACSEE exam type
3. Get distinct `subject_id` values
4. Fetch only those subjects from Subject table
5. Load marks for those specific subjects
6. Calculate performance metrics

**Code Approach:**
```php
// Get unique subjects registered by candidates in this school
$registeredSubjectIds = \App\Models\CandidateSubjectSelection::whereHas('candidate', function ($q) use ($schoolId) {
    $q->where('school_id', $schoolId);
})
    ->when($acseeType, function ($q) use ($acseeType) {
        $q->where('exam_type_id', $acseeType->id);
    })
    ->distinct()
    ->pluck('subject_id')
    ->toArray();

// Get only those subjects with their performance stats
$subjects = \App\Models\Subject::whereIn('id', $registeredSubjectIds)
    ->with(['marks' => function ($query) use ($schoolId, $acseeType) {
        // ... marks eager loading
    }])
    ->get();
```

## Benefits

✅ **More Relevant Display** - Only shows subjects candidates are taking  
✅ **Cleaner Tables** - Removes "0 candidates" rows  
✅ **Better Performance** - Fewer subjects to process and display  
✅ **Accurate Analysis** - Focuses on actual combinations registered  
✅ **User Experience** - Less scrolling, more relevant data  

## Test Results

**Test School:** IRINGA GIRLS' SECONDARY SCHOOL (ID: 19)

| Metric | Value |
|--------|-------|
| Total subjects in system | 21 |
| Subjects registered in school | 12 |
| Subjects NOT registered | 9 |
| Reduction in Section 3 display | 43% |

**Subjects Now Displayed in Section 3:**
1. GENERAL STUDIES (111)
2. HISTORY (112)
3. GEOGRAPHY (113)
4. KISWAHILI (121)
5. ENGLISH LANGUAGE (122)
6. PHYSICS (131)
7. CHEMISTRY (132)
8. BIOLOGY (133)
9. BASIC APPLIED MATHEMATICS (141)
10. ADVANCED MATHEMATICS (142)
11. ECONOMICS (151)
12. FOOD AND HUMAN NUTRITION (155)

**Subjects NOT Displayed:**
- AGRICULTURE
- COMMERCE
- CIVICS/SOCIAL STUDIES
- HOME ECONOMICS
- COMPUTER STUDIES
- MUSIC
- FINE ART
- LITERATURE IN ENGLISH
- SWAHILI

## How It Works

1. User navigates to a school via hierarchy: Region → District → School
2. System identifies candidates in that school
3. Queries their subject selections/combinations
4. Extracts unique subject IDs
5. Displays only those subjects in Section 3
6. Calculates performance metrics for registered subjects only

## Compatibility

- ✅ Works with existing candidate data
- ✅ No database migrations needed
- ✅ Uses existing CandidateSubjectSelection table
- ✅ Respects ACSEE exam type filtering
- ✅ Maintains all other functionality

## Testing Checklist

✅ Verified SQL queries correct  
✅ Tested with sample school (19 candidates)  
✅ Confirmed subject count (12 unique subjects)  
✅ Validated syntax (no PHP errors)  
✅ Tested controller loading successfully  

## Future Enhancements

If needed, similar filtering could be applied to:
- Section 2 candidates (show only those registered for relevant subjects)
- Performance metrics (aggregate only relevant subject data)
- PDF exports (reduce output size with relevant subjects only)

## Rollback

If needed to revert, change the subjects query from:
```php
$subjects = \App\Models\Subject::whereIn('id', $registeredSubjectIds)...
```

Back to:
```php
$subjects = \App\Models\Subject::all();
```

---

**Implementation Status:** ✅ COMPLETE  
**Verification:** ✅ PASSED  
**Ready for Production:** ✅ YES
