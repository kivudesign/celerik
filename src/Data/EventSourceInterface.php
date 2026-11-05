<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

/**
 * A source of calendar events for the Scheduler.
 */
interface EventSourceInterface
{
    /**
     * Events that overlap the range (start inclusive, end exclusive).
     *
     * @return iterable<Event>
     */
    public function between(DateRange $range): iterable;
}
