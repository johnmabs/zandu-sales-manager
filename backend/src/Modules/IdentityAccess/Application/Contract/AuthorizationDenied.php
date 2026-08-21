<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

use RuntimeException;
use Zandu\SharedKernel\Access\PermissionCode;

final class AuthorizationDenied extends RuntimeException
{
    public static function forPermission(PermissionCode $permission): self
    {
        return new self(sprintf('Permission "%s" is required for this operation.', $permission->value));
    }
}
