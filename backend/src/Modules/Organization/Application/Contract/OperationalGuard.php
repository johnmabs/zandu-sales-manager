<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

interface OperationalGuard
{
    public function assertTenant(
        ActorContext $actorContext,
        OperationalMode $mode = OperationalMode::Standard,
    ): void;

    public function assertStore(
        ActorContext $actorContext,
        StoreId $storeId,
        OperationalMode $mode = OperationalMode::Standard,
    ): void;
}
