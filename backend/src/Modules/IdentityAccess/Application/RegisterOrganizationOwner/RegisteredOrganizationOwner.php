<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterOrganizationOwner;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;

final readonly class RegisteredOrganizationOwner
{
    public function __construct(public UserId $userId, public OrganizationId $organizationId) {}
}
