<?php

declare(strict_types=1);

namespace Zandu\Platform\Api\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;

/** @implements ProviderInterface<object> */
final readonly class PaginatedCollectionProvider implements ProviderInterface
{
    public const string CURSOR_PAGINATION = 'zandu_cursor_pagination';
    public const string NEXT_CURSOR_ATTRIBUTE = '_zandu_next_cursor';

    public function __construct(
        /** @var ProviderInterface<object> */
        private ProviderInterface $decorated,
        private Pagination $pagination,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (!$operation instanceof CollectionOperationInterface || !is_array($data) || !$this->pagination->isEnabled($operation, $context)) {
            return $data;
        }

        [, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        if (true === ($operation->getExtraProperties()[self::CURSOR_PAGINATION] ?? false)) {
            return $this->cursorPage(array_values($data), $operation, $context, $limit);
        }

        return new ArrayPaginator($data, $offset, $limit);
    }

    /**
     * @param list<object> $data
     * @param array<string, mixed> $context
     *
     * @return CursorPaginator<object>
     */
    private function cursorPage(array $data, Operation $operation, array $context, int $limit): CursorPaginator
    {
        if ($limit < 1) {
            throw new \ApiPlatform\Metadata\Exception\InvalidArgumentException('The pagination limit must be at least 1.');
        }

        $direction = $operation->getExtraProperties()['zandu_cursor_direction'] ?? 'asc';
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new \LogicException('Cursor pagination direction must be asc or desc.');
        }

        usort($data, static function (object $left, object $right) use ($direction): int {
            $comparison = strcmp(self::id($left), self::id($right));

            return 'asc' === $direction ? $comparison : -$comparison;
        });

        $filters = $context['filters'] ?? [];
        $rawCursor = is_array($filters) ? ($filters['cursor'] ?? null) : null;
        if (null !== $rawCursor) {
            if (!is_string($rawCursor) || '' === $rawCursor) {
                throw new \ApiPlatform\Metadata\Exception\InvalidArgumentException('The pagination cursor is invalid.');
            }
            $cursorId = OpaqueCursor::decode($rawCursor);
            $data = array_values(array_filter(
                $data,
                static fn(object $item): bool => 'asc' === $direction
                    ? strcmp(self::id($item), $cursorId) > 0
                    : strcmp(self::id($item), $cursorId) < 0,
            ));
        }

        $hasNextPage = count($data) > $limit;
        $items = array_slice($data, 0, $limit);
        $nextCursor = $hasNextPage && [] !== $items ? OpaqueCursor::encode(self::id($items[array_key_last($items)])) : null;
        $request = $context['request'] ?? null;
        if ($request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request->attributes->set(self::NEXT_CURSOR_ATTRIBUTE, $nextCursor);
        }

        return new CursorPaginator($items, $limit, $hasNextPage);
    }

    private static function id(object $item): string
    {
        if (!isset($item->id) || !is_string($item->id) || '' === $item->id) {
            throw new \LogicException('Cursor-paginated resources must expose a non-empty string id.');
        }

        return $item->id;
    }
}
