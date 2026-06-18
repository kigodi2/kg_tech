import { test, expect } from '@playwright/test';

test('verify PSLE manual school registration, validation, edit flow, and cleanup', async ({ page }) => {
  test.setTimeout(60000);

  // Handle confirm dialogs automatically for deletes
  page.on('dialog', async (dialog) => {
    expect(dialog.message()).toContain('Delete this school record?');
    await dialog.accept();
  });

  // 1. Login
  await page.goto('/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  // 2. Navigate to PSLE Schools tab
  await page.goto('/admin/exam-types/psle?tab=schools');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  // 3. Open Add School modal
  const addSchoolBtn = page.locator('button:has-text("Add School")');
  await expect(addSchoolBtn).toBeVisible();
  await addSchoolBtn.click();

  // Verify modal is visible
  const modalHeader = page.locator('h2:has-text("Add Primary School")');
  await expect(modalHeader).toBeVisible();

  // 4. Test client-side/form validations
  // Click Save School without filling any field
  const saveBtn = page.locator('button.registration-modal-button-primary:has-text("Save School")');
  await saveBtn.click();

  // Verify client-side validations are shown under inputs
  const errorCode = page.locator('text=School code is required.');
  await expect(errorCode).toBeVisible();

  const errorName = page.locator('text=School name is required.');
  await expect(errorName).toBeVisible();

  // 5. Fill valid manual school test data
  const schoolCode = 'TESTPSLE001';
  const schoolName = 'TEST PSLE SCHOOL';

  const codeInput = page.locator('input[x-model="schoolForm.code"]');
  const nameInput = page.locator('input[x-model="schoolForm.name"]');
  const ownershipSelect = page.locator('select[x-model="schoolForm.ownership"]');
  const regionSelect = page.locator('select[x-model="schoolForm.region_id"]');
  const councilSelect = page.locator('select[x-model="schoolForm.district_id"]');

  await codeInput.fill(schoolCode);
  await nameInput.fill(schoolName);
  await ownershipSelect.selectOption('GOVERNMENT');

  // Select first region
  await expect(regionSelect).toBeVisible();
  await regionSelect.selectOption({ index: 1 });
  await page.waitForTimeout(500);

  // Select first council
  await expect(councilSelect).toBeEnabled();
  await councilSelect.selectOption({ index: 1 });
  await page.waitForTimeout(500);

  // 6. Submit manual school registration
  await saveBtn.click();

  // Verify modal closes
  await expect(modalHeader).not.toBeVisible();
  await page.waitForTimeout(1000);

  // 7. Search for newly added school
  const searchInput = page.locator('input[placeholder="Search primary schools..."]');
  await expect(searchInput).toBeVisible();
  await searchInput.fill(schoolCode);
  await page.waitForTimeout(1500); // Wait for debounce filter reload

  // Verify school row is visible with correct details
  const cellSchoolCode = page.locator(`td:has-text("${schoolCode}")`);
  await expect(cellSchoolCode).toBeVisible();

  const cellSchoolName = page.locator(`td:has-text("${schoolName}")`);
  await expect(cellSchoolName).toBeVisible();

  // Verify candidate count column renders 0
  const cellCandidates = page.locator('td.text-center:has-text("0")');
  await expect(cellCandidates).toBeVisible();

  // 8. Open Edit School modal
  const editSchoolBtn = page.locator('button[title="Edit School"]');
  await expect(editSchoolBtn).toBeVisible();
  await editSchoolBtn.click();

  // Verify edit modal is visible with prefilled values
  const editModalHeader = page.locator('h2:has-text("Edit Primary School")');
  await expect(editModalHeader).toBeVisible();
  await expect(codeInput).toHaveValue(schoolCode);
  await expect(nameInput).toHaveValue(schoolName);

  // Verify region and council select elements are enabled (since candidates = 0)
  await expect(regionSelect).toBeEnabled();
  await expect(councilSelect).toBeEnabled();

  // 9. Modify name and submit
  const updatedSchoolName = 'TEST PSLE SCHOOL UPDATED';
  await nameInput.fill(updatedSchoolName);

  const updateBtn = page.locator('button.registration-modal-button-primary:has-text("Update School")');
  await updateBtn.click();

  // Verify modal closes
  await expect(editModalHeader).not.toBeVisible();
  await page.waitForTimeout(1000);

  // 10. Verify update is reflected in the table
  const cellUpdatedSchoolName = page.locator(`td:has-text("${updatedSchoolName}")`);
  await expect(cellUpdatedSchoolName).toBeVisible();

  // 11. Delete manual school record for clean workspace
  const deleteBtn = page.locator('button[title="Delete School"]');
  await expect(deleteBtn).toBeVisible();
  await deleteBtn.click();

  // Wait for deletion reload
  await page.waitForTimeout(2000);

  // Verify the school is removed from the UI list
  await expect(cellSchoolCode).not.toBeVisible();

  console.log('--- ALL PSLE SCHOOLS SYSTEM TESTS PASSED SUCCESSFULLY ---');
});
