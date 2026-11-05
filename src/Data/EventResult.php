<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

use JsonSerializable;

/**
 * Events for one date range, in the standard Celerik envelope:
 *
 *     {"data": [{"id", "title", "start", "end", "allDay"...}], "meta": {"start", "end", "count"}}
 */
final class EventResult implements JsonSerializable
{
    /**
     * @param list<Event> $events
     */
    public function __construct(
        public readonly array $events,
        public readonly DateRange $range,
    ) {
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (Event $event): array => $event->toArray(), $this->events),
            'meta' => [
                'start' => $this->range->start->format(DATE_ATOM),
                'end' => $this->range->end->format(DATE_ATOM),
                'count' => count($this->events),
            ],
        ];
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
