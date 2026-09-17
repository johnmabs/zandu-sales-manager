<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\State;

use ApiPlatform\State\Pagination\HasNextPagePaginatorInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;

/**
 * @template T of object
 *
 * @implements \IteratorAggregate<mixed, T>
 * @implements PartialPaginatorInterface<T>
 */
final readonly class CursorPaginator implements \IteratorAggregate, PartialPaginatorInterface, HasNextPagePaginatorInterface
{
    /** @param list<T> $items */
    public function __construct(
        private array $items,
        private int $limit,
        private bool $hasNextPage,
    ) {}

    public function getCurrentPage(): float
    {
        return 1.0;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->limit;
    }

    public function hasNextPage(): bool
    {
        return $this->hasNextPage;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return \Traversable<mixed, T> */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
