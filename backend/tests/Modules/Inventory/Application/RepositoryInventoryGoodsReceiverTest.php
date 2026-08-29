<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\ReceiveSupplierGoods;
use Zandu\Modules\Inventory\Application\RepositoryInventoryGoodsReceiver;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptId, OrganizationId, ProductId, StockId, StoreId, SupplierId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class RepositoryInventoryGoodsReceiverTest extends TestCase
{
    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItReceivesAndValuesSupplierGoodsOnce(): void
    {
        $stock = $this->stock();
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $saved): bool => '15' === $saved->quantityOnHand()->toString()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(fn(StockMovement $movement): bool => StockMovementType::PurchaseReceipt === $movement->type()
            && '5' === $movement->quantity()->toString()
            && '10' === $movement->previousQuantity()->toString()
            && '15' === $movement->resultingQuantity()->toString()
            && 'GOODS_RECEIPT' === $movement->source()->type()
            && $this->receiptId()->toString() === $movement->source()->referenceId()))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::PurchaseReceipt === $movement->type
            && '5' === $movement->quantity->toString()
            && '7000' === $movement->incomingUnitCost?->toString()
            && $this->receiptId()->toString() === $movement->reason))->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => new ValuedInventoryMovement(
                $movement->stockId,
                $movement->stockMovementId,
                $movement->quantity,
                $this->money('7000'),
                $this->money('35000'),
                3,
                $movement->occurredAt,
            ));

        $result = $this->receiver($stocks, $movements, $costing)->receive($this->request());

        self::assertFalse($result->alreadyReceived);
        self::assertSame(1, $result::CONTRACT_VERSION);
        self::assertCount(1, $result->items);
        self::assertSame('15', $result->items[0]->resultingQuantity->toString());
    }

    public function testReplayDoesNotMutateStockOrCostingAgain(): void
    {
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($this->stock());
        $stocks->expects(self::never())->method('save');
        $movements = $this->createStub(StockMovementRepository::class);
        $movements->method('appendOnce')->willReturn(false);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::never())->method('value');

        $result = $this->receiver($stocks, $movements, $costing)->receive($this->request());

        self::assertTrue($result->alreadyReceived);
        self::assertSame([], $result->items);
    }

    private function receiver(StockRepository $stocks, StockMovementRepository $movements, InventoryMovementValuer $costing): RepositoryInventoryGoodsReceiver
    {
        return new RepositoryInventoryGoodsReceiver($stocks, $movements, $costing, new SymfonyUuidV7Generator());
    }

    private function request(): ReceiveSupplierGoods
    {
        return new ReceiveSupplierGoods(
            $this->organization(),
            $this->storeId(),
            $this->receiptId(),
            SupplierId::fromString('019a3500-0000-7000-8000-000000000008', $this->uuids),
            [['productId' => $this->productId(), 'baseQuantity' => $this->quantity('5'), 'inventoryUnitCost' => $this->money('7000')]],
            new DateTimeImmutable('2026-08-29T11:00:00Z'),
            $this->actor(),
        );
    }

    private function stock(): Stock
    {
        return Stock::reconstitute(
            StockId::fromString('019a3500-0000-7000-8000-000000000001', $this->uuids),
            $this->organization(),
            $this->storeId(),
            $this->productId(),
            new StockQuantity($this->quantity('10')),
            true,
            new DateTimeImmutable('2026-08-29T08:00:00Z'),
            $this->actor()->actorId(),
            2,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('019a3500-0000-7000-8000-000000000002', $this->uuids),
            $this->organization(),
            ActorType::User,
            CorrelationId::fromString('019a3500-0000-7000-8000-000000000003', $this->uuids),
            new DateTimeImmutable('2026-08-29T08:00:00Z'),
        );
    }

    private function organization(): OrganizationId
    {
        return OrganizationId::fromString('019a3500-0000-7000-8000-000000000004', $this->uuids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('019a3500-0000-7000-8000-000000000005', $this->uuids);
    }

    private function productId(): ProductId
    {
        return ProductId::fromString('019a3500-0000-7000-8000-000000000006', $this->uuids);
    }

    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('019a3500-0000-7000-8000-000000000007', $this->uuids);
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
