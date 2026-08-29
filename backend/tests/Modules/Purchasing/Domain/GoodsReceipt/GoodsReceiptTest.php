<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain\GoodsReceipt;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptCancelled;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\Event\GoodsReceiptPosted;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceipt;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptNumber;
use Zandu\Modules\Purchasing\Domain\GoodsReceipt\GoodsReceiptStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\GoodsReceiptLineId;
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

final class GoodsReceiptTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testDraftKeepsCommercialAndInventorySnapshots(): void
    {
        $receipt = $this->receipt();
        $line = $this->line('001', '010', '5', '12', '60', '120', '10');

        $receipt->addLine($line);

        self::assertSame(GoodsReceiptStatus::Draft, $receipt->status());
        self::assertSame('60', $line->receivedBaseQuantity()->toString());
        self::assertSame('10', $line->inventoryUnitCost()->amount()->toString());
        self::assertSame(2, $receipt->version());
        self::assertNull($receipt->purchaseOrderId());
    }

    public function testLinkedAndDirectReceiptLineShapesCannotBeMixed(): void
    {
        $direct = $this->receipt();
        $this->assertViolation('GOODS_RECEIPT_PURCHASE_ORDER_LINE_INVALID', fn() => $direct->addLine($this->line('001', '010', '1', '1', '1', '10', '10', true)));

        $linked = $this->receipt(true);
        $this->assertViolation('GOODS_RECEIPT_PURCHASE_ORDER_LINE_INVALID', fn() => $linked->addLine($this->line('002', '011', '1', '1', '1', '10', '10')));
    }

    public function testAProductCanOccurOnlyOnce(): void
    {
        $receipt = $this->receipt();
        $receipt->addLine($this->line('001', '010', '1', '1', '1', '10', '10'));

        $this->assertViolation('GOODS_RECEIPT_PRODUCT_DUPLICATE', fn() => $receipt->addLine($this->line('002', '010', '1', '1', '1', '10', '10')));
    }

    public function testPostingRequiresLinesAndMakesReceiptImmutable(): void
    {
        $receipt = $this->receipt();
        $this->assertViolation('GOODS_RECEIPT_EMPTY', fn() => $receipt->post($this->actorId(), new DateTimeImmutable()));
        $receipt->addLine($this->line('001', '010', '1', '1', '1', '10', '10'));

        $receipt->post($this->actorId(), new DateTimeImmutable('2026-08-29T10:00:00+01:00'));

        self::assertSame(GoodsReceiptStatus::Posted, $receipt->status());
        self::assertSame('UTC', $receipt->postedAt()?->getTimezone()->getName());
        self::assertInstanceOf(GoodsReceiptPosted::class, array_slice($receipt->releaseEvents(), -1)[0]);
        $this->assertViolation('GOODS_RECEIPT_ALREADY_POSTED', fn() => $receipt->removeLine($receipt->lines()[0]->id()));
    }

    public function testDraftCanBeCancelledWithAudit(): void
    {
        $receipt = $this->receipt();
        $receipt->cancel($this->actorId(), new DateTimeImmutable('2026-08-29T11:00:00Z'));

        self::assertSame(GoodsReceiptStatus::Cancelled, $receipt->status());
        self::assertSame($this->actorId()->toString(), $receipt->cancelledBy()?->toString());
        self::assertInstanceOf(GoodsReceiptCancelled::class, array_slice($receipt->releaseEvents(), -1)[0]);
    }

    public function testInventoryCostMustMatchCommercialCostWhenProvided(): void
    {
        $this->assertViolation('GOODS_RECEIPT_INVENTORY_COST_INVALID', fn() => $this->line('001', '010', '5', '12', '60', '120', '11'));
    }

    private function receipt(bool $linked = false): GoodsReceipt
    {
        return GoodsReceipt::create(
            GoodsReceiptId::fromString($this->uuid('900'), $this->ids),
            OrganizationId::fromString($this->uuid('901'), $this->ids),
            StoreId::fromString($this->uuid('902'), $this->ids),
            SupplierId::fromString($this->uuid('903'), $this->ids),
            $linked ? PurchaseOrderId::fromString($this->uuid('904'), $this->ids) : null,
            GoodsReceiptNumber::fromString(' gr-2026-001 '),
            ' BL-42 ',
            ' Checked on arrival ',
            $this->actorId(),
            new DateTimeImmutable('2026-08-29T08:00:00+01:00'),
        );
    }

    private function line(string $id, string $product, string $entered, string $factor, string $base, string $actualCost, string $inventoryCost, bool $linked = false): GoodsReceiptLine
    {
        return new GoodsReceiptLine(
            GoodsReceiptLineId::fromString($this->uuid($id), $this->ids),
            GoodsReceiptId::fromString($this->uuid('900'), $this->ids),
            ProductId::fromString($this->uuid($product), $this->ids),
            ProductPackagingId::fromString($this->uuid('100'), $this->ids),
            $this->quantity($entered),
            $this->quantity($factor),
            $this->quantity($base),
            $this->money($actualCost),
            $this->money($inventoryCost),
            $linked ? PurchaseOrderLineId::fromString($this->uuid('200'), $this->ids) : null,
        );
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString($this->uuid('905'), $this->ids);
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
        return '0198dc00-0000-7000-8000-000000000' . $suffix;
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
