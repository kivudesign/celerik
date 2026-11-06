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
  head: [['meta', { name: 'theme-color', content: '#0d6efd' }]],

  themeConfig: {
    nav: [
      { text: 'Guide', link: '/guide/getting-started', activeMatch: '/guide/' },
      { text: 'GitHub', link: 'https://github.com/kivudesign/celerik' },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Introduction',
          items: [
            { text: 'Getting started', link: '/guide/getting-started' },
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
