# Add TOTAL FAILED CANDIDATES Row - 2026-02-09

## Change Request
Add a new row "TOTAL FAILED CANDIDATES" in the EXAMINATION CENTRE OVERALL PERFORMANCE section, right below "TOTAL PASSED CANDIDATES".

## Definition
- **TOTAL PASSED CANDIDATES**: All candidates with Division I, II, III, or IV (3-19 points)
- **TOTAL FAILED CANDIDATES**: All candidates with Division 0 (0 points or 20+ points)

## Changes Made

### File: resources/views/hierarchy/school-results.blade.php

#### Added New Row (Line 470-472)

**Location:** After "TOTAL PASSED CANDIDATES" row, before "EXAMINATION CENTRE GPA" row

**Code Added:**
```blade
<tr>
    <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold;" colspan="10">TOTAL FAILED CANDIDATES</td>
    <td style="border: 1px solid #999; padding: 0.25rem; font-size: 0.75rem; font-weight: bold; text-align: left;">{{ $totalDivisions['0'] }}</td>
</tr>
```

## Data Source

- **Value:** `$totalDivisions['0']`
- **Description:** Count of candidates with Division 0 (failed)
- **Calculation:** Already calculated in early metrics section
- **Consistency:** Uses same division calculation as Division Performance Summary

## Display Format

| Row | Label | Value |
|-----|-------|-------|
| ... | TOTAL REGISTERED CANDIDATES | 84 |
| ... | TOTAL PASSED CANDIDATES | 17 |
| **NEW** | **TOTAL FAILED CANDIDATES** | **{{ $totalDivisions['0'] }}** |
| ... | EXAMINATION CENTRE GPA | 1.6924 (AVERAGE) |

## Expected Result

For a school with:
- Total candidates: 84
- Division I: 0
- Division II: 8
- Division III: 14
- Division IV: 0
- Division 0: X (failed)

Display:
```
TOTAL REGISTERED CANDIDATES: 84
TOTAL PASSED CANDIDATES: 22 (I+II+III+IV)
TOTAL FAILED CANDIDATES: X (Division 0)
EXAMINATION CENTRE GPA: ...
```

## Notes

- Value automatically calculated from `$totalDivisions['0']`
- Consistent with Division Performance Summary table
- No database changes required
- No calculations added (uses pre-calculated value)
- Formatting matches other rows in the section

## Deployment

✓ File updated: `resources/views/hierarchy/school-results.blade.php`
✓ No database changes needed
✓ No controller changes needed
✓ No cache clearing required
✓ Backward compatible with existing data

---

**Status:** ADDED - TOTAL FAILED CANDIDATES row now displays
**Completed:** 2026-02-09
