<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

enum RoleStatus: string
{
    case Active = 'ACTIVE';
    case Archived = 'ARCHIVED';
}
