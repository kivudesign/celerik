<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Symfony;

use Celerik\Ui\Adapters\Symfony\DoctrineEventSource;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use Celerik\Ui\Tests\Adapters\Symfony\Entity\Meeting;
use DateTimeImmutable;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\SchemaTool;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DoctrineEventSourceTest extends TestCase
{
    private const FIELDS = ['start' => 'startsAt', 'end' => 'endsAt', 'color' => 'color'];

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

        foreach (
            [
                ['Last month', '2026-10-01 09:00', '2026-10-01 10:00', false, null],
                ['Ends at range start', '2026-11-01 22:00', '2026-11-02 00:00', false, null],
                ['Overnight', '2026-11-01 23:00', '2026-11-02 01:00', false, '#dc3545'],
                ['Stand-up', '2026-11-05 09:00', null, false, null],
                ['Holiday', '2026-11-06 00:00', null, true, 'green'],
                ['Next week', '2026-11-09 00:00', null, false, null],
            ] as [$title, $start, $end, $allDay, $color]
        ) {
            $this->em->persist(new Meeting(
                $title,
                new DateTimeImmutable($start),
                $end === null ? null : new DateTimeImmutable($end),
                $allDay,
                $color,
            ));
        }

        $this->em->flush();
        $this->em->clear();
    }

    public function testReturnsOnlyOverlappingEventsInStartOrder(): void
    {
        $events = (new DoctrineEventSource($this->meetings(), self::FIELDS))->between(
            new DateRange(new DateTimeImmutable('2026-11-02 00:00'), new DateTimeImmutable('2026-11-09 00:00')),
        );

        self::assertSame(
            ['Overnight', 'Stand-up', 'Holiday'],
            array_map(static fn (Event $event) => $event->title, [...$events]),
        );
    }

    public function testRespondSerializesMappedFieldsAndExcludesEventsEndingAtRangeStart(): void
    {
        $result = Scheduler::make()->respond(new DoctrineEventSource($this->meetings(), self::FIELDS), [
            'start' => '2026-11-02T00:00:00Z',
            'end' => '2026-11-03T00:00:00Z',
        ]);

        self::assertSame([[
            'id' => 3,
            'title' => 'Overnight',
            'start' => '2026-11-01T23:00:00+00:00',
            'end' => '2026-11-02T01:00:00+00:00',
            'allDay' => false,
            'color' => '#dc3545',
            'textColor' => '#000',
        ]], $result->toArray()['data']);
    }

    public function testExistingConditionsOnTheQueryAreKept(): void
    {
        $query = $this->meetings()->andWhere('m.allDay = :allDay')->setParameter('allDay', true);

        $events = (new DoctrineEventSource($query, self::FIELDS))->between(
            new DateRange(new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-12-01')),
        );

        self::assertSame(['Holiday'], array_map(static fn (Event $event) => $event->title, [...$events]));
    }

    public function testRejectsDottedOrInvalidProperties(): void
    {
        try {
            new DoctrineEventSource($this->meetings(), ['start' => 'other.startsAt']);
            self::fail('Expected an exception for a dotted property.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        new DoctrineEventSource($this->meetings(), ['title' => null]);
    }

    private function meetings(): QueryBuilder
    {
        return $this->em->createQueryBuilder()->select('m')->from(Meeting::class, 'm');
    }
}
