import { buildChartConfig, renderDataTable } from '../../resources/js/chart-config.js';
import { readOptions } from '../../resources/js/core.js';
import { loadFixture } from './helpers.js';

describe('chart config', () => {
  test('builds a Chart.js config from the PHP options', () => {
    const figure = loadFixture('chart');
    const config = buildChartConfig(readOptions(figure));

    expect(config.type).toBe('bar');
    expect(config.data.labels).toEqual(['Jan', 'Feb', 'Mar']);
    expect(config.data.datasets[0]).toEqual({ label: 'Revenue', data: [1200, 1900, 1500] });
    expect(config.options.plugins.title).toEqual({ display: true, text: 'Monthly revenue' });
    expect(config.options.maintainAspectRatio).toBe(true);
  });

  test('a fixed height disables the aspect ratio and raw options are merged', () => {
    const config = buildChartConfig({ height: 300, options: { plugins: { legend: { display: false } } } });

    expect(config.options.maintainAspectRatio).toBe(false);
    expect(config.options.plugins.legend.display).toBe(false);
    expect(config.options.plugins.title.display).toBe(false);
  });

  test('reduced motion turns animation off, unless PHP options set it', () => {
    expect(buildChartConfig({}).options.animation).toBeUndefined();
    expect(buildChartConfig({}, { reducedMotion: true }).options.animation).toBe(false);

    const config = buildChartConfig({ options: { animation: { duration: 200 } } }, { reducedMotion: true });
    expect(config.options.animation).toEqual({ duration: 200 });
  });

  test('renders an accessible data table for remote data', () => {
    document.body.innerHTML = '<figure id="f"></figure>';
    const table = renderDataTable(document.getElementById('f'), 'Signups', {
      labels: ['Mon', 'Tue'],
      datasets: [{ label: 'Users', data: [3, 5] }],
    });

    expect(table.caption.textContent).toBe('Signups');
    expect(table.querySelector('tbody tr:nth-child(2)').textContent).toBe('Tue5');
    expect(table.classList.contains('visually-hidden')).toBe(true);
  });
});
