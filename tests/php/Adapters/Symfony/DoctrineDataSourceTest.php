<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Symfony;

use Celerik\Ui\Adapters\Symfony\DoctrineDataSource;
use Celerik\Ui\Components\Grid;
use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Tests\Adapters\Symfony\Entity\Author;
use Celerik\Ui\Tests\Adapters\Symfony\Entity\Book;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class DoctrineDataSourceTest extends TestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        $config = ORMSetup::createAttributeMetadataConfiguration([__DIR__ . '/Entity'], isDevMode: true);
        if (PHP_VERSION_ID >= 80400 && method_exists($config, 'enableNativeLazyObjects')) {
            $config->enableNativeLazyObjects(true);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $this->em = new EntityManager($connection, $config);
        (new SchemaTool($this->em))->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $ada = new Author('Ada Lovelace');
        $alan = new Author('Alan Turing');
        $this->em->persist($ada);
        $this->em->persist($alan);

        foreach (
            [
                ['Notes on the Engine', 'Science', 120, $ada],
                ['Poetical Science', 'Essay', 45, $ada],
                ['Computable Numbers', 'Science', 36, $alan],
                ['100% Machines', 'Essay', 300, $alan],
                ['Under_score Tales', 'Fiction', 210, $alan],
            ] as [$title, $genre, $pages, $author]
        ) {
            $this->em->persist(new Book($title, $genre, $pages, $author));
        }

        $this->em->flush();
        $this->em->clear();
    }

    public function testPaginatesSortsAndCounts(): void
    {
        $result = (new DoctrineDataSource($this->books()))->fetch(new DataRequest(
            page: 2,
            perPage: 2,
            sort: [['field' => 'pages', 'direction' => 'desc']],
        ));

        self::assertSame(['Notes on the Engine', 'Poetical Science'], array_column($result->rows, 'title'));
        self::assertSame(5, $result->total);
        self::assertSame(5, $result->filtered);
    }

    public function testSearchIsCaseInsensitiveAndFiltersAreExact(): void
    {
        $result = (new DoctrineDataSource($this->books(), ['title']))->fetch(new DataRequest(
            search: 'SCIENCE',
            filters: ['genre' => 'Essay'],
        ));

        self::assertSame(['Poetical Science'], array_column($result->rows, 'title'));
        self::assertSame(5, $result->total);
        self::assertSame(1, $result->filtered);
    }

    public function testSearchTreatsWildcardsLiterally(): void
    {
        $source = new DoctrineDataSource($this->books(), ['title']);

        self::assertSame(['100% Machines'], array_column($source->fetch(new DataRequest(search: '%'))->rows, 'title'));
        self::assertSame(['Under_score Tales'], array_column($source->fetch(new DataRequest(search: '_'))->rows, 'title'));
        self::assertSame([], $source->fetch(new DataRequest(search: '!'))->rows);
    }

    public function testDottedFieldsWorkAfterAJoin(): void
    {
        $query = $this->books()->addSelect('a.name')->join('b.author', 'a');

        $result = (new DoctrineDataSource($query))->fetch(new DataRequest(
            sort: [['field' => 'a.name', 'direction' => 'desc'], ['field' => 'title', 'direction' => 'asc']],
            filters: ['a.name' => 'Alan Turing'],
        ));

        self::assertSame(
            ['100% Machines', 'Computable Numbers', 'Under_score Tales'],
            array_column($result->rows, 'title'),
        );
        self::assertSame(3, $result->filtered);
    }

    public function testFieldMapLetsAGridSortSearchAndReturnJoinedColumns(): void
    {
        $query = $this->em->createQueryBuilder()
            ->select('b.id, b.title, a.name AS author')
            ->from(Book::class, 'b')
            ->join('b.author', 'a');

        $grid = Grid::make('books')->column('title')->column('author');

        $result = $grid->respond(new DoctrineDataSource($query, fieldMap: ['author' => 'a.name']), [
            'sort' => '-author,title',
            'search' => 'ada',
            'per_page' => '10',
        ]);

        self::assertSame([
            ['title' => 'Notes on the Engine', 'author' => 'Ada Lovelace'],
            ['title' => 'Poetical Science', 'author' => 'Ada Lovelace'],
        ], $result->rows);
        self::assertSame(2, $result->filtered);
    }

    public function testExistingOrderByDoesNotBreakCounts(): void
    {
        $result = (new DoctrineDataSource($this->books()->orderBy('b.title')))->fetch(new DataRequest(perPage: 2));

        self::assertSame(5, $result->total);
        self::assertSame(['100% Machines', 'Computable Numbers'], array_column($result->rows, 'title'));
    }

    private function books(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('b.id, b.title, b.genre, b.pages')
            ->from(Book::class, 'b');
    }
}
