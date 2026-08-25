<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\UnitOfMeasureId;

interface BasePackagingPresence
{
    public function exists(
        OrganizationId $organizationId,
        ProductId $productId,
        UnitOfMeasureId $baseUnitId,
    ): bool;
}
