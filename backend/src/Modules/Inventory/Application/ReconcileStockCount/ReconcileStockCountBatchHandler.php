<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\ReconcileStockCount;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\Stock\{MovementQuantity, Stock, StockQuantity, StockRepository};
use Zandu\Modules\Inventory\Domain\StockCount\{StockCountLine, StockCountLineRepository, StockCountRepository, StockCountStatus};
use Zandu\Modules\Inventory\Domain\StockMovement\{StockMovement, StockMovementRepository, StockMovementSource, StockMovementType};
use Zandu\Modules\InventoryCosting\Application\Contract\{InventoryCostingMovementType, InventoryMovementValuer, ValueInventoryMovement};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator, StockId, StockMovementId};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class ReconcileStockCountBatchHandler
{
    public function __construct(
        private StockCountRepository $stockCounts,
        private StockCountLineRepository $lines,
        private StockRepository $stocks,
        private StockMovementRepository $movements,
        private InventoryMovementValuer $costing,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
        private IdGenerator $ids,
        private DecimalFactory $decimals,
        private Clock $clock,
    ) {}

    public function __invoke(ReconcileStockCountBatch $command): ReconcileStockCountResult
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): ReconcileStockCountResult {
            $stockCount = $this->stockCounts->getForUpdate($organizationId, $command->stockCountId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockCountFinalize, ResourceScope::store($organizationId, $stockCount->storeId()));
            if (StockCountStatus::Finalizing !== $stockCount->status()) {
                throw InventoryRuleViolation::with('STOCK_COUNT_NOT_FINALIZING', 'Stock count reconciliation requires a finalizing stock count.');
            }
            $this->guard->assertStore($command->actorContext, $stockCount->storeId(), OperationalMode::Remediation);
            $pendingLines = $this->lines->findPendingForUpdate($organizationId, $stockCount->id(), $command->batchSize);
            $now = $this->clock->now();

            foreach ($pendingLines as $line) {
                $this->reconcileLine($line, $command, $now);
            }
            $processedCount = count($pendingLines);
            $stockCount->registerReconciledLines($processedCount);
            if ($processedCount > 0) {
                $this->stockCounts->save($stockCount);
            }

            return new ReconcileStockCountResult($processedCount, $stockCount->totalLineCount() - $stockCount->reconciledLineCount());
        });
    }

    private function reconcileLine(StockCountLine $line, ReconcileStockCountBatch $command, \DateTimeImmutable $occurredAt): void
    {
        $counted = $line->countedQuantity() ?? throw InventoryRuleViolation::with('STOCK_COUNT_LINE_UNCOUNTED', 'An uncounted stock count line cannot be reconciled.');
        $expected = $line->expectedQuantity();
        $stock = $this->stocks->find($line->organizationId(), $line->storeId(), $line->productId());
        if (null !== $stock) {
            $stock = $this->stocks->getForUpdate($line->organizationId(), $line->storeId(), $line->productId());
        } elseif (!$expected->isZero()) {
            throw InventoryRuleViolation::with('STOCK_COUNT_SNAPSHOT_CONFLICT', 'Current stock no longer matches the stock count snapshot.');
        } elseif (!$counted->isZero()) {
            $stock = Stock::create(StockId::generate($this->ids), $line->organizationId(), $line->storeId(), $line->productId(), $this->stockQuantity('0'));
            $stock->initialize($this->stockQuantity('0'), $command->actorContext->actorId(), $occurredAt);
        }

        if (null !== $stock) {
            $previous = $stock->quantityOnHand();
            $stock->reconcile(new StockQuantity($expected), new StockQuantity($counted));
            $comparison = $counted->compareTo($expected);
            if (0 !== $comparison) {
                $quantity = new MovementQuantity($comparison > 0 ? $counted->subtract($expected) : $expected->subtract($counted));
                $type = $comparison > 0 ? StockMovementType::StockCountCorrectionIn : StockMovementType::StockCountCorrectionOut;
                $costAssignment = $comparison > 0 ? $command->costAssignmentFor($line->productId()) : null;
                if (null !== $costAssignment) {
                    $this->authorization->authorize(
                        $command->actorContext,
                        PermissionCode::InventoryCostAssign,
                        ResourceScope::store($line->organizationId(), $line->storeId()),
                    );
                }
                $movementReason = null === $costAssignment ? 'Stock count reconciliation' : $costAssignment->reason;
                $movement = StockMovement::record(
                    StockMovementId::generate($this->ids),
                    $line->organizationId(),
                    $line->storeId(),
                    $line->productId(),
                    $stock->id(),
                    $type,
                    $quantity,
                    $previous,
                    StockMovementSource::stockCount($line->stockCountId()),
                    $movementReason,
                    $command->actorContext->actorId(),
                    $occurredAt,
                );
                $this->stocks->save($stock);
                if (!$this->movements->appendOnce($movement)) {
                    throw InventoryRuleViolation::with('STOCK_COUNT_RECONCILIATION_REPLAY_CONFLICT', 'A stock count correction already exists for a pending line.');
                }
                $this->costing->value(new ValueInventoryMovement(
                    $line->storeId(),
                    $line->productId(),
                    $stock->id(),
                    $movement->id(),
                    $comparison > 0 ? InventoryCostingMovementType::StockCountCorrectionIn : InventoryCostingMovementType::StockCountCorrectionOut,
                    $quantity->value(),
                    $movement->previousQuantity()->value(),
                    $movement->resultingQuantity()->value(),
                    $costAssignment?->manualUnitCost,
                    $line->stockCountId()->toString(),
                    $occurredAt,
                    $command->actorContext,
                ));
            }
        }
        $line->markReconciled();
        $this->lines->save($line);
    }

    private function stockQuantity(string $value): StockQuantity
    {
        return new StockQuantity(Quantity::fromString($value, $this->decimals));
    }
}
