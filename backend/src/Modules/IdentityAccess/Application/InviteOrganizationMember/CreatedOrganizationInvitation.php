<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember;

use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;

final readonly class CreatedOrganizationInvitation
{
    public function __construct(public OrganizationInvitation $invitation, private string $rawToken) {}

    public function revealToken(): string
    {
        return $this->rawToken;
    }
}
