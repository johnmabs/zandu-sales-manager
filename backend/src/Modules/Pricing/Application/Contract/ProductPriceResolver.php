<?php

declare(strict_types=1);

namespace Zandu\Modules\Pricing\Application\Contract;

use DateTimeImmutable;
use Zandu\SharedKernel\Identity\OrganizationId;
use Zandu\SharedKernel\Identity\ProductId;
use Zandu\SharedKernel\Identity\ProductPackagingId;

interface ProductPriceResolver
{
    public function resolve(
        OrganizationId $organizationId,
        ProductId $productId,
        ProductPackagingId $packagingId,
        DateTimeImmutable $businessInstant,
    ): ResolvedProductPrice;
}
