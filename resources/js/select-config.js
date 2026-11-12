// Tom Select settings for a searchable select, from its data-celerik-select options
// (Field::searchable(), Field::remote()). Pure, so it can be unit-tested.

// The URL of a remote search: the endpoint, with ?search=<query>.
export function optionsUrl(url, query, base = window.location.href) {
  const target = new URL(url, base);
  target.searchParams.set('search', query);
  return target.toString();
}

// Fetches the choices of a remote select: {data: [{value, label}]} (Field::respond()).
export async function fetchOptions(url, query, fetchImpl = (...args) => fetch(...args)) {
  const response = await fetchImpl(optionsUrl(url, query), {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });
  if (!response.ok) {
    throw new Error(`Celerik: ${url} answered ${response.status}.`);
  }
  const { data } = await response.json();
  return Array.isArray(data)
    ? data.map(({ value, label }) => ({ value: String(value), label: String(label) }))
    : [];
}

export function buildSelectConfig(select, options = {}, fetchImpl = undefined) {
  const config = {
    plugins: select.multiple ? ['remove_button'] : [],
    maxOptions: null,
    // The empty option of a single select is its placeholder, not a choice.
    allowEmptyOption: false,
    // Filter local choices on every key: with Tom Select's 300 ms delay, Enter typed
    // quickly picks the option highlighted before the filter.
    refreshThrottle: options.url ? 300 : 0,
  };
  if (options.placeholder) {
    config.placeholder = options.placeholder;
  }

  if (options.url) {
    Object.assign(config, {
      valueField: 'value',
      labelField: 'label',
      searchField: ['label'],
      // The first choices as soon as the select opens, before anything is typed.
      preload: 'focus',
      // Tom Select also filters the loaded choices by label, like the endpoint
      // (Field::respond() searches the label only): earlier results that do not match
      // the new search are hidden.
      load(query, callback) {
        fetchOptions(options.url, query, fetchImpl).then(callback, (error) => {
          callback();
          console.error(error);
        });
      },
    });
  }

  return config;
}
