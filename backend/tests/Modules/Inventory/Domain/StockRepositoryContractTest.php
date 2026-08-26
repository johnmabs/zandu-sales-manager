<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;

final class StockRepositoryContractTest extends TestCase
{
    public function testContractExposesOnlyThePositionAndIdentityOperations(): void
    {
        $methods = array_map(static fn($method): string => $method->getName(), (new ReflectionClass(StockRepository::class))->getMethods());
        self::assertSame(['save', 'get', 'find', 'getById', 'findByStore', 'decreaseIfAvailable'], $methods);
    }
}
