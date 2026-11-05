import { expect, test } from '@playwright/test';
import { openComponents } from './helpers.mjs';

test.describe('prefers-reduced-motion: reduce', () => {
  test.use({ reducedMotion: 'reduce' });

  test('charts, toasts and the modal do not animate', async ({ page }) => {
    await openComponents(page);

    expect(await page.evaluate(() => document.querySelector('#revenue').celerik.options.animation)).toBe(false);

    // The error toast stays until dismissed; the others may expire during the test.
    const toast = page.locator('.celerik-toast-error');
    expect(await toast.evaluate((element) => getComputedStyle(element).animationDuration)).toBe('0.001s');

    // Toasts still close: Notyf removes them on animationend.
    await toast.getByRole('button', { name: /Dismiss/i }).click();
    await expect(toast).toHaveCount(0);

    await page.getByRole('button', { name: 'Contact us' }).click();
    const dialog = page.locator('.modal-dialog');
    expect(await dialog.evaluate((element) => getComputedStyle(element).transitionDuration)).toBe('0s');
  });
});

test('without the preference, charts and toasts keep their animations', async ({ page }) => {
  await openComponents(page);
  expect(await page.evaluate(() => document.querySelector('#revenue').celerik.options.animation)).not.toBe(false);
  expect(await page.locator('.celerik-toast-error').evaluate((element) => getComputedStyle(element).animationDuration))
    .toBe('0.3s');
});
