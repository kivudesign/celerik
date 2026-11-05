<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

use Celerik\Ui\Support\Dates;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * The visible date range FullCalendar asks events for: `?start=...&end=...` (end exclusive).
 */
final class DateRange
{
    /** Longest range accepted from a request, to keep event queries bounded. */
    public const MAX_DAYS = 400;

    public function __construct(
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {
        if ($end <= $start) {
            throw new InvalidArgumentException('Date range end must be after its start.');
        }

        if ($start->diff($end)->days > self::MAX_DAYS) {
            throw new InvalidArgumentException(sprintf('Date range must not exceed %d days.', self::MAX_DAYS));
        }
    }

    /**
     * @param array<string, mixed> $query Usually `$_GET` or `$request->query()`.
     */
    public static function fromQuery(array $query, ?DateTimeZone $timeZone = null): self
    {
        if (!isset($query['start'], $query['end'])) {
            throw new InvalidArgumentException('Both "start" and "end" are required.');
        }

        return new self(Dates::parse($query['start'], $timeZone), Dates::parse($query['end'], $timeZone));
    }

    /**
     * Whether an event is at least partly inside the range.
     */
    public function overlaps(Event $event): bool
    {
        if ($event->start >= $this->end) {
            return false;
        }

        return $event->end === null ? $event->start >= $this->start : $event->end > $this->start;
    }
}
