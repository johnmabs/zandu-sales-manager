<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Access;

use RuntimeException;
use Zandu\SharedKernel\Context\ActorContext;

final class AuthorizationDenied extends RuntimeException
{
    private function __construct(
        public readonly ActorContext $actorContext,
        public readonly PermissionCode $permission,
        public readonly ResourceScope $resourceScope,
    ) {
        parent::__construct(sprintf('Permission "%s" is required for this operation.', $permission->value));
    }

    public static function forPermission(ActorContext $actorContext, PermissionCode $permission, ResourceScope $resourceScope): self
    {
        return new self($actorContext, $permission, $resourceScope);
    }
}
