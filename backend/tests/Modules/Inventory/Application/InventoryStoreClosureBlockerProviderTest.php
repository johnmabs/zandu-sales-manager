<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\InventoryStoreClosureBlockerProvider;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockTransfer\StockTransferRepository;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class InventoryStoreClosureBlockerProviderTest extends TestCase
{
    public function testInTransitTransferAndRemainingStockBothBlockClosure(): void
    {
        $ids = new SymfonyUuidFactory();
        $organizationId = OrganizationId::fromString('0199f500-0000-7000-8000-000000000001', $ids);
        $storeId = StoreId::fromString('0199f500-0000-7000-8000-000000000002', $ids);
        $stock = Stock::reconstitute(StockId::fromString('0199f500-0000-7000-8000-000000000003', $ids), $organizationId, $storeId, ProductId::fromString('0199f500-0000-7000-8000-000000000004', $ids), new StockQuantity(Quantity::fromString('2', new BrickDecimalFactory())), true, new DateTimeImmutable('2026-08-31T12:00:00Z'), ActorId::fromString('0199f500-0000-7000-8000-000000000005', $ids), 1);
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('findByStore')->with($organizationId, $storeId)->willReturn([$stock]);
        $transfers = $this->createMock(StockTransferRepository::class);
        $transfers->expects(self::once())->method('hasInTransitForStore')->with($organizationId, $storeId)->willReturn(true);

        self::assertSame(['STOCK_TRANSFER_IN_TRANSIT', 'STOCK_REMAINING'], (new InventoryStoreClosureBlockerProvider($stocks, $transfers))->blockers($organizationId, $storeId));
    }
}
