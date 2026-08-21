<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Membership;

use RuntimeException;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;

final class OrganizationMembershipNotFound extends RuntimeException
{
    public static function withId(OrganizationMembershipId $id): self
    {
        return new self(sprintf('Organization membership "%s" was not found.', $id->toString()));
    }
}
