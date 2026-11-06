import { readdirSync, statSync } from 'node:fs';
import { join, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const dist = fileURLToPath(new URL('../../site/.vitepress/dist/', import.meta.url));

// Every page of the built site, as a path relative to the base ("guide/getting-started").
export function sitePages() {
  const pages = [];
  const walk = (directory) => {
    for (const name of readdirSync(directory)) {
      const path = join(directory, name);
      if (statSync(path).isDirectory()) {
        if (!['assets', 'demo-assets', 'demos'].includes(name)) {
          walk(path);
        }
      } else if (name.endsWith('.html') && name !== '404.html') {
        pages.push(relative(dist, path).split(sep).join('/').replace(/(index)?\.html$/, ''));
      }
    }
  };
  walk(dist);
  return pages.sort();
}

// The demos, from site/demos/*.php.
export function demoNames() {
  return readdirSync(new URL('../../site/demos/', import.meta.url))
    .filter((name) => name.endsWith('.php') && name !== 'build.php')
    .map((name) => name.replace(/\.php$/, ''));
}

export const WCAG_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
