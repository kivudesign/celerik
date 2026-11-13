// A form's sections as the steps of a wizard (Form::wizard()). PHP renders the steps, the
// step indicator and the buttons, with the current step shown; this moves between them.
// Next checks the current step's fields; a failed submit shows the first step with an error.
//
// onInvalid(fields) shows the errors of a step (the error summary, or focus on the first
// field); onValid() clears them.

const NOT_TEXT = ['submit', 'button', 'reset', 'checkbox', 'radio', 'file', 'range', 'color'];

export function initWizard(form, parsley, { onInvalid, onValid, summary }) {
  const steps = [...form.querySelectorAll('[data-celerik-step]')];
  if (steps.length === 0) {
    return null;
  }
  const indicators = [...form.querySelectorAll('.celerik-wizard-steps > li')];
  const previous = form.querySelector('[data-celerik-wizard="previous"]');
  const next = form.querySelector('[data-celerik-wizard="next"]');
  const submit = form.querySelector('.celerik-wizard-nav [type="submit"]');
  const last = steps.length - 1;
  let current = Math.max(0, steps.findIndex((step) => !step.hidden));

  const show = (index, { focus = true } = {}) => {
    current = index;
    steps.forEach((step, i) => {
      step.hidden = i !== index;
    });
    indicators.forEach((item, i) => {
      item.classList.toggle('is-complete', i < index);
      const done = item.querySelector('.celerik-wizard-done');
      if (done) {
        done.hidden = i >= index;
      }
      if (i === index) {
        item.setAttribute('aria-current', 'step');
      } else {
        item.removeAttribute('aria-current');
      }
    });
    previous.hidden = index === 0;
    next.hidden = index === last;
    if (submit) {
      submit.hidden = index !== last;
    }
    // The heading announces "Step 2 of 3: Address" and starts the step's tab order.
    if (focus) {
      steps[index].querySelector('[tabindex="-1"]')?.focus();
    }
  };

  const stepOf = (element) => steps.findIndex((step) => step.contains(element));

  const goNext = () => {
    const invalid = parsley.fields
      .filter((field) => steps[current].contains(field.$element[0]))
      .filter((field) => field.validate() !== true);
    if (invalid.length > 0) {
      onInvalid(invalid);
      return;
    }
    onValid();
    show(Math.min(current + 1, last));
  };

  next.addEventListener('click', goNext);
  previous.addEventListener('click', () => show(Math.max(current - 1, 0)));

  // Enter in a text-like field submits the form: before the last step, it means Next.
  form.addEventListener('keydown', (event) => {
    const field = event.target;
    if (event.key !== 'Enter' || event.defaultPrevented || current === last
      || !(field instanceof HTMLInputElement) || NOT_TEXT.includes(field.type)) {
      return;
    }
    event.preventDefault();
    goNext();
  });

  // A failed submit: show the first step with an error. The error summary, if any, takes
  // focus; otherwise the first invalid field does (Parsley could not: it was hidden).
  parsley.on('form:error', () => {
    const invalid = parsley.fields.find((field) => field.validationResult !== true);
    const index = invalid ? stepOf(invalid.$element[0]) : -1;
    if (index >= 0) {
      if (index !== current) {
        show(index, { focus: false });
      }
      if (!summary) {
        invalid.$element[0].focus();
      }
    }
  });

  return {
    // Shows the step of an element, e.g. a field an error summary link points to.
    showStepOf(element) {
      const index = stepOf(element);
      if (index >= 0 && index !== current) {
        show(index, { focus: false });
      }
    },
  };
}
