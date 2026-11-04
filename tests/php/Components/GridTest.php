<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Grid;
use Celerik\Ui\Components\Grid\Column;
use Celerik\Ui\Data\ArrayDataSource;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class GridTest extends TestCase
{
    public function testRendersAccessibleTableWithEscapedRows(): void
    {
        $html = Grid::make('users')
            ->caption('Users', visuallyHidden: true)
            ->column('name')
            ->column('email', 'E-mail')
            ->rows([['name' => '<b>Ada</b>', 'email' => 'ada@example.com']])
            ->render();

        self::assertStringContainsString('<table id="users"', $html);
        self::assertStringContainsString('data-celerik="grid"', $html);
        self::assertStringContainsString('<caption class="visually-hidden">Users</caption>', $html);
        self::assertStringContainsString('<th scope="col">E-mail</th>', $html);
        self::assertStringContainsString('<td>&lt;b&gt;Ada&lt;/b&gt;</td>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testServerSideModeRendersEmptyBodyAndPassesUrl(): void
    {
        $grid = Grid::make('users')->url('/users/data')->column('name')->export('csv', 'copy')->pageSize(25);

        self::assertStringContainsString('<tbody></tbody>', $grid->render());
        self::assertSame('/users/data', $grid->toArray()['url']);
        self::assertSame(['csv', 'copy'], $grid->toArray()['export']);
        self::assertSame(25, $grid->toArray()['pageSize']);
    }

    public function testRespondOnlyExposesDeclaredColumns(): void
    {
        $grid = Grid::make()
            ->column('name')
            ->column('team.name', 'Team')
            ->addColumn(Column::make('secret_note')->searchable(false)->sortable(false)->hidden());

        $source = new ArrayDataSource([
            ['name' => 'Ada', 'password' => 'hash', 'team' => ['name' => 'Math', 'budget' => 1], 'secret_note' => 'shh'],
            ['name' => 'Bob', 'password' => 'hash', 'team' => ['name' => 'Ops', 'budget' => 2], 'secret_note' => 'x'],
        ]);

        $result = $grid->respond($source, ['search' => 'shh', 'sort' => '-name']);
        self::assertSame([], $result->rows, 'Non-searchable columns must not be searched.');

        $result = $grid->respond($source, ['sort' => '-name']);
        self::assertSame(
            ['name' => 'Bob', 'team' => ['name' => 'Ops'], 'secret_note' => 'x'],
            $result->rows[0],
        );
    }

    public function testRespondIgnoresSortOnNonSortableColumns(): void
    {
        $grid = Grid::make()->column('name', null, static fn (Column $c) => $c->sortable(false));

        $result = $grid->respond(new ArrayDataSource([['name' => 'b'], ['name' => 'a']]), ['sort' => 'name']);

        self::assertSame([], $result->request->sort);
        self::assertSame('b', $result->rows[0]['name']);
    }

    public function testJsonResponseShape(): void
    {
        $grid = Grid::make()->column('name');
        $json = json_encode($grid->respond(new ArrayDataSource([['name' => 'a']]), ['draw' => '7', 'start' => '0', 'length' => '10']));

        self::assertSame(
            '{"data":[{"name":"a"}],"meta":{"page":1,"perPage":10,"total":1,"filtered":1,"draw":7,'
            . '"sort":[],"search":null,"filters":{}}}',
            $json,
        );
    }

    public function testRequiresColumns(): void
    {
        $this->expectException(LogicException::class);

        Grid::make()->render();
    }

    public function testRejectsUnknownExportFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Grid::make()->export('pdf');
    }

    public function testRejectsInvalidColumnField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Column::make('name; drop');
    }
}
