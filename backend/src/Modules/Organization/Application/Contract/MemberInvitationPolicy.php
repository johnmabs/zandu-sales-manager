<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

interface MemberInvitationPolicy
{
    /**
     * @param list<StoreId> $selectedStoreIds
     */
    public function assertCanInvite(ActorContext $actorContext, array $selectedStoreIds): void;
}
