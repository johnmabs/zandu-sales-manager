<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Domain\PriceList\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\PriceListId;

abstract readonly class PriceListEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private PriceListId $priceListId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }
    public function priceListId(): PriceListId
    {
        return $this->priceListId;
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
