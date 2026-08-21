<?php

declare(strict_types=1);

namespace Zandu\Modules\IdentityAccess\Domain\Access;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class ScopedStore
{
    public function __construct(
        private StoreId $storeId,
        private OrganizationId $organizationId,
    ) {}

    public function storeId(): StoreId
    {
        return $this->storeId;
    }

    public function belongsTo(OrganizationId $organizationId): bool
    {
        return $this->organizationId->equals($organizationId);
    }
}
