<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation;

use RuntimeException;
use Zandu\SharedKernel\Error\ResourceNotFound;

final class OrganizationInvitationNotFound extends RuntimeException implements ResourceNotFound
{
    public static function forToken(): self
    {
        return new self('Organization invitation was not found.');
    }
}
