/**
 * @jest-environment node
 */
import Ajv2020 from 'ajv/dist/2020';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { compile } from 'sass';
import { normalizeHex, properties } from './css-helpers.js';

// The theme presets (theme/presets/*.json) describe the shipped stylesheets.
// These tests fail when a preset and its stylesheet drift apart.
const root = join(__dirname, '../..');
const read = (path) => JSON.parse(readFileSync(join(root, path), 'utf8'));
const schema = read('theme/theme.schema.json');
const preset = (name) => read(`theme/presets/${name}.json`);

// Palette token => the custom property the theme stylesheets set for it.
const TOKENS = {
  primary: '--bs-primary',
  primaryHover: '--celerik-primary-hover',
  onPrimary: '--celerik-on-primary',
  bodyBg: '--bs-body-bg',
  bodyColor: '--bs-body-color',
  muted: '--bs-secondary-color',
  surface: '--bs-tertiary-bg',
  border: '--bs-border-color',
  inputBorder: '--celerik-input-border',
  danger: '--bs-form-invalid-color',
};

const SHADOWS = {
  elevated: '0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)',
  outlined: '0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 0 0 1px var(--bs-border-color)',
};

describe('theme presets', () => {
  const validate = new Ajv2020({ allErrors: true }).compile(schema);

  test.each(['bootstrap', 'material', 'tailwind'])('%s matches the schema', (name) => {
    expect(validate(preset(name)) ? [] : validate.errors).toEqual([]);
    expect(preset(name).base).toBe(name);
  });

  test('the schema rejects unsafe or malformed values', () => {
    const invalid = (change) => !validate({ ...preset('bootstrap'), ...change(preset('bootstrap')) });
    expect(invalid((theme) => ({ light: { ...theme.light, primary: 'red' } }))).toBe(true);
    expect(invalid((theme) => ({ dark: { ...theme.dark, primary: '#fff' } }))).toBe(true);
    expect(invalid(() => ({ fontFamily: 'Inter; } body { display: none' }))).toBe(true);
    expect(invalid(() => ({ fontFamily: 'url(https://evil.example/font)' }))).toBe(true);
    expect(invalid(() => ({ radius: 5 }))).toBe(true);
    expect(invalid(() => ({ base: 'bulma' }))).toBe(true);
    expect(invalid(() => ({ extra: true }))).toBe(true);
    expect(invalid(() => ({ name: '<script>' }))).toBe(true);
    expect(invalid(() => ({ name: 'Acme Café 2' }))).toBe(false);
  });

  describe.each(['material', 'tailwind'])('%s preset', (name) => {
    const css = compile(join(root, 'resources/scss/themes', `${name}.scss`)).css;
    const theme = preset(name);

    test.each(['light', 'dark'])('%s palette matches the stylesheet', (mode) => {
      const palette = properties(css, `[data-bs-theme=${mode}]`);
      for (const [token, property] of Object.entries(TOKENS)) {
        // Tailwind keeps Bootstrap's red for errors: the preset uses Bootstrap's values.
        const expected = palette[property] ?? preset('bootstrap')[mode][token];
        expect([token, theme[mode][token]]).toEqual([token, normalizeHex(expected)]);
      }
    });

    test('shape tokens match the stylesheet', () => {
      const shape = properties(css, ':root');
      expect(shape['--celerik-radius']).toBe(`${theme.radius}rem`);
      expect(shape['--celerik-surface-shadow']).toBe(SHADOWS[theme.shadow]);
      expect(shape['--bs-body-font-family'] ?? null).toBe(theme.fontFamily ?? null);
    });
  });

  test('the bootstrap preset is Bootstrap 5.3, with field borders that meet 3:1', () => {
    const css = readFileSync(join(root, 'node_modules/bootstrap/dist/css/bootstrap.css'), 'utf8');
    const light = Object.fromEntries(Object.entries(properties(css, ':root'))
      .map(([name, value]) => [name, normalizeHex(value)]));
    const theme = preset('bootstrap');

    expect(theme.light.primary).toBe(light['--bs-primary']);
    expect(theme.light.primaryHover).toBe(light['--bs-link-hover-color']);
    expect(theme.light.bodyBg).toBe(light['--bs-body-bg']);
    expect(theme.light.bodyColor).toBe(light['--bs-body-color']);
    expect(theme.light.surface).toBe(light['--bs-tertiary-bg']);
    expect(theme.light.border).toBe(light['--bs-border-color']);
    expect(theme.light.danger).toBe(light['--bs-form-invalid-color']);
    // Bootstrap's field border is its decorative border (#dee2e6, 1.3:1): the preset uses gray-600.
    expect(theme.light.inputBorder).toBe(light['--bs-gray-600']);
    expect(`${theme.radius}rem`).toBe(light['--bs-border-radius']);
  });
});
