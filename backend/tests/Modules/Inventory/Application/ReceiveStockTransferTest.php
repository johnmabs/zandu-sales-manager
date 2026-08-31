<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\Inventory\Application;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferReceiver, ReceiveStockTransferStock};
use Zandu\Modules\Inventory\Application\ReceiveStockTransfer\{ReceiveStockTransfer, ReceiveStockTransferHandler};
use Zandu\Modules\Inventory\Application\RepositoryInventoryStockTransferReceiver;
use Zandu\Modules\Inventory\Domain\Stock\{Stock, StockRepository};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementType};
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferLine, StockTransferRepository, StockTransferStatus};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\Platform\Decimal\BrickDecimalFactory;
use Zandu\Platform\Identity\{SymfonyUuidFactory, SymfonyUuidV7Generator};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\{ActorContext, ActorType};
use Zandu\SharedKernel\Identity\{ActorId, OrganizationId, ProductId, StockTransferId, StockTransferLineId, StoreId};
use Zandu\SharedKernel\Messaging\{CorrelationId, OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\Tests\SharedKernel\Time\FrozenClock;

final class ReceiveStockTransferTest extends TestCase
{
    private SymfonyUuidFactory $ids;
    private BrickDecimalFactory $decimals;
    private FrozenClock $clock;

    protected function setUp(): void
    {
        $this->ids = new SymfonyUuidFactory();
        $this->decimals = new BrickDecimalFactory();
        $this->clock = new FrozenClock(new DateTimeImmutable('2026-08-31T18:00:00Z'));
    }

    public function testHandlerReceivesDestinationStockAndPersistsFinalDocument(): void
    {
        $transfer = $this->shippedTransfer();
        $repository = $this->createMock(StockTransferRepository::class);
        $repository->expects(self::once())->method('getForUpdate')->willReturn($transfer);
        $repository->expects(self::once())->method('save')->with($transfer);
        $inventory = $this->createMock(InventoryStockTransferReceiver::class);
        $inventory->expects(self::once())->method('receive')->with(self::callback(fn(ReceiveStockTransferStock $request): bool => '2' === $request->items[0]['baseQuantity']->toString() && $this->destinationStoreId()->equals($request->destinationStoreId)))->willReturn(1);
        $authorization = $this->createMock(AuthorizationService::class);
        $authorization->expects(self::once())->method('authorize')->with($this->actor(), PermissionCode::StockTransferReceive, self::callback(fn(ResourceScope $scope): bool => $this->destinationStoreId()->equals($scope->storeId)));
        $guard = $this->createMock(OperationalGuard::class);
        $guard->expects(self::once())->method('assertStore')->with($this->actor(), $this->destinationStoreId(), OperationalMode::Remediation);
        $outbox = $this->createMock(OutboxRepository::class);
        $outbox->expects(self::once())->method('append')->with(self::callback(static fn(OutboxMessage $message): bool => true === $message->payload['hasDiscrepancy']));
        $handler = new ReceiveStockTransferHandler($repository, $inventory, new ReceiveTransferTransaction(), $authorization, $guard, $outbox, new SymfonyUuidV7Generator(), $this->clock);

        $result = $handler(new ReceiveStockTransfer($transfer->id(), [$this->lineId()->toString() => $this->quantity('2')], $this->actor(), 'receive-1'));

        self::assertSame(StockTransferStatus::Received, $result->status());
        self::assertSame('2', $result->lines()[0]->receivedQuantity()?->toString());
    }

    public function testReceiverCreatesInitializedDestinationStockAndWritesTransferIn(): void
    {
        $stocks = $this->createMock(StockRepository::class);
        $stocks->expects(self::once())->method('find')->willReturn(null);
        $stocks->expects(self::never())->method('getForUpdate');
        $stocks->expects(self::once())->method('save')->with(self::callback(static fn(Stock $stock): bool => $stock->initialized() && '3' === $stock->quantityOnHand()->toString()));
        $movements = $this->createMock(StockMovementRepository::class);
        $movements->expects(self::once())->method('appendOnce')->with(self::callback(fn(StockMovement $movement): bool => StockMovementType::TransferIn === $movement->type() && 'TRANSFER' === $movement->source()->type() && $this->transferId()->toString() === $movement->source()->referenceId()))->willReturn(true);
        $receiver = new RepositoryInventoryStockTransferReceiver($stocks, $movements, new SymfonyUuidV7Generator(), $this->decimals);

        self::assertSame(1, $receiver->receive(new ReceiveStockTransferStock($this->organizationId(), $this->destinationStoreId(), $this->transferId(), [['productId' => $this->productId(), 'baseQuantity' => $this->quantity('3')]], $this->actor(), $this->clock->now())));
    }

    public function testHandlerKeepsAZeroReceivedLineWithoutRequestingAStockMovement(): void
    {
        $transfer = $this->shippedTransfer();
        $repository = $this->createMock(StockTransferRepository::class);
        $repository->method('getForUpdate')->willReturn($transfer);
        $repository->expects(self::once())->method('save');
        $inventory = $this->createMock(InventoryStockTransferReceiver::class);
        $inventory->expects(self::once())->method('receive')->with(self::callback(static fn(ReceiveStockTransferStock $request): bool => [] === $request->items))->willReturn(0);
        $handler = new ReceiveStockTransferHandler($repository, $inventory, new ReceiveTransferTransaction(), $this->createStub(AuthorizationService::class), $this->createStub(OperationalGuard::class), $this->createStub(OutboxRepository::class), new SymfonyUuidV7Generator(), $this->clock);

        $result = $handler(new ReceiveStockTransfer($transfer->id(), [$this->lineId()->toString() => $this->quantity('0')], $this->actor()));

        self::assertSame('0', $result->lines()[0]->receivedQuantity()?->toString());
    }

    private function shippedTransfer(): StockTransfer
    {
        $transfer = StockTransfer::create($this->transferId(), $this->organizationId(), $this->sourceStoreId(), $this->destinationStoreId(), $this->actorId(), $this->clock->now());
        $transfer->addLine(new StockTransferLine($this->lineId(), $transfer->id(), $this->productId(), $this->quantity('4')));
        $transfer->ship($this->actorId(), $this->clock->now(), [$this->lineId()->toString() => $this->quantity('3')]);
        return $transfer;
    }
    private function actor(): ActorContext
    {
        return new ActorContext($this->actorId(), $this->organizationId(), ActorType::User, CorrelationId::fromString('0199f200-0000-7000-8000-000000000008', $this->ids), $this->clock->now());
    }
    private function organizationId(): OrganizationId
    {
        return OrganizationId::fromString('0199f200-0000-7000-8000-000000000001', $this->ids);
    }
    private function sourceStoreId(): StoreId
    {
        return StoreId::fromString('0199f200-0000-7000-8000-000000000002', $this->ids);
    }
    private function destinationStoreId(): StoreId
    {
        return StoreId::fromString('0199f200-0000-7000-8000-000000000003', $this->ids);
    }
    private function productId(): ProductId
    {
        return ProductId::fromString('0199f200-0000-7000-8000-000000000004', $this->ids);
    }
    private function transferId(): StockTransferId
    {
        return StockTransferId::fromString('0199f200-0000-7000-8000-000000000005', $this->ids);
    }
    private function lineId(): StockTransferLineId
    {
        return StockTransferLineId::fromString('0199f200-0000-7000-8000-000000000006', $this->ids);
    }
    private function actorId(): ActorId
    {
        return ActorId::fromString('0199f200-0000-7000-8000-000000000009', $this->ids);
    }
    private function quantity(string $value): Quantity
    {
        return Quantity::fromString($value, $this->decimals);
    }
}

final class ReceiveTransferTransaction implements TenantTransaction
{
    public function transactional(OrganizationId $organizationId, callable $operation): mixed
    {
        return $operation();
    }
}
