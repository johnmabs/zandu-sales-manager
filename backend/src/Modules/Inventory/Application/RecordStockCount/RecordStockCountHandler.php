<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\RecordStockCount;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\InventoryRuleViolation;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLine, StockCountLineRepository, StockCountRepository, StockCountStatus};
use Zandu\Modules\Organization\Application\Contract\OperationalGuard;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;
use Zandu\SharedKernel\Tenancy\TenantTransaction;
use Zandu\SharedKernel\Time\Clock;

final readonly class RecordStockCountHandler
{
    public function __construct(private StockCountRepository $stockCounts, private StockCountLineRepository $lines, private TenantTransaction $transaction, private AuthorizationService $authorization, private OperationalGuard $guard, private Clock $clock) {}

    public function __invoke(RecordStockCount $command): StockCountLine
    {
        return $this->record($command->stockCountId, [new StockCountEntry($command->productId, $command->countedQuantity, $command->expectedLineVersion)], $command->actorContext)[0];
    }

    /** @return non-empty-list<StockCountLine> */
    public function batch(RecordStockCountBatch $command): array
    {
        return $this->record($command->stockCountId, $command->entries, $command->actorContext);
    }

    /**
     * @param non-empty-list<StockCountEntry> $entries
     * @return non-empty-list<StockCountLine>
     */
    private function record(StockCountId $stockCountId, array $entries, ActorContext $actorContext): array
    {
        $organizationId = $actorContext->organizationId();

        return $this->transaction->transactional($organizationId, function () use ($stockCountId, $entries, $actorContext, $organizationId): array {
            $stockCount = $this->stockCounts->getForUpdate($organizationId, $stockCountId);
            $this->authorization->authorize($actorContext, PermissionCode::StockCountRecord, ResourceScope::store($organizationId, $stockCount->storeId()));
            if (StockCountStatus::Open !== $stockCount->status()) {
                throw InventoryRuleViolation::with('STOCK_COUNT_NOT_OPEN', 'Stock count entries require an open stock count.');
            }
            $this->guard->assertStore($actorContext, $stockCount->storeId());
            $entriesByProduct = [];
            foreach ($entries as $entry) {
                $key = $entry->productId->toString();
                if (isset($entriesByProduct[$key])) {
                    throw InventoryRuleViolation::with('STOCK_COUNT_PRODUCT_DUPLICATE', 'A stock count batch can contain a product only once.');
                }
                $entriesByProduct[$key] = $entry;
            }
            ksort($entriesByProduct, SORT_STRING);
            $recorded = [];
            $newlyCounted = 0;
            $now = $this->clock->now();
            foreach ($entriesByProduct as $entry) {
                $line = $this->lines->getForUpdateByProduct($organizationId, $stockCount->id(), $entry->productId);
                if ($line->version() !== $entry->expectedLineVersion) {
                    throw InventoryRuleViolation::with('STOCK_COUNT_LINE_VERSION_CONFLICT', 'Stock count line was modified since it was read.');
                }
                $wasUncounted = null === $line->countedQuantity();
                $line->record($entry->countedQuantity, $actorContext->actorId(), $now);
                $this->lines->save($line);
                $recorded[] = $line;
                $newlyCounted += (int) $wasUncounted;
            }
            $stockCount->registerCountedLines($newlyCounted);
            if ($newlyCounted > 0) {
                $this->stockCounts->save($stockCount);
            }

            return $recorded;
        });
    }
}
