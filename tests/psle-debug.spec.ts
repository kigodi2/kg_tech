import { test, expect } from '@playwright/test';

test('debug PSLE body direct children layout', async ({ page }) => {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');
  await Promise.all([
    page.click('button[type="submit"]'),
    page.waitForLoadState('networkidle').catch(() => {}),
  ]);

  await page.goto('/admin/exam-types/psle?tab=pupils');
  await page.waitForTimeout(5000);

  const bodyRowChildren = await page.evaluate(() => {
    const root = document.querySelector('.um-body-row');
    if (!root) return 'um-body-row not found';

    return Array.from(root.children).map(el => {
      const rect = el.getBoundingClientRect();
      const style = window.getComputedStyle(el);
      return {
        tagName: el.tagName.toLowerCase(),
        classes: el.className,
        rect: {
          x: rect.x,
          y: rect.y,
          width: rect.width,
          height: rect.height,
        },
        styles: {
          display: style.display,
          position: style.position,
          flex: style.flex,
          width: style.width,
          margin: style.margin,
        }
      };
    });
  });

  console.log('\n--- BODY ROW DIRECT CHILDREN ---');
  console.log(JSON.stringify(bodyRowChildren, null, 2));
  console.log('---------------------------------\n');
});
