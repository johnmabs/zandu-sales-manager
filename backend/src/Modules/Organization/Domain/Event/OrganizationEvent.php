<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;

abstract readonly class OrganizationEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

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
