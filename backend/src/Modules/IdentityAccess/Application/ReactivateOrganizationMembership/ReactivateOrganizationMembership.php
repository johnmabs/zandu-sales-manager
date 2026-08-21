<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application\ReactivateOrganizationMembership;

use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;

final readonly class ReactivateOrganizationMembership
{
    public function __construct(public OrganizationMembershipId $membershipId, public ActorContext $actorContext) {}
}
