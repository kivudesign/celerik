import { deepMerge } from './core.js';

// Point shapes and line dashes that tell series apart without color (WCAG 1.4.1).
export const POINT_STYLES = ['circle', 'triangle', 'rect', 'rectRot', 'crossRot', 'star', 'rectRounded'];
export const LINE_DASHES = [[], [6, 4], [2, 3], [10, 3, 2, 3], [12, 6], [4, 4, 1, 4], [1, 2]];
const PATTERN_TYPES = ['line', 'radar', 'scatter'];

// Per-dataset options from PHP (dataset(..., options: [...])) are never overwritten.
export function withPatterns(data, type) {
  return {
    ...data,
    datasets: (data.datasets ?? []).map((dataset, index) => ({
      pointStyle: POINT_STYLES[index % POINT_STYLES.length],
      pointRadius: 4,
      pointHoverRadius: 6,
      ...(type === 'scatter' ? {} : { borderDash: LINE_DASHES[index % LINE_DASHES.length] }),
      ...dataset,
    })),
  };
}

export function usesPatterns(options) {
  return options.patterns ?? PATTERN_TYPES.includes(options.type ?? 'line');
}

// reducedMotion: the user asked for less motion. Animation options from PHP still win.
export function buildChartConfig(options, { reducedMotion = false } = {}) {
  const type = options.type ?? 'line';
  const patterns = usesPatterns(options);
  const data = options.data ?? { labels: [], datasets: [] };
  return {
    type,
    data: patterns ? withPatterns(data, type) : data,
    options: deepMerge({
      responsive: true,
      maintainAspectRatio: !options.height,
      ...(reducedMotion ? { animation: false } : {}),
      plugins: {
        title: { display: Boolean(options.title), text: options.title ?? '' },
        ...(patterns ? { legend: { labels: { usePointStyle: true } } } : {}),
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
