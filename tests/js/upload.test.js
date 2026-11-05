import { buildFilePondConfig, csrfTokenFor, MESSAGES, uploadProblem } from '../../resources/js/upload-config.js';
import { initForm } from '../../resources/js/form.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('upload config', () => {
  test('maps the PHP options to FilePond with the CSRF header', () => {
    const input = { name: 'photos[]' };
    const config = buildFilePondConfig(input, {
      url: '/uploads', maxFiles: 3, maxSize: 2 * 1024 * 1024, types: ['image/png'],
    }, 'tok');

    expect(config).toMatchObject({
      name: 'photos[]',
      allowMultiple: true,
      maxFiles: 3,
      acceptedFileTypes: ['image/png'],
      fileSizeBase: 1024,
      maxFileSize: '2048KB',
      credits: false,
      server: {
        process: { url: '/uploads', method: 'POST', headers: { 'X-CSRF-TOKEN': 'tok' } },
        revert: { url: '/uploads', method: 'DELETE', headers: { 'X-CSRF-TOKEN': 'tok' } },
      },
    });
    expect(buildFilePondConfig(input, { url: '/u' }, null).server.process.headers).toEqual({});
  });

  test('raw FilePond options from PHP are applied last', () => {
    expect(buildFilePondConfig({ name: 'a' }, { url: '/u', filepond: { labelIdle: 'Drop here' } }).labelIdle)
      .toBe('Drop here');
  });

  test('uploadProblem blocks submission while files are missing, pending or failed', () => {
    const done = { done: true, pending: false, failed: false };
    const pending = { done: false, pending: true, failed: false };
    const failed = { done: false, pending: false, failed: true };

    expect(uploadProblem([], { required: true })).toBe(MESSAGES.required);
    expect(uploadProblem([], { required: false })).toBeNull();
    expect(uploadProblem([done, pending], { required: true })).toBe(MESSAGES.pending);
    expect(uploadProblem([done, failed], {})).toBe(MESSAGES.failed);
    expect(uploadProblem([done], { required: true })).toBeNull();
  });

  test('csrfTokenFor reads the form hidden token named in the form options', () => {
    document.body.innerHTML = '<form><input type="hidden" name="_csrf" value="abc"><input name="_csrf" value="visible"></form>';
    const form = document.querySelector('form');

    expect(csrfTokenFor(form, { csrf: { field: '_csrf' } })).toBe('abc');
    expect(csrfTokenFor(form, {})).toBeNull();
  });
});

describe('upload integration (FilePond)', () => {
  test('enhances async file inputs and blocks submitting without the required file', async () => {
    const form = loadFixture('form-rich');
    const parsley = await initForm(form, readOptions(form));

    expect(form.querySelectorAll('.filepond--root')).toHaveLength(2);

    // The visible field label now names FilePond's real file input.
    const label = form.querySelector('#article-cover-label');
    const browser = document.getElementById(label.htmlFor);
    expect(browser.matches('input[type="file"].filepond--browser')).toBe(true);
    expect(browser.getAttribute('aria-labelledby')).toMatch(/^article-cover-label filepond--drop-label-/);
    expect(browser.getAttribute('aria-describedby')).toBe('article-cover-help');

    const submitted = jest.fn((event) => event.preventDefault());
    form.addEventListener('submit', submitted);
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

    expect(submitted).not.toHaveBeenCalled();
    const error = form.querySelector('.celerik-upload-error');
    expect(error.textContent).toBe(MESSAGES.required);
    expect(error.getAttribute('role')).toBe('alert');
    expect(error.closest('.mb-3').querySelector('label').textContent).toContain('Cover image');

    parsley.destroy();
  });

  test('falls back to the native file input where FilePond is not supported', async () => {
    jest.resetModules();
    jest.doMock('filepond', () => ({
      ...jest.requireActual('filepond'),
      supported: () => false,
      create: jest.fn(),
    }));
    const { initUploads } = await import('../../resources/js/upload.js');
    const form = loadFixture('form-rich');

    expect(await initUploads(form, 'tok')).toEqual([]);

    const cover = form.querySelector('input[name="cover"]');
    expect(form.querySelector('.filepond--root')).toBeNull();
    expect(cover.required).toBe(true);
    expect(cover.hasAttribute('data-parsley-excluded')).toBe(false);
    expect(form.getAttribute('enctype')).toBe('multipart/form-data');
    jest.dontMock('filepond');
  });
});
