<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\StockCountViewFactory;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLine, StockCountMode, StockCountScopeType};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\SymfonyUuidFactory;
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockCountId, StockCountLineId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final class StockCountViewFactoryTest extends TestCase
{
    public function testBlindCountHidesExpectedQuantityAndVarianceUntilEntriesAreFrozen(): void
    {
        [$count, $line] = $this->openCount(StockCountMode::Blind);
        $factory = new StockCountViewFactory();

        $openView = $factory->create($count, [$line]);

        self::assertArrayNotHasKey('expectedQuantity', $openView->lines[0]);
        self::assertArrayNotHasKey('variance', $openView->lines[0]);

        $line->record($this->quantity('8'), $this->actorId(), $this->now());
        $count->registerCountedLines(1);
        $count->beginFinalization($this->actorId(), $this->now());
        $frozenView = $factory->create($count, [$line]);

        self::assertSame('10', $frozenView->lines[0]['expectedQuantity']);
        self::assertSame('-2', $frozenView->lines[0]['variance']);
    }

    public function testGuidedCountShowsExpectedQuantityWhileOpen(): void
    {
        [$count, $line] = $this->openCount(StockCountMode::Guided);

        $view = (new StockCountViewFactory())->create($count, [$line]);

        self::assertSame('10', $view->lines[0]['expectedQuantity']);
        self::assertNull($view->lines[0]['variance']);
    }

    /** @return array{StockCount, StockCountLine} */
    private function openCount(StockCountMode $mode): array
    {
        $count = StockCount::create(
            $this->stockCountId(),
            $this->organizationId(),
            $this->storeId(),
            StockCountScopeType::Partial,
            $this->actorId(),
            $this->now(),
            $mode,
            [$this->productId()],
        );
        $count->start($this->actorId(), $this->now(), 1);

        return [$count, StockCountLine::create(
            StockCountLineId::fromString('019a0500-0000-7000-8000-000000000005', $this->ids()),
            $count->id(),
            $count->organizationId(),
            $count->storeId(),
            $this->productId(),
            $this->quantity('10'),
        )];
    }

    private function ids(): SymfonyUuidFactory
    {
        return new SymfonyUuidFactory();
    }

    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('019a0500-0000-7000-8000-000000000001', $this->ids());
    }

    private function storeId(): StoreId
    {
        return StoreId::fromString('019a0500-0000-7000-8000-000000000002', $this->ids());
    }

    private function productId(): ProductId
    {
        return ProductId::fromString('019a0500-0000-7000-8000-000000000003', $this->ids());
    }

    private function actorId(): ActorId
    {
        return ActorId::fromString('019a0500-0000-7000-8000-000000000004', $this->ids());
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, new BrickDecimalFactory());
    }

    private function stockCountId(): StockCountId
    {
        return StockCountId::fromString('019a0500-0000-7000-8000-000000000006', $this->ids());
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-03T12:00:00Z');
    }
}
