<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Access\SystemRoleCatalog;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class RoleQueryService
{
    public function __construct(
        private SystemRoleCatalog $roles,
        private AuthorizationService $authorization,
    ) {}

    /** @return non-empty-list<RoleView> */
    public function list(ActorContext $actor): array
    {
        $this->authorization->authorize($actor, PermissionCode::RoleRead, ResourceScope::organization($actor->organizationId()));

        return array_map(static fn($role): RoleView => new RoleView(
            $role->id()->toString(),
            $role->code()->value(),
            $role->type()->value,
            $role->status()->value,
            $role->name(),
            $role->description(),
            array_map(static fn(PermissionCode $permission): string => $permission->value, $role->permissions()),
            $role->version(),
        ), $this->roles->roles());
    }
}
