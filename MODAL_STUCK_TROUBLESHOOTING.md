# Modal Stuck - Emergency Troubleshooting Guide

## If Modal Is Stuck and Nothing Responds

Follow these steps in order:

### STEP 1: Hard Clear Browser Cache (CRITICAL)

This is the most common cause of modals appearing stuck.

**Chrome/Edge/Brave:**
```
1. Press: Ctrl + Shift + Delete (Windows) or Cmd + Shift + Delete (Mac)
2. Time Range: Select "All time"
3. Check these boxes:
   ☑ Cookies and other site data
   ☑ Cached images and files
4. Click: Clear data
```

**Firefox:**
```
1. Press: Ctrl + Shift + Delete (Windows) or Cmd + Shift + Delete (Mac)
2. Time Range: Select "Everything"
3. Check:
   ☑ Cookies
   ☑ Cache
4. Click: Clear Now
```

**Safari:**
```
1. Menu: Safari → Settings (or Preferences)
2. Tab: Privacy
3. Click: "Manage Website Data"
4. Click: "Remove All"
5. Confirm when prompted
```

### STEP 2: Close All Tabs with This Site

Close ALL tabs/windows that have the candidates page open. This ensures Alpine.js starts fresh.

### STEP 3: Hard Refresh Page

After closing tabs, open a NEW tab and go to candidates page:

**Windows/Linux:**
```
Ctrl + F5  (Hard refresh, clears cache for this tab)
```

**Mac:**
```
Cmd + Shift + R  (Hard refresh)
```

OR

```
Cmd + Option + Delete (Clear cache)
then Cmd + R (Refresh)
```

### STEP 4: Wait for Page to Load Completely

- Wait for all loading indicators to disappear
- Wait 3-5 seconds after page appears
- Don't click anything until page is fully loaded

### STEP 5: Test the Modal

1. Click: **Tools** button (blue, with wrench icon)
2. Click: **Import CSV**
3. Check if modal appears
4. Try clicking buttons

### STEP 6: If Still Stuck - Check Browser Console

**Open console:**
```
Press: F12
Go to: Console tab
```

**Look for:**
- Red error messages
- JavaScript warnings
- Failed network requests

**Copy any red errors and report them**

---

## Common Issues & Solutions

### Issue 1: Modal Appears But Buttons Don't Work

**Solution:**
- Make sure all cache is cleared (STEP 1)
- Check browser console for errors (STEP 6)
- Try different browser to test

### Issue 2: Page Doesn't Load

**Solution:**
- Check Network tab (F12 → Network)
- Look for failed requests (red lines)
- Refresh page

### Issue 3: Nothing Happens When I Click Tools

**Solution:**
- Page may not be fully loaded yet
- Wait 5 seconds and try again
- Hard refresh (Ctrl+F5)
- Check console for errors

---

## Nuclear Option: Complete Browser Reset

If all else fails:

### Chrome/Edge:
```
1. Settings → Privacy and security
2. Clear browsing data → All time
3. Check ALL boxes
4. Click Clear data
5. Close browser completely
6. Reopen browser
7. Open candidates page
```

### Firefox:
```
1. Menu (≡) → Settings → Privacy & Security
2. Cookies and Site Data → Clear Data
3. Check all boxes
4. Click Clear
5. Close Firefox completely
6. Reopen Firefox
7. Open candidates page
```

### Safari:
```
1. Preferences → Privacy
2. Remove All Website Data
3. Preferences → Advanced
4. Check "Show full website address"
5. Close Safari
6. Reopen Safari
7. Open candidates page
```

---

## Still Stuck? Diagnostic Steps

### Test 1: Check if Alpine.js Loads

Open browser console (F12) and type:
```javascript
console.log(Alpine)
```

**Expected:** Should print an Alpine object  
**If undefined:** Alpine.js not loading - contact admin

### Test 2: Check if Component Initializes

In console, type:
```javascript
const comp = document.querySelector('[x-data="candidatesManager()"]');
console.log('Component:', comp ? 'Found' : 'NOT FOUND');
```

**Expected:** Should print "Component: Found"  
**If NOT FOUND:** Page structure issue - contact admin

### Test 3: Check for JavaScript Errors

In console, type:
```javascript
console.log('Errors:', window.errorLog || 'None logged');
```

**Look for any red text in console** - these are actual errors

### Test 4: Test Event Manually

In console, type:
```javascript
// Try to manually set modal state
const comp = document.querySelector('[x-data="candidatesManager()"]');
if (comp && comp.__x) {
    const alpineData = Alpine.raw(comp.__x);
    alpineData.showImportConflictModal = true;
    console.log('Modal state set, check if modal appears');
}
```

---

## Information to Report If Issue Persists

If none of the above works, gather this information:

1. **Browser & Version:**
   ```
   [e.g., Chrome 120.0.1234.567]
   ```

2. **Operating System:**
   ```
   [e.g., Windows 11, Mac OS 14.2, Ubuntu 22.04]
   ```

3. **Steps You Took:**
   ```
   [List all troubleshooting steps you completed]
   ```

4. **Console Errors:**
   ```javascript
   [Copy any red error messages from F12 → Console]
   ```

5. **Network Issues:**
   ```
   [Check F12 → Network for failed requests]
   ```

6. **Screenshot:**
   ```
   [Take screenshot of the stuck modal]
   ```

---

## What Should Work Now

After clearing cache and hard refresh:

✅ Clicking Tools button opens dropdown  
✅ Clicking Import CSV opens modal  
✅ Clicking Cancel closes modal  
✅ Clicking Import processes file  
✅ Clicking X closes modal  
✅ Clicking outside modal closes it  

If any of these don't work after clearing cache, there's a deeper issue that needs admin investigation.

---

## Code Changes Made

**File:** `resources/views/registration/candidates.blade.php`

**Changes:**
1. Removed `console.log()` statements from button handlers
2. Restructured modal layout with flexbox
3. Made buttons always visible (not scrolled away)
4. Added proper type="button" attributes

**All changes are clean and validated.**

---

**Last Resort:** If absolutely nothing works, try:
1. Different browser entirely (Chrome, Firefox, Safari, Edge)
2. Incognito/Private browsing mode
3. Different device/computer

This will help identify if it's a browser issue vs server issue.

