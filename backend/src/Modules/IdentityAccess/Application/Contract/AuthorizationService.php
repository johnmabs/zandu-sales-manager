<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;

interface AuthorizationService
{
    /**
     * Must be invoked inside the tenant transaction that performs the protected operation.
     *
     * @throws AuthorizationDenied
     */
    public function authorize(
        ActorContext $actorContext,
        PermissionCode $permission,
        ResourceScope $resourceScope,
    ): void;
}
