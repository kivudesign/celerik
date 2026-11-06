import { expect, test } from '@playwright/test';
import { axeViolations, MODES, openComponents, THEMES } from './helpers.mjs';

// The ThemeBuilder used by keyboard only, in forced-colors mode, and the theme it exports
// checked on every component.
const open = (page, options = {}) => openComponents(page, { path: '/theme-builder', ...options });

// Presses Tab until `locator` has focus (at most `limit` times).
async function tabTo(page, locator, limit = 80) {
  for (let i = 0; i < limit; i += 1) {
    if (await locator.evaluate((element) => element === document.activeElement)) {
      return;
    }
    await page.keyboard.press('Tab');
  }
  throw new Error(`Tab never reached ${locator}`);
}

test('keyboard only: edit a color, switch the preview mode, undo, export', async ({ page }) => {
  await open(page);
  await page.locator('h1').evaluate((heading) => {
    heading.tabIndex = -1;
    heading.focus();
  });

  const primary = page.getByRole('textbox', { name: 'Primary', exact: true }).first();
  await tabTo(page, primary);
  await page.keyboard.press('Control+A');
  await page.keyboard.type('#0f766e');
  await page.keyboard.press('Tab'); // "change": one undo step
  await expect.poll(() => page.locator('#builder .celerik-theme-builder__canvas .btn-primary').first()
    .evaluate((button) => getComputedStyle(button).backgroundColor)).toBe('rgb(15, 118, 110)');

  // The color pickers are skipped (tabindex -1): every color has a hex field instead.
  expect(await page.locator('#builder input[type=color]').evaluateAll((pickers) => pickers.every((p) => p.tabIndex === -1)))
    .toBe(true);

  const light = page.getByRole('radio', { name: 'Light' });
  await tabTo(page, light);
  await page.keyboard.press('ArrowRight');
  await expect(page.getByRole('radio', { name: 'Dark' })).toBeChecked();
  await expect(page.locator('#builder .celerik-theme-builder__canvas')).toHaveAttribute('data-bs-theme', 'dark');

  const undo = page.locator('#builder .celerik-theme-builder__history').getByRole('button', { name: 'Undo' });
  await undo.focus();
  await page.keyboard.press('Enter');
  await expect(primary).toHaveValue('#0d6efd');
  await expect(page.getByRole('textbox', { name: 'Theme name' })).toBeFocused(); // Undo became disabled

  await tabTo(page, page.getByRole('button', { name: 'Download CSS' }));
  const [download] = await Promise.all([page.waitForEvent('download'), page.keyboard.press('Enter')]);
  expect(download.suggestedFilename()).toBe('bootstrap.css');
});

test('the import button is reachable and opens the file chooser from the keyboard', async ({ page }) => {
  await open(page);
  const input = page.locator('#builder-import');
  await input.focus(); // the visually hidden input behind the "Import JSON" label
  const [chooser] = await Promise.all([page.waitForEvent('filechooser'), page.keyboard.press('Enter')]);
  expect(chooser.isMultiple()).toBe(false);
});

test.describe('forced colors', () => {
  test.use({ forcedColors: 'active' });

  test('focus stays visible on every tab stop of the builder', async ({ page }) => {
    test.setTimeout(120_000);
    await open(page);
    await page.getByRole('textbox', { name: 'Theme name' }).focus();
    const invisible = new Set();
    for (let i = 0; i < 60; i += 1) {
      const result = await page.evaluate(() => {
        const element = document.activeElement;
        if (!element?.closest('.celerik-theme-builder__controls')) {
          return null;
        }
        const style = getComputedStyle(element);
        return style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0 ? null : element.id || element.className;
      });
      if (result) {
        invisible.add(result);
      }
      await page.keyboard.press('Tab');
    }
    expect([...invisible]).toEqual([]);
  });
});

// A theme edited in the builder, exported and loaded on the page with every component.
for (const base of THEMES) {
  for (const mode of MODES) {
    test(`an exported theme passes axe-core on every component (${base} ${mode})`, async ({ page }) => {
      await open(page, { theme: base });
      await page.getByRole('textbox', { name: 'Theme name' }).fill('Exported');
      await page.getByRole('textbox', { name: 'Primary', exact: true }).first().fill('#0f766e');
      await page.getByRole('textbox', { name: 'Primary, hovered', exact: true }).first().fill('#115e59');
      await page.getByRole('textbox', { name: 'Primary', exact: true }).nth(1).fill('#5eead4');
      await page.getByRole('textbox', { name: 'Primary, hovered', exact: true }).nth(1).fill('#99f6e4');
      await page.getByRole('textbox', { name: 'Text on primary', exact: true }).nth(1).fill('#042f2e');
      await expect(page.locator('#builder .celerik-theme-builder__summary')).toHaveText('Every pair meets its minimum.');

      const [download] = await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Download CSS' }).click()]);
      const css = await (await import('node:fs/promises')).readFile(await download.path(), 'utf8');

      // Serve the exported stylesheet as the page's custom theme.
      await page.route('**/custom-theme.css*', (route) => route.fulfill({ contentType: 'text/css', body: css }));
      await openComponents(page, { theme: base, mode, custom: true });
      expect(await page.locator('#signup .btn-primary').evaluate((button) => getComputedStyle(button).backgroundColor))
        .toBe(mode === 'light' ? 'rgb(15, 118, 110)' : 'rgb(94, 234, 212)');
      expect(await axeViolations(page)).toEqual([]);
    });
  }
}
