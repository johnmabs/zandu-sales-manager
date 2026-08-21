<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\ExpireOrganizationInvitations;

use Zandu\SharedKernel\Context\ActorContext;

final readonly class ExpireOrganizationInvitations
{
    public function __construct(public ActorContext $actorContext, public int $limit = 100) {}
}
