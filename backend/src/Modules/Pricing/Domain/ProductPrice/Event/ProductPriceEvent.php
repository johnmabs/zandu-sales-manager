<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\ProductPrice\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductPriceId;

abstract readonly class ProductPriceEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private ProductPriceId $productPriceId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function productPriceId(): ProductPriceId
    {
        return $this->productPriceId;
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
