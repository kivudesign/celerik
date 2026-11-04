import { deepMerge, init, readOptions, register } from '../../resources/js/core.js';

describe('core', () => {
  test('initialises each element once and passes parsed options', () => {
    const calls = [];
    register('probe', (element, options) => {
      calls.push(options);
      return { id: element.id };
    });
    document.body.innerHTML = `<div id="a" data-celerik="probe" data-celerik-options='{"size":3}'></div>`;

    const ready = jest.fn();
    document.addEventListener('celerik:ready', ready);
    init();
    init();

    expect(calls).toEqual([{ size: 3 }]);
    expect(document.getElementById('a').celerik).toEqual({ id: 'a' });
    expect(ready).toHaveBeenCalledTimes(1);
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
