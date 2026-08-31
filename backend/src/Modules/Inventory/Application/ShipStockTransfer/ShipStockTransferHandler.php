<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ShipStockTransfer;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferShipper, ShipStockTransferStock, StockTransferIdempotency, StockTransferPhase, StockTransferStockUnavailable};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferRepository, StockTransferStatus};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ShipStockTransferHandler
{
    public function __construct(private StockTransferRepository $transfers, private InventoryStockTransferShipper $inventory, private StockTransferIdempotency $idempotency, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    public function __invoke(ShipStockTransfer $command): StockTransfer
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockTransfer {
            $transfer = $this->transfers->getForUpdate($organizationId, $command->transferId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockTransferShip, ResourceScope::store($organizationId, $transfer->sourceStoreId()));
            $this->authorization->authorize($command->actorContext, PermissionCode::StockTransferShip, ResourceScope::store($organizationId, $transfer->destinationStoreId()));
            if ('' !== $command->commandId) {
                $this->idempotency->claim($transfer->id(), StockTransferPhase::TransferOut, $command->commandId, $this->payloadHash($command->shippedQuantities));
            }
            if (StockTransferStatus::Shipped === $transfer->status()) {
                return $transfer;
            }
            if (StockTransferStatus::Draft !== $transfer->status()) {
                throw InventoryRuleViolation::with('STOCK_TRANSFER_NOT_EDITABLE', 'Only a draft stock transfer can be shipped.');
            }
            $this->guard->assertStore($command->actorContext, $transfer->sourceStoreId());
            $this->guard->assertStore($command->actorContext, $transfer->destinationStoreId());
            $now = $this->clock->now();
            $transfer->ship($command->actorContext->actorId(), $now, $command->shippedQuantities);
            $items = [];
            foreach ($transfer->lines() as $line) {
                $quantity = $line->shippedQuantity();
                if (null !== $quantity && !$quantity->isZero()) {
                    $items[] = ['productId' => $line->productId(), 'baseQuantity' => $quantity];
                }
            }
            try {
                $result = $this->inventory->ship(new ShipStockTransferStock($organizationId, $transfer->sourceStoreId(), $transfer->id(), $items, $command->actorContext, $now));
            } catch (StockTransferStockUnavailable) {
                throw InventoryRuleViolation::with('TRANSFER_INSUFFICIENT_STOCK', 'Stock transfer quantity exceeds the available source stock.');
            }
            if ($result->processedCount !== count($items)) {
                throw InventoryRuleViolation::with('STOCK_TRANSFER_CONFLICT', 'Stock transfer shipment is incomplete.');
            }
            $transfer->attachShipmentCosts($result->costs);
            $this->transfers->save($transfer);
            $this->outbox->append(new OutboxMessage(OutboxMessageId::generate($this->ids), $organizationId, 'inventory.stock_transfer_shipped.v1', ['stockTransferId' => $transfer->id()->toString(), 'sourceStoreId' => $transfer->sourceStoreId()->toString(), 'destinationStoreId' => $transfer->destinationStoreId()->toString(), 'lineCount' => count($transfer->lines()), 'commandId' => $command->commandId], $command->actorContext->correlationId(), $command->actorContext->causationId(), $now));
            return $transfer;
        });
    }

    /** @param array<string, \Zandu\SharedKernel\Quantity\Quantity> $quantities */
    private function payloadHash(array $quantities): string
    {
        ksort($quantities, SORT_STRING);

        return hash('sha256', implode('|', array_map(static fn(string $lineId, $quantity): string => $lineId . '=' . $quantity->toString(), array_keys($quantities), $quantities)));
    }
}
