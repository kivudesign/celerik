<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Data;

use Celerik\Ui\Data\DataRequest;
use PHPUnit\Framework\TestCase;

final class DataRequestTest extends TestCase
{
    public function testParsesDataTablesProtocol(): void
    {
        $request = DataRequest::fromArray([
            'draw' => '3',
            'start' => '20',
            'length' => '10',
            'order' => [['column' => '1', 'dir' => 'desc']],
            'columns' => [
                ['data' => 'id', 'search' => ['value' => '']],
                ['data' => 'name', 'search' => ['value' => 'Ada']],
            ],
            'search' => ['value' => '  lovelace '],
        ]);

        self::assertSame(3, $request->page);
        self::assertSame(10, $request->perPage);
        self::assertSame(20, $request->offset());
        self::assertSame([['field' => 'name', 'direction' => 'desc']], $request->sort);
        self::assertSame('lovelace', $request->search);
        self::assertSame(['name' => 'Ada'], $request->filters);
        self::assertSame(3, $request->draw);
    }

    public function testParsesSimpleFormat(): void
    {
        $request = DataRequest::fromArray([
            'page' => '2',
            'per_page' => '25',
            'sort' => 'name,-created_at',
            'search' => 'x',
            'filter' => ['status' => 'active'],
        ]);

        self::assertSame(2, $request->page);
        self::assertSame(25, $request->perPage);
        self::assertSame(
            [['field' => 'name', 'direction' => 'asc'], ['field' => 'created_at', 'direction' => 'desc']],
            $request->sort,
        );
        self::assertSame(['status' => 'active'], $request->filters);
    }

    public function testDropsFieldsOutsideTheWhitelist(): void
    {
        $request = DataRequest::fromArray(
            ['sort' => 'name,password', 'filter' => ['password' => 'x', 'name' => 'y']],
            sortable: ['name'],
            searchable: ['name'],
        );

        self::assertSame([['field' => 'name', 'direction' => 'asc']], $request->sort);
        self::assertSame(['name' => 'y'], $request->filters);
        self::assertSame(['name'], $request->searchFields);
    }

    public function testDropsInvalidFieldNamesEvenWithoutWhitelist(): void
    {
        $request = DataRequest::fromArray(['sort' => 'name;DROP TABLE users,(select 1)', 'filter' => ['a b' => '1']]);

        self::assertSame([], $request->sort);
        self::assertSame([], $request->filters);
    }

    public function testClampsPageSize(): void
    {
        self::assertSame(DataRequest::MAX_PER_PAGE, DataRequest::fromArray(['draw' => 1, 'length' => '-1'])->perPage);
        self::assertSame(DataRequest::MAX_PER_PAGE, DataRequest::fromArray(['per_page' => '999999'])->perPage);
        self::assertSame(1, DataRequest::fromArray(['per_page' => '0'])->perPage);
        self::assertSame(1, DataRequest::fromArray(['page' => '-4'])->page);
    }

    public function testLikePatternEscapesWildcardsAndTheEscapeCharacter(): void
    {
        self::assertNull((new DataRequest())->likePattern());
        self::assertSame('%100!%!_a!!b%', (new DataRequest(search: '100%_a!b'))->likePattern());
    }

    public function testUsesDefaultPageSize(): void
    {
        self::assertSame(25, DataRequest::fromArray([], defaultPerPage: 25)->perPage);
    }
}
