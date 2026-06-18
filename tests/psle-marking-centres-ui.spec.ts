import { test, expect } from '@playwright/test';

test('verify PSLE Marking Centre Management premium UI, summary cards, and edit overlay modals', async ({ page }) => {
  test.setTimeout(60000);

  // 1. Login
  await page.goto('/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  // 2. Navigate to Marking Centres
  await page.goto('/mark-entry/psle?view=marking-centres');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  // 3. Assert stats summary cards are present
  const totalCentresCard = page.locator('.adm-stat-label:has-text("Total Centres")');
  await expect(totalCentresCard).toBeVisible();

  const activeCentresCard = page.locator('.adm-stat-label:has-text("Active Centres")');
  await expect(activeCentresCard).toBeVisible();

  // 4. Assert Add New Marking Centre form is visible (as Admin)
  const addFormTitle = page.locator('.adm-card-title:has-text("Add New Marking Centre")');
  await expect(addFormTitle).toBeVisible();

  // 5. Assert that at least one row exists or the table is visible
  const regionalCentresCard = page.locator('.adm-card-title:has-text("Regional Centres")');
  await expect(regionalCentresCard).toBeVisible();

  // If there are existing records, click Edit button to open overlay modal
  const editButton = page.locator('button[title="Edit Centre"]').first();
  const editCentreOverlay = page.locator('div#editCentreModal');

  if (await editButton.count() > 0) {
    await editButton.click();
    await expect(editCentreOverlay).toBeVisible();
    await expect(editCentreOverlay).toHaveCSS('display', 'flex');

    // Confirm edit form input has content loaded
    const editNameInput = page.locator('input#edit_name');
    await expect(editNameInput).toBeVisible();
    const loadedNameValue = await editNameInput.inputValue();
    expect(loadedNameValue.length).toBeGreaterThan(0);

    // Click Cancel to dismiss modal
    const cancelBtn = page.locator('#editCentreModal button:has-text("Cancel")');
    await expect(cancelBtn).toBeVisible();
    await cancelBtn.click();
    await expect(editCentreOverlay).not.toBeVisible();
  }

  console.log('--- ALL PSLE MARKING CENTRES MANAGEMENT UI REDESIGN E2E TESTS PASSED SUCCESSFULLY ---');
});
