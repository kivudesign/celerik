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

describe('radio, checkbox group, switch, range and color fields', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form-choices');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const inputs = (name) => [...form.querySelectorAll(`[name="${name}"]`)];

  test('a required group flags every input, and its message follows the group', () => {
    expect(parsley.validate()).toBe(false);

    for (const name of ['plan', 'topics[]']) {
      expect(inputs(name).length).toBe(3);
      expect(inputs(name).every((input) => input.classList.contains('is-invalid'))).toBe(true);
      expect(inputs(name).every((input) => input.getAttribute('aria-invalid') === 'true')).toBe(true);
      const group = inputs(name)[0].closest('.mb-3');
      expect(group.querySelector('fieldset')).not.toBeNull();
      expect(group.querySelector('.parsley-errors-list, .invalid-feedback:not(.celerik-server-error)').textContent)
        .toMatch(/required/i);
    }
    // The server error is replaced by Parsley's.
    expect(form.querySelector('.celerik-server-error')).toBeNull();
  });

  test('one radio and one checkbox are enough', () => {
    inputs('plan')[1].checked = true;
    inputs('topics[]')[2].checked = true;

    expect(parsley.validate()).toBe(true);
    expect(inputs('plan').some((input) => input.hasAttribute('aria-invalid'))).toBe(false);
    expect(new FormData(form).getAll('topics[]')).toEqual(['security']);
  });

  test('the range value follows the slider, and a reset', () => {
    jest.useFakeTimers();
    const range = inputs('volume')[0];
    const output = form.querySelector('output.celerik-range-value');
    expect(output.textContent).toBe('4');

    range.value = '7';
    range.dispatchEvent(new Event('input', { bubbles: true }));
    expect(output.textContent).toBe('7');

    form.reset();
    jest.runAllTimers();
    expect(output.textContent).toBe('4');
    jest.useRealTimers();
  });

  test('switch and color submit their values', () => {
    expect(inputs('newsletter')[0].getAttribute('role')).toBe('switch');
    inputs('newsletter')[0].checked = true;
    const data = new FormData(form);
    expect(data.get('newsletter')).toBe('1');
    expect(data.get('accent')).toBe('#0d6efd');
  });
});

describe('searchable selects (Tom Select)', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    global.fetch = jest.fn(async (url) => ({
      ok: true,
      json: async () => ({
        data: [{ value: 3, label: `Ada Lovelace (${new URL(url).searchParams.get('search')})` }],
        meta: {},
      }),
    }));
    form = loadFixture('form-select');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => {
    parsley.destroy();
    delete global.fetch;
  });

  const select = (name) => form.querySelector(`select[name="${name}"]`);

  test('replaces each select, keeps its value, labels and describes the search box', () => {
    for (const name of ['country', 'languages[]', 'author']) {
      expect(select(name).tomselect).toBeDefined();
    }
    const country = select('country').tomselect;
    // Tom Select points the label at its search box.
    const label = select('country').closest('.mb-3').querySelector('label');
    expect(label.htmlFor).toBe(country.control_input.id);
    expect(country.control_input.getAttribute('aria-labelledby')).toBe(label.id);
    expect(country.control_input.getAttribute('aria-describedby')).toBe('assign-country-help');
    expect(country.control_input.getAttribute('placeholder')).toBe('Choose a country');

    expect(select('languages[]').tomselect.getValue()).toEqual(['fr', 'ln']);
    expect(select('author').tomselect.getValue()).toBe('7');
    expect(new FormData(form).getAll('languages[]')).toEqual(['fr', 'ln']);
  });

  test('a required select shows its error on the visible control', () => {
    expect(parsley.validate()).toBe(false);
    const country = select('country').tomselect;
    expect(country.wrapper.classList.contains('is-invalid')).toBe(true);
    expect(country.control_input.getAttribute('aria-invalid')).toBe('true');

    country.setValue('ca');
    expect(parsley.validate()).toBe(true);
    expect(country.wrapper.classList.contains('is-invalid')).toBe(false);
    expect(country.control_input.hasAttribute('aria-invalid')).toBe(false);
  });

  test('focusing the hidden select focuses the search box (error summary links)', () => {
    select('country').focus();
    expect(document.activeElement).toBe(select('country').tomselect.control_input);
  });

  test('a remote select loads its choices from the endpoint as the user types', async () => {
    const author = select('author').tomselect;
    await new Promise((resolve) => {
      author.on('load', resolve);
      author.load('ada');
    });

    expect(global.fetch).toHaveBeenCalledWith(
      expect.stringMatching(/\/authors\/options\?search=ada$/),
      expect.objectContaining({ credentials: 'same-origin' }),
    );
    expect(author.options['3'].label).toBe('Ada Lovelace (ada)');
  });
});
