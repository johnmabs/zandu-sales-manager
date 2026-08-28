<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application\Contract;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};

final class InventoryStockConsumerContractTest extends TestCase
{
    public function testContractIsVersionedAndExposesIdempotentResult(): void
    {
        self::assertTrue(method_exists(InventoryStockConsumer::class, 'consumeStockForSale'));
        self::assertSame(3, StockConsumptionResult::CONTRACT_VERSION);
        $saleId = \Zandu\SharedKernel\Identity\SaleId::fromString('00000000-0000-7000-8000-000000000001', new \Zandu\Platform\Identity\SymfonyUuidFactory());
        $result = new StockConsumptionResult($saleId, true);
        self::assertTrue($result->alreadyConsumed);
        self::assertSame($saleId, $result->saleId);
        self::assertSame(5, (new \ReflectionClass(ConsumeStockForSale::class))->getConstructor()->getNumberOfParameters());
    }
}
