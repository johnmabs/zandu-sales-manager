<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReceiveStockTransfer;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Application\Contract\{InventoryStockTransferReceiver, ReceiveStockTransferStock};
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockTransfer\{StockTransfer, StockTransferRepository, StockTransferStatus};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ReceiveStockTransferHandler
{
    public function __construct(private StockTransferRepository $transfers, private InventoryStockTransferReceiver $inventory, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    public function __invoke(ReceiveStockTransfer $command): StockTransfer
    {
        $organizationId = $command->actorContext->organizationId();
        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockTransfer {
            $transfer = $this->transfers->getForUpdate($organizationId, $command->transferId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockTransferReceive, ResourceScope::store($organizationId, $transfer->destinationStoreId()));
            if (StockTransferStatus::Received === $transfer->status()) {
                return $transfer;
            }
            if (StockTransferStatus::Shipped !== $transfer->status()) {
                throw InventoryRuleViolation::with('STOCK_TRANSFER_NOT_SHIPPED', 'Only a shipped stock transfer can be received.');
            }
            $this->guard->assertStore($command->actorContext, $transfer->destinationStoreId(), OperationalMode::Remediation);
            $now = $this->clock->now();
            $transfer->receive($command->actorContext->actorId(), $now, $command->receivedQuantities);
            $items = [];
            foreach ($transfer->lines() as $line) {
                $quantity = $line->receivedQuantity();
                if (null === $quantity) {
                    throw new \LogicException('Received stock transfer line quantities are incomplete.');
                }
                if (!$quantity->isZero()) {
                    $unitCost = $line->shippedUnitCostSnapshot() ?? throw new \LogicException('Shipped transfer cost snapshot is missing.');
                    $items[] = ['productId' => $line->productId(), 'baseQuantity' => $quantity, 'incomingUnitCost' => $unitCost->amount()];
                }
            }
            $result = $this->inventory->receive(new ReceiveStockTransferStock($organizationId, $transfer->destinationStoreId(), $transfer->id(), $items, $command->actorContext, $now));
            if ($result->processedCount !== count($items)) {
                throw InventoryRuleViolation::with('STOCK_TRANSFER_CONFLICT', 'Stock transfer reception is incomplete.');
            }
            $transfer->attachReceivedValues($result->costs);
            $this->transfers->save($transfer);
            $this->outbox->append(new OutboxMessage(OutboxMessageId::generate($this->ids), $organizationId, 'inventory.stock_transfer_received.v1', ['stockTransferId' => $transfer->id()->toString(), 'sourceStoreId' => $transfer->sourceStoreId()->toString(), 'destinationStoreId' => $transfer->destinationStoreId()->toString(), 'lineCount' => count($transfer->lines()), 'hasDiscrepancy' => $transfer->hasTransitDiscrepancy(), 'commandId' => $command->commandId], $command->actorContext->correlationId(), $command->actorContext->causationId(), $now));
            return $transfer;
        });
    }
}
