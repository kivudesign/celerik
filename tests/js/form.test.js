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

describe('date, time and date range fields', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form-dates');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const input = (name) => form.querySelector(`[name="${name}"]`);
  const fill = (values) => {
    for (const [name, value] of Object.entries(values)) {
      input(name).value = value;
    }
  };
  const invalid = () => [...form.querySelectorAll('.is-invalid')].map((element) => element.name);

  test('times and date-times are checked against min and max as times, not numbers', () => {
    fill({ 'stay[start]': '2026-03-01', 'stay[end]': '2026-03-04', arrival: '09:30', day: '2026-06-01' });
    expect(parsley.validate()).toBe(true);

    fill({ arrival: '07:45', day: '2027-01-01' });
    expect(parsley.validate()).toBe(false);
    expect(invalid()).toEqual(['day', 'arrival']);
  });

  test('a date range needs both dates, the end on or after the start', () => {
    expect(parsley.validate()).toBe(false);
    expect(invalid()).toEqual(['stay[start]', 'stay[end]']);

    fill({ 'stay[start]': '2026-03-04', 'stay[end]': '2026-03-01' });
    expect(parsley.validate()).toBe(false);
    expect(invalid()).toEqual(['stay[end]']);
    expect(input('stay[end]').closest('.mb-3').textContent).toMatch('on or after the start');

    fill({ 'stay[end]': '2026-03-04' });
    expect(parsley.validate()).toBe(true);

    // Time ranges too, and an empty optional range is fine.
    fill({ 'shift[start]': '14:00', 'shift[end]': '09:00' });
    expect(parsley.validate()).toBe(false);
    expect(invalid()).toEqual(['shift[end]']);
  });

  test('the end picker starts at the start, never before its own min', () => {
    const start = input('stay[start]');
    const end = input('stay[end]');
    expect(end.getAttribute('min')).toBe('2026-01-01');

    start.value = '2026-05-10';
    start.dispatchEvent(new Event('change'));
    expect(end.getAttribute('min')).toBe('2026-05-10');

    start.value = '';
    start.dispatchEvent(new Event('change'));
    expect(end.getAttribute('min')).toBe('2026-01-01');
  });

  test('the end picker follows a start from old input, and a reset', async () => {
    parsley.destroy();
    form = loadFixture('form-dates');
    form.querySelector('[name="stay[start]"]').setAttribute('value', '2026-04-02');
    form.querySelector('[name="stay[start]"]').value = '2026-04-02';
    parsley = await initForm(form, readOptions(form));
    expect(input('stay[end]').getAttribute('min')).toBe('2026-04-02');

    input('stay[start]').value = '2026-08-01';
    input('stay[start]').dispatchEvent(new Event('change'));
    form.reset();
    await new Promise((resolve) => setTimeout(resolve));
    expect(input('stay[end]').getAttribute('min')).toBe('2026-04-02');
  });

  test('changing the start checks an end that was already checked', () => {
    fill({ 'stay[start]': '2026-03-01', 'stay[end]': '2026-03-04' });
    parsley.validate();
    expect(input('stay[end]').classList.contains('is-valid')).toBe(true);

    fill({ 'stay[start]': '2026-03-09' });
    input('stay[start]').dispatchEvent(new Event('change'));
    expect(input('stay[end]').classList.contains('is-invalid')).toBe(true);
    // One message: the end's min now follows the start, but only "after" is shown.
    const messages = input('stay[end]').closest('.mb-3').querySelectorAll('.invalid-feedback li, .invalid-feedback > div');
    expect([...messages].map((message) => message.textContent)).toEqual(['This must be on or after the start.']);
  });
});

describe('sections', () => {
  let form;
  let parsley;

  beforeEach(async () => {
    form = loadFixture('form-layout');
    parsley = await initForm(form, readOptions(form));
  });

  afterEach(() => parsley.destroy());

  const company = () => form.querySelector('details.celerik-form-section');
  const fillNames = () => {
    form.querySelector('[name="first_name"]').value = 'Ada';
    form.querySelector('[name="last_name"]').value = 'Lovelace';
  };

  test('a collapsed section opens when a field inside it is invalid', () => {
    fillNames();
    expect(company().open).toBe(false);

    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

    expect(company().open).toBe(true);
    const link = form.querySelector('.celerik-error-summary a');
    expect(link.getAttribute('href')).toBe('#signup-vat');
  });

  test('an error summary link opens the collapsed section of its field', () => {
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    company().open = false;

    form.querySelector('.celerik-error-summary a[href="#signup-vat"]').click();

    expect(company().open).toBe(true);
    expect(document.activeElement).toBe(form.querySelector('#signup-vat'));
  });

  test('a valid collapsed section stays closed', () => {
    fillNames();
    form.querySelector('[name="vat"]').value = 'FR123';
    expect(parsley.validate()).toBe(true);
    expect(company().open).toBe(false);
  });
});

