<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CompleteStockCountFinalization;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StockCountId;

final readonly class CompleteStockCountFinalization
{
    public function __construct(public StockCountId $stockCountId, public ActorContext $actorContext) {}
}
