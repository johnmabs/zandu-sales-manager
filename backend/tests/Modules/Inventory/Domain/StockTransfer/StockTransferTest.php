<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain\StockTransfer;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockTransferId, StockTransferLineId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockTransferTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItCreatesADraftBetweenDistinctStoresAndAddsUniqueProducts(): void
    {
        $transfer = $this->transfer();
        $transfer->addLine(new StockTransferLine($this->lineId(), $transfer->id(), $this->productId(), $this->quantity('4')));

        self::assertSame(StockTransferStatus::Draft, $transfer->status());
        self::assertCount(1, $transfer->lines());
        self::assertSame(2, $transfer->version());
    }

    public function testItRejectsSameStoreAndDuplicateProducts(): void
    {
        $this->expectException(InventoryRuleViolation::class);
        $this->expectExceptionMessage('Stock transfer source and destination must differ.');
        StockTransfer::create($this->transferId(), $this->organizationId(), $this->sourceStoreId(), $this->sourceStoreId(), $this->actorId(), new DateTimeImmutable());
    }

    public function testItRejectsInvalidLineQuantity(): void
    {
        $this->expectException(InventoryRuleViolation::class);
        new StockTransferLine($this->lineId(), $this->transferId(), $this->productId(), $this->quantity('0'));
    }

    public function testItRejectsDuplicateProducts(): void
    {
        $transfer = $this->transfer();
        $transfer->addLine(new StockTransferLine($this->lineId(), $transfer->id(), $this->productId(), $this->quantity('1')));

        $this->expectException(InventoryRuleViolation::class);
        $transfer->addLine(new StockTransferLine(StockTransferLineId::fromString('0199f000-0000-7000-8000-000000000010', $this->ids), $transfer->id(), $this->productId(), $this->quantity('2')));
    }

    public function testItEditsRemovesAndCancelsItsDraftLifecycle(): void
    {
        $transfer = $this->transfer();
        $transfer->addLine(new StockTransferLine($this->lineId(), $transfer->id(), $this->productId(), $this->quantity('1')));
        $transfer->updateLine($this->lineId(), $this->quantity('3'));
        self::assertSame('3', $transfer->lines()[0]->requestedQuantity()->toString());
        $transfer->removeLine($this->lineId());
        self::assertCount(0, $transfer->lines());
        $transfer->cancel($this->actorId(), 'Store closed for emergency maintenance', new DateTimeImmutable('2026-08-31T15:00:00Z'));
        self::assertSame(StockTransferStatus::Cancelled, $transfer->status());
        self::assertSame('Store closed for emergency maintenance', $transfer->cancellationReason());
    }

    private function transfer(): StockTransfer
    {
        return StockTransfer::create($this->transferId(), $this->organizationId(), $this->sourceStoreId(), $this->destinationStoreId(), $this->actorId(), new DateTimeImmutable('2026-08-31T14:00:00Z'));
    }

    private function transferId(): StockTransferId
    {
        return StockTransferId::fromString('0199f000-0000-7000-8000-000000000001', $this->ids);
    }
    private function lineId(): StockTransferLineId
    {
        return StockTransferLineId::fromString('0199f000-0000-7000-8000-000000000002', $this->ids);
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0199f000-0000-7000-8000-000000000003', $this->ids);
    }
    private function sourceStoreId(): StoreId
    {
        return StoreId::fromString('0199f000-0000-7000-8000-000000000004', $this->ids);
    }
    private function destinationStoreId(): StoreId
    {
        return StoreId::fromString('0199f000-0000-7000-8000-000000000005', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0199f000-0000-7000-8000-000000000006', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0199f000-0000-7000-8000-000000000007', $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
