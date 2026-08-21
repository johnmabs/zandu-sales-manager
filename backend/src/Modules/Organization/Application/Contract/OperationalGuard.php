<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\Modules\Organization\Domain\Organization;
use Zandu\Modules\Organization\Domain\Store\Store;
use Zandu\SharedKernel\Context\ActorContext;

interface OperationalGuard
{
    public function assertOrganization(
        Organization $organization,
        OperationalMode $mode = OperationalMode::Standard,
    ): void;

    public function assertStore(
        ActorContext $actorContext,
        Store $store,
        OperationalMode $mode = OperationalMode::Standard,
    ): void;
}
