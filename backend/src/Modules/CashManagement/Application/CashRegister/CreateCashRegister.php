<?php

declare(strict_types=1);

namespace Zandu\Modules\CashManagement\Application\CashRegister;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class CreateCashRegister
{
    public function __construct(public StoreId $storeId, public string $code, public string $name, public ActorContext $actorContext) {}
}
