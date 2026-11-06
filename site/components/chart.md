# Chart

Charts on [Chart.js 4](https://www.chartjs.org/), with a data table as their text alternative.

<Demo name="chart" :min-height="360" />

<<< @/demos/chart.php

## Data

```php
// Embedded
Chart::make('sales')->type('bar')->title('Sales')->labels(['Jan', 'Feb'])->dataset('2026', [120, 190]);

// From rows (an Eloquent or Doctrine result as arrays): one dataset per series
Chart::make('sales')->type('line')->title('Sales')->fromRows($rows, 'month', ['revenue' => 'Revenue', 'costs' => 'Costs']);

// From an endpoint answering {"data": {"labels": [...], "datasets": [...]}}
Chart::make('signups')->type('bar')->title('Sign-ups')->url('/stats/signups');
```

## Options

| Method | Use |
|---|---|
| `type($type)` | `line` (default), `bar`, `pie`, `doughnut`, `radar`, `polarArea`, `scatter` or `bubble`. |
| `title($title, $visible = true)` | The chart's accessible name; shown above the chart unless `$visible` is false. Always set one. |
| `labels([...])`, `dataset($label, $data, $options = [])` | Embedded data; `$options` are Chart.js dataset options (colors, `fill`…). |
| `fromRows($rows, $labelField, $series)` | Labels and datasets from rows. |
| `url($url)` | Loads the data from an endpoint. |
| `height($pixels)` | A fixed height; without it the chart keeps Chart.js' aspect ratio. |
| `patterns($enabled = true)` | A point shape and line dash per series; on by default for line, radar and scatter. |
| `options([...])` | Raw Chart.js options. |

Time scales are not included: they need a date adapter Celerik does not ship. Use category labels instead.

## Accessibility

- The canvas is an image named by the title, and a visually hidden data table holds the same numbers (also built for data loaded from `url()`).
- Each series has its own point shape and line dash, in the legend too, so series can be told apart without color (WCAG 1.4.1).
- No animation when the user asks for reduced motion.
