<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Adapters\Laravel;

use Celerik\Ui\Adapters\Laravel\EloquentEventSource;
use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EloquentEventSourceTest extends TestCase
{
    private const FIELDS = ['start' => 'starts_at', 'end' => 'ends_at'];

    protected function setUp(): void
    {
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('meetings', static function ($table): void {
            $table->increments('id');
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('secret')->nullable();
        });

        foreach (
            [
                ['Last month', '2026-10-01 09:00:00', '2026-10-01 10:00:00', false],
                ['Ends at range start', '2026-11-01 22:00:00', '2026-11-02 00:00:00', false],
                ['Overnight', '2026-11-01 23:00:00', '2026-11-02 01:00:00', false],
                ['Stand-up', '2026-11-05 09:00:00', null, false],
                ['Holiday', '2026-11-06 00:00:00', null, true],
                ['Next week', '2026-11-09 00:00:00', null, false],
            ] as [$title, $start, $end, $allDay]
        ) {
            Meeting::query()->create([
                'title' => $title,
                'starts_at' => $start,
                'ends_at' => $end,
                'all_day' => $allDay,
                'secret' => 'never sent',
            ]);
        }
    }

    public function testReturnsOnlyOverlappingEvents(): void
    {
        $events = (new EloquentEventSource(Meeting::query(), self::FIELDS))->between(
            new DateRange(new DateTimeImmutable('2026-11-02 00:00:00'), new DateTimeImmutable('2026-11-09 00:00:00')),
        );

        self::assertSame(
            ['Overnight', 'Stand-up', 'Holiday'],
            array_map(static fn (Event $event) => $event->title, [...$events]),
        );
    }

    public function testMapsColumnsAndOnlyExposesEventFields(): void
    {
        $result = Scheduler::make()->respond(new EloquentEventSource(Meeting::query(), self::FIELDS), [
            'start' => '2026-11-06T00:00:00Z',
            'end' => '2026-11-07T00:00:00Z',
        ]);

        self::assertSame([['id' => 5, 'title' => 'Holiday', 'start' => '2026-11-06', 'allDay' => true]], $result->toArray()['data']);
    }

    public function testRangeOffsetsAreConvertedToTheApplicationTimeZone(): void
    {
        // 09:00 UTC is 10:00 in Paris: a range starting at 09:30 Paris (08:30 UTC) includes the stand-up.
        $events = (new EloquentEventSource(Meeting::query(), self::FIELDS))->between(new DateRange(
            new DateTimeImmutable('2026-11-05T09:30:00+01:00'),
            new DateTimeImmutable('2026-11-05T11:00:00+01:00'),
        ));

        self::assertSame(['Stand-up'], array_map(static fn (Event $event) => $event->title, [...$events]));
    }

    public function testRejectsUnknownPropertiesAndInvalidColumns(): void
    {
        try {
            new EloquentEventSource(Meeting::query(), ['location' => 'room']);
            self::fail('Expected an exception for an unknown property.');
        } catch (InvalidArgumentException) {
        }

        $this->expectException(InvalidArgumentException::class);
        new EloquentEventSource(Meeting::query(), ['start' => 'starts_at; drop table meetings']);
    }
}
