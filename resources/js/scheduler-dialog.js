// Keyboard-friendly dialog to create an event, or change an event's dates and delete it:
// dragging is not the only way to edit the calendar (WCAG 2.1.1, 2.5.7). A native <dialog>
// opened with showModal() keeps focus inside, closes with Escape and makes the page inert.
// Built with DOM methods and textContent only: no inline HTML, styles or scripts (CSP).

let counter = 0;

function create(tag, attributes = {}, text = null) {
  const element = document.createElement(tag);
  for (const [name, value] of Object.entries(attributes)) {
    if (value !== null && value !== undefined && value !== false) {
      element.setAttribute(name, value === true ? '' : value);
    }
  }
  if (text !== null) {
    element.textContent = text;
  }
  return element;
}

function field(id, label, input) {
  const wrapper = create('div', { class: 'mb-3' });
  wrapper.append(create('label', { class: 'form-label', for: id }, label), input);
  return wrapper;
}

// Date inputs show "YYYY-MM-DD" for all-day events, "YYYY-MM-DDTHH:mm" otherwise.
function convert(value, allDay) {
  if (!value) {
    return '';
  }
  return allDay ? value.slice(0, 10) : (value.length === 10 ? `${value}T09:00` : value.slice(0, 16));
}

// Opens the dialog and resolves with {action: 'save', title, start, end, allDay},
// {action: 'delete'} or null (cancelled). `validate(values)` returns an error message or null.
export function openEventDialog({ heading, values, labels, withTitle = false, canSave = true, canDelete = false, validate }) {
  counter += 1;
  const id = `celerik-event-dialog-${counter}`;
  const dialog = create('dialog', { class: 'celerik-scheduler-dialog', 'aria-labelledby': `${id}-heading` });
  const form = create('form', { method: 'dialog', novalidate: true });
  form.append(create('h2', { id: `${id}-heading`, class: 'h5 mb-3' }, heading));

  const title = create('input', { id: `${id}-title`, class: 'form-control', type: 'text', required: true });
  title.value = values.title ?? '';
  if (withTitle) {
    form.append(field(title.id, labels.title, title));
  }

  const allDay = create('input', { id: `${id}-allday`, class: 'form-check-input', type: 'checkbox' });
  allDay.checked = Boolean(values.allDay);
  const check = create('div', { class: 'form-check mb-3' });
  check.append(allDay, create('label', { class: 'form-check-label', for: allDay.id }, labels.allDay));

  const type = () => (allDay.checked ? 'date' : 'datetime-local');
  const start = create('input', { id: `${id}-start`, class: 'form-control', type: type(), required: true });
  const end = create('input', { id: `${id}-end`, class: 'form-control', type: type() });
  start.value = convert(values.start, allDay.checked);
  end.value = convert(values.end, allDay.checked);
  allDay.addEventListener('change', () => {
    for (const input of [start, end]) {
      const value = input.value;
      input.type = type();
      input.value = convert(value, allDay.checked);
    }
  });

  const error = create('div', { id: `${id}-error`, class: 'invalid-feedback d-block', role: 'alert' });
  const inputs = [title, start, end];
  if (canSave) {
    form.append(check, field(start.id, labels.start, start), field(end.id, labels.end, end));
  }
  form.append(error);

  const buttons = create('div', { class: 'd-flex flex-wrap gap-2 justify-content-end' });
  const deleteButton = create('button', { type: 'button', class: 'btn btn-outline-danger me-auto' }, labels.delete);
  const cancelButton = create('button', { type: 'button', class: 'btn btn-secondary' }, labels.cancel);
  const saveButton = create('button', { type: 'submit', class: 'btn btn-primary' }, labels.save);
  if (canDelete) {
    buttons.append(deleteButton);
  }
  buttons.append(cancelButton);
  if (canSave) {
    buttons.append(saveButton);
  }
  form.append(buttons);
  dialog.append(form);

  const opener = document.activeElement;
  document.body.append(dialog);

  return new Promise((resolve) => {
    let result = null;
    const close = (value) => {
      result = value;
      if (typeof dialog.close === 'function' && dialog.open) {
        dialog.close();
      } else {
        dialog.dispatchEvent(new Event('close'));
      }
    };

    dialog.addEventListener('close', () => {
      dialog.remove();
      if (opener?.isConnected) {
        opener.focus();
      }
      resolve(result);
    }, { once: true });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const value = { title: title.value.trim(), start: start.value, end: end.value, allDay: allDay.checked };
      const message = validate?.(value) ?? null;
      for (const input of inputs) {
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
      }
      if (message) {
        error.textContent = message;
        const invalid = withTitle && !value.title ? title : (!value.start ? start : end);
        invalid.setAttribute('aria-invalid', 'true');
        invalid.setAttribute('aria-describedby', error.id);
        invalid.focus();
        return;
      }
      close({ action: 'save', ...value });
    });
    cancelButton.addEventListener('click', () => close(null));
    deleteButton.addEventListener('click', () => close({ action: 'delete' }));

    if (typeof dialog.showModal === 'function') {
      dialog.showModal();
    } else {
      dialog.setAttribute('open', ''); // environments without <dialog> support (jsdom)
    }
    (withTitle ? title : (canSave ? start : cancelButton)).focus();
  });
}
