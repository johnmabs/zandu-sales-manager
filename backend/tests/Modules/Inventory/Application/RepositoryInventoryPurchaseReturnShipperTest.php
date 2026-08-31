<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\PurchaseReturnStockUnavailable;
use Zandu\Modules\Inventory\Application\Contract\ShipPurchaseReturnStock;
use Zandu\Modules\Inventory\Application\RepositoryInventoryPurchaseReturnShipper;
use Zandu\Modules\Inventory\Domain\Stock\Stock;
use Zandu\Modules\Inventory\Domain\Stock\StockQuantity;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovement;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementRepository;
use Zandu\Modules\Inventory\Domain\StockMovement\StockMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryCostingMovementType;
use Zandu\Modules\InventoryCosting\Application\Contract\InventoryMovementValuer;
use Zandu\Modules\InventoryCosting\Application\Contract\ValuedInventoryMovement;
use Zandu\Modules\InventoryCosting\Application\Contract\ValueInventoryMovement;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\Platform\Identity\SymfonyUuidV7Generator;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Context\ActorType;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\PurchaseReturnId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final class RepositoryInventoryPurchaseReturnShipperTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItShipsAndValuesReturnedStockAtTheCurrentAverageCost(): void
    {
        $stock = $this->stock('10');
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(
            static fn(Stock $saved): bool => '6' === $saved->quantityOnHand()->toString(),
        ));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(
            fn(StockMovement $movement): bool => StockMovementType::PurchaseReturn === $movement->type()
                && '4' === $movement->quantity()->toString()
                && 'PURCHASE_RETURN' === $movement->source()->type()
                && $this->returnId()->toString() === $movement->source()->referenceId(),
        ))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(
            fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::PurchaseReturn === $movement->type
                && null === $movement->incomingUnitCost
                && '10' === $movement->previousQuantity->toString()
                && '6' === $movement->resultingQuantity->toString()
                && $this->returnId()->toString() === $movement->reason,
        ))->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => new ValuedInventoryMovement(
            $movement->stockId,
            $movement->stockMovementId,
            $movement->quantity,
            $this->money('5000'),
            $this->money('20000'),
            3,
            $movement->occurredAt,
        ));

        self::assertSame(1, $this->shipper($stocks, $movements, $costing)->ship($this->request('4')));
    }

    public function testItRejectsInsufficientStockBeforeWritingMovementOrCosting(): void
    {
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($this->stock('3'));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::never())->method('appendOnce');
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::never())->method('value');

        $this->expectException(PurchaseReturnStockUnavailable::class);
        $this->shipper($stocks, $movements, $costing)->ship($this->request('4'));
    }

    private function shipper(StockRepository $stocks, StockMovementRepository $movements, InventoryMovementValuer $costing): RepositoryInventoryPurchaseReturnShipper
    {
        return new RepositoryInventoryPurchaseReturnShipper($stocks, $movements, $costing, new SymfonyUuidV7Generator());
    }

    private function request(string $quantity): ShipPurchaseReturnStock
    {
        return new ShipPurchaseReturnStock(
            $this->organizationId(),
            $this->storeId(),
            $this->returnId(),
            [['productId' => $this->productId(), 'baseQuantity' => $this->quantity($quantity)]],
            'Damaged delivery',
            new DateTimeImmutable('2026-08-31T10:00:00Z'),
            $this->actor(),
        );
    }

    private function stock(string $quantity): Stock
    {
        return Stock::reconstitute(
            StockId::fromString('0198e500-0000-7000-8000-000000000001', $this->ids),
            $this->organizationId(),
            $this->storeId(),
            $this->productId(),
            new StockQuantity($this->quantity($quantity)),
            true,
            new DateTimeImmutable('2026-08-29T08:00:00Z'),
            $this->actor()->actorId(),
            2,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString('0198e500-0000-7000-8000-000000000002', $this->ids),
            $this->organizationId(),
            ActorType::User,
            CorrelationId::fromString('0198e500-0000-7000-8000-000000000003', $this->ids),
            new DateTimeImmutable('2026-08-31T10:00:00Z'),
        );
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e500-0000-7000-8000-000000000004', $this->ids);
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('0198e500-0000-7000-8000-000000000005', $this->ids);
    }

    private function productId(): ProductId
    {
        return ProductId::fromString('0198e500-0000-7000-8000-000000000006', $this->ids);
    }

    private function returnId(): PurchaseReturnId
    {
        return PurchaseReturnId::fromString('0198e500-0000-7000-8000-000000000007', $this->ids);
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
