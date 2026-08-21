<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\RevokeOrganizationMembership;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;

final readonly class RevokeOrganizationMembership
{
    public function __construct(public OrganizationMembershipId $membershipId, public ActorContext $actorContext) {}
}
