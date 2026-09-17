<?php

declare(strict_types=1);

namespace Zandu\Tests\Platform\Api\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Zandu\Platform\Api\State\CursorPaginator;
use Zandu\Platform\Api\State\OpaqueCursor;
use Zandu\Platform\Api\State\PaginatedCollectionProvider;

final class PaginatedCollectionProviderTest extends TestCase
{
    public function testItAppliesTheCommonPageAndLimitToCollectionArrays(): void
    {
        $items = [new \stdClass(), new \stdClass(), new \stdClass(), new \stdClass(), new \stdClass()];
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($items),
            new Pagination([
                'items_per_page' => 30,
                'client_items_per_page' => true,
                'items_per_page_parameter_name' => 'limit',
                'maximum_items_per_page' => 100,
            ]),
            100,
        );

        $result = $provider->provide(
            new GetCollection(),
            context: ['filters' => ['page' => '2', 'limit' => '2']],
        );

        self::assertInstanceOf(ArrayPaginator::class, $result);
        self::assertSame(2.0, $result->getCurrentPage());
        self::assertSame(3.0, $result->getLastPage());
        self::assertSame(5.0, $result->getTotalItems());
        self::assertSame([$items[2], $items[3]], array_values(iterator_to_array($result)));
    }

    public function testItCapsTheClientLimit(): void
    {
        $items = array_map(static fn(int $index): object => (object) ['index' => $index], range(1, 250));
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($items),
            new Pagination([
                'items_per_page' => 30,
                'client_items_per_page' => true,
                'items_per_page_parameter_name' => 'limit',
                'maximum_items_per_page' => 1000,
            ]),
            100,
        );

        $result = $provider->provide(new GetCollection(), context: ['filters' => ['page' => '2', 'limit' => '1000']]);

        self::assertInstanceOf(ArrayPaginator::class, $result);
        self::assertSame(100.0, $result->getItemsPerPage());
        self::assertCount(100, iterator_to_array($result));
        self::assertSame($items[100], array_values(iterator_to_array($result))[0]);
    }

    public function testItUsesAnOpaqueCursorWithoutDuplicates(): void
    {
        $items = [
            (object) ['id' => '0198c728-0003-7000-8000-000000000003'],
            (object) ['id' => '0198c728-0001-7000-8000-000000000001'],
            (object) ['id' => '0198c728-0002-7000-8000-000000000002'],
        ];
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($items),
            new Pagination([
                'items_per_page' => 30,
                'client_items_per_page' => true,
                'items_per_page_parameter_name' => 'limit',
                'maximum_items_per_page' => 100,
            ]),
            100,
        );
        $operation = new GetCollection(extraProperties: [
            PaginatedCollectionProvider::CURSOR_PAGINATION => true,
            'zandu_cursor_direction' => 'asc',
        ]);
        $firstRequest = new Request();

        $firstPage = $provider->provide($operation, context: [
            'filters' => ['limit' => '2'],
            'request' => $firstRequest,
        ]);

        self::assertInstanceOf(CursorPaginator::class, $firstPage);
        self::assertTrue($firstPage->hasNextPage());
        self::assertSame([$items[1], $items[2]], array_values(iterator_to_array($firstPage)));
        $cursor = $firstRequest->attributes->get(PaginatedCollectionProvider::NEXT_CURSOR_ATTRIBUTE);
        self::assertIsString($cursor);
        self::assertSame($items[2]->id, OpaqueCursor::decode($cursor));

        $secondRequest = new Request();
        $secondPage = $provider->provide($operation, context: [
            'filters' => ['limit' => '2', 'cursor' => $cursor],
            'request' => $secondRequest,
        ]);

        self::assertInstanceOf(CursorPaginator::class, $secondPage);
        self::assertFalse($secondPage->hasNextPage());
        self::assertSame([$items[0]], array_values(iterator_to_array($secondPage)));
        self::assertNull($secondRequest->attributes->get(PaginatedCollectionProvider::NEXT_CURSOR_ATTRIBUTE));
    }

    public function testItSupportsNewestFirstCursorPagination(): void
    {
        $items = [
            (object) ['id' => '0198c728-0001-7000-8000-000000000001'],
            (object) ['id' => '0198c728-0002-7000-8000-000000000002'],
        ];
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($items),
            new Pagination(['items_per_page' => 1]),
            100,
        );

        $result = $provider->provide(new GetCollection(extraProperties: [
            PaginatedCollectionProvider::CURSOR_PAGINATION => true,
            'zandu_cursor_direction' => 'desc',
        ]));

        self::assertInstanceOf(CursorPaginator::class, $result);
        self::assertSame([$items[1]], array_values(iterator_to_array($result)));
    }

    public function testItCapsTheClientLimitForCursorPagination(): void
    {
        $items = [];
        for ($index = 1; $index <= 150; ++$index) {
            $items[] = (object) ['id' => sprintf('0198c728-%04d-7000-8000-%012d', $index, $index)];
        }
        $request = new Request();
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($items),
            new Pagination([
                'items_per_page' => 30,
                'client_items_per_page' => true,
                'items_per_page_parameter_name' => 'limit',
                'maximum_items_per_page' => 1000,
            ]),
            100,
        );

        $result = $provider->provide(new GetCollection(extraProperties: [
            PaginatedCollectionProvider::CURSOR_PAGINATION => true,
        ]), context: [
            'filters' => ['limit' => '1000'],
            'request' => $request,
        ]);

        self::assertInstanceOf(CursorPaginator::class, $result);
        self::assertSame(100.0, $result->getItemsPerPage());
        self::assertCount(100, iterator_to_array($result));
        self::assertTrue($result->hasNextPage());
        self::assertIsString($request->attributes->get(PaginatedCollectionProvider::NEXT_CURSOR_ATTRIBUTE));
    }

    public function testItLeavesItemOperationsUntouched(): void
    {
        $item = new \stdClass();
        $provider = new PaginatedCollectionProvider(
            $this->providerReturning($item),
            new Pagination(),
            100,
        );

        self::assertSame($item, $provider->provide(new Get()));
    }

    /**
     * @param object|list<object>|null $data
     *
     * @return ProviderInterface<object>
     */
    private function providerReturning(object|array|null $data): ProviderInterface
    {
        $provider = $this->createStub(ProviderInterface::class);
        $provider->method('provide')->willReturn($data);

        return $provider;
    }
}
