// Theme tokens (theme/theme.schema.json) in the browser: the stylesheet, the contrast
// report and the validation of src/Theme in PHP. The stylesheet must be the same text as
// Theme::toCss(): tests/js/theme-css.test.js compares both.

export const BASES = ['bootstrap', 'material', 'tailwind'];
export const MODES = ['light', 'dark'];
export const TOKENS = [
  'primary', 'primaryHover', 'onPrimary', 'bodyBg', 'bodyColor',
  'muted', 'surface', 'border', 'inputBorder', 'danger',
];

export const SHADOWS = {
  none: 'none',
  subtle: '0 1px 2px rgba(0, 0, 0, 0.08)',
  elevated: '0 1px 2px rgba(0, 0, 0, 0.3), 0 1px 3px 1px rgba(0, 0, 0, 0.15)',
  outlined: '0 1px 2px 0 rgba(0, 0, 0, 0.05), 0 0 0 1px var(--bs-border-color)',
};

const FOCUS_RING_ALPHA = { bootstrap: '0.25', material: '0.35', tailwind: '0.5' };

// Bootstrap compiles these components with its default blue: bind them to the theme.
const BOOTSTRAP_BINDINGS = `.btn-primary {
  --bs-btn-color: var(--celerik-on-primary);
  --bs-btn-bg: var(--celerik-primary);
  --bs-btn-border-color: var(--celerik-primary);
  --bs-btn-hover-color: var(--celerik-on-primary);
  --bs-btn-hover-bg: var(--celerik-primary-hover);
  --bs-btn-hover-border-color: var(--celerik-primary-hover);
  --bs-btn-active-color: var(--celerik-on-primary);
  --bs-btn-active-bg: var(--celerik-primary-hover);
  --bs-btn-active-border-color: var(--celerik-primary-hover);
  --bs-btn-disabled-color: var(--celerik-on-primary);
  --bs-btn-disabled-bg: var(--celerik-primary);
  --bs-btn-disabled-border-color: var(--celerik-primary);
  --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
}

.btn-outline-primary {
  --bs-btn-color: var(--celerik-primary);
  --bs-btn-border-color: var(--celerik-primary);
  --bs-btn-hover-color: var(--celerik-on-primary);
  --bs-btn-hover-bg: var(--celerik-primary);
  --bs-btn-hover-border-color: var(--celerik-primary);
  --bs-btn-active-color: var(--celerik-on-primary);
  --bs-btn-active-bg: var(--celerik-primary-hover);
  --bs-btn-active-border-color: var(--celerik-primary-hover);
  --bs-btn-focus-shadow-rgb: var(--bs-primary-rgb);
}

.form-control,
.form-select,
.form-check-input {
  border-color: var(--celerik-input-border);
}

.form-control:focus,
.form-select:focus,
.form-check-input:focus {
  border-color: var(--celerik-primary);
  box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.form-check-input:checked {
  background-color: var(--celerik-primary);
  border-color: var(--celerik-primary);
}

.nav-pills {
  --bs-nav-pills-link-active-bg: var(--celerik-primary);
  --bs-nav-pills-link-active-color: var(--celerik-on-primary);
}

.pagination {
  --bs-pagination-active-bg: var(--celerik-primary);
  --bs-pagination-active-border-color: var(--celerik-primary);
  --bs-pagination-active-color: var(--celerik-on-primary);
  --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
}

.celerik-scheduler {
  --fc-button-text-color: var(--celerik-on-primary);
  --fc-button-bg-color: var(--celerik-primary);
  --fc-button-border-color: var(--celerik-primary);
  --fc-button-hover-bg-color: var(--celerik-primary-hover);
  --fc-button-hover-border-color: var(--celerik-primary-hover);
  --fc-button-active-bg-color: var(--celerik-primary-hover);
  --fc-button-active-border-color: var(--celerik-primary-hover);
  --fc-event-bg-color: var(--celerik-primary);
  --fc-event-border-color: var(--celerik-primary);
  --fc-event-text-color: var(--celerik-on-primary);
}`;

export function rgb(hex) {
  return [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16)).join(', ');
}

function block(selector, properties) {
  return `${selector} {\n${Object.entries(properties).map(([name, value]) => `  ${name}: ${value};\n`).join('')}}\n`;
}

function palette(theme, mode) {
  const color = theme[mode];
  const properties = {
    '--bs-primary': color.primary,
    '--bs-primary-rgb': rgb(color.primary),
    '--bs-link-color': color.primary,
    '--bs-link-color-rgb': rgb(color.primary),
    '--bs-link-hover-color': color.primaryHover,
    '--bs-link-hover-color-rgb': rgb(color.primaryHover),
    '--bs-primary-text-emphasis': color.primaryHover,
    '--bs-body-bg': color.bodyBg,
    '--bs-body-bg-rgb': rgb(color.bodyBg),
    '--bs-body-color': color.bodyColor,
    '--bs-body-color-rgb': rgb(color.bodyColor),
    '--bs-secondary-color': color.muted,
    '--bs-tertiary-bg': color.surface,
    '--bs-tertiary-bg-rgb': rgb(color.surface),
    '--bs-border-color': color.border,
    '--bs-focus-ring-color': `rgba(${rgb(color.primary)}, ${FOCUS_RING_ALPHA[theme.base]})`,
    '--bs-form-invalid-color': color.danger,
    '--bs-form-invalid-border-color': color.danger,
    '--bs-danger-text-emphasis': color.danger,
    '--celerik-primary': color.primary,
    '--celerik-on-primary': color.onPrimary,
    '--celerik-primary-hover': color.primaryHover,
    '--celerik-input-border': color.inputBorder,
  };
  // The tailwind stylesheet derives these from its palette too.
  if (theme.base === 'tailwind') {
    properties['--celerik-grid-header-color'] = color.muted;
    properties['--celerik-focus-ring'] = `0 0 0 2px ${color.bodyBg}, 0 0 0 4px ${color.primary}`;
  }
  return properties;
}

