import { expect, test } from '@playwright/test';
import { axeViolations, MODES, openComponents, THEMES } from './helpers.mjs';

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

      test('no axe-core violations with the modal open', async ({ page }) => {
        await openComponents(page, { theme, mode });
        await page.getByRole('button', { name: 'Contact us' }).click();
        await expect(page.getByRole('dialog', { name: 'Contact us' })).toBeVisible();
        await page.waitForTimeout(400);
        expect(await axeViolations(page, { include: '.modal.show' })).toEqual([]);
      });
    });
  }
}
