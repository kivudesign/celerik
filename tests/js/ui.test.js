import * as bootstrap from 'bootstrap';
import axe from 'axe-core';
import { initModal } from '../../resources/js/modal.js';
import { initTabs, tabForHash } from '../../resources/js/tabs.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

async function violations() {
  const results = await axe.run(document.body, {
    runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
    rules: { 'color-contrast': { enabled: false } },
  });
  return results.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`);
}

beforeAll(() => { window.bootstrap = bootstrap; });
afterAll(() => { delete window.bootstrap; });

describe('modal', () => {
  test('opens on load when asked, as an accessible modal dialog', async () => {
    const element = loadFixture('modal');
    const modal = initModal(element, { open: true });
    // Bootstrap finishes showing after the fade transition (emulated with a timer).
    await new Promise((resolve) => { element.addEventListener('shown.bs.modal', resolve, { once: true }); });

    expect(modal).toBeInstanceOf(bootstrap.Modal);
    expect(element.getAttribute('role')).toBe('dialog');
    expect(element.getAttribute('aria-modal')).toBe('true');
    expect(element.hasAttribute('aria-hidden')).toBe(false);
    modal.dispose();
  });

  test('stays closed by default', () => {
    const element = loadFixture('modal');
    const modal = initModal(element, readOptions(element));

    expect(element.getAttribute('aria-hidden')).toBe('true');
    modal.dispose();
  });

  test('warns instead of failing when Bootstrap JS is missing', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    const saved = window.bootstrap;
    delete window.bootstrap;

    expect(initModal(loadFixture('modal'), {})).toBeNull();
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('bootstrap.bundle.js'));

    window.bootstrap = saved;
    warn.mockRestore();
  });

  test('has no WCAG 2.1 A/AA violations', async () => {
    loadFixture('modal');
    expect(await violations()).toEqual([]);
  });
});

describe('tabs', () => {
  afterEach(() => window.history.replaceState(null, '', '/'));

  test('only known tab targets match the hash', () => {
    const element = loadFixture('tabs');

    expect(tabForHash(element, '#account-security').id).toBe('account-security-tab');
    expect(tabForHash(element, '#nope')).toBeNull();
    expect(tabForHash(element, '"]; alert(1); //')).toBeNull();
  });

  test('opens the tab from the URL hash and keeps the hash in sync', () => {
    window.history.replaceState(null, '', '/#account-billing');
    const element = loadFixture('tabs');
    initTabs(element, readOptions(element));

    expect(element.querySelector('#account-billing-tab').getAttribute('aria-selected')).toBe('true');
    expect(element.querySelector('#account-billing').classList.contains('active')).toBe(true);
    expect(element.querySelector('#account-profile-tab').getAttribute('aria-selected')).toBe('false');

    bootstrap.Tab.getOrCreateInstance(element.querySelector('#account-security-tab')).show();
    expect(window.location.hash).toBe('#account-security');
  });

  test('arrow keys move between tabs (Bootstrap keyboard support)', () => {
    const element = loadFixture('tabs');
    initTabs(element, readOptions(element));
    const first = element.querySelector('#account-profile-tab');
    bootstrap.Tab.getOrCreateInstance(first);

    first.focus();
    first.dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowRight', bubbles: true }));

    expect(element.querySelector('#account-security-tab').getAttribute('aria-selected')).toBe('true');
  });

  test('has no WCAG 2.1 A/AA violations', async () => {
    const element = loadFixture('tabs');
    initTabs(element, readOptions(element));
    expect(await violations()).toEqual([]);
  });
});
