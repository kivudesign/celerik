import {
  contrastReport, formatRatio, isColor, MODES, themeToCss, TOKENS, validateTheme,
} from './theme-css.js';

// The ThemeBuilder (src/Components/ThemeBuilder.php): live preview, contrast report,
// presets and undo. The preview uses the real theme stylesheet (themeToCss(), the same
// text as Theme::toCss()), scoped to the preview area so a poor color choice never makes
// the builder's own controls unreadable. The base look (celerik-material.css,
// celerik-tailwind.css) is switched for the whole page.

const NAME = /^[\p{L}\p{N} ._-]{1,60}$/u;
const FONT = /^[A-Za-z0-9 ,'"-]{1,200}$/;
const HISTORY_LIMIT = 50;

const clone = (value) => JSON.parse(JSON.stringify(value));
const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);

// Rewrites a theme stylesheet to apply inside `scope` only: :root becomes the scope,
// [data-bs-theme=…] applies to the scope in that mode, other selectors are nested in it.
export function scopeCss(css, scope) {
  return css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/([^{}]+)\{/g, (_, selectors) => {
    const scoped = selectors.split(',').map((selector) => selector.trim()).filter(Boolean).map((selector) => {
      if (selector === ':root') {
        return scope;
      }
      return selector.startsWith('[data-bs-theme') ? `${scope}${selector}` : `${scope} ${selector}`;
    });
    return `${selectors.match(/^\s*/)[0]}${scoped.join(',\n')} {`;
  }).trim();
}

// A stylesheet the builder can rewrite: a constructed stylesheet (no inline <style>, so a
// strict CSP needs no change), or a <style> with the page's CSP nonce where unsupported.
function previewSheet(id) {
  try {
    const sheet = new CSSStyleSheet();
    document.adoptedStyleSheets = [...document.adoptedStyleSheets, sheet];
    return (css) => sheet.replaceSync(css);
  } catch {
    const style = document.createElement('style');
    const nonce = document.querySelector('meta[name="csp-nonce"]')?.getAttribute('content');
    if (nonce) {
      style.nonce = nonce;
    }
    style.dataset.celerikThemePreview = id;
    document.head.append(style);
    return (css) => {
      style.textContent = css;
    };
  }
}

