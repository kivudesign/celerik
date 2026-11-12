import axe from 'axe-core';
import { initGrid } from '../../resources/js/grid.js';
import { initForm } from '../../resources/js/form.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

// WCAG 2.1 A/AA rules. Color contrast cannot be computed in jsdom and is checked in the browser.
async function violations() {
  const results = await axe.run(document.body, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
    rules: { 'color-contrast': { enabled: false } },
  });
  return results.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`);
}

describe('accessibility (axe-core, WCAG 2.1 AA)', () => {
  test('grid markup from PHP', async () => {
    loadFixture('grid');
    expect(await violations()).toEqual([]);
  });

  test('grid after DataTables enhancement', async () => {
    const table = loadFixture('grid');
    const grid = initGrid(table, readOptions(table));
    expect(await violations()).toEqual([]);
    grid.destroy();
  });

  test('scheduler markup from PHP (no-JS fallback list)', async () => {
    loadFixture('scheduler');
    expect(await violations()).toEqual([]);
  });

  test('form with editor and uploads: PHP markup, then Quill and FilePond, then failed validation', async () => {
    const form = loadFixture('form-rich');
    expect(await violations()).toEqual([]);

    const parsley = await initForm(form, readOptions(form));
    expect(await violations()).toEqual([]);

    form.querySelector('textarea[name="body"]').celerikEditor.setText('', 'user');
    parsley.validate();
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    expect(await violations()).toEqual([]);
    parsley.destroy();
  }, 30000);

  test('file manager markup from PHP (elFinder itself is checked in tests/browser)', async () => {
    const element = loadFixture('filemanager');
    expect(element.getAttribute('role')).toBe('region');
    expect(element.getAttribute('aria-label')).toBe('Shared files');
    expect(await violations()).toEqual([]);
  });

  test('theme builder markup from PHP, with a failing contrast pair', async () => {
    const element = loadFixture('themebuilder');
    expect(element.querySelectorAll('.celerik-theme-builder__pair.is-fail')).toHaveLength(1);
    expect(await violations()).toEqual([]);
  });

  test('chart markup from PHP', async () => {
    loadFixture('chart');
    expect(await violations()).toEqual([]);
  });

  test('form markup from PHP, before and after failed validation', async () => {
    const form = loadFixture('form');
    expect(await violations()).toEqual([]);

    const parsley = await initForm(form, readOptions(form));
    parsley.validate();
    expect(await violations()).toEqual([]);
    parsley.destroy();
  });

  test('radio, checkbox group, switch, range and color markup, before and after failed validation', async () => {
    const form = loadFixture('form-choices');
    expect(await violations()).toEqual([]);

    const parsley = await initForm(form, readOptions(form));
    parsley.validate();
    expect(await violations()).toEqual([]);
    parsley.destroy();
  });
});
