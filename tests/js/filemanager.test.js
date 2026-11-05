import { buildElfinderConfig, elfinderAssets, loadAsset } from '../../resources/js/filemanager-config.js';

describe('file manager config', () => {
  test('maps the PHP options to elFinder with the framework CSRF header', () => {
    const config = buildElfinderConfig({
      url: '/files/connector',
      assetsUrl: '/vendor/elfinder/',
      height: 600,
      lang: 'fr',
      csrf: { header: 'X-CSRF-TOKEN', token: 'tok' },
    });

    expect(config).toMatchObject({
      url: '/files/connector',
      baseUrl: '/vendor/elfinder/',
      soundPath: '/vendor/elfinder/sounds/',
      cssAutoLoad: false,
      lang: 'fr',
      height: 600,
      requestType: 'post',
      customHeaders: { 'X-CSRF-TOKEN': 'tok' },
      useBrowserHistory: false,
      rememberLastDir: false,
    });
    expect(buildElfinderConfig({ url: '/c' }).customHeaders).toEqual({});
    expect(buildElfinderConfig({ url: '/c', options: { height: 300 } }).height).toBe(300);
  });

  test('locates elFinder assets under the configured base', () => {
    expect(elfinderAssets({})).toEqual({
      base: '/elfinder',
      styles: ['/elfinder/css/elfinder.min.css', '/elfinder/css/theme.css'],
      script: '/elfinder/js/elfinder.min.js',
    });
  });
});

describe('loadAsset', () => {
  beforeEach(() => {
    document.head.innerHTML = '<meta name="csp-nonce" content="abc123">';
  });

  test('adds each asset once, with the CSP nonce, and resolves on load', async () => {
    const first = loadAsset('script', '/elfinder/js/elfinder.min.js');
    const second = loadAsset('script', '/elfinder/js/elfinder.min.js');

    const scripts = document.head.querySelectorAll('script');
    expect(scripts).toHaveLength(1);
    expect(scripts[0].nonce).toBe('abc123');
    expect(second).toBe(first);

    scripts[0].dispatchEvent(new Event('load'));
    await expect(first).resolves.toBeUndefined();
  });

  test('stylesheets are links and failures reject with the URL', async () => {
    const loading = loadAsset('link', '/elfinder/css/theme.css');
    const link = document.head.querySelector('link');

    expect(link.rel).toBe('stylesheet');
    link.dispatchEvent(new Event('error'));
    await expect(loading).rejects.toThrow('/elfinder/css/theme.css');
  });
});
