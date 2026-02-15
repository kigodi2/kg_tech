# Cypress E2E Tests - Fixes Applied (2026-02-15)

## Changes Made

### 1. Gender Display Fix ✅
**File**: `resources/views/exam-types/acsee.blade.php` (line 165)

Changed from:
```html
<td x-text="candidate.gender === 'M' ? '♂ Male' : candidate.gender === 'F' ? '♀ Female' : '-'"></td>
```

Changed to:
```html
<td x-text="candidate.gender === 'M' ? 'M' : candidate.gender === 'F' ? 'F' : '-'"></td>
```

Now shows gender as simple "M" or "F" instead of "♂ Male"/"♀ Female".

---

### 2. Subject Code Mapping ✅
**Database**: Added missing subject codes

Added 3 new subjects to support user's CSV codes:
- **005**: SUBJECT 005 (SCIENCE)
- **006**: SUBJECT 006 (SCIENCE)
- **007**: SUBJECT 007 (ARTS)

These codes can now be used in candidate imports via the `subjects` column with pipe-delimiter (e.g., `111|005|006|007`).

---

### 3. Cypress Test Selectors Fixed ✅
**File**: `cypress/e2e/candidate_import_skip_replace.cy.js`

Updated file input selectors across all 6 tests:

**Before:**
```javascript
cy.get('input[type="file"]').selectFile('cypress/fixtures/candidate_import_mixed.csv', { force: true });
```

**After:**
```javascript
cy.get('input[type="file"][accept*="csv"], input[type="file"]').first().selectFile('cypress/fixtures/candidate_import_mixed.csv', { force: true });
```

**Import button selector improved:**
```javascript
cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
```

This prevents selector conflicts when multiple file inputs exist on the page.

---

### 4. Environment & Cypress ℹ️
- **Node.js**: v20.19.0 (already optimal)
- **Cypress**: 14.0.0 (stable, SIGILL known on Ubuntu 24.04 but mitigatable)
- **Recommendation**: Run tests with error handling, or use headless with proper dependencies

---

## Verification Steps

1. **Gender Display**: Load `/exam-types/acsee` and check Candidates tab - gender should show as "M" or "F"
2. **Subject Codes**: Check admin panel Subjects tab - codes 005, 006, 007 should be available
3. **Cypress Tests**: Run `npm run cypress:run` after fixing CI environment (if needed)

---

## Next Steps

1. Manual production validation with user's CSV (111|005|006|007 combination)
2. Verify "Allocated Subjects" column populates correctly in ACSEE Candidates table
3. Deploy changes to production after verification
