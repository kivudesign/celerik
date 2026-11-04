<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Components\Grid\Column;
use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Data\DataResult;
use Celerik\Ui\Data\DataSourceInterface;
use Celerik\Ui\Support\Arr;
use Celerik\Ui\Support\Html;
use InvalidArgumentException;
use LogicException;

/**
 * Data grid backed by DataTables.
 *
 * Client-side mode: pass rows with rows(); they are rendered as HTML (readable without JS).
 * Server-side mode: pass an endpoint with url(); the endpoint answers with respond().
 */
final class Grid extends Component
{
    public const EXPORT_FORMATS = ['copy', 'csv', 'print'];

    /** @var list<Column> */
    private array $columns = [];

    private ?string $url = null;

    /** @var list<array<string, mixed>> */
    private array $rows = [];

    private int $pageSize = 10;

    /** @var list<int> */
    private array $pageSizes = [10, 25, 50, 100];

    private bool $searchable = true;

    /** @var list<string> */
    private array $export = [];

    private ?string $caption = null;

    private bool $captionHidden = false;

    public static function name(): string
    {
        return 'grid';
    }

    /**
     * @param (callable(Column): mixed)|null $configure
     */
    public function column(string $field, ?string $title = null, ?callable $configure = null): self
    {
        $column = Column::make($field, $title);

        if ($configure !== null) {
            $configure($column);
        }

        $this->columns[] = $column;

        return $this;
    }

    public function addColumn(Column ...$columns): self
    {
        array_push($this->columns, ...$columns);

        return $this;
    }

    /**
     * @return list<Column>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Switches to server-side mode: paging, sorting and search are done by this endpoint.
     */
    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param iterable<array<string, mixed>> $rows
     */
    public function rows(iterable $rows): self
    {
        $this->rows = [];

        foreach ($rows as $row) {
            $this->rows[] = $row;
        }

        return $this;
    }

    public function pageSize(int $pageSize): self
    {
        if ($pageSize < 1 || $pageSize > DataRequest::MAX_PER_PAGE) {
            throw new InvalidArgumentException(
                sprintf('Page size must be between 1 and %d.', DataRequest::MAX_PER_PAGE),
            );
        }

        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * @param list<int> $pageSizes
     */
    public function pageSizes(array $pageSizes): self
    {
        $this->pageSizes = array_values(array_map('intval', $pageSizes));

        return $this;
    }

    public function searchable(bool $searchable = true): self
    {
        $this->searchable = $searchable;

        return $this;
    }

    public function export(string ...$formats): self
    {
        $unknown = array_diff($formats, self::EXPORT_FORMATS);

        if ($unknown !== []) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported export format(s): %s. Supported: %s.',
                implode(', ', $unknown),
                implode(', ', self::EXPORT_FORMATS),
            ));
        }

        $this->export = array_values(array_unique($formats));

        return $this;
    }

    /**
     * Table caption, used by screen readers to identify the table (WCAG 1.3.1).
     */
    public function caption(string $caption, bool $visuallyHidden = false): self
    {
        $this->caption = $caption;
        $this->captionHidden = $visuallyHidden;

        return $this;
    }

    /**
     * Answers a server-side request for this grid.
     *
     * Only declared columns may be sorted or searched, and the returned rows contain
     * only declared column fields, so nothing else from the data source leaks out.
     *
     * @param array<string, mixed> $query Usually `$_GET` or `$request->query()`.
     */
    public function respond(DataSourceInterface $source, array $query): DataResult
    {
        $request = DataRequest::fromArray(
            $query,
            sortable: $this->fields(static fn (Column $column): bool => $column->isSortable()),
            searchable: $this->fields(static fn (Column $column): bool => $column->isSearchable()),
            defaultPerPage: $this->pageSize,
        );

        $fields = $this->fields();

        return $source->fetch($request)->map(static function (array $row) use ($fields): array {
            $projected = [];

            foreach ($fields as $field) {
                Arr::set($projected, $field, Arr::get($row, $field));
            }

            return $projected;
        });
    }

    public function toArray(): array
    {
        return array_filter([
            'columns' => array_map(static fn (Column $column): array => $column->toArray(), $this->columns),
            'url' => $this->url,
            'pageSize' => $this->pageSize,
            'pageSizes' => $this->pageSizes,
            'searchable' => $this->searchable,
            'export' => $this->export,
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        if ($this->columns === []) {
            throw new LogicException(sprintf('Grid "%s" has no columns.', $this->id));
        }

        $caption = $this->caption === null ? '' : Html::tag(
            'caption',
            ['class' => $this->captionHidden ? 'visually-hidden' : null],
            Html::escape($this->caption),
        );

        $headers = '';
        foreach ($this->columns as $column) {
            $headers .= Html::tag('th', ['scope' => 'col'], Html::escape($column->getTitle()));
        }

        $body = '';
        if ($this->url === null) {
            foreach ($this->rows as $row) {
                $cells = '';
                foreach ($this->columns as $column) {
                    $cells .= Html::tag('td', [], Html::escape(Arr::toText(Arr::get($row, $column->getField()))));
                }
                $body .= Html::tag('tr', [], $cells);
            }
        }

        return Html::tag(
            'table',
            $this->rootAttributes(['table', 'table-striped', 'table-hover', 'w-100', 'celerik-grid']),
            $caption . Html::tag('thead', [], Html::tag('tr', [], $headers)) . Html::tag('tbody', [], $body),
        );
    }

    /**
     * @param (callable(Column): bool)|null $filter
     * @return list<string>
     */
    private function fields(?callable $filter = null): array
    {
        $columns = $filter === null ? $this->columns : array_filter($this->columns, $filter);

        return array_values(array_map(static fn (Column $column): string => $column->getField(), $columns));
    }
}
