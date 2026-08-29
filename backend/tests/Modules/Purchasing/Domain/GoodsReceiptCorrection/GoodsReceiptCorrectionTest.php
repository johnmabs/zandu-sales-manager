<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Purchasing\Domain\GoodsReceiptCorrection;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrection;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionLine;
use Zandu\Modules\Purchasing\Domain\GoodsReceiptCorrection\GoodsReceiptCorrectionStatus;
use Zandu\Modules\Purchasing\Domain\PurchasingRuleViolation;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\GoodsReceiptCorrectionId;
use Zandu\SharedKernel\Identity\GoodsReceiptId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Quantity\Quantity;

final class GoodsReceiptCorrectionTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItCalculatesSignedProspectiveDifferences(): void
    {
        self::assertSame('3', $this->line('10', '13')->difference()->toString());
        self::assertSame('-4', $this->line('10', '6')->difference()->toString());
        self::assertSame('0', $this->line('10', '10')->difference()->toString());
    }

    public function testItRequiresAReasonAndNonNegativeQuantities(): void
    {
        $this->expectException(PurchasingRuleViolation::class);
        GoodsReceiptCorrection::create($this->correctionId(), $this->organizationId(), $this->receiptId(), '  ', $this->actorId(), new DateTimeImmutable());
    }

    public function testItPostsOnceAndBecomesImmutable(): void
    {
        $correction = $this->correction();
        $correction->addLine($this->line('10', '8'));
        $correction->post($this->actorId(), new DateTimeImmutable('2026-08-29T16:00:00+01:00'));

        self::assertSame(GoodsReceiptCorrectionStatus::Posted, $correction->status());
        self::assertSame('2026-08-29T15:00:00+00:00', $correction->postedAt()?->format(DATE_ATOM));
        self::assertSame(3, $correction->version());

        $this->expectException(PurchasingRuleViolation::class);
        $correction->addLine(new GoodsReceiptCorrectionLine($correction->id(), ProductId::fromString('0198df00-0000-7000-8000-000000000006', $this->ids), $this->quantity('4'), $this->quantity('4'), $this->quantity('3')));
    }

    public function testItRejectsDuplicateProducts(): void
    {
        $correction = $this->correction();
        $correction->addLine($this->line('10', '9'));

        try {
            $correction->addLine($this->line('10', '8'));
            self::fail('A correction cannot contain a product twice.');
        } catch (PurchasingRuleViolation $exception) {
            self::assertSame('GOODS_RECEIPT_CORRECTION_PRODUCT_DUPLICATE', $exception->errorCode());
        }
    }

    private function correction(): GoodsReceiptCorrection
    {
        return GoodsReceiptCorrection::create($this->correctionId(), $this->organizationId(), $this->receiptId(), 'Count discrepancy', $this->actorId(), new DateTimeImmutable('2026-08-29T14:00:00Z'));
    }

    private function line(string $current, string $corrected): GoodsReceiptCorrectionLine
    {
        return new GoodsReceiptCorrectionLine($this->correctionId(), ProductId::fromString('0198df00-0000-7000-8000-000000000005', $this->ids), $this->quantity('10'), $this->quantity($current), $this->quantity($corrected));
    }

    private function correctionId(): GoodsReceiptCorrectionId
    {
        return GoodsReceiptCorrectionId::fromString('0198df00-0000-7000-8000-000000000001', $this->ids);
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0198df00-0000-7000-8000-000000000002', $this->ids);
    }
    private function receiptId(): GoodsReceiptId
    {
        return GoodsReceiptId::fromString('0198df00-0000-7000-8000-000000000003', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0198df00-0000-7000-8000-000000000004', $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
