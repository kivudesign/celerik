<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Laravel;

use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Data\DataResult;
use Celerik\Ui\Data\DataSourceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Data source for an Eloquent or query builder, e.g. `new EloquentDataSource(User::query())`.
 *
 * Models are serialized with toArray(), so `$hidden` attributes are never sent.
 */
final class EloquentDataSource implements DataSourceInterface
{
    /**
     * @param list<string> $searchable Search fields used when the request does not specify any.
     */
    public function __construct(
        private readonly EloquentBuilder|QueryBuilder $query,
        private readonly array $searchable = [],
    ) {
    }

    public function fetch(DataRequest $request): DataResult
    {
        $total = (clone $this->query)->count();
        $query = clone $this->query;

        $fields = $request->searchFields !== [] ? $request->searchFields : $this->searchable;

        if ($request->search !== null && $fields !== []) {
            $term = '%' . addcslashes($request->search, '%_\\') . '%';

            $query->where(static function (EloquentBuilder|QueryBuilder $where) use ($fields, $term): void {
                foreach ($fields as $field) {
                    $where->orWhere($field, 'like', $term);
                }
            });
        }

        foreach ($request->filters as $field => $value) {
            $query->where($field, '=', $value);
        }

        $filtered = (clone $query)->count();

        foreach ($request->sort as $rule) {
            $query->orderBy($rule['field'], $rule['direction']);
        }

        $rows = [];
        foreach ($query->offset($request->offset())->limit($request->perPage)->get() as $row) {
            $rows[] = $row instanceof Arrayable ? $row->toArray() : (array) $row;
        }

        return new DataResult($rows, $total, $filtered, $request);
    }
}
