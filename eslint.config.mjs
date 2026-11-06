import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'coverage/**', 'test-results/**', 'playwright-report/**', 'site/.vitepress/dist/**', 'site/.vitepress/cache/**', 'site/public/demo-assets/**', 'site/public/demos/**'] },
  js.configs.recommended,
  {
    files: ['resources/js/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: globals.browser,
    },
  },
  {
    files: ['tests/js/**/*.js'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: { ...globals.browser, ...globals.node, ...globals.jest },
    },
  },
  {
    // Loaded as a classic script in the <head> of each demo page.
    files: ['site/demos/*.js'],
    languageOptions: { ecmaVersion: 2022, sourceType: 'script', globals: globals.browser },
  },
  {
    files: ['site/**/*.mjs'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: { ...globals.browser, ...globals.node },
    },
  },
  {
    files: ['tests/browser/**/*.mjs'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: { ...globals.browser, ...globals.node },
    },
  },
  {
    files: ['*.config.{js,mjs,cjs}', 'babel.config.cjs', 'jest.config.cjs'],
    languageOptions: { globals: globals.node },
  },
];
