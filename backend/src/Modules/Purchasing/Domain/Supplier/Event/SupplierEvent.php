<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\Supplier\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\SupplierId;

abstract readonly class SupplierEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private SupplierId $supplierId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function supplierId(): SupplierId
    {
        return $this->supplierId;
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
