import $ from 'jquery';
import 'parsleyjs';
import Inputmask from 'inputmask';
import { buildParsleyConfig, renderErrorSummary } from './form-config.js';
import { csrfTokenFor } from './upload-config.js';

export async function initForm(element, options) {
  for (const input of element.querySelectorAll('[data-celerik-mask]')) {
    Inputmask(input.getAttribute('data-celerik-mask')).mask(input);
  }

  const parsley = $(element).parsley(buildParsleyConfig(options));

  // Server-side errors are rendered by PHP; drop them once the field is re-validated.
  parsley.on('field:validated', (field) => {
    const input = field.$element[0];
    input.closest('.mb-3')?.querySelector('.celerik-server-error')?.remove();
    if (field.isValid()) {
      input.removeAttribute('aria-invalid');
    } else {
      input.setAttribute('aria-invalid', 'true');
    }
  });

  const summary = options.errorSummary ? element.querySelector('.celerik-error-summary') : null;
  if (summary) {
    initErrorSummary(element, summary, parsley);
  }

  // Quill and FilePond are large: load them only for forms that use them.
  const enhancements = [];
  if (element.querySelector('textarea[data-celerik-editor]')) {
    enhancements.push(import('./editor.js').then(({ initEditors }) => initEditors(element)));
  }
  if (element.querySelector('input[type="file"][data-celerik-upload]')) {
    const token = csrfTokenFor(element, options);
    enhancements.push(import('./upload.js').then(({ initUploads }) => initUploads(element, token)));
  }
  await Promise.all(enhancements);

  return parsley;
}

// The error summary: filled and focused after a failed submit, focused on load when the
// server rendered errors, and its links move focus to the field (or its editor).
function initErrorSummary(element, summary, parsley) {
  parsley.on('form:error', () => {
    const errors = parsley.fields
      .filter((field) => field.validationResult !== true)
      .map((field) => ({ id: field.$element[0].id, message: field.getErrorsMessages()[0] ?? '' }))
      .filter(({ id, message }) => id && message);
    renderErrorSummary(summary, errors);
    summary.focus();
  });
  parsley.on('form:success', () => renderErrorSummary(summary, []));

  summary.addEventListener('click', (event) => {
    const link = event.target.closest('a[href^="#"]');
    const field = link && document.getElementById(link.getAttribute('href').slice(1));
    if (!field) {
      return;
    }
    event.preventDefault();
    (field.celerikEditor?.root ?? field).focus();
  });

  if (!summary.hidden) {
    summary.focus();
  }
}
