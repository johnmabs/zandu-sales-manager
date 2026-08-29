<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain\PurchaseReturn;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\PurchaseReturn\{PurchaseReturn, PurchaseReturnLine, PurchaseReturnStatus};
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, GoodsReceiptId, GoodsReceiptLineId, OrganizationId, ProductId, PurchaseReturnId, PurchaseReturnLineId, StoreId, SupplierId};
use Zandu\SharedKernel\Quantity\Quantity;

final class PurchaseReturnTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }
    public function testItShipsALinkedReturnAndBecomesImmutable(): void
    {
        $return = $this->return();
        $return->addLine($this->line());
        $return->ship($this->actor(), new DateTimeImmutable('2026-08-29T20:00:00+01:00'));
        self::assertSame(PurchaseReturnStatus::Shipped, $return->status());
        self::assertSame('2026-08-29T19:00:00+00:00', $return->shippedAt()?->format(DATE_ATOM));
        self::assertSame(3, $return->version());
        $this->expectException(PurchasingRuleViolation::class);
        $return->cancel($this->actor(), new DateTimeImmutable());
    }
    public function testItCancelsOnlyWhileDraft(): void
    {
        $return = $this->return();
        $return->cancel($this->actor(), new DateTimeImmutable());
        self::assertSame(PurchaseReturnStatus::Cancelled, $return->status());
    }
    public function testItRequiresReasonAndLinesBeforeShipping(): void
    {
        try {
            $this->return()->ship($this->actor(), new DateTimeImmutable());
            self::fail();
        } catch (PurchasingRuleViolation $e) {
            self::assertSame('PURCHASE_RETURN_EMPTY', $e->errorCode());
        }
        $this->expectException(PurchasingRuleViolation::class);
        PurchaseReturn::create($this->returnId(), $this->org(), $this->store(), $this->supplier(), $this->receipt(), null, ' ', $this->actor(), new DateTimeImmutable());
    }
    public function testLinkedReturnRequiresLinkedLinesAndUniqueProducts(): void
    {
        $return = $this->return();
        $return->addLine($this->line());
        try {
            $return->addLine($this->line());
            self::fail();
        } catch (PurchasingRuleViolation $e) {
            self::assertSame('PURCHASE_RETURN_PRODUCT_DUPLICATE', $e->errorCode());
        }
    }
    private function return(): PurchaseReturn
    {
        return PurchaseReturn::create($this->returnId(), $this->org(), $this->store(), $this->supplier(), $this->receipt(), null, 'Damaged delivery', $this->actor(), new DateTimeImmutable('2026-08-29T18:00:00Z'));
    }
    private function line(): PurchaseReturnLine
    {
        return new PurchaseReturnLine(PurchaseReturnLineId::fromString('0198e200-0000-7000-8000-000000000007', $this->ids), $this->returnId(), ProductId::fromString('0198e200-0000-7000-8000-000000000006', $this->ids), $this->q('2'), GoodsReceiptLineId::fromString('0198e200-0000-7000-8000-000000000008', $this->ids));
    }
    private function returnId(): PurchaseReturnId
    {
        return PurchaseReturnId::fromString('0198e200-0000-7000-8000-000000000001', $this->ids);
    } private function org(): OrganizationId
    {
        return OrganizationId::fromString('0198e200-0000-7000-8000-000000000002', $this->ids);
    } private function store(): StoreId
    {
        return StoreId::fromString('0198e200-0000-7000-8000-000000000003', $this->ids);
    } private function supplier(): SupplierId
    {
        return SupplierId::fromString('0198e200-0000-7000-8000-000000000004', $this->ids);
    } private function receipt(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198e200-0000-7000-8000-000000000005', $this->ids);
    } private function actor(): ActorId
    {
        return ActorId::fromString('0198e200-0000-7000-8000-000000000009', $this->ids);
    } private function q(string $v): Quantity
    {
        return Quantity::fromString($v,$this->decimals);
    }
}
