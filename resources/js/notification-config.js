import { deepMerge } from './core.js';

export const TYPES = ['success', 'error', 'info', 'warning'];

// Notyf inserts messages with innerHTML: messages are always escaped first.
export function escapeHtml(text) {
  return String(text)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

// Notyf's screen-reader announcer uses textContent, so it must get the plain text back.
export function unescapeHtml(html) {
  return String(html)
    .replaceAll('&#39;', "'")
    .replaceAll('&quot;', '"')
    .replaceAll('&gt;', '>')
    .replaceAll('&lt;', '<')
    .replaceAll('&amp;', '&');
}

export function buildNotyfConfig(options = {}) {
  const duration = options.duration ?? 5000;
  const errorDuration = options.errorDuration ?? 0;
  const dismissible = options.dismissible ?? true;

  // Colors come from Bootstrap's CSS variables; text colors are set in SCSS for contrast.
  const type = (name, color, overrides = {}) => ({
    type: name,
    className: `celerik-toast celerik-toast-${name}`,
    background: `var(--bs-${color})`,
    duration,
    dismissible,
    ...overrides,
  });

  return deepMerge({
    position: options.position ?? { x: 'right', y: 'top' },
    duration,
    dismissible,
    ripple: false,
    types: [
      type('success', 'success'),
      type('error', 'danger', { duration: errorDuration, dismissible: true }),
      type('info', 'info', { icon: false }),
      type('warning', 'warning', { icon: false }),
    ],
  }, options.options);
}

export function toastFor(type, message) {
  if (!TYPES.includes(type)) {
    throw new TypeError(`Celerik: unknown notification type "${type}".`);
  }
  return { type, message: escapeHtml(message) };
}

// How long a toast of this type stays, in ms (0: until dismissed).
export function durationFor(config, type) {
  return config.types.find((entry) => entry.type === type)?.duration ?? config.duration ?? 0;
}

// A dismiss timer that can pause: toasts stay while the pointer or focus is on them,
// so they can be read and dismissed in time (WCAG 2.2.1, Timing Adjustable).
export function pausableTimer(duration, onExpire, clock = {
  set: (callback, ms) => setTimeout(callback, ms),
  clear: (id) => clearTimeout(id),
  now: () => Date.now(),
}) {
  let remaining = duration;
  let started = clock.now();
  let id = clock.set(onExpire, remaining);
  let running = true;

  return {
    pause() {
      if (running) {
        running = false;
        clock.clear(id);
        remaining -= clock.now() - started;
      }
    },
    resume() {
      if (!running) {
        running = true;
        started = clock.now();
        id = clock.set(onExpire, Math.max(remaining, 0));
      }
    },
  };
}

// How far to scroll the page so the focused element is not hidden behind toasts
// (WCAG 2.4.11, Focus Not Obscured): 0 when less than half of it is covered. The element
// clears the whole stack in its column: toasts at the top push it below the lowest one
// (scroll up, negative), toasts at the bottom above the highest one.
export function uncoverScroll(target, toasts, { y = 'top', margin = 8 } = {}) {
  const area = Math.max(1, target.width * target.height);
  let covered = 0;
  let edge = null;
  for (const toast of toasts) {
    const width = Math.min(target.right, toast.right) - Math.max(target.left, toast.left);
    if (width <= 0) {
      continue; // another column
    }
    edge = y === 'bottom' ? Math.min(edge ?? Infinity, toast.top) : Math.max(edge ?? -Infinity, toast.bottom);
    const height = Math.min(target.bottom, toast.bottom) - Math.max(target.top, toast.top);
    covered += height > 0 ? width * height : 0;
  }
  if (edge === null || covered / area < 0.5) {
    return 0;
  }
  return y === 'bottom' ? target.bottom - edge + margin : -(edge - target.top + margin);
}
