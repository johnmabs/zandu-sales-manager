<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\Inventory\Domain\StockCount\{StockCount, StockCountLineRepository, StockCountRepository};
use Zandu\SharedKernel\Access\{AuthorizationDenied, PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class StockCountQueryService
{
    public function __construct(
        private StockCountRepository $stockCounts,
        private StockCountLineRepository $lines,
        private AuthorizationService $authorization,
        private StockCountViewFactory $views,
    ) {}

    public function get(ActorContext $actor, StockCountId $stockCountId): StockCountView
    {
        $stockCount = $this->stockCounts->get($actor->organizationId(), $stockCountId);
        $this->authorizeRead($actor, $stockCount);

        return $this->view($stockCount);
    }

    /** @return list<StockCountView> */
    public function list(ActorContext $actor): array
    {
        $views = [];
        foreach ($this->stockCounts->findAll($actor->organizationId()) as $stockCount) {
            try {
                $this->authorizeRead($actor, $stockCount);
            } catch (AuthorizationDenied) {
                continue;
            }
            $views[] = $this->view($stockCount);
        }

        return $views;
    }

    private function view(StockCount $stockCount): StockCountView
    {
        return $this->views->create(
            $stockCount,
            $this->lines->findByStockCount($stockCount->organizationId(), $stockCount->id()),
        );
    }

    private function authorizeRead(ActorContext $actor, StockCount $stockCount): void
    {
        $this->authorization->authorize(
            $actor,
            PermissionCode::StockCountRead,
            ResourceScope::store($actor->organizationId(), $stockCount->storeId()),
        );
    }
}
