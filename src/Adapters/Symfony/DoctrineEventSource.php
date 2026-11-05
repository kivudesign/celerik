<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Symfony;

use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Data\DateRange;
use Celerik\Ui\Data\Event;
use Celerik\Ui\Data\EventSourceInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

/**
 * Event source for a Doctrine ORM query builder, e.g.
 * `new DoctrineEventSource($repository->createQueryBuilder('m'), ['start' => 'startsAt', 'end' => 'endsAt'])`.
 *
 * Field values are entity property names on the root alias. Only rows overlapping the
 * requested range are queried; rows are read with getArrayResult().
 */
final class DoctrineEventSource implements EventSourceInterface
{
    private const DEFAULT_FIELDS = [
        'id' => 'id',
        'title' => 'title',
        'start' => 'start',
        'end' => 'end',
        'allDay' => 'allDay',
        'color' => null,
        'url' => null,
    ];

    /** @var array<string, string|null> */
    private readonly array $fields;

    /**
     * @param array<string, string|null> $fields Event property => entity property (null = not stored).
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        array $fields = [],
    ) {
        $fields = array_replace(self::DEFAULT_FIELDS, $fields);

        foreach ($fields as $property => $field) {
            if (!array_key_exists($property, self::DEFAULT_FIELDS)) {
                throw new InvalidArgumentException(sprintf('Unknown event property "%s".', $property));
            }
            if ($field !== null && (!DataRequest::isValidField($field) || str_contains($field, '.'))) {
                throw new InvalidArgumentException(sprintf('Invalid entity property "%s".', $field));
            }
        }

        if ($fields['title'] === null || $fields['start'] === null) {
            throw new InvalidArgumentException('The title and start properties are required.');
        }

        $this->fields = $fields;
    }

    public function between(DateRange $range): iterable
    {
        $query = clone $this->queryBuilder;
        $alias = $query->getRootAliases()[0];
        $start = $alias . '.' . $this->fields['start'];

        $query->andWhere($start . ' < :celerik_range_end')->setParameter('celerik_range_end', $range->end);

        if ($this->fields['end'] === null) {
            $query->andWhere($start . ' >= :celerik_range_start');
        } else {
            $end = $alias . '.' . $this->fields['end'];
            // Ends after the range starts, or has no end and starts inside the range.
            $query->andWhere(sprintf(
                '(%2$s > :celerik_range_start OR (%2$s IS NULL AND %1$s >= :celerik_range_start))',
                $start,
                $end,
            ));
        }

        $query->setParameter('celerik_range_start', $range->start)->addOrderBy($start);

        $events = [];
        foreach ($query->getQuery()->getArrayResult() as $row) {
            $events[] = Event::fromArray($this->map($row));
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

        foreach ($this->fields as $property => $field) {
            if ($field !== null && array_key_exists($field, $row)) {
                $data[$property] = $row[$field];
            }
        }

        return $data;
    }
}
