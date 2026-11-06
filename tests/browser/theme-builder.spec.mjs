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
  const field = page.getByRole('textbox', { name: 'Primary', exact: true }).first();
  await field.fill('#0f766e');

  await expect.poll(() => style(page, `${canvas} .btn-primary`, 'backgroundColor')).toBe('rgb(15, 118, 110)');
  expect(await style(page, `${canvas} #preview-grid_wrapper .page-item.active .page-link`, 'backgroundColor')).toBe('rgb(15, 118, 110)');
  expect(await style(page, `${canvas} .nav-link.active`, 'color')).not.toBe('');
  // The builder's own button keeps the page's primary color.
  expect(await style(page, '#builder [data-celerik-action="preset"]', 'borderColor')).not.toBe('rgb(15, 118, 110)');
  expect(await page.getByRole('textbox', { name: 'Primary', exact: true }).first().inputValue()).toBe('#0f766e');
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

  await page.getByRole('textbox', { name: 'Muted text', exact: true }).first().fill('#aaaaaa');
  await expect(summary).toHaveText('2 pairs are below their minimum.');
  await expect(page.locator('#builder li.is-fail')).toHaveCount(2);
  await expect(page.locator('#builder li.is-fail').first()).toContainText('Fail Light · Muted text');
});

test('presets, undo and reset', async ({ page }) => {
  await open(page);
  await page.getByLabel('Start from a preset').selectOption('tailwind');
  await page.getByRole('button', { name: 'Load preset' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary', exact: true }).first()).toHaveValue('#4f46e5');
  await expect(page.getByLabel('Base look')).toHaveValue('tailwind');

  const history = page.locator('#builder .celerik-theme-builder__history');
  await history.getByRole('button', { name: 'Undo' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary', exact: true }).first()).toHaveValue('#0d6efd');

  await page.getByRole('textbox', { name: 'Primary', exact: true }).first().fill('#123456');
  await page.getByRole('textbox', { name: 'Primary', exact: true }).first().press('Tab');
  await history.getByRole('button', { name: 'Reset' }).click();
  await expect(page.getByRole('textbox', { name: 'Primary', exact: true }).first()).toHaveValue('#0d6efd');
});

for (const theme of THEMES) {
  for (const mode of MODES) {
    test(`no axe-core violations (${theme} ${mode})`, async ({ page }) => {
      await open(page, { theme, mode });
      expect(await axeViolations(page)).toEqual([]);
    });
  }
}

test.describe('export, import and save', () => {
  test('the downloaded CSS is what Theme::toCss() writes for the downloaded JSON', async ({ page, request }) => {
    await open(page);
    await page.getByRole('textbox', { name: 'Theme name' }).fill('Acme Café');
    await page.getByRole('textbox', { name: 'Primary', exact: true }).first().fill('#0f766e');
    await page.getByLabel('Base look').selectOption('tailwind');

    const [cssDownload] = await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Download CSS' }).click()]);
    const [jsonDownload] = await Promise.all([page.waitForEvent('download'), page.getByRole('button', { name: 'Download JSON' }).click()]);
    expect(cssDownload.suggestedFilename()).toBe('acme-cafe.css');
    expect(jsonDownload.suggestedFilename()).toBe('acme-cafe.json');

    const read = async (download) => (await import('node:fs/promises')).readFile(await download.path(), 'utf8');
    const [css, json] = [await read(cssDownload), await read(jsonDownload)];
    expect(JSON.parse(json)).toMatchObject({ name: 'Acme Café', base: 'tailwind', light: { primary: '#0f766e' } });

    const php = await request.post('/theme-css', { data: json, headers: { 'Content-Type': 'application/json' } });
    expect(await php.text()).toBe(css);
  });

  test('importing a theme file fills the builder; a bad file is reported', async ({ page }) => {
    await open(page);
    const input = page.locator('#builder-import');
    const message = page.locator('#builder .celerik-theme-builder__message');

    await input.setInputFiles({
      name: 'teal.json',
      mimeType: 'application/json',
      buffer: (await import('node:fs')).readFileSync(new URL('./custom-theme.json', import.meta.url)),
    });
    await expect(message).toHaveText('Theme imported.');
    await expect(page.getByRole('textbox', { name: 'Theme name' })).toHaveValue('Teal test');
    await expect(page.getByRole('textbox', { name: 'Primary', exact: true }).first()).toHaveValue('#0f766e');

    await input.setInputFiles({ name: 'bad.json', mimeType: 'application/json', buffer: Buffer.from('{"name": "X", "base": "bulma"}') });
    await expect(message).toHaveText('This file is not a valid theme: Unknown base "bulma". Available: bootstrap, material, tailwind.');
    await expect(page.getByRole('textbox', { name: 'Theme name' })).toHaveValue('Teal test');
  });

  test('Copy PHP puts a snippet on the clipboard', async ({ page, context }) => {
    await context.grantPermissions(['clipboard-read', 'clipboard-write']);
    await open(page);
    await page.getByRole('button', { name: 'Copy PHP' }).click();

    await expect(page.locator('#builder .celerik-theme-builder__message')).toHaveText('Copied.');
    // The Windows clipboard turns line feeds into CRLF.
    const snippet = (await page.evaluate(() => navigator.clipboard.readText())).replaceAll('\r\n', '\n');
    expect(snippet).toContain("$theme = Theme::fromJson(<<<'JSON'\n{\n    \"name\": \"Bootstrap\",");
  });

  test('Save posts the theme as JSON with the CSRF token', async ({ page, request }) => {
    await open(page);
    await page.getByRole('textbox', { name: 'Theme name' }).fill('Saved with JS');
    await page.getByRole('button', { name: 'Save theme' }).click();

    await expect(page.locator('#builder .celerik-theme-builder__message')).toHaveText('Theme saved.');
    const saved = await (await request.get('/theme-saved', { params: { name: 'Saved with JS' } })).json();
    expect(saved).toMatchObject({ name: 'Saved with JS', base: 'bootstrap' });
  });
});

test.describe('without JavaScript', () => {
  test.use({ javaScriptEnabled: false });

  test('the builder is a form that saves the theme, with a contrast report from PHP', async ({ page, request }) => {
    await page.goto('/theme-builder?theme=material');
    await expect(page.locator('#builder .celerik-theme-builder__summary')).toHaveText('Every pair meets its minimum.');
    for (const part of ['__preset', '__history', '__export', '__modes']) {
      await expect(page.locator(`#builder .celerik-theme-builder${part}`)).toBeHidden();
    }

    await page.getByRole('textbox', { name: 'Theme name' }).fill('Saved without JS');
    await page.getByRole('textbox', { name: 'Primary', exact: true }).first().fill('#0b5394');
    await page.getByRole('button', { name: 'Save theme' }).click();

    await expect(page).toHaveURL(/\/theme-builder\?theme=material$/);
    const saved = await (await request.get('/theme-saved', { params: { name: 'Saved without JS' } })).json();
    expect(saved).toMatchObject({ name: 'Saved without JS', base: 'material', light: { primary: '#0b5394' } });
  });
});
