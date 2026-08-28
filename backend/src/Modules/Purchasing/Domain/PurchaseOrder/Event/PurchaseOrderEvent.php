<?php

declare(strict_types=1);

namespace Zandu\Modules\Purchasing\Domain\PurchaseOrder\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PurchaseOrderId;

abstract readonly class PurchaseOrderEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private PurchaseOrderId $purchaseOrderId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function purchaseOrderId(): PurchaseOrderId
    {
        return $this->purchaseOrderId;
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
