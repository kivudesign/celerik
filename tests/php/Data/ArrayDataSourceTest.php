<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Data;

use Celerik\Ui\Data\ArrayDataSource;
use Celerik\Ui\Data\DataRequest;
use PHPUnit\Framework\TestCase;

final class ArrayDataSourceTest extends TestCase
{
    private ArrayDataSource $source;

    protected function setUp(): void
    {
        $this->source = new ArrayDataSource([
            ['id' => 1, 'name' => 'Ada Lovelace', 'team' => ['name' => 'Math']],
            ['id' => 2, 'name' => 'Alan Turing', 'team' => ['name' => 'Math']],
            ['id' => 10, 'name' => 'Grace Hopper', 'team' => ['name' => 'Navy']],
        ]);
    }

    public function testPaginates(): void
    {
        $result = $this->source->fetch(new DataRequest(page: 2, perPage: 2));

        self::assertSame([10], array_column($result->rows, 'id'));
        self::assertSame(3, $result->total);
        self::assertSame(3, $result->filtered);
    }

    public function testSearchesCaseInsensitively(): void
    {
        $result = $this->source->fetch(new DataRequest(search: 'HOPPER'));

        self::assertSame([10], array_column($result->rows, 'id'));
        self::assertSame(1, $result->filtered);
    }

    public function testSearchRespectsSearchFields(): void
    {
        $result = $this->source->fetch(new DataRequest(search: 'navy', searchFields: ['name']));

        self::assertSame([], $result->rows);
    }

    public function testFiltersOnNestedFields(): void
    {
        $result = $this->source->fetch(new DataRequest(filters: ['team.name' => 'Math']));

        self::assertSame([1, 2], array_column($result->rows, 'id'));
    }

    public function testSortsNumericallyAndByMultipleFields(): void
    {
        $result = $this->source->fetch(new DataRequest(sort: [
            ['field' => 'team.name', 'direction' => 'desc'],
            ['field' => 'id', 'direction' => 'desc'],
        ]));

        self::assertSame([10, 2, 1], array_column($result->rows, 'id'));
    }
}