// Enables the stylesheet of the chosen base look and disables the others.
function baseSwitcher(assetsUrl) {
  const stylesheets = () => [...document.querySelectorAll('link[rel="stylesheet"]')];
  const fileOf = (link) => new URL(link.href, window.location.href).pathname.split('/').pop();
  const celerikCss = stylesheets().find((link) => fileOf(link) === 'celerik.css');
  const baseUrl = assetsUrl ?? celerikCss?.href.replace(/celerik\.css([?#].*)?$/, '').replace(/\/$/, '');

  return (base) => {
    for (const name of ['material', 'tailwind']) {
      let link = stylesheets().find((candidate) => fileOf(candidate) === `celerik-${name}.css`);
      if (!link && base === name && baseUrl) {
        link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = `${baseUrl}/celerik-${name}.css`;
        (celerikCss ?? document.head.lastChild).after(link);
      }
      if (link) {
        link.disabled = base !== name;
      }
    }
  };
}

export function initThemeBuilder(element, options) {
  const labels = options.labels ?? {};
  const pairs = options.pairs ?? [];
  const presets = options.presets ?? {};
  const initial = validateTheme(options.theme).theme;
  if (!initial) {
    throw new TypeError(`Celerik: invalid theme for #${element.id}.`);
  }

  let theme = clone(initial);
  let committed = clone(theme);
  const history = [];
  const listeners = new Set();

  const find = (selector) => element.querySelector(selector);
  const canvas = find('.celerik-theme-builder__canvas');
  const field = (name) => find(`[name="theme[${name}]"]`);
  const nameInput = field('name');
  const baseSelect = field('base');
  const radiusInput = field('radius');
  const radiusOutput = find(`#${element.id}-radius-value`);
  const fontInput = field('fontFamily');
  const shadowSelect = field('shadow');
  const undoButton = find('[data-celerik-action="undo"]');
  const summary = find('.celerik-theme-builder__summary');
  const list = find('.celerik-theme-builder__report ul');

  const applyPreview = previewSheet(element.id);
  const switchBase = baseSwitcher(options.assetsUrl);
  const scope = `#${element.id} .celerik-theme-builder__canvas`;

  function markInvalid(input, message) {
    input.setAttribute('aria-invalid', 'true');
    let error = document.getElementById(`${input.id}-error`);
    if (!error) {
      error = document.createElement('div');
      error.id = `${input.id}-error`;
      error.className = 'invalid-feedback d-block';
      (input.closest('.input-group') ?? input).after(error);
      input.setAttribute('aria-describedby', `${input.getAttribute('aria-describedby') ?? ''} ${error.id}`.trim());
    }
    error.textContent = message;
  }

  function clearInvalid(input) {
    input.removeAttribute('aria-invalid');
    const error = document.getElementById(`${input.id}-error`);
    if (error) {
      error.remove();
      const describedBy = (input.getAttribute('aria-describedby') ?? '').split(' ').filter((id) => id && id !== error.id);
      if (describedBy.length) {
        input.setAttribute('aria-describedby', describedBy.join(' '));
      } else {
        input.removeAttribute('aria-describedby');
      }
    }
  }

  function renderReport() {
    const results = contrastReport(theme, pairs);
    list.replaceChildren(...results.map((result) => {
      const item = document.createElement('li');
      item.className = `celerik-theme-builder__pair ${result.passes ? 'is-pass' : 'is-fail'}`;
      item.dataset.celerikPair = `${result.mode}.${result.foreground}.${result.background}`;
      const badge = item.appendChild(document.createElement('span'));
      badge.className = `badge ${result.passes ? 'text-bg-success' : 'text-bg-danger'}`;
      badge.textContent = result.passes ? labels.pass : labels.fail;
      const mode = result.mode === 'light' ? labels.modeLight : labels.modeDark;
      item.append(` ${mode} · ${result.label}: ${formatRatio(result.ratio)}:1 (${labels.needs} ${result.minimum}:1)`);
      return item;
    }));

    const failures = results.filter((result) => !result.passes).length;
    const text = failures === 0 ? labels.allPass
      : failures === 1 ? labels.oneFails : labels.manyFail.replace('{count}', String(failures));
    // Only a changed summary is announced (role="status"), not every edit.
    if (summary.textContent !== text) {
      summary.textContent = text;
    }
    summary.classList.toggle('text-success-emphasis', failures === 0);
    summary.classList.toggle('text-danger-emphasis', failures > 0);
  }

  function render() {
    applyPreview(scopeCss(themeToCss(theme), scope));
    switchBase(theme.base);
    renderReport();
    for (const listener of listeners) {
      listener(clone(theme));
    }
  }

  // Puts the state back into every control.
  function sync() {
    nameInput.value = theme.name;
    baseSelect.value = theme.base;
    radiusInput.value = String(theme.radius ?? 0.375);
    radiusOutput.textContent = String(theme.radius ?? 0.375);
    fontInput.value = theme.fontFamily ?? '';
    shadowSelect.value = theme.shadow ?? '';
    for (const mode of MODES) {
      for (const token of TOKENS) {
        for (const input of element.querySelectorAll(`[data-celerik-token="${mode}.${token}"]`)) {
          input.value = theme[mode][token];
        }
      }
    }
    for (const input of element.querySelectorAll('[aria-invalid="true"]')) {
      clearInvalid(input);
    }
    undoButton.disabled = history.length === 0;
  }

  // One undo step per finished change ("change" events), not per keystroke.
  function commit() {
    if (!same(theme, committed)) {
      history.push(committed);
      if (history.length > HISTORY_LIMIT) {
        history.shift();
      }
      committed = clone(theme);
    }
    undoButton.disabled = history.length === 0;
  }

  function setTheme(next) {
    const { theme: valid, error } = validateTheme(next);
    if (!valid) {
      throw new TypeError(error);
    }
    if (!same(valid, theme)) {
      history.push(clone(theme));
      if (history.length > HISTORY_LIMIT) {
        history.shift();
      }
    }
    theme = valid;
    committed = clone(theme);
    sync();
    render();
  }

  // Colors: the hex field and the picker edit the same token.
  for (const input of element.querySelectorAll('[data-celerik-token]')) {
    const [mode, token] = input.dataset.celerikToken.split('.');
    input.addEventListener('input', () => {
      const value = input.value.trim();
      if (!isColor(value)) {
        return; // a half-typed color: checked on "change"
      }
      theme[mode][token] = value.toLowerCase();
      for (const other of element.querySelectorAll(`[data-celerik-token="${mode}.${token}"]`)) {
        if (other !== input) {
          other.value = theme[mode][token];
        }
      }
      if (input.type === 'text') {
        clearInvalid(input);
      }
      render();
    });
    input.addEventListener('change', () => {
      if (input.type === 'text' && !isColor(input.value.trim())) {
        markInvalid(input, labels.invalidColor);
        return;
      }
      commit();
    });
  }

  nameInput.addEventListener('input', () => {
    if (NAME.test(nameInput.value)) {
      theme.name = nameInput.value;
      clearInvalid(nameInput);
    }
  });
  nameInput.addEventListener('change', () => {
    if (!NAME.test(nameInput.value)) {
      markInvalid(nameInput, nameInput.validationMessage || labels.name);
      return;
    }
    render();
    commit();
  });

  baseSelect.addEventListener('change', () => {
    theme.base = baseSelect.value;
    render();
    commit();
  });

  radiusInput.addEventListener('input', () => {
    theme.radius = Number(radiusInput.value);
    radiusOutput.textContent = radiusInput.value;
    render();
  });
  radiusInput.addEventListener('change', commit);

  fontInput.addEventListener('input', () => {
    const value = fontInput.value.trim();
    if (value === '') {
      delete theme.fontFamily;
    } else if (FONT.test(value)) {
      theme.fontFamily = value;
    } else {
      return;
    }
    clearInvalid(fontInput);
    render();
  });
  fontInput.addEventListener('change', () => {
    const value = fontInput.value.trim();
    if (value !== '' && !FONT.test(value)) {
      markInvalid(fontInput, labels.fontHelp);
      return;
    }
    commit();
  });

  shadowSelect.addEventListener('change', () => {
    if (shadowSelect.value === '') {
      delete theme.shadow;
    } else {
      theme.shadow = shadowSelect.value;
    }
    render();
    commit();
  });

  // Controls that need JavaScript start hidden in the markup.
  const presetSelect = find(`#${element.id}-preset`);
  find('.celerik-theme-builder__preset').hidden = false;
  find('[data-celerik-action="preset"]').addEventListener('click', () => {
    if (presets[presetSelect.value]) {
      setTheme(clone(presets[presetSelect.value]));
    }
  });

  find('.celerik-theme-builder__history').hidden = false;
  undoButton.addEventListener('click', () => {
    if (history.length === 0) {
      return;
    }
    theme = history.pop();
    committed = clone(theme);
    sync();
    render();
    if (history.length === 0) {
      nameInput.focus(); // the disabled Undo button would drop focus
    }
  });
  find('[data-celerik-action="reset"]').addEventListener('click', () => setTheme(clone(initial)));

  find('.celerik-theme-builder__modes').hidden = false;
  for (const radio of element.querySelectorAll(`input[name="${element.id}-preview-mode"]`)) {
    radio.addEventListener('change', () => {
      if (radio.checked) {
        canvas.dataset.bsTheme = radio.value;
      }
    });
  }

  sync();
  render();

  const api = {
    getTheme: () => clone(theme),
    setTheme,
    toCss: () => themeToCss(theme),
    onChange(listener) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
  };
  initExport(element, options, api);

  return api;
}

// A file name from the theme name: "Acme Café 2" -> "acme-cafe-2".
export function fileName(name, extension) {
  const slug = name.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
  return `${slug || 'theme'}.${extension}`;
}

export function themeJson(theme) {
  return `${JSON.stringify(theme, null, 4)}\n`;
}

// PHP that rebuilds the theme: paste it into a config file or a seeder.
export function phpSnippet(theme) {
  return `use Celerik\\Ui\\Theme\\Theme;\n\n$theme = Theme::fromJson(<<<'JSON'\n${themeJson(theme)}JSON);\n`;
}

function download(name, type, text) {
  const url = URL.createObjectURL(new Blob([text], { type }));
  const link = document.createElement('a');
  link.href = url;
  link.download = name;
  link.hidden = true;
  document.body.append(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

// Download, copy, import and save.
function initExport(element, options, api) {
  const labels = options.labels ?? {};
  const message = element.querySelector('.celerik-theme-builder__message');
  const say = (text, isError = false) => {
    message.textContent = text;
    message.classList.toggle('text-danger-emphasis', isError);
  };
  const action = (name) => element.querySelector(`[data-celerik-action="${name}"]`);

  element.querySelector('.celerik-theme-builder__export').hidden = false;

  action('download-css').addEventListener('click', () => {
    download(fileName(api.getTheme().name, 'css'), 'text/css', api.toCss());
  });
  action('download-json').addEventListener('click', () => {
    download(fileName(api.getTheme().name, 'json'), 'application/json', themeJson(api.getTheme()));
  });
  action('copy-php').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(phpSnippet(api.getTheme()));
      say(labels.copied);
    } catch {
      say(labels.copyFailed, true);
    }
  });

  const importInput = action('import');
  importInput.addEventListener('change', async () => {
    const [file] = importInput.files;
    importInput.value = ''; // importing the same file again fires "change"
    if (!file) {
      return;
    }
    let data;
    try {
      data = JSON.parse(await file.text());
    } catch (error) {
      say(labels.importError.replace('{error}', error.message), true);
      return;
    }
    const { theme, error } = validateTheme(data);
    if (!theme) {
      say(labels.importError.replace('{error}', error), true);
      return;
    }
    api.setTheme(theme);
    say(labels.imported);
  });

  // Save sends JSON; without JavaScript the form posts its fields (ThemeBuilder::fromRequest()).
  const form = element.querySelector('form');
  if (options.saveUrl) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };
      const token = options.csrf ? form.querySelector(`input[name="${options.csrf.field}"]`)?.value : null;
      if (token) {
        headers['X-CSRF-TOKEN'] = token;
      }
      try {
        const response = await fetch(options.saveUrl, {
          method: 'POST',
          headers,
          credentials: 'same-origin',
          body: JSON.stringify({ theme: api.getTheme() }),
        });
        say(response.ok ? labels.saved : labels.saveFailed, !response.ok);
      } catch {
        say(labels.saveFailed, true);
      }
    });
  }
}
