<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

interface PasswordHasher
{
    public function hash(string $plainPassword): string;
}
