import { expect, test } from '@playwright/test';
import { openComponents } from './helpers.mjs';

test.describe('prefers-reduced-motion: reduce', () => {
  test.use({ reducedMotion: 'reduce' });

  test('charts, toasts and the modal do not animate', async ({ page }) => {
    await openComponents(page);

    expect(await page.evaluate(() => document.querySelector('#revenue').celerik.options.animation)).toBe(false);

    const toast = page.locator('.notyf__toast').first();
    expect(await toast.evaluate((element) => getComputedStyle(element).animationDuration)).toBe('0.001s');

    // Toasts still close: Notyf removes them on animationend.
    const count = await page.locator('.notyf__toast').count();
    await page.getByRole('button', { name: /Dismiss/i }).first().click();
    await expect(page.locator('.notyf__toast')).toHaveCount(count - 1);

    await page.getByRole('button', { name: 'Contact us' }).click();
    const dialog = page.locator('.modal-dialog');
    expect(await dialog.evaluate((element) => getComputedStyle(element).transitionDuration)).toBe('0s');
  });
});

test('without the preference, charts and toasts keep their animations', async ({ page }) => {
  await openComponents(page);
  expect(await page.evaluate(() => document.querySelector('#revenue').celerik.options.animation)).not.toBe(false);
  expect(await page.locator('.notyf__toast').first().evaluate((element) => getComputedStyle(element).animationDuration))
    .toBe('0.3s');
});
