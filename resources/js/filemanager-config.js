import { deepMerge } from './core.js';

export function elfinderAssets(options) {
  const base = (options.assetsUrl ?? '/elfinder').replace(/\/+$/, '');
  return {
    base,
    styles: [`${base}/css/elfinder.min.css`, `${base}/css/theme.css`],
    script: `${base}/js/elfinder.min.js`,
  };
}

export function buildElfinderConfig(options) {
  const { base } = elfinderAssets(options);
  const headers = options.csrf?.header && options.csrf?.token ? { [options.csrf.header]: options.csrf.token } : {};

  return deepMerge({
    url: options.url,
    baseUrl: `${base}/`,
    soundPath: `${base}/sounds/`,
    cssAutoLoad: false,
    lang: options.lang ?? 'en',
    height: options.height ?? 500,
    // POST for every command, with the framework's CSRF header; elFinder adds its own.
    requestType: 'post',
    customHeaders: headers,
    // Do not rewrite the page URL or remember folders across sessions.
    useBrowserHistory: false,
    rememberLastDir: false,
  }, options.options);
}

// Adds a <link>/<script> once, with the page's CSP nonce if it has one.
export function loadAsset(tag, url, documentRef = document) {
  const attribute = tag === 'link' ? 'href' : 'src';
  for (const existing of documentRef.querySelectorAll(tag)) {
    if (existing.getAttribute(attribute) === url) {
      return existing.celerikLoaded ?? Promise.resolve();
    }
  }

  const element = documentRef.createElement(tag);
  const nonce = documentRef.querySelector('meta[name="csp-nonce"]')?.getAttribute('content');
  if (nonce) {
    element.nonce = nonce;
  }
  if (tag === 'link') {
    element.rel = 'stylesheet';
  }
  element.setAttribute(attribute, url);

  element.celerikLoaded = new Promise((resolve, reject) => {
    element.addEventListener('load', () => resolve(), { once: true });
    element.addEventListener('error', () => reject(new Error(`Celerik: could not load ${url}`)), { once: true });
  });
  documentRef.head.append(element);

  return element.celerikLoaded;
}
