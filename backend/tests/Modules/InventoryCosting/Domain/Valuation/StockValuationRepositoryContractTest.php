<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain\Valuation;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Modules\InventoryCosting\Domain\Valuation\StockValuationRepository;

final class StockValuationRepositoryContractTest extends TestCase
{
    public function testItExposesOnlyTenantScopedPositionOperations(): void
    {
        $methods = array_map(
            static fn($method): string => $method->getName(),
            (new ReflectionClass(StockValuationRepository::class))->getMethods(),
        );

        self::assertSame(['save', 'findByStock', 'getByStock', 'getByStockForUpdate', 'findByStore'], $methods);
    }
}
