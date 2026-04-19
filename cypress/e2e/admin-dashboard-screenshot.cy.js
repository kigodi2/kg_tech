describe('Admin dashboard screenshot', () => {
  it('logs in and captures admin dashboard', () => {
    cy.visit('/login');
    cy.get('input[name="email"]').type('agreykigodi@gmail.com');
    cy.get('input[name="password"]').type('Vian@2012');
    cy.get('button[type="submit"], button').contains(/login/i).click();

    cy.visit('/admin/dashboard');
    cy.screenshot('admin-dashboard', { capture: 'fullPage' });
  });
});
