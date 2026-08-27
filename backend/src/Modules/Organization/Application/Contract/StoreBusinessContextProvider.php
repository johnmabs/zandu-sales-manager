<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId, StoreId};

interface StoreBusinessContextProvider
{
    public function provide(OrganizationId $organizationId, StoreId $storeId): StoreBusinessContext;
}
