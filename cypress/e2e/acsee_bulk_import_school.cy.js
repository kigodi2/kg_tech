/**
 * ACSEE Bulk CSV Import - E2E Tests for School Candidate Allocation
 * Integration test covering: File upload -> Validate -> Commit
 */

describe('ACSEE Bulk CSV Import - School Candidate Allocation', () => {

  beforeEach(() => {
    // Login first
    cy.login();
    
    // Wait for page to fully load and Alpine to initialize
    cy.get('[data-testid="candidates-tab"]', { timeout: 15000 }).should('exist');
    
    // Ensure we're on the Candidates tab
    cy.get('[data-testid="candidates-tab"]').click();
    
    // Wait for candidates table to be visible
    cy.get('[data-testid="candidates-table"]', { timeout: 15000 }).should('be.visible');
  });

  it('should complete valid school import workflow', () => {
    // Step 1: Open bulk import modal
    cy.get('[data-testid="bulk-import-button"]').click();
    // Wait for Alpine to toggle the modal visibility
    cy.get('[data-testid="bulk-import-modal"]', { timeout: 5000 })
      .should('exist')
      .and('have.css', 'display', 'flex');

    // Step 2: Select School import mode
    cy.get('input[value="SCHOOL"]', { timeout: 5000 }).check({ force: true });

    // Step 3: Upload valid CSV file
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    
    // Verify file is displayed
    cy.contains('test_school_valid.csv').should('be.visible');

    // Step 4: Select exam year (auto-selected or manually select first available)
    cy.selectExamYear();

    // Step 5: Click Validate button
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: {
          total_rows: 5,
          valid_count: 5,
          invalid_count: 0
        },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Step 6: Verify validation report is shown
    cy.contains('5').should('be.visible'); // Total rows
    cy.contains('5').should('be.visible'); // Valid count
    cy.get('[data-testid="validation-report"]').should('be.visible');

    // Step 7: Click Commit button
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', {
      statusCode: 200,
      body: {
        report: {
          success_count: 5,
          skipped_count: 0,
          failed_count: 0,
          affected_candidates: [
            {
              id: 1,
              index_number: 'S0001',
              full_name: 'John Doe',
              allocation_count: 5
            }
          ]
        },
        errors: []
      }
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    
    // Confirm in dialog if present
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });

    cy.wait('@commitRequest');

    // Step 8: Verify commit report is shown
    cy.get('[data-testid="commit-report"]').should('be.visible');
    cy.contains('5').should('be.visible'); // Success count
    cy.contains('Successful').should('be.visible');
  });

  it('should download school allocation template', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Intercept template download
    cy.intercept('GET', '/api/exam-types/acsee/templates/school-allocation.csv', {
      statusCode: 200,
      body: 'exam_year,index_number,combination_code,replace_allocations\n'
    }).as('downloadTemplate');

    cy.get('[data-testid="download-school-template"]').click();
    cy.wait('@downloadTemplate');
  });

  it('should prevent validation without file', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Try to validate without selecting file
    cy.get('[data-testid="validate-button"]').should('be.disabled');
  });

  it('should prevent validation without exam year', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Upload file
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );

    // Exam year not selected - button should be disabled or validation should fail
    cy.get('[data-testid="validate-button"]').click();
    
    // Should show error message
    cy.contains('Please select an exam year').should('be.visible');
  });

  it('should close modal and reset state on close button click', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Upload file and select mode
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );

    // Close modal
    cy.get('[data-testid="modal-close-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('not.exist');

    // Reopen modal and verify state is reset
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');
    cy.contains('test_school_valid.csv').should('not.exist');
  });

  it('should show replace allocations warning when checked', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Initially warning should not be visible
    cy.get('[data-testid="replace-warning"]').should('not.be.visible');

    // Check replace checkbox
    cy.get('[data-testid="bulk-replace-checkbox"]').check();

    // Warning should now be visible
    cy.get('[data-testid="replace-warning"]').should('be.visible');
    cy.contains('PERMANENTLY DELETE').should('be.visible');
  });

});
