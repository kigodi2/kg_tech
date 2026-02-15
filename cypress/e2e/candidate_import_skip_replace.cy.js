/**
 * Candidate Import Skip/Replace E2E Tests
 * Tests the skip vs replace mode for candidate imports
 * 
 * NOTE: These tests verify the actual Candidates import modal in candidates.blade.php
 * The modal uses x-show and Alpine.js, so selectors must account for display:none initially
 */

describe('Candidate Import - Skip vs Replace Mode', () => {
  beforeEach(() => {
    cy.login();
    cy.visit('/registration/candidates');
    cy.wait(1000);
  });

  /**
   * Test 1: SKIP MODE - Existing candidates should not be modified
   */
  it('SKIP MODE: should not modify existing candidates', () => {
    // Find and click the import button
    // In candidates.blade.php, there's a button that calls openImportModal()
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    // Wait for modal to be visible
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Upload the CSV file - use specific selector to avoid other file inputs
    cy.get('input[type="file"][accept*="csv"], input[type="file"]').first().selectFile(
      'cypress/fixtures/candidate_import_mixed.csv',
      { force: true }
    );
    cy.wait(500);

    // File should now show as selected
    cy.contains('candidate_import_mixed.csv', { timeout: 5000 }).should('be.visible');

    // Select SKIP mode (radio button with value="skip")
    cy.get('input[type="radio"][value="skip"]').check({ force: true });

    // Select exam year 2026
    cy.get('select').eq(1).select('2026', { force: true });

    // Click Validate button
    cy.contains('button', 'Validate', { timeout: 5000 }).click({ force: true });

    // Wait for validation phase to complete
    // Should see "Step 2: Review Results"
    cy.contains('Step 2: Review Results', { timeout: 10000 }).should('be.visible');

    // Verify counts: 1 new, 2 skipped
    cy.contains('New').parent().contains('1', { timeout: 5000 }).should('be.visible');
    cy.contains('Will Skip').parent().contains('2', { timeout: 5000 }).should('be.visible');

    // Click Commit/Proceed button
    cy.get('button').contains(/Commit|Proceed|Import/, { matchCase: false })
      .click({ force: true });

    // Should see success message
    cy.contains(/successful|imported/i, { timeout: 10000 }).should('be.visible');

    // Close modal by clicking close button (X)
    cy.get('button').contains('×').click({ force: true });
    cy.wait(500);

    // Reload page to see fresh data
    cy.reload();

    // Verify: existing candidates should keep original names
    cy.contains('JOHN DOE', { timeout: 15000 }).should('be.visible');
    cy.contains('JANE SMITH').should('be.visible');
  });

  /**
   * Test 2: REPLACE MODE - Existing candidates should be updated
   */
  it('REPLACE MODE: should update existing candidates', () => {
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Upload CSV - use specific selector to avoid other file inputs
    cy.get('input[type="file"][accept*="csv"], input[type="file"]').first().selectFile(
      'cypress/fixtures/candidate_import_mixed.csv',
      { force: true }
    );
    cy.wait(500);

    cy.contains('candidate_import_mixed.csv', { timeout: 5000 }).should('be.visible');

    // Select REPLACE mode
    cy.get('input[type="radio"][value="replace"]').check({ force: true });

    // Select exam year
    cy.get('select').eq(1).select('2026', { force: true });

    // Validate
    cy.contains('button', 'Validate', { timeout: 5000 }).click({ force: true });

    cy.contains('Step 2: Review Results', { timeout: 10000 }).should('be.visible');

    // Verify counts: 1 new, 2 will update
    cy.contains('New').parent().contains('1', { timeout: 5000 }).should('be.visible');
    cy.contains('Will Update').parent().contains('2', { timeout: 5000 }).should('be.visible');

    // Commit
    cy.get('button').contains(/Commit|Proceed|Import/, { matchCase: false })
      .click({ force: true });

    // Verify success
    cy.contains(/successful|imported/i, { timeout: 10000 }).should('be.visible');

    // Close modal
    cy.get('button').contains('×').click({ force: true });
    cy.wait(500);

    // Reload page
    cy.reload();

    // Verify: existing candidates should have UPDATED names
    cy.contains('JOHN PETER DOE', { timeout: 15000 }).should('be.visible');
    cy.contains('JANE MARIE SMITH').should('be.visible');
  });

  /**
   * Test 3: Validation should show errors for invalid data
   */
  it('should show validation errors for invalid CSV', () => {
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Upload invalid CSV - use specific selector to avoid other file inputs
    cy.get('input[type="file"][accept*="csv"], input[type="file"]').first().selectFile(
      'cypress/fixtures/candidate_import_errors.csv',
      { force: true }
    );
    cy.wait(500);

    cy.contains('candidate_import_errors.csv').should('be.visible');

    // Select exam year
    cy.get('select').eq(1).select('2026', { force: true });

    // Validate
    cy.contains('button', 'Validate', { timeout: 5000 }).click({ force: true });

    cy.contains('Step 2: Review Results', { timeout: 10000 }).should('be.visible');

    // Should show errors
    cy.contains('Errors').parent().then(($errorDiv) => {
      // Get the error count - should be > 0
      cy.wrap($errorDiv).contains(/[1-9]/, { timeout: 5000 }).should('be.visible');
    });

    // Can Import should show "No ✗"
    cy.contains('Can Import').parent().contains('No ✗', { timeout: 5000 }).should('be.visible');
  });

  /**
   * Test 4: Download template functionality
   */
  it('should allow downloading import template', () => {
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Click template download button
    cy.get('button').contains(/template/i, { matchCase: false }).click({ force: true });

    // Button should be clickable (file download happens without Cypress tracking it)
    cy.get('button').contains(/template/i, { matchCase: false }).should('be.visible');
  });

  /**
   * Test 5: Cannot validate without file
   */
  it('should prevent validation without file', () => {
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Try to validate without file - button should be disabled or do nothing
    // The validate button is disabled when no file is selected
    cy.contains('button', 'Validate').should('be.disabled');
  });

  /**
   * Test 6: Modal can be closed
   */
  it('should close modal when close button clicked', () => {
    cy.get('button[data-testid="bulk-import-button"], button').contains(/bulk import/i, { matchCase: false }).click({ force: true });
    
    cy.get('h2').contains('Import Candidates', { timeout: 5000 }).should('be.visible');

    // Click close button
    cy.get('button').contains('×').click({ force: true });

    // Modal should no longer be visible
    cy.get('h2').contains('Import Candidates').should('not.be.visible');
  });
});
