import { deepMerge } from './core.js';

// Parsley settings that produce Bootstrap 5 validation markup.
export const parsleyDefaults = {
  errorClass: 'is-invalid',
  successClass: 'is-valid',
  classHandler: (field) => field.$element,
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
