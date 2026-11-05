<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

/**
 * Normalized paging, sorting, search and filter parameters.
 *
 * Built from query parameters in either of two formats:
 * - DataTables server-side protocol: draw, start, length, order[i][column|dir], columns[i][data], search[value]
 * - Celerik simple format: page, per_page, sort=name,-created_at, search, filter[field]=value
 */
final class DataRequest
{
    /** A plain field (`email`) or one level of relation (`author.name`). */
    public const FIELD_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/';
    public const MAX_PER_PAGE = 1000;
    public const MAX_SEARCH_LENGTH = 255;

    /**
     * Escape character for SQL LIKE. Pass it explicitly (`LIKE ? ESCAPE '!'`): SQLite and
     * SQL Server have no default escape character, and `!` needs no quoting in any dialect.
     */
    public const LIKE_ESCAPE = '!';

    /**
     * @param list<array{field: string, direction: 'asc'|'desc'}> $sort
     * @param array<string, string> $filters Exact-match filters, field => value.
     * @param list<string> $searchFields Fields the search term applies to; empty lets the source decide.
     */
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 10,
        public readonly array $sort = [],
        public readonly ?string $search = null,
        public readonly array $filters = [],
        public readonly array $searchFields = [],
        public readonly int $draw = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $query Usually `$_GET` or `$request->query()`.
     * @param list<string>|null $sortable Fields the client may sort on; null allows any valid field name.
     * @param list<string>|null $searchable Fields searched and allowed as filters; null allows any valid field name.
     */
    public static function fromArray(
        array $query,
        ?array $sortable = null,
        ?array $searchable = null,
        int $defaultPerPage = 10,
    ): self {
        $isDataTables = isset($query['draw']) || isset($query['start']);

        if ($isDataTables) {
            $length = self::int($query['length'] ?? $defaultPerPage);
            $perPage = self::clamp($length === -1 ? self::MAX_PER_PAGE : $length);
            $page = intdiv(max(0, self::int($query['start'] ?? 0)), $perPage) + 1;
            $columns = is_array($query['columns'] ?? null) ? $query['columns'] : [];
            $sort = self::dataTablesSort($query['order'] ?? [], $columns);
            $search = self::search(is_array($query['search'] ?? null) ? ($query['search']['value'] ?? null) : null);
            $filters = self::dataTablesFilters($columns);
        } else {
            $perPage = self::clamp(self::int($query['per_page'] ?? $defaultPerPage));
            $page = max(1, self::int($query['page'] ?? 1));
            $sort = self::simpleSort($query['sort'] ?? null);
            $search = self::search($query['search'] ?? null);
            $filters = is_array($query['filter'] ?? null) ? $query['filter'] : [];
        }

        $sort = array_values(array_filter(
            $sort,
            static fn (array $rule): bool => self::isAllowed($rule['field'], $sortable),
        ));

        $cleanFilters = [];
        foreach ($filters as $field => $value) {
            if (!is_string($field) || !is_scalar($value) || (string) $value === '') {
                continue;
            }

            if (self::isAllowed($field, $searchable)) {
                $cleanFilters[$field] = (string) $value;
            }
        }

        return new self(
            page: $page,
            perPage: $perPage,
            sort: $sort,
            search: $search,
            filters: $cleanFilters,
            searchFields: $searchable ?? [],
            draw: max(0, self::int($query['draw'] ?? 0)),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * The search term as a "contains" LIKE pattern, with `%` and `_` matched literally.
     * Use it with an explicit escape clause: `LIKE ? ESCAPE '!'` (see LIKE_ESCAPE).
     */
    public function likePattern(): ?string
    {
        if ($this->search === null) {
            return null;
        }

        $escape = self::LIKE_ESCAPE;
        $escaped = str_replace(
            [$escape, '%', '_'],
            [$escape . $escape, $escape . '%', $escape . '_'],
            $this->search,
        );

        return '%' . $escaped . '%';
    }

    public static function isValidField(string $field): bool
    {
        return preg_match(self::FIELD_PATTERN, $field) === 1;
    }

    /**
     * @param list<string>|null $whitelist
     */
    private static function isAllowed(string $field, ?array $whitelist): bool
    {
        return self::isValidField($field) && ($whitelist === null || in_array($field, $whitelist, true));
    }

    /**
     * @param mixed $order
     * @param array<array-key, mixed> $columns
     * @return list<array{field: string, direction: 'asc'|'desc'}>
     */
    private static function dataTablesSort(mixed $order, array $columns): array
    {
        $sort = [];

        foreach (is_array($order) ? $order : [] as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $column = $columns[self::int($rule['column'] ?? -1)] ?? null;
            $field = is_array($column) ? ($column['data'] ?? null) : null;

            if (is_string($field)) {
                $sort[] = ['field' => $field, 'direction' => self::direction($rule['dir'] ?? 'asc')];
            }
        }

        return $sort;
    }

    /**
     * @param array<array-key, mixed> $columns
     * @return array<string, mixed>
     */
    private static function dataTablesFilters(array $columns): array
    {
        $filters = [];

        foreach ($columns as $column) {
            if (!is_array($column) || !is_array($column['search'] ?? null)) {
                continue;
            }

            $field = $column['data'] ?? null;
            $value = $column['search']['value'] ?? null;

            if (is_string($field) && is_scalar($value)) {
                $filters[$field] = $value;
            }
        }

        return $filters;
    }

    /**
     * @return list<array{field: string, direction: 'asc'|'desc'}>
     */
    private static function simpleSort(mixed $sort): array
    {
        if (!is_string($sort) || $sort === '') {
            return [];
        }

        $rules = [];

        foreach (explode(',', $sort) as $field) {
            $field = trim($field);
            $descending = str_starts_with($field, '-');
            $rules[] = ['field' => ltrim($field, '-+'), 'direction' => $descending ? 'desc' : 'asc'];
        }

        return $rules;
    }

    /**
     * @return 'asc'|'desc'
     */
    private static function direction(mixed $direction): string
    {
        return is_string($direction) && strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }

    private static function search(mixed $search): ?string
    {
        if (!is_scalar($search)) {
            return null;
        }

        $search = trim((string) $search);

        return $search === '' ? null : mb_substr($search, 0, self::MAX_SEARCH_LENGTH);
    }

    private static function int(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private static function clamp(int $perPage): int
    {
        return max(1, min(self::MAX_PER_PAGE, $perPage));
    }
}
