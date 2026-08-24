<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembershipRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;
use Zandu\SharedKernel\Identity\OrganizationMembershipId;

final readonly class MembershipQueryService
{
    public function __construct(
        private OrganizationMembershipRepository $memberships,
        private AuthorizationService $authorization,
        private MembershipViewFactory $views,
    ) {}

    /** @return list<MembershipView> */
    public function list(ActorContext $actor): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::MemberRead, ResourceScope::organization($organizationId));

        return array_map($this->views->fromAggregate(...), $this->memberships->findAll($organizationId));
    }

    public function get(OrganizationMembershipId $id, ActorContext $actor): MembershipView
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize($actor, PermissionCode::MemberRead, ResourceScope::organization($organizationId));

        return $this->views->fromAggregate($this->memberships->get($organizationId, $id));
    }
}
