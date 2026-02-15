/**
 * Cypress E2E Support File
 * Global hooks and utilities for ACSEE Bulk Import tests
 */

// Hide fetch/XHR requests from log
const app = window.top;

if (!app.document.head.querySelector('[data-hide-command-log-request]')) {
  const style = app.document.createElement('style');
  style.innerHTML =
    '.command-name-request, .command-name-xhr { display: none }';
  style.setAttribute('data-hide-command-log-request', '');

  app.document.head.appendChild(style);
}

// Cypress 13+ has built-in selectFile() command, no need to define custom one

/**
 * Custom Command: cy.login()
 * Authenticates the user for E2E tests
 * Directly seeds test user in database and establishes session
 */
Cypress.Commands.add('login', (email = 'admin@test.com', password = 'password') => {
  cy.session([email, password], () => {
    // Seed test user via API/backend call
    cy.request({
      method: 'POST',
      url: '/api/test-seed/user',
      body: { email, password },
      failOnStatusCode: false
    });
    
    // Try traditional login flow
    cy.visit('/login');
    cy.get('input[name="email"]', { timeout: 10000 }).type(email, { delay: 50 });
    cy.get('input[name="password"]', { timeout: 10000 }).type(password, { delay: 50 });
    cy.get('button[type="submit"]', { timeout: 10000 }).click();
    
    // Wait for any redirect
    cy.url({ timeout: 15000 }).should('not.include', '/login');
  });
  
  // Navigate to ACSEE page
  cy.visit('/exam-types/acsee');
  cy.wait(500);
});

/**
 * Alternative: Direct session authentication via cy.request()
 * This approach bypasses the login form entirely
 */
Cypress.Commands.add('loginViaAPI', (email = 'admin@test.com', password = 'password') => {
  cy.session([email, password], () => {
    cy.request({
      method: 'POST',
      url: '/login',
      form: true,
      body: {
        email: email,
        password: password,
        _token: 'test-token' // Laravel will validate this
      }
    }).then((response) => {
      expect(response.status).to.eq(200);
    });
  });
});

/**
 * Custom Command: cy.selectExamYear()
 * Selects an exam year from the bulk import dropdown
 * Handles both auto-selected values and manual fallback
 */
Cypress.Commands.add('selectExamYear', (selector = '[data-testid="bulk-exam-year-select"]') => {
  cy.get(selector, { timeout: 5000 }).should('be.visible');
  cy.get(`${selector} option`).should('have.length.greaterThan', 1);
  cy.get(selector).then(($select) => {
    const value = $select.val();
    if (!value) {
      // Not auto-selected, manually select first available
      cy.get(`${selector} option`).then(($options) => {
        const firstValue = $options.eq(1).val();
        cy.get(selector).select(firstValue);
      });
    }
  });
});

// Add authentication if needed
beforeEach(() => {
  // Add CSRF token to window if not present
  if (!document.querySelector('meta[name="csrf-token"]')) {
    const meta = document.createElement('meta');
    meta.name = 'csrf-token';
    meta.content = 'test-csrf-token-' + Date.now();
    document.head.appendChild(meta);
  }
});

// Global error handler
Cypress.on('uncaught:exception', (err, runnable) => {
  // Return false to prevent Cypress from failing the test
  // Ignore expected test errors
  if (err.message.includes('ResizeObserver loop limit exceeded')) {
    return false;
  }
  // Ignore fetch/network errors for mocked API calls
  if (err.message.includes('fetch')) {
    return false;
  }
  // Ignore cross-origin script errors (Alpine.js CDN)
  if (err.message.includes('Script error') || err.message.includes('cross origin')) {
    return false;
  }
  // Ignore Alpine.js errors
  if (err.message.includes('Alpine') || err.toString().includes('Alpine')) {
    return false;
  }
  return true;
});
