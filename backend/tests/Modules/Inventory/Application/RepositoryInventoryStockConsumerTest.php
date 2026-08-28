<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\Inventory\Application\Contract\ConsumeStockForSale;
use Zandu\Modules\Inventory\Application\RepositoryInventoryStockConsumer;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, SaleId, StockId, StoreId};
use Zandu\SharedKernel\Messaging\CorrelationId;
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Time\Clock;

final class RepositoryInventoryStockConsumerTest extends TestCase
{
    private const ORGANIZATION = '0198f801-1111-7111-8111-111111111111';
    private const STORE = '0198f802-1111-7111-8111-111111111111';
    private const PRODUCT = '0198f803-1111-7111-8111-111111111111';
    private const STOCK = '0198f804-1111-7111-8111-111111111111';
    private const SALE = '0198f805-1111-7111-8111-111111111111';
    private const ACTOR = '0198f806-1111-7111-8111-111111111111';
    private const CORRELATION = '0198f807-1111-7111-8111-111111111111';

    private SymfonyUuidFactory $uuids;
    private BrickDecimalFactory $decimals;

    protected function setUp(): void
    {
        $this->uuids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
    }

    public function testItValuesTheAcceptedPhysicalSaleMovement(): void
    {
        $stock = $this->stock();
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('get')->willReturn($stock);
        $stocks->expects(self::once())->method('decreaseIfAvailable')->with(
            $this->organization(),
            $stock->id(),
            self::callback(static fn(MovementQuantity $quantity): bool => '2' === $quantity->toString()),
            2,
        )->willReturn(true);
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::isInstanceOf(StockMovement::class))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->with(self::callback(static fn(ValueInventoryMovement $movement): bool => InventoryCostingMovementType::Sale === $movement->type
            && '2' === $movement->quantity->toString()
            && '10' === $movement->previousQuantity->toString()
            && '8' === $movement->resultingQuantity->toString()
            && self::SALE === $movement->reason
            && self::CORRELATION === $movement->actorContext->correlationId()->toString()))
            ->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => new ValuedInventoryMovement(
                $movement->stockId,
                $movement->stockMovementId,
                $movement->quantity,
                Money::fromString('400', Currency::fromCode('XAF'), $this->decimals),
                Money::fromString('800', Currency::fromCode('XAF'), $this->decimals),
                2,
                $movement->occurredAt,
            ));

        $result = $this->consumer($stocks, $movements, $costing)->consumeStockForSale($this->request());

        self::assertFalse($result->alreadyConsumed);
        self::assertSame(self::SALE, $result->saleId->toString());
        self::assertCount(1, $result->costedItems);
        self::assertSame('400', $result->costedItems[0]->unitCost->amount()->toString());
    }

    public function testReplayDoesNotDecreaseOrValueStockAgain(): void
    {
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('get')->willReturn($this->stock());
        $stocks->expects(self::never())->method('decreaseIfAvailable');
        $movements = $this->createStub(StockMovementRepository::class);
        $movements->method('appendOnce')->willReturn(false);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::never())->method('value');

        $result = $this->consumer($stocks, $movements, $costing)->consumeStockForSale($this->request());

        self::assertTrue($result->alreadyConsumed);
    }

    private function consumer(
        StockRepository $stocks,
        StockMovementRepository $movements,
        InventoryMovementValuer $costing,
    ): RepositoryInventoryStockConsumer {
        return new RepositoryInventoryStockConsumer(
            $stocks,
            $movements,
            $costing,
            new SymfonyUuidV7Generator(),
            new class implements Clock {
                public function now(): DateTimeImmutable
                {
                    return new DateTimeImmutable('2026-08-27T18:00:00Z');
                }
            },
        );
    }

    private function request(): ConsumeStockForSale
    {
        return new ConsumeStockForSale(
            $this->organization(),
            StoreId::fromString(self::STORE, $this->uuids),
            SaleId::fromString(self::SALE, $this->uuids),
            [['productId' => ProductId::fromString(self::PRODUCT, $this->uuids), 'baseQuantity' => $this->quantity('2')]],
            $this->actor(),
        );
    }

    private function stock(): Stock
    {
        return Stock::reconstitute(
            StockId::fromString(self::STOCK, $this->uuids),
            $this->organization(),
            StoreId::fromString(self::STORE, $this->uuids),
            ProductId::fromString(self::PRODUCT, $this->uuids),
            new StockQuantity($this->quantity('10')),
            true,
            new DateTimeImmutable('2026-08-27T17:00:00Z'),
            ActorId::fromString(self::ACTOR, $this->uuids),
            2,
        );
    }

    private function actor(): ActorContext
    {
        return new ActorContext(
            ActorId::fromString(self::ACTOR, $this->uuids),
            $this->organization(),
            ActorType::User,
            CorrelationId::fromString(self::CORRELATION, $this->uuids),
            new DateTimeImmutable('2026-08-27T17:00:00Z'),
        );
    }

    private function organization(): OrganizationId
    {
        return OrganizationId::fromString(self::ORGANIZATION, $this->uuids);
    }

    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}
