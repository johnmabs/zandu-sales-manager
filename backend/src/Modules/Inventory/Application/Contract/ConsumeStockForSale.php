<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,SaleId,StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class ConsumeStockForSale
{
    /** @param non-empty-list<array{productId: ProductId, baseQuantity: Quantity}> $items */
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public SaleId $saleId,
        public array $items,
    ) {}
}
