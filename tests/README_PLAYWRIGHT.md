Playwright smoke test for `mark-entry`

Prerequisites

- Node.js (16+ recommended)
- Browsers will be installed by Playwright
- Local dev server running (example below uses Laravel's built-in server)

Install and run

```bash
cd /home/prosmart-technologies/SOL/irms
# install dev deps
npm install
# install browsers (Playwright)
npx playwright install

# start Laravel dev server in a separate terminal
php artisan serve --host=127.0.0.1 --port=8000

# run the smoke test
npx playwright test tests/mark-entry.spec.ts --project=chromium
```

What the test checks

- Sidebar navigation items trigger `?view=...` URL updates
- Active view's heading / breadcrumb appears
- Back/forward browser navigation restores views

Notes

- If your app runs on a different host/port, edit `playwright.config.ts` baseURL.
- Running Playwright in headed mode: add `--headed` to the `npx playwright test` command.
