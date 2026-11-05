import { buildEditorConfig, editorHtml, KEYBOARD_BINDINGS, TOOLBARS } from '../../resources/js/editor-config.js';
import { initForm } from '../../resources/js/form.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('editor config', () => {
  test('presets limit formats to what the server sanitizer keeps', () => {
    expect(buildEditorConfig({ toolbar: 'basic', placeholder: 'Write…' })).toEqual({
      theme: 'snow',
      placeholder: 'Write…',
      modules: { toolbar: TOOLBARS.basic.toolbar, keyboard: { bindings: KEYBOARD_BINDINGS } },
      formats: ['bold', 'italic', 'underline', 'list', 'link'],
    });
    expect(buildEditorConfig({ toolbar: 'unknown' }).formats).toEqual(TOOLBARS.standard.formats);
  });

  test('a custom toolbar array is passed through without format limits', () => {
    const config = buildEditorConfig({ toolbar: [['bold'], ['image']] });

    expect(config.modules.toolbar).toEqual([['bold'], ['image']]);
    expect(config).not.toHaveProperty('formats');
  });

  test('an empty editor submits an empty string', () => {
    expect(editorHtml({ getText: () => '\n', getSemanticHTML: () => '<p><br></p>' })).toBe('');
    expect(editorHtml({ getText: () => 'Hi\n', getSemanticHTML: () => '<p>Hi</p>' })).toBe('<p>Hi</p>');
  });
});

describe('editor integration (Quill)', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form-rich');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const textarea = () => form.querySelector('textarea[name="body"]');
  const editable = () => form.querySelector('.celerik-editor .ql-editor');

  test('replaces the textarea with an accessible editor holding the allowed content', () => {
    expect(editable()).not.toBeNull();
    expect(editable().innerHTML).toContain('<h2>Intro</h2>');
    expect(editable().innerHTML).toContain('<strong>world</strong>');
    expect(editable().innerHTML).not.toContain('script');

    expect(textarea().classList.contains('visually-hidden')).toBe(true);
    expect(textarea().tabIndex).toBe(-1);
    expect(textarea().getAttribute('aria-hidden')).toBe('true');
    expect(editable().getAttribute('role')).toBe('textbox');
    expect(editable().getAttribute('aria-labelledby')).toBe('article-body-label');
    expect(editable().getAttribute('aria-describedby')).toBe('article-body-help');
    expect(editable().getAttribute('aria-required')).toBe('true');
  });

  test('Tab is left to the browser (no keyboard trap)', () => {
    const quill = textarea().celerikEditor;
    // Only Quill's table binding is left, and no toolbar allows tables.
    // Leaving the editor with Tab is checked in a real browser (tests/browser/keyboard.spec.mjs).
    expect((quill.keyboard.bindings.Tab ?? []).map((binding) => binding.format)).toEqual([['table']]);
  });

  test('typing updates the submitted value, with normal spaces', () => {
    const quill = textarea().celerikEditor;

    quill.setText('New text\n', 'user');

    expect(textarea().value).toBe('<p>New text</p>');
  });

  test('real non-breaking spaces and a typed "&nbsp;" survive the space fix', () => {
    const quill = textarea().celerikEditor;

    quill.setText('10 km &nbsp; literal\n', 'user');

    expect(textarea().value).toBe('<p>10 km &amp;nbsp; literal</p>');
  });

  test('an emptied required editor fails validation and shows it on the editor', async () => {
    const quill = textarea().celerikEditor;
    quill.setText('', 'user');

    expect(textarea().value).toBe('');
    parsley.validate();
    await Promise.resolve(); // let the MutationObserver mirror the class

    expect(textarea().classList.contains('is-invalid')).toBe(true);
    expect(form.querySelector('.celerik-editor').classList.contains('is-invalid')).toBe(true);
    expect(editable().getAttribute('aria-invalid')).toBe('true');
    expect(textarea().closest('.mb-3').querySelector('.invalid-feedback').textContent).toMatch(/required/i);
  });
});
