import {
  buildNotyfConfig,
  durationFor,
  escapeHtml,
  pausableTimer,
  toastFor,
  unescapeHtml,
} from '../../resources/js/notification-config.js';
import axe from 'axe-core';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('notification config', () => {
  test('escaping round-trips and neutralizes markup', () => {
    const text = `Tom & Jerry <img src=x onerror="alert('x')">`;

    expect(escapeHtml(text)).toBe('Tom &amp; Jerry &lt;img src=x onerror=&quot;alert(&#39;x&#39;)&quot;&gt;');
    expect(unescapeHtml(escapeHtml(text))).toBe(text);
    expect(toastFor('error', '<b>x</b>')).toEqual({ type: 'error', message: '&lt;b&gt;x&lt;/b&gt;' });
    expect(() => toastFor('fatal', 'x')).toThrow(TypeError);
  });

  test('errors stay until dismissed and colors come from Bootstrap', () => {
    const config = buildNotyfConfig({ duration: 3000, errorDuration: 0, dismissible: false, position: { x: 'center', y: 'bottom' } });
    const types = Object.fromEntries(config.types.map((type) => [type.type, type]));

    expect(config.position).toEqual({ x: 'center', y: 'bottom' });
    expect(types.success).toMatchObject({ duration: 3000, dismissible: false, background: 'var(--bs-success)' });
    expect(types.error).toMatchObject({ duration: 0, dismissible: true, background: 'var(--bs-danger)' });
    expect(types.warning.className).toBe('celerik-toast celerik-toast-warning');
  });
});

describe('pausableTimer', () => {
  test('pausing keeps the remaining time', () => {
    let now = 0;
    const timers = [];
    const clock = {
      set: (callback, ms) => timers.push({ callback, at: now + ms }) - 1,
      clear: (id) => { timers[id] = null; },
      now: () => now,
    };
    const expired = jest.fn();
    const timer = pausableTimer(1000, expired, clock);

    now = 400;
    timer.pause();
    timer.pause();
    now = 5000;
    timer.resume();
    expect(timers.filter(Boolean)).toEqual([{ callback: expired, at: 5600 }]);
  });

  test('durationFor uses the type, then the default', () => {
    const config = buildNotyfConfig({ duration: 3000 });
    expect(durationFor(config, 'success')).toBe(3000);
    expect(durationFor(config, 'error')).toBe(0);
  });
});

describe('notification integration (Notyf)', () => {
  let initNotification;
  let notify;

  beforeEach(async () => {
    jest.useFakeTimers();
    jest.resetModules();
    ({ initNotification, notify } = await import('../../resources/js/notification.js'));
  });

  afterEach(() => {
    jest.useRealTimers();
    document.querySelectorAll('.notyf, .notyf-announcer').forEach((node) => node.remove());
  });

  test('shows flash messages as text and announces the original text', () => {
    const element = loadFixture('notification');
    initNotification(element, readOptions(element));

    const messages = [...document.querySelectorAll('.notyf__message')].map((node) => node.textContent);
    expect(messages).toEqual(['Profile saved.', 'Tom & Jerry <img src=x onerror="window.pwned=1">', 'Disk almost full.']);
    expect(document.querySelector('.notyf__message img')).toBeNull();
    expect(window.pwned).toBeUndefined();

    jest.advanceTimersByTime(150);
    expect(document.querySelector('.notyf-announcer').textContent).toBe('Disk almost full.');
    expect(document.querySelector('.notyf-announcer').getAttribute('aria-live')).toBe('polite');
  });

  test('errors are dismissible and not removed by the timer', () => {
    const element = loadFixture('notification');
    initNotification(element, readOptions(element));

    jest.advanceTimersByTime(60000);

    // Notyf marks expired toasts as disappearing (removal waits for animationend,
    // which jsdom never fires).
    const disappearing = (type) => document.querySelector(`.celerik-toast-${type}`).classList.contains('notyf__toast--disappear');
    expect(disappearing('success')).toBe(true);
    expect(disappearing('warning')).toBe(true);
    expect(disappearing('error')).toBe(false);
    expect(document.querySelector('.celerik-toast-error .notyf__dismiss-btn')).not.toBeNull();
  });

  test('a toast waits while hovered or focused, then closes after the time left', () => {
    notify('success', 'Saved.');
    const toast = document.querySelector('.celerik-toast-success');
    const disappearing = () => toast.classList.contains('notyf__toast--disappear');

    jest.advanceTimersByTime(3000);
    toast.dispatchEvent(new MouseEvent('mouseenter'));
    jest.advanceTimersByTime(20000);
    expect(disappearing()).toBe(false);

    toast.querySelector('.notyf__dismiss-btn').dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
    toast.dispatchEvent(new MouseEvent('mouseleave'));
    jest.advanceTimersByTime(20000);
    expect(disappearing()).toBe(false); // still focused

    toast.querySelector('.notyf__dismiss-btn').dispatchEvent(new FocusEvent('focusout', { bubbles: true }));
    jest.advanceTimersByTime(1900);
    expect(disappearing()).toBe(false);
    jest.advanceTimersByTime(200);
    expect(disappearing()).toBe(true); // 5 s in total, pauses excluded
  });

  test('rendered toasts have no WCAG 2.1 A/AA violations', async () => {
    jest.useRealTimers();
    const element = loadFixture('notification');
    initNotification(element, readOptions(element));

    const results = await axe.run(document.body, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] },
      rules: { 'color-contrast': { enabled: false } },
    });

    expect(results.violations.map((v) => `${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`)).toEqual([]);
  });

  test('Celerik.notify works without a Notification component on the page', () => {
    document.body.innerHTML = '<main></main>';

    notify('success', 'Saved <again>');

    expect(document.querySelector('.notyf__message').textContent).toBe('Saved <again>');
  });
});
