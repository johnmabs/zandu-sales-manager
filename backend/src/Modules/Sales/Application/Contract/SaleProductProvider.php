<?php

declare(strict_types=1);

namespace Zandu\Modules\Sales\Application\Contract;

use Zandu\SharedKernel\Identity\{OrganizationId,ProductId,ProductPackagingId};

interface SaleProductProvider
{
    public function provide(OrganizationId $organizationId, ProductId $productId, ProductPackagingId $productPackagingId): SaleProductDescriptor;
}
