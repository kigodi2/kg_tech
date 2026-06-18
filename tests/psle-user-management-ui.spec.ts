import { test, expect } from '@playwright/test';

test('verify PSLE User Management premium UI, action buttons, and overlay modals', async ({ page }) => {
  test.setTimeout(60000);

  // 1. Login
  await page.goto('/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  // 2. Navigate to User Management
  await page.goto('/mark-entry/psle?view=user-management');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  // 3. Assert search input and action buttons are visible and styled correctly
  const searchInput = page.locator('input#userSearchInput');
  await expect(searchInput).toBeVisible();
  await expect(searchInput).toHaveClass(/psle-search-input/);

  const csvTemplateBtn = page.locator('.psle-toolbar-grid a:has-text("CSV Template")');
  await expect(csvTemplateBtn).toBeVisible();
  await expect(csvTemplateBtn).toHaveClass(/psle-action-btn-dark/);

  const importCsvBtn = page.locator('.psle-toolbar-grid button:has-text("Import CSV")');
  await expect(importCsvBtn).toBeVisible();
  await expect(importCsvBtn).toHaveClass(/psle-action-btn-blue/);

  const addUserBtn = page.locator('.psle-toolbar-grid button:has-text("Add User")');
  await expect(addUserBtn).toBeVisible();
  await expect(addUserBtn).toHaveClass(/psle-action-btn-green/);

  // 4. Test Add User Modal overlay trigger
  await addUserBtn.click();
  const createUserOverlay = page.locator('div#createUserCard');
  await expect(createUserOverlay).toBeVisible();
  await expect(createUserOverlay).toHaveCSS('display', 'flex');

  // Verify modal elements
  const createUserTitle = page.locator('#createUserCard h2:has-text("Create New User")');
  await expect(createUserTitle).toBeVisible();

  // Verify Role select has "Subject Panel Leader" role option
  const roleSelect = page.locator('#createUserCard select[name="role_id"]');
  await expect(roleSelect).toBeVisible();
  const optionText = await roleSelect.innerText();
  expect(optionText).toContain('Subject Panel Leader');

  // Verify dynamic Region / Council / Marking Centre dropdown filtering
  const regionSelect = page.locator('#createUserCard select[name="region_id"]');
  const councilSelect = page.locator('#createUserCard select[name="district_council_id"]');
  const centreSelect = page.locator('#createUserCard select[name="marking_centre_id"]');

  await expect(regionSelect).toBeVisible();
  await expect(councilSelect).toBeVisible();
  await expect(centreSelect).toBeVisible();

  const regionOptions = await regionSelect.locator('option').all();
  if (regionOptions.length > 1) {
    // Select the first real region option (index 1)
    const testRegionText = await regionOptions[1].innerText();
    const testRegionVal = await regionOptions[1].getAttribute('value');
    
    // Choose the region
    await regionSelect.selectOption({ value: testRegionVal });
    
    // Check that display names are cleaned up (no region brackets)
    const filteredCouncilText = await councilSelect.innerText();
    expect(filteredCouncilText).not.toContain(`(${testRegionText})`);

    // Verify auto-clearing / resetting of selected values on region change
    const councilOptions = await councilSelect.locator('option').all();
    if (councilOptions.length > 1) {
      const testCouncilVal = await councilOptions[1].getAttribute('value');
      await councilSelect.selectOption({ value: testCouncilVal });
      
      // Confirm selection
      await expect(councilSelect).toHaveValue(testCouncilVal);
      
      // Clear region choice
      await regionSelect.selectOption({ value: '' });
      
      // Council value should be automatically reset to empty
      await expect(councilSelect).toHaveValue('');
    }
  }

  // Close Add User Modal
  const cancelBtn = page.locator('#createUserCard button:has-text("Cancel")');
  await expect(cancelBtn).toBeVisible();
  await cancelBtn.click();
  await expect(createUserOverlay).not.toBeVisible();

  // 5. Test Import CSV Modal overlay trigger
  await importCsvBtn.click();
  const importCsvOverlay = page.locator('div#importUserCard');
  await expect(importCsvOverlay).toBeVisible();
  await expect(importCsvOverlay).toHaveCSS('display', 'flex');

  // Verify modal elements
  const importTitle = page.locator('#importUserCard h2:has-text("Import PSLE Users")');
  await expect(importTitle).toBeVisible();

  const fileInput = page.locator('#importUserCard input[name="users_csv"]');
  await expect(fileInput).toBeVisible();

  // Close Import CSV Modal
  const importCancelBtn = page.locator('#importUserCard button:has-text("Cancel")');
  await expect(importCancelBtn).toBeVisible();
  await importCancelBtn.click();
  await expect(importCsvOverlay).not.toBeVisible();

  console.log('--- ALL PSLE USER MANAGEMENT UI REDESIGN TESTS PASSED SUCCESSFULLY ---');
});
