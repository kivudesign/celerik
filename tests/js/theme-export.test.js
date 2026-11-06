/**
 * @jest-environment node
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { fileName, phpSnippet, themeJson } from '../../resources/js/themebuilder.js';
import { validateTheme } from '../../resources/js/theme-css.js';

const root = join(__dirname, '../..');
const theme = {
  ...JSON.parse(readFileSync(join(root, 'tests/browser/custom-theme.json'), 'utf8')),
  name: 'Acme Café 2',
  fontFamily: "'Inter', system-ui, sans-serif",
};

test('file names come from the theme name', () => {
  expect(fileName('Acme Café 2', 'css')).toBe('acme-cafe-2.css');
  expect(fileName('  --My.Theme--  ', 'json')).toBe('my-theme.json');
  expect(fileName('Ünïcödé', 'css')).toBe('unicode.css');
  expect(fileName('東京', 'css')).toBe('theme.css');
});

test('exported JSON imports back to the same theme', () => {
  expect(validateTheme(JSON.parse(themeJson(theme)))).toEqual({ theme });
  expect(themeJson(theme).endsWith('}\n')).toBe(true);
});

test('the PHP snippet rebuilds the same theme in PHP', () => {
  const dir = mkdtempSync(join(tmpdir(), 'celerik-snippet-'));
  try {
    const file = join(dir, 'snippet.php');
    const autoload = join(root, 'vendor/autoload.php').replaceAll('\\', '/');
    writeFileSync(file, `<?php\nrequire '${autoload}';\n${phpSnippet(theme)}echo json_encode($theme->toArray());\n`);

    // The same php as "npm test" (on Windows a shell finds php.bat too); PHP=/path/to/php overrides it.
    const output = execFileSync(process.env.PHP ?? 'php', [file], { encoding: 'utf8', shell: process.platform === 'win32' });
    const php = JSON.parse(output);

    expect(php).toEqual({ ...theme, radius: 0.25 });
  } finally {
    rmSync(dir, { recursive: true, force: true });
  }
});
