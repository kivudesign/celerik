import TomSelect from 'tom-select/base';
import removeButton from 'tom-select/plugins/remove_button/plugin.js';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import { buildSelectConfig } from './select-config.js';

// The base build has no plugins: add the one we use (removable tags on multiple selects).
TomSelect.define('remove_button', removeButton);

// Searchable selects (Field::searchable(), Field::remote()). The original <select> stays
// in the form, hidden: it submits the value, and Parsley validates it.
export function initSelects(form) {
  return [...form.querySelectorAll('select[data-celerik-select]')].map((select) => {
    const options = JSON.parse(select.getAttribute('data-celerik-select') || '{}');
    const tomSelect = new TomSelect(select, buildSelectConfig(select, options));

    // Help and error texts describe the search box, which has the focus.
    const describedBy = select.getAttribute('aria-describedby');
    if (describedBy) {
      tomSelect.control_input.setAttribute('aria-describedby', describedBy);
    }
    // The search box is the control now (the label points to it): the hidden select is
    // out of the tab order, and hidden from assistive technology too.
    select.setAttribute('aria-hidden', 'true');
    // Error summary links and Parsley focus the hidden select: move to the search box.
    select.addEventListener('focus', () => tomSelect.focus());

    return tomSelect;
  });
}
