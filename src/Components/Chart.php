<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Support\Arr;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;

/**
 * Chart backed by Chart.js.
 *
 * Data is either embedded (labels() + dataset(), or fromRows()) or loaded from an
 * endpoint with url(); the endpoint returns `{"data": $chart->toData()}`.
 * A visually hidden data table is rendered as the text alternative (WCAG 1.1.1).
 */
final class Chart extends Component
{
    public const TYPES = ['line', 'bar', 'pie', 'doughnut', 'radar', 'polarArea', 'scatter', 'bubble'];

    private string $type = 'line';

    /** @var list<string|int|float> */
    private array $labels = [];

    /** @var list<array<string, mixed>> */
    private array $datasets = [];

    private ?string $url = null;

    private string $title = 'Chart';

    private bool $showTitle = false;

    private ?int $height = null;

    private ?bool $patterns = null;

    public static function name(): string
    {
        return 'chart';
    }

    public function type(string $type): self
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported chart type "%s". Supported: %s.',
                $type,
                implode(', ', self::TYPES),
            ));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @param list<string|int|float> $labels
     */
    public function labels(array $labels): self
    {
        $this->labels = array_values($labels);

        return $this;
    }

    /**
     * @param list<int|float|null|array<string, int|float>> $data
     * @param array<string, mixed> $options Chart.js dataset options (backgroundColor, borderColor, tension...).
     */
    public function dataset(string $label, array $data, array $options = []): self
    {
        $this->datasets[] = ['label' => $label, 'data' => array_values($data)] + $options;

        return $this;
    }

    /**
     * Builds labels and datasets from rows, e.g. an Eloquent or Doctrine result as arrays.
     *
     * @param iterable<array<string, mixed>> $rows
     * @param array<string, string> $series field => dataset label
     */
    public function fromRows(iterable $rows, string $labelField, array $series): self
    {
        $labels = [];
        $values = array_fill_keys(array_keys($series), []);

        foreach ($rows as $row) {
            $labels[] = Arr::toText(Arr::get($row, $labelField));

            foreach (array_keys($series) as $field) {
                $value = Arr::get($row, $field);
                $values[$field][] = is_numeric($value) ? $value + 0 : null;
            }
        }

        $this->labels = $labels;
        $this->datasets = [];

        foreach ($series as $field => $label) {
            $this->dataset($label, $values[$field]);
        }

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Accessible name of the chart. Also shown above the chart when $visible is true.
     */
    public function title(string $title, bool $visible = true): self
    {
        $this->title = $title;
        $this->showTitle = $visible;

        return $this;
    }

    /**
     * Fixed height in pixels. Without it the chart keeps Chart.js' default aspect ratio.
     */
    public function height(int $height): self
    {
        if ($height < 1) {
            throw new InvalidArgumentException('Chart height must be positive.');
        }

        $this->height = $height;

        return $this;
    }

    /**
     * @return array{labels: list<string|int|float>, datasets: list<array<string, mixed>>}
     */
    /**
     * Gives each series its own point shape and line dash, shown in the legend too, so series
     * can be told apart without color (WCAG 1.4.1). On by default for line, radar and
     * scatter charts; options set per dataset are kept.
     */
    public function patterns(bool $enabled = true): self
    {
        $this->patterns = $enabled;

        return $this;
    }

    public function toData(): array
    {
        return ['labels' => $this->labels, 'datasets' => $this->datasets];
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'data' => $this->url === null ? $this->toData() : null,
            'url' => $this->url,
            'title' => $this->showTitle ? $this->title : null,
            'height' => $this->height,
            'patterns' => $this->patterns,
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        $canvas = Html::tag('canvas', ['role' => 'img', 'aria-label' => $this->title], '');

        return Html::tag(
            'figure',
            $this->rootAttributes(['celerik-chart']),
            Html::tag('div', ['class' => 'celerik-chart__canvas'], $canvas) . $this->dataTable(),
        );
    }

    /**
     * Text alternative for screen readers. Charts loaded from a URL get theirs from the JS wrapper.
     */
    private function dataTable(): string
    {
        if ($this->url !== null || $this->datasets === []) {
            return '';
        }

        $headers = Html::tag('th', ['scope' => 'col'], '');
        foreach ($this->datasets as $dataset) {
            $headers .= Html::tag('th', ['scope' => 'col'], Html::escape((string) $dataset['label']));
        }

        $body = '';
        foreach ($this->labels as $index => $label) {
            $cells = Html::tag('th', ['scope' => 'row'], Html::escape($label));
            foreach ($this->datasets as $dataset) {
                $cells .= Html::tag('td', [], Html::escape(Arr::toText($dataset['data'][$index] ?? null)));
            }
            $body .= Html::tag('tr', [], $cells);
        }

        return Html::tag(
            'table',
            ['class' => 'visually-hidden'],
            Html::tag('caption', [], Html::escape($this->title))
                . Html::tag('thead', [], Html::tag('tr', [], $headers))
                . Html::tag('tbody', [], $body),
        );
    }
}
