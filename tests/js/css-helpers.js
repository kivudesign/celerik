// Helpers for tests that read compiled stylesheets (themes.test.js, presets.test.js).

// Custom properties declared in a block whose selector list contains `selector`.
export function properties(css, selector) {
  const values = {};
  const code = css.replace(/\/\*[\s\S]*?\*\//g, '');
  for (const [, selectors, body] of code.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
    // Statements before a block (e.g. @charset "UTF-8";) are not part of its selector.
    if (!selectors.split(';').pop().split(',').map((s) => s.trim()).includes(selector)) {
      continue;
    }
    for (const [, name, value] of body.matchAll(/(--[\w-]+)\s*:\s*([^;]+);/g)) {
      values[name] = value.trim();
    }
  }
  return values;
}

function luminance(hex) {
  const value = hex.replace('#', '');
  const full = value.length === 3 ? [...value].map((c) => c + c).join('') : value;
  const [r, g, b] = [0, 2, 4].map((i) => parseInt(full.slice(i, i + 2), 16) / 255)
    .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
  return 0.2126 * r + 0.7152 * g + 0.0722 * b;
}

export function contrast(a, b) {
  const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);
  return (light + 0.05) / (dark + 0.05);
}

// "#FFF" and "#ffffff" are the same color: compare colors in this form.
export function normalizeHex(value) {
  const hex = String(value).trim().toLowerCase();
  return /^#[0-9a-f]{3}$/.test(hex) ? `#${[...hex.slice(1)].map((c) => c + c).join('')}` : hex;
}
