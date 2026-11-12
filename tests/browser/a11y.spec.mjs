import { expect, test } from '@playwright/test';
import { axeViolations, MODES, openComponents, settled, THEMES } from './helpers.mjs';

// Every component, in every theme and color mode, with color contrast checked in a real browser.
for (const theme of THEMES) {
  for (const mode of MODES) {
    test.describe(`${theme} ${mode}`, () => {
      test('all components are ready, with no console errors', async ({ page }) => {
        const errors = await openComponents(page, { theme, mode });
        expect(errors).toEqual([]);
      });

      test('no axe-core violations', async ({ page }) => {
        await openComponents(page, { theme, mode });
        expect(await axeViolations(page)).toEqual([]);
      });

      test('no axe-core violations after failed validation, with the error summary', async ({ page }) => {
        await openComponents(page, { theme, mode });
        await page.locator('#article').getByRole('textbox', { name: /Title/ }).fill('');
        await page.locator('#article').getByRole('button', { name: 'Publish' }).click();
        await expect(page.locator('#article .celerik-error-summary')).toBeVisible();
        expect(await axeViolations(page, { include: '#article' })).toEqual([]);
      });

      test('no axe-core violations with the modal open', async ({ page }) => {
        await openComponents(page, { theme, mode });
        await page.getByRole('button', { name: 'Contact us' }).click();
        await expect(page.getByRole('dialog', { name: 'Contact us' })).toBeVisible();
        await settled(page);
        expect(await axeViolations(page, { include: '.modal.show' })).toEqual([]);
      });

      test('no axe-core violations with a searchable select open, matches highlighted', async ({ page }) => {
        await openComponents(page, { theme, mode });
        await page.getByRole('combobox', { name: 'Reviewer' }).focus();
        await page.keyboard.type('an');
        // The endpoint answers through the test server: allow for a slow machine.
        await expect(page.locator('#preferences-reviewer-ts-dropdown .highlight').first()).toBeVisible({ timeout: 15_000 });
        expect(await axeViolations(page, { include: '#preferences' })).toEqual([]);
      });
    });
  }
}
