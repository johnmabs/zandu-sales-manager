<?php

declare(strict_types=1);

namespace Zandu\Modules\Catalog\Application\Contract;

use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

interface SaleablePackagingSnapshotProvider
{
    public function provide(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
    ): SaleablePackagingSnapshot;
}
