<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain\PurchaseOrder;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\Event\PurchaseOrderClosed;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\Event\PurchaseOrderFullyReceived;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrder;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderLine;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderNumber;
use Zandu\Modules\Purchasing\Domain\PurchaseOrder\PurchaseOrderStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;
use Zandu\SharedKernel\Identity\PurchaseOrderLineId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;
use Zandu\SharedKernel\Money\Currency;
use Zandu\SharedKernel\Money\Money;
use Zandu\SharedKernel\Quantity\Quantity;

final class PurchaseOrderTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testDraftLinesMaintainSnapshotsTotalAndVersion(): void
    {
        $order = $this->order();
        $line = $this->line('001', '010', '100', '5', '12', '60', '120', '10');

        $order->addLine($line);

        self::assertSame(PurchaseOrderStatus::Draft, $order->status());
        self::assertSame('600.000000', $order->expectedTotal()->amount()->toString());
        self::assertSame('10', $line->inventoryUnitCost()->amount()->toString());
        self::assertSame(2, $order->version());

        $replacement = $this->line('001', '010', '100', '3', '12', '36', '120', '10');
        $order->updateLine($replacement);
        self::assertSame('360.000000', $order->expectedTotal()->amount()->toString());

        $order->removeLine($replacement->id());
        self::assertSame('0.000000', $order->expectedTotal()->amount()->toString());
        self::assertSame(0, $order->lineCount());
    }

    public function testAProductCanOccurOnlyOnce(): void
    {
        $order = $this->order();
        $order->addLine($this->line('001', '010', '100', '1', '1', '1', '20', '20'));

        $this->assertViolation(
            'PURCHASE_ORDER_PRODUCT_DUPLICATE',
            fn() => $order->addLine($this->line('002', '010', '101', '1', '1', '1', '20', '20')),
        );
    }

    public function testLineMustBelongToOrderAndUseItsCurrency(): void
    {
        $order = $this->order();
        $otherOrderLine = new PurchaseOrderLine(
            PurchaseOrderLineId::fromString($this->uuid('001'), $this->ids),
            PurchaseOrderId::fromString($this->uuid('999'), $this->ids),
            ProductId::fromString($this->uuid('010'), $this->ids),
            null,
            $this->quantity('1'),
            $this->quantity('1'),
            $this->quantity('1'),
            $this->money('20'),
            $this->money('20'),
            $this->quantity('0'),
        );

        $this->assertViolation('PURCHASE_ORDER_LINE_MISMATCH', fn() => $order->addLine($otherOrderLine));
    }

    public function testInvalidCostSnapshotIsRejected(): void
    {
        $this->assertViolation(
            'PURCHASE_ORDER_INVENTORY_COST_INVALID',
            fn() => $this->line('001', '010', '100', '5', '12', '60', '120', '11'),
        );
    }

    public function testConfirmationRequiresLinesAndFreezesTheirSnapshots(): void
    {
        $order = $this->order();
        $this->assertViolation('PURCHASE_ORDER_EMPTY', fn() => $order->confirm($this->actorId(), new DateTimeImmutable()));
        $order->addLine($this->line('001', '010', '100', '1', '1', '1', '20', '20'));

        $order->confirm($this->actorId(), new DateTimeImmutable('2026-08-28T14:00:00+01:00'));

        self::assertSame(PurchaseOrderStatus::Confirmed, $order->status());
        self::assertSame('UTC', $order->confirmedAt()?->getTimezone()->getName());
        $this->assertViolation(
            'PURCHASE_ORDER_NOT_EDITABLE',
            fn() => $order->addLine($this->line('002', '011', '101', '1', '1', '1', '20', '20')),
        );
    }

    public function testReceiptsAreCumulativeAndCannotExceedOrderedQuantity(): void
    {
        $order = $this->confirmedOrder();
        $lineId = $order->lines()[0]->id();

        $order->recordReceipt($lineId, $this->quantity('5'), $this->actorId(), new DateTimeImmutable());
        self::assertSame(PurchaseOrderStatus::PartiallyReceived, $order->status());
        self::assertSame('5', $order->lines()[0]->receivedQuantity()->toString());
        $this->assertViolation(
            'PURCHASE_ORDER_OVER_RECEIPT',
            fn() => $order->recordReceipt($lineId, $this->quantity('8'), $this->actorId(), new DateTimeImmutable()),
        );

        $order->recordReceipt($lineId, $this->quantity('7'), $this->actorId(), new DateTimeImmutable());
        self::assertSame(PurchaseOrderStatus::FullyReceived, $order->status());
        self::assertInstanceOf(PurchaseOrderFullyReceived::class, array_slice($order->releaseEvents(), -1)[0]);
    }

    public function testCancellationIsRejectedAfterAReceipt(): void
    {
        $order = $this->confirmedOrder();
        $order->recordReceipt($order->lines()[0]->id(), $this->quantity('1'), $this->actorId(), new DateTimeImmutable());

        $this->assertViolation('PURCHASE_ORDER_NOT_CANCELLABLE', fn() => $order->cancel($this->actorId(), new DateTimeImmutable()));
    }

    public function testClosingAPartialOrderRequiresAnAuditedReason(): void
    {
        $order = $this->confirmedOrder();
        $order->recordReceipt($order->lines()[0]->id(), $this->quantity('1'), $this->actorId(), new DateTimeImmutable());
        $this->assertViolation('PURCHASE_ORDER_CLOSE_REASON_REQUIRED', fn() => $order->close($this->actorId(), new DateTimeImmutable()));

        $order->close($this->actorId(), new DateTimeImmutable('2026-08-28T15:00:00+01:00'), 'Supplier cannot deliver remainder');

        self::assertSame(PurchaseOrderStatus::Closed, $order->status());
        self::assertSame('Supplier cannot deliver remainder', $order->closedReason());
        self::assertInstanceOf(PurchaseOrderClosed::class, array_slice($order->releaseEvents(), -1)[0]);
    }

    private function order(): PurchaseOrder
    {
        return PurchaseOrder::create(
            PurchaseOrderId::fromString($this->uuid('900'), $this->ids),
            OrganizationId::fromString($this->uuid('901'), $this->ids),
            StoreId::fromString($this->uuid('902'), $this->ids),
            SupplierId::fromString($this->uuid('903'), $this->ids),
            PurchaseOrderNumber::fromString(' po-2026-001 '),
            Currency::fromCode('XAF'),
            $this->money('0'),
            ActorId::fromString($this->uuid('904'), $this->ids),
            new DateTimeImmutable('2026-08-28T12:00:00+01:00'),
        );
    }

    private function confirmedOrder(): PurchaseOrder
    {
        $order = $this->order();
        $order->addLine($this->line('001', '010', '100', '1', '12', '12', '120', '10'));
        $order->confirm($this->actorId(), new DateTimeImmutable());

        return $order;
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString($this->uuid('904'), $this->ids);
    }

    private function line(string $line, string $product, string $packaging, string $entered, string $factor, string $base, string $unitCost, string $inventoryCost): PurchaseOrderLine
    {
        return new PurchaseOrderLine(
            PurchaseOrderLineId::fromString($this->uuid($line), $this->ids),
            PurchaseOrderId::fromString($this->uuid('900'), $this->ids),
            ProductId::fromString($this->uuid($product), $this->ids),
            ProductPackagingId::fromString($this->uuid($packaging), $this->ids),
            $this->quantity($entered),
            $this->quantity($factor),
            $this->quantity($base),
            $this->money($unitCost),
            $this->money($inventoryCost),
            $this->quantity('0'),
        );
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
    private function uuid(string $suffix): string
    {
        return '0198da00-0000-7000-8000-000000000' . $suffix;
    }

    /** @param callable(): void $operation */
    private function assertViolation(string $code, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected a purchasing rule violation.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame($code, $exception->errorCode());
        }
    }
}
