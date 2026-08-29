<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CreatePurchaseOrder;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;
use Zandu\SharedKernel\Identity\SupplierId;

final readonly class CreatePurchaseOrder
{
    public function __construct(
        public StoreId $destinationStoreId,
        public SupplierId $supplierId,
        public string $number,
        public string $currency,
        public ActorContext $actorContext,
    ) {}
}
