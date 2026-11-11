import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { demoNames, sitePages, WCAG_TAGS } from './helpers.mjs';

// The built documentation site: npm run docs:build && npm run test:site.
const pages = sitePages();
const demos = demoNames();

async function setMode(page, mode) {
  await page.addInitScript((dark) => {
    window.localStorage.setItem('vitepress-theme-appearance', dark ? 'dark' : 'light');
  }, mode === 'dark');
}

async function axeViolations(page, build = (axe) => axe) {
  const results = await build(new AxeBuilder({ page }).withTags(WCAG_TAGS)).analyze();
  return results.violations.flatMap((violation) => violation.nodes.map(
    (node) => `${violation.id}: ${node.target.join(' ')} (${node.failureSummary?.split('\n').slice(1).join(' ').trim()})`,
  ));
}

for (const mode of ['light', 'dark']) {
  test.describe(`pages, ${mode} mode`, () => {
    test.beforeEach(async ({ page }) => setMode(page, mode));

    for (const path of pages) {
      test(`/${path} has no axe-core violations`, async ({ page }) => {
        await page.goto(path);
        await expect(page.locator('.VPContent')).toBeVisible();
        // Demos are checked on their own pages below, in every look and mode.
        expect(await axeViolations(page, (axe) => axe.exclude('.celerik-demo iframe'))).toEqual([]);
      });
    }
  });
}

for (const name of demos) {
  for (const theme of ['bootstrap', 'material', 'tailwind']) {
    for (const mode of ['light', 'dark']) {
      test(`demo ${name} (${theme} ${mode}) starts with no errors or axe-core violations`, async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => message.type() === 'error' && errors.push(message.text()));
        // The Scheduler demo shows November 2026: a fixed date keeps its tinted today
        // cell in view, so the result does not depend on the day the tests run.
        await page.clock.setFixedTime(new Date('2026-11-11T10:00:00Z'));
        await page.addInitScript(() => {
          window.celerikReady = 0;
          document.addEventListener('celerik:ready', () => {
            window.celerikReady += 1;
          });
        });

        await page.goto(`demos/${name}.html?theme=${theme}&mode=${mode}`);
        await page.waitForFunction(() => window.celerikReady === document.querySelectorAll('[data-celerik]').length, null, { timeout: 30_000 });
        await page.waitForTimeout(800);

        expect(errors).toEqual([]);
        expect(await page.evaluate(() => document.documentElement.dataset.bsTheme)).toBe(mode);
        expect(await axeViolations(page, (axe) => axe.include('main'))).toEqual([]);
      });
    }
  }
}

test('internal links and anchors lead somewhere', async ({ page, request }) => {
  test.setTimeout(300_000);
  const targets = new Map();
  for (const path of pages) {
    await page.goto(path);
    const hrefs = await page.locator('.VPContent a[href], .VPSidebar a[href], .VPNav a[href]').evaluateAll(
      (links) => links.map((link) => link.href),
    );
    for (const href of hrefs) {
      const url = new URL(href);
      if (url.origin === new URL(page.url()).origin && !targets.has(url.href)) {
        targets.set(url.href, `/${path}`);
      }
    }
  }

  const broken = [];
  const anchors = new Map();
  for (const [href, from] of targets) {
    const url = new URL(href);
    url.hash = '';
    if (!anchors.has(url.href)) {
      const response = await request.get(url.href);
      if (!response.ok()) {
        broken.push(`${href} (from ${from}): HTTP ${response.status()}`);
        anchors.set(url.href, null);
        continue;
      }
      const html = await response.text();
      anchors.set(url.href, new Set([...html.matchAll(/\sid="([^"]+)"/g)].map((match) => match[1])));
    }
    const hash = decodeURIComponent(new URL(href).hash.slice(1));
    const ids = anchors.get(url.href);
    if (hash && ids && !ids.has(hash)) {
      broken.push(`${href} (from ${from}): no #${hash}`);
    }
  }
  expect(broken).toEqual([]);
  expect(targets.size).toBeGreaterThan(50);
});

test.describe('keyboard', () => {
  test('the skip link moves focus to the content', async ({ page }) => {
    await page.goto('guide/getting-started');
    await page.keyboard.press('Tab');
    const skip = page.getByRole('link', { name: 'Skip to content' });
    await expect(skip).toBeFocused();
    await page.keyboard.press('Enter');
    await page.keyboard.press('Tab');
    expect(await page.evaluate(() => document.activeElement.closest('#VPContent, .VPContent') !== null)).toBe(true);
  });

  test('search opens from the keyboard and finds a component', async ({ page }) => {
    await page.goto('');
    await expect(page.locator('.DocSearch-Button')).toBeVisible();
    await page.keyboard.press('Control+k'); // lowercase: VitePress listens for "k"
    const input = page.getByRole('searchbox').or(page.getByPlaceholder(/search/i)).first();
    await expect(input).toBeFocused();
    await page.keyboard.type('scheduler');
    await expect(page.locator('.VPLocalSearchBox .result').first()).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('.VPLocalSearchBox')).toHaveCount(0);
  });

  test('a demo can switch its look from the keyboard', async ({ page }) => {
    await page.goto('components/tabs');
    const look = page.getByLabel('Look');
    await look.focus();
    await look.selectOption('material');
    await expect(page.locator('.celerik-demo iframe')).toHaveAttribute('src', /theme=material/);
    await expect(page.locator('.celerik-demo iframe')).toHaveAttribute('title', 'Demo: Tabs');
  });
});
