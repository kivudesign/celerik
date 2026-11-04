import js from '@eslint/js';
import globals from 'globals';

export default [
  { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'coverage/**'] },
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
    files: ['*.config.{js,mjs,cjs}', 'babel.config.cjs', 'jest.config.cjs'],
    languageOptions: { globals: globals.node },
  },
];
