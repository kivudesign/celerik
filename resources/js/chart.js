import {
  ArcElement,
  BarController,
  BarElement,
  BubbleController,
  CategoryScale,
  Chart,
  Colors,
  DoughnutController,
  Filler,
  Legend,
  LinearScale,
  LineController,
  LineElement,
  LogarithmicScale,
  PieController,
  PointElement,
  PolarAreaController,
  RadarController,
  RadialLinearScale,
  ScatterController,
  SubTitle,
  Title,
  Tooltip,
} from 'chart.js';
import { buildChartConfig, renderDataTable, usesPatterns, withPatterns } from './chart-config.js';
import { prefersReducedMotion } from './core.js';

// Everything the PHP Chart types and common options need. Time scales are left out:
// they require a date adapter that Celerik does not ship.
Chart.register(
  LineController, BarController, PieController, DoughnutController,
  RadarController, PolarAreaController, ScatterController, BubbleController,
  LineElement, BarElement, ArcElement, PointElement,
  CategoryScale, LinearScale, LogarithmicScale, RadialLinearScale,
  Colors, Filler, Legend, Title, SubTitle, Tooltip,
);

export function initChart(element, options) {
  const canvas = element.querySelector('canvas');
  if (options.height) {
    canvas.parentElement.style.height = `${options.height}px`;
  }

  const chart = new Chart(canvas, buildChartConfig(options, { reducedMotion: prefersReducedMotion() }));

  if (options.url) {
    fetch(options.url, { headers: { Accept: 'application/json' } })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
      })
      .then((payload) => {
        chart.data = usesPatterns(options) ? withPatterns(payload.data, chart.config.type) : payload.data;
        chart.update();
        renderDataTable(element, canvas.getAttribute('aria-label') ?? '', payload.data);
      })
      .catch((error) => console.error(`Celerik: failed to load chart #${element.id}`, error));
  }

  return chart;
}
