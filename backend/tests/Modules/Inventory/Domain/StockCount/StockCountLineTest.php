<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain\StockCount;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCountLine, StockCountReconciliationStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StockCountLineId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockCountLineTest extends TestCase
{
    public function testItCreatesAPendingUncountedSnapshotLine(): void
    {
        $line = $this->line('0');

        self::assertSame('0', $line->expectedQuantity()->toString());
        self::assertNull($line->countedQuantity());
        self::assertNull($line->countedBy());
        self::assertNull($line->countedAt());
        self::assertSame(0, $line->revision());
        self::assertSame(StockCountReconciliationStatus::Pending, $line->reconciliationStatus());
        self::assertSame(1, $line->version());
    }

    public function testItRejectsANegativeExpectedQuantity(): void
    {
        $this->expectException(InventoryRuleViolation::class);
        $this->expectExceptionMessage('Stock count expected quantity cannot be negative.');
        $this->line('-1');
    }

    public function testItRecordsExplicitZeroAndAllowsCorrectionWithIndependentRevision(): void
    {
        $ids = new SymfonyUuidFactory();
        $actorId = ActorId::fromString('0199f700-0000-7000-8000-000000000006', $ids);
        $line = $this->line('5');
        $line->record(Quantity::fromString('0', new BrickDecimalFactory()), $actorId, new DateTimeImmutable('2026-08-31T21:00:00+01:00'));

        self::assertSame('0', $line->countedQuantity()?->toString());
        self::assertSame(1, $line->revision());
        self::assertSame(2, $line->version());
        self::assertSame('2026-08-31T20:00:00+00:00', $line->countedAt()?->format(DATE_ATOM));

        $line->record(Quantity::fromString('4', new BrickDecimalFactory()), $actorId, new DateTimeImmutable('2026-08-31T21:05:00Z'));
        self::assertSame('4', $line->countedQuantity()?->toString());
        self::assertSame(2, $line->revision());
        self::assertSame(3, $line->version());
    }

    public function testOnlyACountedPendingLineCanBeReconciled(): void
    {
        $ids = new SymfonyUuidFactory();
        $line = $this->line('5');
        try {
            $line->markReconciled();
            self::fail('An uncounted line must not be reconciled.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('STOCK_COUNT_LINE_UNCOUNTED', $exception->errorCode());
        }

        $line->record(Quantity::fromString('4', new BrickDecimalFactory()), ActorId::fromString('0199f700-0000-7000-8000-000000000006', $ids), new DateTimeImmutable());
        $line->markReconciled();
        self::assertSame(StockCountReconciliationStatus::Reconciled, $line->reconciliationStatus());
        self::assertSame(3, $line->version());

        $this->expectException(InventoryRuleViolation::class);
        $line->markReconciled();
    }

    private function line(string $expectedQuantity): StockCountLine
    {
        $ids = new SymfonyUuidFactory();

        return StockCountLine::create(
            StockCountLineId::fromString('0199f700-0000-7000-8000-000000000001', $ids),
            StockCountId::fromString('0199f700-0000-7000-8000-000000000002', $ids),
            OrganizationId::fromString('0199f700-0000-7000-8000-000000000003', $ids),
            StoreId::fromString('0199f700-0000-7000-8000-000000000004', $ids),
            ProductId::fromString('0199f700-0000-7000-8000-000000000005', $ids),
            Quantity::fromString($expectedQuantity, new BrickDecimalFactory()),
        );
    }
}
