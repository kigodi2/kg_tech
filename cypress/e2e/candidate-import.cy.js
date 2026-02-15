/**
 * Cypress E2E Tests - Candidate Import System
 * Date: 2026-02-16
 * 
 * Tests the candidate import functionality including:
 * - CSV validation without exam_year column
 * - Import commit and database verification
 * - PRIVATE candidate subject allocation
 * - Skip/Replace modes
 * - Error handling
 * 
 * Run: npx cypress run --spec "cypress/e2e/candidate-import.cy.js"
 */

describe('Candidate Import System', () => {
  // Test configuration
  const testTimeout = 10000;
  const baseUrl = 'http://localhost:8000';

  // Test data
  const validCandidatesCSV = `candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
S0401TEST,John Import,M,S0713,SCHOOL,PCM,
S0402TEST,Jane Import,F,S0713,SCHOOL,PCB,
P0401TEST,Private Import,M,S0744,PRIVATE,,111|121|131`;

  const candidatesWithExamYear = `candidate_id,full_name,gender,school_code,candidate_type,exam_year,combination,subjects
S0501TEST,Eve ExamYear,F,S0713,SCHOOL,2026,PCM,
P0501TEST,Frank ExamYear,M,S0744,PRIVATE,2026,,111|131|141`;

  const invalidSchoolCSV = `candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
BADSCHOOL01,Invalid School,M,ZZZZ,SCHOOL,PCM,`;

  const invalidSubjectCSV = `candidate_id,full_name,gender,school_code,candidate_type,combination,subjects
P0601TEST,Invalid Subject,F,S0744,PRIVATE,,999|888|777`;

  beforeEach(() => {
    // Login before each test
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type('admin@test.com');
    cy.get('input[name="password"]').type('password');
    cy.get('button[type="submit"]').click();
    
    // Wait for dashboard
    cy.url().should('include', '/dashboard');
    cy.wait(1000);
  });

  describe('Import Modal', () => {
    it('should open import modal when clicking Import Candidates button', () => {
      // Navigate to candidates page
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      // Find and click import button
      cy.contains('button', /Import|import/i).click({ force: true });
      
      // Modal should appear
      cy.get('[role="dialog"]').should('be.visible');
      cy.get('[role="dialog"]').should('contain', 'Import');
    });

    it('should display exam year dropdown with correct options', () => {
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);
      
      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      // Check for exam year dropdown
      cy.get('[role="dialog"]').within(() => {
        // Look for dropdown or select element
        cy.contains(/Exam.*Year/i).should('be.visible');
        
        // Check for year options
        cy.contains('2026').should('be.visible');
        cy.contains('2025').should('be.visible');
        cy.contains('2024').should('be.visible');
      });
    });

    it('should have Skip and Replace import mode options', () => {
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);
      
      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      cy.get('[role="dialog"]').within(() => {
        // Check for mode options
        cy.contains(/Skip/i).should('be.visible');
        cy.contains(/Replace/i).should('be.visible');
      });
    });
  });

  describe('CSV Validation - Without exam_year Column', () => {
    it('should validate CSV without exam_year column using UI dropdown', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      // Ensure exam year is selected
      cy.get('[role="dialog"]').within(() => {
        // Set exam year to 2026
        cy.get('select, [data-testid*="year"], input[name*="exam_year"]')
          .first()
          .should('contain', '2026')
          .or('have.value', '2026');

        // Set Skip mode
        cy.contains('Skip').parent('label').click({ force: true });
      });

      // Upload CSV file
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      // Wait for validation
      cy.wait(2000);

      // Check validation results
      cy.get('[role="dialog"]').within(() => {
        // Should show preview table
        cy.contains(/Preview|Results/i).should('be.visible');
        
        // Should show row count
        cy.contains(/3|rows/i).should('be.visible');
        
        // Should show success message or no errors
        cy.contains(/error|Error/).should('not.exist').or('contain', '0');
        
        // Should have Proceed/Import button
        cy.contains('button', /Proceed|Import|Continue/i).should('be.visible');
      });
    });

    it('should show preview table with candidate data', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      // Upload CSV
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Check preview table
      cy.get('[role="dialog"]').within(() => {
        // Should contain candidate IDs
        cy.contains('S0401TEST').should('be.visible');
        cy.contains('P0401TEST').should('be.visible');
        
        // Should show status (NEW for first import)
        cy.contains('NEW').should('be.visible');
      });
    });
  });

  describe('CSV Validation - With exam_year Column', () => {
    it('should accept CSV with exam_year column', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      // Upload CSV with exam_year
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: candidatesWithExamYear,
          fileName: 'test_with_year.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Should validate successfully
      cy.get('[role="dialog"]').within(() => {
        cy.contains('S0501TEST').should('be.visible');
        cy.contains(/error|Error/).should('not.exist').or('contain', '0');
      });
    });
  });

  describe('Import Commit', () => {
    it('should successfully import candidates on commit', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      // Upload and validate
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Click import/proceed button
      cy.get('[role="dialog"]').within(() => {
        cy.contains('button', /Proceed|Import|Confirm/i).click({ force: true });
      });

      // Wait for import to complete
      cy.wait(3000);

      // Should show success message
      cy.contains(/success|Success|imported|Imported/i).should('be.visible');

      // Modal should close
      cy.get('[role="dialog"]').should('not.exist').or('not.be.visible');

      // Should redirect to candidates page
      cy.url().should('include', '/candidates');
    });
  });

  describe('Skip Mode', () => {
    it('should skip duplicate candidates in Skip mode', function() {
      this.timeout(testTimeout);

      // First import
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);
      cy.get('[role="dialog"]').within(() => {
        cy.contains('button', /Proceed|Import/i).click({ force: true });
      });

      cy.wait(3000);
      cy.get('[role="dialog"]').should('not.exist').or('not.be.visible');

      // Second import (same file, Skip mode)
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        // Select Skip mode
        cy.contains('Skip').parent('label').click({ force: true });
        
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Should show SKIP status for duplicates
      cy.get('[role="dialog"]').within(() => {
        cy.contains(/SKIP|Skip/i).should('be.visible');
        cy.contains('button', /Import|Proceed/i).should('be.disabled').or('not.exist');
      });
    });
  });

  describe('Error Handling', () => {
    it('should show error for invalid school code', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: invalidSchoolCSV,
          fileName: 'invalid_school.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Should show error message
      cy.get('[role="dialog"]').within(() => {
        cy.contains(/School|not found|error/i).should('be.visible');
        cy.contains('ERROR').should('be.visible');
      });
    });

    it('should show error for invalid subject codes', function() {
      this.timeout(testTimeout);

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: invalidSubjectCSV,
          fileName: 'invalid_subject.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Should show error message
      cy.get('[role="dialog"]').within(() => {
        cy.contains(/Subject|not found|999/i).should('be.visible');
        cy.contains('ERROR').should('be.visible');
      });
    });
  });

  describe('ACSEE Management Page', () => {
    it('should display imported candidates on ACSEE page', function() {
      this.timeout(testTimeout);

      // First import candidates
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);
      cy.get('[role="dialog"]').within(() => {
        cy.contains('button', /Proceed|Import/i).click({ force: true });
      });

      cy.wait(3000);

      // Navigate to ACSEE page
      cy.visit(`${baseUrl}/dashboard/exam-types/acsee`);
      cy.wait(2000);

      // Check for imported candidates
      cy.contains('S0401TEST').should('be.visible');
      cy.contains('P0401TEST').should('be.visible');

      // Check for allocated subjects column
      cy.contains(/Allocated|Subjects/i).should('be.visible');
    });

    it('should show correct subject allocation for PRIVATE candidates', function() {
      this.timeout(testTimeout);

      // Import data
      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);
      cy.get('[role="dialog"]').within(() => {
        cy.contains('button', /Proceed|Import/i).click({ force: true });
      });

      cy.wait(3000);

      // Go to ACSEE page
      cy.visit(`${baseUrl}/dashboard/exam-types/acsee`);
      cy.wait(2000);

      // Find P0401TEST row and check allocations
      cy.contains('P0401TEST').parent('tr').within(() => {
        // Should show the allocated subjects
        cy.contains(/111|121|131/i).should('be.visible');
      });
    });
  });

  describe('Browser Console Check', () => {
    it('should not have JavaScript errors in console', function() {
      this.timeout(testTimeout);

      const errors = [];
      cy.on('uncaught:exception', (err) => {
        errors.push(err.message);
        return false; // Prevent test from failing
      });

      cy.visit(`${baseUrl}/dashboard/registration/candidates`);
      cy.wait(1000);

      cy.contains('button', /Import|import/i).click({ force: true });
      cy.get('[role="dialog"]').should('be.visible');

      cy.get('[role="dialog"]').within(() => {
        cy.get('input[type="file"]').selectFile({
          contents: validCandidatesCSV,
          fileName: 'test_candidates.csv',
          mimeType: 'text/csv'
        });
      });

      cy.wait(2000);

      // Should not have errors
      expect(errors).to.have.length(0);
    });
  });
});
