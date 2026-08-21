<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;

interface MembershipManagementPolicy
{
    public function assertCanManage(ActorContext $actorContext): void;
}
