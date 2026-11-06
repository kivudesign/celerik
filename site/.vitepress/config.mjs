import { defineConfig } from 'vitepress';

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
      { text: 'GitHub', link: 'https://github.com/kivudesign/celerik' },
    ],

    sidebar: {
      '/components/': [
        {
          text: 'Components',
          items: [
            { text: 'Overview', link: '/components/' },
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
