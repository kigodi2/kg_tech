/**
 * ACSEE Bulk CSV Import - E2E Tests for Private Candidate Allocation
 * Integration test for subject-based imports
 */

describe('ACSEE Bulk CSV Import - Private Candidate Allocation', () => {

  beforeEach(() => {
    // Login first
    cy.login();
    
    cy.get('[data-testid="candidates-tab"]', { timeout: 10000 }).click();
    cy.get('[data-testid="candidates-table"]', { timeout: 10000 }).should('be.visible');
  });

  it('should complete valid private import workflow', () => {
    // Step 1: Open bulk import modal
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Step 2: Select Private import mode
    cy.get('input[value="PRIVATE"]').check();

    // Step 3: Upload valid private CSV
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_private_valid.csv',
      { force: true }
    );

    // Step 4: Select exam year (auto-selected or manually select first available)
    cy.selectExamYear();

    // Step 5: Validate
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: {
          total_rows: 3,
          valid_count: 3,
          invalid_count: 0
        },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Verify validation report
    cy.get('[data-testid="validation-report"]').should('be.visible');

    // Step 6: Commit
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', {
      statusCode: 200,
      body: {
        report: {
          success_count: 3,
          skipped_count: 0,
          failed_count: 0,
          affected_candidates: []
        },
        errors: []
      }
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });
    cy.wait('@commitRequest');

    // Verify success
    cy.get('[data-testid="commit-report"]').should('be.visible');
  });

  it('should download private allocation template', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    cy.intercept('GET', '/api/exam-types/acsee/templates/private-allocation.csv', {
      statusCode: 200,
      body: 'exam_year,index_number,subject_codes,replace_allocations\n'
    }).as('downloadTemplate');

    cy.get('[data-testid="download-private-template"]').click();
    cy.wait('@downloadTemplate');
  });

  it('should switch between school and private modes', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    // Start with School mode
    cy.get('input[value="SCHOOL"]').check();
    cy.get('input[value="SCHOOL"]').should('be.checked');

    // Switch to Private
    cy.get('input[value="PRIVATE"]').check();
    cy.get('input[value="PRIVATE"]').should('be.checked');
    cy.get('input[value="SCHOOL"]').should('not.be.checked');
  });

});
