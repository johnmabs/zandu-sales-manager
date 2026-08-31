<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Application\CancelPurchaseReturn;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\PurchaseReturnId;

final readonly class CancelPurchaseReturn
{
    public function __construct(
        public PurchaseReturnId $purchaseReturnId,
        public ActorContext $actorContext,
    ) {}
}
