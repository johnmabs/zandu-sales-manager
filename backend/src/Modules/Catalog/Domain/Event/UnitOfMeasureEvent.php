<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

abstract readonly class UnitOfMeasureEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private UnitOfMeasureId $unitOfMeasureId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function unitOfMeasureId(): UnitOfMeasureId
    {
        return $this->unitOfMeasureId;
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
