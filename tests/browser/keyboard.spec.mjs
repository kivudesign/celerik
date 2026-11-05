import { expect, test } from '@playwright/test';
import { axeViolations, openComponents } from './helpers.mjs';

// Keyboard behaviour does not depend on the theme: these run once, in the default theme.
test.beforeEach(async ({ page }) => {
  await openComponents(page);
});

test.describe('modal', () => {
  test('opens from the keyboard, traps focus, closes with Escape and returns focus', async ({ page }) => {
    const trigger = page.getByRole('button', { name: 'Contact us' });
    await trigger.focus();
    await page.keyboard.press('Enter');

    const dialog = page.getByRole('dialog', { name: 'Contact us' });
    await expect(dialog).toBeVisible();
    await expect.poll(() => dialog.evaluate((element) => element.contains(document.activeElement))).toBe(true);

    for (let i = 0; i < 8; i += 1) {
      await page.keyboard.press('Tab');
      expect(await dialog.evaluate((element) => element.contains(document.activeElement))).toBe(true);
    }

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
  });
});

test.describe('tabs', () => {
  test('arrow keys, Home and End move the selection, with one tab stop', async ({ page }) => {
    const tablist = page.getByRole('tablist', { name: 'Account settings' });
    const tab = (name) => tablist.getByRole('tab', { name });

    await tab('Profile').focus();
    await page.keyboard.press('ArrowRight');
    await expect(tab('Security')).toBeFocused();
    await expect(tab('Security')).toHaveAttribute('aria-selected', 'true');
    await expect(page.getByRole('tabpanel', { name: 'Security' })).toBeVisible();

    await page.keyboard.press('End');
    await expect(tab('Billing')).toBeFocused();
    await page.keyboard.press('ArrowRight');
    await expect(tab('Profile')).toBeFocused();
    await page.keyboard.press('ArrowLeft');
    await expect(tab('Billing')).toBeFocused();
    await page.keyboard.press('Home');
    await expect(tab('Profile')).toBeFocused();

    // Roving tabindex: only the selected tab is in the Tab order.
    expect(await tablist.getByRole('tab').evaluateAll((tabs) => tabs.map((t) => t.tabIndex))).toEqual([0, -1, -1]);
  });
});

test.describe('grid', () => {
  test('sorts from the keyboard and announces the sort order', async ({ page }) => {
    // DataTables 2 puts the sort control in the header as a named button.
    const header = page.getByRole('columnheader', { name: /Name/ });
    const sort = header.getByRole('button', { name: /Name: Activate to sort/ });
    await sort.focus();
    await expect(sort).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(header).toHaveAttribute('aria-sort', 'ascending');
    await page.keyboard.press('Enter');
    await expect(header).toHaveAttribute('aria-sort', 'descending');
  });

  test('pages from the keyboard', async ({ page }) => {
    const grid = page.locator('#users_wrapper');
    await grid.getByRole('link', { name: 'Next' }).focus();
    await page.keyboard.press('Enter');
    await expect(page.locator('#users tbody')).toContainText('Grace Hopper');
  });
});

test.describe('form', () => {
  test('a failed submit moves focus to the first invalid field', async ({ page }) => {
    const form = page.locator('#signup');
    await form.getByRole('textbox', { name: /Name/ }).fill('');
    await form.getByRole('button', { name: 'Create account' }).focus();
    await page.keyboard.press('Enter');

    const name = form.getByRole('textbox', { name: /Name/ });
    await expect(name).toBeFocused();
    await expect(name).toHaveAttribute('aria-invalid', 'true');
  });

  test('with an error summary, a failed submit focuses the summary and its links reach the fields', async ({ page }) => {
    const form = page.locator('#article');
    await form.getByRole('textbox', { name: /Title/ }).fill('');
    await form.getByRole('button', { name: 'Publish' }).focus();
    await page.keyboard.press('Enter');

    const summary = form.locator('.celerik-error-summary');
    await expect(summary).toBeVisible();
    await expect(summary).toBeFocused();
    await expect(summary.getByRole('heading', { name: 'There is a problem' })).toBeVisible();
    expect(await axeViolations(page, { include: '#article' })).toEqual([]);

    await page.keyboard.press('Tab');
    await page.keyboard.press('Enter');
    await expect(form.getByRole('textbox', { name: /Title/ })).toBeFocused();
  });

  test('the rich text editor is reachable and editable from the keyboard', async ({ page }) => {
    const title = page.locator('#article').getByRole('textbox', { name: /Title/ });
    await title.focus();
    await page.keyboard.press('Tab');

    // Tab goes through the toolbar buttons, then into the editor.
    const editor = page.getByRole('textbox', { name: 'Article' });
    for (let i = 0; i < 20 && !(await editor.evaluate((element) => element === document.activeElement)); i += 1) {
      await page.keyboard.press('Tab');
    }
    await expect(editor).toBeFocused();
    await page.keyboard.type(' typed');
    await expect(editor).toContainText('typed');

    // Tab leaves the editor instead of inserting a tab (WCAG 2.1.2, No Keyboard Trap).
    await page.keyboard.press('Tab');
    await expect(editor).not.toBeFocused();
    expect(await editor.textContent()).not.toContain('	');
    await page.keyboard.press('Shift+Tab');
    await expect(editor).toBeFocused();
  });
});

test.describe('scheduler', () => {
  test('toolbar buttons work from the keyboard', async ({ page }) => {
    const calendar = page.locator('#team-calendar');
    const title = calendar.locator('.fc-toolbar-title');
    await expect(title).toHaveText(/November 2026/);

    await calendar.getByRole('button', { name: /Next/ }).focus();
    await page.keyboard.press('Enter');
    await expect(title).toHaveText(/December 2026/);
  });
});

test.describe('notification', () => {
  test('a toast can be dismissed from the keyboard', async ({ page }) => {
    // The error toast stays until dismissed; the others may expire during the test.
    const toast = page.locator('.celerik-toast-error');
    const dismiss = toast.getByRole('button', { name: /Dismiss/i });
    await expect(dismiss).toBeVisible();
    await dismiss.focus();
    await page.keyboard.press('Enter');
    await expect(toast).toHaveCount(0);
  });
});
