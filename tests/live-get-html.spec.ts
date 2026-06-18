import { test, expect } from '@playwright/test';
import * as path from 'path';

test('capture HTML response from live validation', async ({ page }) => {
  console.log('Navigating to login...');
  await page.goto('https://irms.ac.tz/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForURL(/.*admin.*/).catch(() => {}),
  ]);

  console.log('Logged in successfully!');

  await page.goto('https://irms.ac.tz/admin/exam-types/psle?tab=pupils');
  await page.waitForLoadState('networkidle');
  console.log('Navigated to PSLE page');

  // Monitor network response
  page.on('response', async response => {
    if (response.url().includes('import/validate')) {
      console.log('\n========================================');
      console.log('<< RESPONSE RECEIVED:', response.url());
      console.log('Status Code:', response.status());
      console.log('Headers:', JSON.stringify(response.headers(), null, 2));
      try {
        const text = await response.text();
        console.log('Response Body:', text);
      } catch (e) {
        console.log('Could not read response body:', e.message);
      }
      console.log('========================================\n');
    }
  });

  // Open Tools modal
  await page.click('button:has-text("Tools")');
  await page.waitForTimeout(500);

  // Click Import Pupils button
  await page.click('button:has-text("Import Pupils")');
  await page.waitForTimeout(500);

  // Upload file
  const fileInput = await page.locator('#psle-import-file-input');
  const filePath = path.resolve('tests/temp_test.csv');
  await fileInput.setInputFiles(filePath);
  console.log('Uploaded temp_test.csv');

  await page.waitForTimeout(500);

  // Click Validate File button
  console.log('Clicking Validate File...');
  await page.click('button:has-text("Validate File")');

  // Wait a few seconds to let the response complete
  await page.waitForTimeout(8000);
});
