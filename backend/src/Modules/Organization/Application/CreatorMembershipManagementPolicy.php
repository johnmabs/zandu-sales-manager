<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application;

use LogicException;
use Zandu\Modules\Organization\Application\Contract\MembershipManagementPolicy;
use Zandu\Modules\Organization\Domain\OrganizationRepository;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class CreatorMembershipManagementPolicy implements MembershipManagementPolicy
{
    public function __construct(private OrganizationRepository $organizations) {}
    public function assertCanManage(ActorContext $actorContext): void
    {
        $organization = $this->organizations->get($actorContext->organizationId());
        if (!$organization->createdBy()->equals($actorContext->actorId())) {
            throw new LogicException('The actor is not authorized to manage organization memberships.');
        }
    }
}
