<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use RuntimeException;

final class OrganizationInvitationNotFound extends RuntimeException
{
    public static function forToken(): self
    {
        return new self('Organization invitation was not found.');
    }
}
