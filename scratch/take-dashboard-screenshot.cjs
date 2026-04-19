const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'agreykigodi@gmail.com');
  await page.fill('input[name="password"]', 'Vian@2012');

  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"], button:has-text("Login")'),
  ]);

  await page.goto('http://127.0.0.1:8000/admin/dashboard', { waitUntil: 'networkidle' });
  await page.screenshot({ path: 'scratch/admin-dashboard.png', fullPage: true });

  await browser.close();
  console.log('saved:scratch/admin-dashboard.png');
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
