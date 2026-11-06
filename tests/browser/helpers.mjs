import AxeBuilder from '@axe-core/playwright';

export const THEMES = ['bootstrap', 'material', 'tailwind'];
export const MODES = ['light', 'dark'];
export const WCAG_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

// Opens the test page and waits until every [data-celerik] component has fired
// celerik:ready. Console errors are collected and returned.
export async function openComponents(page, {
  path = '/', theme = 'bootstrap', mode = 'light', custom = false,
} = {}) {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error') {
      errors.push(`${message.text()} ${message.location().url}`.trim());
    }
  });

  await page.addInitScript(() => {
    window.celerikReady = new Set();
    document.addEventListener('celerik:ready', (event) => window.celerikReady.add(event.target));
  });
  await page.goto(`${path}?theme=${theme}&mode=${mode}${custom ? '&custom=1' : ''}`);
  await page.waitForFunction(
    () => window.celerikReady.size === document.querySelectorAll('[data-celerik]').length,
    null,
    { timeout: 30_000 },
  );
  // Let libraries finish their first layout (calendar sizing, chart and toast animations).
  await page.waitForTimeout(1500);

  return errors;
}

// Runs axe-core with color-contrast and returns readable violations, one string per node.
export async function axeViolations(page, { include = 'main', tags = WCAG_TAGS } = {}) {
  const results = await new AxeBuilder({ page }).include(include).withTags(tags).analyze();
  return results.violations.flatMap((violation) => violation.nodes.map(
    (node) => `${violation.id}: ${node.target.join(' ')} (${node.failureSummary?.split('\n').slice(1).join(' ').trim()})`,
  ));
}
