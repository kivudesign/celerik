import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import { buildEditorConfig, editorHtml } from './editor-config.js';

const LABELS = {
  header: 'Text style',
  linkInput: 'Link URL',
  linkPreview: 'Open link',
};

// Quill leaves a few controls without an accessible name (WCAG 4.1.2): the heading
// picker, the link tooltip's URL input and its preview link.
function nameQuillControls(container, toolbar, labels) {
  toolbar?.querySelector('.ql-header .ql-picker-label')?.setAttribute('aria-label', labels.header);
  container.querySelector('.ql-tooltip input[type="text"]')?.setAttribute('aria-label', labels.linkInput);
  container.querySelector('.ql-tooltip .ql-preview')?.setAttribute('aria-label', labels.linkPreview);
}

// The PHP <textarea> stays in the form as the submitted value (and as the no-JS
// fallback). Quill is created next to it, and Parsley keeps validating the textarea.
export function initEditor(textarea) {
  const options = JSON.parse(textarea.getAttribute('data-celerik-editor') || '{}');
  const container = document.createElement('div');
  container.className = 'celerik-editor';
  textarea.after(container);

  const quill = new Quill(container, buildEditorConfig(options));
  nameQuillControls(container, quill.getModule('toolbar')?.container, { ...LABELS, ...options.labels });
  if (textarea.value.trim() !== '') {
    // Converting to Quill's model keeps only the allowed formats.
    quill.setContents(quill.clipboard.convert({ html: textarea.value }), 'silent');
  }

  // Hide the textarea from sight, keyboard and screen readers; the editor replaces it.
  textarea.classList.add('visually-hidden');
  textarea.tabIndex = -1;
  textarea.setAttribute('aria-hidden', 'true');

  const editable = quill.root;
  editable.setAttribute('role', 'textbox');
  editable.setAttribute('aria-multiline', 'true');
  editable.setAttribute('aria-labelledby', `${textarea.id}-label`);
  for (const name of ['aria-describedby', 'aria-invalid']) {
    if (textarea.hasAttribute(name)) {
      editable.setAttribute(name, textarea.getAttribute(name));
    }
  }
  if (textarea.required) {
    editable.setAttribute('aria-required', 'true');
  }

  quill.on('text-change', () => {
    textarea.value = editorHtml(quill);
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
  });

  // Mirror the validation state Parsley (or the server) puts on the textarea.
  const mirror = () => {
    const invalid = textarea.classList.contains('is-invalid');
    container.classList.toggle('is-invalid', invalid);
    if (invalid) {
      editable.setAttribute('aria-invalid', 'true');
    } else {
      editable.removeAttribute('aria-invalid');
    }
  };
  mirror();
  new MutationObserver(mirror).observe(textarea, { attributes: true, attributeFilter: ['class'] });

  textarea.celerikEditor = quill;
  return quill;
}

export function initEditors(form) {
  return [...form.querySelectorAll('textarea[data-celerik-editor]')].map(initEditor);
}
