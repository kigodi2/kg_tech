# ACSEE Bulk CSV Import - Phase 2b Test Runbook

**Date:** February 15, 2026  
**Purpose:** Execute automated test suite for ACSEE Bulk CSV Import UI  
**Scope:** Jest unit tests + Cypress e2e tests

---

## Quick Start

```bash
# Install dependencies
npm install

# Run all tests (unit + e2e)
npm test

# Or run individually:
npm run test:unit       # Jest unit tests only
npm run test:e2e        # Cypress e2e tests (headless)
npm run test:e2e:open   # Cypress e2e tests (interactive UI)
```

---

## Prerequisites

### System Requirements
- Node.js 16+ installed
- npm or yarn package manager
- Modern web browser (Chrome 90+, Firefox 88+, Safari 14+)

### Application Requirements
- Laravel application running locally or on accessible server
- Backend API endpoints implemented (Phase 2a)
- Database with test data populated

### Port Configuration
Ensure the following are accessible:

```
Local Laravel: http://localhost:8000
Cypress Tests: Test against http://localhost:8000
```

If using a different port, update `cypress.config.js`:

```javascript
module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost:YOUR_PORT'
  }
});
```

---

## Step 1: Install Dependencies

```bash
cd /path/to/irms

# Install npm packages
npm install

# Verify installations
npm list jest cypress babel-jest
```

**Expected Output:**
```
├── @babel/preset-env@7.23.0
├── babel-jest@29.0.0
├── cypress@13.0.0
├── jest@29.0.0
└── ...
```

---

## Step 2: Run Unit Tests (Jest)

Unit tests validate:
- File upload validation
- State management
- Phase transitions
- API integration (mocked)
- Error handling
- User interactions

### Run All Unit Tests

```bash
npm run test:unit
```

### Run Unit Tests in Watch Mode

Automatically re-run tests when files change:

```bash
npm run test:unit:watch
```

### Run Specific Test Suite

```bash
# Run only file upload tests
jest tests/js/acsee-bulk-import.test.js -t "handleBulkFileUpload"

# Run only state management tests
jest tests/js/acsee-bulk-import.test.js -t "State Management"
```

### Expected Output

```
PASS  tests/js/acsee-bulk-import.test.js
  ACSEE Bulk CSV Import - Unit Tests
    handleBulkFileUpload()
      ✓ should accept CSV files and store file metadata
      ✓ should reject non-CSV files
      ✓ should reset state when uploading a new file
    State Management
      ✓ should initialize bulk import state correctly
      ✓ should reset all bulk state on modal close
      ✓ should maintain state during import phases
    Phase Transitions
      ✓ should transition idle -> validating -> reviewing on success
      ✓ should transition reviewing -> committing -> complete on success
      ✓ should revert to idle on validation error
      ✓ should revert to reviewing on commit error
    API Integration
      ✓ should call validate endpoint with correct FormData
      ✓ should call commit endpoint with correct FormData
      ✓ should include CSRF token in all POST requests
    Error Handling
      ✓ should validate file is selected before validating
      ✓ should validate exam year is selected before validating
      ✓ should handle network errors gracefully
      ✓ should handle JSON parse errors
    User Interactions
      ✓ should open bulk import modal and set initial state
      ✓ should close bulk import modal and reset state
      ✓ should require confirmation before committing import
      ✓ should proceed with commit when user confirms
      ✓ should set import mode based on candidate type filter

Test Suites: 1 passed, 1 total
Tests:       21 passed, 21 total
```

---

## Step 3: Verify Application is Running

Before running e2e tests, ensure the Laravel application is accessible:

```bash
# Check if application is running
curl -s http://localhost:8000/exam-types/acsee | grep -q "ACSEE" && echo "✓ Application is running" || echo "✗ Application not responding"

# Or open in browser
open http://localhost:8000/exam-types/acsee
```

---

## Step 4: Run E2E Tests (Cypress)

E2e tests validate complete workflows:
- School candidate allocation import
- Private candidate allocation import
- Error handling and recovery
- Replace allocations workflow

### Run All E2E Tests (Headless)

Runs all tests without displaying browser UI:

```bash
npm run test:e2e
```

### Open Cypress Interactive UI

Interactive mode allows you to watch tests run and debug:

```bash
npm run test:e2e:open
```

