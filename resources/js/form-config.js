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

export function buildParsleyConfig(options) {
  return deepMerge(parsleyDefaults, options.options);
}
