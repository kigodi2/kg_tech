/**
 * ACSEE Bulk CSV Import - E2E Tests for Replace Allocations
 * Integration tests for destructive operations
 */

describe('ACSEE Bulk CSV Import - Replace Allocations', () => {

  beforeEach(() => {
    // Login first
    cy.login();
    
    cy.get('[data-testid="candidates-tab"]', { timeout: 10000 }).click();
    cy.get('[data-testid="candidates-table"]', { timeout: 10000 }).should('be.visible');
  });

  it('should preserve allocations by default (add mode)', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Replace checkbox should not be checked by default
    cy.get('[data-testid="bulk-replace-checkbox"]').should('not.be.checked');

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 5, valid_count: 5, invalid_count: 0 },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Verify request includes replace_allocations=false
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', (req) => {
      expect(req.body).to.contain('replace_allocations=false');
      req.reply({
        statusCode: 200,
        body: {
          report: { success_count: 5, failed_count: 0 },
          errors: []
        }
      });
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });
    cy.wait('@commitRequest');
  });

  it('should enable destructive replace mode when checked', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Check replace checkbox
    cy.get('[data-testid="bulk-replace-checkbox"]').check();
    cy.get('[data-testid="bulk-replace-checkbox"]').should('be.checked');

    // Warning should be visible
    cy.get('[data-testid="replace-warning"]').should('be.visible');
    cy.contains('PERMANENTLY DELETE').should('be.visible');
    cy.contains('all existing allocations').should('be.visible');

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 5, valid_count: 5, invalid_count: 0 },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // Commit with replace enabled
    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', (req) => {
      expect(req.body).to.contain('replace_allocations=true');
      req.reply({
        statusCode: 200,
        body: {
          report: { success_count: 5, failed_count: 0 },
          errors: []
        }
      });
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });
    cy.wait('@commitRequest');
  });

  it('should warn user before replacing allocations', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Enable replace mode
    cy.get('[data-testid="bulk-replace-checkbox"]').check();

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 5, valid_count: 5, invalid_count: 0 },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // User cancels confirmation dialog
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(false);
    });

    cy.get('[data-testid="commit-button"]').click();

    // Modal should still be open, operation should not proceed
    cy.get('[data-testid="bulk-import-modal"]').should('be.visible');
  });

  it('should proceed with replace when user confirms', () => {
    cy.get('[data-testid="bulk-import-button"]').click();
    cy.get('input[value="SCHOOL"]').check();
    cy.get('[data-testid="bulk-csv-file"]').selectFile(
      'cypress/fixtures/test_school_valid.csv',
      { force: true }
    );
    cy.selectExamYear();

    // Enable replace mode
    cy.get('[data-testid="bulk-replace-checkbox"]').check();

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/validate', {
      statusCode: 200,
      body: {
        report: { total_rows: 5, valid_count: 5, invalid_count: 0 },
        errors: []
      }
    }).as('validateRequest');

    cy.get('[data-testid="validate-button"]').click();
    cy.wait('@validateRequest');

    // User confirms
    cy.window().then((win) => {
      cy.stub(win, 'confirm').returns(true);
    });

    cy.intercept('POST', '/api/exam-types/acsee/allocate-from-csv/commit', {
      statusCode: 200,
      body: {
        report: {
          success_count: 5,
          failed_count: 0,
          affected_candidates: []
        },
        errors: []
      }
    }).as('commitRequest');

    cy.get('[data-testid="commit-button"]').click();
    cy.wait('@commitRequest');

    // Should show success report
    cy.get('[data-testid="commit-report"]').should('be.visible');
  });

});
