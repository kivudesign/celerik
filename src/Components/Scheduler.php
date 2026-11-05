<?php

declare(strict_types=1);

namespace Celerik\Ui\Components;

use Celerik\Ui\Component;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use Celerik\Ui\Data\EventResult;
use Celerik\Ui\Data\EventSourceInterface;
use Celerik\Ui\Support\Dates;
use Celerik\Ui\Support\Html;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Scheduler / calendar backed by FullCalendar (MIT plugins only).
 *
 * Events are embedded with events() or loaded per visible range from url(); the endpoint
 * answers with respond(). With createUrl(), updateUrl() and deleteUrl() users can add,
 * move, resize and remove events; each change is POSTed as JSON with the CSRF header,
 * and rolled back in the calendar if the server rejects it.
 */
final class Scheduler extends Component
{
    /** Public view names => FullCalendar view names. */
    public const VIEWS = [
        'month' => 'dayGridMonth',
        'week' => 'timeGridWeek',
        'day' => 'timeGridDay',
        'list' => 'listWeek',
    ];

    private string $view = 'month';

    /** @var list<string> */
    private array $views = ['month', 'week', 'day', 'list'];

    private ?string $date = null;

    private ?string $url = null;

    /** @var list<Event> */
    private array $events = [];

    /** @var array{startTime: string, endTime: string, daysOfWeek: list<int>}|null */
    private ?array $businessHours = null;

    private ?int $firstDay = null;

    private ?string $locale = null;

    private string $timeZone = 'local';

    private string $title = 'Calendar';

    private ?string $slotMinTime = null;

    private ?string $slotMaxTime = null;

    private ?string $createUrl = null;

    private ?string $updateUrl = null;

    private ?string $deleteUrl = null;

    private ?string $csrfToken = null;

    private string $csrfHeader = 'X-CSRF-TOKEN';

    public static function name(): string
    {
        return 'scheduler';
    }

    /**
     * Initial view: month, week, day or list.
     */
    public function view(string $view): self
    {
        $this->view = self::assertView($view);

        return $this;
    }

    /**
     * Views offered in the toolbar, in order.
     */
    public function views(string ...$views): self
    {
        $this->views = array_values(array_unique(array_map(self::assertView(...), $views)));

        return $this;
    }

    /**
     * Initially displayed date (defaults to today).
     */
    public function date(DateTimeInterface|string $date): self
    {
        $this->date = Dates::parse($date)->format('Y-m-d');

        return $this;
    }

    /**
     * Loads events for each visible range from this endpoint (answered by respond()).
     */
    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Embedded events, also rendered as a list for browsers without JavaScript.
     *
     * @param iterable<Event|array<string, mixed>> $events
     */
    public function events(iterable $events): self
    {
        $this->events = [];

        foreach ($events as $event) {
            $this->events[] = $event instanceof Event ? $event : Event::fromArray($event);
        }

        return $this;
    }

    /**
     * Highlights working hours, e.g. businessHours('08:00', '18:00', [1, 2, 3, 4, 5]).
     *
     * @param list<int> $days 0 = Sunday ... 6 = Saturday
     */
    public function businessHours(string $start = '09:00', string $end = '17:00', array $days = [1, 2, 3, 4, 5]): self
    {
        foreach ($days as $day) {
            if (!is_int($day) || $day < 0 || $day > 6) {
                throw new InvalidArgumentException('Business days must be integers from 0 (Sunday) to 6.');
            }
        }

        $this->businessHours = [
            'startTime' => self::assertTime($start),
            'endTime' => self::assertTime($end),
            'daysOfWeek' => array_values($days),
        ];

        return $this;
    }

    /**
     * Visible hours in the week and day views, e.g. slotTimes('07:00', '20:00').
     */
    public function slotTimes(string $min, string $max): self
    {
        $this->slotMinTime = self::assertTime($min);
        $this->slotMaxTime = self::assertTime($max);

        return $this;
    }

    /**
     * First day of the week: 0 = Sunday, 1 = Monday...
     */
    public function firstDay(int $day): self
    {
        if ($day < 0 || $day > 6) {
            throw new InvalidArgumentException('First day must be from 0 (Sunday) to 6.');
        }

        $this->firstDay = $day;

        return $this;
    }

    /**
     * BCP 47 locale for dates and toolbar texts, e.g. "fr" or "en-GB".
     */
    public function locale(string $locale): self
    {
        if (preg_match('/^[a-z]{2,3}(-[A-Za-z]{2,4})?$/', $locale) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid locale "%s".', $locale));
        }

        $this->locale = $locale;

        return $this;
    }

