<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\InventoryCosting\Domain\ValuationMovement;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\StockValuationMovementRepository;

final class StockValuationMovementRepositoryContractTest extends TestCase
{
    public function testItExposesAppendOnlyTenantScopedOperations(): void
    {
        $methods = array_map(
            static fn($method): string => $method->getName(),
            (new ReflectionClass(StockValuationMovementRepository::class))->getMethods(),
        );

        self::assertSame(['append', 'appendOnce', 'findByValuation', 'findByStore'], $methods);
    }
}
