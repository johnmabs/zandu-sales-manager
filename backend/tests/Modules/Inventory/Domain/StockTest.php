<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Domain;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Domain\Stock\Stock;
use Zandu\Modules\Inventory\Domain\Stock\StockQuantity;
use Zandu\Modules\Inventory\Domain\Stock\MovementQuantity;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StockId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Quantity\Quantity;

final class StockTest extends TestCase
{
    public function testStockMustBeInitializedBeforeChangesAndCannotGoNegative(): void
    {
        $factory = new SymfonyUuidFactory();
        $stock = Stock::create(
            StockId::fromString('0198d301-1111-7111-8111-111111111111', $factory),
            OrganizationId::fromString('0198d302-1111-7111-8111-111111111111', $factory),
            StoreId::fromString('0198d303-1111-7111-8111-111111111111', $factory),
            ProductId::fromString('0198d304-1111-7111-8111-111111111111', $factory),
            new StockQuantity($this->quantity('0')),
        );
        $actor = ActorId::fromString('0198d305-1111-7111-8111-111111111111', $factory);

        $this->expectException(LogicException::class);
        $stock->increase(new MovementQuantity($this->quantity('1')));
        $stock->initialize(new StockQuantity($this->quantity('5')), $actor, new DateTimeImmutable('2026-08-26T10:00:00Z'));
    }

    public function testInitializationAndOperationsUpdateQuantityAndVersion(): void
    {
        $factory = new SymfonyUuidFactory();
        $stock = Stock::create(StockId::fromString('0198d306-1111-7111-8111-111111111111', $factory), OrganizationId::fromString('0198d307-1111-7111-8111-111111111111', $factory), StoreId::fromString('0198d308-1111-7111-8111-111111111111', $factory), ProductId::fromString('0198d309-1111-7111-8111-111111111111', $factory), new StockQuantity($this->quantity('0')));
        $stock->initialize(new StockQuantity($this->quantity('5')), ActorId::fromString('0198d30a-1111-7111-8111-111111111111', $factory), new DateTimeImmutable('2026-08-26T10:00:00Z'));
        $stock->increase(new MovementQuantity($this->quantity('2.5')));
        $stock->decrease(new MovementQuantity($this->quantity('1.5')));
        self::assertSame('6.0', $stock->quantityOnHand()->toString());
        self::assertSame(4, $stock->version());
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, new BrickDecimalFactory());
    }
}
