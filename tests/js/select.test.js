import { buildSelectConfig, fetchOptions, optionsUrl } from '../../resources/js/select-config.js';

describe('searchable select config', () => {
  test('local choices: Tom Select defaults, removable tags on multiple selects', () => {
    expect(buildSelectConfig({ multiple: false }, {})).toEqual({
      plugins: [], maxOptions: null, allowEmptyOption: false, refreshThrottle: 0,
    });
    expect(buildSelectConfig({ multiple: true }, { placeholder: 'Pick' })).toMatchObject({
      plugins: ['remove_button'],
      placeholder: 'Pick',
    });
  });

  test('remote choices: value and label fields, preloaded on focus', () => {
    const config = buildSelectConfig({ multiple: false }, { url: '/people' });
    expect(config).toMatchObject({ valueField: 'value', labelField: 'label', searchField: ['label'], preload: 'focus' });
  });

  test('the remote URL keeps its own query string', () => {
    expect(optionsUrl('/people?team=2', 'a&b', 'https://example.com/form'))
      .toBe('https://example.com/people?team=2&search=a%26b');
  });

  test('fetchOptions maps {data} to strings, and fails on HTTP errors', async () => {
    const ok = jest.fn(async () => ({ ok: true, json: async () => ({ data: [{ value: 1, label: 'Ada', email: 'x' }] }) }));
    await expect(fetchOptions('/people', 'a', ok)).resolves.toEqual([{ value: '1', label: 'Ada' }]);

    const failing = jest.fn(async () => ({ ok: false, status: 403 }));
    await expect(fetchOptions('/people', 'a', failing)).rejects.toThrow('403');
  });

  test('a failed load still ends the loading state', async () => {
    const error = jest.spyOn(console, 'error').mockImplementation(() => {});
    const config = buildSelectConfig({ multiple: false }, { url: '/people' }, async () => ({ ok: false, status: 500 }));
    await new Promise((resolve) => config.load('a', resolve));
    expect(error).toHaveBeenCalled();
    error.mockRestore();
  });
});
