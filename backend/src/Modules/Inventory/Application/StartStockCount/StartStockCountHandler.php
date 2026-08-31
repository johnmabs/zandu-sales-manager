<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StartStockCount;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockCount\{OpenStockCountScopeRepository, StockCount, StockCountLine, StockCountLineRepository, StockCountRepository, StockCountScopeType, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId, ProductId, StockCountLineId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Quantity\Quantity;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class StartStockCountHandler
{
    public function __construct(private StockCountRepository $stockCounts, private StockCountLineRepository $lines, private OpenStockCountScopeRepository $scopes, private StockRepository $stocks, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private OutboxRepository $outbox, private IdGenerator $ids, private DecimalFactory $decimals, private Clock $clock) {}

    public function __invoke(StartStockCount $command): StockCount
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockCount {
            $stockCount = $this->stockCounts->getForUpdate($organizationId, $command->stockCountId);
            $this->authorization->authorize($command->actorContext, PermissionCode::StockCountStart, ResourceScope::store($organizationId, $stockCount->storeId()));
            if (StockCountStatus::Open === $stockCount->status()) {
                return $stockCount;
            }
            if (StockCountStatus::Draft !== $stockCount->status()) {
                throw InventoryRuleViolation::with('STOCK_COUNT_NOT_DRAFT', 'Only a draft stock count can be started.');
            }
            $this->guard->assertStore($command->actorContext, $stockCount->storeId());
            $productIds = $this->resolveProductIds($stockCount);
            $this->scopes->acquire($organizationId, $stockCount->storeId(), $stockCount->id(), $productIds);
            foreach ($productIds as $productId) {
                $position = $this->stocks->find($organizationId, $stockCount->storeId(), $productId);
                if (null !== $position) {
                    $position = $this->stocks->getForUpdate($organizationId, $stockCount->storeId(), $productId);
                }
                $this->lines->save(StockCountLine::create(
                    StockCountLineId::generate($this->ids),
                    $stockCount->id(),
                    $organizationId,
                    $stockCount->storeId(),
                    $productId,
                    $position?->quantityOnHand()->value() ?? Quantity::fromString('0', $this->decimals),
                ));
            }
            $now = $this->clock->now();
            $stockCount->start($command->actorContext->actorId(), $now, count($productIds));
            $this->stockCounts->save($stockCount);
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'inventory.stock_count_started.v1',
                ['stockCountId' => $stockCount->id()->toString(), 'storeId' => $stockCount->storeId()->toString(), 'lineCount' => count($productIds), 'mode' => $stockCount->mode()->value],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $stockCount;
        });
    }

    /** @return list<ProductId> */
    private function resolveProductIds(StockCount $stockCount): array
    {
        $productIds = StockCountScopeType::Partial === $stockCount->scopeType()
            ? $stockCount->requestedProductIds()
            : array_map(static fn($stock): ProductId => $stock->productId(), $this->stocks->findByStore($stockCount->organizationId(), $stockCount->storeId()));
        usort($productIds, static fn(ProductId $left, ProductId $right): int => $left->toString() <=> $right->toString());

        return $productIds;
    }
}
