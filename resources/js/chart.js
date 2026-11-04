import Chart from 'chart.js/auto';
import { buildChartConfig, renderDataTable } from './chart-config.js';

export function initChart(element, options) {
  const canvas = element.querySelector('canvas');
  if (options.height) {
    canvas.parentElement.style.height = `${options.height}px`;
  }

  const chart = new Chart(canvas, buildChartConfig(options));

  if (options.url) {
    fetch(options.url, { headers: { Accept: 'application/json' } })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      })
      .then((payload) => {
        chart.data = payload.data;
        chart.update();
        renderDataTable(element, canvas.getAttribute('aria-label') ?? '', payload.data);
      })
      .catch((error) => console.error(`Celerik: failed to load chart #${element.id}`, error));
  }

  return chart;
}
