<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Application\Contract\AuthorizationService;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitationRepository;
use Zandu\SharedKernel\Access\PermissionCode;
use Zandu\SharedKernel\Access\ResourceScope;
use Zandu\SharedKernel\Context\ActorContext;

final readonly class InvitationQueryService
{
    public function __construct(
        private OrganizationInvitationRepository $invitations,
        private AuthorizationService $authorization,
        private InvitationViewFactory $views,
    ) {}

    /** @return list<InvitationView> */
    public function list(ActorContext $actor): array
    {
        $organizationId = $actor->organizationId();
        $this->authorization->authorize(
            $actor,
            PermissionCode::MemberInvite,
            ResourceScope::organization($organizationId),
        );

        return array_map($this->views->fromAggregate(...), $this->invitations->findAll($organizationId));
    }
}