This opens the Cypress Test Runner where you can:
- Click on individual tests to run them
- See live browser interaction
- Debug failures with browser DevTools
- Watch video recordings of tests

### Run Specific E2E Test File

```bash
# Test school import workflow
cypress run --spec "cypress/e2e/acsee_bulk_import_school.cy.js"

# Test private import workflow
cypress run --spec "cypress/e2e/acsee_bulk_import_private.cy.js"

# Test error scenarios
cypress run --spec "cypress/e2e/acsee_bulk_import_errors.cy.js"

# Test replace allocations
cypress run --spec "cypress/e2e/acsee_bulk_import_replace.cy.js"
```

### Cypress Test Files

| File | Purpose |
|------|---------|
| `acsee_bulk_import_school.cy.js` | School candidate bulk import workflow |
| `acsee_bulk_import_private.cy.js` | Private candidate bulk import workflow |
| `acsee_bulk_import_errors.cy.js` | Error handling and recovery |
| `acsee_bulk_import_replace.cy.js` | Replace allocations (destructive) |

### Expected Output (Headless)

```
====================================================================================================

  (Run Starting)

  ┌────────────────────────────────────────────────────────────────────────────────────────────────┐
  │ Cypress: 13.0.0                                                                                │
  │ Browser: Chrome 120 (headless)                                                                 │
  │ Node Version: v18.0.0                                                                          │
  │ Specs: 4 found (acsee_bulk_import*.cy.js)                                                      │
  │ Spec Pattern: cypress/e2e/**/*.cy.{js,jsx,ts,tsx}                                              │
  └────────────────────────────────────────────────────────────────────────────────────────────────┘

  Running: acsee_bulk_import_school.cy.js                                           (1 of 4)

  ACSEE Bulk CSV Import - School Candidate Allocation
    ✓ should complete valid school import workflow (3.2s)
    ✓ should download school allocation template (0.8s)
    ✓ should prevent validation without file (0.5s)
    ✓ should prevent validation without exam year (0.6s)
    ✓ should close modal and reset state on close button click (1.1s)
    ✓ should show replace allocations warning when checked (0.7s)

  6 passing (7.9s)

  Running: acsee_bulk_import_private.cy.js                                          (2 of 4)

  ACSEE Bulk CSV Import - Private Candidate Allocation
    ✓ should complete valid private import workflow (3.0s)
    ✓ should download private allocation template (0.7s)
    ✓ should switch between school and private modes (0.9s)

  3 passing (4.6s)

  Running: acsee_bulk_import_errors.cy.js                                           (3 of 4)

  ACSEE Bulk CSV Import - Error Scenarios
    ✓ should show validation errors for invalid CSV (2.1s)
    ✓ should allow downloading error rows (1.8s)
    ✓ should prevent commit if errors exist (1.5s)
    ✓ should show commit errors and allow recovery (2.3s)
    ✓ should handle network errors gracefully (1.2s)

  5 passing (8.9s)

  Running: acsee_bulk_import_replace.cy.js                                          (4 of 4)

  ACSEE Bulk CSV Import - Replace Allocations
    ✓ should preserve allocations by default (add mode) (2.8s)
    ✓ should enable destructive replace mode when checked (2.6s)
    ✓ should warn user before replacing allocations (2.2s)
    ✓ should proceed with replace when user confirms (2.9s)

  4 passing (10.5s)

====================================================================================================

18 passing (32.9s)
```

---

## Step 5: Run Complete Test Suite

Run all tests (unit + e2e) together:

```bash
npm test
```

**Expected Output:**
```
Unit Tests: 21 passed
E2E Tests: 18 passed
Total: 39 tests passed
```

---

## Test Fixtures

Test CSV files are located in `cypress/fixtures/`:

### test_school_valid.csv
Valid school candidate allocation CSV with proper format:
```csv
exam_year,index_number,combination_code,replace_allocations
2026,S0001,111112,NO
2026,S0002,111123,NO
...
```

### test_school_invalid.csv
Invalid CSV for error testing:
```csv
exam_year,index_number,combination_code,replace_allocations
2026,INVALID,BADCODE,NO
2026,,111125,NO
...
```

### test_private_valid.csv
Valid private candidate allocation CSV:
```csv
exam_year,index_number,subject_codes,replace_allocations
2026,P0001,111|112|115|119|122,NO
2026,P0002,111|112|114|117|125,NO
...
```

