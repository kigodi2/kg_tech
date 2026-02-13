# Import Button - Step-by-Step Debugging

Since the button is still not responding, let's systematically verify each part of the workflow.

## Step 1: Verify the Import Candidates Modal Opens

1. Open the Candidates Management page
2. Click the **Tools** button (top right, blue button)
3. Click **"Import CSV"** from the dropdown

**Check**:
- ✓ Does the "Import Candidates" modal open?
- ✓ Does it show "Import Candidates" as title?
- ✓ Does it have an "Exam Year" dropdown?
- ✓ Does it have a "Select File" button (should be greyed out initially)?

**If NO**: The import flow itself isn't working. Skip to Debugging Section A.
**If YES**: Continue to Step 2.

---

## Step 2: Select Exam Year and Open File Picker

1. In the Import Candidates modal, click the "Exam Year" dropdown
2. Select any exam year (e.g., 2026)
3. Click the "Select File" button

**Check**:
- ✓ Does file picker dialog open?
- ✓ Can you navigate and select files?

**If NO**: The file picker isn't opening. Skip to Debugging Section B.
**If YES**: Continue to Step 3.

---

## Step 3: Select CSV File and Wait for Conflict Modal

1. Prepare a CSV file with candidates that **already exist** in the system
2. Select the CSV file in the file picker
3. Wait 2-3 seconds for the system to check conflicts

**Check**:
- ✓ Does "Import Conflicts Detected" modal open?
- ✓ Does it show "X candidate(s) already exist in the system"?
- ✓ Does it list conflicting candidate IDs?
- ✓ Does it show three import mode options?
- ✓ Are "Cancel" and "Import" buttons visible?

**If NO MODAL**: The conflict modal isn't showing. Skip to Debugging Section C.
**If MODAL SHOWS BUT NO BUTTONS**: The buttons aren't rendering. Skip to Debugging Section D.
**If YES**: Continue to Step 4.

---

## Step 4: Test Import Button Click

1. In the "Import Conflicts Detected" modal:
2. Select an import mode (e.g., "Skip Existing Records")
3. **Open browser console** (F12, go to Console tab)
4. Click the **"Import" button**

**Check Console**:
- ✓ Does a message appear: `"performImport called with: {...}"`?
- ✓ Are there any red errors?

**If YES (message appears)**: Function is being called! Skip to Debugging Section E (the import might actually be working, just check network).
**If NO message**: Button click isn't firing. Skip to Debugging Section F.

---

# Debugging Sections

## Debugging Section A: Import Modal Not Opening

**Run in browser console**:
```javascript
// Check if Alpine component exists
const comp = document.querySelector('[x-data="candidatesManager()"]');
console.log('Alpine component found:', !!comp);

// Check if state variables exist
if (comp && comp.__x) {
    console.log('showImportModal state:', comp.__x.$data.showImportModal);
    console.log('examYears loaded:', comp.__x.$data.examYears.length);
}

// Check if clicking Tools changes state
// (manually click Tools button while console is open)
setTimeout(() => {
    if (comp && comp.__x) {
        console.log('showToolsMenu after click:', comp.__x.$data.showToolsMenu);
    }
}, 500);
```

**If component doesn't exist**: 
- Alpine.js might not be loaded
- x-data might not be initialized
- Check if errors in browser console

**If state isn't updating**:
- Event handlers might not be bound
- Check line 141: Import CSV button should have `@click="showToolsMenu = false; showImportModal = true"`

---

## Debugging Section B: File Picker Not Opening

**Run in browser console**:
```javascript
// Check if Select File button exists
const btn = document.querySelector('button:contains("Select File")');
console.log('Select File button exists:', !!btn);

// Try clicking it manually
if (btn) {
    console.log('Attempting manual click...');
    btn.click();
    console.log('Click executed');
}

// Check if importInput element exists
const fileInput = document.getElementById('importInput');
console.log('File input exists:', !!fileInput);
console.log('File input visible:', fileInput && window.getComputedStyle(fileInput).display);
```

**If button doesn't exist**:
- Modal might not be rendering properly
- Check line 1508: Button should trigger file picker click

**If file input doesn't exist**:
- Line 148 has the hidden file input
- Verify it's in the DOM

---

## Debugging Section C: Conflict Modal Not Showing

**Run in browser console**:
```javascript
// Open DevTools Network tab, then do the import again
// You should see a POST to /api/candidates/import/check

// After selecting file, run:
const comp = document.querySelector('[x-data="candidatesManager()"]');
if (comp && comp.__x) {
    const data = comp.__x.$data;
    console.log('showImportConflictModal:', data.showImportConflictModal);
    console.log('importConflicts length:', data.importConflicts.length);
    console.log('importFile:', data.importFile);
}

// Check the actual modal div
const modal = document.querySelector('[x-show="showImportConflictModal"]');
console.log('Modal exists:', !!modal);
console.log('Modal display:', window.getComputedStyle(modal).display);
```

