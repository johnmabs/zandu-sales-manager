<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

final readonly class NoStoreClosureBlockers implements StoreClosureBlockerProvider
{
    public function blockers(OrganizationId $organizationId, StoreId $storeId): array
    {
        return [];
    }
}