---

## Debugging Tests

### Debug Unit Tests

```bash
# Run with verbose output
npm run test:unit -- --verbose

# Run with coverage report
npm run test:unit -- --coverage

# Debug in Node inspector
node --inspect-brk node_modules/.bin/jest
```

### Debug E2E Tests

In interactive mode:

```bash
npm run test:e2e:open
```

Then:
1. Click on test in Cypress UI
2. Right-click in browser view
3. Select "Inspect" to open DevTools
4. Step through with debugger

Or add debugging to test code:

```javascript
it('test name', () => {
  cy.get('[data-testid="element"]').debug();
  // Test continues...
});
```

### Check Browser Console

In interactive mode, Cypress shows console logs and errors. Look for:
- Alpine.js errors
- JavaScript syntax errors
- Fetch/XHR request failures
- Missing selectors

### View Test Videos

After running headless tests, videos are saved to:

```
cypress/videos/
```

Watch to see exactly what happened during test failure.

---

## Common Issues & Solutions

### Issue: "Application not responding"
```bash
# Ensure Laravel is running
php artisan serve

# Check port is correct in cypress.config.js
```

### Issue: "Element not found" in Cypress test
```bash
# Verify data-testid attributes exist in blade template
grep "data-testid" resources/views/exam-types/acsee.blade.php

# Add missing attributes if needed (see "STEP 1 — STUDY & PREPARE SELECTORS" in main instructions)
```

### Issue: Jest tests fail with "Cannot find module"
```bash
# Ensure setup files are loaded
npm run test:unit -- --setup-files-after-env

# Check jest.config.js setupFilesAfterEnv path
```

### Issue: Cypress times out on file upload
```bash
# Increase timeout in cypress.config.js
module.exports = defineConfig({
  e2e: {
    requestTimeout: 20000
  }
});
```

### Issue: CSRF token validation fails
```bash
# Check CSRF token is being sent
# Look in Cypress Network tab for X-CSRF-TOKEN header

# Ensure meta tag exists in layout
grep "csrf-token" resources/views/layout.blade.php
```

---

## Test Coverage Reports

Generate coverage report for unit tests:

```bash
npm run test:unit -- --coverage
```

**Output:** `coverage/lcov-report/index.html`

Open in browser to see line-by-line coverage.

---

## Continuous Integration

### GitHub Actions Example

```yaml
name: ACSEE Phase 2b Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Install dependencies
        run: npm install
      
      - name: Run unit tests
        run: npm run test:unit
      
      - name: Start Laravel
        run: php artisan serve &
      
      - name: Run e2e tests
        run: npm run test:e2e
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

---

## Test Maintenance

### Update Tests When Code Changes

1. **Function names change:** Update in test files
2. **State variables change:** Update initialization in test setup
3. **API endpoints change:** Update intercept URLs in Cypress
4. **UI selectors change:** Update data-testid attributes and Cypress selectors

### Add New Tests

1. Create new file: `cypress/e2e/test_name.cy.js`
2. Follow pattern from existing test files
3. Add fixtures if needed: `cypress/fixtures/data.csv`
4. Run specific test: `npm run test:e2e -- --spec "cypress/e2e/test_name.cy.js"`

---

## Success Criteria

✅ **All tests pass:**
- 21 Jest unit tests PASS
- 18 Cypress e2e tests PASS
- Zero critical failures
- Zero timeouts

✅ **Code coverage:**
- Minimum 80% coverage for bulk import functions
- All error paths tested

✅ **Performance:**
- Unit tests complete in < 30 seconds
- E2E tests complete in < 60 seconds

---

## Next Steps

After successful test execution:

1. **Code Review:** Review test code for quality
2. **Deployment:** Proceed to staging deployment
3. **Integration:** Integrate tests into CI/CD pipeline
4. **Maintenance:** Keep tests updated as code changes

---

## Support

For test issues or questions:

1. Check test output and error messages
2. Review relevant test file source code
3. Check browser console (interactive mode)
4. Review Cypress Network tab for API failures
5. Check backend API logs for 500 errors

---

**Test Suite Created:** February 15, 2026  
**Test Framework:** Jest + Cypress  
**Node Version Required:** 16+