    /**
     * "local" (browser time zone, default), "UTC" or an IANA name such as "Europe/Paris".
     */
    public function timeZone(string $timeZone): self
    {
        $known = DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC);

        if ($timeZone !== 'local' && $timeZone !== 'UTC' && !in_array($timeZone, $known, true)) {
            throw new InvalidArgumentException(sprintf('Unknown time zone "%s".', $timeZone));
        }

        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * Accessible name of the calendar region.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Endpoint receiving new events (selecting a time range creates one).
     */
    public function createUrl(string $url): self
    {
        $this->createUrl = $url;

        return $this;
    }

    /**
     * Endpoint receiving moved and resized events.
     */
    public function updateUrl(string $url): self
    {
        $this->updateUrl = $url;

        return $this;
    }

    /**
     * Endpoint receiving deleted events (clicking an event without a URL deletes it).
     */
    public function deleteUrl(string $url): self
    {
        $this->deleteUrl = $url;

        return $this;
    }

    /**
     * CSRF token sent in a header with every change (Laravel reads X-CSRF-TOKEN natively).
     */
    public function csrf(string $token, string $header = 'X-CSRF-TOKEN'): self
    {
        if (preg_match('/^[A-Za-z0-9-]+$/', $header) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid header name "%s".', $header));
        }

        $this->csrfToken = $token;
        $this->csrfHeader = $header;

        return $this;
    }

    /**
     * Answers FullCalendar's request for the visible range (`?start=...&end=...`).
     *
     * @param array<string, mixed> $query Usually `$_GET` or `$request->query()`.
     * @throws InvalidArgumentException When the range is missing, invalid or too long: answer with HTTP 400.
     */
    public function respond(EventSourceInterface $source, array $query): EventResult
    {
        $range = DateRange::fromQuery($query);
        $events = [];

        foreach ($source->between($range) as $event) {
            if ($range->overlaps($event)) {
                $events[] = $event;
            }
        }

        return new EventResult($events, $range);
    }

    public function toArray(): array
    {
        return array_filter([
            'view' => self::VIEWS[$this->view],
            'views' => array_map(static fn (string $view): string => self::VIEWS[$view], $this->views),
            'date' => $this->date,
            'url' => $this->url,
            'events' => $this->url === null
                ? array_map(static fn (Event $event): array => $event->toArray(), $this->events)
                : null,
            'businessHours' => $this->businessHours,
            'slotMinTime' => $this->slotMinTime,
            'slotMaxTime' => $this->slotMaxTime,
            'firstDay' => $this->firstDay,
            'locale' => $this->locale,
            'timeZone' => $this->timeZone,
            'createUrl' => $this->createUrl,
            'updateUrl' => $this->updateUrl,
            'deleteUrl' => $this->deleteUrl,
            'csrf' => $this->csrfToken === null ? null : ['header' => $this->csrfHeader, 'token' => $this->csrfToken],
            'options' => $this->options,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    public function render(): string
    {
        $attributes = array_merge($this->rootAttributes(['celerik-scheduler']), [
            'role' => 'region',
            'aria-label' => $this->title,
        ]);

        return Html::tag('div', $attributes, $this->fallback());
    }

    /**
     * Content shown until (or unless) JavaScript replaces it with the calendar.
     */
    private function fallback(): string
    {
        if ($this->url !== null) {
            return Html::tag('noscript', [], Html::tag('p', [], 'JavaScript is required to display this calendar.'));
        }

        if ($this->events === []) {
            return Html::tag('p', ['class' => 'celerik-scheduler__fallback'], 'No events.');
        }

        $items = '';
        foreach ($this->events as $event) {
            $format = $event->allDay ? 'Y-m-d' : 'Y-m-d H:i';
            $when = $event->start->format($format)
                . ($event->end === null ? '' : ' – ' . $event->end->format($format));
            $datetime = $event->start->format($event->allDay ? 'Y-m-d' : DATE_ATOM);

            $items .= Html::tag(
                'li',
                [],
                Html::tag('time', ['datetime' => $datetime], Html::escape($when)) . ' ' . Html::escape($event->title),
            );
        }

        return Html::tag('ul', ['class' => 'celerik-scheduler__fallback'], $items);
    }

    private static function assertView(string $view): string
    {
        if (!isset(self::VIEWS[$view])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown view "%s". Supported: %s.',
                $view,
                implode(', ', array_keys(self::VIEWS)),
            ));
        }

        return $view;
    }

    private static function assertTime(string $time): string
    {
        if (preg_match('/^([01]\d|2[0-4]):[0-5]\d$/', $time) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid time "%s": expected HH:MM.', $time));
        }

        return $time;
    }
}
