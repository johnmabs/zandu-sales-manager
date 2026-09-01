<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CancelStockCount;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockCount\{OpenStockCountScopeRepository, StockCount, StockCountRepository, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\{OperationalGuard, OperationalMode};
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CancelStockCountHandler
{
    public function __construct(
        private StockCountRepository $stockCounts,
        private OpenStockCountScopeRepository $scopes,
        private TenantTransaction $transaction,
        private AuthorizationService $authorization,
        private OperationalGuard $guard,
        private OutboxRepository $outbox,
        private IdGenerator $ids,
        private Clock $clock,
    ) {}

    public function __invoke(CancelStockCount $command): StockCount
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockCount {
            $stockCount = $this->stockCounts->getForUpdate($organizationId, $command->stockCountId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockCountCancel, ResourceScope::store($organizationId, $stockCount->storeId()));
            if (StockCountStatus::Cancelled === $stockCount->status()) {
                return $stockCount;
            }
            $this->guard->assertStore($command->actorContext, $stockCount->storeId(), OperationalMode::Remediation);
            $previousStatus = $stockCount->status();
            $now = $this->clock->now();
            $stockCount->cancel($command->actorContext->actorId(), $now);
            $this->stockCounts->save($stockCount);
            if (StockCountStatus::Open === $previousStatus) {
                $this->scopes->release($organizationId, $stockCount->id());
            }
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'inventory.stock_count_cancelled.v1',
                ['stockCountId' => $stockCount->id()->toString(), 'storeId' => $stockCount->storeId()->toString(), 'previousStatus' => $previousStatus->value],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $stockCount;
        });
    }
}
