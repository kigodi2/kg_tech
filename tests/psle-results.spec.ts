import { test, expect } from '@playwright/test';
import fs from 'fs';

const demoEmail = 'psle-demo-admin@example.com';
const demoPassword = 'password123';
const demoMetadata = JSON.parse(fs.readFileSync('storage/app/testing/psle-lifecycle-demo.json', 'utf8'));

test.describe('PSLE results lifecycle smoke', () => {
  test('navigates lifecycle tabs and performs core actions', async ({ page }) => {
    await page.goto('/login');
    await page.fill('input[name="email"]', demoEmail);
    await page.fill('input[name="password"]', demoPassword);
    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForLoadState('networkidle').catch(() => {}),
    ]);

    await page.goto('/results/psle?year=2026');
    await expect(page.locator('text=PSLE Results').first()).toBeVisible({ timeout: 15000 });

    const tabs = [
      { label: 'Entry & Upload', url: /view=entry-upload/ },
      { label: 'Validation', url: /view=validation/ },
      { label: 'Moderation', url: /view=moderation/ },
      { label: 'Approval', url: /view=approval/ },
      { label: 'Results Browser', url: /view=browser/ },
      { label: 'Publication', url: /view=publication/ },
      { label: 'Amendments', url: /view=amendments/ },
    ];
    const sidebar = page.locator('aside').first();
    const navLink = (label: string) => sidebar.locator('a').filter({ hasText: label }).first();

    for (const tab of tabs) {
      await navLink(tab.label).click();
      await expect(page).toHaveURL(tab.url);
    }

    await navLink('Approval').click();
    page.once('dialog', async (dialog) => dialog.accept('Playwright approval note'));
    await page.getByRole('button', { name: 'Approve' }).first().click();
    await expect(page.locator('text=Approval action recorded successfully.').first()).toBeVisible({ timeout: 15000 });

    const demoContext = {
      schoolId: demoMetadata.school_ids[0],
      resultId: String(demoMetadata.candidate_result_ids[0] ?? ''),
    };

    await expect(demoContext.schoolId).not.toBeNull();
    await page.goto('/results/psle?year=2026&view=publication');
    await page.evaluate(async ({ schoolId }) => {
      const root = document.querySelector('.acsee-lifecycle-shell') as any;
      const data = root?._x_dataStack?.[0];
      if (!data) throw new Error('Alpine PSLE workspace not found');
      data.filters.school_id = String(schoolId);
      data.activeView = 'publication';
      data.updateUrl();
      await data.onViewEnter('publication');
    }, { schoolId: demoContext.schoolId });
    page.once('dialog', async (dialog) => dialog.accept('Playwright publication note'));
    await page.getByRole('button', { name: 'Publish Scope' }).first().click();
    await expect(page.locator('text=PSLE snapshot published successfully.').first()).toBeVisible({ timeout: 15000 });

    page.once('dialog', async (dialog) => dialog.accept('Playwright lock note'));
    await page.getByRole('button', { name: 'Lock Release' }).first().click();
    await expect(page.locator('text=PSLE snapshot locked successfully.').first()).toBeVisible({ timeout: 15000 });

    await page.goto('/results/psle?year=2026&view=amendments');
    await page.evaluate(async ({ schoolId }) => {
      const root = document.querySelector('.acsee-lifecycle-shell') as any;
      const data = root?._x_dataStack?.[0];
      if (!data) throw new Error('Alpine PSLE workspace not found');
      data.filters.school_id = String(schoolId);
      data.activeView = 'amendments';
      data.updateUrl();
      await data.onViewEnter('amendments');
    }, { schoolId: demoContext.schoolId });
    await expect(demoContext.resultId).not.toEqual('');
    await page.fill('input[placeholder="Candidate result ID"]', demoContext.resultId);
    await page.fill('textarea[placeholder="Reason for amendment"]', 'Playwright amendment request');
    await page.fill('textarea[placeholder="Old value / current state"]', 'Demo old value');
    await page.fill('textarea[placeholder="Requested new value"]', 'Demo new value');
    await page.getByRole('button', { name: 'Submit Amendment' }).click();
    await expect(page.locator('text=Amendment request submitted.').first()).toBeVisible({ timeout: 15000 });
  });
});
