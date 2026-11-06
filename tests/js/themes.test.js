/**
 * @jest-environment node
 */
import { compile } from 'sass';
import { join } from 'node:path';
import { contrast, normalizeHex, properties } from './css-helpers.js';

const themes = ['material', 'tailwind'];

const PAIRS = [
  // [foreground, background, minimum, what]
  ['--bs-body-color', '--bs-body-bg', 4.5, 'body text'],
  ['--bs-secondary-color', '--bs-body-bg', 4.5, 'muted text'],
  ['--bs-body-color', '--bs-tertiary-bg', 4.5, 'text on surfaces (grid header, upload area)'],
  ['--bs-secondary-color', '--bs-tertiary-bg', 4.5, 'muted text on surfaces'],
  ['--bs-link-color', '--bs-body-bg', 4.5, 'links'],
  ['--celerik-on-primary', '--celerik-primary', 4.5, 'primary button label'],
  ['--celerik-on-primary', '--celerik-primary-hover', 4.5, 'hovered primary button label'],
  ['--celerik-input-border', '--bs-body-bg', 3, 'form field border (WCAG 1.4.11)'],
];

describe.each(themes)('%s theme', (theme) => {
  const css = compile(join(__dirname, '../../resources/scss/themes', `${theme}.scss`)).css;

  test.each(['light', 'dark'])('%s mode meets WCAG 2.1 AA contrast', (mode) => {
    const palette = properties(css, `[data-bs-theme=${mode}]`);
    const failures = [];

    for (const [fg, bg, minimum, what] of PAIRS) {
      expect(palette[fg]).toMatch(/^#[0-9a-f]{3,6}$/i);
      expect(palette[bg]).toMatch(/^#[0-9a-f]{3,6}$/i);
      const ratio = contrast(palette[fg], palette[bg]);
      if (ratio < minimum) {
        failures.push(`${what}: ${palette[fg]} on ${palette[bg]} = ${ratio.toFixed(2)} (needs ${minimum})`);
      }
    }

    expect(failures).toEqual([]);
  });

  test.each(['light', 'dark'])('%s mode: every *-rgb property matches its color', (mode) => {
    const palette = properties(css, `[data-bs-theme=${mode}]`);
    const rgb = (hex) => [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(', ');
    for (const name of ['--bs-primary', '--bs-link-color', '--bs-link-hover-color']) {
      // Bootstrap colors links (and hovered links) from the -rgb variant.
      expect([name, palette[`${name}-rgb`]]).toEqual([name, rgb(normalizeHex(palette[name]))]);
    }
  });

  test('light mode is also the default (:root)', () => {
    expect(properties(css, ':root')['--bs-body-color']).toBe(properties(css, '[data-bs-theme=light]')['--bs-body-color']);
  });
});

test('contrast() matches known WCAG values', () => {
  expect(contrast('#000000', '#ffffff')).toBeCloseTo(21, 1);
  expect(contrast('#767676', '#ffffff')).toBeCloseTo(4.54, 2);
});
