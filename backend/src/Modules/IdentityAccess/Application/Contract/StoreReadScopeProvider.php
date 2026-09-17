<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\StoreId;

interface StoreReadScopeProvider
{
    /**
     * Returns null for organization-wide access, otherwise the store ids visible to the actor.
     *
     * @return list<StoreId>|null
     */
    public function visibleStoreIds(ActorContext $actorContext): ?array;
}
