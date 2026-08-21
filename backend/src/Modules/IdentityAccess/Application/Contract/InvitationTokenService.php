<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;

interface InvitationTokenService
{
    public function issue(OrganizationId $organizationId): IssuedInvitationToken;

    public function hash(string $rawToken): string;

    public function organizationId(string $rawToken): OrganizationId;
}
