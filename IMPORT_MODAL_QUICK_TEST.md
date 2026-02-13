# Import Modal - Quick Test

## Steps to Test the Fix

### Prerequisites
- Browser with Developer Tools support (F12)
- IRMS admin or registration user logged in

### Test Procedure

1. **Navigate to Candidates Page**
   - Go to: Registration → Candidates
   - Wait for page to fully load (loading indicator disappears)

2. **Locate Tools Button**
   - Look for blue "Tools" button with wrench icon in the top-right of the Filters section
   - Note: It's next to the green "Register Candidate" button

3. **Click Tools Button**
   - Click the blue Tools button
   - Expected: Dropdown menu appears with 3 options:
     - CSV Template
     - Import CSV ← This is what we're testing
     - Export Excel

4. **Click Import CSV**
   - Click on "Import CSV" in the dropdown
   - Expected: Modal appears with:
     - Title: "Import Candidates"
     - Exam Year dropdown (required field marked with *)
     - Exam Type dropdown (optional)
     - Cancel button (gray)
     - Select File button (blue, initially disabled)

5. **Verify Exam Years Loaded**
   - Click on the Exam Year dropdown
   - Expected: List of years appears (e.g., 2023, 2024, 2025, etc.)
   - If empty: See "Debug Exam Years" section below

6. **Select Exam Year**
   - Choose any year from the dropdown
   - Expected: Select File button becomes enabled (turns from gray to blue)

7. **Click Select File**
   - Click the "Select File" button
   - Expected: File picker dialog appears

8. **Close Modal**
   - Click Cancel or the X button
   - Expected: Modal closes smoothly

---

## Debug Exam Years

If the Exam Year dropdown is empty:

1. Open Browser Developer Tools (F12)
2. Go to **Network** tab
3. Refresh the page (F5)
4. Look for a request to `/api/exam-years`
5. Check the response:
   - Status should be **200**
   - Response should contain: `{"exam_years": [...]}`

If no response or error:
- Contact admin - database may not have exam years configured
- See: Admin → System Settings → Exam Years

---

## Console Debugging

If the modal doesn't appear when clicking the button:

1. Open Browser Developer Tools (F12)
2. Go to **Console** tab
3. Click "Import CSV" button
4. Look for message: `Import button clicked`
5. If no message appears, JavaScript may be blocked

---

## Expected Behavior

| Action | Expected Result | Status |
|--------|-----------------|--------|
| Click Tools → Import CSV | Modal appears | ✓ |
| Exam Year dropdown | Shows list of years | ✓ |
| Select exam year | Select File button enables | ✓ |
| Click Select File | File picker appears | ✓ |
| Click Cancel | Modal closes | ✓ |
| Click X button | Modal closes | ✓ |
| Click outside modal | Modal closes | ✓ |

---

## If Tests Fail

### Modal doesn't appear:
- Clear browser cache: Ctrl+Shift+Delete (Windows) or Cmd+Shift+Delete (Mac)
- Refresh page: F5 or Cmd+R
- Check console for errors: F12 → Console tab

### Dropdown doesn't show years:
- Check Network tab for `/api/exam-years` response
- Verify database has exam_years records
- See IMPORT_MODAL_DEBUGGING_GUIDE.md for detailed steps

### Buttons don't respond:
- Check console for JavaScript errors
- Verify Alpine.js is loaded (type `Alpine` in console)
- Try refreshing the page

### File picker doesn't appear:
- Ensure browser allows file input access
- Check for browser file picker blocking/settings
- Try different browser if issue persists

---

## Success Indicators

✅ Modal appears and disappears smoothly  
✅ Exam years populate the dropdown  
✅ Select File button enables/disables correctly  
✅ File picker opens when clicking Select File  
✅ No console errors appear  

Once all tests pass, the import modal is working correctly!
