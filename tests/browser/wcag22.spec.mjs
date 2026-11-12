import { expect, test } from '@playwright/test';
import { openComponents, settled } from './helpers.mjs';

// WCAG 2.2 AA checks axe-core cannot make on its own. The axe matrix also runs the wcag22aa rules.

// 2.5.8 Target Size (Minimum): pointer targets are at least 24 x 24 px, or exempt.
test('interactive controls are at least 24 x 24 px', async ({ page }) => {
  await openComponents(page);
  const small = await page.evaluate(() => {
    const selector = 'a[href], button, input:not([type=hidden]), select, textarea, [tabindex="0"], [role="button"]';
    // Exempt: inline links in text, controls hidden from pointer use (including the select a
    // searchable select replaces), checkboxes whose label is part of the target, and sort
    // buttons inside a header cell that sorts when clicked.
    const exempt = (element) => (element.tagName === 'A' && getComputedStyle(element).display === 'inline')
      || element.matches('.visually-hidden, .ts-hidden-accessible, .filepond--browser, .filepond--label-action')
      || (element.matches('.form-check-input') && element.labels?.length > 0)
      || element.matches('th .dt-column-order');
    return [...document.querySelectorAll(selector)]
      .filter((element) => {
        const rect = element.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && (rect.width < 24 || rect.height < 24) && !exempt(element);
      })
      .map((element) => `${element.tagName.toLowerCase()}.${[...element.classList].join('.')}`);
  });
  expect(small).toEqual([]);
});

test('the File Manager tree arrow has a 24 x 24 px hit area', async ({ page }) => {
  await openComponents(page);
  // elFinder shows the arrow once it knows the folder has subfolders (Reports/).
  const arrow = page.locator('#files .elfinder-navbar-collapsed > .elfinder-navbar-arrow').first();
  await expect(arrow).toBeVisible({ timeout: 20_000 });
  await arrow.scrollIntoViewIfNeeded(); // elementFromPoint only sees the viewport
  const hit = await arrow.evaluate((element) => {
    const rect = element.getBoundingClientRect();
    const x = rect.left + rect.width / 2;
    const y = rect.top + rect.height / 2;
    return [[-11, 0], [11, 0], [0, -11], [0, 11]].map(([dx, dy]) => document.elementFromPoint(x + dx, y + dy) === element);
  });
  expect(hit).toEqual([true, true, true, true]);

  // A click in the enlarged area still toggles the folder (the root starts expanded).
  const expanded = () => arrow.evaluate((element) => element.parentElement.classList.contains('elfinder-navbar-expanded'));
  const before = await expanded();
  const box = await arrow.boundingBox();
  await page.mouse.click(box.x + box.width / 2 - 10, box.y + box.height / 2);
  await expect.poll(expanded).toBe(!before);
});

// 2.4.11 Focus Not Obscured (Minimum): an element focused behind the toasts is scrolled
// into the clear. The pager link is first scrolled to sit right under the toast stack, so
// the check does not depend on the layout of the browser running it.
test('an element focused behind a toast is scrolled into the clear', async ({ page }) => {
  await openComponents(page);
  const toast = page.locator('.celerik-toast-error'); // errors stay until dismissed
  const next = page.locator('#users_wrapper').getByRole('link', { name: 'Next' });
  await expect(toast).toBeVisible();
  // A stable stack: the success toast would expire and move the error toast up.
  await page.locator('.celerik-toast-success').getByRole('button', { name: /Dismiss/i }).click();
  await expect(page.locator('.notyf__toast')).toHaveCount(1);
  await settled(page); // the remaining toast slides up

  // Put the link under the toast: same height on screen, by scrolling the page.
  const toastBox = await toast.boundingBox();
  await next.evaluate((link, top) => {
    window.scrollBy({ top: link.getBoundingClientRect().top - top, behavior: 'instant' });
  }, toastBox.y + 5);
  const linkBox = await next.boundingBox();
  test.skip(linkBox.x + linkBox.width < toastBox.x, 'the pager is not under the toasts at this width');

  // Focus it from the keyboard: Shift+Tab from the element after it.
  await page.locator('#users_wrapper').getByRole('link', { name: 'Last' }).focus();
  await page.keyboard.press('Shift+Tab');
  await expect(next).toBeFocused();

  await expect.poll(async () => {
    const [link, cover] = [await next.boundingBox(), await toast.boundingBox()];
    return link.y >= cover.y + cover.height || link.y + link.height <= cover.y;
  }, { timeout: 1500 }).toBe(true);
});

// 2.4.11 Focus Not Obscured (Minimum): toasts never cover the focused element entirely.
test('toasts never hide the focused element', async ({ page }) => {
  await openComponents(page);
  await page.locator('h1').click();
  const hidden = [];
  for (let i = 0; i < 80; i += 1) {
    await page.keyboard.press('Tab');
    const covered = await page.evaluate(() => {
      const element = document.activeElement;
      const a = element.getBoundingClientRect();
      return [...document.querySelectorAll('.notyf__toast')].some((toast) => {
        const b = toast.getBoundingClientRect();
        return !toast.contains(element) && a.width > 0
          && a.left >= b.left && a.right <= b.right && a.top >= b.top && a.bottom <= b.bottom;
      }) ? (element.id || element.className) : null;
    });
    if (covered) {
      hidden.push(covered);
    }
  }
  expect(hidden).toEqual([]);
});
