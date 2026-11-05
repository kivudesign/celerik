import { initForm } from '../../resources/js/form.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('form integration (Parsley + Inputmask)', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const field = (name) => form.querySelector(`[name="${name}"]`);

  test('flags required fields with Bootstrap classes and messages', () => {
    expect(parsley.validate()).toBe(false);

    expect(field('name').classList.contains('is-invalid')).toBe(true);
    expect(field('name').getAttribute('aria-invalid')).toBe('true');
    expect(field('name').closest('.mb-3').querySelector('.invalid-feedback').textContent)
      .toMatch(/required/i);
  });

  test('validates e-mail, length and password confirmation', () => {
    field('name').value = 'Ada';
    field('email').value = 'not-an-email';
    field('password').value = 'short';
    field('password_confirmation').value = 'different';
    field('terms').checked = true;

    parsley.validate();

    expect(field('name').classList.contains('is-valid')).toBe(true);
    expect(field('email').classList.contains('is-invalid')).toBe(true);
    expect(field('password').classList.contains('is-invalid')).toBe(true);
    expect(field('password_confirmation').classList.contains('is-invalid')).toBe(true);

    field('email').value = 'ada@example.com';
    field('password').value = 'long-enough';
    field('password_confirmation').value = 'long-enough';

    expect(parsley.validate()).toBe(true);
  });

  test('removes the server-side error once the field is re-validated', () => {
    expect(form.querySelector('.celerik-server-error')).not.toBeNull();

    field('email').value = 'ada@example.com';
    parsley.validate();

    expect(form.querySelector('.celerik-server-error')).toBeNull();
    expect(field('email').hasAttribute('aria-invalid')).toBe(false);
  });

  test('applies input masks', () => {
    expect(field('phone').inputmask).toBeDefined();
    expect(field('phone').inputmask.isComplete()).toBe(false);
  });
});

describe('error summary', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form-summary');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const summary = () => form.querySelector('.celerik-error-summary');
  const submit = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

  test('a failed submit lists each error with a link, focuses the summary only', () => {
    expect(summary().hidden).toBe(true);
    form.querySelector('[name="email"]').value = 'not-an-email';
    submit();

    expect(summary().hidden).toBe(false);
    expect(document.activeElement).toBe(summary());
    const links = [...summary().querySelectorAll('a')].map((a) => [a.getAttribute('href'), a.textContent]);
    expect(links.map(([href]) => href)).toEqual(['#order-name', '#order-email', '#order-notes']);
    expect(links.every(([, text]) => text.length > 0)).toBe(true);

    // One announcement: the field errors are not live regions.
    expect(form.querySelectorAll('[aria-live]')).toHaveLength(0);
    expect(form.querySelector('#order-name').getAttribute('aria-invalid')).toBe('true');
  });

  test('links focus their field, or the rich text editor', () => {
    submit();
    summary().querySelector('a[href="#order-email"]').click();
    expect(document.activeElement.id).toBe('order-email');

    summary().querySelector('a[href="#order-notes"]').click();
    expect(document.activeElement).toBe(form.querySelector('#order-notes').celerikEditor.root);
  });

  test('a successful validation hides it again', () => {
    submit();
    form.querySelector('[name="name"]').value = 'Ada';
    form.querySelector('[name="email"]').value = 'ada@example.com';
    form.querySelector('#order-notes').celerikEditor.setText('Two please', 'user');
    expect(parsley.validate()).toBe(true);
    expect(summary().hidden).toBe(true);
  });
});
