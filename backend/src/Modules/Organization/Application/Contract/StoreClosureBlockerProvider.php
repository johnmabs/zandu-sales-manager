<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

interface StoreClosureBlockerProvider
{
    /** @return list<string> Human-readable blocker codes supplied by operational modules. */
    public function blockers(OrganizationId $organizationId, StoreId $storeId): array;
}
