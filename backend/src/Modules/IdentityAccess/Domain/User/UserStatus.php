<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\User;

enum UserStatus: string
{
    case Active = 'ACTIVE';
    case Disabled = 'DISABLED';
}
