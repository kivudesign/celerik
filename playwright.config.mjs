import { defineConfig, devices } from '@playwright/test';

// Browser accessibility tests: npm run test:a11y (after npm run build).
// PLAYWRIGHT_CHANNEL=msedge or chrome uses an installed browser instead of the downloaded
// Chromium (npx playwright install chromium). Playwright starts its own browser instance
// with a temporary profile, so open browser windows are never touched.
const port = Number(process.env.CELERIK_TEST_PORT ?? 8123);

export default defineConfig({
  testDir: 'tests/browser',
  testMatch: '*.spec.mjs',
  fullyParallel: true,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['list']] : 'list',
  timeout: 60_000,
  use: {
    baseURL: `http://127.0.0.1:${port}`,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], channel: process.env.PLAYWRIGHT_CHANNEL || undefined },
    },
  ],
  webServer: {
    command: `php -S 127.0.0.1:${port} tests/browser/server.php`,
    url: `http://127.0.0.1:${port}/`,
    reuseExistingServer: !process.env.CI,
    // PHP's built-in server logs every request on stderr.
    stderr: 'ignore',
  },
});
