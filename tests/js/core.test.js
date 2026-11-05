import { deepMerge, init, readOptions, register } from '../../resources/js/core.js';

describe('core', () => {
  test('initialises each element once and passes parsed options', async () => {
    const calls = [];
    register('probe', (element, options) => {
      calls.push(options);
      return { id: element.id };
    });
    document.body.innerHTML = `<div id="a" data-celerik="probe" data-celerik-options='{"size":3}'></div>`;

    const ready = jest.fn();
    document.addEventListener('celerik:ready', ready);
    await init();
    await init();
    document.removeEventListener('celerik:ready', ready);

    expect(calls).toEqual([{ size: 3 }]);
    expect(document.getElementById('a').celerik).toEqual({ id: 'a' });
    expect(ready).toHaveBeenCalledTimes(1);
  });

  test('supports async initializers and skips elements that are still loading', async () => {
    let finish;
    const initializer = jest.fn(() => new Promise((resolve) => { finish = resolve; }));
    register('lazy', initializer);
    document.body.innerHTML = '<div id="l" data-celerik="lazy"></div>';
    const element = document.getElementById('l');
    const ready = jest.fn();
    element.addEventListener('celerik:ready', ready);

    const first = init();
    const second = init();
    expect(initializer).toHaveBeenCalledTimes(1);
    expect(ready).not.toHaveBeenCalled();

    finish({ loaded: true });
    await Promise.all([first, second]);

    expect(element.celerik).toEqual({ loaded: true });
    expect(ready).toHaveBeenCalledWith(expect.objectContaining({ detail: { instance: { loaded: true } } }));
  });

  test('logs a failed initializer and allows a retry', async () => {
    const error = jest.spyOn(console, 'error').mockImplementation(() => {});
    let attempts = 0;
    register('flaky', () => {
      attempts += 1;
      if (attempts === 1) {
        throw new Error('chunk failed to load');
      }
      return 'ok';
    });
    document.body.innerHTML = '<div id="f" data-celerik="flaky"></div>';

    await init();
    expect(error).toHaveBeenCalledWith(expect.stringContaining('"flaky" #f'), expect.any(Error));
    expect(document.getElementById('f').celerik).toBeUndefined();

    await init();
    expect(document.getElementById('f').celerik).toBe('ok');
    error.mockRestore();
  });

  test('warns about unknown components instead of throwing', () => {
    const warn = jest.spyOn(console, 'warn').mockImplementation(() => {});
    document.body.innerHTML = '<div data-celerik="unknown"></div>';

    expect(() => init()).not.toThrow();
    expect(warn).toHaveBeenCalledWith(expect.stringContaining('"unknown"'));
    warn.mockRestore();
  });

  test('tolerates invalid JSON options', () => {
    const error = jest.spyOn(console, 'error').mockImplementation(() => {});
    document.body.innerHTML = `<div id="x" data-celerik-options="{oops"></div>`;

    expect(readOptions(document.getElementById('x'))).toEqual({});
    error.mockRestore();
  });

  test('deepMerge merges objects and replaces arrays', () => {
    expect(deepMerge({ a: { b: 1, c: 2 }, list: [1, 2] }, { a: { c: 3 }, list: [9] }))
      .toEqual({ a: { b: 1, c: 3 }, list: [9] });
  });
});
