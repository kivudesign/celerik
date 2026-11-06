import { expect, test } from '@playwright/test';
import { axeViolations, MODES, openComponents, THEMES } from './helpers.mjs';

// Changing, creating and deleting events without dragging (WCAG 2.1.1, 2.5.7).
const event = (page, title) => page.locator('#team-calendar .fc-event', { hasText: title });

test('Enter on an event opens the dialog; new dates are saved and focus returns', async ({ page }) => {
  await openComponents(page);
  const requests = [];
  page.on('request', (request) => {
    if (request.url().includes('/events/')) {
      requests.push({ url: new URL(request.url()).pathname, body: request.postDataJSON(), csrf: request.headers()['x-csrf-token'] });
    }
  });

  await event(page, 'Kick-off').focus();
  await page.keyboard.press('Enter');

  const dialog = page.getByRole('dialog', { name: 'Edit "Kick-off"' });
  await expect(dialog).toBeVisible();
  await expect(dialog.getByLabel('Start')).toBeFocused();

  await dialog.getByLabel('Start').fill('2026-11-10T09:00');
  await dialog.getByLabel('End').fill('2026-11-10T10:30');
  await dialog.getByRole('button', { name: 'Save' }).press('Enter');

  await expect(dialog).toBeHidden();
  await expect(page.locator('#team-calendar td[data-date="2026-11-10"] .fc-event')).toContainText('Kick-off');
  await expect(event(page, 'Kick-off')).toBeFocused();
  expect(requests).toEqual([{
    url: '/events/update',
    body: expect.objectContaining({ id: '1', start: '2026-11-10T09:00:00Z', end: '2026-11-10T10:30:00Z' }),
    csrf: 'test-token',
  }]);
});

test('the dialog explains invalid dates and keeps focus; Escape cancels', async ({ page }) => {
  await openComponents(page);
  await event(page, 'Kick-off').focus();
  await page.keyboard.press('Enter');
  const dialog = page.getByRole('dialog', { name: 'Edit "Kick-off"' });

  await dialog.getByLabel('End').fill('2026-11-01T08:00');
  await dialog.getByRole('button', { name: 'Save' }).click();
  await expect(dialog.getByRole('alert')).toHaveText('The end must be after the start.');
  await expect(dialog.getByLabel('End')).toBeFocused();
  await expect(dialog.getByLabel('End')).toHaveAttribute('aria-invalid', 'true');

  await page.keyboard.press('Escape');
  await expect(dialog).toBeHidden();
  await expect(event(page, 'Kick-off')).toBeFocused();
  await expect(page.locator('#team-calendar td[data-date="2026-11-03"] .fc-event')).toContainText('Kick-off');
});

test('the New event button creates an event from the keyboard', async ({ page }) => {
  await openComponents(page);
  await page.getByRole('button', { name: 'New event' }).focus();
  await page.keyboard.press('Enter');

  const dialog = page.getByRole('dialog', { name: 'New event' });
  await expect(dialog.getByLabel('Title')).toBeFocused();
  await page.keyboard.type('Retro');
  await dialog.getByLabel('All day').check();
  await dialog.getByLabel('Start').fill('2026-11-12');
  await dialog.getByLabel('End').fill('2026-11-13');
  await page.keyboard.press('Enter');

  await expect(dialog).toBeHidden();
  const created = event(page, 'Retro');
  await expect(created).toHaveCount(1);
  await expect(page.locator('#team-calendar td[data-date="2026-11-12"]')).toContainText('Retro');
  await expect(created.first()).toBeFocused();
});

test('Delete in the dialog removes the event after confirmation', async ({ page }) => {
  await openComponents(page);
  page.on('dialog', (confirm) => confirm.accept());
  await event(page, 'Holiday').focus();
  await page.keyboard.press('Enter');
  await page.getByRole('dialog', { name: 'Edit "Holiday"' }).getByRole('button', { name: 'Delete' }).click();
  await expect(event(page, 'Holiday')).toHaveCount(0);
});

for (const theme of THEMES) {
  for (const mode of MODES) {
    test(`no axe-core violations in the event dialog (${theme} ${mode})`, async ({ page }) => {
      await openComponents(page, { theme, mode });
      await event(page, 'Kick-off').focus();
      await page.keyboard.press('Enter');
      await expect(page.getByRole('dialog')).toBeVisible();
      expect(await axeViolations(page, { include: '.celerik-scheduler-dialog' })).toEqual([]);

      // axe can leave the dialog's contrast "incomplete" (it sits in the top layer, over a
      // backdrop), so the buttons are also measured directly: text on the button, or on
      // the dialog for transparent (outline) buttons.
      const ratios = await page.locator('.celerik-scheduler-dialog').evaluate((dialog) => {
        const rgb = (color) => color.match(/[\d.]+/g).slice(0, 4).map(Number);
        const luminance = ([r, g, b]) => [r, g, b].map((c) => c / 255)
          .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4))
          .reduce((sum, c, i) => sum + c * [0.2126, 0.7152, 0.0722][i], 0);
        const dialogBg = rgb(getComputedStyle(dialog).backgroundColor);
        return [...dialog.querySelectorAll('button')].map((button) => {
          const style = getComputedStyle(button);
          const own = rgb(style.backgroundColor);
          const bg = own[3] === 0 ? dialogBg : own;
          const [light, dark] = [luminance(rgb(style.color)), luminance(bg)].sort((a, b) => b - a);
          return `${button.textContent} ${((light + 0.05) / (dark + 0.05)).toFixed(2)}`;
        });
      });
      expect(ratios.filter((entry) => Number(entry.split(' ').pop()) < 4.5)).toEqual([]);
      expect(ratios.map((entry) => entry.split(' ')[0])).toEqual(['Delete', 'Cancel', 'Save']);
    });
  }
}
