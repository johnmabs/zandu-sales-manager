<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity,StockQuantity};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement,StockMovementId,StockMovementSource,StockMovementType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,StockId,StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockMovementTest extends TestCase
{
    public function testDirectionIsDerivedAndResultingQuantityIsComputed(): void
    {
        $f = new SymfonyUuidFactory();
        $m = StockMovement::record(StockMovementId::fromString('0198d401-1111-7111-8111-111111111111', $f), OrganizationId::fromString('0198d402-1111-7111-8111-111111111111', $f), StoreId::fromString('0198d403-1111-7111-8111-111111111111', $f), ProductId::fromString('0198d404-1111-7111-8111-111111111111', $f), StockId::fromString('0198d405-1111-7111-8111-111111111111', $f), StockMovementType::AdjustmentOut, new MovementQuantity($this->q('2')), new StockQuantity($this->q('5')), StockMovementSource::manualAdjustment(), null, null, new DateTimeImmutable('2026-08-26T10:00:00Z'));
        self::assertSame('3', $m->resultingQuantity()->toString());
    }

    public function testAdjustmentOutPreservesLedgerExplanation(): void
    {
        $f = new SymfonyUuidFactory();
        $m = StockMovement::record(
            StockMovementId::fromString('0198d406-1111-7111-8111-111111111111', $f),
            OrganizationId::fromString('0198d407-1111-7111-8111-111111111111', $f),
            StoreId::fromString('0198d408-1111-7111-8111-111111111111', $f),
            ProductId::fromString('0198d409-1111-7111-8111-111111111111', $f),
            StockId::fromString('0198d40a-1111-7111-8111-111111111111', $f),
            StockMovementType::AdjustmentOut,
            new MovementQuantity($this->q('3')),
            new StockQuantity($this->q('10')),
            StockMovementSource::manualAdjustment(),
            'Damaged items',
            null,
            new DateTimeImmutable('2026-08-26T10:00:00Z'),
        );

        self::assertSame(StockMovementType::AdjustmentOut, $m->type());
        self::assertSame('10', $m->previousQuantity()->toString());
        self::assertSame('3', $m->quantity()->toString());
        self::assertSame('7', $m->resultingQuantity()->toString());
        self::assertSame('Damaged items', $m->reason());
        self::assertTrue((new \ReflectionClass($m))->isReadOnly());
    }
    private function q(string $v): Quantity
    {
        return Quantity::fromString($v, new BrickDecimalFactory());
    }
}
