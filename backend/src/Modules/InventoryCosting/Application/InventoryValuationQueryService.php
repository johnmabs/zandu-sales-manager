<?php

declare(strict_types=1);

namespace Zandu\Modules\InventoryCosting\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\InventoryCosting\Domain\InventoryCostingRuleViolation;
use Zandu\Modules\InventoryCosting\Domain\Valuation\{StockValuation, StockValuationRepository};
use Zandu\Modules\InventoryCosting\Domain\ValuationMovement\StockValuationMovementRepository;
use Zandu\SharedKernel\Access\{PermissionCode, ResourceScope};
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId, StoreId};

final readonly class InventoryValuationQueryService
{
    public function __construct(
        private StockValuationRepository $valuations,
        private StockValuationMovementRepository $movements,
        private AuthorizationService $authorization,
        private StockValuationViewFactory $valuationViews,
        private StockValuationMovementViewFactory $movementViews,
    ) {}

    /** @return list<StockValuationView> */
    public function list(ActorContext $actor, StoreId $storeId): array
    {
        $this->authorize($actor, $storeId);

        return array_map(
            $this->valuationViews->from(...),
            $this->valuations->findByStore($actor->organizationId(), $storeId),
        );
    }

    public function get(ActorContext $actor, StoreId $storeId, ProductId $productId): StockValuationView
    {
        $this->authorize($actor, $storeId);

        return $this->valuationViews->from($this->find($actor, $storeId, $productId));
    }

    /** @return list<StockValuationMovementView> */
    public function movements(ActorContext $actor, StoreId $storeId, ProductId $productId): array
    {
        $this->authorize($actor, $storeId);
        $valuation = $this->find($actor, $storeId, $productId);

        return array_map(
            $this->movementViews->from(...),
            $this->movements->findByValuation($actor->organizationId(), $valuation->id()),
        );
    }

    private function authorize(ActorContext $actor, StoreId $storeId): void
    {
        $this->authorization->authorize(
            $actor,
            PermissionCode::InventoryRead,
            ResourceScope::store($actor->organizationId(), $storeId),
        );
    }

    private function find(ActorContext $actor, StoreId $storeId, ProductId $productId): StockValuation
    {
        foreach ($this->valuations->findByStore($actor->organizationId(), $storeId) as $valuation) {
            if ($valuation->productId()->equals($productId)) {
                return $valuation;
            }
        }

        throw InventoryCostingRuleViolation::with(
            'VALUATION_NOT_INITIALIZED',
            'Stock valuation must be initialized.',
        );
    }
}
