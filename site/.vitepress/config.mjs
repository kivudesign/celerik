import { existsSync, readFileSync } from 'node:fs';
import { defineConfig } from 'vitepress';

// The API reference sidebar, written by site/tools/reference.php (npm run docs:reference).
const referenceFile = new URL('../.generated/reference.json', import.meta.url);
const reference = existsSync(referenceFile) ? JSON.parse(readFileSync(referenceFile, 'utf8')) : [];

// Guides include the repository's Markdown files (THEMING.md, ACCESSIBILITY.md…), whose
// links are relative to the repository root. Root documents are linked to their page on
// this site, other paths (theme/presets/, tests/…) to GitHub.
const REPOSITORY = 'https://github.com/kivudesign/celerik';
const ROOT_PAGES = {
  'THEMING.md': '/guides/theming',
  'ACCESSIBILITY.md': '/guides/accessibility',
  'SECURITY.md': '/guides/security',
  'TESTING.md': '/guides/testing',
  'ROADMAP.md': '/guides/roadmap',
};

function repositoryLinks(md) {
  md.core.ruler.push('celerik-repository-links', (state) => {
    for (const block of state.tokens) {
      for (const token of block.children ?? []) {
        const href = token.type === 'link_open' ? token.attrGet('href') : null;
        // Absolute URLs, anchors, site paths and ./ or ../ links are the site's own.
        if (!href || /^([a-z]+:|#|\/|\.)/i.test(href)) {
          continue;
        }
        const [path, hash] = href.split('#');
        const page = ROOT_PAGES[path];
        token.attrSet('href', page ? `${page}${hash ? `#${hash}` : ''}` : `${REPOSITORY}/blob/main/${href}`);
      }
    }
  });
}

// Documentation site: npm run docs:dev, npm run docs:build (output: site/.vitepress/dist).
// DOCS_BASE is the path the site is served from: /celerik/ on GitHub Pages.
export default defineConfig({
  title: 'Celerik UI',
  description: 'Accessible, Bootstrap-based PHP UI components for plain PHP, Laravel and Symfony.',
  lang: 'en',
  base: process.env.DOCS_BASE ?? '/celerik/',
  cleanUrls: true,
  lastUpdated: true,
  srcExclude: ['**/README.md'],
  markdown: {
    config: repositoryLinks,
    // High-contrast highlighting: every token at 4.5:1 or more on the code background.
    theme: { light: 'github-light-high-contrast', dark: 'github-dark-high-contrast' },
  },
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${process.env.DOCS_BASE ?? '/celerik/'}logo.svg` }],
    ['meta', { name: 'theme-color', content: '#0d6efd' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'Components', link: '/components/', activeMatch: '/components/' },
      { text: 'Guides', link: '/guides/theming', activeMatch: '/guides/' },
      { text: 'Reference', link: '/reference/', activeMatch: '/reference/' },
      { text: 'GitHub', link: 'https://github.com/kivudesign/celerik' },
    ],

    sidebar: {
      '/reference/': [{ text: 'API reference', link: '/reference/' }, ...reference],
      '/guides/': [
        {
          text: 'Guides',
          items: [
            { text: 'Theming', link: '/guides/theming' },
            { text: 'Security', link: '/guides/security' },
            { text: 'Accessibility', link: '/guides/accessibility' },
            { text: 'Testing', link: '/guides/testing' },
            { text: 'Testing an integration', link: '/guides/integration-testing' },
            { text: 'Roadmap', link: '/guides/roadmap' },
            { text: 'Changelog', link: '/guides/changelog' },
          ],
        },
      ],
      '/components/': [
        {
          text: 'Components',
          items: [
            { text: 'Overview', link: '/components/' },
            { text: 'Grid', link: '/components/grid' },
            { text: 'Chart', link: '/components/chart' },
            { text: 'Form', link: '/components/form' },
            { text: 'Rich text editor', link: '/components/editor' },
            { text: 'File upload', link: '/components/upload' },
            { text: 'Scheduler', link: '/components/scheduler' },
            { text: 'Modal', link: '/components/modal' },
            { text: 'Tabs', link: '/components/tabs' },
            { text: 'Notification', link: '/components/notification' },
            { text: 'File manager', link: '/components/file-manager' },
            { text: 'Theme builder', link: '/components/theme-builder' },
          ],
        },
      ],
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting started', link: '/guide/getting-started' },
            { text: 'How it works', link: '/guide/how-it-works' },
            { text: 'Data endpoints', link: '/guide/data' },
          ],
        },
        {
          text: 'Setup',
          items: [
            { text: 'Plain PHP', link: '/guide/plain-php' },
            { text: 'Laravel', link: '/guide/laravel' },
            { text: 'Symfony', link: '/guide/symfony' },
            { text: 'Content Security Policy', link: '/guide/csp' },
          ],
        },
      ],
    },

    search: { provider: 'local' },
    outline: { level: [2, 3] },
    editLink: {
      // Guides that include a repository file are edited there (THEMING.md…). The function
      // runs in the browser, so it cannot use this module's constants.
      pattern: ({ filePath }) => {
        const included = {
          'guides/theming.md': 'THEMING.md',
          'guides/accessibility.md': 'ACCESSIBILITY.md',
          'guides/security.md': 'SECURITY.md',
          'guides/testing.md': 'TESTING.md',
          'guides/roadmap.md': 'ROADMAP.md',
        }[filePath];
        return `https://github.com/kivudesign/celerik/edit/main/${included ?? `site/${filePath}`}`;
      },
      text: 'Edit this page on GitHub',
    },
    socialLinks: [{ icon: 'github', link: 'https://github.com/kivudesign/celerik' }],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Celerik UI contributors',
    },
  },
});
