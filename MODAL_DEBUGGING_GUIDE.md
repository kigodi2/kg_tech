# Modal System - Debugging Guide

## Latest Fix Applied

### Fixed Issue: Alpine.js Directive Error
**Changed**: `@init="init()"` → `x-init="init()"`

Alpine.js 3.x uses `x-init` (not `@init`) to run initialization code.

**File**: `/resources/views/exam-types/show.blade.php`
**Line**: 11
**Status**: ✅ FIXED

---

## Testing Instructions

### Step 1: Refresh Browser Cache
```
1. Open /exam-types/acsee in browser
2. Hard refresh (Ctrl+Shift+R on Windows, Cmd+Shift+R on Mac)
3. Wait for page to fully load
```

### Step 2: Check Browser Console
```
1. Open DevTools (F12)
2. Go to Console tab
3. Look for JavaScript errors (red text)
4. Note any warning messages
```

### Step 3: Test Modal Opening
```
1. Click "Add Subject" button
2. Modal should slide in from center
3. Form should be empty
4. Close button should work
```

### Step 4: Check Alpine.js State
```
In browser console, run:
console.log(Alpine)

Should show Alpine.js is loaded.
```

---

## Common Issues & Solutions

### Issue 1: Modal Not Appearing
**Symptoms**: Click button, nothing happens

**Solutions**:
1. Check browser console for errors (F12)
2. Verify Alpine.js is loaded: `Alpine` in console should return an object
3. Hard refresh page (Ctrl+Shift+R)
4. Check that `x-data="examTypeManager()"` is on the container
5. Verify `x-init="init()"` is present (not @init)

### Issue 2: Form Not Resetting
**Symptoms**: Open modal, close, reopen, old data still there

**Solutions**:
1. Make sure click handler sets form to empty object:
   ```javascript
   @click="showSubjectModal = true; subjectForm = { code: '', name: '', ... }"
   ```
2. Form should auto-reset when modal closes

### Issue 3: Z-index Problems
**Symptoms**: Modal behind page content

**Solutions**:
1. Verify `z-[9999]` is on modal container
2. Check that overlay has `z-[9999]` too
3. Ensure no other element has higher z-index

### Issue 4: Double Modal Display
**Symptoms**: Two modals showing at once (previous issue)

**Solutions**:
1. ✅ Already fixed: Removed duplicate modals
2. All modals now have `style="display: none;"`
3. Only one modal should show at a time

---

## Browser Console Debugging

### Check if Alpine.js is loaded
```javascript
typeof Alpine !== 'undefined'
// Should return: true
```

### Check if component is registered
```javascript
document.querySelector('[x-data]')
// Should return the main container element
```

### Manually test modal state
```javascript
// Get the Alpine component
const el = document.querySelector('[x-data="examTypeManager()"]');
const component = Alpine.getEvaluator(el);

// Check state
console.log(component.showSubjectModal);
// Should return: false

// Toggle it
component.showSubjectModal = true;
// Modal should appear
```

### Check if init() was called
```javascript
// Look for evidence in the DOM or add console.log to init()
// You should see network requests to load data if init() ran
```

---

## Step-by-Step Verification

### 1. Page Load Sequence
```
Browser loads HTML
    ↓
Tailwind CSS applies (styling visible)
    ↓
Font Awesome loads (icons visible)
    ↓
Alpine.js loads (with defer, so after page parse)
    ↓
Alpine parses x-data directive
    ↓
examTypeManager() function called
    ↓
Component state initialized
    ↓
x-init="init()" triggered
    ↓
init() function runs
    ↓
Data loads from API
    ↓
Page fully interactive
```

### 2. Click Event Sequence
```
User clicks "Add Subject" button
    ↓
@click handler fires
    ↓
Alpine processes:
    showSubjectModal = true
    editingSubjectId = null
    subjectForm = {...}
    ↓
x-show="showSubjectModal" evaluates to true
    ↓
Alpine toggles display style from "none" to "block"
    ↓
Modal fades in (x-transition)
    ↓
Modal visible on screen
```

---

## What to Check in Code

