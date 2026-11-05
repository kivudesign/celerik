<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Laravel;

use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use Celerik\Ui\Data\EventSourceInterface;
use DateTimeZone;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;

/**
 * Event source for an Eloquent or query builder, e.g.
 * `new EloquentEventSource(Meeting::query(), ['start' => 'starts_at', 'end' => 'ends_at'])`.
 *
 * Only rows overlapping the requested range are queried. Dates are compared in the
 * application time zone (PHP's default time zone, which Laravel sets from config/app.php).
 *
 * When saving events, convert dates to that time zone first
 * (`$event->start->setTimezone(new DateTimeZone(config('app.timezone')))`): Eloquent
 * stores a date's wall-clock time and drops its UTC offset.
 */
final class EloquentEventSource implements EventSourceInterface
{
    private const DEFAULT_FIELDS = [
        'id' => 'id',
        'title' => 'title',
        'start' => 'start',
        'end' => 'end',
        'allDay' => 'all_day',
        'color' => null,
        'url' => null,
    ];

    /** @var array<string, string|null> */
    private readonly array $fields;

    /**
     * @param array<string, string|null> $fields Event property => column (null = not stored).
     */
    public function __construct(
        private readonly EloquentBuilder|QueryBuilder $query,
        array $fields = [],
    ) {
        $fields = array_replace(self::DEFAULT_FIELDS, $fields);

        foreach ($fields as $property => $column) {
            if (!array_key_exists($property, self::DEFAULT_FIELDS)) {
                throw new InvalidArgumentException(sprintf('Unknown event property "%s".', $property));
            }
            if ($column !== null && !DataRequest::isValidField($column)) {
                throw new InvalidArgumentException(sprintf('Invalid column "%s".', $column));
            }
        }

        if ($fields['title'] === null || $fields['start'] === null) {
            throw new InvalidArgumentException('The title and start columns are required.');
        }

        $this->fields = $fields;
    }

    public function between(DateRange $range): iterable
    {
        $timeZone = new DateTimeZone(date_default_timezone_get());
        $start = $range->start->setTimezone($timeZone);
        $end = $range->end->setTimezone($timeZone);
        $startColumn = (string) $this->fields['start'];
        $endColumn = $this->fields['end'];

        $query = (clone $this->query)->where($startColumn, '<', $end);

        if ($endColumn === null) {
            $query->where($startColumn, '>=', $start);
        } else {
            // Ends after the range starts, or has no end and starts inside the range.
            $open = static fn (EloquentBuilder|QueryBuilder $q) => $q->whereNull($endColumn)
                ->where($startColumn, '>=', $start);

            $query->where(static fn (EloquentBuilder|QueryBuilder $q) => $q->where($endColumn, '>', $start)
                ->orWhere($open));
        }

        $events = [];
        foreach ($query->orderBy($startColumn)->get() as $row) {
            $data = $row instanceof Arrayable ? $row->toArray() : (array) $row;
            $events[] = Event::fromArray($this->map($data), $timeZone);
        }

        return $events;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function map(array $row): array
    {
        $data = [];

        foreach ($this->fields as $property => $column) {
            if ($column !== null && array_key_exists($column, $row)) {
                $data[$property] = $row[$column];
            }
        }

        return $data;
    }
}
