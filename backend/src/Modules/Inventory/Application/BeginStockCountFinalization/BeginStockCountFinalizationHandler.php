<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\BeginStockCountFinalization;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLineRepository, StockCountRepository, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class BeginStockCountFinalizationHandler
{
    public function __construct(private StockCountRepository $stockCounts, private StockCountLineRepository $lines, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    public function __invoke(BeginStockCountFinalization $command): StockCount
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockCount {
            $stockCount = $this->stockCounts->getForUpdate($organizationId, $command->stockCountId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockCountFinalize, ResourceScope::store($organizationId, $stockCount->storeId()));
            if (StockCountStatus::Finalizing === $stockCount->status()) {
                return $stockCount;
            }
            if (StockCountStatus::Open !== $stockCount->status()) {
                throw InventoryRuleViolation::with('STOCK_COUNT_NOT_OPEN', 'Only an open stock count can begin finalization.');
            }
            $this->guard->assertStore($command->actorContext, $stockCount->storeId(), OperationalMode::Remediation);
            if ($this->lines->countUncountedForUpdate($organizationId, $stockCount->id()) > 0) {
                throw InventoryRuleViolation::with('STOCK_COUNT_HAS_UNCOUNTED_LINES', 'Every stock count line must be counted before finalization.');
            }
            $now = $this->clock->now();
            $stockCount->beginFinalization($command->actorContext->actorId(), $now);
            $this->stockCounts->save($stockCount);
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'inventory.stock_count_finalization_started.v1',
                ['stockCountId' => $stockCount->id()->toString(), 'storeId' => $stockCount->storeId()->toString(), 'lineCount' => $stockCount->totalLineCount()],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $stockCount;
        });
    }
}
