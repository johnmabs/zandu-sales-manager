<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;

interface MembershipAccessGuard
{
    public function assertFreshActiveMembership(ActorContext $actorContext): void;
}
