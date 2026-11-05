<?php

declare(strict_types=1);

namespace Celerik\Ui\Tests\Components;

use Celerik\Ui\Components\Scheduler;
use Celerik\Ui\Data\ArrayEventSource;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SchedulerTest extends TestCase
{
    public function testRendersAccessibleRegionWithEscapedFallbackList(): void
    {
        $html = Scheduler::make('team')
            ->title('Team <calendar>')
            ->events([
                ['title' => '<b>Kick-off</b>', 'start' => '2026-11-05T09:00:00+01:00', 'end' => '2026-11-05T10:00:00+01:00'],
                ['title' => 'Holiday', 'start' => '2026-11-11'],
            ])
            ->render();

        self::assertStringContainsString('<div id="team" class="celerik-scheduler" data-celerik="scheduler"', $html);
        self::assertStringContainsString('role="region" aria-label="Team &lt;calendar&gt;"', $html);
        self::assertStringContainsString(
            '<li><time datetime="2026-11-05T09:00:00+01:00">2026-11-05 09:00 – 2026-11-05 10:00</time> &lt;b&gt;Kick-off&lt;/b&gt;</li>',
            $html,
        );
        self::assertStringContainsString('<li><time datetime="2026-11-11">2026-11-11</time> Holiday</li>', $html);
        self::assertStringNotContainsString('<script', $html);
    }

    public function testRemoteSchedulerHasNoscriptFallbackAndNoEmbeddedEvents(): void
    {
        $scheduler = Scheduler::make('team')->url('/events')->events([['title' => 'x', 'start' => '2026-11-05']]);

        self::assertStringContainsString('<noscript>', $scheduler->render());
        self::assertArrayNotHasKey('events', $scheduler->toArray());
        self::assertSame('/events', $scheduler->toArray()['url']);
    }

    public function testMapsOptionsToFullCalendar(): void
    {
        $options = Scheduler::make()
            ->view('week')
            ->views('week', 'day', 'week', 'list')
            ->date('2026-11-05T15:00:00')
            ->businessHours('08:00', '18:00', [1, 2, 3, 4])
            ->slotTimes('07:00', '20:00')
            ->firstDay(1)
            ->locale('fr-CA')
            ->timeZone('America/Montreal')
            ->createUrl('/events/create')
            ->updateUrl('/events/update')
            ->deleteUrl('/events/delete')
            ->csrf('token', 'X-XSRF-TOKEN')
            ->options(['nowIndicator' => true])
            ->toArray();

        self::assertSame([
            'view' => 'timeGridWeek',
            'views' => ['timeGridWeek', 'timeGridDay', 'listWeek'],
            'date' => '2026-11-05',
            'businessHours' => ['startTime' => '08:00', 'endTime' => '18:00', 'daysOfWeek' => [1, 2, 3, 4]],
            'slotMinTime' => '07:00',
            'slotMaxTime' => '20:00',
            'firstDay' => 1,
            'locale' => 'fr-CA',
            'timeZone' => 'America/Montreal',
            'createUrl' => '/events/create',
            'updateUrl' => '/events/update',
            'deleteUrl' => '/events/delete',
            'csrf' => ['header' => 'X-XSRF-TOKEN', 'token' => 'token'],
            'options' => ['nowIndicator' => true],
        ], $options);
    }

    public function testRespondReturnsOnlyEventsInTheRequestedRange(): void
    {
        $source = new ArrayEventSource([
            ['id' => 1, 'title' => 'October', 'start' => '2026-10-15'],
            ['id' => 2, 'title' => 'November', 'start' => '2026-11-05T09:00:00+00:00'],
        ]);

        $json = json_encode(Scheduler::make()->respond($source, [
            'start' => '2026-11-01T00:00:00Z',
            'end' => '2026-12-01T00:00:00Z',
        ]));

        self::assertSame(
            '{"data":[{"id":2,"title":"November","start":"2026-11-05T09:00:00+00:00","allDay":false}],'
            . '"meta":{"start":"2026-11-01T00:00:00+00:00","end":"2026-12-01T00:00:00+00:00","count":1}}',
            $json,
        );
    }

    public function testRespondRejectsInvalidRanges(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Scheduler::make()->respond(new ArrayEventSource([]), ['start' => 'yesterday', 'end' => 'today']);
    }

    /**
     * @return iterable<string, array{callable(Scheduler): mixed}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'unknown view' => [static fn (Scheduler $s) => $s->view('year')];
        yield 'unknown toolbar view' => [static fn (Scheduler $s) => $s->views('month', 'timeline')];
        yield 'bad time' => [static fn (Scheduler $s) => $s->businessHours('8am', '18:00')];
        yield 'bad day' => [static fn (Scheduler $s) => $s->businessHours('08:00', '18:00', [7])];
        yield 'bad first day' => [static fn (Scheduler $s) => $s->firstDay(9)];
        yield 'bad locale' => [static fn (Scheduler $s) => $s->locale('fr"><script>')];
        yield 'bad time zone' => [static fn (Scheduler $s) => $s->timeZone('Mars/Olympus')];
        yield 'time zone injection' => [static fn (Scheduler $s) => $s->timeZone('UTC"><script>')];
        yield 'bad csrf header' => [static fn (Scheduler $s) => $s->csrf('t', "X-Token\r\nInjected: 1")];
    }

    public function testLabelsAreEscapedOptions(): void
    {
        $scheduler = Scheduler::make('team')
            ->updateUrl('/events/update')
            ->labels(['save' => 'Enregistrer', 'editEvent' => 'Modifier « {title} »'])
            ->labels(['cancel' => '<Annuler>']);

        self::assertSame(
            ['save' => 'Enregistrer', 'editEvent' => 'Modifier « {title} »', 'cancel' => '<Annuler>'],
            $scheduler->toArray()['labels'],
        );
        self::assertArrayNotHasKey('labels', Scheduler::make()->toArray());
        self::assertStringContainsString('&quot;cancel&quot;:&quot;&lt;Annuler&gt;&quot;', $scheduler->render());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfiguration(callable $configure): void
    {
        $this->expectException(InvalidArgumentException::class);

        $configure(Scheduler::make());
    }
}