### Line 11: Component Initialization
```html
<div x-data="examTypeManager()" x-init="init()" class="flex gap-6">
```
✅ Should be `x-init` (not `@init`)

### Line 747: Subject Modal State
```javascript
showSubjectModal: false,
```
✅ Should be initialized to false

### Line 585-590: Subject Modal HTML
```html
<div 
    x-show="showSubjectModal" 
    class="fixed ... z-[9999] p-4"
    @click.self="showSubjectModal = false;"
    x-transition
    style="display: none;"
>
```
✅ Should have `x-show`, `style="display: none;"`, and `x-transition`

### Line 101: Add Subject Button
```html
<button @click="showSubjectModal = true; editingSubjectId = null; subjectForm = { ... };" class="...">
```
✅ Click handler should set state correctly

---

## Testing Checklist

### Visual Testing
- [ ] Page loads without errors
- [ ] Sidebar visible and functional
- [ ] Subject table displays
- [ ] No overlapping modals on load
- [ ] "Add Subject" button is visible

### Interaction Testing
- [ ] Click "Add Subject" → Modal appears
- [ ] Form fields are visible
- [ ] Close button works
- [ ] Click outside modal (overlay) → closes
- [ ] Click Cancel button → closes
- [ ] Click "Add Subject" again → Form is reset

### Advanced Testing
- [ ] Open Subject modal
- [ ] Without closing, switch tabs
- [ ] Modal should close when switching
- [ ] Open Candidate modal
- [ ] Different content from Subject modal
- [ ] No visual overlap

---

## If Modals Still Don't Work

### Option 1: Check Server Logs
```bash
# Laravel log
tail -f storage/logs/laravel.log

# Check for PHP errors
php -l resources/views/exam-types/show.blade.php
```

### Option 2: Verify HTML Structure
```bash
# In browser DevTools:
# 1. Right-click on page
# 2. Select "Inspect" or "Inspect Element"
# 3. Look for the modal divs (search for "x-show")
# 4. Verify they exist in DOM
```

### Option 3: Check Network Tab
```
1. DevTools → Network tab
2. Refresh page
3. Look for failed requests
4. Check API calls status codes
```

### Option 4: Enable Alpine.js DevTools
```
1. Install Alpine.js DevTools browser extension
2. Refresh page
3. Open extension
4. Inspect component state
5. Check if showSubjectModal, etc. are there
```

---

## Quick Fixes to Try

### Fix 1: Clear Browser Cache
```
1. Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)
2. Clear cache, cookies, site data
3. Refresh page
```

### Fix 2: Restart Laravel Development Server
```bash
# If using Laravel Artisan:
php artisan serve --port=8001

# Or restart your web server
```

### Fix 3: Clear Laravel Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Fix 4: Check CSS Display Property
```javascript
// In browser console:
document.querySelector('[x-show="showSubjectModal"]').style.display
// Should be "none" initially
```

---

## Expected Behavior After Fix

### Before User Interaction
```
showSubjectModal = false
display: none (CSS)
x-show="false"
Modal: HIDDEN ✓
```

### After "Add Subject" Click
```
showSubjectModal = true
display: block (Alpine applied)
x-show="true"
Modal: VISIBLE ✓
```

### After Close/Cancel
```
showSubjectModal = false
display: none (Alpine applied)
x-show="false"
Modal: HIDDEN ✓
```

---

## Documentation References

- Alpine.js x-init: https://alpinejs.dev/directives/init
- Alpine.js x-show: https://alpinejs.dev/directives/show
- Alpine.js x-transition: https://alpinejs.dev/directives/transition
- Alpine.js Event Listeners: https://alpinejs.dev/directives/on

---

## Success Criteria

✅ All checkboxes below should be true:
- [ ] Alpine.js loads without errors
- [ ] Component initializes successfully
- [ ] Modal state variables exist
- [ ] Click handlers execute
- [ ] Modals appear/disappear on command
- [ ] Forms reset properly
- [ ] No console JavaScript errors
- [ ] No visual overlapping
- [ ] All three modals work independently

---

**Last Updated**: January 29, 2026  
**Status**: Debugging guide for modal troubleshooting
