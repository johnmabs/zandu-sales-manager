<?php

declare(strict_types=1);

namespace Zandu\SharedKernel\Access;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class ResourceScope
{
    private function __construct(
        public OrganizationId $organizationId,
        public ?StoreId $storeId,
    ) {}

    public static function organization(OrganizationId $organizationId): self
    {
        return new self($organizationId, null);
    }

    public static function store(OrganizationId $organizationId, StoreId $storeId): self
    {
        return new self($organizationId, $storeId);
    }
}
