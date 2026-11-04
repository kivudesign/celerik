import $ from 'jquery';
import 'parsleyjs';
import Inputmask from 'inputmask';
import { buildParsleyConfig } from './form-config.js';

export function initForm(element, options) {
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

  return parsley;
}
