import { initThemeBuilder, scopeCss } from '../../resources/js/themebuilder.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('scopeCss', () => {
  test('applies a theme stylesheet inside the preview only', () => {
    const css = '/* Theme */\n:root,\n[data-bs-theme=light] {\n  --a: 1;\n}\n[data-bs-theme=dark] {\n  --a: 2;\n}\n'
      + ':root {\n  --r: 1rem;\n}\n.form-control,\n.form-select:focus {\n  color: red;\n}\n';

    expect(scopeCss(css, '#b .c')).toBe('#b .c,\n#b .c[data-bs-theme=light] {\n  --a: 1;\n}\n#b .c[data-bs-theme=dark] {\n  --a: 2;\n}\n'
      + '#b .c {\n  --r: 1rem;\n}\n#b .c .form-control,\n#b .c .form-select:focus {\n  color: red;\n}');
  });
});

describe('theme builder (fixture: material preset, light error color at 4.49:1)', () => {
  let element;
  let builder;

  beforeEach(() => {
    element = loadFixture('themebuilder');
    builder = initThemeBuilder(element, readOptions(element));
  });

  afterEach(() => {
    document.querySelectorAll('style[data-celerik-theme-preview]').forEach((style) => style.remove());
  });

  const $ = (selector) => element.querySelector(selector);
  const text = (id) => document.getElementById(id);
  const type = (input, value, { commit = true } = {}) => {
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    if (commit) {
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };
  const previewCss = () => document.querySelector('style[data-celerik-theme-preview="builder"]').textContent;
  const failures = () => [...element.querySelectorAll('.celerik-theme-builder__pair.is-fail')].map((li) => li.dataset.celerikPair);

  test('reveals the JavaScript controls and previews the theme in the preview area only', () => {
    for (const part of ['__preset', '__history', '__modes']) {
      expect($(`.celerik-theme-builder${part}`).hidden).toBe(false);
    }
    expect($('.celerik-theme-builder__export').hidden).toBe(true); // export comes with its own step
    expect(previewCss()).toContain('#builder .celerik-theme-builder__canvas,\n#builder .celerik-theme-builder__canvas[data-bs-theme=light] {');
    expect(previewCss()).toContain('  --bs-primary: #6750a4;');
    expect(previewCss()).not.toMatch(/(^|\n):root/);
    expect(failures()).toEqual(['light.danger.bodyBg']);
    expect($('.celerik-theme-builder__summary').textContent).toBe('1 pair is below its minimum.');
  });

  test('a hex field updates its picker, the preview and the report as you type', () => {
    const changes = [];
    builder.onChange((theme) => changes.push(theme.light.danger));

    type(text('builder-light-danger'), '#B3261E', { commit: false });

    expect(text('builder-light-danger-picker').value).toBe('#b3261e');
    expect(previewCss()).toContain('  --bs-form-invalid-color: #b3261e;');
    expect(failures()).toEqual([]);
    expect($('.celerik-theme-builder__summary').textContent).toBe('Every pair meets its minimum.');
    expect(changes).toEqual(['#b3261e']);
  });

  test('the picker updates its hex field', () => {
    type(text('builder-dark-primary-picker'), '#bb86fc');

    expect(text('builder-dark-primary').value).toBe('#bb86fc');
    expect(builder.getTheme().dark.primary).toBe('#bb86fc');
  });

  test('an invalid color is reported on its field and not applied', () => {
    const input = text('builder-light-primary');
    type(input, '#12');

    expect(input.getAttribute('aria-invalid')).toBe('true');
    expect(text('builder-light-primary-error').textContent).toBe('Use a color like #0d6efd.');
    expect(input.getAttribute('aria-describedby')).toBe('builder-light-primary-help builder-light-primary-error');
    expect(builder.getTheme().light.primary).toBe('#6750a4');

    type(input, '#123456');
    expect(input.hasAttribute('aria-invalid')).toBe(false);
    expect(text('builder-light-primary-error')).toBeNull();
    expect(input.getAttribute('aria-describedby')).toBe('builder-light-primary-help');
  });

  test('base, radius, font and shadow change the theme', () => {
    type($('[name="theme[base]"]'), 'tailwind');
    type($('[name="theme[radius]"]'), '1.25');
    type($('[name="theme[fontFamily]"]'), 'Inter, sans-serif');
    type($('[name="theme[shadow]"]'), '');

    const theme = builder.getTheme();
    expect([theme.base, theme.radius, theme.fontFamily, theme.shadow]).toEqual(['tailwind', 1.25, 'Inter, sans-serif', undefined]);
    expect(text('builder-radius-value').textContent).toBe('1.25');
    expect(previewCss()).toContain('  --celerik-radius: 1.25rem;');
    expect(previewCss()).toContain('--celerik-focus-ring: 0 0 0 2px #fffbfe'); // tailwind derives its ring

    type($('[name="theme[fontFamily]"]'), 'Inter; } body {');
    expect($('[name="theme[fontFamily]"]').getAttribute('aria-invalid')).toBe('true');
    expect(builder.getTheme().fontFamily).toBe('Inter, sans-serif');
  });

  test('undo steps back one finished change at a time; reset returns to the start', () => {
    const undo = $('[data-celerik-action="undo"]');
    expect(undo.disabled).toBe(true);

    const primary = text('builder-light-primary');
    type(primary, '#111111', { commit: false });
    type(primary, '#222222'); // one change event: one undo step
    type($('[name="theme[base]"]'), 'bootstrap');
    expect(undo.disabled).toBe(false);

    undo.click();
    expect(builder.getTheme().base).toBe('material');
    expect(builder.getTheme().light.primary).toBe('#222222');
    expect($('[name="theme[base]"]').value).toBe('material');

    undo.click();
    expect(builder.getTheme().light.primary).toBe('#6750a4');
    expect(primary.value).toBe('#6750a4');
    expect(undo.disabled).toBe(true);
    expect(document.activeElement).toBe($('[name="theme[name]"]'));

    type(primary, '#333333');
    $('[data-celerik-action="reset"]').click();
    expect(builder.getTheme().light).toEqual(readOptions(element).theme.light);
  });

  test('loading a preset replaces the theme and can be undone', () => {
    $(`#builder-preset`).value = 'tailwind';
    $('[data-celerik-action="preset"]').click();

    expect(builder.getTheme().name).toBe('Tailwind');
    expect(text('builder-light-primary').value).toBe('#4f46e5');
    expect($('[name="theme[base]"]').value).toBe('tailwind');

    $('[data-celerik-action="undo"]').click();
    expect(builder.getTheme().base).toBe('material');
  });

  test('the preview mode switches the preview between light and dark', () => {
    const canvas = $('.celerik-theme-builder__canvas');
    const dark = text('builder-preview-dark');
    dark.checked = true;
    dark.dispatchEvent(new Event('change', { bubbles: true }));

    expect(canvas.dataset.bsTheme).toBe('dark');
  });

  test('setTheme validates like Theme::fromArray()', () => {
    expect(() => builder.setTheme({ ...builder.getTheme(), base: 'bulma' })).toThrow('Unknown base');
  });
});