// The theme's stylesheet: the same text as Theme::toCss() for a valid theme.
export function themeToCss(theme) {
  const files = theme.base === 'bootstrap' ? 'celerik.css' : `celerik.css and celerik-${theme.base}.css`;
  let css = `/* Celerik UI theme "${theme.name}" (base: ${theme.base}). Load it after ${files}. */\n`;
  css += block(':root,\n[data-bs-theme=light]', palette(theme, 'light'));
  css += `\n${block('[data-bs-theme=dark]', palette(theme, 'dark'))}`;

  const shape = {};
  if (theme.radius !== undefined && theme.radius !== null) {
    const radius = `${Math.round(theme.radius * 1000) / 1000}rem`;
    shape['--bs-border-radius'] = radius;
    shape['--celerik-radius'] = radius;
  }
  if (theme.fontFamily) {
    shape['--bs-body-font-family'] = theme.fontFamily;
  }
  if (theme.shadow) {
    shape['--celerik-surface-shadow'] = SHADOWS[theme.shadow];
  }
  if (Object.keys(shape).length > 0) {
    css += `\n${block(':root', shape)}`;
  }

  if (theme.base === 'bootstrap') {
    css += `\n${BOOTSTRAP_BINDINGS}\n`;
  }
  return css;
}

function luminance(hex) {
  const [r, g, b] = [1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16) / 255)
    .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

// WCAG 2.x contrast ratio of two "#rrggbb" colors, from 1 to 21.
export function contrastRatio(foreground, background) {
  const [lighter, darker] = [luminance(foreground), luminance(background)].sort((a, b) => b - a);
  return (lighter + 0.05) / (darker + 0.05);
}

// Every pair of theme/contrast.json in light then dark mode. Thresholds are not rounded.
export function contrastReport(theme, pairs) {
  return MODES.flatMap((mode) => pairs.map((pair) => {
    const ratio = contrastRatio(theme[mode][pair.foreground], theme[mode][pair.background]);
    return { ...pair, mode, ratio, passes: ratio >= pair.minimum };
  }));
}

// Ratios are shown truncated, so a failing 4.497:1 never reads "4.50:1".
export function formatRatio(ratio) {
  return (Math.floor(ratio * 100) / 100).toFixed(2);
}

const HEX = /^#[0-9a-f]{6}$/i;
const NAME = /^[\p{L}\p{N} ._-]{1,60}$/u;
const FONT = /^[A-Za-z0-9 ,'"-]{1,200}$/;
const KEYS = ['name', 'base', 'light', 'dark', 'radius', 'fontFamily', 'shadow'];

export function isColor(value) {
  return typeof value === 'string' && HEX.test(value);
}

// The same rules as Theme::fromArray(): a normalized copy of the theme, or an error message.
export function validateTheme(data) {
  if (data === null || typeof data !== 'object' || Array.isArray(data)) {
    return { error: 'A theme must be a JSON object.' };
  }
  const unknown = Object.keys(data).filter((key) => !KEYS.includes(key));
  if (unknown.length) {
    return { error: `Unknown theme keys: ${unknown.join(', ')}.` };
  }
  if (typeof data.name !== 'string' || !NAME.test(data.name)) {
    return { error: 'A theme name has 1 to 60 letters, digits, spaces, dots, dashes or underscores.' };
  }
  if (!BASES.includes(data.base)) {
    return { error: `Unknown base "${data.base}". Available: ${BASES.join(', ')}.` };
  }
  const theme = { name: data.name, base: data.base };
  for (const mode of MODES) {
    const colors = data[mode];
    if (colors === null || typeof colors !== 'object' || Array.isArray(colors)) {
      return { error: `The ${mode} palette must be an object.` };
    }
    const keys = Object.keys(colors);
    if (keys.length !== TOKENS.length || !TOKENS.every((token) => keys.includes(token))) {
      return { error: `The ${mode} palette needs exactly these colors: ${TOKENS.join(', ')}.` };
    }
    for (const token of TOKENS) {
      if (!isColor(colors[token])) {
        return { error: `Invalid color "${colors[token]}" for ${mode}.${token}: use #rrggbb.` };
      }
    }
    theme[mode] = Object.fromEntries(TOKENS.map((token) => [token, colors[token].toLowerCase()]));
  }
  if (data.radius !== undefined && data.radius !== null) {
    if (typeof data.radius !== 'number' || data.radius < 0 || data.radius > 2) {
      return { error: 'The radius must be a number between 0 and 2 (rem).' };
    }
    theme.radius = Math.round(data.radius * 1000) / 1000;
  }
  if (data.fontFamily !== undefined && data.fontFamily !== null) {
    if (typeof data.fontFamily !== 'string' || !FONT.test(data.fontFamily)) {
      return { error: 'A font stack has only family names, spaces, commas, quotes and dashes.' };
    }
    theme.fontFamily = data.fontFamily;
  }
  if (data.shadow !== undefined && data.shadow !== null) {
    if (!Object.hasOwn(SHADOWS, data.shadow)) {
      return { error: `Unknown shadow "${data.shadow}". Available: ${Object.keys(SHADOWS).join(', ')}.` };
    }
    theme.shadow = data.shadow;
  }
  return { theme };
}
