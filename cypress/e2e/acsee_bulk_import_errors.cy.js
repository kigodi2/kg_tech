/**
 * ACSEE Bulk CSV Import - E2E Tests for Error Scenarios
 * Integration tests for error handling and recovery
 */

describe('ACSEE Bulk CSV Import - Error Scenarios', () => {

  beforeEach(() => {
    // Login first
    cy.login();
    
    cy.get('[data-testid="candidates-tab"]', { timeout: 10000 }).click();
    cy.get('[data-testid="candidates-table"]', { timeout: 10000 }).should('be.visible');
  });

  it('should show validation errors for invalid CSV', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');

    // Select School mode
    cy.get('input[value="SCHOOL"]').check();

    // Upload invalid CSV
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_invalid.csv',
      { force: true }
    );

    // Select exam year
    cy.selectExamYear();

    // Validate with errors
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: {
          total_rows: 3,
          valid_count: 1,
          invalid_count: 2
        },
        errors: [
          {
            row_number: 2,
            index_number: 'INVALID',
            error_messages: ['Combination BADCODE not found']
          },
          {
            row_number: 4,
            index_number: '',
            error_messages: ['Index number is required']
          }
        ]
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Should show error report
    cy.get('[data-testid="validation-report"]').should('be.visible');
    cy.contains('Invalid').should('be.visible');
    cy.contains('2').should('be.visible'); // Invalid count

    // Error list should be visible
    cy.contains('Combination BADCODE not found').should('be.visible');
    cy.contains('Index number is required').should('be.visible');
  });

  it('should allow downloading error rows', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    // Upload and validate with errors (same as above)
    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_invalid.csv',
      { force: true }
    );
    cy.selectExamYear();

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 3, valid_count: 1, invalid_count: 2 },
        errors: [
          { row_number: 2, index_number: 'INVALID', error_messages: ['Error 1'] },
          { row_number: 4, index_number: '', error_messages: ['Error 2'] }
        ]
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Download error rows
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/download-errors', {
      statusCode: 200,
      headers: {
        'Content-Type': 'text/csv',
        'Content-Disposition': 'attachment; filename="errors.csv"'
      },
      body: 'row_number,index_number,error_messages\n2,INVALID,"Error 1"\n4,,"Error 2"'
    }).as('downloadErrors');

    cy.get('[data-testid="download-error-rows-button"]').should('be.visible').click();
    cy.wait('@downloadErrors');
  });

  it('should prevent commit if errors exist', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_invalid.csv',
      { force: true }
    );
    cy.selectExamYear();

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: {
          total_rows: 3,
          valid_count: 1,
          invalid_count: 2
        },
        errors: [{ row_number: 2, error_messages: ['Error'] }]
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Commit button should be disabled when errors exist
    cy.get('[data-testid="commit-button"]').should('be.disabled');
  });

  it('should show commit errors and allow recovery', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Validate successfully
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 5, valid_count: 5, invalid_count: 0 },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Commit with some failures
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', {
      statusCode: 200,
      body: {
        report: {
          success_count: 3,
          skipped_count: 1,
          failed_count: 1
        },
        errors: [
          {
            row_number: 5,
            index_number: 'S0005',
            error_messages: ['Database error']
          }
        ]
      }
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });
    cy.wait('@commitRequest');

    // Should show partial success report
    cy.get('[data-testid="commit-report"]').should('be.visible');
    cy.contains('1').should('be.visible'); // Failed count

    // Download errors should be available
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/download-errors', {
      statusCode: 200,
      body: 'errors'
    }).as('downloadErrors');

    cy.get('[data-testid="download-error-rows-button"]').should('be.visible').click();
    cy.wait('@downloadErrors');
  });

  it('should handle network errors gracefully', () => {
    cy.get('[data-testid="bulk-import-button"]').click();

    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Simulate network error
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 500,
      body: { message: 'Server error' }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Should show error message
    cy.get('[data-testid="error-message"]').should('be.visible');
  });

});
