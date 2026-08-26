<?php
declare(strict_types=1);
namespace Zandu\Modules\Inventory\Application;
use Zandu\Modules\Inventory\Application\Contract\{StockAvailability,StockAvailabilityProvider};
use Zandu\Modules\Inventory\Domain\Stock\StockRepository;
use Zandu\SharedKernel\Decimal\DecimalFactory;
use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,StoreId};
use Zandu\SharedKernel\Quantity\Quantity;
final readonly class RepositoryStockAvailabilityProvider implements StockAvailabilityProvider
{
    public function __construct(private StockRepository $stocks, private DecimalFactory $decimals) {}
    public function provide(OrganizationId $organizationId, StoreId $storeId, ProductId $productId): StockAvailability
    {
        $stock=$this->stocks->find($organizationId,$storeId,$productId);
        if (null === $stock) return new StockAvailability($productId, Quantity::fromString('0', $this->decimals), 0, false);
        return new StockAvailability($productId,$stock->quantityOnHand()->value(),$stock->version(),$stock->initialized());
    }
}
