import { defineConfig, devices } from '@playwright/test';

// Checks of the built documentation site: npm run docs:build, then npm run test:site.
// PLAYWRIGHT_CHANNEL=msedge or chrome uses an installed browser (see playwright.config.mjs).
const port = Number(process.env.CELERIK_SITE_PORT ?? 4174);

export default defineConfig({
  testDir: 'tests/site',
  testMatch: '*.spec.mjs',
  fullyParallel: true,
  // GitHub's runners have 4 CPUs; Playwright's default would use 2.
  workers: process.env.CI ? 4 : undefined,
  forbidOnly: Boolean(process.env.CI),
  retries: process.env.CI ? 1 : 0,
  reporter: process.env.CI ? [['github'], ['list']] : 'list',
  timeout: 120_000,
  use: {
    baseURL: `http://localhost:${port}/celerik/`,
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
    command: `npx vitepress preview site --port ${port}`,
    url: `http://localhost:${port}/celerik/`,
    reuseExistingServer: !process.env.CI,
  },
});
