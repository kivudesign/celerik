import { expect, test } from '@playwright/test';
import { axeViolations, MODES, openComponents, THEMES } from './helpers.mjs';

// The ThemeBuilder in a real browser: live preview of real components, base look, report.
const open = (page, options = {}) => openComponents(page, { path: '/theme-builder', ...options });
const style = (page, selector, property) => page.locator(selector).first()
  .evaluate((element, name) => getComputedStyle(element)[name], property);
const canvas = '#builder .celerik-theme-builder__canvas';

test('opens with no console errors', async ({ page }) => {
  expect(await open(page)).toEqual([]);
});

test('a color change restyles the preview, not the builder controls', async ({ page }) => {
  await open(page);
  const field = page.getByRole('textbox', { name: 'Primary' }).first();
  await field.fill('#0f766e');

  await expect.poll(() => style(page, `${canvas} .btn-primary`, 'backgroundColor')).toBe('rgb(15, 118, 110)');
  expect(await style(page, `${canvas} #preview-grid_wrapper .page-item.active .page-link`, 'backgroundColor')).toBe('rgb(15, 118, 110)');
  expect(await style(page, `${canvas} .nav-link.active`, 'color')).not.toBe('');
  // The builder's own button keeps the page's primary color.
  expect(await style(page, '#builder [data-celerik-action="preset"]', 'borderColor')).not.toBe('rgb(15, 118, 110)');
  expect(await page.getByRole('textbox', { name: 'Primary' }).first().inputValue()).toBe('#0f766e');
});

test('the light/dark switch changes the preview only', async ({ page }) => {
  await open(page);
  await page.getByText('Dark', { exact: true }).click();

  await expect.poll(() => style(page, canvas, 'backgroundColor')).toBe('rgb(33, 37, 41)'); // bootstrap dark bodyBg
  expect(await style(page, 'body', 'backgroundColor')).toBe('rgb(255, 255, 255)');
});

test('the base look switches the add-on stylesheet', async ({ page }) => {
  await open(page);
  await page.getByLabel('Base look').selectOption('material');

  await expect.poll(() => page.evaluate(() => [...document.querySelectorAll('link[rel=stylesheet]')]
    .filter((link) => link.href.includes('celerik-material.css') && !link.disabled).length)).toBe(1);
  // Material's pill buttons (1.25rem = 20px) apply in the preview.
  await expect.poll(() => style(page, `${canvas} .btn-primary`, 'borderTopLeftRadius')).toBe('20px');

  await page.getByLabel('Base look').selectOption('bootstrap');
  await expect.poll(() => page.evaluate(() => [...document.querySelectorAll('link[rel=stylesheet]')]
    .filter((link) => link.href.includes('celerik-material.css') && !link.disabled).length)).toBe(0);
});

test('the contrast report updates live and announces only its summary', async ({ page }) => {
  await open(page);
  const summary = page.locator('#builder .celerik-theme-builder__summary');
  await expect(summary).toHaveText('Every pair meets its minimum.');
  await expect(summary).toHaveAttribute('role', 'status');

  await page.getByRole('textbox', { name: 'Muted text' }).first().fill('#aaaaaa');
  await expect(summary).toHaveText('2 pairs are below their minimum.');
  await expect(page.locator('#builder li.is-fail')).toHaveCount(2);
  await expect(page.locator('#builder li.is-fail').first()).toContainText('Fail Light · Muted text');
});

test('presets, undo and reset', async ({ page }) => {
  await open(page);
  await page.getByLabel('Start from a preset').selectOption('tailwind');
  await page.getByRole('button', { name: 'Load preset' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary' }).first()).toHaveValue('#4f46e5');
  await expect(page.getByLabel('Base look')).toHaveValue('tailwind');

  const history = page.locator('#builder .celerik-theme-builder__history');
  await history.getByRole('button', { name: 'Undo' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary' }).first()).toHaveValue('#0d6efd');

  await page.getByRole('textbox', { name: 'Primary' }).first().fill('#123456');
  await page.getByRole('textbox', { name: 'Primary' }).first().press('Tab');
  await history.getByRole('button', { name: 'Reset' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary' }).first()).toHaveValue('#0d6efd');
});

for (const theme of THEMES) {
  for (const mode of MODES) {
    test(`no axe-core violations (${theme} ${mode})`, async ({ page }) => {
      await open(page, { theme, mode });
      expect(await axeViolations(page)).toEqual([]);
    });
  }
}
