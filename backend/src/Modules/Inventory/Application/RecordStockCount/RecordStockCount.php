<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\RecordStockCount;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{ProductId, StockCountId};
use Zandu\SharedKernel\Quantity\Quantity;

final readonly class RecordStockCount
{
    public function __construct(public StockCountId $stockCountId, public ProductId $productId, public Quantity $countedQuantity, public int $expectedLineVersion, public ActorContext $actorContext) {}
}
