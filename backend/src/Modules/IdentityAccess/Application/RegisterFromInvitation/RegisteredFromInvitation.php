<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RegisterFromInvitation;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UserId;

final readonly class RegisteredFromInvitation
{
    public function __construct(public UserId $userId, public OrganizationId $organizationId) {}
}
