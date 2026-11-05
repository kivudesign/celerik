import { expect, test } from '@playwright/test';
import { openComponents } from './helpers.mjs';

// Series must be distinguishable without color (WCAG 1.4.1): shapes and dashes, in the legend too.
test('line chart series have distinct point shapes and dashes', async ({ page }) => {
  await openComponents(page);
  const chart = await page.evaluate(() => {
    const instance = document.querySelector('#revenue').celerik;
    return {
      styles: instance.data.datasets.map((dataset) => dataset.pointStyle),
      dashes: instance.data.datasets.map((dataset) => JSON.stringify(dataset.borderDash)),
      legend: instance.options.plugins.legend.labels.usePointStyle,
    };
  });
  expect(new Set(chart.styles).size).toBe(3);
  expect(new Set(chart.dashes).size).toBe(3);
  expect(chart.legend).toBe(true);
});
