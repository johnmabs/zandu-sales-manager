<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\StartStockCount;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class StartStockCount
{
    public function __construct(public StockCountId $stockCountId, public ActorContext $actorContext) {}
}
