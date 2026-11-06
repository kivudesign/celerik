import { existsSync, readFileSync } from 'node:fs';
import { defineConfig } from 'vitepress';

// The API reference sidebar, written by site/tools/reference.php (npm run docs:reference).
const referenceFile = new URL('../.generated/reference.json', import.meta.url);
const reference = existsSync(referenceFile) ? JSON.parse(readFileSync(referenceFile, 'utf8')) : [];

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
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: `${process.env.DOCS_BASE ?? '/celerik/'}logo.svg` }],
    ['meta', { name: 'theme-color', content: '#0d6efd' }],
  ],

  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'Components', link: '/components/', activeMatch: '/components/' },
      { text: 'Reference', link: '/reference/', activeMatch: '/reference/' },
      { text: 'GitHub', link: 'https://github.com/kivudesign/celerik' },
    ],

    sidebar: {
      '/reference/': [{ text: 'API reference', link: '/reference/' }, ...reference],
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
      pattern: 'https://github.com/kivudesign/celerik/edit/main/site/:path',
      text: 'Edit this page on GitHub',
    },
    socialLinks: [{ icon: 'github', link: 'https://github.com/kivudesign/celerik' }],
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Celerik UI contributors',
    },
  },
});
