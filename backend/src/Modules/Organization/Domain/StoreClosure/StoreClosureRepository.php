<?php

declare(strict_types=1);

namespace Zandu\Modules\Organization\Domain\StoreClosure;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\StoreId;

interface StoreClosureRepository
{
    public function save(StoreClosure $closure): void;

    public function getActiveForStore(OrganizationId $organizationId, StoreId $storeId): StoreClosure;
}
