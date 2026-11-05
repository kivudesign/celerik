import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import { buildNotyfConfig, toastFor, unescapeHtml } from './notification-config.js';

let shared = null;

function create(options) {
  const notyf = new Notyf(buildNotyfConfig(options));
  const dismissLabel = options.labels?.dismiss ?? 'Dismiss notification';

  // Messages reach Notyf escaped; its live-region announcer (textContent) must read the
  // original text, not "&amp;". _announce is internal: skip the fix if it disappears.
  const view = notyf.view;
  if (typeof view?._announce === 'function') {
    const announce = view._announce.bind(view);
    view._announce = (message) => announce(unescapeHtml(message));
  }

  return {
    // Notyf's dismiss buttons have no accessible name (WCAG 4.1.2): add one.
    open(toast) {
      const notification = notyf.open(toast);
      for (const button of document.querySelectorAll('.notyf__dismiss-btn:not([aria-label])')) {
        button.setAttribute('aria-label', dismissLabel);
      }
      return notification;
    },
    notyf,
  };
}

// One Notyf per page: the first Notification component configures it.
export function initNotification(element, options) {
  shared ??= create(options);
  for (const { type, message } of options.messages ?? []) {
    shared.open(toastFor(type, message));
  }
  return shared.notyf;
}

// Celerik.notify(type, message): for AJAX responses and other client-side events.
export function notify(type, message) {
  shared ??= create({});
  return shared.open(toastFor(type, message));
}
