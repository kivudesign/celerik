import { readFileSync } from 'node:fs';
import { join } from 'node:path';

export function loadFixture(name) {
  document.body.innerHTML = `<main>${readFileSync(join(__dirname, 'fixtures', `${name}.html`), 'utf8')}</main>`;
  return document.body.querySelector('[data-celerik]');
}
