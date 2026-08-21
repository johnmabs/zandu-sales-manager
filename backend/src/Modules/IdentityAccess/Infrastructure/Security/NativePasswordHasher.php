<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Infrastructure\Security;

use SensitiveParameter;
use Zandu\Modules\IdentityAccess\Application\Contract\PasswordHasher;

final readonly class NativePasswordHasher implements PasswordHasher
{
    public function hash(#[SensitiveParameter] string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_ARGON2ID);
    }
}
