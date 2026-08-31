<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application;

use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Inventory\Domain\StockTransfer\StockTransferRepository;
use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\SharedKernel\Identity\{OrganizationId,StoreId};

final readonly class InventoryStoreClosureBlockerProvider implements StoreClosureBlockerProvider
{
    public function __construct(private StockRepository $stocks, private StockTransferRepository $transfers) {}
    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        $blockers = [];
        if ($this->transfers->hasInTransitForStore($organizationId, $storeId)) {
            $blockers[] = 'STOCK_TRANSFER_IN_TRANSIT';
        }
        foreach ($this->stocks->findByStore($organizationId, $storeId) as $stock) {
            if (!$stock->quantityOnHand()->value()->isZero()) {
                $blockers[] = 'STOCK_REMAINING';
                break;
            }
        }
        return $blockers;
    }
}
