<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Application;

use Zandu\Modules\IdentityAccess\Application\InviteOrganizationMember\CreatedOrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Invitation\OrganizationInvitation;
use Zandu\Modules\IdentityAccess\Domain\Membership\OrganizationMembership;

final readonly class InvitationViewFactory
{
    public function fromAggregate(OrganizationInvitation $invitation): InvitationView
    {
        $assignments = array_map(static fn($assignment): array => [
            'roleCode' => $assignment->roleCode(),
            'storeIds' => array_map(static fn($storeId): string => $storeId->toString(), $assignment->storeIds()),
        ], $invitation->intendedRoleAssignments());

        return new InvitationView(
            $invitation->id()->toString(),
            $invitation->organizationId()->toString(),
            $invitation->email()->value(),
            $invitation->status()->value,
            $invitation->expiresAt()->format(DATE_ATOM),
            $assignments,
            $invitation->acceptedAt()?->format(DATE_ATOM),
            $invitation->version(),
        );
    }

    public function fromCreated(CreatedOrganizationInvitation $created): CreatedInvitationView
    {
        return new CreatedInvitationView($this->fromAggregate($created->invitation), $created->revealToken());
    }

    public function fromMembership(OrganizationMembership $membership): AcceptedInvitationView
    {
        return new AcceptedInvitationView(
            $membership->id()->toString(),
            $membership->organizationId()->toString(),
            $membership->userId()->toString(),
            $membership->status()->value,
            $membership->authorizationVersion(),
        );
    }
}
