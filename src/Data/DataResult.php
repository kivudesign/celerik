<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

use JsonSerializable;

/**
 * One page of rows plus the metadata every Celerik endpoint returns:
 *
 *     {"data": [...], "meta": {"page", "perPage", "total", "filtered", "draw", "sort", "search", "filters"}}
 */
final class DataResult implements JsonSerializable
{
    /**
     * @param list<array<string, mixed>> $rows
     * @param int $total Row count before search and filters.
     * @param int $filtered Row count after search and filters, before paging.
     */
    public function __construct(
        public readonly array $rows,
        public readonly int $total,
        public readonly int $filtered,
        public readonly DataRequest $request,
    ) {
    }

    /**
     * Returns a copy with transformed rows (used to project rows onto the declared columns).
     *
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    public function map(callable $callback): self
    {
        return new self(array_values(array_map($callback, $this->rows)), $this->total, $this->filtered, $this->request);
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'data' => $this->rows,
            'meta' => [
                'page' => $this->request->page,
                'perPage' => $this->request->perPage,
                'total' => $this->total,
                'filtered' => $this->filtered,
                'draw' => $this->request->draw,
                'sort' => $this->request->sort,
                'search' => $this->request->search,
                'filters' => (object) $this->request->filters,
            ],
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
