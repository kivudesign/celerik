import { expect, test } from '@playwright/test';
import { openComponents } from './helpers.mjs';

// Windows High Contrast and other forced-colors modes drop box-shadow and author
// background colors, so focus and selection must be shown with outlines, borders or
// system colors.
test.use({ forcedColors: 'active' });

// Tabs through the page and returns the elements whose focus would be invisible:
// no outline (box-shadow rings are not painted in forced-colors mode).
async function invisibleFocus(page, stops) {
  const invisible = [];
  const components = new Set();
  for (let i = 0; i < stops; i += 1) {
    await page.keyboard.press('Tab');
    const result = await page.evaluate(() => {
      const element = document.activeElement;
      if (!element || element === document.body || !element.closest('main, .notyf')) {
        return {};
      }
      const component = element.closest('[data-celerik], .dt-container')?.id ?? null;
      // Bootstrap's focus trap can focus the modal itself: its dialog box shows the outline.
      const style = getComputedStyle(element.matches('.modal') ? element.querySelector('.modal-content') : element);
      const visible = style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
      const name = element.id ? `#${element.id}` : `${element.tagName.toLowerCase()}.${[...element.classList].join('.')}`;
      const label = `${name} "${(element.getAttribute('aria-label') ?? element.textContent).trim().slice(0, 30)}"`;
      return { component, invisible: visible ? null : label };
    });
    if (result.component) {
      components.add(result.component);
    }
    if (result.invisible && !invisible.includes(result.invisible)) {
      invisible.push(result.invisible);
    }
  }
  return { invisible, components: [...components] };
}

test('focus stays visible on every tab stop', async ({ page }) => {
  test.setTimeout(180_000); // 120 tab stops
  await openComponents(page);
  await page.locator('h1').click();
  const { invisible, components } = await invisibleFocus(page, 120);
  expect(invisible).toEqual([]);
  // The walk must reach every component with tab stops.
  expect(components).toEqual(expect.arrayContaining(
    ['users_wrapper', 'signup', 'article', 'team-calendar', 'account', 'files'],
  ));
});

test('focus stays visible in the modal', async ({ page }) => {
  await openComponents(page);
  await page.getByRole('button', { name: 'Contact us' }).click();
  await expect(page.getByRole('dialog')).toBeVisible();
  expect((await invisibleFocus(page, 6)).invisible).toEqual([]);
});

test('selected states use system colors or outlines', async ({ page }) => {
  await openComponents(page);
  const style = (selector, property) => page.locator(selector).first()
    .evaluate((element, name) => getComputedStyle(element)[name], property);

  expect(await style('.celerik-tabs .nav-link.active', 'borderBottomWidth')).toBe('3px');
  expect(await style('.celerik-scheduler .fc-day-today', 'outlineStyle')).toBe('solid');
  expect(await style('.dt-container .page-item.active .page-link', 'forcedColorAdjust')).toBe('none');
  expect(await style('.notyf__toast', 'borderTopStyle')).toBe('solid');
});
