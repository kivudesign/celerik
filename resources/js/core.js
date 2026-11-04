// Component registry and auto-initialisation.
//
// PHP renders `data-celerik="<name>"` plus JSON in `data-celerik-options`; init() finds
// those elements and hands them to the initializer registered under that name.

const initializers = new Map();

export function register(name, initializer) {
  if (typeof initializer !== 'function') {
    throw new TypeError(`Celerik: initializer for "${name}" must be a function.`);
  }
  initializers.set(name, initializer);
}

export function readOptions(element) {
  const raw = element.getAttribute('data-celerik-options');
  if (!raw) {
    return {};
  }
  try {
    return JSON.parse(raw);
  } catch (error) {
    console.error(`Celerik: invalid options on #${element.id}`, error);
    return {};
  }
}

// Initialises every component inside `root` (and `root` itself). Safe to call again
// after injecting HTML: already initialised elements are skipped.
export function init(root = document) {
  const elements = [];
  if (root instanceof Element && root.matches('[data-celerik]')) {
    elements.push(root);
  }
  elements.push(...root.querySelectorAll('[data-celerik]'));

  for (const element of elements) {
    if (element.celerik) {
      continue;
    }
    const name = element.getAttribute('data-celerik');
    const initializer = initializers.get(name);
    if (!initializer) {
      console.warn(`Celerik: no initializer registered for "${name}".`);
      continue;
    }
    element.celerik = initializer(element, readOptions(element)) ?? true;
    element.dispatchEvent(new CustomEvent('celerik:ready', {
      bubbles: true,
      detail: { instance: element.celerik },
    }));
  }
}

export function autoInit() {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => init(), { once: true });
  } else {
    init();
  }
}

// Recursive merge used to apply raw library options from PHP `->options([...])` last.
export function deepMerge(target, source) {
  const output = { ...target };
  for (const [key, value] of Object.entries(source ?? {})) {
    output[key] = isPlainObject(value) && isPlainObject(output[key])
      ? deepMerge(output[key], value)
      : value;
  }
  return output;
}

function isPlainObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}
