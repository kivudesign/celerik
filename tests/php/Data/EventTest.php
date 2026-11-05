<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Data;

use Celerik\Ui\Data\ArrayEventSource;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use Celerik\Ui\Support\Dates;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    public function testBuildsFromRequestDataAndSerializesForFullCalendar(): void
    {
        $event = Event::fromArray([
            'id' => 7,
            'title' => '  Kick-off  ',
            'start' => '2026-11-05T09:00:00+01:00',
            'end' => '2026-11-05T10:30:00+01:00',
            'color' => '#0d6efd',
            'url' => '/meetings/7',
            'extendedProps' => ['room' => 'B12'],
        ]);

        self::assertSame([
            'id' => 7,
            'title' => 'Kick-off',
            'start' => '2026-11-05T09:00:00+01:00',
            'end' => '2026-11-05T10:30:00+01:00',
            'allDay' => false,
            'color' => '#0d6efd',
            'textColor' => '#000',
            'url' => '/meetings/7',
            'extendedProps' => ['room' => 'B12'],
        ], $event->toArray());
    }

    public function testTextColorIsTheHigherContrastOfBlackAndWhite(): void
    {
        self::assertSame('#fff', Event::textColorFor('#0f172a'));
        self::assertSame('#000', Event::textColorFor('#ffc107'));
        self::assertSame('#000', Event::textColorFor('#767676'), 'Black 4.63:1 beats white 4.54:1.');
        self::assertSame('#fff', Event::textColorFor('#757575'));
        self::assertSame('#fff', Event::textColorFor('#123'));
        self::assertNull(Event::textColorFor('green'), 'Named colors are left to the theme.');
        self::assertNull(Event::textColorFor(null));
    }

    public function testPlainDatesAreAllDayByDefault(): void
    {
        $event = Event::fromArray(['title' => 'Holiday', 'start' => '2026-11-11', 'allDay' => 'true']);

        self::assertTrue($event->allDay);
        self::assertSame(['title' => 'Holiday', 'start' => '2026-11-11', 'allDay' => true], $event->toArray());
        self::assertTrue(Event::fromArray(['title' => 'Holiday', 'start' => '2026-11-11'])->allDay);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidEvents(): iterable
    {
        yield 'missing title' => [['start' => '2026-11-05']];
        yield 'blank title' => [['title' => '   ', 'start' => '2026-11-05']];
        yield 'title too long' => [['title' => str_repeat('a', 256), 'start' => '2026-11-05']];
        yield 'missing start' => [['title' => 'x']];
        yield 'relative date' => [['title' => 'x', 'start' => 'next monday']];
        yield 'overflowing date' => [['title' => 'x', 'start' => '2026-02-31']];
        yield 'end before start' => [['title' => 'x', 'start' => '2026-11-05T10:00', 'end' => '2026-11-05T09:00']];
        yield 'javascript url' => [['title' => 'x', 'start' => '2026-11-05', 'url' => 'javascript:alert(1)']];
        yield 'protocol-relative url' => [['title' => 'x', 'start' => '2026-11-05', 'url' => '//evil.example']];
        yield 'css injection color' => [['title' => 'x', 'start' => '2026-11-05', 'color' => 'red;background:url(x)']];
        yield 'array id' => [['title' => 'x', 'start' => '2026-11-05', 'id' => ['1']]];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('invalidEvents')]
    public function testRejectsInvalidEvents(array $data): void
    {
        $this->expectException(InvalidArgumentException::class);

        Event::fromArray($data);
    }

    public function testDatesAcceptOnlyIso8601(): void
    {
        self::assertSame('2026-11-05 14:30', Dates::parse('2026-11-05 14:30')->format('Y-m-d H:i'));
        self::assertSame('+02:00', Dates::parse('2026-11-05T14:30:00.123+02:00')->format('P'));
        self::assertSame('2026-11-05', Dates::parse(new DateTimeImmutable('2026-11-05'))->format('Y-m-d'));

        $this->expectException(InvalidArgumentException::class);
        Dates::parse('tomorrow');
    }

    public function testDateRangeFromQueryAndLimits(): void
    {
        $range = DateRange::fromQuery(['start' => '2026-10-26T00:00:00Z', 'end' => '2026-12-07T00:00:00Z']);
        self::assertSame(42, $range->start->diff($range->end)->days);

        foreach (
            [
                [],
                ['start' => '2026-11-05'],
                ['start' => '2026-11-05', 'end' => '2026-11-05'],
                ['start' => '2026-11-05', 'end' => '2026-11-01'],
                ['start' => '2020-01-01', 'end' => '2026-01-01'],
            ] as $query
        ) {
            try {
                DateRange::fromQuery($query);
                self::fail('Expected an invalid range: ' . json_encode($query));
            } catch (InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testOverlapTreatsEndAsExclusive(): void
    {
        $range = new DateRange(new DateTimeImmutable('2026-11-02'), new DateTimeImmutable('2026-11-09'));
        $source = new ArrayEventSource([
            ['id' => 'inside', 'title' => 'x', 'start' => '2026-11-05T09:00'],
            ['id' => 'before', 'title' => 'x', 'start' => '2026-11-01T10:00', 'end' => '2026-11-02T00:00'],
            ['id' => 'spans-start', 'title' => 'x', 'start' => '2026-11-01T22:00', 'end' => '2026-11-02T02:00'],
            ['id' => 'at-end', 'title' => 'x', 'start' => '2026-11-09'],
            ['id' => 'covers', 'title' => 'x', 'start' => '2026-10-01', 'end' => '2026-12-01'],
        ]);

        // Sorted by start, whatever the storage order.
        self::assertSame(
            ['covers', 'spans-start', 'inside'],
            array_map(static fn (Event $event) => $event->id, [...$source->between($range)]),
        );
    }
}
