<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

use Celerik\Ui\Support\Arr;

/**
 * In-memory data source for plain PHP arrays (framework-free apps, fixtures, tests).
 */
final class ArrayDataSource implements DataSourceInterface
{
    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(private readonly array $rows)
    {
    }

    public function fetch(DataRequest $request): DataResult
    {
        $rows = array_values($this->rows);
        $total = count($rows);

        foreach ($request->filters as $field => $value) {
            $rows = array_filter($rows, static fn (array $row): bool => Arr::toText(Arr::get($row, $field)) === $value);
        }

        if ($request->search !== null) {
            $fields = $request->searchFields !== []
                ? $request->searchFields
                : array_keys($rows[array_key_first($rows)] ?? []);
            $rows = array_filter($rows, static function (array $row) use ($fields, $request): bool {
                foreach ($fields as $field) {
                    if (mb_stripos(Arr::toText(Arr::get($row, (string) $field)), (string) $request->search) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        $rows = array_values($rows);
        $filtered = count($rows);

        if ($request->sort !== []) {
            usort($rows, static function (array $a, array $b) use ($request): int {
                foreach ($request->sort as $rule) {
                    $result = self::compare(Arr::get($a, $rule['field']), Arr::get($b, $rule['field']));

                    if ($result !== 0) {
                        return $rule['direction'] === 'desc' ? -$result : $result;
                    }
                }

                return 0;
            });
        }

        return new DataResult(array_slice($rows, $request->offset(), $request->perPage), $total, $filtered, $request);
    }

    private static function compare(mixed $a, mixed $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return $a <=> $b;
        }

        return strnatcasecmp(Arr::toText($a), Arr::toText($b));
    }
}
