<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Laravel;

use Celerik\Ui\Adapters\Laravel\EloquentDataSource;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Data\DataRequest;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

final class EloquentDataSourceTest extends TestCase
{
    protected function setUp(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('people', static function ($table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('role');
            $table->string('password');
        });

        foreach ([['Ada', 'admin'], ['Alan', 'user'], ['Grace', 'admin'], ['Linus', 'user']] as [$name, $role]) {
            Person::query()->create(['name' => $name, 'role' => $role, 'password' => 'hash']);
        }
    }

    public function testPaginatesSortsAndCounts(): void
    {
        $result = (new EloquentDataSource(Person::query()))->fetch(new DataRequest(
            page: 1,
            perPage: 2,
            sort: [['field' => 'name', 'direction' => 'desc']],
        ));

        self::assertSame(['Linus', 'Grace'], array_column($result->rows, 'name'));
        self::assertSame(4, $result->total);
        self::assertSame(4, $result->filtered);
        self::assertArrayNotHasKey('password', $result->rows[0], 'Hidden model attributes must not be exposed.');
    }

    public function testSearchesAndFilters(): void
    {
        $result = (new EloquentDataSource(Person::query(), ['name']))->fetch(new DataRequest(
            search: 'a',
            filters: ['role' => 'admin'],
        ));

        self::assertSame(['Ada', 'Grace'], array_column($result->rows, 'name'));
        self::assertSame(4, $result->total);
        self::assertSame(2, $result->filtered);
    }

    public function testSearchTreatsWildcardsLiterally(): void
    {
        $result = (new EloquentDataSource(Person::query(), ['name']))->fetch(new DataRequest(search: '%'));

        self::assertSame(0, $result->filtered);
    }

    public function testWorksThroughGridRespond(): void
    {
        $grid = Grid::make()->column('id')->column('name');

        $result = $grid->respond(new EloquentDataSource(Person::query()), [
            'draw' => '1',
            'start' => '1',
            'length' => '1',
            'columns' => [['data' => 'id'], ['data' => 'name']],
            'order' => [['column' => '1', 'dir' => 'asc']],
            'search' => ['value' => 'n'],
        ]);

        self::assertSame([['id' => 4, 'name' => 'Linus']], $result->rows);
        self::assertSame(2, $result->filtered);
    }
}
