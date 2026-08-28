<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{OrganizationId, ProductId, ReturnSaleId, StoreId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RestockSaleReturn
{
    /** @param non-empty-list<array{productId: ProductId, baseQuantity: Quantity}> $items */
    public function __construct(
        public OrganizationId $organizationId,
        public StoreId $storeId,
        public ReturnSaleId $returnSaleId,
        public array $items,
        public ActorContext $actorContext,
    ) {}
}
