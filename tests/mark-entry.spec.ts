import { test, expect } from '@playwright/test';

test.describe('ACSEE mark-entry sidebar navigation (smoke)', () => {
    test('sidebar view navigation updates URL and shows only active view', async ({ page }) => {
    // Ensure logged in: if login link visible, register and login
    await page.goto('/');
    if (await page.locator('text=Login').count() > 0) {
      const unique = Date.now();
      const name = `E2E User ${unique}`;
      const email = `e2e+${unique}@example.com`;
      const password = 'password123';

      await page.goto('/register');
      await page.fill('input[name="name"]', name);
      await page.fill('input[name="email"]', email);
      await page.fill('input[name="password"]', password);
      await page.fill('input[name="password_confirmation"]', password);
      await Promise.all([
        page.click('text=Register'),
        page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {})
      ]);

      // If registration didn't auto-login, perform login
      if (await page.locator('text=Login').count() > 0) {
        await page.goto('/login');
        await page.fill('input[name="email"]', email);
        await page.fill('input[name="password"]', password);
        await Promise.all([
          page.click('button[type="submit"]'),
          page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {})
        ]);
      }
    }

    // Now go to mark-entry
    await page.goto('/mark-entry/acsee');

    // Wait for the sidebar label to render (Upload Marks)
    await expect(page.locator('text=Upload Marks').first()).toBeVisible({ timeout: 10000 });

    // Click Single Subject CSV
    await page.click('text=Single Subject CSV');
    await expect(page).toHaveURL(/view=single-subject-csv/);

    // Check breadcrumb/header updates to Single Subject CSV label
    await expect(page.locator('p', { hasText: 'Single Subject CSV' })).toBeVisible();

    // Click Review Dashboard (lazy view)
    await page.click('text=Review Dashboard');
    await expect(page).toHaveURL(/view=review-dashboard/);

    // Ensure moderation dashboard content appears (table header)
    await expect(page.locator('text=Moderation Dashboard')).toBeVisible();

    // Use back navigation to return to previous view
    await page.goBack();
    await expect(page).toHaveURL(/view=single-subject-csv/);

    // Forward navigation should return to review-dashboard
    await page.goForward();
    await expect(page).toHaveURL(/view=review-dashboard/);
  });
});
