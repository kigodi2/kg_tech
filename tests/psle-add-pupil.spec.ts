import { test, expect } from '@playwright/test';

test('verify PSLE Add Pupil flow, modal validations, and duplicate protection', async ({ page }) => {
  test.setTimeout(60000);
  // 1. Login
  await page.goto('/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  // 2. Navigate to PSLE Pupils tab
  await page.goto('/admin/exam-types/psle?tab=pupils');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  // 3. Open Add Pupil modal
  const addPupilBtn = page.locator('button:has-text("Add Pupil")');
  await expect(addPupilBtn).toBeVisible();
  await addPupilBtn.click();

  // Verify modal is visible
  const modalHeader = page.locator('h2:has-text("Register New Pupil")');
  await expect(modalHeader).toBeVisible();

  // 4. Test client-side/form validations
  // Click Register Pupil without filling any field
  const registerBtn = page.locator('button.registration-modal-button-primary:has-text("Register Pupil")');
  await registerBtn.click();

  // Verify client-side validations are shown under inputs
  const errorCandidateId = page.locator('text=Candidate Number is required.');
  await expect(errorCandidateId).toBeVisible();

  const errorFullName = page.locator('text=Pupil Name is required.');
  await expect(errorFullName).toBeVisible();

  // 5. Fill valid test data
  const candidateNum = 'TEST-PSLE-0001';
  const premNo = 'TESTPREM0001';
  const fullName = 'TEST PUPIL ONE';

  const candidateIdInput = page.locator('input[x-model="candidateForm.candidate_id"]');
  const premNoInput = page.locator('input[x-model="candidateForm.prem_no"]');
  const fullNameInput = page.locator('input[x-model="candidateForm.full_name"]');
  const sexSelect = page.locator('select[x-model="candidateForm.gender"]');
  const regionSelect = page.locator('select[x-model="candidateForm.region_id"]');
  const councilSelect = page.locator('select[x-model="candidateForm.district_id"]');
  const schoolSelect = page.locator('select[x-model="candidateForm.school_id"]');

  await candidateIdInput.fill(candidateNum);
  await premNoInput.fill(premNo);
  await fullNameInput.fill(fullName);

  // Select Sex
  await sexSelect.selectOption('M');

  // Verify Region selector is visible and select first non-empty option
  await expect(regionSelect).toBeVisible();
  // We will select by index 1 (usually the first actual region after "Select region")
  await regionSelect.selectOption({ index: 1 });
  await page.waitForTimeout(500);

  // Select Council (should be enabled now)
  await expect(councilSelect).toBeEnabled();
  await councilSelect.selectOption({ index: 1 });
  await page.waitForTimeout(1000); // Wait for schools to load on-demand

  // Select Primary School (should be enabled now)
  await expect(schoolSelect).toBeEnabled();
  await schoolSelect.selectOption({ index: 1 });

  // 6. Submit valid pupil
  await registerBtn.click();

  // Verify modal closes and success message toast or alert is displayed
  await expect(modalHeader).not.toBeVisible();
  
  // Wait a bit for reload
  await page.waitForTimeout(2000);

  // 7. Search for newly added pupil
  const searchInput = page.locator('input[placeholder="Search pupils..."]');
  await expect(searchInput).toBeVisible();
  await searchInput.fill(candidateNum);
  await page.waitForTimeout(2000); // Wait for debounce search load

  // Verify pupil row is visible with correct details
  const cellCandidateId = page.locator(`td:has-text("${candidateNum}")`);
  await expect(cellCandidateId).toBeVisible();

  const cellFullName = page.locator(`td:has-text("${fullName}")`);
  await expect(cellFullName).toBeVisible();

  // 8. Try adding the duplicate candidate to assert 422 warning
  await addPupilBtn.click();
  await expect(modalHeader).toBeVisible();

  // Fill in duplicate details
  await candidateIdInput.fill(candidateNum);
  await premNoInput.fill(premNo);
  await fullNameInput.fill('TEST DUPLICATE NAME');
  await sexSelect.selectOption('F');
  
  // Reselect region/council/school
  await regionSelect.selectOption({ index: 1 });
  await page.waitForTimeout(500);
  await councilSelect.selectOption({ index: 1 });
  await page.waitForTimeout(1000);
  await schoolSelect.selectOption({ index: 1 });

  // Click Register
  await registerBtn.click();

  // Assert duplicate validation message appears inside the modal (does not close)
  const errorGeneral = page.locator('div.text-red-200:has-text("Candidate number is already registered for the active PSLE year.")');
  await expect(errorGeneral).toBeVisible();
  await expect(modalHeader).toBeVisible();

  // Cancel duplicate modal
  await page.click('button:has-text("Cancel")');
  await expect(modalHeader).not.toBeVisible();
  
  console.log('--- ALL PSLE ADD PUPIL TESTS PASSED SUCCESSFULLY ---');
});
