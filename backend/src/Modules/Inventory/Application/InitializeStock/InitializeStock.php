<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\InitializeStock;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Decimal\Decimal;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class InitializeStock
{
    public function __construct(public StoreId $storeId, public ProductId $productId, public Quantity $quantity, public Decimal $unitCost, public ActorContext $actorContext) {}
}
