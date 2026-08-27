<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Application\Contract\{CostingStockPosition, CostingStockPositionProvider};
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, StoreId};

final readonly class RepositoryCostingStockPositionProvider implements CostingStockPositionProvider
{
    public function __construct(private StockRepository $stocks) {}

    public function getForUpdate(
        OrganizationId $organizationId,
        StoreId $storeId,
        ProductId $productId,
    ): CostingStockPosition {
        $stock = $this->stocks->getForUpdate($organizationId, $storeId, $productId);

        return new CostingStockPosition(
            $stock->id(),
            $stock->storeId(),
            $stock->productId(),
            $stock->quantityOnHand()->value(),
            $stock->version(),
        );
    }
}
