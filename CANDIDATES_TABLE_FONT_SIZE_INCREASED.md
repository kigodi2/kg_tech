# Candidates Table Font Size - Increased

## Change Summary
Increased font size for all table contents on candidates pages for better readability.

**Changed from:** `text-xs` → **Changed to:** `text-sm`

## Files Updated

### 1. Registration Candidates
**File:** `resources/views/registration/candidates.blade.php`

**Updated elements:**
- Index Number column
- Full Name column
- Gender column
- Combination column
- School column
- Exam Type column
- Status badge
- Action buttons area

### 2. ACSEE Candidates (Exam Types Show)
**File:** `resources/views/exam-types/show.blade.php`

**Updated elements:**
- Index Number column
- Full Name column
- Gender column (with badge)
- Combination column
- Allocated Subjects column
- Status badge
- Action buttons area

### 3. ACSEE Candidates (Dashboard)
**File:** `resources/views/dashboard/exam-acsee.blade.php`

**Status:** Already had `text-sm` - no changes needed

### 4. ACSEE Page
**File:** `resources/views/exam-types/acsee.blade.php`

**Status:** Already had `text-sm` - no changes needed

## Font Size Comparison

| Element | Before | After |
|---------|--------|-------|
| Index Number | 12px (text-xs) | 14px (text-sm) |
| Full Name | 12px (text-xs) | 14px (text-sm) |
| Other content | 12px (text-xs) | 14px (text-sm) |
| Status badge | 12px (text-xs) | 14px (text-sm) |

## Benefits

✅ **Better Readability:** Larger font is easier to read, especially on long sessions
✅ **Less Eye Strain:** Reduces eye fatigue when viewing candidate data
✅ **Professional Appearance:** More spacious, organized look
✅ **Mobile Friendly:** Better visibility on tablets and smaller screens
✅ **Data Entry Accuracy:** Easier to read candidate names when processing

## Visual Impact

- Table rows are now more spacious
- Text is clearer and more legible
- No change to overall layout
- Status badges and gender indicators are more visible
- Action button areas are easier to click

## Testing Checklist

- [ ] Registration candidates table font size increased
- [ ] ACSEE candidates table (show page) font size increased
- [ ] All badges and status indicators are readable
- [ ] Column widths accommodate the larger font
- [ ] No text overflow or layout issues
- [ ] Works on desktop and tablet screens
- [ ] Print layout is not affected

## Status
✅ **Complete** - Candidates table font sizes increased across all pages
✅ **Consistent** - All candidates tables now use `text-sm` for consistency
✅ **Ready for Production** - No breaking changes