**If showImportConflictModal is false**:
- checkConflicts() function didn't set it to true
- Check the API response in Network tab

**If importConflicts is empty**:
- API returned no conflicts
- Check if your test CSV has duplicate candidates

---

## Debugging Section D: Modal Shows But No Buttons

**Run in browser console**:
```javascript
// Check if footer buttons div exists
const footer = document.querySelector('.border-t.border-gray-200.p-6.flex.gap-3');
console.log('Footer div exists:', !!footer);
console.log('Footer display:', footer && window.getComputedStyle(footer).display);
console.log('Footer z-index:', footer && window.getComputedStyle(footer).zIndex);

// Check if buttons exist
const buttons = footer ? footer.querySelectorAll('button') : [];
console.log('Buttons found:', buttons.length);
buttons.forEach((btn, idx) => {
    console.log(`Button ${idx}:`, btn.textContent.trim(), '- display:', window.getComputedStyle(btn).display);
});
```

**If footer doesn't exist**:
- Line 1611 might have structural issue
- Check if scrollable content (line 1541) is properly closed

**If buttons don't exist**:
- Check lines 1612-1625: Button HTML

---

## Debugging Section E: Function Called But Nothing Happens

**Check Network tab in DevTools**:

1. Open DevTools → Network tab
2. Click Import button
3. Look for POST request to `/api/candidates/import`
4. Check the response:
   - Status should be 200 or 422
   - Response body should have: `{ count: X, message: "..." }`

**If request doesn't show**:
- performImport() executed but fetch failed
- Check console for errors

**If request shows but fails (422/500)**:
- Server returned error
- Check Laravel logs: `storage/logs/laravel.log`
- Check if file data is being sent properly

---

## Debugging Section F: Button Click Not Firing

**Run in browser console**:
```javascript
// Find the Import button in conflict modal
const allButtons = document.querySelectorAll('button');
let importBtn = null;

for (let btn of allButtons) {
    if (btn.textContent.includes('Import')) {
        const parent = btn.closest('[x-show="showImportConflictModal"]');
        if (parent) {
            importBtn = btn;
            break;
        }
    }
}

console.log('Import button found:', !!importBtn);
if (importBtn) {
    console.log('Button HTML:', importBtn.outerHTML.substring(0, 200));
    console.log('Button visible:', window.getComputedStyle(importBtn).display !== 'none');
    console.log('Button pointer-events:', window.getComputedStyle(importBtn).pointerEvents);
    console.log('Button disabled:', importBtn.disabled);
    
    // Try clicking it
    console.log('Attempting click...');
    importBtn.click();
    
    // Check if performImport was called
    setTimeout(() => {
        console.log('Check console above for performImport message');
    }, 500);
}
```

**If button is hidden**: 
- Check CSS: `display: none` or `visibility: hidden`
- Verify parent container has proper display

**If button has pointer-events: none**:
- Check CSS classes on button and parent
- Line 1611 should have `pointer-events-auto`

**If click doesn't work manually**:
- Try right-clicking and "Inspect" the button
- Look at its `@click` attribute
- Should show: `@click.prevent.stop="performImport(importFile, importMode)"`

---

## Complete Debug Output

Run all of these in console and share the output:

```javascript
// 1. Check Alpine
const comp = document.querySelector('[x-data="candidatesManager()"]');
console.log('=== ALPINE ===');
console.log('Component exists:', !!comp);
if (comp && comp.__x) {
    console.log('performImport function exists:', !!comp.__x.$data.performImport);
    console.log('showImportConflictModal:', comp.__x.$data.showImportConflictModal);
}

// 2. Check Modal
const modal = document.querySelector('[x-show="showImportConflictModal"]');
console.log('\n=== MODAL ===');
console.log('Modal exists:', !!modal);
if (modal) {
    const style = window.getComputedStyle(modal);
    console.log('Display:', style.display);
    console.log('Visibility:', style.visibility);
    console.log('Z-index:', style.zIndex);
}

// 3. Check Button
const buttons = document.querySelectorAll('button');
let importBtn = null;
for (let btn of buttons) {
    if (btn.textContent.includes('Import') && btn.textContent.length < 30) {
        importBtn = btn;
        break;
    }
}
console.log('\n=== BUTTON ===');
console.log('Button exists:', !!importBtn);
if (importBtn) {
    const style = window.getComputedStyle(importBtn);
    console.log('Display:', style.display);
    console.log('Visibility:', style.visibility);
    console.log('Pointer-events:', style.pointerEvents);
    console.log('Z-index:', style.zIndex);
    console.log('Disabled:', importBtn.disabled);
    console.log('Parent pointer-events:', window.getComputedStyle(importBtn.parentElement).pointerEvents);
}
```

---

## What To Provide When Asking For Help

Include:
1. Which step fails (1-4)?
2. Full console output from debug sections
3. Browser type and version
4. Screenshot of the modal (if visible)
5. Network tab showing API requests
6. Laravel log entries (if relevant)

This will help identify exactly where the issue is.
