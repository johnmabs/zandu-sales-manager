<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Product\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;

abstract readonly class ProductEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private ProductId $productId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function productId(): ProductId
    {
        return $this->productId;
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
