/**
 * @jest-environment node
 */
import { compile } from 'sass';
import { join } from 'node:path';

const themes = ['material', 'tailwind'];

// Custom properties declared in a block whose selector list contains `selector`.
function properties(css, selector) {
  const values = {};
  for (const [, selectors, body] of css.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
    if (!selectors.split(',').map((s) => s.trim()).includes(selector)) {
      continue;
    }
    for (const [, name, value] of body.matchAll(/(--[\w-]+)\s*:\s*([^;]+);/g)) {
      values[name] = value.trim();
    }
  }
  return values;
}

function luminance(hex) {
  const value = hex.replace('#', '');
  const full = value.length === 3 ? [...value].map((c) => c + c).join('') : value;
  const [r, g, b] = [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16) / 255)
    .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a, b) {
  const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (light + 0.05) / (dark + 0.05);
}

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

  test('light mode is also the default (:root)', () => {
    expect(properties(css, ':root')['--bs-body-color']).toBe(properties(css, '[data-bs-theme=light]')['--bs-body-color']);
  });
});

test('contrast() matches known WCAG values', () => {
  expect(contrast('#000000', '#ffffff')).toBeCloseTo(21, 1);
  expect(contrast('#767676', '#ffffff')).toBeCloseTo(4.54, 2);
});
