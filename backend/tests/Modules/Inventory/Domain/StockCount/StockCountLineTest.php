<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain\StockCount;

use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCountLine, StockCountReconciliationStatus};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StockCountId, StockCountLineId, StoreId};
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
