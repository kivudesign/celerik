<?php

declare(strict_types=1);

namespace Celerik\Ui\Adapters\Symfony;

use Celerik\Ui\Data\DataRequest;
use Celerik\Ui\Data\DataResult;
use Celerik\Ui\Data\DataSourceInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Data source for a Doctrine ORM query builder, e.g.
 * `new DoctrineDataSource($repository->createQueryBuilder('u')->select('u.id, u.name, u.email'))`.
 *
 * Plain fields resolve against the root alias (`name` => `u.name`); dotted fields are used
 * as-is (`a.name`). For joined columns shown in a Grid, select them under an alias and map
 * that alias to its DQL path, so the same field name sorts, searches and appears in the rows:
 *
 *     $qb->select('b.id, b.title, a.name AS author')->join('b.author', 'a');
 *     new DoctrineDataSource($qb, fieldMap: ['author' => 'a.name']);
 *
 * Select scalar fields rather than whole entities: rows are returned with getArrayResult().
 */
final class DoctrineDataSource implements DataSourceInterface
{
    /**
     * @param list<string> $searchable Search fields used when the request does not specify any.
     * @param array<string, string> $fieldMap Public field name => DQL path (e.g. 'author' => 'a.name').
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $searchable = [],
        private readonly array $fieldMap = [],
    ) {
    }

    public function fetch(DataRequest $request): DataResult
    {
        $alias = $this->queryBuilder->getRootAliases()[0];

        $total = $this->count($this->queryBuilder, $alias);
        $query = clone $this->queryBuilder;

        $fields = $request->searchFields !== [] ? $request->searchFields : $this->searchable;

        $pattern = $request->likePattern();

        if ($pattern !== null && $fields !== []) {
            $search = $query->expr()->orX();
            foreach ($fields as $field) {
                $search->add(sprintf(
                    "LOWER(%s) LIKE :celerik_search ESCAPE '%s'",
                    $this->path($alias, $field),
                    DataRequest::LIKE_ESCAPE,
                ));
            }
            $query->andWhere($search)->setParameter('celerik_search', mb_strtolower($pattern));
        }

        $index = 0;
        foreach ($request->filters as $field => $value) {
            $parameter = 'celerik_filter_' . $index++;
            $query->andWhere($this->path($alias, $field) . ' = :' . $parameter)->setParameter($parameter, $value);
        }

        $filtered = $this->count($query, $alias);

        foreach ($request->sort as $rule) {
            $query->addOrderBy($this->path($alias, $rule['field']), $rule['direction']);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $query->setFirstResult($request->offset())
            ->setMaxResults($request->perPage)
            ->getQuery()
            ->getArrayResult();

        return new DataResult($rows, $total, $filtered, $request);
    }

    private function count(QueryBuilder $queryBuilder, string $alias): int
    {
        return (int) (clone $queryBuilder)
            ->select('COUNT(DISTINCT ' . $alias . ')')
            ->resetDQLPart('orderBy')
            ->setFirstResult(0)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function path(string $alias, string $field): string
    {
        if (isset($this->fieldMap[$field])) {
            return $this->fieldMap[$field];
        }

        return str_contains($field, '.') ? $field : $alias . '.' . $field;
    }
}