describe('wizard', () => {
  let form;
  let parsley;

  const setUp = async (fixture) => {
    form = loadFixture(fixture);
    parsley = await initForm(form, readOptions(form));
  };

  afterEach(() => parsley.destroy());

  const button = (name) => form.querySelector(`[data-celerik-wizard="${name}"]`);
  const submitButton = () => form.querySelector('.celerik-wizard-nav [type="submit"]');
  const visibleStep = () => [...form.querySelectorAll('[data-celerik-step]')].findIndex((step) => !step.hidden);
  const current = () => form.querySelector('.celerik-wizard-steps [aria-current="step"]').textContent;
  const fill = (values) => {
    for (const [name, value] of Object.entries(values)) {
      const input = form.querySelector(`[name="${name}"]`);
      if (input.type === 'checkbox') {
        input.checked = value;
      } else {
        input.value = value;
      }
    }
  };
  const submit = () => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
  const invalid = () => [...form.querySelectorAll('.is-invalid')].map((element) => element.name);

  test('Next checks only the current step, then shows the next one and focuses its heading', async () => {
    await setUp('form-wizard');
    expect(visibleStep()).toBe(0);
    expect(button('previous').hidden).toBe(true);
    expect(submitButton().hidden).toBe(true);

    button('next').click();
    expect(visibleStep()).toBe(0);
    expect(invalid()).toEqual(['name', 'email']);
    expect(document.activeElement).toBe(form.querySelector('[name="name"]'));

    fill({ name: 'Ada', email: 'ada@example.com' });
    button('next').click();
    expect(visibleStep()).toBe(1);
    // The later steps were not checked.
    expect(invalid()).toEqual([]);
    expect(document.activeElement.id).toBe('join-section-Team-heading');
    expect(document.activeElement.textContent).toBe('Step 2 of 3: Team');
    expect(current()).toMatch(/^2Team/);
    // "(completed)", for screen readers, on the steps before the current one only.
    const done = [...form.querySelectorAll('.celerik-wizard-done')].map((span) => !span.hidden);
    expect(done).toEqual([true, false, false]);
    expect(button('previous').hidden).toBe(false);
  });

  test('Back needs no check; the last step shows the submit button', async () => {
    await setUp('form-wizard');
    fill({ name: 'Ada', email: 'ada@example.com', team: 'Engines' });
    button('next').click();
    button('next').click();
    expect(visibleStep()).toBe(2);
    expect(button('next').hidden).toBe(true);
    expect(submitButton().hidden).toBe(false);

    fill({ team: '' });
    button('previous').click();
    expect(visibleStep()).toBe(1);
    expect(invalid()).toEqual([]);
    expect(submitButton().hidden).toBe(true);
    expect(form.querySelector('.celerik-wizard-steps li:nth-child(3) .celerik-wizard-done').hidden).toBe(true);
  });

  test('Enter in a field means Next before the last step', async () => {
    await setUp('form-wizard');
    fill({ name: 'Ada', email: 'ada@example.com' });
    const submitted = jest.fn();
    parsley.on('form:validate', submitted);

    const enter = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
    form.querySelector('[name="email"]').dispatchEvent(enter);

    expect(enter.defaultPrevented).toBe(true);
    expect(visibleStep()).toBe(1);
    expect(submitted).not.toHaveBeenCalled();
  });

  test('a failed submit shows the first step with an error and focuses its field', async () => {
    await setUp('form-wizard');
    fill({ name: 'Ada', email: 'ada@example.com', team: 'Engines' });
    button('next').click();
    button('next').click();
    // The server or another script emptied a field of an earlier step.
    fill({ team: '', terms: true });

    submit();

    expect(visibleStep()).toBe(1);
    expect(document.activeElement).toBe(form.querySelector('[name="team"]'));
    expect(submitButton().hidden).toBe(true);
  });

  test('with an error summary: a step lists its errors there, and the links show their step', async () => {
    await setUp('form-wizard-summary');
    const summary = form.querySelector('.celerik-error-summary');

    button('next').click();
    expect(summary.hidden).toBe(false);
    expect(document.activeElement).toBe(summary);
    expect([...summary.querySelectorAll('a')].map((a) => a.getAttribute('href'))).toEqual(['#join-name']);

    fill({ name: 'Ada' });
    button('next').click();
    expect(summary.hidden).toBe(true);
    expect(visibleStep()).toBe(1);

    fill({ name: '' });
    submit();
    expect(visibleStep()).toBe(0);
    expect(document.activeElement).toBe(summary);
    button('next').click();
    fill({ name: 'Ada' });

    // A link to a field of another step shows that step.
    submit();
    button('previous').click();
    expect(visibleStep()).toBe(0);
    summary.querySelector('a[href="#join-team"]').click();
    expect(visibleStep()).toBe(1);
    expect(document.activeElement).toBe(form.querySelector('#join-team'));
  });
});

