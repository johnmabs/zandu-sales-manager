<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

enum RoleType: string
{
    case System = 'SYSTEM';
    case Custom = 'CUSTOM';
}
