<?php

declare(strict_types=1);

namespace Zandu\Tests\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Application\Contract\ResourceScope;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Context\ActorContext;

final class AllowAllAuthorizationService implements AuthorizationService
{
    public function authorize(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): void {}
}
