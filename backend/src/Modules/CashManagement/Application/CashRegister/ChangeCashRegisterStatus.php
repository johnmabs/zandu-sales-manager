<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashRegister;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashRegisterId, StoreId};

final readonly class ChangeCashRegisterStatus
{
    public function __construct(public CashRegisterId $id, public StoreId $storeId, public string $action, public ActorContext $actorContext) {}
}
