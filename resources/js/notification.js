import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';
import {
  buildNotyfConfig, durationFor, pausableTimer, toastFor, unescapeHtml, uncoverScroll,
} from './notification-config.js';

let shared = null;

// Notyf's own timer cannot pause: toasts are opened without one, and this timer
// dismisses them, waiting while the pointer or keyboard focus is on the toast.
function dismissWhenUnused(notyf, notification, duration) {
  const node = notyf.view?.notifications?.find((entry) => entry.notification === notification)?.node;
  const timer = pausableTimer(duration, () => notyf.dismiss(notification));
  if (!node) {
    return;
  }
  let hovered = false;
  let focused = false;
  const update = () => (hovered || focused ? timer.pause() : timer.resume());
  node.addEventListener('mouseenter', () => { hovered = true; update(); });
  node.addEventListener('mouseleave', () => { hovered = false; update(); });
  node.addEventListener('focusin', () => { focused = true; update(); });
  node.addEventListener('focusout', (event) => {
    focused = node.contains(event.relatedTarget);
    update();
  });
}

// Toasts float over the page: when keyboard focus lands on an element behind them
// (after the browser's own scrolling), scroll it into the clear (WCAG 2.4.11). Mouse
// focus is left alone, so clicks never make the page jump.
function keepFocusInTheClear(container, y) {
  document.addEventListener('focusin', (event) => {
    const target = event.target;
    if (!(target instanceof Element) || container.contains(target)) {
      return;
    }
    requestAnimationFrame(() => {
      if (document.activeElement !== target || !target.matches(':focus-visible')) {
        return;
      }
      const toasts = [...container.querySelectorAll('.notyf__toast:not(.notyf__toast--disappear)')]
        .map((toast) => toast.getBoundingClientRect());
      const delta = uncoverScroll(target.getBoundingClientRect(), toasts, { y });
      if (delta !== 0) {
        window.scrollBy({ top: delta, behavior: 'instant' });
      }
    });
  });
}

function create(options) {
  const config = buildNotyfConfig(options);
  const notyf = new Notyf(config);
  if (notyf.view?.container) {
    keepFocusInTheClear(notyf.view.container, config.position.y);
  }
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
      const duration = durationFor(config, toast.type);
      const notification = notyf.open({ ...toast, duration: 0 });
      for (const button of document.querySelectorAll('.notyf__dismiss-btn:not([aria-label])')) {
        button.setAttribute('aria-label', dismissLabel);
      }
      if (duration > 0) {
        dismissWhenUnused(notyf, notification, duration);
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
