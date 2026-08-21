<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Invitation\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\OrganizationInvitationId;

abstract readonly class OrganizationInvitationEvent
{
    public function __construct(
        private OrganizationInvitationId $invitationId,
        private OrganizationId $organizationId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function invitationId(): OrganizationInvitationId
    {
        return $this->invitationId;
    }
    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function actorId(): ActorId
    {
        return $this->actorId;
    }
    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
