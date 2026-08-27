<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashSession;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashRegisterId, StoreId};
use Zandu\SharedKernel\Money\Money;

final readonly class OpenCashSession
{
    public function __construct(public StoreId $storeId, public CashRegisterId $cashRegisterId, public Money $openingBalance, public ActorContext $actorContext) {}
}
