<?php

declare(strict_types=1);

namespace Celerik\Ui\Data;

/**
 * In-memory event source for plain PHP arrays, fixtures and tests.
 */
final class ArrayEventSource implements EventSourceInterface
{
    /** @var list<Event> */
    private readonly array $events;

    /**
     * @param iterable<Event|array<string, mixed>> $events Events or arrays accepted by Event::fromArray().
     */
    public function __construct(iterable $events)
    {
        $list = [];
        foreach ($events as $event) {
            $list[] = $event instanceof Event ? $event : Event::fromArray($event);
        }

        $this->events = $list;
    }

    public function between(DateRange $range): iterable
    {
        $events = array_values(array_filter($this->events, $range->overlaps(...)));
        usort($events, static fn (Event $a, Event $b): int => $a->start <=> $b->start);

        return $events;
    }
}
