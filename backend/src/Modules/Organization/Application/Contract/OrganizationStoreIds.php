<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

interface OrganizationStoreIds
{
    /** @return list<StoreId> */
    public function forOrganization(OrganizationId $organizationId): array;
}
