import { deepMerge } from './core.js';

// The inputs of a Parsley field, as a jQuery collection: every radio button or checkbox
// of a group (Parsley groups them by name), or the single input.
export function fieldInputs(field) {
  return (field.$elements ?? []).reduce((all, $input) => all.add($input), field.$element);
}

// The elements that show a field's state: its inputs, and the visible wrapper of a
// searchable select (Tom Select hides the select).
function stateElements(field) {
  const wrapper = field.$element[0].tomselect?.wrapper;
  return wrapper ? fieldInputs(field).add(wrapper) : fieldInputs(field);
}

const NUMBER = /^-?(\d+\.?\d*|\.\d+)$/;

// Compares a field value with a bound (min, max, the start of a range): numbers as
// numbers, anything else as text, which orders ISO dates, times and date-times
// (2026-11-05, 09:30, 2026-11-05T09:30). Parsley compares as numbers only, so it rejects
// every time and date-time with a min or max. Returns a negative number, 0 or a positive one.
export function compareValues(value, bound) {
  if (NUMBER.test(value) && NUMBER.test(bound)) {
    return parseFloat(value) - parseFloat(bound);
  }
  return value < bound ? -1 : Number(value > bound);
}

// Validators that replace Parsley's min, max and range, and add "after" (the end of a date
// range: on or after its start, given by a selector). Empty values are left to "required".
export const boundValidators = {
  min: (value, min) => value === '' || compareValues(value, min) >= 0,
  max: (value, max) => value === '' || compareValues(value, max) <= 0,
  range: (value, [min, max]) => value === '' || (compareValues(value, min) >= 0 && compareValues(value, max) <= 0),
  after: (value, start) => value === '' || !start || compareValues(value, start) >= 0,
};

// Parsley settings that produce Bootstrap 5 validation markup.
export const parsleyDefaults = {
  errorClass: 'is-invalid',
  successClass: 'is-valid',
  classHandler: stateElements,
  // Parsley's default, plus the search boxes of searchable selects (Tom Select): the
  // hidden select is the field.
  excluded: 'input[type=button], input[type=submit], input[type=reset], input[type=hidden], .ts-wrapper input',
  errorsContainer: (field) => field.$element.closest('.mb-3'),
  errorsWrapper: '<div class="invalid-feedback d-block" aria-live="polite"></div>',
  errorTemplate: '<div></div>',
};

// With an error summary, the summary is announced and focused instead: no live region
// per field (several would be announced at once) and no focus on the first field.
const summaryDefaults = {
  focus: 'none',
  errorsWrapper: '<div class="invalid-feedback d-block"></div>',
};

export function buildParsleyConfig(options) {
  const defaults = options.errorSummary ? { ...parsleyDefaults, ...summaryDefaults } : parsleyDefaults;
  return deepMerge(defaults, options.options);
}

// Fills the error summary with one link per invalid field, as {id, message} pairs.
export function renderErrorSummary(summary, errors) {
  const list = summary.querySelector('ul');
  list.replaceChildren(...errors.map(({ id, message }) => {
    const item = document.createElement('li');
    const link = item.appendChild(document.createElement('a'));
    link.href = `#${id}`;
    link.className = 'alert-link';
    link.textContent = message;
    return item;
  }));
  summary.hidden = errors.length === 0;
}
