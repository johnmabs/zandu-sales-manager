<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\ShipPurchaseReturn;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseReturnId;

final readonly class ShipPurchaseReturn
{
    public function __construct(
        public PurchaseReturnId $purchaseReturnId,
        public ActorContext $actorContext,
        public string $commandId = '',
    ) {}
}
