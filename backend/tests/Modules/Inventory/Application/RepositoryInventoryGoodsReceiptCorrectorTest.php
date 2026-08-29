<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\ApplyGoodsReceiptCorrection;
use Zandu\Modules\Inventory\Application\RepositoryInventoryGoodsReceiptCorrector;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptCorrectionId, OrganizationId, ProductId, StockId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class RepositoryInventoryGoodsReceiptCorrectorTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItAppliesAndValuesAnIncomingCorrectionAtOriginalReceiptCost(): void
    {
        $stock = $this->stock();
        $stocks = $this->createMock(StockRepository::class);
        $stocks->method('getForUpdate')->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $saved): bool => '13' === $saved->quantityOnHand()->toString()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(fn(StockMovement $movement): bool => StockMovementType::GoodsReceiptCorrectionIn === $movement->type() && 'GOODS_RECEIPT_CORRECTION' === $movement->source()->type() && $this->correctionId()->toString() === $movement->source()->referenceId()))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(static fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::GoodsReceiptCorrectionIn === $movement->type && '3' === $movement->quantity->toString() && '5000' === $movement->incomingUnitCost?->toString()))->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => $this->valued($movement));

        $applied = $this->corrector($stocks, $movements, $costing)->apply($this->request('3'));

        self::assertSame(1, $applied);
    }

    public function testItAppliesOutgoingAtCurrentAverageAndSkipsZeroDifference(): void
    {
        $stock = $this->stock();
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $saved): bool => '6' === $saved->quantityOnHand()->toString()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(static fn(StockMovement $movement): bool => StockMovementType::GoodsReceiptCorrectionOut === $movement->type() && '4' === $movement->quantity()->toString()))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(static fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::GoodsReceiptCorrectionOut === $movement->type && null === $movement->incomingUnitCost))->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => $this->valued($movement));
        $request = $this->request('-4');
        $request = new ApplyGoodsReceiptCorrection($request->organizationId, $request->storeId, $request->correctionId, [...$request->items, ['productId' => ProductId::fromString('0198e000-0000-7000-8000-000000000010', $this->ids), 'difference' => $this->quantity('0'), 'incomingUnitCost' => $this->money('5000')]], $request->reason, $request->occurredAt, $request->actorContext);

        self::assertSame(1, $this->corrector($stocks, $movements, $costing)->apply($request));
    }

    private function corrector(StockRepository $stocks, StockMovementRepository $movements, InventoryMovementValuer $costing): RepositoryInventoryGoodsReceiptCorrector
    {
        return new RepositoryInventoryGoodsReceiptCorrector($stocks, $movements, $costing, new SymfonyUuidV7Generator(), $this->decimals);
    }

    private function valued(ValueInventoryMovement $movement): ValuedInventoryMovement
    {
        return new ValuedInventoryMovement($movement->stockId, $movement->stockMovementId, $movement->quantity, $this->money('5000'), $this->money('5000'), 2, $movement->occurredAt);
    }

    private function request(string $difference): ApplyGoodsReceiptCorrection
    {
        return new ApplyGoodsReceiptCorrection($this->organizationId(), $this->storeId(), $this->correctionId(), [['productId' => $this->productId(), 'difference' => $this->quantity($difference), 'incomingUnitCost' => $this->money('5000')]], 'Recount correction', new DateTimeImmutable('2026-08-29T17:00:00Z'), $this->actor());
    }

    private function stock(): Stock
    {
        return Stock::reconstitute(StockId::fromString('0198e000-0000-7000-8000-000000000001', $this->ids), $this->organizationId(), $this->storeId(), $this->productId(), new StockQuantity($this->quantity('10')), true, new DateTimeImmutable('2026-08-29T08:00:00Z'), $this->actor()->actorId(), 2);
    }
    private function actor(): ActorContext
    {
        return new ActorContext(ActorId::fromString('0198e000-0000-7000-8000-000000000002', $this->ids), $this->organizationId(), ActorType::User, CorrelationId::fromString('0198e000-0000-7000-8000-000000000003', $this->ids), new DateTimeImmutable('2026-08-29T08:00:00Z'));
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198e000-0000-7000-8000-000000000004', $this->ids);
    }
    private function storeId(): StoreId
    {
        return StoreId::fromString('0198e000-0000-7000-8000-000000000005', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0198e000-0000-7000-8000-000000000006', $this->ids);
    }
    private function correctionId(): GoodsReceiptCorrectionId
    {
        return GoodsReceiptCorrectionId::fromString('0198e000-0000-7000-8000-000000000007', $this->ids);
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
