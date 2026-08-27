<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashRegister;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\{CashRegisterId, StoreId};

final readonly class UpdateCashRegister
{
    public function __construct(public CashRegisterId $id, public StoreId $storeId, public string $code, public string $name, public ActorContext $actorContext) {}
}
