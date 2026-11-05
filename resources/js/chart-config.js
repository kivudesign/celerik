import { deepMerge } from './core.js';

// reducedMotion: the user asked for less motion. Animation options from PHP still win.
export function buildChartConfig(options, { reducedMotion = false } = {}) {
  return {
    type: options.type ?? 'line',
    data: options.data ?? { labels: [], datasets: [] },
    options: deepMerge({
      responsive: true,
      maintainAspectRatio: !options.height,
      ...(reducedMotion ? { animation: false } : {}),
      plugins: {
        title: { display: Boolean(options.title), text: options.title ?? '' },
      },
    }, options.options),
  };
}

// Text alternative for charts whose data arrives over HTTP (PHP renders it for embedded data).
export function renderDataTable(figure, caption, data) {
  figure.querySelector('table.celerik-chart__data')?.remove();
  const table = document.createElement('table');
  table.className = 'visually-hidden celerik-chart__data';
  table.createCaption().textContent = caption;

  const head = table.createTHead().insertRow();
  head.appendChild(document.createElement('th')).scope = 'col';
  for (const dataset of data.datasets ?? []) {
    const th = head.appendChild(document.createElement('th'));
    th.scope = 'col';
    th.textContent = dataset.label ?? '';
  }

  const body = table.createTBody();
  (data.labels ?? []).forEach((label, index) => {
    const row = body.insertRow();
    const th = row.appendChild(document.createElement('th'));
    th.scope = 'row';
    th.textContent = String(label);
    for (const dataset of data.datasets ?? []) {
      row.insertCell().textContent = dataset.data?.[index] ?? '';
    }
  });

  figure.appendChild(table);
  return table;
}
