<?php

declare(strict_types=1);

namespace Zandu\Modules\Inventory\Application\CreateStockTransfer;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{StoreId};

final readonly class CreateStockTransfer
{
    public function __construct(public StoreId $sourceStoreId, public StoreId $destinationStoreId, public ActorContext $actorContext) {}
}
