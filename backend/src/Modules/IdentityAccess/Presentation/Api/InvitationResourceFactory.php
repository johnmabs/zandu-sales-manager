<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Presentation\Api;

use Zandu\Modules\IdentityAccess\Application\AcceptedInvitationView;
use Zandu\Modules\IdentityAccess\Application\CreatedInvitationView;
use Zandu\Modules\IdentityAccess\Application\InvitationView;

final readonly class InvitationResourceFactory
{
    public function fromView(InvitationView $invitation): InvitationResource
    {
        return new InvitationResource(
            $invitation->id,
            $invitation->organizationId,
            $invitation->email,
            $invitation->status,
            $invitation->expiresAt,
            $invitation->roleAssignments,
            $invitation->acceptedAt,
            $invitation->version,
        );
    }

    public function fromCreated(CreatedInvitationView $created): CreatedInvitationResource
    {
        return new CreatedInvitationResource($this->fromView($created->invitation), $created->token);
    }

    public function fromAccepted(AcceptedInvitationView $accepted): AcceptedInvitationResource
    {
        return new AcceptedInvitationResource(
            $accepted->membershipId,
            $accepted->organizationId,
            $accepted->userId,
            $accepted->status,
            $accepted->authorizationVersion,
        );
    }
}
