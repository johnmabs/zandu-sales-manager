<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\RestockSaleReturn;
use Zandu\Modules\Inventory\Application\RepositoryInventoryStockRestocker;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, ReturnSaleId, StockId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class RepositoryInventoryStockRestockerTest extends TestCase
{
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItAggregatesAProductAndRestocksItOnce(): void
    {
        $stock = $this->stock();
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->with($this->organization(), $this->storeId(), $this->productId())->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $saved): bool => '13' === $saved->quantityOnHand()->toString() && 3 === $saved->version()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(fn(StockMovement $movement): bool => StockMovementType::SaleReturn === $movement->type()
            && '3' === $movement->quantity()->toString()
            && '10' === $movement->previousQuantity()->toString()
            && '13' === $movement->resultingQuantity()->toString()
            && 'RETURN' === $movement->source()->type()
            && $this->returnId()->toString() === $movement->source()->referenceId()))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::SaleReturn === $movement->type
            && '3' === $movement->quantity->toString()
            && '466.666666666667' === $movement->incomingUnitCost?->toString()
            && $this->returnId()->toString() === $movement->reason))->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => new ValuedInventoryMovement(
                $movement->stockId,
                $movement->stockMovementId,
                $movement->quantity,
                $this->money('466.666666666667'),
                $this->money('1400'),
                3,
                $movement->occurredAt,
            ));

        $result = $this->restocker($stocks, $movements, $costing)->restockSaleReturn($this->request());

        self::assertFalse($result->alreadyRestocked);
        self::assertSame(1, $result::CONTRACT_VERSION);
        self::assertCount(1, $result->items);
        self::assertSame('3', $result->items[0]->quantity->toString());
        self::assertSame('13', $result->items[0]->resultingQuantity->toString());
    }

    public function testReplayDoesNotIncreaseStockAgain(): void
    {
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($this->stock());
        $stocks->expects(self::never())->method('save');
        $movements = $this->createStub(StockMovementRepository::class);
        $movements->method('appendOnce')->willReturn(false);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::never())->method('value');

        $result = $this->restocker($stocks, $movements, $costing)->restockSaleReturn($this->request());

        self::assertTrue($result->alreadyRestocked);
        self::assertSame([], $result->items);
    }

    private function restocker(StockRepository $stocks, StockMovementRepository $movements, InventoryMovementValuer $costing): RepositoryInventoryStockRestocker
    {
        return new RepositoryInventoryStockRestocker(
            $stocks,
            $movements,
            $costing,
            new SymfonyUuidV7Generator(),
            new FrozenClock(new DateTimeImmutable('2026-08-28T14:00:00Z')),
        );
    }

    private function request(): RestockSaleReturn
    {
        return new RestockSaleReturn(
            $this->organization(),
            $this->storeId(),
            $this->returnId(),
            [
                ['productId' => $this->productId(), 'baseQuantity' => $this->quantity('1'), 'originalUnitCost' => $this->money('400')],
                ['productId' => $this->productId(), 'baseQuantity' => $this->quantity('2'), 'originalUnitCost' => $this->money('500')],
            ],
            $this->actor(),
        );
    }

    private function stock(): Stock
    {
        return Stock::reconstitute(
            StockId::fromString('019a3400-0000-7000-8000-000000000001', $this->uuids),
            $this->organization(),
            $this->storeId(),
            $this->productId(),
            new StockQuantity($this->quantity('10')),
            true,
            new DateTimeImmutable('2026-08-28T08:00:00Z'),
            $this->actor()->actorId(),
            2,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('019a3400-0000-7000-8000-000000000002', $this->uuids),
            $this->organization(),
            ActorType::User,
            CorrelationId::fromString('019a3400-0000-7000-8000-000000000003', $this->uuids),
            new DateTimeImmutable('2026-08-28T08:00:00Z'),
        );
    }

    private function organization(): OrganizationId
    {
        return OrganizationId::fromString('019a3400-0000-7000-8000-000000000004', $this->uuids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3400-0000-7000-8000-000000000005', $this->uuids);
    }

    private function productId(): ProductId
    {
        return ProductId::fromString('019a3400-0000-7000-8000-000000000006', $this->uuids);
    }

    private function returnId(): ReturnSaleId
    {
        return ReturnSaleId::fromString('019a3400-0000-7000-8000-000000000007', $this->uuids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }

    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}
