import { bootstrapComponent } from './bootstrap-global.js';

// Bootstrap handles opening, focus trapping, Escape and restoring focus; this only adds
// "open on load" (e.g. to show a form again with its server errors).
export function initModal(element, options) {
  const Modal = bootstrapComponent('Modal');
  if (!Modal) {
    return null;
  }
  const modal = Modal.getOrCreateInstance(element);
  if (options.open) {
    modal.show();
  }
  return modal;
}
