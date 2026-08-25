<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Domain\Category\Event;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\ActorId;
use Zandu\SharedKernel\Identity\CategoryId;
use Zandu\SharedKernel\Identity\OrganizationId;

abstract readonly class CategoryEvent
{
    public function __construct(
        private OrganizationId $organizationId,
        private CategoryId $categoryId,
        private ActorId $actorId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function organizationId(): OrganizationId
    {
        return $this->organizationId;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
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
