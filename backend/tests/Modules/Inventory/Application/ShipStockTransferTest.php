<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferShipper, ShipStockTransferStock, StockTransferStockResult, StockTransferStockUnavailable};
use Zandu\Modules\Inventory\Application\RepositoryInventoryStockTransferShipper;
use Zandu\Modules\Inventory\Application\ShipStockTransfer\{ShipStockTransfer, ShipStockTransferHandler};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementType};
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferRepository, StockTransferStatus};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryMovementValuer, ValueInventoryMovement, ValuedInventoryMovement};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockId, StockTransferId, StockTransferLineId, StoreId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxRepository};
use Zandu\SharedKernel\Money\{Currency, Money};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class ShipStockTransferTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-31T17:00:00Z'));
    }

    public function testHandlerShipsInventoryAndPersistsTheFrozenDocument(): void
    {
        $transfer = $this->transfer();
        $repository = $this->createMock(StockTransferRepository::class);
        $repository->expects(self::once())->method('getForUpdate')->willReturn($transfer);
        $repository->expects(self::once())->method('save')->with($transfer);
        $inventory = $this->createMock(InventoryStockTransferShipper::class);
        $inventory->expects(self::once())->method('ship')->with(self::callback(fn(ShipStockTransferStock $request): bool => '3' === $request->items[0]['baseQuantity']->toString() && $this->transferId()->equals($request->transferId)))->willReturn(new StockTransferStockResult(1, [$this->productId()->toString() => ['unitCost' => $this->money('2'), 'totalValue' => $this->money('6')]]));
        $authorization = $this->createMock(AuthorizationService::class);
        $authorizedStores = [];
        $authorization->expects(self::exactly(2))->method('authorize')->willReturnCallback(function (ActorContext $actor, PermissionCode $permission, ResourceScope $scope) use (&$authorizedStores): void {
            self::assertSame(PermissionCode::StockTransferShip, $permission);
            self::assertTrue($this->actorId()->equals($actor->actorId()));
            $authorizedStores[] = $scope->storeId?->toString();
        });
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::exactly(2))->method('assertStore');
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append');
        $handler = new ShipStockTransferHandler($repository, $inventory, new StockTransferTransaction(), $authorization, $guard, $outbox, new SymfonyUuidV7Generator(), $this->clock);

        $result = $handler(new ShipStockTransfer($transfer->id(), [$this->lineId()->toString() => $this->quantity('3')], $this->actor(), 'ship-1'));

        self::assertSame([$this->sourceStoreId()->toString(), $this->destinationStoreId()->toString()], $authorizedStores);
        self::assertSame(StockTransferStatus::Shipped, $result->status());
    }

    public function testHandlerMapsInsufficientStockAndDoesNotPersist(): void
    {
        $transfer = $this->transfer();
        $repository = $this->createMock(StockTransferRepository::class);
        $repository->method('getForUpdate')->willReturn($transfer);
        $repository->expects(self::never())->method('save');
        $inventory = $this->createStub(InventoryStockTransferShipper::class);
        $inventory->method('ship')->willThrowException(new StockTransferStockUnavailable($this->productId()));
        $handler = new ShipStockTransferHandler($repository, $inventory, new StockTransferTransaction(), $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $this->createStub(OutboxRepository::class), new SymfonyUuidV7Generator(), $this->clock);

        try {
            $handler(new ShipStockTransfer($transfer->id(), [$this->lineId()->toString() => $this->quantity('3')], $this->actor()));
            self::fail('Insufficient stock must fail.');
        } catch (InventoryRuleViolation $exception) {
            self::assertSame('TRANSFER_INSUFFICIENT_STOCK', $exception->errorCode());
        }
    }

    public function testRepositoryShipperLocksDecreasesAndWritesTransferOut(): void
    {
        $stock = Stock::reconstitute($this->stockId(), $this->organizationId(), $this->sourceStoreId(), $this->productId(), new StockQuantity($this->quantity('5')), true, $this->clock->now(), $this->actorId(), 1);
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('getForUpdate')->willReturn($stock);
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $saved): bool => '2' === $saved->quantityOnHand()->toString()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(fn(StockMovement $movement): bool => StockMovementType::TransferOut === $movement->type() && 'TRANSFER' === $movement->source()->type() && $this->transferId()->toString() === $movement->source()->referenceId()))->willReturn(true);
        $costing = $this->createMock(InventoryMovementValuer::class);
        $costing->expects(self::once())->method('value')->willReturnCallback(fn(ValueInventoryMovement $movement): ValuedInventoryMovement => new ValuedInventoryMovement($movement->stockId, $movement->stockMovementId, $movement->quantity, $this->money('2'), $this->money('6'), 2, $movement->occurredAt));
        $shipper = new RepositoryInventoryStockTransferShipper($stocks, $movements, $costing, new SymfonyUuidV7Generator());

        self::assertSame('6', $shipper->ship(new ShipStockTransferStock($this->organizationId(), $this->sourceStoreId(), $this->transferId(), [['productId' => $this->productId(), 'baseQuantity' => $this->quantity('3')]], $this->actor(), $this->clock->now()))->costs[$this->productId()->toString()]['totalValue']->amount()->toString());
    }

    private function transfer(): StockTransfer
    {
        $transfer = StockTransfer::create($this->transferId(), $this->organizationId(), $this->sourceStoreId(), $this->destinationStoreId(), $this->actorId(), $this->clock->now());
        $transfer->addLine(new StockTransferLine($this->lineId(), $transfer->id(), $this->productId(), $this->quantity('4')));
        return $transfer;
    }
    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0199f100-0000-7000-8000-000000000008', $this->ids), $this->clock->now());
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0199f100-0000-7000-8000-000000000001', $this->ids);
    }
    private function sourceStoreId(): StoreId
    {
        return StoreId::fromString('0199f100-0000-7000-8000-000000000002', $this->ids);
    }
    private function destinationStoreId(): StoreId
    {
        return StoreId::fromString('0199f100-0000-7000-8000-000000000003', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0199f100-0000-7000-8000-000000000004', $this->ids);
    }
    private function transferId(): StockTransferId
    {
        return StockTransferId::fromString('0199f100-0000-7000-8000-000000000005', $this->ids);
    }
    private function lineId(): StockTransferLineId
    {
        return StockTransferLineId::fromString('0199f100-0000-7000-8000-000000000006', $this->ids);
    }
    private function stockId(): StockId
    {
        return StockId::fromString('0199f100-0000-7000-8000-000000000007', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0199f100-0000-7000-8000-000000000009', $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
    private function money(string $value): Money
    {
        return Money::fromString($value, Currency::fromCode('XAF'), $this->decimals);
    }
}

final class StockTransferTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
