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
 * as-is, so `a.name` works after `->join('u.author', 'a')`. Select scalar fields rather than
 * whole entities: rows are returned with getArrayResult().
 */
final class DoctrineDataSource implements DataSourceInterface
{
    /**
     * @param list<string> $searchable Search fields used when the request does not specify any.
     */
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly array $searchable = [],
    ) {
    }

    public function fetch(DataRequest $request): DataResult
    {
        $alias = $this->queryBuilder->getRootAliases()[0];

        $total = $this->count($this->queryBuilder, $alias);
        $query = clone $this->queryBuilder;

        $fields = $request->searchFields !== [] ? $request->searchFields : $this->searchable;

        if ($request->search !== null && $fields !== []) {
            $search = $query->expr()->orX();
            foreach ($fields as $field) {
                $search->add($query->expr()->like('LOWER(' . $this->path($alias, $field) . ')', ':celerik_search'));
            }
            $query->andWhere($search)
                ->setParameter('celerik_search', '%' . addcslashes(mb_strtolower($request->search), '%_\\') . '%');
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
        return str_contains($field, '.') ? $field : $alias . '.' . $field;
    }
}
