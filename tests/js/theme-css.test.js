/**
 * @jest-environment node
 */
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import {
  BASES, contrastRatio, contrastReport, formatRatio, themeToCss, validateTheme,
} from '../../resources/js/theme-css.js';

const root = join(__dirname, '../..');
const read = (path) => JSON.parse(readFileSync(join(root, path), 'utf8'));
const fixture = (name) => readFileSync(join(__dirname, 'fixtures', name), 'utf8');
const preset = (base) => read(`theme/presets/${base}.json`);
const { pairs } = read('theme/contrast.json');

// tests/js/fixtures/theme-*.css are written by Theme::toCss() in PHP (fixtures/build.php).
describe('themeToCss() writes the same stylesheet as Theme::toCss()', () => {
  test.each(BASES)('%s preset', (base) => {
    expect(themeToCss(preset(base))).toBe(fixture(`theme-${base}.css`));
  });

  test.each(BASES)('custom theme on the %s base, with a font and a whole-number radius', (base) => {
    const theme = {
      ...read('tests/browser/custom-theme.json'),
      base,
      radius: 1,
      fontFamily: '"Inter", system-ui, sans-serif',
    };
    expect(themeToCss(theme)).toBe(fixture(`theme-custom-${base}.css`));
  });
});

describe('contrast', () => {
  test('ratios match known WCAG values', () => {
    expect(contrastRatio('#000000', '#ffffff')).toBeCloseTo(21, 2);
    expect(contrastRatio('#767676', '#ffffff')).toBeCloseTo(4.54, 2);
    expect(contrastRatio('#ffffff', '#0d6efd')).toBe(contrastRatio('#0d6efd', '#ffffff'));
  });

  test('the report covers every pair in both modes and does not round thresholds', () => {
    const theme = preset('bootstrap');
    theme.light.danger = '#dd3545';
    const report = contrastReport(theme, pairs);

    expect(report).toHaveLength(2 * pairs.length);
    const failures = report.filter((result) => !result.passes);
    expect(failures.map((result) => `${result.mode} ${result.foreground}`)).toEqual(['light danger']);
    expect(formatRatio(failures[0].ratio)).toBe('4.49');
    expect(formatRatio(21)).toBe('21.00');
  });

  test.each(BASES)('the %s preset passes', (base) => {
    expect(contrastReport(preset(base), pairs).filter((result) => !result.passes)).toEqual([]);
  });
});

describe('validateTheme() follows Theme::fromArray()', () => {
  test('presets are valid and keep their shape', () => {
    for (const base of BASES) {
      expect(validateTheme(preset(base))).toEqual({ theme: preset(base) });
    }
    const upper = preset('material');
    upper.light.primary = '#6750A4';
    expect(validateTheme(upper).theme.light.primary).toBe('#6750a4');
  });

  test.each([
    ['not an object', () => 'material'],
    ['an unknown key', (t) => ({ ...t, css: 'body {}' })],
    ['markup in the name', (t) => ({ ...t, name: '<script>' })],
    ['an unknown base', (t) => ({ ...t, base: 'bulma' })],
    ['a missing color', (t) => ({ ...t, light: { ...t.light, danger: undefined } })],
    ['an extra color', (t) => ({ ...t, dark: { ...t.dark, accent: '#000000' } })],
    ['a named color', (t) => ({ ...t, light: { ...t.light, primary: 'red' } })],
    ['css in a color', (t) => ({ ...t, light: { ...t.light, primary: '#ffffff;}body{' } })],
    ['a radius out of range', (t) => ({ ...t, radius: 3 })],
    ['a radius as text', (t) => ({ ...t, radius: '1rem' })],
    ['css in the font', (t) => ({ ...t, fontFamily: 'Inter; } body { display: none' })],
    ['a url as font', (t) => ({ ...t, fontFamily: 'url(https://evil.example/f.woff)' })],
    ['an unknown shadow', (t) => ({ ...t, shadow: 'glow' })],
    ['an inherited shadow name', (t) => ({ ...t, shadow: 'toString' })],
  ])('rejects %s', (_, change) => {
    const data = change(preset('bootstrap'));
    if (data?.light && Object.hasOwn(data.light, 'danger') && data.light.danger === undefined) {
      delete data.light.danger;
    }
    expect(validateTheme(data).error).toEqual(expect.any(String));
  });
});
