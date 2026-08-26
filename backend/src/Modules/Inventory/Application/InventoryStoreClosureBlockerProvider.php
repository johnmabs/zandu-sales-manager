<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application;
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\Modules\Organization\Application\Contract\StoreClosureBlockerProvider;
use Zandu\SharedKernel\Identity\{OrganizationId,StoreId};
final readonly class InventoryStoreClosureBlockerProvider implements StoreClosureBlockerProvider
{
    public function __construct(private StockRepository $stocks) {}
    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        foreach ($this->stocks->findByStore($organizationId, $storeId) as $stock) if (!$stock->quantityOnHand()->value()->isZero()) return ['STOCK_REMAINING'];
        return [];
    }
}
