<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CreateStockCount;

use Zandu\Modules\Catalog\Application\Contract\InventoryProductProvider;
use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountRepository, StockCountScopeType};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Identity\{IdGenerator, OutboxMessageId, StockCountId};
use Zandu\SharedKernel\Messaging\{OutboxMessage, OutboxRepository};
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class CreateStockCountHandler
{
    public function __construct(private StockCountRepository $stockCounts, private InventoryProductProvider $products, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private OutboxRepository $outbox, private IdGenerator $ids, private Clock $clock) {}

    public function __invoke(CreateStockCount $command): StockCount
    {
        $organizationId = $command->actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($command, $organizationId): StockCount {
            $this->authorization->authorize($command->actorContext, PermissionCode::StockCountCreate, ResourceScope::store($organizationId, $command->storeId));
            $this->guard->assertStore($command->actorContext, $command->storeId);
            if (StockCountScopeType::Partial === $command->scopeType) {
                foreach ($command->productIds as $productId) {
                    $descriptor = $this->products->provide($organizationId, $productId);
                    if (!$descriptor->inventoryTracked() || 'PHYSICAL' !== $descriptor->productType()) {
                        throw InventoryRuleViolation::with('STOCK_COUNT_PRODUCT_INELIGIBLE', 'Stock count scope contains a product that is not physically tracked.');
                    }
                }
            }
            $now = $this->clock->now();
            $stockCount = StockCount::create(StockCountId::generate($this->ids), $organizationId, $command->storeId, $command->scopeType, $command->actorContext->actorId(), $now, $command->mode, $command->productIds);
            $this->stockCounts->save($stockCount);
            $this->outbox->append(new OutboxMessage(
                OutboxMessageId::generate($this->ids),
                $organizationId,
                'inventory.stock_count_created.v1',
                ['stockCountId' => $stockCount->id()->toString(), 'storeId' => $stockCount->storeId()->toString(), 'scopeType' => $stockCount->scopeType()->value, 'mode' => $stockCount->mode()->value, 'requestedProductCount' => count($stockCount->requestedProductIds())],
                $command->actorContext->correlationId(),
                $command->actorContext->causationId(),
                $now,
            ));

            return $stockCount;
        });
    }
}
