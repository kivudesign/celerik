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