describe('repeater', () => {
  let form;
  let parsley;

  const setUp = async (fixture = 'form-repeater') => {
    form = loadFixture(fixture);
    parsley = await initForm(form, readOptions(form));
  };

  afterEach(() => parsley.destroy());

  const rows = () => [...form.querySelectorAll('.celerik-repeater-items > li')];
  const keys = () => rows().map((row) => row.getAttribute('data-celerik-key'));
  const labels = () => rows().map((row) => row.querySelector('legend').textContent);
  const add = () => form.querySelector('[data-celerik-repeater-add]');
  const addRow = async () => {
    add().click();
    // The row's searchable select is set up asynchronously.
    await new Promise((resolve) => setTimeout(resolve));
  };
  const within = (row, selector) => row.querySelector(selector);

  test('Add appends an empty row with the next key, set up like the others, and focuses it', async () => {
    await setUp();
    expect(keys()).toEqual(['0']);

    await addRow();

    expect(keys()).toEqual(['0', '1']);
    expect(labels()).toEqual(['Line 1', 'Line 2']);
    const row = rows()[1];
    expect(within(row, 'select').name).toBe('lines[1][product]');
    expect(row.querySelector('label[for="order-lines-1-qty"]').textContent).toMatch('Quantity');
    // Its searchable select works, and it has the focus.
    expect(within(row, 'select').tomselect).toBeDefined();
    expect(document.activeElement).toBe(within(row, 'select').tomselect.control_input);
    // Its date range's end follows its own start.
    const start = within(row, '[name="lines[1][delivery][start]"]');
    start.value = '2026-05-01';
    start.dispatchEvent(new Event('change'));
    expect(within(row, '[name="lines[1][delivery][end]"]').getAttribute('min')).toBe('2026-05-01');
  });

  test('new rows are validated and listed in the error summary', async () => {
    await setUp();
    await addRow();

    expect(parsley.validate()).toBe(false);
    const links = [...form.querySelectorAll('.celerik-error-summary a')].map((a) => a.getAttribute('href'));
    expect(links).toEqual(['#order-lines-0-product', '#order-lines-0-qty', '#order-lines-1-product', '#order-lines-1-qty']);
  });

  test('min and max: Remove and Add are disabled at the limits', async () => {
    await setUp();
    expect(within(rows()[0], '[data-celerik-repeater-remove]').disabled).toBe(true);

    await addRow();
    await addRow();
    expect(rows()).toHaveLength(3);
    expect(add().disabled).toBe(true);
    expect(rows().every((row) => !within(row, '[data-celerik-repeater-remove]').disabled)).toBe(true);
  });

  test('Remove deletes the row, renames the others and moves focus to the next row', async () => {
    await setUp();
    await addRow();
    await addRow();

    within(rows()[1], '[data-celerik-repeater-remove]').click();

    expect(keys()).toEqual(['0', '2']);
    expect(labels()).toEqual(['Line 1', 'Line 2']);
    expect(document.activeElement).toBe(within(rows()[1], 'select').tomselect.control_input);
    expect(add().disabled).toBe(false);
    // The removed row's fields are no longer validated.
    parsley.validate();
    expect(parsley.fields.some((field) => field.$element[0].name.startsWith('lines[1]'))).toBe(false);

    // The next new key is never reused.
    await addRow();
    expect(keys()).toEqual(['0', '2', '3']);
  });

  test('Move up and Move down reorder the rows and keep the focus', async () => {
    await setUp();
    await addRow();
    const second = rows()[1];
    const up = within(second, '[data-celerik-repeater-move="up"]');
    up.focus();

    up.click();

    expect(keys()).toEqual(['1', '0']);
    expect(labels()).toEqual(['Line 1', 'Line 2']);
    // Up is now disabled at the top: focus moves to Down.
    expect(up.disabled).toBe(true);
    expect(document.activeElement).toBe(within(second, '[data-celerik-repeater-move="down"]'));

    document.activeElement.click();
    expect(keys()).toEqual(['0', '1']);
  });

  test('a wizard step checks the rows added to it', async () => {
    await setUp('form-wizard-repeater');
    const next = form.querySelector('[data-celerik-wizard="next"]');
    await addRow();

    next.click();
    expect(form.querySelector('[data-celerik-step="0"]').hidden).toBe(false);
    expect(form.querySelector('[name="people[0][name]"]').classList.contains('is-invalid')).toBe(true);

    form.querySelector('[name="people[0][name]"]').value = 'Ada';
    next.click();
    expect(form.querySelector('[data-celerik-step="1"]').hidden).toBe(false);
  });
});
