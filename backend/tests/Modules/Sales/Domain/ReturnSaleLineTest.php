<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Sales\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Sales\Domain\{ReturnSaleLine, SaleLine, SaleLineCostSnapshot, SalesRuleViolation};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, ProductPackagingId, ReturnSaleLineId, SaleId, SaleLineId, StockId, StockMovementId, UnitOfMeasureId};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;

final class ReturnSaleLineTest extends TestCase
{
    public function testItPreservesOriginalSnapshotsAndConvertsTheReturnedQuantity(): void
    {
        $original = $this->originalLine();
        $cost = $this->costSnapshot($original->id());
        $line = new ReturnSaleLine(
            $this->returnLineId(),
            $original,
            $cost,
            $this->quantity('1.5'),
            true,
            '  Customer return  ',
        );

        self::assertTrue($line->saleLineId()->equals($original->id()));
        self::assertTrue($line->productId()->equals($original->productId()));
        self::assertSame('1.5', $line->returnedQuantity()->toString());
        self::assertSame('9.000000000000', $line->baseReturnedQuantity()->toString());
        self::assertTrue($line->restock());
        self::assertSame('Customer return', $line->reason());
        self::assertSame($original, $line->originalLine());
        self::assertSame($cost, $line->originalCostSnapshot());
    }

    public function testItSupportsAServiceReturnWithoutCostOrRestock(): void
    {
        $line = new ReturnSaleLine($this->returnLineId(), $this->originalLine(), null, $this->quantity('1'), false, '  ');

        self::assertFalse($line->restock());
        self::assertNull($line->reason());
        self::assertNull($line->originalCostSnapshot());
    }

    public function testItRejectsAQuantityGreaterThanTheOriginalSoldQuantity(): void
    {
        $this->expectException(SalesRuleViolation::class);
        $this->expectExceptionMessage('Return quantity cannot exceed the sold quantity.');

        new ReturnSaleLine($this->returnLineId(), $this->originalLine(), null, $this->quantity('3'), false, null);
    }

    public function testItRejectsACostSnapshotFromAnotherSaleLine(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Return cost snapshot belongs to another sale line.');

        new ReturnSaleLine(
            $this->returnLineId(),
            $this->originalLine(),
            $this->costSnapshot($this->saleLineId('019a3000-0000-7000-8000-000000000099')),
            $this->quantity('1'),
            true,
            null,
        );
    }

    private function originalLine(): SaleLine
    {
        $currency = Currency::fromCode('XAF');
        $zero = Money::fromString('0', $currency, new BrickDecimalFactory());
        $total = Money::fromString('18000', $currency, new BrickDecimalFactory());

        return new SaleLine(
            $this->saleLineId('019a3000-0000-7000-8000-000000000002'),
            SaleId::fromString('019a3000-0000-7000-8000-000000000003', $this->uuids()),
            ProductId::fromString('019a3000-0000-7000-8000-000000000004', $this->uuids()),
            ProductPackagingId::fromString('019a3000-0000-7000-8000-000000000005', $this->uuids()),
            'SKU',
            'Product',
            'PACK-6',
            'Pack of six',
            UnitOfMeasureId::fromString('019a3000-0000-7000-8000-000000000006', $this->uuids()),
            $this->quantity('2'),
            $this->quantity('6'),
            $this->quantity('12'),
            Money::fromString('9000', $currency, new BrickDecimalFactory()),
            'price-list',
            'product-price',
            $zero,
            $total,
            $zero,
            $total,
            $total,
            ['product' => 3, 'packaging' => 2, 'pricingPolicy' => 'snapshot'],
        );
    }

    private function costSnapshot(SaleLineId $saleLineId): SaleLineCostSnapshot
    {
        return SaleLineCostSnapshot::capture(
            OrganizationId::fromString('019a3000-0000-7000-8000-000000000007', $this->uuids()),
            $saleLineId,
            StockId::fromString('019a3000-0000-7000-8000-000000000008', $this->uuids()),
            StockMovementId::fromString('019a3000-0000-7000-8000-000000000009', $this->uuids()),
            $this->quantity('12'),
            Money::fromString('400', Currency::fromCode('XAF'), new BrickDecimalFactory()),
            2,
            new DateTimeImmutable('2026-08-28T10:00:00+01:00'),
        );
    }

    private function returnLineId(): ReturnSaleLineId
    {
        return ReturnSaleLineId::fromString('019a3000-0000-7000-8000-000000000001', $this->uuids());
    }

    private function saleLineId(string $value): SaleLineId
    {
        return SaleLineId::fromString($value, $this->uuids());
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, new BrickDecimalFactory());
    }

    private function uuids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }
}
