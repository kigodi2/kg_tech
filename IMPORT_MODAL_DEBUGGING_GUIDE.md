# Import Modal Debugging Guide

If the Import CSV modal is not responding, follow these steps:

## Step 1: Check Browser Console
1. Open the page in your browser
2. Press **F12** to open Developer Tools
3. Go to **Console** tab
4. Look for any error messages in red

## Step 2: Verify Alpine.js is Loaded
In the browser console, type:
```javascript
console.log(Alpine)
```
If you see an Alpine object, Alpine.js is loaded correctly.

## Step 3: Check Component State
In the browser console, type:
```javascript
// Find the component element
const component = document.querySelector('[x-data="candidatesManager()"]');
if (component) {
    console.log('Component found:', component);
    console.log('Alpine data:', Alpine.raw(component.__x));
}
```

## Step 4: Test Modal State Change Manually
In the browser console, type:
```javascript
const component = document.querySelector('[x-data="candidatesManager()"]');
const alpineData = Alpine.raw(component.__x);
console.log('Current showImportModal:', alpineData.showImportModal);

// Try to open the modal
alpineData.showImportModal = true;
console.log('After setting to true:', alpineData.showImportModal);
```

## Step 5: Verify Modal HTML Exists
In the browser console, type:
```javascript
const modal = document.querySelector('[x-show="showImportModal"]');
console.log('Modal element found:', modal);
console.log('Modal display:', window.getComputedStyle(modal).display);
```

## Step 6: Check Exam Years are Loaded
In the browser console, type:
```javascript
const component = document.querySelector('[x-data="candidatesManager()"]');
const alpineData = Alpine.raw(component.__x);
console.log('Exam years:', alpineData.examYears);
console.log('Exam years count:', alpineData.examYears.length);
```

If the array is empty, the API endpoint `/api/exam-years` may not be returning data.

## Step 7: Test API Endpoints
In the browser console, type:
```javascript
// Test exam years endpoint
fetch('/api/exam-years')
    .then(r => r.json())
    .then(data => console.log('Exam Years API Response:', data));

// Test active exam year endpoint
fetch('/api/exam-years/active')
    .then(r => r.json())
    .then(data => console.log('Active Exam Year API Response:', data));
```

## Common Issues & Solutions

### Issue 1: Modal doesn't appear when button clicked
**Cause:** Event handler not firing or showImportModal not toggling

**Solution:**
- Check browser console for JavaScript errors
- Verify that clicking the button logs "Import button clicked" to console
- Try manually setting showImportModal in console (see Step 4)

### Issue 2: Modal appears but no exam years in dropdown
**Cause:** examYears array is empty - data not loaded from API

**Solution:**
```javascript
// Check if API is returning data
fetch('/api/exam-years')
    .then(r => r.json())
    .then(data => {
        console.log('Response status:', data);
        if (!data.exam_years) {
            console.log('exam_years field missing!');
        }
    });
```

The API should return:
```json
{
    "exam_years": [
        {"id": 1, "year_label": "2023"},
        {"id": 2, "year_label": "2024"}
    ]
}
```

### Issue 3: Dropdown appears but buttons don't work
**Cause:** Click events not propagating correctly

**Solution:**
- Verify `@click.stop` is on the dropdown container
- Check that there are no z-index issues blocking clicks
- Try clicking directly on the button text

### Issue 4: Modal shows but inputs are unresponsive
**Cause:** x-model binding not working

**Solution:**
```javascript
// Check if x-model is binding
const component = document.querySelector('[x-data="candidatesManager()"]');
const alpineData = Alpine.raw(component.__x);

// Try updating importExamYear
alpineData.importExamYear = '2024';
console.log('Value after change:', alpineData.importExamYear);
```

## Quick Test Sequence

Run this in the browser console to test the entire flow:

```javascript
const component = document.querySelector('[x-data="candidatesManager()"]');
const alpineData = Alpine.raw(component.__x);

console.log('=== Import Modal Test ===');
console.log('1. Component found:', !!component);
console.log('2. Alpine data loaded:', !!alpineData);
console.log('3. Exam years count:', alpineData.examYears.length);
console.log('4. showImportModal (before):', alpineData.showImportModal);

// Simulate button click
alpineData.showImportModal = true;

console.log('5. showImportModal (after):', alpineData.showImportModal);

// Try to set exam year
alpineData.importExamYear = alpineData.examYears[0]?.year_label || '2024';
console.log('6. importExamYear set to:', alpineData.importExamYear);
```

## Network Issues
If the page loads but shows no exam years:

1. Open **Network** tab in DevTools
2. Look for requests to `/api/exam-years`
3. Check the response:
   - Status should be **200**
   - Response should contain `exam_years` array
   - Each year object should have `id` and `year_label`

If the request fails (404, 500, etc.), check:
- Backend route is registered (in `routes/web.php`)
- Controller method exists (ExamYearController::indexApi)
- Database has exam_years records

## File Locations to Check

```
- Frontend modal HTML: resources/views/registration/candidates.blade.php (lines 1424-1490)
- State initialization: resources/views/registration/candidates.blade.php (line 610)
- Load function: resources/views/registration/candidates.blade.php (line 685)
- Backend API route: routes/web.php (line 1152)
- Controller: app/Http/Controllers/ExamYearController.php
```

## Enable Detailed Logging

Add this to the modal button to enable debug output:

```html
@click="console.log('Modal state:', {showImportModal, importExamYear, importExamType, examYears}); showImportModal = true; showToolsMenu = false;"
```

This will log all relevant state when the button is clicked.
