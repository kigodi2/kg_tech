# Table Rows Hover Color - Updated to Light Blue

## Change Summary
Updated all table rows throughout the system to have light blue hover color.

**Final state:** `hover:bg-blue-100` (light blue with dark text preserved)

## Files Updated

### Registration Module
1. **resources/views/registration/candidates.blade.php**
   - Candidates table row hover

2. **resources/views/registration/schools.blade.php**
   - Schools table row hover

3. **resources/views/registration/districts.blade.php**
   - Districts table row hover

4. **resources/views/registration/regions.blade.php**
   - Regions table row hover

### Exam Types Module
5. **resources/views/exam-types/index.blade.php**
   - Exam types table row hover

6. **resources/views/exam-types/show.blade.php**
   - Subjects table row hover
   - Combinations table row hover
   - Candidates table row hover

7. **resources/views/exam-types/acsee.blade.php**
   - ACSEE subjects table row hover
   - ACSEE combinations table row hover
   - ACSEE candidates table row hover

### Dashboard Module
8. **resources/views/dashboard/exam-acsee.blade.php**
   - Dashboard ACSEE candidates table row hover

9. **resources/views/regions/dashboard.blade.php**
   - Regions dashboard table row hover

## Visual Changes

### Evolution
1. **Initial:** Light gray background (`bg-gray-50`)
2. **Dark update:** Dark/black background (`bg-gray-900`) with white text
3. **Medium blue:** Dark blue background (`bg-blue-600`) with white text
4. **Current (Light blue):** Subtle light blue background (`bg-blue-100`) with dark text

### Current State
- Table rows on hover: Light blue background (`bg-blue-100`)
- Text remains dark gray/black for excellent readability
- Subtle but visible hover feedback
- Smooth transition with `transition-colors`

## CSS Classes Applied
```css
hover:bg-blue-100 transition-colors
```

This creates a professional, subtle light blue hover effect that:
- ✅ Provides clear hover feedback
- ✅ Maintains text readability
- ✅ Differentiates from selected state (`bg-blue-50`)
- ✅ Professional appearance suitable for data-heavy tables

## Tables Affected
- ✅ Candidates tables (Registration & Exam Types)
- ✅ Schools table
- ✅ Districts table
- ✅ Regions table
- ✅ Subjects tables (Exam Types & ACSEE)
- ✅ Combinations tables (Exam Types & ACSEE)
- ✅ Dashboard tables

## Browser Compatibility
✅ Works on all modern browsers supporting Tailwind CSS

## Testing Checklist
- [ ] Hover over candidates table rows → dark background appears
- [ ] Hover over schools table rows → dark background appears
- [ ] Hover over districts table rows → dark background appears
- [ ] Hover over regions table rows → dark background appears
- [ ] Hover over exam types table rows → dark background appears
- [ ] Hover over subjects table rows → dark background appears
- [ ] Hover over combinations table rows → dark background appears
- [ ] Text is readable (white on dark background)
- [ ] Transition is smooth
- [ ] Works on both desktop and mobile

## Status
✅ **Complete** - All table rows updated with dark hover color
✅ **Consistent** - Same styling applied across all tables
✅ **Ready for Production** - No breaking changes
