<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\CancelOrganizationInvitation;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;

final readonly class CancelOrganizationInvitation
{
    public function __construct(public OrganizationInvitationId $invitationId, public ActorContext $actorContext) {}
}
