<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Application;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\{ConsumeStockForSale,InventoryStockConsumer,StockConsumptionResult};
use Zandu\Modules\Sales\Application\Contract\SaleProductDescriptor;
use Zandu\Modules\Sales\Application\InventoryConsumptionService;
use Zandu\Modules\Sales\Domain\Sale;
use Zandu\SharedKernel\Identity\SaleId;

final class InventoryConsumptionServiceTest extends TestCase
{
    public function testUntrackedProductDoesNotCallInventory(): void
    {
        $consumer = new class implements InventoryStockConsumer {
            public int $calls = 0;
            public function consumeStockForSale(ConsumeStockForSale $request): StockConsumptionResult
            {
                ++$this->calls;
                return new StockConsumptionResult($request->saleId);
            }
        };
        $result = (new InventoryConsumptionService($consumer))->consume($this->createSale(), []);
        self::assertNull($result);
        self::assertSame(0, $consumer->calls);
    }

    private function createSale(): Sale
    {
        $f = new \Zandu\Platform\Identity\SymfonyUuidFactory();
        return Sale::create(SaleId::fromString('0198ec01-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Identity\OrganizationId::fromString('0198ec02-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Identity\StoreId::fromString('0198ec03-1111-7111-8111-111111111111', $f), 'XAF', \Zandu\SharedKernel\Money\Money::fromString('0', \Zandu\SharedKernel\Money\Currency::fromCode('XAF'), new \Zandu\Platform\Decimal\BrickDecimalFactory()), new \Zandu\SharedKernel\Context\ActorContext(\Zandu\SharedKernel\Identity\ActorId::fromString('0198ec04-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Identity\OrganizationId::fromString('0198ec02-1111-7111-8111-111111111111', $f), \Zandu\SharedKernel\Context\ActorType::User, \Zandu\SharedKernel\Messaging\CorrelationId::fromString('0198ec05-1111-7111-8111-111111111111', $f), new \DateTimeImmutable()), new \DateTimeImmutable());
    }
}
